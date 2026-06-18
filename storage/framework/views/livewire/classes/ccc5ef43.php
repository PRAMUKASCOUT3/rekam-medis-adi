<?php

use Livewire\Component;
use App\Models\RekamMedis;
use App\Models\Pasien;
use App\Models\Obat;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

return new class extends Component {
    use WithPagination;

    // Form fields
    public ?int $pasien_id = null;
    public string $pasien_search = '';
    public string $obat_search = '';
    public ?int $user_id = null;
    public string $nomor_rekam_medis = '';
    public string $tanggal_pemeriksaan = '';
    public ?string $keluhan = null;
    public ?string $diagnosa = null;
    public ?string $catatan = null;
    public ?string $tekanan_darah = null;
    public ?string $suhu_tubuh = null;
    public ?string $berat_badan = null;
    public ?string $tinggi_badan = null;
    public ?int $detak_jantung = null;
    public ?int $laju_pernapasan = null;

    // Selected drugs array: [{ obat_id, jumlah, dosis, catatan }]
    public array $selected_drugs = [];

    // State
    public bool $showModal = false;
    public bool $editMode = false;
    public int|null $editingId = null;
    public bool $showDeleteConfirm = false;
    public int|null $deletingId = null;
    public bool $pasien_focused = false;
    public bool $obat_focused = false;
    public string $search = '';
    public int $perPage = 10;
    public string $sortColumn = 'tanggal_pemeriksaan';
    public string $sortDirection = 'desc';

    protected function rules(): array
    {
        return [
            'pasien_id' => ['required', 'exists:pasiens,id'],
            'user_id' => ['required', 'exists:users,id'],
            'nomor_rekam_medis' => ['required', 'string', 'max:255', $this->editMode ? 'unique:rekam_medis,nomor_rekam_medis,' . $this->editingId : 'unique:rekam_medis,nomor_rekam_medis'],
            'tanggal_pemeriksaan' => ['required', 'date'],
            'keluhan' => ['nullable', 'string'],
            'diagnosa' => ['required', 'string'],
            'catatan' => ['nullable', 'string'],
            'selected_drugs' => ['required', 'array', 'min:1'],
            'selected_drugs.*.obat_id' => ['required', 'exists:obats,id'],
            'tekanan_darah' => ['nullable', 'string', 'max:20'],
            'suhu_tubuh' => ['nullable', 'string', 'max:20'],
            'berat_badan' => ['nullable', 'string', 'max:20'],
            'tinggi_badan' => ['nullable', 'string', 'max:20'],
            'detak_jantung' => ['nullable', 'integer', 'min:0'],
            'laju_pernapasan' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function render()
    {
        $query = RekamMedis::query()->with(['pasien:id,nama', 'obat:id,nama', 'user:id,name']);

        if ($this->search) {
            $query
                ->whereHas('pasien', function ($q) {
                    $q->where('nama', 'like', '%' . $this->search . '%');
                })
                ->orWhere('nomor_rekam_medis', 'like', '%' . $this->search . '%')
                ->orWhereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
        }

        $records = $query->orderBy($this->sortColumn, $this->sortDirection)->paginate($this->perPage);

        return view('pages.medical_records.index', [
            'records' => $records,
            'pasiens' => Pasien::orderBy('nama')->get(['id', 'nama', 'nik']),
            'all_obats' => Obat::orderBy('nama')->get(['id', 'kode', 'nama', 'type', 'satuan', 'stok']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'filteredPasiens' => Pasien::query()
                ->when($this->pasien_search, function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('nama', 'like', '%' . $this->pasien_search . '%')->orWhere('nik', 'like', '%' . $this->pasien_search . '%');
                    });
                })
                ->orderBy('nama')
                ->limit(5)
                ->get(['id', 'nama', 'nik']),
            'filtered_obats' => $this->lookupObats(),
        ]);
    }

    public function resetForm(): void
    {
        $this->reset(['pasien_id', 'user_id', 'nomor_rekam_medis', 'tanggal_pemeriksaan', 'keluhan', 'diagnosa', 'catatan', 'tekanan_darah', 'suhu_tubuh', 'berat_badan', 'tinggi_badan', 'detak_jantung', 'laju_pernapasan', 'editMode', 'editingId', 'showDeleteConfirm', 'deletingId', 'pasien_search', 'obat_search', 'pasien_focused', 'obat_focused']);
        $this->selected_drugs = [];
        $this->user_id = Auth::id();
    }

    public function generatingRecordNumber(): string
    {
        $last = RekamMedis::orderByDesc('id')->first();
        $number = $last ? $last->id + 1 : 1;
        return 'RM-' . str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }

    public function creating(): void
    {
        $this->resetForm();
        $this->nomor_rekam_medis = $this->generatingRecordNumber();
        $this->tanggal_pemeriksaan = now()->format('Y-m-d\TH:i');
        $this->pasien_focused = true;
        $this->editMode = false;
        $this->editingId = null;
        $this->showModal = true;
    }

    public function editing(int $id): void
    {
        $record = RekamMedis::with(['pasien', 'obats'])->findOrFail($id);
        $this->pasien_id = $record->pasien_id;
        $this->user_id = Auth::id();
        $this->nomor_rekam_medis = $record->nomor_rekam_medis;
        $this->tanggal_pemeriksaan = $record->tanggal_pemeriksaan->format('Y-m-d\TH:i');
        $this->keluhan = $record->keluhan;
        $this->diagnosa = $record->diagnosa;
        $this->catatan = $record->catatan;
        $this->tekanan_darah = $record->tekanan_darah;
        $this->suhu_tubuh = $record->suhu_tubuh;
        $this->berat_badan = $record->berat_badan;
        $this->tinggi_badan = $record->tinggi_badan;
        $this->detak_jantung = $record->detak_jantung;
        $this->laju_pernapasan = $record->laju_pernapasan;
        $this->pasien_search = $record->pasien?->nama ?? '';
        $this->pasien_focused = false;

        // Load all drugs from pivot obat_rekam_medis
        $this->selected_drugs = $record->obats
            ->map(function ($obat) {
                return [
                    'obat_id' => $obat->id,
                    'nama' => $obat->nama,
                    'jumlah' => $obat->pivot?->jumlah ?? 1,
                    'dosis' => $obat->pivot?->dosis ?? '',
                    'catatan' => $obat->pivot?->catatan ?? '',
                ];
            })
            ->toArray();

        $this->editMode = true;
        $this->editingId = $id;
        $this->showModal = true;
    }

    public function focusPasienSearch(): void
    {
        $this->pasien_focused = true;
    }

    public function blurPasienSearch(): void
    {
        if (!$this->pasien_id) {
            $this->pasien_search = '';
        }
        $this->pasien_focused = false;
    }

    public function blurObatSearch(): void
    {
        $this->obat_focused = false;
        if (!$this->selected_drugs) {
            $this->obat_search = '';
        }
    }

    public function addDrug(int $obatId): void
    {
        $obat = Obat::find($obatId);

        if (!$obat) {
            return;
        }

        // Cegah duplikat
        foreach ($this->selected_drugs as $item) {
            if ($item['obat_id'] == $obatId) {
                return;
            }
        }

        $this->selected_drugs[] = [
            'obat_id' => $obat->id,
            'nama' => $obat->nama,
            'jumlah' => 1,
            'dosis' => '',
            'catatan' => '',
        ];

        $this->obat_search = '';
        $this->obat_focused = false;
    }

    public function removeDrug(int $index): void
    {
        unset($this->selected_drugs[$index]);
        $this->selected_drugs = array_values($this->selected_drugs);
    }

    public function updateDrugQuantity(int $index, int $value): void
    {
        if (isset($this->selected_drugs[$index])) {
            $this->selected_drugs[$index]['jumlah'] = max(1, $value);
        }
    }

    public function updateDrugDosage(int $index, string $value): void
    {
        if (isset($this->selected_drugs[$index])) {
            $this->selected_drugs[$index]['dosis'] = $value;
        }
    }

    public function updateDrugNotes(int $index, string $value): void
    {
        if (isset($this->selected_drugs[$index])) {
            $this->selected_drugs[$index]['catatan'] = $value;
        }
    }

    public function selectPasien(int $id, string $nama): void
    {
        $this->pasien_id = $id;
        $this->pasien_search = $nama;
    }

    public function clearPasienSearch(): void
    {
        $this->pasien_id = null;
        $this->pasien_search = '';
    }

    public function clearObatSearch(): void
    {
        $this->obat_search = '';
    }

    private function lookupObats(): \Illuminate\Support\Collection
    {
        return Obat::query()
            ->when($this->obat_search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('nama', 'like', '%' . $this->obat_search . '%')->orWhere('kode', 'like', '%' . $this->obat_search . '%');
                });
            })
            ->orderBy('nama')
            ->limit(5)
            ->get(['id', 'kode', 'nama', 'type', 'satuan', 'stok']);
    }

    public function addDrugFromSearch(): void
    {
        $first = $this->lookupObats()->first();
        if (!$first) {
            return;
        }
        $this->addDrug($first->id);
        $this->obat_search = '';
    }

    public function save(): void
    {
        if (empty($this->selected_drugs)) {
            $this->addError('selected_drugs', 'Pilih minimal satu obat yang diresepkan.');
            return;
        }

        $validated = $this->validate();

        $data = [
            'pasien_id' => $validated['pasien_id'],
            'user_id' => $this->user_id,
            'nomor_rekam_medis' => $validated['nomor_rekam_medis'],
            'tanggal_pemeriksaan' => $validated['tanggal_pemeriksaan'],
            'keluhan' => $validated['keluhan'],
            'diagnosa' => $validated['diagnosa'],
            'catatan' => $validated['catatan'],
            'tekanan_darah' => $validated['tekanan_darah'],
            'suhu_tubuh' => $validated['suhu_tubuh'],
            'berat_badan' => $validated['berat_badan'],
            'tinggi_badan' => $validated['tinggi_badan'],
            'detak_jantung' => $validated['detak_jantung'],
            'laju_pernapasan' => $validated['laju_pernapasan'],
        ];

        if ($this->editMode && $this->editingId) {
            $record = RekamMedis::findOrFail($this->editingId);
            $this->restoreOldDrugStock($record);
            $record->update($data);
            $message = 'Rekam medis berhasil diperbarui.';
        } else {
            $record = RekamMedis::create($data);
            $message = 'Rekam medis berhasil ditambahkan.';
        }

        $this->decreaseDrugStock($record->id);
        $this->syncPivotObats($record->id);

        $this->dispatch('toast', type: 'success', message: $message);
        $this->resetForm();
        $this->showModal = false;
    }

    private function decreaseDrugStock(int $recordId): void
    {
        foreach ($this->selected_drugs as $item) {
            $qty = (int) ($item['jumlah'] ?? 1);
            $qty = max(1, $qty);
            Obat::where('id', $item['obat_id'])->decrement('stok', $qty);
        }
    }

    private function restoreOldDrugStock(RekamMedis $record): void
    {
        $record->obats()->get()->each(function ($obat) {
            $qty = (int) ($obat->pivot?->jumlah ?? 1);
            Obat::where('id', $obat->id)->increment('stok', $qty);
        });
    }

    public function syncPivotObats(int $recordId): void
    {
        $obats = [];
        foreach ($this->selected_drugs as $item) {
            $obats[$item['obat_id']] = [
                'jumlah' => (int) ($item['jumlah'] ?? 1),
                'dosis' => $item['dosis'] ?? '',
                'catatan' => $item['catatan'] ?? '',
            ];
        }
        RekamMedis::findOrFail($recordId)->obats()->sync($obats);
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            RekamMedis::findOrFail($this->deletingId)->delete();
            $this->dispatch('toast', type: 'warning', message: 'Rekam medis berhasil dihapus.');
        }
        $this->closeDeleteModal();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteConfirm = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteConfirm = false;
        $this->deletingId = null;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function sortBy(string $column): void
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function totalRecords(): int
    {
        return RekamMedis::count();
    }

    public function todayRecords(): int
    {
        return RekamMedis::whereDate('tanggal_pemeriksaan', now()->toDateString())->count();
    }

    public function monthRecords(): int
    {
        return RekamMedis::whereMonth('tanggal_pemeriksaan', now()->month)->count();
    }

    public function getSelectedPasienName(): ?string
    {
        return $this->pasien_id ? Pasien::find($this->pasien_id)?->nama : null;
    }

    public function focusObatSearch(): void
    {
        $this->obat_focused = true;
    }

    protected function view($data = [])
    {
        return app('view')->file('D:\laragon\www\rekam-medis\storage\framework/views/livewire/views/ccc5ef43.blade.php', $data);
    }
};

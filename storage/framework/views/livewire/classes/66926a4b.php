<?php

use Livewire\Component;
use App\Models\Imunisasi;
use App\Models\Pasien;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

return new class extends Component {
    use WithPagination;

    // Form fields
    public ?int $pasien_id = null;
    public string $pasien_search = '';
    public ?int $user_id = null;
    public string $tanggal_imunisasi = '';
    public string $jenis_imunisasi = '';
    public ?string $tanggal_lahir_pasien = null;
    public ?string $nama_orang_tua = null;
    public ?string $alamat = null;
    public ?string $pengobatan = null;
    public ?string $keterangan = null;

    // Patient helpers
    public ?int $umur_pasien = null;

    // State
    public bool $showModal = false;
    public bool $editMode = false;
    public int|null $editingId = null;
    public bool $showDeleteConfirm = false;
    public int|null $deletingId = null;
    public string $search = '';
    public int $perPage = 10;
    public string $sortColumn = 'tanggal_imunisasi';
    public string $sortDirection = 'desc';
    public bool $pasien_focused = false;

    // Jenis imunisasi options
    public array $jenisImunisasiList = [
        'Hepatitis B',
        'BCG',
        'Polio (OPV)',
        'DPT-HB-Hib',
        'Campak',
        'PCV',
        'Rotavirus',
        'MR (Campak-Rubella)',
        'Japanese Encephalitis',
        'Lainnya',
    ];

    protected function rules(): array
    {
        return [
            'pasien_id' => ['required', 'exists:pasiens,id'],
            'user_id' => ['required', 'exists:users,id'],
            'tanggal_imunisasi' => ['required', 'date'],
            'jenis_imunisasi' => ['required', 'string', 'max:255'],
            'tanggal_lahir_pasien' => ['nullable', 'string', 'max:255'],
            'nama_orang_tua' => ['nullable', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'pengobatan' => ['nullable', 'string', 'max:2000'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Called when pasien_id is changed from the UI (wire:change).
     */
    public function onPasienChanged(): void
    {
        if ($this->pasien_id) {
            $pasien = Pasien::findOrFail($this->pasien_id);
            $this->tanggal_lahir_pasien = $pasien->tanggal_lahir?->format('Y-m-d');
            $this->alamat = $pasien->alamat;
            if ($pasien->tanggal_lahir) {
                $this->umur_pasien = $pasien->tanggal_lahir->age;
            }
        } else {
            $this->tanggal_lahir_pasien = null;
            $this->alamat = null;
            $this->umur_pasien = null;
        }
    }

    public function render()
    {
        $query = Imunisasi::query()->with(['pasien:id,nama,jenis_pasien,tanggal_lahir,alamat', 'user:id,name']);

        if ($this->search) {
            $query
                ->whereHas('pasien', function ($q) {
                    $q->where('nama', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                })
                ->orWhere('jenis_imunisasi', 'like', '%' . $this->search . '%')
                ->orWhere('nama_orang_tua', 'like', '%' . $this->search . '%');
        }

        $records = $query->orderBy($this->sortColumn, $this->sortDirection)->paginate($this->perPage);

        return view('pages.immunization.index', [
            'records' => $records,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'filteredPasiens' => $this->lookupPasiens(),
        ]);
    }

    public function resetForm(): void
    {
        $this->reset([
            'pasien_id', 'pasien_search', 'user_id', 'tanggal_imunisasi', 'jenis_imunisasi',
            'tanggal_lahir_pasien', 'nama_orang_tua', 'alamat',
            'pengobatan', 'keterangan', 'umur_pasien',
            'editMode', 'editingId', 'showDeleteConfirm', 'deletingId', 'search', 'pasien_focused',
        ]);
        $this->user_id = Auth::id();
        $this->tanggal_imunisasi = now()->format('Y-m-d');
        $this->jenis_imunisasi = '';
        $this->pasien_search = '';
    }

    public function creating(): void
    {
        $this->resetForm();
        $this->editMode = false;
        $this->editingId = null;
        $this->showModal = true;
    }

    public function editing(int $id): void
    {
        $record = Imunisasi::findOrFail($id);
        $this->pasien_id = $record->pasien_id;
        $this->pasien_search = $record->pasien?->nama ?? '';
        $this->user_id = Auth::id();
        $this->tanggal_imunisasi = $record->tanggal_imunisasi?->format('Y-m-d') ?? '';
        $this->jenis_imunisasi = $record->jenis_imunisasi ?? '';
        $this->tanggal_lahir_pasien = $record->tanggal_lahir_pasien;
        $this->nama_orang_tua = $record->nama_orang_tua ?? '';
        $this->alamat = $record->alamat;
        $this->pengobatan = $record->pengobatan;
        $this->keterangan = $record->keterangan;

        if ($record->pasien && $record->pasien->tanggal_lahir) {
            $this->umur_pasien = $record->pasien->tanggal_lahir->age;
        }

        $this->editMode = true;
        $this->editingId = $id;
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $data = [
            'pasien_id' => $validated['pasien_id'],
            'user_id' => $this->user_id,
            'tanggal_imunisasi' => $validated['tanggal_imunisasi'],
            'jenis_imunisasi' => $validated['jenis_imunisasi'],
            'tanggal_lahir_pasien' => $validated['tanggal_lahir_pasien'],
            'nama_orang_tua' => $validated['nama_orang_tua'],
            'alamat' => $validated['alamat'],
            'pengobatan' => $validated['pengobatan'],
            'keterangan' => $validated['keterangan'],
        ];

        if ($this->editMode && $this->editingId) {
            Imunisasi::findOrFail($this->editingId)->update($data);
            $message = 'Data imunisasi berhasil diperbarui.';
        } else {
            Imunisasi::create($data);
            $message = 'Data imunisasi berhasil ditambahkan.';
        }

        $this->dispatch('toast', type: 'success', message: $message);
        $this->resetForm();
        $this->showModal = false;
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Imunisasi::findOrFail($this->deletingId)->delete();
            $this->dispatch('toast', type: 'warning', message: 'Data imunisasi berhasil dihapus.');
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

    /**
     * Handle pasien_id being set programmatically (edit mode, lifecycle hook).
     */
    public function updatedPasienId(?int $value): void
    {
        $this->onPasienChanged();
    }

    public function totalRecords(): int
    {
        return Imunisasi::count();
    }

    public function bayiCount(): int
    {
        return Imunisasi::whereHas('pasien', function ($q) {
            $q->where('jenis_pasien', 'bayi');
        })->count();
    }

    public function todayCount(): int
    {
        return Imunisasi::whereDate('tanggal_imunisasi', now()->toDateString())->count();
    }

    public function focusPasienSearch(): void
    {
        $this->pasien_focused = true;
    }

    public function blurPasienSearch(): void
    {
        if (! $this->pasien_id) {
            $this->pasien_search = '';
        }
        $this->pasien_focused = false;
    }

    public function selectPasien(int $id, string $nama): void
    {
        $this->pasien_id = $id;
        $this->pasien_search = $nama;
        $this->pasien_focused = false;
        $this->onPasienChanged();
    }

    public function clearPasienSearch(): void
    {
        $this->pasien_id = null;
        $this->pasien_search = '';
        $this->tanggal_lahir_pasien = null;
        $this->alamat = null;
        $this->umur_pasien = null;
    }

    private function lookupPasiens(): \Illuminate\Support\Collection
    {
        return Pasien::query()
            ->when($this->pasien_search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('nama', 'like', '%' . $this->pasien_search . '%')
                        ->orWhere('nik', 'like', '%' . $this->pasien_search . '%');
                });
            })
            ->where('jenis_pasien', 'bayi')
            ->orderBy('nama')
            ->limit(5)
            ->get(['id', 'nama', 'nik', 'tanggal_lahir', 'alamat']);
    }

    public function getSelectedPasienName(): ?string
    {
        return $this->pasien_id ? Pasien::find($this->pasien_id)?->nama : null;
    }

    protected function view($data = [])
    {
        return app('view')->file('D:\laragon\www\rekam-medis\storage\framework/views/livewire/views/66926a4b.blade.php', $data);
    }
};

<?php

use Livewire\Component;
use App\Models\Pregnancy;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

return new class extends Component {
    use WithPagination;

    // Form fields
    public ?int $user_id = null;
    public string $tanggal = '';
    public string $nama_istri = '';
    public string $nama_suami = '';
    public ?int $umur_istri = null;
    public ?int $umur_suami = null;
    public ?string $alamat = null;
    public ?string $no_telpon = null;
    public int $gravida = 0;
    public int $partus = 0;
    public int $abortus = 0;
    public string $hpht = '';
    public string $tp = '';
    public ?string $pemeriksaan = null;
    public ?string $keluhan = null;
    public ?string $terapi = null;
    public ?string $keterangan = null;

    // State
    public bool $showModal = false;
    public bool $editMode = false;
    public int|null $editingId = null;
    public bool $showDeleteConfirm = false;
    public int|null $deletingId = null;
    public string $search = '';
    public int $perPage = 10;
    public string $sortColumn = 'tanggal';
    public string $sortDirection = 'desc';

    protected function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'tanggal' => ['required', 'date'],
            'nama_istri' => ['required', 'string', 'max:255'],
            'nama_suami' => ['nullable', 'string', 'max:255'],
            'umur_istri' => ['nullable', 'integer', 'min:0', 'max:120'],
            'umur_suami' => ['nullable', 'integer', 'min:0', 'max:120'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'no_telpon' => ['nullable', 'string', 'max:20'],
            'gravida' => ['required', 'integer', 'min:0'],
            'partus' => ['required', 'integer', 'min:0'],
            'abortus' => ['required', 'integer', 'min:0'],
            'hpht' => ['nullable', 'date'],
            'tp' => ['nullable', 'date'],
            'pemeriksaan' => ['nullable', 'string', 'max:2000'],
            'keluhan' => ['nullable', 'string', 'max:2000'],
            'terapi' => ['nullable', 'string', 'max:2000'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function render()
    {
        $query = Pregnancy::query()->with('user:id,name');

        if ($this->search) {
            $query
                ->where('nama_istri', 'like', '%' . $this->search . '%')
                ->orWhere('nama_suami', 'like', '%' . $this->search . '%')
                ->orWhere('alamat', 'like', '%' . $this->search . '%')
                ->orWhereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
        }

        $records = $query->orderBy($this->sortColumn, $this->sortDirection)->paginate($this->perPage);

        return view('pages.pregnancy.index', [
            'records' => $records,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'totalRecords' => Pregnancy::count(),
            'todayRecords' => Pregnancy::whereDate('tanggal', now()->toDateString())->count(),
            'monthRecords' => Pregnancy::whereMonth('tanggal', now()->month)->count(),
            'thirdTrimesterRecords' => Pregnancy::whereDate('hpht', '<=', now()->subWeeks(28)->toDateString())->count(),
        ]);
    }

    public function resetForm(): void
    {
        $this->reset(['user_id', 'tanggal', 'nama_istri', 'nama_suami', 'umur_istri', 'umur_suami', 'alamat', 'no_telpon', 'gravida', 'partus', 'abortus', 'hpht', 'tp', 'pemeriksaan', 'keluhan', 'terapi', 'keterangan', 'editMode', 'editingId', 'showDeleteConfirm', 'deletingId', 'search']);
        $this->user_id = Auth::id();
    }

    public function creating(): void
    {
        $this->resetForm();
        $this->tanggal = now()->format('Y-m-d');
        $this->editMode = false;
        $this->editingId = null;
        $this->showModal = true;
    }

    public function editing(int $id): void
    {
        $record = Pregnancy::findOrFail($id);
        $this->user_id = Auth::id();
        $this->tanggal = $record->tanggal->format('Y-m-d');
        $this->nama_istri = $record->nama_istri;
        $this->nama_suami = $record->nama_suami ?? '';
        $this->umur_istri = $record->umur_istri;
        $this->umur_suami = $record->umur_suami;
        $this->alamat = $record->alamat ?? '';
        $this->no_telpon = $record->no_telpon ?? '';
        $this->gravida = $record->gravida;
        $this->partus = $record->partus;
        $this->abortus = $record->abortus;
        $this->hpht = $record->hpht ? $record->hpht->format('Y-m-d') : '';
        $this->tp = $record->tp ? $record->tp->format('Y-m-d') : '';
        $this->pemeriksaan = $record->pemeriksaan ?? '';
        $this->keluhan = $record->keluhan ?? '';
        $this->terapi = $record->terapi ?? '';
        $this->keterangan = $record->keterangan ?? '';

        $this->editMode = true;
        $this->editingId = $id;
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $data = [
            'user_id' => $this->user_id,
            'tanggal' => $validated['tanggal'],
            'nama_istri' => $validated['nama_istri'],
            'nama_suami' => $validated['nama_suami'],
            'umur_istri' => $validated['umur_istri'],
            'umur_suami' => $validated['umur_suami'],
            'alamat' => $validated['alamat'],
            'no_telpon' => $validated['no_telpon'],
            'gravida' => $validated['gravida'],
            'partus' => $validated['partus'],
            'abortus' => $validated['abortus'],
            'hpht' => $validated['hpht'] ?: null,
            'tp' => $validated['tp'] ?: null,
            'pemeriksaan' => $validated['pemeriksaan'],
            'keluhan' => $validated['keluhan'],
            'terapi' => $validated['terapi'],
            'keterangan' => $validated['keterangan'],
        ];

        if ($this->editMode && $this->editingId) {
            Pregnancy::findOrFail($this->editingId)->update($data);
            $message = 'Data kehamilan berhasil diperbarui.';
        } else {
            Pregnancy::create($data);
            $message = 'Data kehamilan berhasil ditambahkan.';
        }

        $this->dispatch('toast', type: 'success', message: $message);
        $this->resetForm();
        $this->showModal = false;
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Pregnancy::findOrFail($this->deletingId)->delete();
            $this->dispatch('toast', type: 'warning', message: 'Data kehamilan berhasil dihapus.');
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
     * Calculate TP (Hari Perkiraan Persalinan) using Indonesian midwifery rules:
     * - Jan–Mar : HPHT + 7 hari, + 9 bulan, tahun tetap
     * - Apr–Dec : HPHT + 7 hari, – 3 bulan, tahun + 1
     * Called on wire:change of the HPHT input.
     */
    public function autoCalculateTp(): void
    {
        if (! $this->hpht) {
            $this->tp = '';
            return;
        }

        try {
            $hpht = Carbon::parse($this->hpht);
            $month = (int) $hpht->format('m'); // 1 = Jan, 12 = Des

            if ($month >= 1 && $month <= 3) {
                // Januari–Maret : +9 bulan, +7 hari, tahun tetap
                $this->tp = $hpht
                    ->copy()
                    ->addMonths(9)
                    ->addDays(7)
                    ->format('Y-m-d');
            } else {
                // April–Desember : -3 bulan, +7 hari, tahun +1
                $this->tp = $hpht
                    ->copy()
                    ->subMonths(3)
                    ->addDays(7)
                    ->addYear()
                    ->format('Y-m-d');
            }
        } catch (\Exception $e) {
            $this->tp = '';
        }
    }

    public function gestationalAgeWeeks(): int|null
    {
        if (! $this->hpht) {
            return null;
        }

        try {
            return Carbon::parse($this->hpht)->diffInWeeks(now());
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Calculate pregnancy weeks from a given HPHT date (used in table @foreach).
     */
    public function calcWeeks(?string $hphtValue): ?int
    {
        if (! $hphtValue) {
            return null;
        }

        try {
            return Carbon::parse($hphtValue)->diffInWeeks(now());
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function view($data = [])
    {
        return app('view')->file('D:\laragon\www\rekam-medis\storage\framework/views/livewire/views/036d67f5.blade.php', $data);
    }
};


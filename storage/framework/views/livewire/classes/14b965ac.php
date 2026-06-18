<?php

use Livewire\Component;
use App\Models\Kb;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

return new class extends Component {
    use WithPagination;

    // Form fields
    public ?int $user_id = null;
    public string $tanggal = '';
    public string $no_regis = '';
    public string $nama_istri = '';
    public ?string $nama_suami = null;
    public ?int $umur_istri = null;
    public ?string $alamat = null;
    public ?string $nik_istri = null;
    public ?string $no_hp = null;
    public ?string $tekanan_darah = null;
    public ?string $berat_badan = null;
    public string $metode_kb = 'Pil';
    public string $tanggal_kunjungan = '';
    public ?string $tanggal_kunjungan_ulang = null;
    public ?string $keluhan = null;
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

    public array $metodeKbList = [
        'Pil',
        'Suntik',
        'IUD/IUCD',
        'Implant',
        'Kontrasepsi Darah (Injeksi)',
        'MOW',
        'MOP',
        'Lainnya',
    ];

    protected function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'tanggal' => ['required', 'date'],
            'no_regis' => ['required', 'string', 'max:50', $this->editMode ? 'unique:kb,no_regis,' . $this->editingId : 'unique:kb,no_regis'],
            'nama_istri' => ['required', 'string', 'max:255'],
            'nama_suami' => ['nullable', 'string', 'max:255'],
            'umur_istri' => ['nullable', 'integer', 'min:0', 'max:120'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'nik_istri' => ['nullable', 'string', 'max:20'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'tekanan_darah' => ['nullable', 'string', 'max:20'],
            'berat_badan' => ['nullable', 'string', 'max:20'],
            'metode_kb' => ['required', 'string', 'max:100'],
            'tanggal_kunjungan' => ['required', 'date'],
            'tanggal_kunjungan_ulang' => ['nullable', 'date'],
            'keluhan' => ['nullable', 'string', 'max:2000'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function render()
    {
        $query = Kb::query()->with('user:id,name');

        if ($this->search) {
            $query
                ->where('nama_istri', 'like', '%' . $this->search . '%')
                ->orWhere('nama_suami', 'like', '%' . $this->search . '%')
                ->orWhere('no_regis', 'like', '%' . $this->search . '%')
                ->orWhere('metode_kb', 'like', '%' . $this->search . '%')
                ->orWhereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
        }

        $records = $query->orderBy($this->sortColumn, $this->sortDirection)->paginate($this->perPage);

        return view('pages.kb.index', [
            'records' => $records,
        ]);
    }

    public function resetForm(): void
    {
        $this->reset([
            'user_id', 'tanggal', 'no_regis', 'nama_istri', 'nama_suami',
            'umur_istri', 'alamat', 'nik_istri', 'no_hp',
            'tekanan_darah', 'berat_badan', 'metode_kb',
            'tanggal_kunjungan', 'tanggal_kunjungan_ulang',
            'keluhan', 'keterangan',
            'editMode', 'editingId', 'showDeleteConfirm', 'deletingId', 'search',
        ]);
        $this->user_id = Auth::id();
        $this->tanggal = now()->format('Y-m-d');
        $this->tanggal_kunjungan = now()->format('Y-m-d');
        $this->metode_kb = 'Pil';
        $this->no_regis = $this->generateNoRegis();
    }

    public function generateNoRegis(): string
    {
        $datePart = now()->format('Ymd');
        $last = Kb::whereDate('tanggal', now()->toDateString())
            ->orderByDesc('id')
            ->first();
        $number = $last ? ($last->id + 1) : 1;
        return 'KB-' . $datePart . '-' . str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }

    public function creating(): void
    {
        $this->resetForm();
        $this->editMode = false;
        $this->editingId = null;
        $this->showModal = true;
        $this->autoCalculateKunjunganUlang();
    }

    public function editing(int $id): void
    {
        $record = Kb::findOrFail($id);
        $this->user_id = Auth::id();
        $this->tanggal = $record->tanggal->format('Y-m-d');
        $this->no_regis = $record->no_regis;
        $this->nama_istri = $record->nama_istri;
        $this->nama_suami = $record->nama_suami ?? '';
        $this->umur_istri = $record->umur_istri;
        $this->alamat = $record->alamat ?? '';
        $this->nik_istri = $record->nik_istri ?? '';
        $this->no_hp = $record->no_hp ?? '';
        $this->tekanan_darah = $record->tekanan_darah ?? '';
        $this->berat_badan = $record->berat_badan ?? '';
        $this->metode_kb = $record->metode_kb;
        $this->tanggal_kunjungan = $record->tanggal_kunjungan->format('Y-m-d');
        $this->tanggal_kunjungan_ulang = $record->tanggal_kunjungan_ulang?->format('Y-m-d');
        $this->keluhan = $record->keluhan ?? '';
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
            'no_regis' => $validated['no_regis'],
            'nama_istri' => $validated['nama_istri'],
            'nama_suami' => $validated['nama_suami'],
            'umur_istri' => $validated['umur_istri'],
            'alamat' => $validated['alamat'],
            'nik_istri' => $validated['nik_istri'],
            'no_hp' => $validated['no_hp'],
            'tekanan_darah' => $validated['tekanan_darah'],
            'berat_badan' => $validated['berat_badan'],
            'metode_kb' => $validated['metode_kb'],
            'tanggal_kunjungan' => $validated['tanggal_kunjungan'],
            'tanggal_kunjungan_ulang' => $validated['tanggal_kunjungan_ulang'],
            'keluhan' => $validated['keluhan'],
            'keterangan' => $validated['keterangan'],
        ];

        if ($this->editMode && $this->editingId) {
            Kb::findOrFail($this->editingId)->update($data);
            $message = 'Data KB berhasil diperbarui.';
        } else {
            Kb::create($data);
            $message = 'Data KB berhasil ditambahkan.';
        }

        $this->dispatch('toast', type: 'success', message: $message);
        $this->resetForm();
        $this->showModal = false;
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Kb::findOrFail($this->deletingId)->delete();
            $this->dispatch('toast', type: 'warning', message: 'Data KB berhasil dihapus.');
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

    public function updatedMetodeKb(): void
    {
        $this->autoCalculateKunjunganUlang();
    }

    public function autoCalculateKunjunganUlang(): void
    {
        $metode = $this->metode_kb;

        switch ($metode) {
            case 'Pil':
                $this->tanggal_kunjungan_ulang = now()->addMonths(1)->format('Y-m-d');
                break;
            case 'Suntik':
                $this->tanggal_kunjungan_ulang = now()->addMonths(1)->format('Y-m-d');
                break;
            case 'Suntik 3 Bulan':
                $this->tanggal_kunjungan_ulang = now()->addMonths(3)->format('Y-m-d');
                break;
            case 'IUD/IUCD':
                $this->tanggal_kunjungan_ulang = now()->addYear()->format('Y-m-d');
                break;
            case 'Implant':
                $this->tanggal_kunjungan_ulang = now()->addYears(3)->format('Y-m-d');
                break;
            case 'MOW':
            case 'MOP':
                $this->tanggal_kunjungan_ulang = null;
                break;
            default:
                $this->tanggal_kunjungan_ulang = null;
                break;
        }
    }

    public function totalRecords(): int
    {
        return Kb::count();
    }

    public function pilCount(): int
    {
        return Kb::where('metode_kb', 'Pil')->count();
    }

    public function suntikCount(): int
    {
        return Kb::where('metode_kb', 'Suntik')->count();
    }

    public function todayCount(): int
    {
        return Kb::whereDate('tanggal', now()->toDateString())->count();
    }

    protected function view($data = [])
    {
        return app('view')->file('D:\laragon\www\rekam-medis\storage\framework/views/livewire/views/14b965ac.blade.php', $data);
    }
};


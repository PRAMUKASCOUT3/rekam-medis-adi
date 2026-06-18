<?php

use Livewire\Component;
use App\Models\Delivery;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

return new class extends Component {
    use WithPagination;

    // Form fields
    public ?int $user_id = null;
    public string $tanggal = '';
    public ?int $umur_istri = null;
    public ?int $umur_suami = null;
    public ?string $alamat = null;
    public ?string $no_telpon = null;
    public ?string $pekerjaan_istri = null;
    public ?string $pekerjaan_suami = null;
    public ?string $keluhan = null;
    public ?string $tindakan = null;
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
            'pekerjaan_istri' => ['nullable', 'string', 'max:255'],
            'pekerjaan_suami' => ['nullable', 'string', 'max:255'],
            'keluhan' => ['nullable', 'string', 'max:2000'],
            'tindakan' => ['nullable', 'string', 'max:2000'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function render()
    {
        $query = Delivery::query()->with('user:id,name');

        if ($this->search) {
            $query
                ->where('nama_istri', 'like', '%' . $this->search . '%')
                ->orWhere('nama_suami', 'like', '%' . $this->search . '%')
                ->orWhere('umur_istri', 'like', '%' . $this->search . '%')
                ->orWhere('umur_suami', 'like', '%' . $this->search . '%')
                ->orWhere('no_telpon', 'like', '%' . $this->search . '%')
                ->orWhere('pekerjaan_istri', 'like', '%' . $this->search . '%')
                ->orWhere('pekerjaan_suami', 'like', '%' . $this->search . '%')
                ->orWhereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
        }

        $records = $query->orderBy($this->sortColumn, $this->sortDirection)->paginate($this->perPage);

        return view('pages.delivery.index', [
            'records' => $records,
            'users' => \App\Models\User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function resetForm(): void
    {
        $this->reset([
            'user_id', 'tanggal', 'umur_istri', 'umur_suami', 'alamat',
            'no_telpon', 'pekerjaan_istri', 'pekerjaan_suami', 'keluhan',
            'tindakan', 'keterangan', 'editMode', 'editingId',
            'showDeleteConfirm', 'deletingId', 'search',
        ]);
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
        $record = Delivery::findOrFail($id);
        $this->user_id = Auth::id();
        $this->tanggal = $record->tanggal->format('Y-m-d');
        $this->nama_istri = $record->nama_istri ?? '';
        $this->nama_suami = $record->nama_suami ?? '';
        $this->umur_istri = $record->umur_istri;
        $this->umur_suami = $record->umur_suami;
        $this->alamat = $record->alamat ?? '';
        $this->no_telpon = $record->no_telpon ?? '';
        $this->pekerjaan_istri = $record->pekerjaan_istri ?? '';
        $this->pekerjaan_suami = $record->pekerjaan_suami ?? '';
        $this->keluhan = $record->keluhan ?? '';
        $this->tindakan = $record->tindakan ?? '';
        $this->bayi_lahir = $record->bayi_lahir;
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
            'umur_istri' => $validated['umur_istri'],
            'umur_suami' => $validated['umur_suami'],
            'alamat' => $validated['alamat'],
            'no_telpon' => $validated['no_telpon'],
            'pekerjaan_istri' => $validated['pekerjaan_istri'],
            'pekerjaan_suami' => $validated['pekerjaan_suami'],
            'keluhan' => $validated['keluhan'],
            'tindakan' => $validated['tindakan'],
            'keterangan' => $validated['keterangan'],
        ];

        if ($this->editMode && $this->editingId) {
            Delivery::findOrFail($this->editingId)->update($data);
            $message = 'Data persalinan berhasil diperbarui.';
        } else {
            Delivery::create($data);
            $message = 'Data persalinan berhasil ditambahkan.';
        }

        $this->dispatch('toast', type: 'success', message: $message);
        $this->resetForm();
        $this->showModal = false;
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Delivery::findOrFail($this->deletingId)->delete();
            $this->dispatch('toast', type: 'warning', message: 'Data persalinan berhasil dihapus.');
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

    protected function view($data = [])
    {
        return app('view')->file('D:\laragon\www\rekam-medis\storage\framework/views/livewire/views/608bb32b.blade.php', $data);
    }
};


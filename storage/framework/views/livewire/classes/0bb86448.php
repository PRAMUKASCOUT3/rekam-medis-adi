<?php

use Livewire\Component;
use App\Models\Obat;
use Livewire\WithPagination;

return new class extends Component {
    use WithPagination;

    // Form fields
    public string $kode = '';
    public string $nama = '';
    public string $type = 'tablet';
    public string $satuan = 'pcs';
    public int $stok = 0;

    // State
    public bool $showModal = false;
    public bool $editMode = false;
    public int|null $editingId = null;
    public bool $showDeleteConfirm = false;
    public int|null $deletingId = null;
    public string $search = '';
    public int $perPage = 10;
    public string $sortColumn = 'created_at';
    public string $sortDirection = 'desc';

    protected function rules(): array
    {
        return [
            'kode' => ['required', 'string', 'max:255', $this->editMode ? 'unique:obats,kode,' . $this->editingId : 'unique:obats,kode'],
            'nama' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:tablet,kapsul,sirup,salep,vitamin,injeksi,tetes'],
            'satuan' => ['required', 'string', 'max:255'],
            'stok' => ['required', 'integer', 'min:0'],
        ];
    }

    public function render()
    {
        $query = Obat::query();

        if ($this->search) {
            $query->where('nama', 'like', '%' . $this->search . '%')->orWhere('kode', 'like', '%' . $this->search . '%');
        }

        $drugs = $query->orderBy($this->sortColumn, $this->sortDirection)->paginate($this->perPage);

        return view('pages.drugs.index', [
            'obat' => $drugs,
        ]);
    }

    public function resetForm(): void
    {
        $this->reset(['kode', 'nama', 'type', 'satuan', 'stok', 'editMode', 'editingId', 'showDeleteConfirm', 'deletingId']);
        $this->type = 'tablet';
        $this->satuan = 'pcs';
        $this->stok = 0;
    }

    public function generatingCode(): string
    {
        $last = Obat::orderByDesc('id')->first();
        $number = $last ? $last->id + 1 : 1;
        return 'OB-' . str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }

    public function creating(): void
    {
        $this->resetForm();
        $this->kode = $this->generatingCode();
        $this->editMode = false;
        $this->editingId = null;
        $this->showModal = true;
    }

    public function editing(int $id): void
    {
        $obat = Obat::findOrFail($id);
        $this->kode = $obat->kode;
        $this->nama = $obat->nama;
        $this->type = $obat->type;
        $this->satuan = $obat->satuan;
        $this->stok = $obat->stok;
        $this->editMode = true;
        $this->editingId = $id;
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $data = [
            'kode' => $validated['kode'],
            'nama' => $validated['nama'],
            'type' => $validated['type'],
            'satuan' => $validated['satuan'],
            'stok' => $validated['stok'],
        ];

        if ($this->editMode && $this->editingId) {
            Obat::findOrFail($this->editingId)->update($data);
            $message = 'Data obat berhasil diperbarui.';
        } else {
            Obat::create($data);
            $message = 'Data obat berhasil ditambahkan.';
        }

        $this->dispatch('toast', type: 'success', message: $message);
        $this->resetForm();
        $this->showModal = false;
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Obat::findOrFail($this->deletingId)->delete();
            $this->dispatch('toast', type: 'warning', message: 'Data obat berhasil dihapus.');
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

    public function totalDrugs(): int
    {
        return Obat::count();
    }

    public function lowStockCount(): int
    {
        return Obat::where('stok', '<', 10)->count();
    }

    public function outOfStockCount(): int
    {
        return Obat::where('stok', 0)->count();
    }

    protected function view($data = [])
    {
        return app('view')->file('D:\laragon\www\rekam-medis\storage\framework/views/livewire/views/0bb86448.blade.php', $data);
    }
};

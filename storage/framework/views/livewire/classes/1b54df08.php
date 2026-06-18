<?php

use Livewire\Component;
use App\Models\Pasien;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

return new class extends Component {
    use WithPagination;

    // Form fields
    public string $nama = '';
    public string $jenis_pasien = 'dewasa';
    public ?string $nik = null;
    public ?string $no_telpon = null;
    public ?string $alamat = null;
    public ?string $tanggal_lahir = null;
    public string $jenis_kelamin = 'L';
    public ?string $golongan_darah = null;
    public ?string $alergi = null;

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
            'nama' => ['required', 'string', 'max:255'],
            'jenis_pasien' => ['required', 'in:bayi,anak-anak,dewasa'],
            'nik' => ['nullable', 'string', 'max:16'],
            'no_telpon' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'golongan_darah' => ['nullable', 'in:A,B,AB,O,A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'alergi' => ['nullable', 'string'],
        ];
    }

    public function render()
    {
        $query = Pasien::query();

        if ($this->search) {
            $query
                ->where('nama', 'like', '%' . $this->search . '%')
                ->orWhere('nik', 'like', '%' . $this->search . '%')
                ->orWhere('no_telpon', 'like', '%' . $this->search . '%');
        }

        $pasiens = $query->orderBy($this->sortColumn, $this->sortDirection)->paginate($this->perPage);

        return view('pages.patient.index', [
            'patients' => $pasiens,
        ]);
    }

    public function resetForm(): void
    {
        $this->reset(['nama', 'jenis_pasien', 'nik', 'no_telpon', 'alamat', 'tanggal_lahir', 'jenis_kelamin', 'golongan_darah', 'alergi', 'editMode', 'editingId', 'showDeleteConfirm', 'deletingId']);
        $this->jenis_kelamin = 'L';
        $this->jenis_pasien = 'dewasa';
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
        $pasien = Pasien::findOrFail($id);
        $this->nama = $pasien->nama;
        $this->jenis_pasien = $pasien->jenis_pasien ?: 'dewasa';
        $this->nik = $pasien->nik;
        $this->no_telpon = $pasien->no_telpon;
        $this->alamat = $pasien->alamat;
        $this->tanggal_lahir = $pasien->tanggal_lahir?->format('Y-m-d');
        $this->jenis_kelamin = $pasien->jenis_kelamin ?: 'L';
        $this->golongan_darah = $pasien->golongan_darah;
        $this->alergi = $pasien->alergi;
        $this->editMode = true;
        $this->editingId = $id;
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $data = [
            'nama' => $validated['nama'],
            'jenis_pasien' => $validated['jenis_pasien'],
            'nik' => $validated['nik'],
            'no_telpon' => $validated['no_telpon'],
            'alamat' => $validated['alamat'],
            'tanggal_lahir' => $validated['tanggal_lahir'],
            'jenis_kelamin' => $validated['jenis_kelamin'],
            'golongan_darah' => $validated['golongan_darah'],
            'alergi' => $validated['alergi'],
        ];

        if ($this->editMode && $this->editingId) {
            Pasien::findOrFail($this->editingId)->update($data);
            $message = 'Data pasien berhasil diperbarui.';
        } else {
            Pasien::create($data);
            $message = 'Data pasien berhasil ditambahkan.';
        }

        $this->dispatch('toast', type: 'success', message: $message);
        $this->resetForm();
        $this->showModal = false;
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Pasien::findOrFail($this->deletingId)->delete();
            $this->dispatch('toast', type: 'warning', message: 'Data pasien berhasil dihapus.');
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

    public function sortBy(string $column): void
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function totalPatients(): int
    {
        return Pasien::count();
    }

    public function maleCount(): int
    {
        return Pasien::where('jenis_kelamin', 'L')->count();
    }

    public function femaleCount(): int
    {
        return Pasien::where('jenis_kelamin', 'P')->count();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    protected function view($data = [])
    {
        return app('view')->file('D:\laragon\www\rekam-medis\storage\framework/views/livewire/views/1b54df08.blade.php', $data);
    }
};

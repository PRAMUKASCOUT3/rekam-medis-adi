<?php

use Livewire\Component;
use App\Models\Pasien;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

new class extends Component {
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
};
?>

<div>
    <!-- Page Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                <i class="bi bi-person-hearts text-emerald-600 mr-2"></i>Data Pasien
            </h1>
            <p class="mt-1 text-sm text-gray-500">Kelola data pasien &amp; informasi kesehatan</p>
        </div>
        <button type="button" wire:click="creating"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-elevation-sm hover:shadow-elevation-md transition-all">
            <i class="bi bi-plus-lg"></i>
            Tambah Pasien
        </button>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
        <!-- Total Patients -->
        <div class="bg-white rounded-2xl p-5 shadow-elevation-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div class="p-3 rounded-xl bg-emerald-50">
                    <i class="bi bi-people-fill text-2xl text-emerald-600"></i>
                </div>
                <span class="text-xs text-gray-400 font-medium">Total</span>
            </div>
            <p class="mt-4 text-sm text-gray-500 font-medium">Semua Pasien</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $this->totalPatients() }}</p>
        </div>

        <!-- Male -->
        <div class="bg-white rounded-2xl p-5 shadow-elevation-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div class="p-3 rounded-xl bg-blue-50">
                    <i class="bi bi-gender-male text-2xl text-blue-600"></i>
                </div>
                <span class="text-xs text-gray-400 font-medium">Laki-laki</span>
            </div>
            <p class="mt-4 text-sm text-gray-500 font-medium">Pasien Pria</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $this->maleCount() }}</p>
        </div>

        <!-- Female -->
        <div class="bg-white rounded-2xl p-5 shadow-elevation-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div class="p-3 rounded-xl bg-pink-50">
                    <i class="bi bi-gender-female text-2xl text-pink-500"></i>
                </div>
                <span class="text-xs text-gray-400 font-medium">Perempuan</span>
            </div>
            <p class="mt-4 text-sm text-gray-500 font-medium">Pasien Wanita</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $this->femaleCount() }}</p>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white rounded-2xl shadow-elevation-sm border border-gray-100 p-5 sm:p-6 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                <input type="text" wire:model.live.debounce="search"
                    placeholder="Cari nama, NIK, atau telepon pasien..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all placeholder:text-gray-400" />
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-500 whitespace-nowrap">Per halaman:</label>
                <select wire:model="perPage"
                    class="px-3 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-700 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all cursor-pointer">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Patients Table Card -->
    <div class="bg-white rounded-2xl shadow-elevation-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px]">
                <thead>
                    <tr class="text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50/80">
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('nama')">
                            <div class="flex items-center gap-1.5">
                                <span>Nama</span>
                                @if ($sortColumn === 'nama')
                                    <i
                                        class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up-fill' : 'down-fill' }} text-emerald-600"></i>
                                @else
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none"
                            wire:click="sortBy('jenis_pasien')">
                            <div class="flex items-center gap-1.5">
                                <span>Jenis Pasien</span>
                                @if ($sortColumn === 'jenis_pasien')
                                    <i
                                        class="bi {{ $sortDirection === 'asc' ? 'bi bi-balloon-heart-fill' : 'bi-person-heart' }} text-emerald-600"></i>
                                @else
                                    <i class="bi bi-person text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('nik')">
                            <div class="flex items-center gap-1.5">
                                <span>NIK</span>
                                @if ($sortColumn === 'nik')
                                    <i
                                        class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up-fill' : 'down-fill' }} text-emerald-600"></i>
                                @else
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none"
                            wire:click="sortBy('jenis_kelamin')">
                            <div class="flex items-center gap-1.5">
                                <span>Jenis Kelamin</span>
                                @if ($sortColumn === 'jenis_kelamin')
                                    <i
                                        class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up-fill' : 'down-fill' }} text-emerald-600"></i>
                                @else
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5">No. Telpon</th>
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none"
                            wire:click="sortBy('tanggal_lahir')">
                            <div class="flex items-center gap-1.5">
                                <span>Tanggal Lahir</span>
                                @if ($sortColumn === 'tanggal_lahir')
                                    <i
                                        class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up-fill' : 'down-fill' }} text-emerald-600"></i>
                                @else
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('created_at')">
                            <div class="flex items-center gap-1.5">
                                <span>Dibuat</span>
                                @if ($sortColumn === 'created_at')
                                    <i
                                        class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up-fill' : 'down-fill' }} text-emerald-600"></i>
                                @else
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="text-center px-6 py-3.5 font-semibold">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse($patients as $pasien)
                        <tr class="table-row-hover hover:bg-gray-50/60 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white font-bold text-sm shadow-elevation-sm shrink-0">
                                        {{ strtoupper(substr($pasien->nama, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 leading-tight">
                                            {{ $pasien->nama }}</p>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            @if ($pasien->alergi)
                                                <i
                                                    class="bi bi-exclamation-triangle-fill text-amber-500 mr-1"></i>Alergi
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $jenisLabels = [
                                        'bayi' => 'Bayi',
                                        'anak-anak' => 'Anak-anak',
                                        'dewasa' => 'Dewasa',
                                    ];

                                    $jenisColors = [
                                        'bayi' => ['bg-pink-50', 'text-pink-700'],
                                        'anak-anak' => ['bg-blue-50', 'text-blue-700'],
                                        'dewasa' => ['bg-emerald-50', 'text-emerald-700'],
                                    ];

                                    // Pakai emoji agar pasti tampil
                                    $jenisIcons = [
                                        'bayi' => '👶',
                                        'anak-anak' => '🧒',
                                        'dewasa' => '🧑',
                                    ];

                                    $jenis = $jenisLabels[$pasien->jenis_pasien] ?? ucfirst($pasien->jenis_pasien);

                                    $colors = $jenisColors[$pasien->jenis_pasien] ?? ['bg-gray-50', 'text-gray-700'];

                                    $icon = $jenisIcons[$pasien->jenis_pasien] ?? '❓';
                                @endphp

                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg {{ $colors[0] }} {{ $colors[1] }} text-xs font-semibold">

                                    <span class="text-sm">{{ $icon }}</span>
                                    {{ $jenis }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600 font-mono">{{ $pasien->nik ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                @if ($pasien->jenis_kelamin === 'L')
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 text-xs font-semibold">
                                        <i class="bi bi-gender-male text-[10px]"></i>Laki-laki
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-pink-50 text-pink-700 text-xs font-semibold">
                                        <i class="bi bi-gender-female text-[10px]"></i>Perempuan
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <i class="bi bi-telephone text-gray-400"></i>
                                    <span>{{ $pasien->no_telpon ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    @if ($pasien->tanggal_lahir)
                                        <span
                                            class="text-sm text-gray-600">{{ $pasien->tanggal_lahir->format('d M Y') }}</span>
                                        <span class="text-xs text-gray-400">{{ $pasien->tanggal_lahir->age }}
                                            tahun</span>
                                    @else
                                        <span class="text-sm text-gray-400">-</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span
                                        class="text-sm text-gray-600">{{ $pasien->created_at->format('d M Y') }}</span>
                                    <span class="text-xs text-gray-400">{{ $pasien->created_at->format('H:i') }}
                                        WIB</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" wire:click="editing({{ $pasien->id }})"
                                        class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 transition-colors"
                                        title="Edit">
                                        <i class="bi bi-pencil-square text-lg"></i>
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $pasien->id }})"
                                        class="p-2 rounded-lg text-red-500 hover:bg-red-50 transition-colors"
                                        title="Hapus">
                                        <i class="bi bi-trash3 text-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="p-4 rounded-2xl bg-gray-50">
                                        <i class="bi bi-person-x text-4xl text-gray-300"></i>
                                    </div>
                                    <p class="text-gray-500 font-medium">Tidak ada data pasien ditemukan</p>
                                    <p class="text-sm text-gray-400">Coba kata kunci lain atau tambah pasien baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($patients->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/50">
                <p class="text-sm text-gray-500">
                    Menampilkan
                    <span class="font-semibold text-gray-700">{{ $patients->firstItem() ?? 0 }}</span>
                    –
                    <span class="font-semibold text-gray-700">{{ $patients->lastItem() }}</span>
                    dari
                    <span class="font-semibold text-gray-700">{{ $patients->total() }}</span>
                    pasien
                </p>
                <div class="flex gap-1">
                    {{ $patients->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- Create / Edit Modal -->
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 transition-opacity duration-200
        {{ $showModal ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none' }}"
        wire:click="closeModal">

        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

        <!-- Modal Content -->
        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-elevation-xl border border-gray-100 overflow-hidden
            transform transition-all duration-200
            {{ $showModal ? 'scale-100 translate-y-0' : 'scale-95 translate-y-4' }}"
            wire:click.stop>

            <!-- Modal Header -->
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">
                        {{ $editMode ? 'Edit Data Pasien' : 'Tambah Pasien Baru' }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-0.5">
                        {{ $editMode ? 'Perbarui informasi data pasien' : 'Isi data pasien dengan lengkap' }}
                    </p>
                </div>
                <button type="button" wire:click="closeModal"
                    class="p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                    <i class="bi bi-x-lg text-xl"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-5 space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- Nama -->
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="nama">
                            Nama Lengkap <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="nama" wire:model="nama" placeholder="Masukkan nama lengkap"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
                        @error('nama')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Jenis Pasien -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Jenis Pasien <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach (['bayi' => 'Bayi', 'anak-anak' => 'Anak-anak', 'dewasa' => 'Dewasa'] as $value => $label)
                                <button type="button" wire:click="$set('jenis_pasien', '{{ $value }}')"
                                    class="flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl border-2 text-sm font-semibold transition-all
                                        {{ $jenis_pasien === $value
                                            ? 'border-emerald-500 bg-emerald-50 text-emerald-700'
                                            : 'border-gray-200 text-gray-500 hover:border-gray-300 hover:bg-gray-50' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                        @error('jenis_pasien')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- NIK -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="nik">
                            NIK
                        </label>
                        <input type="text" id="nik" wire:model="nik" placeholder="16 digit NIK"
                            maxlength="16"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
                        @error('nik')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- No. Telepon -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="no_telpon">
                            Nomor Telepon
                        </label>
                        <input type="text" id="no_telpon" wire:model="no_telpon" placeholder="08xxxxxxxxxx"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
                        @error('no_telpon')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tanggal Lahir -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="tanggal_lahir">
                            Tanggal Lahir
                        </label>
                        <input type="date" id="tanggal_lahir" wire:model="tanggal_lahir"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
                        @error('tanggal_lahir')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Golongan Darah -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Golongan Darah
                        </label>
                        <div class="grid grid-cols-4 gap-2">
                            @foreach (['A', 'B', 'AB', 'O', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $type)
                                <button type="button" wire:click="$set('golongan_darah', '{{ $type }}')"
                                    class="px-2.5 py-2 rounded-lg border-2 text-xs font-semibold transition-all
                                        {{ $golongan_darah === $type
                                            ? 'border-emerald-500 bg-emerald-50 text-emerald-700'
                                            : 'border-gray-200 text-gray-500 hover:border-gray-300 hover:bg-gray-50' }}">
                                    {{ $type }}
                                </button>
                            @endforeach
                        </div>
                        @error('golongan_darah')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Jenis Kelamin -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Jenis Kelamin <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" wire:click="$set('jenis_kelamin', 'L')"
                                class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border-2 text-sm font-semibold transition-all
                                    {{ $jenis_kelamin === 'L'
                                        ? 'border-blue-500 bg-blue-50 text-blue-700'
                                        : 'border-gray-200 text-gray-500 hover:border-gray-300 hover:bg-gray-50' }}">
                                <i
                                    class="bi bi-gender-male {{ $jenis_kelamin === 'L' ? 'text-blue-600' : 'text-gray-400' }}"></i>
                                Laki-laki
                            </button>
                            <button type="button" wire:click="$set('jenis_kelamin', 'P')"
                                class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border-2 text-sm font-semibold transition-all
                                    {{ $jenis_kelamin === 'P'
                                        ? 'border-pink-500 bg-pink-50 text-pink-700'
                                        : 'border-gray-200 text-gray-500 hover:border-gray-300 hover:bg-gray-50' }}">
                                <i
                                    class="bi bi-gender-female {{ $jenis_kelamin === 'P' ? 'text-pink-500' : 'text-gray-400' }}"></i>
                                Perempuan
                            </button>
                        </div>
                        @error('jenis_kelamin')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Alamat -->
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="alamat">
                            Alamat
                        </label>
                        <textarea id="alamat" wire:model="alamat" rows="2" placeholder="Masukkan alamat lengkap"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all resize-none"></textarea>
                        @error('alamat')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Alergi -->
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="alergi">
                            Alergi
                        </label>
                        <textarea id="alergi" wire:model="alergi" rows="2" placeholder="Tuliskan alergi yang dimiliki (jika ada)"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all resize-none"></textarea>
                        @error('alergi')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3 bg-gray-50/50">
                <button type="button" wire:click="closeModal"
                    class="px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-colors">
                    Batal
                </button>
                <button type="button" wire:click="save"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-elevation-sm transition-all">
                    <i class="bi bi-check-lg"></i>
                    {{ $editMode ? 'Simpan Perubahan' : 'Tambah Pasien' }}
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 transition-opacity duration-200
        {{ $showDeleteConfirm ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none' }}"
        wire:click="closeDeleteModal">

        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

        <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-elevation-xl border border-gray-100 p-6
            transform transition-all duration-200
            {{ $showDeleteConfirm ? 'scale-100 translate-y-0' : 'scale-95 translate-y-4' }}"
            wire:click.stop>

            <div class="flex flex-col items-center text-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-red-50 flex items-center justify-center">
                    <i class="bi bi-exclamation-triangle-fill text-2xl text-red-500"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Hapus Data Pasien?</h3>
                    <p class="text-sm text-gray-500 mt-1">Tindakan ini tidak dapat dibatalkan. Data pasien akan dihapus
                        secara permanen dari sistem.</p>
                </div>
                <div class="flex items-center gap-3 w-full">
                    <button type="button" wire:click="closeDeleteModal"
                        class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                        Batal
                    </button>
                    <button type="button" wire:click="delete"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-semibold transition-colors">
                        <i class="bi bi-trash3"></i>
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast -->
    @script
        <script>
            Livewire.on('toast', (event) => {
                const toast = document.createElement('div');
                const icon = event.type === 'success' ?
                    '<i class="bi bi-check-circle-fill text-emerald-500"></i>' :
                    event.type === 'warning' ?
                    '<i class="bi bi-exclamation-circle-fill text-amber-500"></i>' :
                    '<i class="bi bi-x-circle-fill text-red-500"></i>';

                toast.className =
                    `fixed bottom-6 right-6 z-[100] flex items-center gap-3 px-5 py-3 rounded-xl shadow-elevation-lg border border-gray-100 bg-white transition-all duration-300 translate-y-2 opacity-0`;
                toast.innerHTML = `
                ${icon}
                <span class="text-sm font-semibold text-gray-800">${event.message}</span>
            `;

                document.body.appendChild(toast);

                requestAnimationFrame(() => {
                    toast.classList.remove('translate-y-2', 'opacity-0');
                    toast.classList.add('translate-y-0', 'opacity-100');
                });

                setTimeout(() => {
                    toast.classList.remove('translate-y-0', 'opacity-100');
                    toast.classList.add('translate-y-2', 'opacity-0');
                    setTimeout(() => toast.remove(), 300);
                }, 3500);
            });
        </script>
    @endscript
</div>

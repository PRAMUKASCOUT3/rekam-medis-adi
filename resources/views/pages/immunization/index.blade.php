<?php

use Livewire\Component;
use App\Models\Imunisasi;
use App\Models\Pasien;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

new class extends Component {
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
};
?>

<div>
    <!-- Page Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                <i class="bi bi-shield-fill-check text-purple-600 mr-2"></i>Data Imunisasi
            </h1>
            <p class="mt-1 text-sm text-gray-500">Kelola data imunisasi bayi</p>
        </div>
        <button
            type="button"
            wire:click="creating"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold shadow-elevation-sm hover:shadow-elevation-md transition-all">
            <i class="bi bi-plus-lg"></i>
            Tambah Imunisasi
        </button>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 mb-8">
        <!-- Total -->
        <div class="bg-white rounded-2xl p-5 shadow-elevation-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div class="p-3 rounded-xl bg-purple-50">
                    <i class="bi bi-shield-fill text-2xl text-purple-600"></i>
                </div>
                <span class="text-xs text-gray-400 font-medium">Total</span>
            </div>
            <p class="mt-4 text-sm text-gray-500 font-medium">Semua Data</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $this->totalRecords() }}</p>
        </div>

        <!-- Bayi -->
        <div class="bg-white rounded-2xl p-5 shadow-elevation-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div class="p-3 rounded-xl bg-blue-50">
                    <i class="bi bi-person-dots-fill text-2xl text-blue-600"></i>
                </div>
                <span class="text-xs text-gray-400 font-medium">Bayi</span>
            </div>
            <p class="mt-4 text-sm text-gray-500 font-medium">Imunisasi Bayi</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $this->bayiCount() }}</p>
        </div>

        <!-- Hari ini -->
        <div class="bg-white rounded-2xl p-5 shadow-elevation-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div class="p-3 rounded-xl bg-amber-50">
                    <i class="bi bi-calendar-check-fill text-2xl text-amber-600"></i>
                </div>
                <span class="text-xs text-gray-400 font-medium">Hari ini</span>
            </div>
            <p class="mt-4 text-sm text-gray-500 font-medium">Imunisasi Hari Ini</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $this->todayCount() }}</p>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white rounded-2xl shadow-elevation-sm border border-gray-100 p-5 sm:p-6 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                <input
                    type="text"
                    wire:model.live.debounce="search"
                    placeholder="Cari nama pasien, jenis imunisasi, atau nama orang tua..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all placeholder:text-gray-400" />
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-500 whitespace-nowrap">Per halaman:</label>
                <select
                    wire:model="perPage"
                    class="px-3 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-700 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all cursor-pointer">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="bg-white rounded-2xl shadow-elevation-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px]">
                <thead>
                    <tr class="text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50/80">
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('tanggal_imunisasi')">
                            <div class="flex items-center gap-1.5">
                                <span>Tanggal</span>
                                @if($sortColumn === 'tanggal_imunisasi')
                                    <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up-fill' : 'down-fill' }} text-purple-600"></i>
                                @else
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('pasien_id')">
                            <div class="flex items-center gap-1.5">
                                <span>Nama Bayi</span>
                                @if($sortColumn === 'pasien_id')
                                    <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up-fill' : 'down-fill' }} text-purple-600"></i>
                                @else
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5">Tanggal Lahir</th>
                        <th class="text-left px-6 py-3.5">Nama Orang Tua</th>
                        <th class="text-left px-6 py-3.5">Alamat</th>
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('jenis_imunisasi')">
                            <div class="flex items-center gap-1.5">
                                <span>Jenis Imunisasi</span>
                                @if($sortColumn === 'jenis_imunisasi')
                                    <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up-fill' : 'down-fill' }} text-purple-600"></i>
                                @else
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5">Pengobatan</th>
                        <th class="text-left px-6 py-3.5">Petugas</th>
                        <th class="text-center px-6 py-3.5 font-semibold">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse($records as $record)
                        <tr class="table-row-hover hover:bg-gray-50/60 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-600 font-semibold">{{ $record->tanggal_imunisasi?->format('d M Y') ?? '-' }}</span>
                                    @if($record->tanggal_imunisasi && $record->tanggal_imunisasi->isToday())
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-semibold mt-1 w-fit">
                                            <i class="bi bi-circle-fill text-[6px]"></i>Hari ini
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm shadow-elevation-sm shrink-0">
                                        {{ strtoupper(substr($record->pasien?->nama ?? '?', 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 leading-tight">{{ $record->pasien?->nama ?? '-' }}</p>
                                        @php
                                            $jenisColor = match($record->pasien?->jenis_pasien) {
                                                'bayi' => ['bg-purple-50', 'text-purple-700'],
                                                default => ['bg-gray-50', 'text-gray-700'],
                                            };
                                        @endphp
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md {{ $jenisColor[0] }} {{ $jenisColor[1] }} text-[10px] font-semibold mt-0.5">
                                            {{ ucfirst(str_replace('-', ' ', $record->pasien?->jenis_pasien ?? '-')) }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ $record->tanggal_lahir_pasien ?? ($record->pasien?->tanggal_lahir?->format('d M Y') ?? '-') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ $record->nama_orang_tua ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="max-w-[150px] truncate block text-sm text-gray-600">{{ $record->alamat ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-purple-50 text-purple-700 text-xs font-semibold">
                                    <i class="bi bi-shield-fill-check text-[10px]"></i>{{ $record->jenis_imunisasi }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="max-w-[150px] truncate block text-sm text-gray-600">{{ $record->pengobatan ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ $record->user?->name ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-1">
                                    <button
                                        type="button"
                                        wire:click="editing({{ $record->id }})"
                                        class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 transition-colors"
                                        title="Edit">
                                        <i class="bi bi-pencil-square text-lg"></i>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="confirmDelete({{ $record->id }})"
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
                                        <i class="bi bi-shield-x text-4xl text-gray-300"></i>
                                    </div>
                                    <p class="text-gray-500 font-medium">Tidak ada data imunisasi ditemukan</p>
                                    <p class="text-sm text-gray-400">Coba kata kunci lain atau tambah data baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($records->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/50">
                <p class="text-sm text-gray-500">
                    Menampilkan
                    <span class="font-semibold text-gray-700">{{ $records->firstItem() ?? 0 }}</span>
                    –
                    <span class="font-semibold text-gray-700">{{ $records->lastItem() }}</span>
                    dari
                    <span class="font-semibold text-gray-700">{{ $records->total() }}</span>
                    data
                </p>
                <div class="flex gap-1">{{ $records->links() }}</div>
            </div>
        @endif
    </div>

    <!-- Create / Edit Modal -->
    <div
        class="fixed inset-0 z-50 flex items-center justify-center p-4 transition-opacity duration-200
        {{ $showModal ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none' }}"
        wire:click="closeModal">

        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

        <!-- Modal Content -->
        <div
            class="relative w-full max-w-3xl bg-white rounded-2xl shadow-elevation-xl border border-gray-100 overflow-hidden
            transform transition-all duration-200
            {{ $showModal ? 'scale-100 translate-y-0' : 'scale-95 translate-y-4' }}"
            wire:click.stop>

            <!-- Modal Header -->
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">
                        {{ $editMode ? 'Edit Data Imunisasi' : 'Tambah Data Imunisasi' }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-0.5">
                        {{ $editMode ? 'Perbarui data imunisasi' : 'Input data imunisasi baru' }}
                    </p>
                </div>
                <button
                    type="button"
                    wire:click="closeModal"
                    class="p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                    <i class="bi bi-x-lg text-xl"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-5 space-y-5 max-h-[70vh] overflow-y-auto">
                <!-- Row 1: Tanggal Imunisasi + Petugas -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- Tanggal Imunisasi -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="tanggal_imunisasi">
                            Tanggal Imunisasi <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="date"
                            id="tanggal_imunisasi"
                            wire:model="tanggal_imunisasi"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all" />
                        @error('tanggal_imunisasi')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Petugas -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="user_id">
                            Petugas <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="user_id" value="{{ Auth::user()?->name ?? '' }}" readonly
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-100 focus:outline-none cursor-not-allowed" />
                        @error('user_id')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Divider -->
                <div class="border-t border-gray-100"></div>

                <!-- Row 2: Pilih Pasien -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Pasien (Bayi) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative" @click.away.stop="blurPasienSearch">
                        <input type="text" wire:model.live.debounce="pasien_search"
                            wire:focus="focusPasienSearch" wire:blur="blurPasienSearch"
                            wire:keydown.enter.prevent
                            placeholder="Ketik nama bayi untuk mencari..."
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all" />
                        @if ($pasien_focused && !$pasien_id && $filteredPasiens->isNotEmpty())
                            <div
                                class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-elevation-lg max-h-56 overflow-y-auto">
                                @foreach ($filteredPasiens as $p)
                                    <button type="button"
                                        wire:click="selectPasien({{ $p->id }}, '{{ addslashes($p->nama) }}')"
                                        class="w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-purple-50 transition-colors">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center text-purple-600 font-bold text-xs shrink-0">
                                            {{ strtoupper(substr($p->nama, 0, 2)) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $p->nama }}</p>
                                            <p class="text-xs text-gray-400">{{ $p->tanggal_lahir?->format('d M Y') ?? 'Tanpa Tgl Lahir' }}</p>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @if ($pasien_id)
                        <p class="mt-1.5 text-xs text-emerald-600 font-medium">
                            <i class="bi bi-check-circle-fill mr-1"></i>
                            {{ $this->getSelectedPasienName() }}
                            <button type="button" wire:click="clearPasienSearch"
                                class="ml-1 text-gray-400 hover:text-red-500">
                                <i class="bi bi-x"></i>
                            </button>
                        </p>
                    @endif
                    @error('pasien_id')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Row 3: Tanggal Lahir Pasien + Jenis Imunisasi -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- Tanggal Lahir Pasien -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="tanggal_lahir_pasien">
                            Tanggal Lahir Bayi
                        </label>
                        <input
                            type="date"
                            id="tanggal_lahir_pasien"
                            wire:model.live="tanggal_lahir_pasien"
                            placeholder="Terisi otomatis saat memilih pasien"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all" disabled />
                        @error('tanggal_lahir_pasien')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Jenis Imunisasi -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="jenis_imunisasi">
                            Jenis Imunisasi <span class="text-red-500">*</span>
                        </label>
                        <select id="jenis_imunisasi" wire:model="jenis_imunisasi"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all cursor-pointer">
                            <option value="">-- Pilih Jenis Imunisasi --</option>
                            @foreach ($jenisImunisasiList as $jenis)
                                <option value="{{ $jenis }}">{{ $jenis }}</option>
                            @endforeach
                        </select>
                        @error('jenis_imunisasi')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Row 4: Nama Orang Tua + Umur -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="nama_orang_tua">
                            Nama Orang Tua
                        </label>
                        <input
                            type="text"
                            id="nama_orang_tua"
                            wire:model="nama_orang_tua"
                            placeholder="Nama ayah / ibu"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all" />
                        @error('nama_orang_tua')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Umur Pasien
                        </label>
                        <div class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-600 bg-gray-50 min-h-[42.5px] flex items-center">
                            @if($umur_pasien !== null)
                                <span>{{ $umur_pasien }} tahun</span>
                            @else
                                <span class="text-gray-400">Terisi otomatis dari data pasien</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Row 5: Alamat -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="alamat">
                        Alamat
                    </label>
                    <textarea
                        id="alamat"
                        wire:model="alamat"
                        rows="2"
                        placeholder="Alamat pasien (terisi otomatis dari data pasien)"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all resize-none"></textarea>
                    @error('alamat')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Divider -->
                <div class="border-t border-gray-100"></div>

                <!-- Row 6: Pengobatan + Keterangan -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- Pengobatan -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="pengobatan">
                            Pengobatan
                        </label>
                        <textarea
                            id="pengobatan"
                            wire:model="pengobatan"
                            rows="3"
                            placeholder="Deskripsi pengobatan yang diberikan..."
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all resize-none"></textarea>
                        @error('pengobatan')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Keterangan -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="keterangan">
                            Keterangan
                        </label>
                        <textarea
                            id="keterangan"
                            wire:model="keterangan"
                            rows="3"
                            placeholder="Catatan tambahan (misal: reaksi alergi, pemberian ke-2, dll)..."
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all resize-none"></textarea>
                        @error('keterangan')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/50">
                <div class="flex items-center gap-2 text-xs text-gray-400">
                    <i class="bi bi-shield-check"></i>
                    <span>Data dicatat oleh <strong>{{ Auth::user()?->name ?? 'Admin' }}</strong></span>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        wire:click="closeModal"
                        class="px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-colors">
                        Batal
                    </button>
                    <button
                        type="button"
                        wire:click="save"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold shadow-elevation-sm transition-all">
                        <i class="bi bi-check-lg"></i>
                        {{ $editMode ? 'Simpan Perubahan' : 'Tambah Imunisasi' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div
        class="fixed inset-0 z-50 flex items-center justify-center p-4 transition-opacity duration-200
        {{ $showDeleteConfirm ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none' }}"
        wire:click="closeDeleteModal">

        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

        <div
            class="relative w-full max-w-sm bg-white rounded-2xl shadow-elevation-xl border border-gray-100 p-6
            transform transition-all duration-200
            {{ $showDeleteConfirm ? 'scale-100 translate-y-0' : 'scale-95 translate-y-4' }}"
            wire:click.stop>

            <div class="flex flex-col items-center text-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-red-50 flex items-center justify-center">
                    <i class="bi bi-exclamation-triangle-fill text-2xl text-red-500"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Hapus Data Imunisasi?</h3>
                    <p class="text-sm text-gray-500 mt-1">Tindakan ini tidak dapat dibatalkan. Data imunisasi akan dihapus secara permanen dari sistem.</p>
                </div>
                <div class="flex items-center gap-3 w-full">
                    <button
                        type="button"
                        wire:click="closeDeleteModal"
                        class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                        Batal
                    </button>
                    <button
                        type="button"
                        wire:click="delete"
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
            const icon = event.type === 'success'
                ? '<i class="bi bi-check-circle-fill text-emerald-500"></i>'
                : event.type === 'warning'
                    ? '<i class="bi bi-exclamation-circle-fill text-amber-500"></i>'
                    : '<i class="bi bi-x-circle-fill text-red-500"></i>';

            toast.className = `fixed bottom-6 right-6 z-[100] flex items-center gap-3 px-5 py-3 rounded-xl shadow-elevation-lg border border-gray-100 bg-white transition-all duration-300 translate-y-2 opacity-0`;
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

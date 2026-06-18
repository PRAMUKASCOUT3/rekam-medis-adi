<?php
use Livewire\Component;
use App\Models\Delivery;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
?>

<div>
    <!-- Page Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                <i class="bi bi-three-dots-vertical text-emerald-600 mr-2"></i>Data Persalinan
            </h1>
            <p class="mt-1 text-sm text-gray-500">Kelola data persalinan ibu hamil</p>
        </div>
        <button
            type="button"
            wire:click="creating"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-elevation-sm hover:shadow-elevation-md transition-all">
            <i class="bi bi-plus-lg"></i>
            Tambah Data
        </button>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white rounded-2xl shadow-elevation-sm border border-gray-100 p-5 sm:p-6 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                <input
                    type="text"
                    wire:model.live.debounce="search"
                    placeholder="Cari nama ibu, nama ayah, alamat, atau no telpon..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all placeholder:text-gray-400" />
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-500 whitespace-nowrap">Per halaman:</label>
                <select
                    wire:model="perPage"
                    class="px-3 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-700 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all cursor-pointer">
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
        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px]">
                <thead>
                    <tr class="text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50/80">
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('tanggal')">
                            <div class="flex items-center gap-1.5">
                                <span>Tanggal</span>
                                @if($sortColumn === 'tanggal')
                                    <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up-fill' : 'down-fill' }} text-emerald-600"></i>
                                @else
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('nama_istri')">
                            <div class="flex items-center gap-1.5">
                                <span>Nama Ibu</span>
                                @if($sortColumn === 'nama_istri')
                                    <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up-fill' : 'down-fill' }} text-emerald-600"></i>
                                @else
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5">Nama Ayah</th>
                        <th class="text-left px-6 py-3.5">Umur Ibu</th>
                        <th class="text-left px-6 py-3.5">Umur Ayah</th>
                        <th class="text-left px-6 py-3.5">No. Telpon</th>
                        <th class="text-left px-6 py-3.5">Alamat</th>
                        <th class="text-left px-6 py-3.5">Petugas</th>
                        <th class="text-center px-6 py-3.5 font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($records as $record)
                        <tr class="table-row-hover hover:bg-gray-50/60 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-600">{{ $record->tanggal->format('d M Y') }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <p class="text-sm font-semibold text-gray-900 leading-tight">{{ $record->nama_istri ?? '-' }}</p>
                                    @if($record->umur_istri)
                                        <p class="text-xs text-gray-400">{{ $record->umur_istri }} tahun</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ $record->nama_suami ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">
                                    {{ $record->umur_istri ? $record->umur_istri . ' tahun' : '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">
                                    {{ $record->umur_suami ? $record->umur_suami . ' tahun' : '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ $record->no_telpon ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="max-w-[150px] truncate block text-sm text-gray-600">{{ $record->alamat ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ $record->user->name ?? '-' }}</span>
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
                                        <i class="bi bi-three-dots-vertical text-4xl text-gray-300"></i>
                                    </div>
                                    <p class="text-gray-500 font-medium">Tidak ada data persalinan ditemukan</p>
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
            class="relative w-full max-w-4xl bg-white rounded-2xl shadow-elevation-xl border border-gray-100 overflow-hidden
            transform transition-all duration-200
            {{ $showModal ? 'scale-100 translate-y-0' : 'scale-95 translate-y-4' }}"
            wire:click.stop>

            <!-- Modal Header -->
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">
                        {{ $editMode ? 'Edit Data Persalinan' : 'Tambah Data Persalinan' }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-0.5">
                        {{ $editMode ? 'Perbarui data persalinan ibu hamil' : 'Input data persalinan ibu hamil baru' }}
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
            <div class="px-6 py-5 space-y-6 max-h-[70vh] overflow-y-auto">
                <!-- Row 1: Tanggal + Petugas + No. Telpon -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="tanggal">
                            Tanggal <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="date"
                            id="tanggal"
                            wire:model="tanggal"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
                        @error('tanggal')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
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
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="no_telpon">
                            No. Telepon
                        </label>
                        <input
                            type="text"
                            id="no_telpon"
                            wire:model="no_telpon"
                            placeholder="0812-xxxx-xxxx"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
                        @error('no_telpon')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Divider -->
                <div class="border-t border-gray-100"></div>

                <!-- Row 2: Data Pasien - Nama -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="nama_istri">
                            Nama Ibu
                        </label>
                        <input
                            type="text"
                            id="nama_istri"
                            wire:model="nama_istri"
                            placeholder="Nama lengkap ibu hamil"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
                        @error('nama_istri')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="nama_suami">
                            Nama Ayah
                        </label>
                        <input
                            type="text"
                            id="nama_suami"
                            wire:model="nama_suami"
                            placeholder="Nama lengkap ayah"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
                        @error('nama_suami')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Row 3: Umur -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="umur_istri">
                            Umur Ibu (tahun)
                        </label>
                        <input
                            type="number"
                            id="umur_istri"
                            wire:model="umur_istri"
                            placeholder="contoh: 28" min="10" max="60"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
                        @error('umur_istri')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="umur_suami">
                            Umur Ayah (tahun)
                        </label>
                        <input
                            type="number"
                            id="umur_suami"
                            wire:model="umur_suami"
                            placeholder="contoh: 30" min="10" max="70"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
                        @error('umur_suami')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Row 4: Pekerjaan -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="pekerjaan_istri">
                            Pekerjaan Ibu
                        </label>
                        <input
                            type="text"
                            id="pekerjaan_istri"
                            wire:model="pekerjaan_istri"
                            placeholder="Pekerjaan ibu hamil"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
                        @error('pekerjaan_istri')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="pekerjaan_suami">
                            Pekerjaan Ayah
                        </label>
                        <input
                            type="text"
                            id="pekerjaan_suami"
                            wire:model="pekerjaan_suami"
                            placeholder="Pekerjaan ayah"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
                        @error('pekerjaan_suami')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
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
                        placeholder="Alamat lengkap ibu hamil..."
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all resize-none"></textarea>
                    @error('alamat')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Divider -->
                <div class="border-t border-gray-100"></div>

                <!-- Row 6: Keluhan + Tindakan -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="keluhan">
                            Keluhan
                        </label>
                        <textarea
                            id="keluhan"
                            wire:model="keluhan"
                            rows="3"
                            placeholder="Keluhan yang disampaikan ibu..."
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all resize-none"></textarea>
                        @error('keluhan')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="tindakan">
                            Tindakan
                        </label>
                        <textarea
                            id="tindakan"
                            wire:model="tindakan"
                            rows="3"
                            placeholder="Tindakan yang dilakukan..."
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all resize-none"></textarea>
                        @error('tindakan')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Divider -->
                <div class="border-t border-gray-100"></div>

                <!-- Row 7: Keterangan -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="keterangan">
                        Keterangan
                    </label>
                    <textarea
                        id="keterangan"
                        wire:model="keterangan"
                        rows="3"
                        placeholder="Catatan tambahan..."
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all resize-none"></textarea>
                    @error('keterangan')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
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
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-elevation-sm transition-all">
                        <i class="bi bi-check-lg"></i>
                        {{ $editMode ? 'Simpan Perubahan' : 'Tambah Data' }}
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
                <!-- Icon -->
                <div class="w-14 h-14 rounded-2xl bg-red-50 flex items-center justify-center">
                    <i class="bi bi-exclamation-triangle-fill text-2xl text-red-500"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Hapus Data Persalinan?</h3>
                    <p class="text-sm text-gray-500 mt-1">Tindakan ini tidak dapat dibatalkan. Data akan dihapus secara permanen dari sistem.</p>
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
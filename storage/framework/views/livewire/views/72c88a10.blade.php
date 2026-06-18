<?php
use Livewire\Component;
use App\Models\Kb;
use Illuminate\Support\Carbon;
use Livewire\WithPagination;
?>

<div wire:poll.30000>
    @script
        <script>
            Livewire.on('pdf-done', () => {
                Livewire.dispatch('toast', {
                    type: 'success',
                    message: 'Laporan PDF berhasil diunduh!'
                });
            });
            Livewire.on('pdf-error', () => {
                Livewire.dispatch('toast', {
                    type: 'error',
                    message: 'Gagal mengunduh PDF. Coba lagi.'
                });
            });
        </script>
    @endscript

    <!-- Page Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                <i class="bi bi-file-earmark-bar-graph-fill text-emerald-600 mr-2"></i>Laporan Data KB
            </h1>
            <p class="mt-1 text-sm text-gray-500">Lihat dan cetak laporan rekap data KB</p>
        </div>
        <button type="button" wire:click="downloadPdf"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-elevation-sm hover:shadow-elevation-md transition-all">
            <i class="bi bi-filetype-pdf"></i>
            Unduh Laporan PDF
        </button>
    </div>

    <!-- Stat Cards -->
    @php
        $stats = $this->getAllStatistics();
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-4 mb-6">
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Total</p>
            <p class="text-xl font-bold text-gray-900 mt-0.5">{{ number_format($stats['total_semua']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Periode</p>
            <p class="text-xl font-bold text-emerald-600 mt-0.5">{{ number_format($stats['total_dari_sampai']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Pil</p>
            <p class="text-xl font-bold text-blue-600 mt-0.5">{{ number_format($stats['total_pil']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Suntik</p>
            <p class="text-xl font-bold text-purple-600 mt-0.5">{{ number_format($stats['total_suntik']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">IUD</p>
            <p class="text-xl font-bold text-amber-600 mt-0.5">{{ number_format($stats['total_iud']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Implan</p>
            <p class="text-xl font-bold text-pink-600 mt-0.5">{{ number_format($stats['total_implan']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Lainnya</p>
            <p class="text-xl font-bold text-orange-600 mt-0.5">{{ number_format($stats['total_lainnya']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Kunj. Hari Ini</p>
            <p class="text-xl font-bold text-teal-600 mt-0.5">{{ number_format($stats['kunjungan_hari_ini']) }}</p>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white rounded-2xl shadow-elevation-sm border border-gray-100 p-5 sm:p-6 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                <input type="text" wire:model.live.debounce="search"
                    placeholder="Cari nama, No. Regis, metode KB..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all placeholder:text-gray-400" />
            </div>
            <div>
                <label class="sr-only" for="tanggal_dari">Dari Tanggal</label>
                <input type="date" id="tanggal_dari" wire:model="tanggal_dari"
                    class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
            </div>
            <div>
                <label class="sr-only" for="tanggal_sampai">Sampai Tanggal</label>
                <input type="date" id="tanggal_sampai" wire:model="tanggal_sampai"
                    class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
            </div>
            <div class="flex items-center gap-2 shrink-0">
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

    <!-- KB Table Card -->
    <div class="bg-white rounded-2xl shadow-elevation-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px]">
                <thead>
                    <tr class="text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50/80">
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('tanggal')">
                            <div class="flex items-center gap-1.5">
                                <span>Tanggal</span>
                                @if ($sortColumn === 'tanggal')
                                    <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up-fill' : 'down-fill' }} text-emerald-600"></i>
                                @else
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5">No. Regis</th>
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('nama_istri')">
                            <div class="flex items-center gap-1.5">
                                <span>Nama Ibu</span>
                                @if ($sortColumn === 'nama_istri')
                                    <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up-fill' : 'down-fill' }} text-emerald-600"></i>
                                @else
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5">Metode KB</th>
                        <th class="text-left px-6 py-3.5">Kunjungan Ulang</th>
                        <th class="text-left px-6 py-3.5">Petugas</th>
                        <th class="text-center px-6 py-3.5">No. HP</th>
                        <th class="text-center px-6 py-3.5">Alamat</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse ($records as $r)
                        <tr class="table-row-hover hover:bg-gray-50/60 transition-colors">
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ $r->tanggal->format('d M Y') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-mono font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-lg">{{ $r->no_regis }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <p class="text-sm font-semibold text-gray-900 leading-tight">{{ $r->nama_istri }}</p>
                                    @if ($r->umur_istri)
                                        <p class="text-xs text-gray-400">{{ $r->umur_istri }} tahun</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 text-xs font-semibold">
                                    <i class="bi bi-capsule text-[10px]"></i>{{ $r->metode_kb }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">
                                    {{ $r->tanggal_kunjungan_ulang ? $r->tanggal_kunjungan_ulang->format('d M Y') : '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs text-gray-600">{{ $r->user?->name ?? 'N/A' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ $r->no_hp ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ Str::limit($r->alamat, 30) ?? '-' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="p-4 rounded-2xl bg-gray-50">
                                        <i class="bi bi-heart-pulse text-4xl text-gray-300"></i>
                                    </div>
                                    <p class="text-gray-500 font-medium">Tidak ada data KB ditemukan</p>
                                    <p class="text-sm text-gray-400">Coba ubah filter tanggal atau kata kunci pencarian.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($records->hasPages())
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
</div>
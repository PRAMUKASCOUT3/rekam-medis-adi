<?php
use Livewire\Component;
use App\Models\Imunisasi;
use App\Models\Pasien;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\WithPagination;
?>

<div>
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
    </div>

    <!-- Page Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                <i class="bi bi-file-earmark-bar-graph-fill text-indigo-600 mr-2"></i>Laporan Data Imunisasi
            </h1>
            <p class="mt-1 text-sm text-gray-500">Lihat dan cetak laporan rekap data imunisasi</p>
        </div>
        <button type="button" wire:click="downloadPdf"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-elevation-sm hover:shadow-elevation-md transition-all">
            <i class="bi bi-filetype-pdf"></i>
            Unduh Laporan PDF
        </button>
    </div>

    <!-- Stat Cards (from filter period) -->
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
            <p class="text-xl font-bold text-indigo-600 mt-0.5">{{ number_format($stats['total_periode']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Hari Ini</p>
            <p class="text-xl font-bold text-blue-600 mt-0.5">{{ number_format($stats['hari_ini']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Bulan Ini</p>
            <p class="text-xl font-bold text-emerald-600 mt-0.5">{{ number_format($stats['bulan_ini']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Ada Catatan</p>
            <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($stats['dengan_keterangan']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Ada Pengobatan</p>
            <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($stats['dengan_pengobatan']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Ada Alamat</p>
            <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($stats['dengan_alamat']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Jenis Vaksin</p>
            <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($stats['total_jenis']) }}</p>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white rounded-2xl shadow-elevation-sm border border-gray-100 p-5 sm:p-6 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">

            <!-- Cari -->
            <div class="relative flex-1">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                <input type="text" wire:model.live.debounce="search"
                    placeholder="Cari nama pasien, jenis imunisasi, nama orang tua, atau petugas..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-gray-400" />
            </div>

            <!-- Tanggal Dari -->
            <div>
                <label class="sr-only" for="tanggal_dari">Dari Tanggal</label>
                <input type="date" id="tanggal_dari" wire:model="tanggal_dari"
                    class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" />
            </div>

            <!-- Tanggal Sampai -->
            <div>
                <label class="sr-only" for="tanggal_sampai">Sampai Tanggal</label>
                <input type="date" id="tanggal_sampai" wire:model="tanggal_sampai"
                    class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" />
            </div>

            <!-- Per Halaman -->
            <div class="flex items-center gap-2 shrink-0">
                <label class="text-sm text-gray-500 whitespace-nowrap">Per halaman:</label>
                <select wire:model="perPage"
                    class="px-3 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-700 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all cursor-pointer">
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
                        <th class="text-center px-6 py-3.5 w-12">No</th>
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('tanggal_imunisasi')">
                            <div class="flex items-center gap-1.5">
                                <span>Tgl Imunisasi</span>
                                @if ($sortColumn === 'tanggal_imunisasi')
                                    <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up-fill' : 'down-fill' }} text-indigo-600"></i>
                                @else
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5">Nama Pasien</th>
                        <th class="text-center px-6 py-3.5">JK</th>
                        <th class="text-center px-6 py-3.5">Umur</th>
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('jenis_imunisasi')">
                            <div class="flex items-center gap-1.5">
                                <span>Jenis Imunisasi</span>
                                @if ($sortColumn === 'jenis_imunisasi')
                                    <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up-fill' : 'down-fill' }} text-indigo-600"></i>
                                @else
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5">Nama Orang Tua</th>
                        <th class="text-left px-6 py-3.5">Petugas</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse ($records as $i => $r)
                        <tr class="table-row-hover hover:bg-gray-50/60 transition-colors">
                            <td class="px-6 py-4 text-center text-sm text-gray-500">{{ $records->firstItem() + $i }}</td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-600">{{ $r->tanggal_imunisasi->format('d/m/Y') }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-semibold text-gray-900 leading-tight">{{ $r->pasien?->nama ?? 'N/A' }}</p>
                                        @if ($r->pasien?->jenis_pasien === 'bayi' || ($r->pasien?->tanggal_lahir && \Carbon\Carbon::parse($r->pasien->tanggal_lahir)->age < 2))
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 text-[10px] font-bold">Bayi</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-400">
                                        {{ $r->pasien?->tanggal_lahir ? $r->pasien->tanggal_lahir->format('d/m/Y') : ($r->tanggal_lahir_pasien ?: '-') }}
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if ($r->pasien?->jenis_kelamin === 'L')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-xs font-semibold">Laki-laki</span>
                                @elseif($r->pasien?->jenis_kelamin === 'P')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-pink-50 text-pink-700 text-xs font-semibold">Perempuan</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-gray-50 text-gray-700 text-xs font-semibold">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $dob = $r->pasien?->tanggal_lahir ?? ($r->tanggal_lahir_pasien ? \Carbon\Carbon::parse($r->tanggal_lahir_pasien) : null);
                                    $age = $dob ? $dob->age . ' thn' : '-';
                                @endphp
                                <span class="text-sm font-semibold text-gray-700">{{ $age }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 text-xs font-semibold">
                                    <i class="bi bi-shield-fill-check mr-1"></i>{{ $r->jenis_imunisasi }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-600">{{ $r->nama_orang_tua ?? '-' }}</span>
                                    @if ($r->alamat)
                                        <span class="text-xs text-gray-400">{{ Str::limit($r->alamat, 40) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ $r->user?->name ?? 'N/A' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="p-4 rounded-2xl bg-gray-50">
                                        <i class="bi bi-shield-plus text-4xl text-gray-300"></i>
                                    </div>
                                    <p class="text-gray-500 font-medium">Tidak ada data imunisasi</p>
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
                <div class="flex gap-1">
                    {{ $records->links() }}
                </div>
            </div>
        @endif
    </div>

</div>

@script
    <script>
        Livewire.on('toast', (event) => {
            const toast = document.createElement('div');
            const icon = event.type === 'success' ?
                '<i class="bi bi-check-circle-fill text-emerald-500"></i>' :
                event.type === 'error' ?
                '<i class="bi bi-x-circle-fill text-red-500"></i>' :
                '<i class="bi bi-exclamation-circle-fill text-amber-500"></i>';

            toast.className =
                `fixed bottom-6 right-6 z-[100] flex items-center gap-3 px-5 py-3 rounded-xl shadow-elevation-lg border border-gray-100 bg-white transition-all duration-300 translate-y-2 opacity-0`;
            toast.innerHTML = icon + '<span class="text-sm font-semibold text-gray-800">' + event.message +
                '</span>';
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
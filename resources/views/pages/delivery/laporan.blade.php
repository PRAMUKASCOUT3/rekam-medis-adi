<?php

use Livewire\Component;
use App\Models\Delivery;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    // Filter
    public ?string $tanggal_dari = null;
    public ?string $tanggal_sampai = null;
    public string $search = '';
    public int $perPage = 10;

    // Sorting
    public string $sortColumn = 'tanggal';
    public string $sortDirection = 'desc';

    // Calculated
    public int $total_persalinan = 0;

    protected function rules(): array
    {
        return [];
    }

    public function mount(): void
    {
        $this->tanggal_dari = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->tanggal_sampai = Carbon::now()->format('Y-m-d');
    }

    public function render()
    {
        $query = Delivery::query()->with('user:id,name');

        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nama_istri', 'like', '%' . $this->search . '%')
                    ->orWhere('nama_suami', 'like', '%' . $this->search . '%')
                    ->orWhere('no_telpon', 'like', '%' . $this->search . '%')
                    ->orWhere('alamat', 'like', '%' . $this->search . '%')
                    ->orWhereHas('user', function ($sq) {
                        $sq->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        // Apply date filter on tanggal
        if ($this->tanggal_dari) {
            $query->whereDate('tanggal', '>=', $this->tanggal_dari);
        }
        if ($this->tanggal_sampai) {
            $query->whereDate('tanggal', '<=', $this->tanggal_sampai);
        }

        $records = $query->orderBy($this->sortColumn, $this->sortDirection)->paginate($this->perPage);

        $this->total_persalinan = Delivery::count();

        return view('pages.delivery.laporan', [
            'records' => $records,
            'total_persalinan' => $this->total_persalinan,
            'perPage' => $this->perPage,
            'sortColumn' => $this->sortColumn,
            'sortDirection' => $this->sortDirection,
        ]);
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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTanggalDari(): void
    {
        $this->resetPage();
    }

    public function updatedTanggalSampai(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    /**
     * Generate and download PDF report
     */
    public function downloadPdf()
    {
        $query = Delivery::query()->with('user:id,name');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nama_istri', 'like', '%' . $this->search . '%')
                    ->orWhere('nama_suami', 'like', '%' . $this->search . '%')
                    ->orWhere('no_telpon', 'like', '%' . $this->search . '%')
                    ->orWhere('alamat', 'like', '%' . $this->search . '%')
                    ->orWhereHas('user', function ($sq) {
                        $sq->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        if ($this->tanggal_dari) {
            $query->whereDate('tanggal', '>=', $this->tanggal_dari);
        }
        if ($this->tanggal_sampai) {
            $query->whereDate('tanggal', '<=', $this->tanggal_sampai);
        }

        $records = $query->orderBy('tanggal', 'desc')->get();

        // FIX UTF-8 ERROR
        $records = $records->map(function ($item) {
            foreach ($item->getAttributes() as $key => $value) {
                if (is_string($value)) {
                    $item->$key = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                }
            }
            if ($item->user) {
                foreach ($item->user->getAttributes() as $key => $value) {
                    if (is_string($value)) {
                        $item->user->$key = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    }
                }
            }
            return $item;
        });

        $total = $records->count();
        $now = Carbon::now()->format('d/m/Y H:i');

        $html = view('pdf.delivery_report', [
            'records' => $records,
            'total' => $total,
            'tanggal_dari' => $this->tanggal_dari,
            'tanggal_sampai' => $this->tanggal_sampai,
            'search' => $this->search,
            'printed_at' => $now,
        ])->render();

        // Normalize HTML UTF-8
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('A4', 'landscape')
            ->setOptions([
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
            ]);

        $fileName = 'laporan-persalinan-' . Carbon::now()->format('Y-m-d_Hi') . '.pdf';

        return response()->streamDownload(fn() => print $pdf->output(), $fileName);
    }

    /**
     * Get statistics for the page
     */
    public function getAllStatistics(): array
    {
        $dari = $this->tanggal_dari ? Carbon::parse($this->tanggal_dari) : Carbon::now()->startOfMonth();
        $sampai = $this->tanggal_sampai ? Carbon::parse($this->tanggal_sampai)->endOfDay() : Carbon::now()->endOfDay();

        $base = Delivery::whereBetween('tanggal', [$dari, $sampai]);

        return [
            'total_semua' => Delivery::count(),
            'total_periode' => $base->count(),
            'hari_ini' => Delivery::whereDate('tanggal', Carbon::today())->count(),
            'bulan_ini' => Delivery::whereMonth('tanggal', now()->month)->count(),
            'dengan_keluhan' => $base->whereNotNull('keluhan')->where('keluhan', '!=', '')->count(),
            'dengan_tindakan' => $base->whereNotNull('tindakan')->where('tindakan', '!=', '')->count(),
            'dengan_alamat' => $base->whereNotNull('alamat')->where('alamat', '!=', '')->count(),
            'dengan_pekerjaan' => $base->where(function ($q) {
                $q->whereNotNull('pekerjaan_istri')->where('pekerjaan_istri', '!=', '')
                  ->orWhereNotNull('pekerjaan_suami')->where('pekerjaan_suami', '!=', '');
            })->count(),
        ];
    }
};

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
                <i class="bi bi-file-earmark-bar-graph-fill text-emerald-600 mr-2"></i>Laporan Data Persalinan
            </h1>
            <p class="mt-1 text-sm text-gray-500">Lihat dan cetak laporan rekap data persalinan</p>
        </div>
        <button type="button" wire:click="downloadPdf"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-elevation-sm hover:shadow-elevation-md transition-all">
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
            <p class="text-xl font-bold text-emerald-600 mt-0.5">{{ number_format($stats['total_periode']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Hari Ini</p>
            <p class="text-xl font-bold text-blue-600 mt-0.5">{{ number_format($stats['hari_ini']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Bulan Ini</p>
            <p class="text-xl font-bold text-indigo-600 mt-0.5">{{ number_format($stats['bulan_ini']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Ada Keluhan</p>
            <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($stats['dengan_keluhan']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Ada Tindakan</p>
            <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($stats['dengan_tindakan']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Ada Alamat</p>
            <p class="text-2xl font-bold text-rose-500 mt-0.5">{{ number_format($stats['dengan_alamat']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Ada Pekerjaan</p>
            <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($stats['dengan_pekerjaan']) }}</p>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white rounded-2xl shadow-elevation-sm border border-gray-100 p-5 sm:p-6 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">

            <!-- Cari -->
            <div class="relative flex-1">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                <input type="text" wire:model.live.debounce="search"
                    placeholder="Cari nama ibu, nama ayah, alamat, atau petugas..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all placeholder:text-gray-400" />
            </div>

            <!-- Tanggal Dari -->
            <div>
                <label class="sr-only" for="tanggal_dari">Dari Tanggal</label>
                <input type="date" id="tanggal_dari" wire:model="tanggal_dari"
                    class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
            </div>

            <!-- Tanggal Sampai -->
            <div>
                <label class="sr-only" for="tanggal_sampai">Sampai Tanggal</label>
                <input type="date" id="tanggal_sampai" wire:model="tanggal_sampai"
                    class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
            </div>

            <!-- Per Halaman -->
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

    <!-- Data Table Card -->
    <div class="bg-white rounded-2xl shadow-elevation-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px]">
                <thead>
                    <tr class="text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50/80">
                        <th class="text-center px-6 py-3.5 w-12">No</th>
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('tanggal')">
                            <div class="flex items-center gap-1.5">
                                <span>Tgl Pemeriksaan</span>
                                @if ($sortColumn === 'tanggal')
                                    <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up-fill' : 'down-fill' }} text-emerald-600"></i>
                                @else
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5">Nama Ibu</th>
                        <th class="text-left px-6 py-3.5">Nama Ayah</th>
                        <th class="text-center px-6 py-3.5">Umur Ibu</th>
                        <th class="text-center px-6 py-3.5">Umur Ayah</th>
                        <th class="text-left px-6 py-3.5">No. Telepon</th>
                        <th class="text-left px-6 py-3.5">Alamat</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse ($records as $i => $r)
                        <tr class="table-row-hover hover:bg-gray-50/60 transition-colors">
                            <td class="px-6 py-4 text-center text-sm text-gray-500">{{ $records->firstItem() + $i }}</td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ $r->tanggal->format('d/m/Y') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <p class="text-sm font-semibold text-gray-900 leading-tight">{{ $r->nama_istri }}</p>
                                    @if ($r->pekerjaan_istri)
                                        <p class="text-xs text-gray-400">{{ $r->pekerjaan_istri }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ $r->nama_suami ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-sm text-gray-600">
                                    {{ $r->umur_istri ? $r->umur_istri . ' tahun' : '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-sm text-gray-600">
                                    {{ $r->umur_suami ? $r->umur_suami . ' tahun' : '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ $r->no_telpon ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ $r->alamat ?? '-' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="p-4 rounded-2xl bg-gray-50">
                                        <i class="bi bi-three-dots-vertical text-4xl text-gray-300"></i>
                                    </div>
                                    <p class="text-gray-500 font-medium">Tidak ada data persalinan</p>
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

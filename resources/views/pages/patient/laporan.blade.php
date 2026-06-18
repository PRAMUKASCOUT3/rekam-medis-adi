<?php

use Livewire\Component;
use App\Models\Pasien;
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
    public string $sortColumn = 'created_at';
    public string $sortDirection = 'desc';

    // Calculated
    public int $total_pasien = 0;

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
        $query = Pasien::query();

        // Apply search filter
        if ($this->search) {
            $query
                ->where('nama', 'like', '%' . $this->search . '%')
                ->orWhere('nik', 'like', '%' . $this->search . '%')
                ->orWhere('no_telpon', 'like', '%' . $this->search . '%');
        }

        // Apply date filter based on patient creation date (registered date)
        if ($this->tanggal_dari) {
            $query->whereDate('created_at', '>=', $this->tanggal_dari);
        }
        if ($this->tanggal_sampai) {
            $query->whereDate('created_at', '<=', $this->tanggal_sampai);
        }

        $pasiens = $query->orderBy($this->sortColumn, $this->sortDirection)->paginate($this->perPage);

        $this->total_pasien = Pasien::count();

        return view('pages.patient.laporan', [
            'pasiens' => $pasiens,
            'total_pasien' => $this->total_pasien,
            'perPage' => $this->perPage,
            'sortColumn' => 'created_at',
            'sortDirection' => 'desc',
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
        $query = Pasien::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nama', 'like', '%' . $this->search . '%')
                    ->orWhere('nik', 'like', '%' . $this->search . '%')
                    ->orWhere('no_telpon', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->tanggal_dari) {
            $query->whereDate('created_at', '>=', $this->tanggal_dari);
        }

        if ($this->tanggal_sampai) {
            $query->whereDate('created_at', '<=', $this->tanggal_sampai);
        }

        $pasiens = $query->orderBy('nama')->get();

        // FIX UTF-8 ERROR
        $pasiens = $pasiens->map(function ($item) {
            foreach ($item->getAttributes() as $key => $value) {
                if (is_string($value)) {
                    $item->$key = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                }
            }

            return $item;
        });

        $total = $pasiens->count();
        $now = Carbon::now()->format('d/m/Y H:i');

        $html = view('pdf.patients_report', [
            'pasiens' => $pasiens,
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

        $fileName = 'laporan-pasien-' . Carbon::now()->format('Y-m-d_Hi') . '.pdf';

        return response()->streamDownload(fn() => print $pdf->output(), $fileName);
    }

    /**
     * Get statistics for the page
     */
    public function getAllStatistics(): array
    {
        $dari = $this->tanggal_dari ? Carbon::parse($this->tanggal_dari) : Carbon::now()->startOfMonth();
        $sampai = $this->tanggal_sampai ? Carbon::parse($this->tanggal_sampai)->endOfDay() : Carbon::now()->endOfDay();

        return [
            'total_semua' => Pasien::count(),
            'total_dari_sampai' => Pasien::whereBetween('created_at', [$dari, $sampai])->count(),
            'jumlah_laki' => Pasien::whereBetween('created_at', [$dari, $sampai])
                ->where('jenis_kelamin', 'L')
                ->count(),
            'jumlah_perempuan' => Pasien::whereBetween('created_at', [$dari, $sampai])
                ->where('jenis_kelamin', 'P')
                ->count(),
            'dewasa' => Pasien::whereBetween('created_at', [$dari, $sampai])
                ->where('jenis_pasien', 'dewasa')
                ->count(),
            'anak_anak' => Pasien::whereBetween('created_at', [$dari, $sampai])
                ->where('jenis_pasien', 'anak-anak')
                ->count(),
            'ibu_hamil' => Pasien::whereBetween('created_at', [$dari, $sampai])
                ->where('jenis_pasien', 'ibu hamil')
                ->count(),
            'bayi' => Pasien::whereBetween('created_at', [$dari, $sampai])
                ->where('jenis_pasien', 'bayi')
                ->count(),
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
                <i class="bi bi-file-earmark-bar-graph-fill text-indigo-600 mr-2"></i>Laporan Data Pasien
            </h1>
            <p class="mt-1 text-sm text-gray-500">Lihat dan cetak laporan rekap data pasien terdaftar</p>
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
            <p class="text-xl font-bold text-indigo-600 mt-0.5">{{ number_format($stats['total_dari_sampai']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Laki-laki</p>
            <p class="text-xl font-bold text-blue-600 mt-0.5">{{ number_format($stats['jumlah_laki']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Perempuan</p>
            <p class="text-xl font-bold text-pink-600 mt-0.5">{{ number_format($stats['jumlah_perempuan']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Dewasa</p>
            <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($stats['dewasa']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Anak-anak</p>
            <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($stats['anak_anak']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Bayi</p>
            <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($stats['bayi']) }}</p>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white rounded-2xl shadow-elevation-sm border border-gray-100 p-5 sm:p-6 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">

            <!-- Cari -->
            <div class="relative flex-1">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                <input type="text" wire:model.live.debounce="search"
                    placeholder="Cari nama pasien, NIK, atau No. Telpon..."
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
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('nama')">
                            <div class="flex items-center gap-1.5">
                                <span>Nama Pasien</span>
                                @if ($sortColumn === 'nama')
                                    <i
                                        class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up-fill' : 'down-fill' }} text-indigo-600"></i>
                                @else
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="text-center px-6 py-3.5">JK</th>
                        <th class="text-left px-6 py-3.5">Jenis Pasien</th>
                        <th class="text-left px-6 py-3.5">Tanggal Lahir</th>
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('created_at')">
                            <div class="flex items-center gap-1.5">
                                <span>Tgl Daftar</span>
                                @if ($sortColumn === 'created_at')
                                    <i
                                        class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up-fill' : 'down-fill' }} text-indigo-600"></i>
                                @else
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5">No. Telepon</th>
                        <th class="text-center px-6 py-3.5">Gol. Darah</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse ($pasiens as $i => $p)
                        <tr class="table-row-hover hover:bg-gray-50/60 transition-colors">
                            <td class="px-6 py-4 text-center text-sm text-gray-500">{{ $pasiens->firstItem() + $i }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <p class="text-sm font-semibold text-gray-900 leading-tight">{{ $p->nama }}
                                    </p>
                                    <p class="text-xs text-gray-400">{{ $p->nik ?? 'Tanpa NIK' }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if ($p->jenis_kelamin === 'L')
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-xs font-semibold">Laki-laki</span>
                                @else
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-md bg-pink-50 text-pink-700 text-xs font-semibold">Perempuan</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-md bg-gray-50 text-gray-700 text-xs font-semibold">
                                    {{ ucfirst(str_replace('-', ' ', $p->jenis_pasien ?? 'N/A')) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-600">
                                        {{ $p->tanggal_lahir ? $p->tanggal_lahir->format('d/m/Y') : '-' }}
                                    </span>
                                    <span class="text-xs text-gray-400">
                                        @php
                                            echo $p->tanggal_lahir
                                                ? \Carbon\Carbon::parse($p->tanggal_lahir)->age . ' tahun'
                                                : '-';
                                        @endphp
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ $p->created_at->format('d/m/Y') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ $p->no_telpon ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if ($p->golongan_darah)
                                    <span
                                        class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-red-50 text-red-600 text-xs font-bold">
                                        {{ $p->golongan_darah }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="p-4 rounded-2xl bg-gray-50">
                                        <i class="bi bi-people text-4xl text-gray-300"></i>
                                    </div>
                                    <p class="text-gray-500 font-medium">Tidak ada data pasien</p>
                                    <p class="text-sm text-gray-400">Coba ubah filter tanggal atau kata kunci
                                        pencarian.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($pasiens->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/50">
                <p class="text-sm text-gray-500">
                    Menampilkan
                    <span class="font-semibold text-gray-700">{{ $pasiens->firstItem() ?? 0 }}</span>
                    –
                    <span class="font-semibold text-gray-700">{{ $pasiens->lastItem() }}</span>
                    dari
                    <span class="font-semibold text-gray-700">{{ $pasiens->total() }}</span>
                    pasien
                </p>
                <div class="flex gap-1">
                    {{ $pasiens->links() }}
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

<?php

use Livewire\Component;
use App\Models\Pregnancy;
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
    public int $total_kehamilan = 0;

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
        $query = Pregnancy::query()->with('user:id,name');

        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nama_istri', 'like', '%' . $this->search . '%')
                    ->orWhere('nama_suami', 'like', '%' . $this->search . '%')
                    ->orWhere('alamat', 'like', '%' . $this->search . '%')
                    ->orWhereHas('user', function ($sq) {
                        $sq->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        // Apply date filter on tanggal pemeriksaan
        if ($this->tanggal_dari) {
            $query->whereDate('tanggal', '>=', $this->tanggal_dari);
        }
        if ($this->tanggal_sampai) {
            $query->whereDate('tanggal', '<=', $this->tanggal_sampai);
        }

        $records = $query->orderBy($this->sortColumn, $this->sortDirection)->paginate($this->perPage);

        $this->total_kehamilan = Pregnancy::count();

        return view('pages.pregnancy.laporan', [
            'records' => $records,
            'total_kehamilan' => $this->total_kehamilan,
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
     * Gestational age in weeks from HPHT
     */
    public function calcWeeks(?string $hphtValue): ?int
    {
        if (! $hphtValue) {
            return null;
        }
        try {
            return Carbon::parse($hphtValue)->diffInWeeks(now());
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generate and download PDF report
     */
    public function downloadPdf()
    {
        $query = Pregnancy::query()->with('user:id,name');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nama_istri', 'like', '%' . $this->search . '%')
                    ->orWhere('nama_suami', 'like', '%' . $this->search . '%')
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

        $html = view('pdf.pregnancy_report', [
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

        $fileName = 'laporan-kehamilan-' . Carbon::now()->format('Y-m-d_Hi') . '.pdf';

        return response()->streamDownload(fn() => print $pdf->output(), $fileName);
    }

    /**
     * Get statistics for the page
     */
    public function getAllStatistics(): array
    {
        $dari = $this->tanggal_dari ? Carbon::parse($this->tanggal_dari) : Carbon::now()->startOfMonth();
        $sampai = $this->tanggal_sampai ? Carbon::parse($this->tanggal_sampai)->endOfDay() : Carbon::now()->endOfDay();

        $base = Pregnancy::whereBetween('tanggal', [$dari, $sampai]);

        return [
            'total_semua' => Pregnancy::count(),
            'total_periode' => $base->count(),
            'hari_ini' => Pregnancy::whereDate('tanggal', Carbon::today())->count(),
            'bulan_ini' => Pregnancy::whereMonth('tanggal', now()->month)->count(),
            'trim_1' => $base->whereNull('hpht')->orWhere(function ($q) use ($sampai) {
                $q->whereNotNull('hpht')
                    ->where(function ($sq) use ($sampai) {
                        // G1-P0-A0~ (rough trimester 1)
                        for ($w = 0; $w <= 12; $w++) {
                            $date = (clone $sampai)->subWeeks($w)->format('Y-m-d');
                            $sq->orWhereDate('hpht', $date);
                        }
                    });
            })->count(),
            'trim_3' => $base->whereNotNull('hpht')->where(function ($q) use ($sampai) {
                // >= 28 weeks meaning HPHT >= today - 27 weeks
                $cutoff = (clone $sampai)->subWeeks(27)->format('Y-m-d');
                $q->whereDate('hpht', '>=', $cutoff);
            })->count(),
            'dengan_alamat' => $base->whereNotNull('alamat')->where('alamat', '!=', '')->count(),
        ];
    }
};

?>
<div>
    <div wire:poll.30000>
            <?php
        $__scriptKey = '2396666873-0';
        ob_start();
    ?>
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
            <?php
        $__output = ob_get_clean();

        \Livewire\store($this)->push('scripts', $__output, $__scriptKey)
    ?>
    </div>

    <!-- Page Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                <i class="bi bi-file-earmark-bar-graph-fill text-rose-600 mr-2"></i>Laporan Data Kehamilan
            </h1>
            <p class="mt-1 text-sm text-gray-500">Lihat dan cetak laporan rekap data kehamilan</p>
        </div>
        <button type="button" wire:click="downloadPdf"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold shadow-elevation-sm hover:shadow-elevation-md transition-all">
            <i class="bi bi-filetype-pdf"></i>
            Unduh Laporan PDF
        </button>
    </div>

    <!-- Stat Cards (from filter period) -->
    <?php
        $stats = $this->getAllStatistics();
    ?>
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-4 mb-6">
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Total</p>
            <p class="text-xl font-bold text-gray-900 mt-0.5"><?php echo e(number_format($stats['total_semua'])); ?></p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Periode</p>
            <p class="text-xl font-bold text-rose-600 mt-0.5"><?php echo e(number_format($stats['total_periode'])); ?></p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Hari Ini</p>
            <p class="text-xl font-bold text-blue-600 mt-0.5"><?php echo e(number_format($stats['hari_ini'])); ?></p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Bulan Ini</p>
            <p class="text-xl font-bold text-indigo-600 mt-0.5"><?php echo e(number_format($stats['bulan_ini'])); ?></p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Trimester 1</p>
            <p class="text-2xl font-bold text-gray-900 mt-0.5"><?php echo e(number_format($stats['trim_1'])); ?></p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Trimester 2</p>
            <p class="text-2xl font-bold text-gray-900 mt-0.5">-</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Trimester 3</p>
            <p class="text-2xl font-bold text-rose-500 mt-0.5"><?php echo e(number_format($stats['trim_3'])); ?></p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Ada Alamat</p>
            <p class="text-2xl font-bold text-gray-900 mt-0.5"><?php echo e(number_format($stats['dengan_alamat'])); ?></p>
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
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all placeholder:text-gray-400" />
            </div>

            <!-- Tanggal Dari -->
            <div>
                <label class="sr-only" for="tanggal_dari">Dari Tanggal</label>
                <input type="date" id="tanggal_dari" wire:model="tanggal_dari"
                    class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all" />
            </div>

            <!-- Tanggal Sampai -->
            <div>
                <label class="sr-only" for="tanggal_sampai">Sampai Tanggal</label>
                <input type="date" id="tanggal_sampai" wire:model="tanggal_sampai"
                    class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all" />
            </div>

            <!-- Per Halaman -->
            <div class="flex items-center gap-2 shrink-0">
                <label class="text-sm text-gray-500 whitespace-nowrap">Per halaman:</label>
                <select wire:model="perPage"
                    class="px-3 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-700 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all cursor-pointer">
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
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortColumn === 'tanggal'): ?>
                                    <i class="bi bi-caret-<?php echo e($sortDirection === 'asc' ? 'up-fill' : 'down-fill'); ?> text-rose-600"></i>
                                <?php else: ?>
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5">Nama Ibu</th>
                        <th class="text-left px-6 py-3.5">Nama Ayah</th>
                        <th class="text-center px-6 py-3.5">G / P / A</th>
                        <th class="text-center px-6 py-3.5">Usia Kandungan</th>
                        <th class="text-left px-6 py-3.5">HPHT / TP</th>
                        <th class="text-left px-6 py-3.5">Petugas</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $records; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                        <tr class="table-row-hover hover:bg-gray-50/60 transition-colors">
                            <td class="px-6 py-4 text-center text-sm text-gray-500"><?php echo e($records->firstItem() + $i); ?></td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600"><?php echo e($r->tanggal->format('d/m/Y')); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <p class="text-sm font-semibold text-gray-900 leading-tight"><?php echo e($r->nama_istri); ?></p>
                                    <p class="text-xs text-gray-400">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($r->umur_istri): ?>
                                            <?php echo e($r->umur_istri); ?> tahun
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($r->alamat): ?> &middot; <?php echo e(Str::limit($r->alamat, 30)); ?> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        <?php elseif($r->alamat): ?>
                                            <?php echo e(Str::limit($r->alamat, 40)); ?>

                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600"><?php echo e($r->nama_suami ?? '-'); ?></span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-1.5 text-xs">
                                    <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 font-bold">G<?php echo e($r->gravida); ?></span>
                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 font-bold">P<?php echo e($r->partus); ?></span>
                                    <span class="px-2.5 py-1 rounded-lg bg-rose-50 text-rose-700 font-bold">A<?php echo e($r->abortus); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php
                                    $weeks = $this->calcWeeks($r->hpht);
                                ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($weeks !== null): ?>
                                    <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg <?php echo e($weeks >= 28 ? 'bg-purple-50 text-purple-700' : 'bg-indigo-50 text-indigo-700'); ?> text-sm font-semibold">
                                        <i class="bi bi-clock-history text-xs"></i>
                                        <?php echo e($weeks); ?> minggu
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">-</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col text-xs">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-gray-400 font-medium">HPHT:</span>
                                        <span class="text-gray-700"><?php echo e($r->hpht ? $r->hpht->format('d/m/Y') : '-'); ?></span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-gray-400 font-medium">TP:</span>
                                        <span class="font-semibold text-gray-700"><?php echo e($r->tp ? $r->tp->format('d/m/Y') : '-'); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600"><?php echo e($r->user?->name ?? 'N/A'); ?></span>
                            </td>
                        </tr>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="p-4 rounded-2xl bg-gray-50">
                                        <i class="bi bi-heart-pulse text-4xl text-gray-300"></i>
                                    </div>
                                    <p class="text-gray-500 font-medium">Tidak ada data kehamilan</p>
                                    <p class="text-sm text-gray-400">Coba ubah filter tanggal atau kata kunci pencarian.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($records->hasPages()): ?>
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/50">
                <p class="text-sm text-gray-500">
                    Menampilkan
                    <span class="font-semibold text-gray-700"><?php echo e($records->firstItem() ?? 0); ?></span>
                    –
                    <span class="font-semibold text-gray-700"><?php echo e($records->lastItem()); ?></span>
                    dari
                    <span class="font-semibold text-gray-700"><?php echo e($records->total()); ?></span>
                    data
                </p>
                <div class="flex gap-1">
                    <?php echo e($records->links()); ?>

                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

</div>

    <?php
        $__scriptKey = '2396666873-1';
        ob_start();
    ?>
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
    <?php
        $__output = ob_get_clean();

        \Livewire\store($this)->push('scripts', $__output, $__scriptKey)
    ?>
<?php /**PATH D:\laragon\www\rekam-medis\resources\views/pages/pregnancy/laporan.blade.php ENDPATH**/ ?>
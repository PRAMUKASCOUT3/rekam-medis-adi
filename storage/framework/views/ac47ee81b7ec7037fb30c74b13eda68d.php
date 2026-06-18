<?php

use Livewire\Component;
use App\Models\Obat;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    // Filter
    public ?string $tanggal_dari = null;
    public ?string $tanggal_sampai = null;
    public ?int $stok_min = null;
    public ?int $stok_max = null;
    public string $search = '';
    public string $type_filter = '';
    public int $perPage = 10;

    // Sorting
    public string $sortColumn = 'created_at';
    public string $sortDirection = 'desc';

    // Stats
    public int $total_obat = 0;
    public int $total_stok = 0;
    public int $stok_habis = 0;
    public int $stok_menipis = 0;
    public int $stok_aman = 0;

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
        $query = Obat::query();

        if ($this->search) {
            $query->where('nama', 'like', '%' . $this->search . '%')->orWhere('kode', 'like', '%' . $this->search . '%');
        }

        if ($this->type_filter) {
            $query->where('type', $this->type_filter);
        }

        if ($this->stok_min !== null && $this->stok_min !== '') {
            $query->where('stok', '>=', (int) $this->stok_min);
        }
        if ($this->stok_max !== null && $this->stok_max !== '') {
            $query->where('stok', '<=', (int) $this->stok_max);
        }

        if ($this->tanggal_dari) {
            $query->whereDate('created_at', '>=', $this->tanggal_dari);
        }
        if ($this->tanggal_sampai) {
            $query->whereDate('created_at', '<=', $this->tanggal_sampai);
        }

        $obats = $query->orderBy($this->sortColumn, $this->sortDirection)->paginate($this->perPage);

        $this->recalculateStats();

        return view('pages.drugs.laporan', [
            'obats' => $obats,
            'total_obat' => $this->total_obat,
            'total_stok' => $this->total_stok,
            'stok_habis' => $this->stok_habis,
            'stok_menipis' => $this->stok_menipis,
            'stok_aman' => $this->stok_aman,
            'perPage' => $this->perPage,
            'type_filter' => $this->type_filter,
            'sortColumn' => $this->sortColumn,
            'sortDirection' => $this->sortDirection,
        ]);
    }

    private function recalculateStats(): void
    {
        $q = Obat::query();
        if ($this->tanggal_dari) {
            $q->whereDate('created_at', '>=', $this->tanggal_dari);
        }
        if ($this->tanggal_sampai) {
            $q->whereDate('created_at', '<=', $this->tanggal_sampai);
        }
        $all = $q->get(['stok']);
        $this->total_obat = $all->count();
        $this->total_stok = $all->sum('stok');
        $this->stok_habis = $all->where('stok', 0)->count();
        $this->stok_menipis = $all->where('stok', '>', 0)->where('stok', '<', 10)->count();
        $this->stok_aman = $all->where('stok', '>=', 10)->count();
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
    public function updatedTypeFilter(): void
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
    public function updatedStokMin(): void
    {
        $this->resetPage();
    }
    public function updatedStokMax(): void
    {
        $this->resetPage();
    }
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'type_filter', 'stok_min', 'stok_max']);
        $this->tanggal_dari = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->tanggal_sampai = Carbon::now()->format('Y-m-d');
    }

    public function downloadPdf()
    {
        $query = Obat::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nama', 'like', '%' . $this->search . '%')->orWhere('kode', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->type_filter) {
            $query->where('type', $this->type_filter);
        }

        if ($this->stok_min !== null && $this->stok_min !== '') {
            $query->where('stok', '>=', (int) $this->stok_min);
        }

        if ($this->stok_max !== null && $this->stok_max !== '') {
            $query->where('stok', '<=', (int) $this->stok_max);
        }

        if ($this->tanggal_dari) {
            $query->whereDate('created_at', '>=', $this->tanggal_dari);
        }

        if ($this->tanggal_sampai) {
            $query->whereDate('created_at', '<=', $this->tanggal_sampai);
        }

        $obats = $query->orderBy('nama')->get();

        // Fix UTF-8
        $obatData = $obats
            ->map(function ($o) {
                return [
                    'nama' => mb_convert_encoding((string) $o->nama, 'UTF-8', 'UTF-8'),
                    'kode' => mb_convert_encoding((string) $o->kode, 'UTF-8', 'UTF-8'),
                    'type' => mb_convert_encoding((string) $o->type, 'UTF-8', 'UTF-8'),
                    'satuan' => mb_convert_encoding((string) $o->satuan, 'UTF-8', 'UTF-8'),
                    'stok' => (int) $o->stok,
                    'created_at' => optional($o->created_at)->format('d/m/Y H:i') ?? '-',
                ];
            })
            ->all();

        $total = count($obatData);
        $now = Carbon::now()->format('d/m/Y H:i');

        $html = view('pdf.drugs_report', [
            'obats' => $obatData,
            'total' => $total,
            'total_stok' => collect($obatData)->sum('stok'),
            'jenis' => $this->type_filter ? strtoupper($this->type_filter) : null,
            'stok_min' => $this->stok_min,
            'stok_max' => $this->stok_max,
            'tanggal_dari' => $this->tanggal_dari,
            'tanggal_sampai' => $this->tanggal_sampai,
            'printed_at' => $now,
            'stok_habis' => collect($obatData)->where('stok', 0)->count(),
            'stok_menipis' => collect($obatData)->where('stok', '>', 0)->where('stok', '<', 10)->count(),
            'stok_aman' => collect($obatData)->where('stok', '>=', 10)->count(),
        ])->render();

        // Fix malformed UTF-8
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('A4', 'landscape')
            ->setOptions([
                'isRemoteEnabled' => true,
                'defaultFont' => 'dejavusans',
                'isHtml5ParserEnabled' => true,
            ]);

        $fileName = 'laporan-obat-' . Carbon::now()->format('Y-m-d_Hi') . '.pdf';

        return response()->streamDownload(fn() => print $pdf->output(), $fileName);
    }

    public function getAllStatistics(): array
    {
        $q = Obat::query();
        if ($this->tanggal_dari) {
            $q->whereDate('created_at', '>=', $this->tanggal_dari);
        }
        if ($this->tanggal_sampai) {
            $q->whereDate('created_at', '<=', $this->tanggal_sampai);
        }
        $all = $q->get(['stok', 'type']);
        return [
            'total' => $all->count(),
            'total_stok' => $all->sum('stok'),
            'stok_habis' => $all->where('stok', 0)->count(),
            'stok_menipis' => $all->where('stok', '>', 0)->where('stok', '<', 10)->count(),
            'stok_aman' => $all->where('stok', '>=', 10)->count(),
        ];
    }
};
?>
<div>
    <!-- Page Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                <i class="bi bi-file-earmark-bar-graph-fill text-emerald-600 mr-2"></i>Laporan Data Obat
            </h1>
            <p class="mt-1 text-sm text-gray-500">Lihat dan cetak laporan rekap stok &amp; data obat</p>
        </div>
        <button type="button" wire:click="downloadPdf"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-elevation-sm hover:shadow-elevation-md transition-all">
            <i class="bi bi-filetype-pdf"></i>
            Unduh Laporan PDF
        </button>
    </div>

    <!-- Stat Cards -->
    <?php
        $stats = $this->getAllStatistics();
    ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Total Jenis Obat</p>
            <p class="text-2xl font-bold text-gray-900 mt-0.5"><?php echo e(number_format($stats['total'])); ?></p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Total Stok</p>
            <p class="text-2xl font-bold text-gray-900 mt-0.5"><?php echo e(number_format($stats['total_stok'])); ?></p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Stok Aman (&ge;10)</p>
            <p class="text-2xl font-bold text-emerald-600 mt-0.5"><?php echo e(number_format($stats['stok_aman'])); ?></p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Stok Menipis (&lt;10)</p>
            <p class="text-2xl font-bold text-amber-600 mt-0.5"><?php echo e(number_format($stats['stok_menipis'])); ?></p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-elevation-sm border border-gray-100">
            <p class="text-xs text-gray-500 font-medium">Stok Habis (0)</p>
            <p class="text-2xl font-bold text-red-600 mt-0.5"><?php echo e(number_format($stats['stok_habis'])); ?></p>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white rounded-2xl shadow-elevation-sm border border-gray-100 p-5 sm:p-6 mb-6">
        <div class="flex flex-col sm:flex-row gap-3 items-end">

            <!-- Cari -->
            <div class="relative flex-1">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                <input type="text" wire:model.live.debounce="search" placeholder="Cari nama obat atau kode..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all placeholder:text-gray-400" />
            </div>

            <!-- Jenis Obat -->
            <div class="sm:w-44">
                <label class="sr-only" for="type_filter">Jenis Obat</label>
                <select id="type_filter" wire:model.live="type_filter"
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all cursor-pointer">
                    <option value="">Semua Jenis</option>
                    <option value="tablet">Tablet</option>
                    <option value="kapsul">Kapsul</option>
                    <option value="sirup">Sirup</option>
                    <option value="salep">Salep</option>
                    <option value="vitamin">Vitamin</option>
                    <option value="injeksi">Injeksi</option>
                    <option value="tetes">Tetes</option>
                </select>
            </div>

            <!-- Stok Min -->
            <div class="sm:w-32">
                <label class="sr-only" for="stok_min">Stok Min</label>
                <input type="number" id="stok_min" wire:model.live="stok_min" placeholder="Min" min="0"
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
            </div>

            <!-- Stok Max -->
            <div class="sm:w-32">
                <label class="sr-only" for="stok_max">Stok Max</label>
                <input type="number" id="stok_max" wire:model.live="stok_max" placeholder="Max" min="0"
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
            </div>

            <!-- Tanggal Dari -->
            <div class="sm:w-40">
                <label class="sr-only" for="tanggal_dari">Dari Tanggal</label>
                <input type="date" id="tanggal_dari" wire:model.live="tanggal_dari"
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
            </div>

            <!-- Tanggal Sampai -->
            <div class="sm:w-40">
                <label class="sr-only" for="tanggal_sampai">Sampai Tanggal</label>
                <input type="date" id="tanggal_sampai" wire:model.live="tanggal_sampai"
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
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
            <table class="w-full min-w-[800px]">
                <thead>
                    <tr class="text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50/80">
                        <th class="text-center px-6 py-3.5 w-12">No</th>
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('kode')">
                            <div class="flex items-center gap-1.5">
                                <span>Kode Obat</span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortColumn === 'kode'): ?>
                                    <i
                                        class="bi bi-caret-<?php echo e($sortDirection === 'asc' ? 'up-fill' : 'down-fill'); ?> text-emerald-600"></i>
                                <?php else: ?>
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('nama')">
                            <div class="flex items-center gap-1.5">
                                <span>Nama Obat</span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortColumn === 'nama'): ?>
                                    <i
                                        class="bi bi-caret-<?php echo e($sortDirection === 'asc' ? 'up-fill' : 'down-fill'); ?> text-emerald-600"></i>
                                <?php else: ?>
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5">Jenis</th>
                        <th class="text-center px-6 py-3.5">Satuan</th>
                        <th class="text-center px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('stok')">
                            <div class="flex items-center justify-center gap-1.5">
                                <span>Stok</span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortColumn === 'stok'): ?>
                                    <i
                                        class="bi bi-caret-<?php echo e($sortDirection === 'asc' ? 'up-fill' : 'down-fill'); ?> text-emerald-600"></i>
                                <?php else: ?>
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none"
                            wire:click="sortBy('created_at')">
                            <div class="flex items-center gap-1.5">
                                <span>Tgl Dibuat</span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortColumn === 'created_at'): ?>
                                    <i
                                        class="bi bi-caret-<?php echo e($sortDirection === 'asc' ? 'up-fill' : 'down-fill'); ?> text-emerald-600"></i>
                                <?php else: ?>
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $obats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                        <?php
                            $s = (int) $o->stok;
                            $stockClass =
                                $s === 0
                                    ? 'text-red-600 bg-red-50'
                                    : ($s < 10
                                        ? 'text-amber-600 bg-amber-50'
                                        : 'text-emerald-600 bg-emerald-50');
                            $stockLabel = $s === 0 ? 'HABIS' : ($s < 10 ? 'MENIPIS' : 'AMAN');
                        ?>
                        <tr class="table-row-hover hover:bg-gray-50/60 transition-colors">
                            <td class="px-6 py-4 text-center text-sm text-gray-500"><?php echo e($obats->firstItem() + $i); ?></td>
                            <td class="px-6 py-4">
                                <span
                                    class="text-xs font-mono font-semibold text-gray-600 bg-gray-100 px-2 py-0.5 rounded"><?php echo e($o->kode); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-gray-900 leading-tight"><?php echo e($o->nama); ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-md bg-gray-50 text-gray-700 text-xs font-semibold">
                                    <?php echo e(strtoupper($o->type)); ?>

                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-sm text-gray-600"><?php echo e($o->satuan); ?></span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-md <?php echo e($stockClass); ?> text-xs font-bold">
                                    <?php echo e(number_format($s)); ?>

                                </span>
                                <p class="text-xs text-gray-400 mt-0.5"><?php echo e($stockLabel); ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600"><?php echo e($o->created_at); ?></span>
                            </td>
                        </tr>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="p-4 rounded-2xl bg-gray-50">
                                        <i class="bi bi-capsule text-4xl text-gray-300"></i>
                                    </div>
                                    <p class="text-gray-500 font-medium">Tidak ada data obat</p>
                                    <p class="text-sm text-gray-400">Coba ubah filter atau kata kunci pencarian.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($obats->hasPages()): ?>
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/50">
                <p class="text-sm text-gray-500">
                    Menampilkan
                    <span class="font-semibold text-gray-700"><?php echo e($obats->firstItem() ?? 0); ?></span>
                    –
                    <span class="font-semibold text-gray-700"><?php echo e($obats->lastItem()); ?></span>
                    dari
                    <span class="font-semibold text-gray-700"><?php echo e($obats->total()); ?></span>
                    obat
                </p>
                <div class="flex gap-1">
                    <?php echo e($obats->links()); ?>

                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

</div>

    <?php
        $__scriptKey = '2093304658-0';
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
<?php /**PATH D:\laragon\www\rekam-medis\resources\views/pages/drugs/laporan.blade.php ENDPATH**/ ?>
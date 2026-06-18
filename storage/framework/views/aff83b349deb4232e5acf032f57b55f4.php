<?php

use Livewire\Component;
use App\Models\Obat;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    // Form fields
    public string $kode = '';
    public string $nama = '';
    public string $type = 'tablet';
    public string $satuan = 'pcs';
    public int $stok = 0;

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
            'kode' => ['required', 'string', 'max:255', $this->editMode ? 'unique:obats,kode,' . $this->editingId : 'unique:obats,kode'],
            'nama' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:tablet,kapsul,sirup,salep,vitamin,injeksi,tetes'],
            'satuan' => ['required', 'string', 'max:255'],
            'stok' => ['required', 'integer', 'min:0'],
        ];
    }

    public function render()
    {
        $query = Obat::query();

        if ($this->search) {
            $query->where('nama', 'like', '%' . $this->search . '%')->orWhere('kode', 'like', '%' . $this->search . '%');
        }

        $drugs = $query->orderBy($this->sortColumn, $this->sortDirection)->paginate($this->perPage);

        return view('pages.drugs.index', [
            'obat' => $drugs,
        ]);
    }

    public function resetForm(): void
    {
        $this->reset(['kode', 'nama', 'type', 'satuan', 'stok', 'editMode', 'editingId', 'showDeleteConfirm', 'deletingId']);
        $this->type = 'tablet';
        $this->satuan = 'pcs';
        $this->stok = 0;
    }

    public function generatingCode(): string
    {
        $last = Obat::orderByDesc('id')->first();
        $number = $last ? $last->id + 1 : 1;
        return 'OB-' . str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }

    public function creating(): void
    {
        $this->resetForm();
        $this->kode = $this->generatingCode();
        $this->editMode = false;
        $this->editingId = null;
        $this->showModal = true;
    }

    public function editing(int $id): void
    {
        $obat = Obat::findOrFail($id);
        $this->kode = $obat->kode;
        $this->nama = $obat->nama;
        $this->type = $obat->type;
        $this->satuan = $obat->satuan;
        $this->stok = $obat->stok;
        $this->editMode = true;
        $this->editingId = $id;
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $data = [
            'kode' => $validated['kode'],
            'nama' => $validated['nama'],
            'type' => $validated['type'],
            'satuan' => $validated['satuan'],
            'stok' => $validated['stok'],
        ];

        if ($this->editMode && $this->editingId) {
            Obat::findOrFail($this->editingId)->update($data);
            $message = 'Data obat berhasil diperbarui.';
        } else {
            Obat::create($data);
            $message = 'Data obat berhasil ditambahkan.';
        }

        $this->dispatch('toast', type: 'success', message: $message);
        $this->resetForm();
        $this->showModal = false;
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Obat::findOrFail($this->deletingId)->delete();
            $this->dispatch('toast', type: 'warning', message: 'Data obat berhasil dihapus.');
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

    public function totalDrugs(): int
    {
        return Obat::count();
    }

    public function lowStockCount(): int
    {
        return Obat::where('stok', '<', 10)->count();
    }

    public function outOfStockCount(): int
    {
        return Obat::where('stok', 0)->count();
    }
};
?>

<div>
    <!-- Page Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                <i class="bi bi-capsule text-indigo-600 mr-2"></i>Data Obat
            </h1>
            <p class="mt-1 text-sm text-gray-500">Kelola data obat, stok &amp; jenis obat</p>
        </div>
        <button type="button" wire:click="creating"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-elevation-sm hover:shadow-elevation-md transition-all">
            <i class="bi bi-plus-lg"></i>
            Tambah Obat
        </button>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
        <!-- Total Drugs -->
        <div class="bg-white rounded-2xl p-5 shadow-elevation-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div class="p-3 rounded-xl bg-indigo-50">
                    <i class="bi bi-capsule text-2xl text-indigo-600"></i>
                </div>
                <span class="text-xs text-gray-400 font-medium">Total</span>
            </div>
            <p class="mt-4 text-sm text-gray-500 font-medium">Semua Obat</p>
            <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo e($this->totalDrugs()); ?></p>
        </div>

        <!-- Low Stock -->
        <div class="bg-white rounded-2xl p-5 shadow-elevation-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div class="p-3 rounded-xl bg-amber-50">
                    <i class="bi bi-exclamation-triangle text-2xl text-amber-600"></i>
                </div>
                <span class="text-xs text-gray-400 font-medium">Perhatian</span>
            </div>
            <p class="mt-4 text-sm text-gray-500 font-medium">Stok Menipis (&lt;10)</p>
            <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo e($this->lowStockCount()); ?></p>
        </div>

        <!-- Out of Stock -->
        <div class="bg-white rounded-2xl p-5 shadow-elevation-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div class="p-3 rounded-xl bg-red-50">
                    <i class="bi bi-x-circle text-2xl text-red-500"></i>
                </div>
                <span class="text-xs text-gray-400 font-medium">Kritis</span>
            </div>
            <p class="mt-4 text-sm text-gray-500 font-medium">Stok Habis</p>
            <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo e($this->outOfStockCount()); ?></p>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white rounded-2xl shadow-elevation-sm border border-gray-100 p-5 sm:p-6 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <!-- Search -->
            <div class="relative flex-1">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                <input type="text" wire:model.live.debounce="search" placeholder="Cari nama atau kode obat..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-gray-400" />
            </div>

            <!-- Per Page -->
            <div class="flex items-center gap-2">
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

    <!-- Drugs Table Card -->
    <div class="bg-white rounded-2xl shadow-elevation-sm border border-gray-100 overflow-hidden">
        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px]">
                <thead>
                    <tr class="text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50/80">
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('kode')">
                            <div class="flex items-center gap-1.5">
                                <span>Kode</span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortColumn === 'kode'): ?>
                                    <i
                                        class="bi bi-caret-<?php echo e($sortDirection === 'asc' ? 'up-fill' : 'down-fill'); ?> text-indigo-600"></i>
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
                                        class="bi bi-caret-<?php echo e($sortDirection === 'asc' ? 'up-fill' : 'down-fill'); ?> text-indigo-600"></i>
                                <?php else: ?>
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('type')">
                            <div class="flex items-center gap-1.5">
                                <span>Jenis</span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortColumn === 'type'): ?>
                                    <i
                                        class="bi bi-caret-<?php echo e($sortDirection === 'asc' ? 'up-fill' : 'down-fill'); ?> text-indigo-600"></i>
                                <?php else: ?>
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5">Satuan</th>
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('stok')">
                            <div class="flex items-center gap-1.5">
                                <span>Stok</span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortColumn === 'stok'): ?>
                                    <i
                                        class="bi bi-caret-<?php echo e($sortDirection === 'asc' ? 'up-fill' : 'down-fill'); ?> text-indigo-600"></i>
                                <?php else: ?>
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('created_at')">
                            <div class="flex items-center gap-1.5">
                                <span>Dibuat</span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortColumn === 'created_at'): ?>
                                    <i
                                        class="bi bi-caret-<?php echo e($sortDirection === 'asc' ? 'up-fill' : 'down-fill'); ?> text-indigo-600"></i>
                                <?php else: ?>
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </th>
                        <th class="text-center px-6 py-3.5 font-semibold">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $obat; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $drug): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                        <tr class="table-row-hover hover:bg-gray-50/60 transition-colors">
                            <td class="px-6 py-4">
                                <span
                                    class="text-sm font-mono font-semibold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg"><?php echo e($drug->kode); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-gray-900 leading-tight"><?php echo e($drug->nama); ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                    $typeLabels = [
                                        'tablet' => ['icon' => 'bi-capsule', 'color' => 'blue', 'bg' => 'blue'],
                                        'kapsul' => ['icon' => 'bi-capsule', 'color' => 'purple', 'bg' => 'purple'],
                                        'sirup' => ['icon' => 'bi-droplet', 'color' => 'cyan', 'bg' => 'cyan'],
                                        'salep' => ['icon' => 'bi-patch', 'color' => 'amber', 'bg' => 'amber'],
                                        'vitamin' => ['icon' => 'bi-flower', 'color' => 'emerald', 'bg' => 'emerald'],
                                        'injeksi' => ['icon' => 'bi-syringe', 'color' => 'red', 'bg' => 'red'],
                                        'tetes' => ['icon' => 'bi-droplet-half', 'color' => 'sky', 'bg' => 'sky'],
                                    ];
                                    $t = $typeLabels[$drug->type] ?? [
                                        'icon' => 'bi-capsule',
                                        'color' => 'blue',
                                        'bg' => 'blue',
                                    ];
                                ?>
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-<?php echo e($t['bg']); ?>-50 text-<?php echo e($t['color']); ?>-700 text-xs font-semibold">
                                    <i class="bi <?php echo e($t['icon']); ?> text-[10px]"></i><?php echo e(ucfirst($drug->type)); ?>

                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600"><?php echo e($drug->satuan); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($drug->stok == 0): ?>
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-50 text-red-700 text-xs font-semibold">
                                        <i class="bi bi-x-circle text-[10px]"></i>Habis
                                    </span>
                                <?php elseif($drug->stok < 10): ?>
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 text-amber-700 text-xs font-semibold">
                                        <i class="bi bi-exclamation-triangle text-[10px]"></i><?php echo e($drug->stok); ?>

                                    </span>
                                <?php else: ?>
                                    <span
                                        class="text-sm font-semibold text-emerald-700"><?php echo e(number_format($drug->stok)); ?></span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-600"><?php echo e($drug->created_at->format('d M Y')); ?></span>
                                    <span class="text-xs text-gray-400"><?php echo e($drug->created_at->format('H:i')); ?>

                                        WIB</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-1">
                                    <!-- Edit Button -->
                                    <button type="button" wire:click="editing(<?php echo e($drug->id); ?>)"
                                        class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 transition-colors"
                                        title="Edit">
                                        <i class="bi bi-pencil-square text-lg"></i>
                                    </button>
                                    <!-- Delete Button -->
                                    <button type="button" wire:click="confirmDelete(<?php echo e($drug->id); ?>)"
                                        class="p-2 rounded-lg text-red-500 hover:bg-red-50 transition-colors"
                                        title="Hapus">
                                        <i class="bi bi-trash3 text-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="p-4 rounded-2xl bg-gray-50">
                                        <i class="bi bi-capsule text-4xl text-gray-300"></i>
                                    </div>
                                    <p class="text-gray-500 font-medium">Tidak ada data obat ditemukan</p>
                                    <p class="text-sm text-gray-400">Coba kata kunci lain atau tambah obat baru.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($obat->hasPages()): ?>
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/50">
                <p class="text-sm text-gray-500">
                    Menampilkan
                    <span class="font-semibold text-gray-700"><?php echo e($obat->firstItem() ?? 0); ?></span>
                    –
                    <span class="font-semibold text-gray-700"><?php echo e($obat->lastItem()); ?></span>
                    dari
                    <span class="font-semibold text-gray-700"><?php echo e($obat->total()); ?></span>
                    obat
                </p>
                <div class="flex gap-1">
                    <?php echo e($obat->links()); ?>

                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <!-- Create / Edit Modal -->
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 transition-opacity duration-200
        <?php echo e($showModal ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'); ?>"
        wire:click="closeModal">

        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

        <!-- Modal Content -->
        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-elevation-xl border border-gray-100 overflow-hidden
            transform transition-all duration-200
            <?php echo e($showModal ? 'scale-100 translate-y-0' : 'scale-95 translate-y-4'); ?>"
            wire:click.stop>

            <!-- Modal Header -->
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">
                        <?php echo e($editMode ? 'Edit Data Obat' : 'Tambah Obat Baru'); ?>

                    </h2>
                    <p class="text-sm text-gray-500 mt-0.5">
                        <?php echo e($editMode ? 'Perbarui informasi data obat' : 'Isi data obat dengan lengkap'); ?>

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
                    <!-- Code -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="kode">
                            Kode Obat <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="kode" wire:model="kode" placeholder="Contoh: OB-001"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                            disabled />
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['kode'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1.5 text-xs text-red-500"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <!-- Nama -->
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="nama">
                            Nama Obat <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="nama" wire:model="nama"
                            placeholder="Contoh: Paracetamol 500mg"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" />
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['nama'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1.5 text-xs text-red-500"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <!-- Type -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Jenis Obat <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-4 gap-2">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = ['tablet', 'kapsul', 'sirup', 'salep', 'vitamin', 'injeksi', 'tetes']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                <button type="button" wire:click="$set('type', '<?php echo e($t); ?>')"
                                    class="px-2 py-2 rounded-lg border-2 text-xs font-semibold transition-all capitalize
                                        <?php echo e($type === $t
                                            ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                            : 'border-gray-200 text-gray-500 hover:border-gray-300 hover:bg-gray-50'); ?>">
                                    <?php echo e($t); ?>

                                </button>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1.5 text-xs text-red-500"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <!-- Unit -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="satuan">
                            Satuan <span class="text-red-500">*</span>
                        </label>
                        <select id="satuan" wire:model="satuan"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all cursor-pointer">
                            <option value="pcs">Pcs (Piece)</option>
                            <option value="tablet">Tablet</option>
                            <option value="strip">Strip</option>
                            <option value="botol">Botol</option>
                            <option value="tube">Tube</option>
                            <option value="amplop">Amplop</option>
                            <option value="box">Box</option>
                        </select>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['satuan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1.5 text-xs text-red-500"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <!-- Stok -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="stok">
                            Stok <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="stok" wire:model="stok" min="0" placeholder="0"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" />
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['stok'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1.5 text-xs text-red-500"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/50">
                <div class="flex items-center gap-2 text-xs text-gray-400">
                    <i class="bi bi-info-circle"></i>
                    <span>Setiap obat memiliki kode unik yang tidak dapat diganti setelah dibuat.</span>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" wire:click="closeModal"
                        class="px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-colors">
                        Batal
                    </button>
                    <button type="button" wire:click="save"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-elevation-sm transition-all">
                        <i class="bi bi-check-lg"></i>
                        <?php echo e($editMode ? 'Simpan Perubahan' : 'Tambah Obat'); ?>

                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 transition-opacity duration-200
        <?php echo e($showDeleteConfirm ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'); ?>"
        wire:click="closeDeleteModal">

        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

        <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-elevation-xl border border-gray-100 p-6
            transform transition-all duration-200
            <?php echo e($showDeleteConfirm ? 'scale-100 translate-y-0' : 'scale-95 translate-y-4'); ?>"
            wire:click.stop>

            <div class="flex flex-col items-center text-center gap-4">
                <!-- Icon -->
                <div class="w-14 h-14 rounded-2xl bg-red-50 flex items-center justify-center">
                    <i class="bi bi-exclamation-triangle-fill text-2xl text-red-500"></i>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-gray-900">Hapus Obat?</h3>
                    <p class="text-sm text-gray-500 mt-1">Tindakan ini tidak dapat dibatalkan. Data obat akan dihapus
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
        <?php
        $__scriptKey = '912572061-0';
        ob_start();
    ?>
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
        <?php
        $__output = ob_get_clean();

        \Livewire\store($this)->push('scripts', $__output, $__scriptKey)
    ?>
</div>
<?php /**PATH D:\laragon\www\rekam-medis\resources\views/pages/drugs/index.blade.php ENDPATH**/ ?>
<?php

use Livewire\Component;
use App\Models\Pasien;
use App\Models\RekamMedis;
use App\Models\Pregnancy;
use App\Models\Delivery;
use App\Models\Imunisasi;
use App\Models\Obat;
use Illuminate\Support\Carbon;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    // ---------- Stat Cards ----------
    public int $total_pasien = 0;
    public int $kunjungan_hari_ini = 0;
    public int $kehamilan_aktif = 0;
    public int $imunisasi_hari_ini = 0;
    public int $persalinan_hari_ini = 0;
    public int $total_obat = 0;
    public int $total_user = 0;

    // ---------- Recent RM table ----------
    public int $perPage = 5;
    public string $sortColumn = 'tanggal_pemeriksaan';
    public string $sortDirection = 'desc';

    // ---------- Visit chart ----------
    public array $chartLabels = [];
    public array $chartValues = [];

    public function mount(): void
    {
        $this->loadStats();
        $this->loadVisitChart();
    }

    public function loadStats(): void
    {
        $today = Carbon::today();
        $this->total_pasien        = Pasien::count();
        $this->kunjungan_hari_ini  = RekamMedis::whereDate('tanggal_pemeriksaan', $today)->count();
        $this->kehamilan_aktif     = Pregnancy::count();
        $this->imunisasi_hari_ini  = Imunisasi::whereDate('tanggal_imunisasi', $today)->count();
        $this->persalinan_hari_ini = Delivery::whereDate('tanggal', $today)->count();
        $this->total_obat          = Obat::count();
        $this->total_user          = \App\Models\User::count();
    }

    public function loadVisitChart(): void
    {
        $days   = [];
        $values = [];
        for ($i = 6; $i >= 0; $i--) {
            $d    = Carbon::today()->subDays($i);
            $days[]   = $d->translatedFormat('D');
            $values[] = RekamMedis::whereDate('tanggal_pemeriksaan', $d)->count();
        }
        $this->chartLabels = $days;
        $this->chartValues = $values;
    }

    public function render()
    {
        $recentRecords = RekamMedis::query()
            ->with(['pasien:id,nama,jenis_kelamin', 'user:id,name'])
            ->orderBy($this->sortColumn, $this->sortDirection)
            ->paginate($this->perPage);

        $recentPregnancies = Pregnancy::query()
            ->with('user:id,name')
            ->orderBy('tanggal', 'desc')
            ->limit(3)
            ->get();

        $recentDeliveries = Delivery::query()
            ->with('user:id,name')
            ->orderBy('tanggal', 'desc')
            ->limit(3)
            ->get();

        $recentImmunizations = Imunisasi::query()
            ->with(['pasien:id,nama', 'user:id,name'])
            ->orderBy('tanggal_imunisasi', 'desc')
            ->limit(3)
            ->get();

        return view('pages.admin.dashboard', [
            'recentRecords'      => $recentRecords,
            'recentPregnancies'  => $recentPregnancies,
            'recentDeliveries'   => $recentDeliveries,
            'recentImmunizations'=> $recentImmunizations,
            'chartLabels'        => $this->chartLabels,
            'chartValues'        => $this->chartValues,
        ]);
    }
};

?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->role === 'admin'): ?>
    <div>
        <!-- Page Header -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">Dashboard</h1>
                <p class="mt-1 text-sm text-gray-500">Selamat datang kembali, Admin Bidan! Berikut ringkasan aktivitas hari ini.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-50 text-emerald-700 text-sm font-medium">
                    <i class="bi bi-calendar3"></i>
                    <?php echo e(now()->translatedFormat('l, d F Y')); ?>

                </span>
            </div>
        </div>

        <!-- Stat Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
            <!-- Total Pasien -->
            <div class="stat-card group bg-white rounded-2xl p-5 shadow-elevation-sm hover:shadow-elevation-md border border-gray-100 cursor-pointer">
                <div class="flex items-center justify-between">
                    <div class="p-3 rounded-xl bg-blue-50 group-hover:bg-blue-100 transition-colors">
                        <i class="bi bi-people-fill text-2xl text-blue-600"></i>
                    </div>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">
                        <i class="bi bi-arrow-up-short"></i> Aktif
                    </span>
                </div>
                <div class="mt-4">
                    <p class="text-sm text-gray-500 font-medium">Total Pasien</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo e(number_format($total_pasien)); ?></p>
                </div>
                <div class="mt-3 flex items-center gap-1.5 text-xs text-gray-400">
                    <i class="bi bi-people"></i>
                    <span>Terdaftar di sistem</span>
                </div>
            </div>

            <!-- Kunjungan Hari Ini -->
            <div class="stat-card group bg-white rounded-2xl p-5 shadow-elevation-sm hover:shadow-elevation-md border border-gray-100 cursor-pointer">
                <div class="flex items-center justify-between">
                    <div class="p-3 rounded-xl bg-emerald-50 group-hover:bg-emerald-100 transition-colors">
                        <i class="bi bi-calendar-check-fill text-2xl text-emerald-600"></i>
                    </div>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">
                        <i class="bi bi-arrow-up-short"></i> Hari ini
                    </span>
                </div>
                <div class="mt-4">
                    <p class="text-sm text-gray-500 font-medium">Kunjungan Hari Ini</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo e(number_format($kunjungan_hari_ini)); ?></p>
                </div>
                <div class="mt-3 flex items-center gap-1.5 text-xs text-gray-400">
                    <i class="bi bi-clipboard-heart"></i>
                    <span>Rekam medis baru hari ini</span>
                </div>
            </div>

            <!-- Kehamilan Aktif -->
            <div class="stat-card group bg-white rounded-2xl p-5 shadow-elevation-sm hover:shadow-elevation-md border border-gray-100 cursor-pointer">
                <div class="flex items-center justify-between">
                    <div class="p-3 rounded-xl bg-pink-50 group-hover:bg-pink-100 transition-colors">
                        <i class="bi bi-heart-pulse-fill text-2xl text-pink-500"></i>
                    </div>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold">
                        <i class="bi bi-person-check"></i> Terdata
                    </span>
                </div>
                <div class="mt-4">
                    <p class="text-sm text-gray-500 font-medium">Kehamilan Aktif</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo e(number_format($kehamilan_aktif)); ?></p>
                </div>
                <div class="mt-3 flex items-center gap-1.5 text-xs text-gray-400">
                    <i class="bi bi-calendar-event"></i>
                    <span>Total data ibu hamil</span>
                </div>
            </div>

            <!-- Imunisasi Bayi -->
            <div class="stat-card group bg-white rounded-2xl p-5 shadow-elevation-sm hover:shadow-elevation-md border border-gray-100 cursor-pointer">
                <div class="flex items-center justify-between">
                    <div class="p-3 rounded-xl bg-purple-50 group-hover:bg-purple-100 transition-colors">
                        <i class="bi bi-shield-fill-check text-2xl text-purple-600"></i>
                    </div>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-purple-50 text-purple-700 text-xs font-semibold">
                        <i class="bi bi-arrow-up-short"></i> Aktif
                    </span>
                </div>
                <div class="mt-4">
                    <p class="text-sm text-gray-500 font-medium">Imunisasi Total</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo e(number_format(\App\Models\Imunisasi::count())); ?></p>
                </div>
                <div class="mt-3 flex items-center gap-1.5 text-xs text-gray-400">
                    <i class="bi bi-calendar-check"></i>
                    <span>Hari ini: <?php echo e(number_format($imunisasi_hari_ini)); ?> &middot; Persalinan: <?php echo e(number_format($persalinan_hari_ini)); ?></span>
                </div>
            </div>
        </div>

        <!-- Charts & Activity Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Visit Trend Chart -->
            <div class="lg:col-span-2 bg-white rounded-2xl p-5 sm:p-6 shadow-elevation-sm border border-gray-100">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Grafik Kunjungan</h2>
                        <p class="text-sm text-gray-500 mt-0.5">Rekam medis 7 hari terakhir</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 text-xs font-semibold">
                        <i class="bi bi-calendar-week"></i>
                        7 Hari Terakhir
                    </span>
                </div>
                <div class="h-72 w-full">
                    <canvas id="visitChart"></canvas>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-2xl p-5 sm:p-6 shadow-elevation-sm border border-gray-100">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-lg font-bold text-gray-900">Aktivitas Terbaru</h2>
                </div>

                <div class="space-y-4">
                    <?php
                        $todayRM = \App\Models\RekamMedis::whereDate('tanggal_pemeriksaan', \Carbon\Carbon::today())->with('pasien:id,nama')->latest()->take(3)->get();
                        $todayKehamilan = \App\Models\Pregnancy::whereDate('tanggal', \Carbon\Carbon::today())->latest()->take(1)->get();
                        $todayImunisasi = \App\Models\Imunisasi::whereDate('tanggal_imunisasi', \Carbon\Carbon::today())->with('pasien:id,nama')->latest()->take(1)->get();
                        $todayPersalinan = \App\Models\Delivery::whereDate('tanggal', \Carbon\Carbon::today())->latest()->take(1)->get();
                        $activities = collect();
                        foreach ($todayRM as $r) { $activities->push((object)['type'=>'rekam_medis','icon'=>'bi-clipboard-heart-fill','color'=>'emerald','bg'=>'emerald-50','text'=>'emerald-600','title'=>'Rekam medis ditambahkan','sub'=>($r->pasien?->nama ?? 'N/A'),'time'=>Carbon::parse($r->tanggal_pemeriksaan)->diffForHumans()]); }
                        foreach ($todayKehamilan as $r) { $activities->push((object)['type'=>'kehamilan','icon'=>'bi-heart-fill','color'=>'pink','bg'=>'pink-50','text'=>'pink-500','title'=>'Data kehamilan ditambahkan','sub'=>$r->nama_istri,'time'=>Carbon::parse($r->tanggal)->diffForHumans()]); }
                        foreach ($todayImunisasi as $r) { $activities->push((object)['type'=>'imunisasi','icon'=>'bi-shield-fill-check','color'=>'purple','bg'=>'purple-50','text'=>'purple-600','title'=>'Imunisasi selesai','sub'=>($r->pasien?->nama ?? 'N/A') . ' · ' . $r->jenis_imunisasi,'time'=>Carbon::parse($r->tanggal_imunisasi)->diffForHumans()]); }
                        foreach ($todayPersalinan as $r) { $activities->push((object)['type'=>'persalinan','icon'=>'bi-calendar-heart-fill','color'=>'amber','bg'=>'amber-50','text'=>'amber-600','title'=>'Persalinan selesai','sub'=>$r->nama_istri . ' · Bayi ' . ($r->bayi_lahir ? 'lahir' : 'belum lahir'),'time'=>Carbon::parse($r->tanggal)->diffForHumans()]); }
                        $newPasienToday = Pasien::whereDate('created_at', Carbon::today())->latest()->take(1)->get();
                        foreach ($newPasienToday as $p) { $activities->push((object)['type'=>'pasien','icon'=>'bi-person-plus-fill','color'=>'blue','bg'=>'blue-50','text'=>'blue-600','title'=>'Pasien baru terdaftar','sub'=>$p->nama . ' · ' . ($p->jenis_pasien ?? 'N/A'),'time'=>Carbon::parse($p->created_at)->diffForHumans()]); }
                        $activities = $activities->sortByDesc('time')->take(8)->values();
                    ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $activities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $act): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                        <div class="flex items-start gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors">
                            <div class="p-2 rounded-lg bg-<?php echo e($act->bg); ?> shrink-0">
                                <i class="bi <?php echo e($act->icon); ?> text-<?php echo e($act->text); ?> text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate"><?php echo e($act->title); ?></p>
                                <p class="text-xs text-gray-500 mt-0.5 truncate"><?php echo e($act->sub); ?></p>
                            </div>
                            <span class="text-xs text-gray-400 shrink-0"><?php echo e($act->time); ?></span>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <div class="text-center py-8 text-sm text-gray-400">
                            <i class="bi bi-inbox text-3xl text-gray-300"></i>
                            <p class="mt-2">Belum ada aktivitas hari ini</p>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Medical Records Table -->
        <div class="bg-white rounded-2xl shadow-elevation-sm border border-gray-100 mb-8 overflow-hidden">
            <div class="px-5 sm:px-6 py-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h2 class="text-lg font-bold text-gray-900">Rekam Medis Terbaru</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px]">
                    <thead>
                        <tr class="text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50/80">
                            <th class="text-left px-6 py-3">No. RM</th>
                            <th class="text-left px-6 py-3">Nama Pasien</th>
                            <th class="text-center px-6 py-3">JK</th>
                            <th class="text-left px-6 py-3">Diagnosa</th>
                            <th class="text-left px-6 py-3">Tgl Pemeriksaan</th>
                            <th class="text-left px-6 py-3">Petugas</th>
                            <th class="text-center px-6 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $recentRecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                            <tr class="table-row-hover hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="text-xs font-mono font-semibold text-rose-600 bg-rose-50 px-2.5 py-1 rounded-lg"><?php echo e($record->nomor_rekam_medis); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-gray-900"><?php echo e($record->pasien?->nama ?? 'N/A'); ?></p>
                                    <p class="text-xs text-gray-400"><?php echo e($record->pasien?->nik ?? ''); ?></p>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($record->pasien?->jenis_kelamin === 'L'): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-xs font-semibold">Laki</span>
                                    <?php elseif($record->pasien?->jenis_kelamin === 'P'): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-pink-50 text-pink-700 text-xs font-semibold">Perempuan</span>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">-</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($record->diagnosa): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-xs font-semibold"><?php echo e(Str::limit($record->diagnosa, 50)); ?></span>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">-</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-sm text-gray-600"><?php echo e($record->tanggal_pemeriksaan->format('d/m/Y')); ?></span>
                                        <span class="text-xs text-gray-400"><?php echo e($record->tanggal_pemeriksaan->format('H:i')); ?> WIB</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-600"><?php echo e($record->user?->name ?? 'N/A'); ?></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php $status = $record->tanggal_pemeriksaan->isToday() ? 'Baru' : ($record->tanggal_pemeriksaan->isYesterday() ? 'Kemarin' : 'Lengkap'); ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($record->tanggal_pemeriksaan->isToday()): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 text-xs font-semibold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>Baru
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-semibold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Lengkap
                                        </span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-400">Belum ada data rekam medis</td>
                            </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($recentRecords->hasPages()): ?>
                    <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/50">
                        <p class="text-sm text-gray-500">Menampilkan <?php echo e($recentRecords->firstItem() ?? 0); ?> – <?php echo e($recentRecords->lastItem()); ?> dari <?php echo e($recentRecords->total()); ?></p>
                        <div class="flex gap-1"><?php echo e($recentRecords->links()); ?></div>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>

        <?php
        $__scriptKey = '2399933709-0';
        ob_start();
    ?>
        <script>
            const labels = <?php echo json_encode($chartLabels, 15, 512) ?>;
            const values = <?php echo json_encode($chartValues, 15, 512) ?>;
            const maxVal = Math.max(...values, 1);

            const canvas = document.getElementById('visitChart');
            if (canvas && typeof Chart !== 'undefined') {
                const ctx = canvas.getContext('2d');
                const gradient = ctx.createLinearGradient(0, 0, 0, 288);
                gradient.addColorStop(0, 'rgba(59, 130, 246, 0.25)');
                gradient.addColorStop(1, 'rgba(59, 130, 246, 0.01)');

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Jumlah Kunjungan',
                            data: values,
                            borderColor: '#3b82f6',
                            backgroundColor: gradient,
                            borderWidth: 2.5,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#3b82f6',
                            pointBorderWidth: 2.5,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            fill: true,
                            tension: 0.35,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1e293b',
                                titleFont: { size: 12, weight: '600' },
                                bodyFont: { size: 12 },
                                padding: 10,
                                cornerRadius: 8,
                                displayColors: false,
                                callbacks: {
                                    label: function(context) {
                                        return context.parsed.y + ' kunjungan';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: Math.ceil(maxVal * 1.3),
                                ticks: {
                                    stepSize: 1,
                                    font: { size: 11 },
                                    color: '#9ca3af',
                                    callback: function(val) { return Number.isInteger(val) ? val : ''; }
                                },
                                grid: { color: '#f1f5f9', drawBorder: false }
                            },
                            x: {
                                ticks: {
                                    font: { size: 11 },
                                    color: '#9ca3af'
                                },
                                grid: { display: false, drawBorder: false }
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        }
                    }
                });
            }
        </script>
        <?php
        $__output = ob_get_clean();

        \Livewire\store($this)->push('scripts', $__output, $__scriptKey)
    ?>
<?php else: ?>
    <div>
        <!-- Header -->
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">Dashboard Pegawai</h1>
                    <p class="mt-1 text-sm text-gray-500">Selamat datang, <?php echo e(auth()->user()->name); ?>! Silakan pilih menu untuk mulai bekerja.</p>
                </div>
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-blue-50 text-blue-700 text-sm font-medium">
                    <i class="bi bi-person-badge"></i>
                    <span>Akses: Pegawai Bidan</span>
                </div>
            </div>
        </div>

        <!-- Welcome Card -->
        <div class="bg-white border border-gray-100 shadow-elevation-sm rounded-3xl p-6 sm:p-8 mb-8">
            <div class="flex items-start gap-4">
                <div class="p-4 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 text-white">
                    <i class="bi bi-heart-pulse-fill text-3xl"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-xl text-gray-900">Halo, <?php echo e(explode(' ', auth()->user()->name)[0] ?? 'Pegawai'); ?>!</h3>
                    <p class="text-gray-600 mt-1">Gunakan menu di sidebar untuk mengelola data pasien, rekam medis, kehamilan, persalinan, imunisasi, dan obat.</p>
                </div>
            </div>
        </div>

        <!-- Menu Utama Pegawai -->
        <div>
            <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="bi bi-grid-3x3-gap-fill text-blue-600"></i>
                Menu Utama
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <!-- Pasien -->
                <a href="<?php echo e(route('patients.index')); ?>"
                    class="group block bg-white border border-gray-100 hover:border-blue-200 hover:shadow-elevation-md transition-all rounded-2xl p-6">
                    <div class="flex items-center gap-4">
                        <div class="p-3.5 rounded-2xl bg-blue-100 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                            <i class="bi bi-person-fill text-2xl"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900 group-hover:text-blue-700">Data Pasien</div>
                            <div class="text-xs text-gray-500 mt-0.5">Kelola data pasien</div>
                        </div>
                    </div>
                </a>

                <!-- Obat -->
                <a href="<?php echo e(route('drugs.index')); ?>"
                    class="group block bg-white border border-gray-100 hover:border-emerald-200 hover:shadow-elevation-md transition-all rounded-2xl p-6">
                    <div class="flex items-center gap-4">
                        <div class="p-3.5 rounded-2xl bg-emerald-100 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                            <i class="bi bi-capsule text-2xl"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900 group-hover:text-emerald-700">Data Obat</div>
                            <div class="text-xs text-gray-500 mt-0.5">Inventaris obat</div>
                        </div>
                    </div>
                </a>

                <!-- Rekam Medis -->
                <a href="<?php echo e(route('medical_records.index')); ?>"
                    class="group block bg-white border border-gray-100 hover:border-rose-200 hover:shadow-elevation-md transition-all rounded-2xl p-6">
                    <div class="flex items-center gap-4">
                        <div class="p-3.5 rounded-2xl bg-rose-100 text-rose-600 group-hover:bg-rose-600 group-hover:text-white transition-colors">
                            <i class="bi bi-clipboard-heart-fill text-2xl"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900 group-hover:text-rose-700">Rekam Medis</div>
                            <div class="text-xs text-gray-500 mt-0.5">Catatan pemeriksaan</div>
                        </div>
                    </div>
                </a>

                <!-- Kehamilan -->
                <a href="<?php echo e(route('pregnancy.index')); ?>"
                    class="group block bg-white border border-gray-100 hover:border-pink-200 hover:shadow-elevation-md transition-all rounded-2xl p-6">
                    <div class="flex items-center gap-4">
                        <div class="p-3.5 rounded-2xl bg-pink-100 text-pink-600 group-hover:bg-pink-600 group-hover:text-white transition-colors">
                            <i class="bi bi-heart-pulse-fill text-2xl"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900 group-hover:text-pink-700">Data Kehamilan</div>
                            <div class="text-xs text-gray-500 mt-0.5">Ibu hamil & ANC</div>
                        </div>
                    </div>
                </a>

                <!-- Persalinan -->
                <a href="<?php echo e(route('delivery.index')); ?>"
                    class="group block bg-white border border-gray-100 hover:border-amber-200 hover:shadow-elevation-md transition-all rounded-2xl p-6">
                    <div class="flex items-center gap-4">
                        <div class="p-3.5 rounded-2xl bg-amber-100 text-amber-600 group-hover:bg-amber-600 group-hover:text-white transition-colors">
                            <i class="bi bi-calendar-heart-fill text-2xl"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900 group-hover:text-amber-700">Persalinan</div>
                            <div class="text-xs text-gray-500 mt-0.5">Catatan persalinan</div>
                        </div>
                    </div>
                </a>

                <!-- Imunisasi -->
                <a href="<?php echo e(route('immunization.index')); ?>"
                    class="group block bg-white border border-gray-100 hover:border-purple-200 hover:shadow-elevation-md transition-all rounded-2xl p-6">
                    <div class="flex items-center gap-4">
                        <div class="p-3.5 rounded-2xl bg-purple-100 text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition-colors">
                            <i class="bi bi-shield-fill-check text-2xl"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900 group-hover:text-purple-700">Imunisasi</div>
                            <div class="text-xs text-gray-500 mt-0.5">Jadwal & riwayat</div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="mt-8 text-center text-xs text-gray-400">
            Sistem Rekam Medis Bidan • Akses Terbatas untuk Pegawai
        </div>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH D:\laragon\www\rekam-medis\resources\views/pages/admin/dashboard.blade.php ENDPATH**/ ?>
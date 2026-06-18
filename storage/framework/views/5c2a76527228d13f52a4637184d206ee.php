<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekam Medis - Admin Dashboard</title>

    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

   <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

</head>

<body class="antialiased bg-gray-50 font-sans text-gray-900">
    <!-- Mobile Overlay -->
    <div id="overlay"
        class="fixed inset-0 bg-black/50 z-40 pointer-events-none transition-opacity duration-300 opacity-0 lg:hidden">
    </div>

    <!-- Sidebar -->
    <aside id="sidebar"
        class="fixed top-0 left-0 z-50 h-screen w-64 bg-gradient-to-b from-blue-800 to-blue-900 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
        <div class="flex flex-col h-full">
            <!-- Logo -->
            <div class="flex items-center justify-between px-6 py-5 border-b border-white/10">
                <a href="#" class="flex items-center gap-3 text-white">
                    <i class="bi bi-heart-pulse-fill text-blue-400 text-2xl"></i>
                    <span class="text-xl font-bold tracking-tight">RekamMedis</span>
                </a>
                <button id="sidebarToggle" type="button"
                    class="lg:hidden p-2 text-white/80 hover:text-white rounded-lg hover:bg-white/10 transition-colors">
                    <i class="bi bi-x-lg text-xl"></i>
                </button>
            </div>

            <!-- Navigation Menu -->
            <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1">
                <?php
                    $userRole = auth()->user()->role ?? 'user';
                    if ($userRole === 'admin') {
                        $menuItems = [
                            ['dashboard', 'Dashboard', 'bi-grid-fill'],
                            ['users', 'Manajemen User', 'bi-people-fill'],
                        ];
                    } else {
                        $menuItems = [
                            ['dashboard', 'Dashboard', 'bi-grid-fill'],
                            ['patients', 'Data Pasien', 'bi-person-fill'],
                            ['drugs', 'Data Obat', 'bi-capsule'],
                            ['medical_records', 'Rekam Medis', 'bi-clipboard-heart-fill'],
                            ['pregnancy', 'Data Kehamilan', 'bi-heart-pulse-fill'],
                            ['delivery', 'Persalinan', 'bi-calendar-heart-fill'],
                            ['immunization', 'Imunisasi', 'bi-shield-fill-check'],
                            ['kb', 'Data KB', 'bi-heart-pulse'],
                        ];
                    }
                ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $menuItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$page, $label, $icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                    <a href="<?php echo e(route($page . '.index')); ?>" data-page="<?php echo e($page); ?>"
                        class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-blue-100 hover:text-white hover:bg-white/10 transition-all duration-200 group <?php echo e(request()->routeIs($page . '.*') ? 'bg-white/10 text-white border-l-4 border-blue-400' : 'border-l-4 border-transparent'); ?>">
                        <i class="<?php echo e($icon); ?> text-lg group-hover:scale-110 transition-transform"></i>
                        <span class="font-medium"><?php echo e($label); ?></span>
                    </a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                <?php
                    $reportItems = [
                        ['pasien', 'Laporan Pasien', 'bi-person-lines-fill', 'bi-person-fill'],
                        ['obat', 'Laporan Obat', 'bi-capsule-pill', 'bi-capsule'],
                        ['rekam-medis', 'Laporan Rekam Medis', 'bi-file-medical-fill', 'bi-clipboard-heart-fill'],
                        ['kehamilan', 'Laporan Kehamilan', 'bi-heart-pulse-fill', 'bi-heart-pulse-fill'],
                        ['persalinan', 'Laporan Persalinan', 'bi-calendar-heart-fill', 'bi-calendar-heart-fill'],
                        ['imunisasi', 'Laporan Imunisasi', 'bi-shield-fill-check', 'bi-shield-fill-check'],
                        ['kb', 'Laporan KB', 'bi-heart-pulse', 'bi-heart-pulse'],
                    ];
                ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($userRole === 'admin'): ?>
                <div class="mt-1 space-y-1" id="reportDropdown">
                    <button type="button" id="reportDropdownToggle"
                        class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl text-blue-100 hover:text-white hover:bg-white/10 transition-all duration-200 group">
                        <span class="flex items-center gap-3">
                            <i class="bi bi-file-earmark-bar-graph-fill text-lg group-hover:scale-110 transition-transform"></i>
                            <span class="font-medium">Laporan</span>
                        </span>
                        <i class="bi bi-chevron-down text-sm transition-transform duration-200" id="reportDropdownCaret"></i>
                    </button>
                    <div id="reportDropdownMenu" class="hidden overflow-hidden space-y-1 pl-4">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $reportItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$page, $label, $icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                            <a href="<?php echo e(url('laporan/' . $page)); ?>"
                                class="flex items-center gap-2.5 px-4 py-2.5 rounded-lg text-blue-100 hover:text-white hover:bg-white/10 transition-all duration-200 text-sm group">
                                <i class="<?php echo e($icon); ?> text-base"></i>
                                <span class="font-medium"><?php echo e($label); ?></span>
                            </a>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </nav>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div id="contentWrapper" class="lg:ml-64 min-h-screen transition-all duration-300">
        <!-- Top Navigation -->
        <header class="bg-white/95 backdrop-blur-sm shadow-sm sticky top-0 z-30 border-b border-gray-100">
            <div class="flex items-center justify-between px-4 sm:px-6 py-4">
                <!-- Left: Mobile Toggle & Title -->
                <div class="flex items-center gap-4">
                    <button id="mobileSidebarToggle" type="button"
                        class="lg:hidden p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                        <i class="bi bi-list text-xl"></i>
                    </button>
                    <div class="flex items-center gap-2">
                        <div
                            class="hidden sm:block w-8 h-8 bg-gradient-to-br from-blue-500 to-emerald-500 rounded-lg flex items-center justify-center">
                            <i class="bi bi-heart-pulse-fill text-white text-sm"></i>
                        </div>
                        <div>
                            <h1 class="text-lg sm:text-xl font-bold text-gray-900 leading-tight">Rekam Medis</h1>
                            <p class="text-xs text-gray-500 hidden sm:block">Sistem Informasi Kesehatan Bidan</p>
                        </div>
                    </div>
                </div>

                <!-- Right: Actions -->
                <div class="flex items-center gap-2 sm:gap-4">
                    <!-- Notifications -->
                   

                    <!-- User Profile -->
                    <div class="relative">
                        <button type="button"
                            class="flex items-center gap-2 sm:gap-3 p-1.5 sm:p-2 rounded-lg hover:bg-gray-50 transition-colors"
                            id="userMenuButton" data-dropdown-toggle="userMenu">
                            <div
                                class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm shadow-sm">
                                <?php echo e(strtoupper(substr(Auth::user()->name ?? 'U', 0, 2))); ?>

                            </div>
                            <div class="hidden md:block text-left">
                                <p class="text-sm font-semibold text-gray-900"><?php echo e(Auth::user()->name ?? 'User'); ?></p>
                                <p class="text-xs text-gray-500"><?php echo e(Auth::user()->email ?? ''); ?></p>
                            </div>
                            <i class="bi bi-chevron-down text-gray-400 text-xs sm:text-sm hidden sm:block"></i>
                        </button>
                        <!-- User Dropdown Menu -->
                        <div id="userMenu"
                            class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-2 hidden z-50">
                            <hr class="my-2 border-gray-100">
                            <form action="<?php echo e(route('logout')); ?>" method="POST" class="block">
                                <?php echo csrf_field(); ?>
                                <button type="submit"
                                    class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                    <i class="bi bi-box-arrow-right text-lg"></i> Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="p-4 sm:p-6 lg:p-8">
            <!-- Flash Messages -->
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>
                <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-2xl flex items-center gap-3 text-sm">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?php echo e(session('success')); ?></span>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('error')): ?>
                <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-3 text-sm">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span><?php echo e(session('error')); ?></span>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php echo e($slot); ?>

        </main>

        <!-- Responsive Footer -->
        <footer class="mt-12 py-6 border-t border-gray-200 bg-white">
            <div class="px-6 text-center text-sm text-gray-500">
                <p>&copy; <?php echo e(date('Y')); ?> Rekam Medis Bidan. Crafted with <i
                        class="bi bi-heart-fill text-red-500 mx-1"></i> for better healthcare.</p>
            </div>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="/assets/js/style.js" defer></script>

    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

</body>

</html>
<?php /**PATH D:\laragon\www\rekam-medis\resources\views/layouts/app.blade.php ENDPATH**/ ?>
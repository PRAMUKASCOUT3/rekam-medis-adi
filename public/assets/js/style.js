/**
 * Kilo Admin Dashboard Styles
 * Compatible with Tailwind CSS v4 + Bootstrap Icons + Chart.js
 */

/* ==================== 
   SIDEBAR TOGGLE LOGIC
   ==================== */
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const mobileToggle = document.getElementById('mobileSidebarToggle');
const desktopToggle = document.getElementById('sidebarToggle');

/**
 * Toggle mobile sidebar (slide-in from left)
 */
function toggleMobileSidebar() {
    const isOpen = sidebar.classList.contains('-translate-x-full');
    
    if (isOpen) {
        // Open mobile sidebar
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        overlay.classList.remove('opacity-0', 'pointer-events-none');
        overlay.classList.add('opacity-100', 'pointer-events-auto');
        document.body.style.overflow = 'hidden';
    } else {
        // Close mobile sidebar
        sidebar.classList.remove('translate-x-0');
        sidebar.classList.add('-translate-x-full');
        overlay.classList.remove('opacity-100', 'pointer-events-auto');
        overlay.classList.add('opacity-0', 'pointer-events-none');
        document.body.style.overflow = '';
    }
}

/**
 * Toggle desktop sidebar (collapsed/expanded mode)
 */
function toggleDesktopSidebar() {
    // This is handled by Tailwind utility classes
    // For now, keep content responsive with CSS
    const content = document.getElementById('contentWrapper');
    sidebar.classList.toggle('w-64');
    sidebar.classList.toggle('w-20');
    content.classList.toggle('lg:ml-64');
    content.classList.toggle('lg:ml-20');
}

// Event Listeners
if (mobileToggle) mobileToggle.addEventListener('click', toggleMobileSidebar);
if (desktopToggle) desktopToggle.addEventListener('click', toggleDesktopSidebar);
if (overlay) overlay.addEventListener('click', toggleMobileSidebar);

// Close mobile sidebar on resize to desktop
window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) { // lg breakpoint
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        overlay.classList.remove('opacity-100', 'pointer-events-auto');
        overlay.classList.add('opacity-0', 'pointer-events-none');
        document.body.style.overflow = '';
    }
});

/**
 * Auto-close mobile sidebar when clicking a link
 */
document.querySelectorAll('.sidebar-link').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth < 1024) {
            toggleMobileSidebar();
        }
        
        // Update active state
        document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active', 'bg-white/10', 'text-white', 'border-l-4', 'border-blue-400', 'pl-3'));
        link.classList.add('active', 'bg-white/10', 'text-white', 'border-l-4', 'border-blue-400', 'pl-3');
    });
});

/**
 * Chart.js Configuration - Patient Visits Line Graph
 */
const chartColors = {
    primary: '#3b82f6',
    emerald: '#10b981',
    amber: '#f59e0b',
    purple: '#8b5cf6',
    red: '#ef4444',
    gray: '#6b7280'
};

function initVisitChart() {
    const ctx = document.getElementById('visitChart');
    if (!ctx) return;

    // Create gradient for chart fill
    const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
    gradient.addColorStop(1, 'rgba(16, 185, 129, 0.02)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
            datasets: [{
                label: 'Jumlah Kunjungan',
                data: [32, 45, 38, 52, 41, 36, 38],
                borderColor: chartColors.emerald,
                backgroundColor: gradient,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: chartColors.emerald,
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    ticks: { font: { size: 12 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 12 } }
                }
            }
        }
    });
}

// Initialize chart when DOM is ready
document.addEventListener('DOMContentLoaded', initVisitChart);

/**
 * User Menu Dropdown Toggle
 */
const userMenuButton = document.getElementById('userMenuButton');
const userMenu = document.getElementById('userMenu');

if (userMenuButton && userMenu) {
    userMenuButton.addEventListener('click', (e) => {
        e.stopPropagation();
        userMenu.classList.toggle('hidden');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
            userMenu.classList.add('hidden');
        }
    });
}

/**
 * Report Dropdown Toggle
 */
const reportDropdownToggle = document.getElementById('reportDropdownToggle');
const reportDropdownMenu = document.getElementById('reportDropdownMenu');
const reportDropdownCaret = document.getElementById('reportDropdownCaret');

if (reportDropdownToggle && reportDropdownMenu) {
    reportDropdownToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        reportDropdownMenu.classList.toggle('hidden');
        reportDropdownCaret.classList.toggle('rotate-180');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!reportDropdownToggle.contains(e.target) && !reportDropdownMenu.contains(e.target)) {
            reportDropdownMenu.classList.add('hidden');
            reportDropdownCaret.classList.remove('rotate-180');
        }
    });
}

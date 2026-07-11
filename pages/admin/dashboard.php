<?php
requireLogin();
requireAdmin();
$pageTitle = 'Dashboard | HRMS Core';
$currentPage = 'dashboard';
require_once __DIR__ . '/../../includes/header.php';

// Stats from DB
$totalEmployees = $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active'")->fetchColumn();
$presentToday = $pdo->query("SELECT COUNT(*) FROM attendance WHERE date = CURDATE() AND status = 'present'")->fetchColumn();
$absentToday = $pdo->query("SELECT COUNT(*) FROM attendance WHERE date = CURDATE() AND status = 'absent'")->fetchColumn();
$pendingLeave = $pdo->query("SELECT COUNT(*) FROM leaves WHERE status = 'pending'")->fetchColumn();
$payrollMonth = $pdo->query("SELECT COALESCE(SUM(net_pay), 0) FROM payroll WHERE MONTH(period_start) = MONTH(CURDATE()) AND YEAR(period_start) = YEAR(CURDATE())")->fetchColumn();
?>
<div class="max-w-7xl mx-auto space-y-8">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface flex items-center gap-2"><img src="<?= BASE_URL ?>/public/emojis/Title%20emojis/dashboard.png" class="w-8 h-8" alt=""> Dashboard Overview</h2>
            <p class="text-body-md text-secondary mt-1">Welcome back. Here's what's happening today in your
                organization.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/leave-management"
                class="btn   align-items-center gap-2 px-4 py-2 rounded-lg!">
                <span class="material-symbols-outlined text-lg">time_to_leave</span>
                File Leave
            </a>
            <a href="<?= BASE_URL ?>/admin/add-employee" class="btn align-items-center gap-2 px-4 py-2 rounded-lg!">
                <span class="material-symbols-outlined text-lg">person_add</span>
                Add Employee
            </a>
            <a href="<?= BASE_URL ?>/admin/payroll"
                class=" btn bg-green-400 hover:bg-green-500 text-white rounded-lg d-inline-flex align-items-center gap-2 px-4 py-2">
                <span class="material-symbols-outlined text-lg">payments</span>
                Generate Payroll
            </a>

        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
        <div
            class="stats-card bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center">
                    <img src="<?= BASE_URL ?>/public/emojis/busts-in-silhouette_1f465.png" class="w-6 h-6"
                        alt="employees">
                </div>
                <span
                    class="text-label-sm text-primary font-bold bg-primary-container/20 px-2 py-0.5 rounded">+2%</span>
            </div>
            <div>
                <p class="text-label-md text-secondary uppercase tracking-wider font-bold">Total Employees</p>
                <h3 class="font-headline-lg text-headline-lg mt-1"><?= number_format($totalEmployees) ?></h3>
            </div>
        </div>
        <div
            class="stats-card bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center">
                    <img src="<?= BASE_URL ?>/public/emojis/check-mark-button_2705.png" class="w-6 h-6" alt="present">
                </div>
                <span class="text-label-sm text-green-600 font-bold bg-green-100 px-2 py-0.5 rounded">98%</span>
            </div>
            <div>
                <p class="text-label-md text-secondary uppercase tracking-wider font-bold">Present Today</p>
                <h3 class="font-headline-lg text-headline-lg mt-1"><?= number_format($presentToday) ?></h3>
            </div>
        </div>
        <div
            class="stats-card bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center">
                    <img src="<?= BASE_URL ?>/public/emojis/prohibited_1f6ab.png" class="w-6 h-6" alt="absent">
                </div>
            </div>
            <div>
                <p class="text-label-md text-secondary uppercase tracking-wider font-bold">Absent Today</p>
                <h3 class="font-headline-lg text-headline-lg mt-1"><?= number_format($absentToday) ?></h3>
            </div>
        </div>
        <div
            class="stats-card bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-xl bg-yellow-50 flex items-center justify-center">
                    <img src="<?= BASE_URL ?>/public/emojis/tear-off-calendar_1f4c6.png" class="w-6 h-6" alt="leave">
                </div>
            </div>
            <div>
                <p class="text-label-md text-secondary uppercase tracking-wider font-bold">Pending Leave</p>
                <h3 class="font-headline-lg text-headline-lg mt-1"><?= number_format($pendingLeave) ?></h3>
            </div>
        </div>
        <div
            class="stats-card bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                    <img src="<?= BASE_URL ?>/public/emojis/money-bag_1f4b0.png" class="w-6 h-6" alt="payroll">
                </div>
            </div>
            <div>
                <p class="text-label-md text-secondary uppercase tracking-wider font-bold">Payroll Month</p>
                <h3 class="font-headline-lg text-headline-lg mt-1 font-bold text-on-surface">
                    $<?= number_format($payrollMonth / 1000000, 1) ?>M</h3>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 bg-surface-container-lowest p-8 rounded-2xl shadow-sm border border-border-subtle">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h4 class="font-headline-md text-headline-md">Attendance Overview</h4>
                    <p class="text-label-md text-secondary">Daily attendance metrics for the current week</p>
                </div>
                <select
                    class="bg-surface-muted border-border-subtle rounded-lg text-label-sm py-1 pl-3 pr-8 focus:ring-primary">
                    <option>Last 7 Days</option>
                    <option>Last 30 Days</option>
                </select>
            </div>
            <div class="h-64 flex items-end justify-between gap-4 px-4">
                <?php
                $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                $heights = [85, 92, 78, 88, 95, 20, 15];
                foreach ($days as $i => $day):
                    $isToday = ($i === 4);
                    ?>
                    <div class="flex-1 flex flex-col items-center gap-3">
                        <div class="w-full <?= $isToday ? 'bg-primary' : 'bg-primary-container' ?> rounded-t-lg transition-all duration-500 hover:brightness-90"
                            style="height: <?= $heights[$i] ?>%;"></div>
                        <span
                            class="text-label-sm <?= $isToday ? 'text-on-surface font-bold' : 'text-secondary' ?>"><?= $day ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="space-y-8">
            <div class="bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle">
                <h4 class="font-headline-md text-headline-md mb-6">Distribution</h4>
                <div class="relative w-48 h-48 mx-auto flex items-center justify-center">
                    <div class="w-full h-full rounded-full border-[16px] border-primary"
                        style="clip-path: polygon(50% 50%, 50% 0, 100% 0, 100% 100%, 0 100%, 0 0, 50% 0);"></div>
                    <div class="absolute w-full h-full rounded-full border-[16px] border-secondary-container"
                        style="transform: rotate(270deg); clip-path: polygon(50% 50%, 50% 0, 100% 0, 100% 100%, 50% 100%);">
                    </div>
                    <div class="absolute flex flex-col items-center">
                        <span
                            class="font-headline-md text-headline-md"><?= number_format($totalEmployees / 1000, 1) ?>k</span>
                        <span class="text-label-sm text-secondary">Active</span>
                    </div>
                </div>
            </div>
            <div class="bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="font-label-md text-label-md uppercase tracking-wider font-bold">Recent Activity</h4>
                    <a class="text-primary text-label-sm font-bold hover:underline" href="#">View All</a>
                </div>
                <div class="activity-item flex gap-4 p-3 hover:bg-surface-muted rounded-xl transition-colors">
                    <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-primary">login</span>
                    </div>
                    <div class="flex-1">
                        <p class="text-body-sm text-on-surface"><strong>System</strong> attendance recorded</p>
                        <p class="text-label-sm text-secondary mt-1">Just now</p>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-primary to-on-primary-container p-6 rounded-2xl shadow-md text-white">
                <div class="flex items-center gap-3 mb-4 items-center">
                    <img src="<?= BASE_URL ?>/public/emojis/party-popper_1f389.png" class="w-10 h-10" alt="present">
                    <span class="text-label-sm uppercase tracking-widest font-bold opacity-80">Upcoming Holiday</span>
                </div>
                <div class="flex justify-between items-start">
                    <div>
                        <h5 class="font-headline-md text-headline-md">Labor Day</h5>
                        <p class="text-body-sm opacity-90 mt-1">Monday, May 1, 2024</p>
                    </div>
                    <div class="text-right">
                        <span class="text-display-lg font-display-lg block leading-none">12</span>
                        <span class="text-label-sm uppercase tracking-widest font-bold opacity-80">Days Left</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const statsCards = document.querySelectorAll(".stats-card");
    statsCards.forEach(card => {
        card.addEventListener("mousemove", (e) => {
            const rect = card.getBoundingClientRect();
            card.style.setProperty("--mouse-x", `${e.clientX - rect.left}px`);
            card.style.setProperty("--mouse-y", `${e.clientY - rect.top}px`);
        });
    });
});
</script>
<style>
main {
    background: linear-gradient(rgba(255,255,255,0.92), rgba(255,255,255,0.92)), url('<?= BASE_URL ?>/public/background/dashboard.jpeg') center/cover no-repeat fixed;
    min-height: 100vh;
}
.stats-card { position: relative; overflow: hidden; }
.stats-card::before {
    content: "";
    position: absolute;
    top: var(--mouse-y, 0);
    left: var(--mouse-x, 0);
    width: 0; height: 0;
    background: rgba(47, 242, 158, 0.15);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: width 0.4s ease, height 0.4s ease;
    pointer-events: none;
    z-index: 0;
}
.stats-card:hover::before { width: 300px; height: 300px; }
.stats-card > * { position: relative; z-index: 1; }
</style>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
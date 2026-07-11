<?php
requireLogin();
$pageTitle = 'Employee Dashboard | HRMS Core';
$currentPage = 'employee_dashboard';
require_once __DIR__ . '/../../includes/header.php';

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT e.*, d.name as department_name, e.position as position_title FROM employees e LEFT JOIN departments d ON e.department_id = d.id WHERE e.user_id = ?");
$stmt->execute([$userId]);
$emp = $stmt->fetch();

$leaveCount = $pdo->prepare("SELECT COUNT(*) FROM leaves WHERE employee_id = ? AND status = 'approved'");
$leaveCount->execute([$emp['id'] ?? 0]);
$leaveUsed = $leaveCount->fetchColumn();
?>
<div class="max-w-7xl mx-auto employeedashboardpage">
    <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-6">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-text-heading mb-2">Good morning, <?= h(explode(' ', $_SESSION['user_name'])[0]) ?>! 👋</h2>
            <p class="text-body-md text-text-body">You have 2 pending tasks for today. Your attendance is looking great.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?= BASE_URL ?>/employee/my-payslips" class="flex items-center px-5 py-2.5 bg-white border border-border-subtle text-secondary font-medium rounded-lg hover:bg-surface-muted transition-all soft-elevated">
                <span class="material-symbols-outlined mr-2 text-xl">payments</span>
                View Payslip
            </a>
            <button class="flex items-center px-5 py-2.5 bg-primary-container text-on-primary-container font-bold rounded-lg hover:brightness-95 transition-all soft-elevated">
                <span class="material-symbols-outlined mr-2 text-xl">add</span>
                Request Leave
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-2xl soft-elevated border border-border-subtle group hover:border-primary transition-colors relative overflow-hidden">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 rounded-xl bg-primary/10 text-primary">
                    <span class="material-symbols-outlined">calendar_month</span>
                </div>
                <span class="text-xs font-bold text-primary bg-primary/10 px-2 py-1 rounded-full uppercase tracking-wider">Annual</span>
            </div>
            <p class="text-sm font-medium text-secondary mb-1">Remaining Leave</p>
            <h3 class="font-headline-lg text-headline-lg text-text-heading"><?= max(0, 15 - $leaveUsed) ?> Days</h3>
            <div class="mt-4 w-full bg-surface-muted h-2 rounded-full overflow-hidden">
                <div class="bg-primary h-full rounded-full" style="width: <?= min(100, ($leaveUsed / 15) * 100) ?>%"></div>
            </div>
            <p class="mt-2 text-xs text-secondary italic">Next reset: Jan 1st</p>
        </div>
        <div class="bg-white p-6 rounded-2xl soft-elevated border border-border-subtle group hover:border-primary transition-colors">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 rounded-xl bg-tertiary-container/10 text-tertiary">
                    <span class="material-symbols-outlined">check_circle</span>
                </div>
                <div class="flex items-center text-primary text-xs font-bold">
                    <span class="material-symbols-outlined text-sm mr-1">trending_up</span>
                    +2%
                </div>
            </div>
            <p class="text-sm font-medium text-secondary mb-1">Attendance</p>
            <h3 class="font-headline-lg text-headline-lg text-text-heading">98%</h3>
            <p class="mt-4 text-xs text-secondary">Based on current month average</p>
        </div>
        <div class="bg-white p-6 rounded-2xl soft-elevated border border-border-subtle group hover:border-primary transition-colors">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 rounded-xl bg-secondary-container/30 text-secondary">
                    <span class="material-symbols-outlined">history_edu</span>
                </div>
                <span class="text-xs font-bold text-secondary bg-surface-muted px-2 py-1 rounded-full uppercase tracking-wider">Quarterly</span>
            </div>
            <p class="text-sm font-medium text-secondary mb-1">Next Review</p>
            <h3 class="font-headline-lg text-headline-lg text-text-heading">Oct 12</h3>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl border border-border-subtle soft-elevated flex flex-col h-full overflow-hidden">
                <div class="p-6 border-b border-border-subtle flex justify-between items-center">
                    <h4 class="font-bold text-on-surface">Recent Activity</h4>
                    <button class="text-sm text-primary font-bold hover:underline">View All</button>
                </div>
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-primary text-xl">login</span>
                        </div>
                        <div class="flex-grow">
                            <div class="flex justify-between">
                                <p class="font-bold text-on-surface">Attendance logged</p>
                                <span class="text-xs text-secondary">08:45 AM</span>
                            </div>
                            <p class="text-sm text-secondary mt-1">Successfully clocked in for the shift. Status: <strong>On-Time</strong></p>
                        </div>
                    </div>
                </div>
                <div class="mt-auto p-6 bg-surface-muted/50 border-t border-border-subtle">
                    <p class="text-xs text-secondary text-center">That's all for today's updates</p>
                </div>
            </div>
        </div>
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl border border-border-subtle soft-elevated overflow-hidden">
                <div class="p-6 border-b border-border-subtle">
                    <h4 class="font-bold text-on-surface">Upcoming Holidays</h4>
                </div>
                <div class="p-0">
                    <div class="p-4 hover:bg-surface-muted transition-colors flex items-center gap-4 border-b border-border-subtle">
                        <div class="bg-tertiary-fixed text-on-tertiary-fixed px-3 py-1 rounded text-center min-w-[56px]">
                            <p class="text-xs font-bold">OCT</p>
                            <p class="text-lg font-bold leading-tight">31</p>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-on-surface">Halloween</p>
                            <p class="text-xs text-secondary">Optional Holiday</p>
                        </div>
                    </div>
                    <div class="p-4 hover:bg-surface-muted transition-colors flex items-center gap-4 border-b border-border-subtle">
                        <div class="bg-primary-container text-on-primary-container px-3 py-1 rounded text-center min-w-[56px]">
                            <p class="text-xs font-bold">NOV</p>
                            <p class="text-lg font-bold leading-tight">11</p>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-on-surface">Veterans Day</p>
                            <p class="text-xs text-secondary">Public Holiday</p>
                        </div>
                    </div>
                    <div class="p-4 hover:bg-surface-muted transition-colors flex items-center gap-4 border-b border-border-subtle">
                        <div class="bg-secondary-container text-on-secondary-container px-3 py-1 rounded text-center min-w-[56px]">
                            <p class="text-xs font-bold">NOV</p>
                            <p class="text-lg font-bold leading-tight">23</p>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-on-surface">Thanksgiving</p>
                            <p class="text-xs text-secondary">Public Holiday</p>
                        </div>
                    </div>
                </div>
                <div class="p-4 text-center">
                    <button class="text-sm font-bold text-primary hover:text-on-primary-container transition-colors">See Holiday Calendar</button>
                </div>
            </div>
        </div>
    </div>

    <button class="fixed bottom-10 right-10 w-14 h-14 bg-primary text-white rounded-full flex items-center justify-center shadow-2xl hover:scale-110 transition-transform active:scale-95 group">
        <span class="material-symbols-outlined text-2xl group-hover:rotate-12 transition-transform">support_agent</span>
    </button>
</div>

<?php $pageScripts = '
<script>
document.querySelectorAll(".soft-elevated").forEach(card => {
    card.addEventListener("mouseenter", () => { card.style.transform = "translateY(-2px)"; card.style.transition = "transform 0.2s ease-out, box-shadow 0.2s ease-out"; card.classList.add("shadow-lg"); });
    card.addEventListener("mouseleave", () => { card.style.transform = "translateY(0px)"; card.classList.remove("shadow-lg"); });
});
</script>';
require_once __DIR__ . '/../../includes/footer.php'; ?>

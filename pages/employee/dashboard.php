<?php
requireLogin();
$pageTitle = 'Employee Dashboard | HRMS Core';
$currentPage = 'employee_dashboard';
require_once __DIR__ . '/../../includes/header.php';

// auto-migration for events table
try { $pdo->query("SELECT id FROM events LIMIT 0"); }
catch (Exception $e) {
    $pdo->exec("CREATE TABLE events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        event_date DATE NOT NULL,
        description TEXT,
        type ENUM('holiday', 'event', 'meeting') DEFAULT 'event',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (event_date)
    )");
    $pdo->exec("INSERT INTO events (title, event_date, description, type) VALUES
        ('New Year\'s Day', CURDATE() + INTERVAL 1 DAY, 'Public Holiday', 'holiday'),
        ('Labor Day', DATE_FORMAT(CURDATE(), '%Y-05-01'), 'Public Holiday', 'holiday'),
        ('Independence Day', DATE_FORMAT(CURDATE(), '%Y-06-12'), 'Public Holiday', 'holiday'),
        ('Christmas Day', DATE_FORMAT(CURDATE(), '%Y-12-25'), 'Public Holiday', 'holiday')
    ");
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT e.*, d.name as department_name, e.position as position_title FROM employees e LEFT JOIN departments d ON e.department_id = d.id WHERE e.user_id = ?");
$stmt->execute([$userId]);
$emp = $stmt->fetch();
$empId = $emp['id'] ?? 0;

// leave stats
$leaveUsed = $pdo->prepare("SELECT COUNT(*) FROM leaves WHERE employee_id = ? AND status = 'approved'");
$leaveUsed->execute([$empId]);
$leaveUsed = $leaveUsed->fetchColumn();
$pendingLeave = $pdo->prepare("SELECT COUNT(*) FROM leaves WHERE employee_id = ? AND status = 'pending'");
$pendingLeave->execute([$empId]);
$pendingLeave = $pendingLeave->fetchColumn();

// attendance today
$todayAtt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = CURDATE()");
$todayAtt->execute([$empId]);
$todayAtt = $todayAtt->fetch();

// attendance rate this month
$totalDays = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE employee_id = ? AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())");
$totalDays->execute([$empId]);
$totalDays = $totalDays->fetchColumn();
$presentDays = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE employee_id = ? AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE()) AND status IN ('present', 'late')");
$presentDays->execute([$empId]);
$presentDays = $presentDays->fetchColumn();
$attRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100) : 0;

// upcoming events
$events = $pdo->query("SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date LIMIT 5")->fetchAll();

// unpaid payroll
$unpaidPayroll = $pdo->prepare("SELECT * FROM payroll WHERE employee_id = ? AND status != 'paid' ORDER BY period_start DESC LIMIT 3");
$unpaidPayroll->execute([$empId]);
$unpaidPayroll = $unpaidPayroll->fetchAll();

// recent audit
$recentAudits = $pdo->prepare("SELECT al.*, u.email as user_email FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id WHERE al.user_id = ? OR al.details LIKE ? ORDER BY al.created_at DESC LIMIT 3");
$recentAudits->execute([$userId, '%' . $empId . '%']);
$recentAudits = $recentAudits->fetchAll();

$hour = (int)date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$pendingTasks = $pendingLeave > 0 ? "You have $pendingLeave pending leave request" . ($pendingLeave > 1 ? 's' : '') . '.' : 'Your attendance is looking great.';
?>
<div class="max-w-7xl mx-auto space-y-8">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface flex items-center gap-2"><?= $greeting ?>, <?= h(explode(' ', $_SESSION['user_name'])[0]) ?>! <img src="<?= BASE_URL ?>/public/emojis/waving-hand_1f44b.png" class="w-8 h-8" alt="waving"></h2>
            <p class="text-body-md text-secondary mt-1"><?= h($pendingTasks) ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/employee/my-payslips"
                class="bg-green-400 hover:bg-green-500 text-white rounded-lg d-inline-flex align-items-center gap-2 px-4 py-2">
                <span class="material-symbols-outlined text-lg">payments</span>
                View Payslip
            </a>
            <a href="<?= BASE_URL ?>/employee/request-leave"
                class="bg-green-400 hover:bg-green-500 text-white rounded-lg d-inline-flex align-items-center gap-2 px-4 py-2">
                <span class="material-symbols-outlined text-lg">add</span>
                Request Leave
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="stats-card bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center">
                    <img src="<?= BASE_URL ?>/public/emojis/check-mark-button_2705.png" class="w-6 h-6" alt="leave">
                </div>
                <span class="text-label-sm text-primary font-bold bg-primary-container/20 px-2 py-0.5 rounded">Annual</span>
            </div>
            <div>
                <p class="text-label-md text-secondary uppercase tracking-wider font-bold">Remaining Leave</p>
                <h3 class="font-headline-lg text-headline-lg mt-1"><?= max(0, 15 - $leaveUsed) ?> Days</h3>
                <div class="mt-4 w-full bg-surface-muted h-2 rounded-full overflow-hidden">
                    <div class="bg-primary h-full rounded-full" style="width: <?= min(100, ($leaveUsed / 15) * 100) ?>%"></div>
                </div>
                <p class="mt-2 text-xs text-secondary italic">Next reset: Jan 1st</p>
            </div>
        </div>
        <div class="stats-card bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center">
                    <img src="<?= BASE_URL ?>/public/emojis/tear-off-calendar_1f4c6.png" class="w-6 h-6" alt="attendance">
                </div>
                <span class="text-label-sm text-green-600 font-bold bg-green-100 px-2 py-0.5 rounded">+<?= $attRate ?>%</span>
            </div>
            <div>
                <p class="text-label-md text-secondary uppercase tracking-wider font-bold">Attendance Rate</p>
                <h3 class="font-headline-lg text-headline-lg mt-1"><?= $attRate ?>%</h3>
                <p class="mt-4 text-xs text-secondary">Based on current month average</p>
            </div>
        </div>
        <div class="stats-card bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-xl bg-yellow-50 flex items-center justify-center">
                    <img src="<?= BASE_URL ?>/public/emojis/pendingleave.png" class="w-6 h-6" alt="pending">
                </div>
                <span class="text-label-sm text-yellow-600 font-bold bg-yellow-100 px-2 py-0.5 rounded"><?= $pendingLeave ?> pending</span>
            </div>
            <div>
                <p class="text-label-md text-secondary uppercase tracking-wider font-bold">Pending Leave</p>
                <h3 class="font-headline-lg text-headline-lg mt-1"><?= $pendingLeave ?></h3>
                <p class="mt-4 text-xs text-secondary">Awaiting approval</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle">
                <div class="mb-4">
                    <h4 class="font-headline-md text-headline-md">Today's Attendance</h4>
                    <p class="text-label-sm text-secondary"><?= date('F d, Y') ?></p>
                </div>
                <?php if ($todayAtt): ?>
                <div class="flex items-center gap-6 p-6 bg-surface-muted rounded-xl">
                    <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary text-3xl">login</span>
                    </div>
                    <div>
                        <p class="text-body-md text-secondary">Clock In: <strong class="text-on-surface"><?= h($todayAtt['clock_in'] ?? '--') ?></strong></p>
                        <p class="text-body-md text-secondary mt-1">Clock Out: <strong class="text-on-surface"><?= h($todayAtt['clock_out'] ?? '--') ?></strong></p>
                        <p class="text-body-md text-secondary mt-1">Status: <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase <?= $todayAtt['status'] === 'present' ? 'bg-green-100 text-green-700' : ($todayAtt['status'] === 'late' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') ?>"><?= ucfirst($todayAtt['status']) ?></span></p>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-center py-12 text-secondary">
                    <span class="material-symbols-outlined text-4xl text-secondary mb-2">calendar_today</span>
                    <p class="text-body-md">No attendance record yet today.</p>
                </div>
                <?php endif; ?>
            </div>

            <div class="bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary">history</span>
                        </div>
                        <div>
                            <h4 class="font-headline-md text-headline-md">Recent Activity</h4>
                            <p class="text-label-sm text-secondary">Your latest actions</p>
                        </div>
                    </div>
                </div>
                <?php if (!empty($recentAudits)): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-border-subtle">
                                <th class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-wider font-bold">Action</th>
                                <th class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-wider font-bold">Details</th>
                                <th class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-wider font-bold">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentAudits as $audit): ?>
                            <tr class="hover:bg-surface-muted transition-colors border-b border-border-subtle/50">
                                <td class="px-4 py-3 text-body-sm font-semibold capitalize"><?= h(str_replace('_', ' ', $audit['action'])) ?></td>
                                <td class="px-4 py-3 text-body-sm text-secondary"><?= h($audit['details'] ?? '') ?></td>
                                <td class="px-4 py-3 text-body-sm text-secondary"><?= date('M d, h:i A', strtotime($audit['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-8 text-secondary">
                    <span class="material-symbols-outlined text-4xl text-secondary mb-2">history</span>
                    <p class="text-body-md">No recent activity</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="flex flex-col gap-4 h-full">
            <div class="flex-1 bg-gradient-to-br from-primary to-on-primary-container p-6 rounded-2xl shadow-md text-white flex flex-col min-h-[140px]">
                <div class="flex items-center gap-2 mb-3">
                    <img src="<?= BASE_URL ?>/public/emojis/party-popper_1f389.png" class="w-7 h-7" alt="holiday">
                    <span class="text-sm uppercase tracking-wider font-bold opacity-80">Upcoming Events</span>
                </div>
                <div class="flex-1">
                    <?php if (!empty($events)): ?>
                    <?php foreach ($events as $ev): ?>
                    <div class="flex items-center gap-3 py-2 border-b border-white/10 last:border-0">
                        <div class="bg-white/20 px-3 py-1 rounded text-center min-w-[56px]">
                            <p class="text-xs font-bold"><?= strtoupper(date('M', strtotime($ev['event_date']))) ?></p>
                            <p class="text-lg font-bold leading-tight"><?= date('j', strtotime($ev['event_date'])) ?></p>
                        </div>
                        <div>
                            <p class="text-sm font-bold"><?= h($ev['title']) ?></p>
                            <p class="text-xs opacity-80"><?= h($ev['type'] == 'holiday' ? 'Public Holiday' : $ev['description']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-center py-4 text-white/80 text-sm">No upcoming events</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex-1 bg-surface-container-lowest p-5 rounded-2xl shadow-sm border border-border-subtle flex flex-col">
                <h4 class="font-label-md text-label-md uppercase tracking-wider font-bold mb-3">Unpaid Payroll</h4>
                <div class="flex-1 space-y-2">
                    <?php if (!empty($unpaidPayroll)): ?>
                    <?php foreach ($unpaidPayroll as $up): ?>
                    <div class="flex items-center gap-3 p-3 rounded-lg bg-surface-muted">
                        <span class="material-symbols-outlined text-primary">payments</span>
                        <div class="flex-1 min-w-0">
                            <p class="text-body-sm font-medium truncate"><?= h($up['period_start']) ?> → <?= h($up['period_end']) ?></p>
                            <p class="text-xs text-secondary">₱<?= number_format($up['net_pay'], 2) ?> · <span class="capitalize"><?= h($up['status']) ?></span></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-center py-6 text-secondary text-sm">No unpaid payroll records</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <a href="<?= BASE_URL ?>/employee/request-leave" class="fixed bottom-10 right-10 w-14 h-14 bg-primary text-white rounded-full flex items-center justify-center shadow-2xl hover:scale-110 transition-transform active:scale-95 group">
        <span class="material-symbols-outlined text-2xl group-hover:rotate-12 transition-transform">add</span>
    </a>
</div>

<?php $pageScripts = '
<style>
main {
    background: linear-gradient(rgba(255,255,255,0.92), rgba(255,255,255,0.92)), url("' . BASE_URL . '/public/background/dashboard.jpeg") center/cover no-repeat fixed;
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
<script>
document.querySelectorAll(".stats-card").forEach(card => {
    card.addEventListener("mousemove", (e) => {
        const rect = card.getBoundingClientRect();
        card.style.setProperty("--mouse-x", e.clientX - rect.left + "px");
        card.style.setProperty("--mouse-y", e.clientY - rect.top + "px");
    });
});
</script>';
require_once __DIR__ . '/../../includes/footer.php'; ?>

<?php
requireLogin();
requireHrOrAdmin();
$pageTitle = 'Dashboard | HRMS Core';
$currentPage = 'dashboard';
require_once __DIR__ . '/../../includes/header.php';

// Get filter from URL parameter (default: 7 days)
$filter = $_GET['filter'] ?? '7days';

// Calculate date range based on filter
switch ($filter) {
    case '1month':
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $filterLabel = 'Last 30 Days';
        break;
    case '1year':
        $startDate = date('Y-m-d', strtotime('-365 days'));
        $filterLabel = 'Last 1 Year';
        break;
    default: // 7days
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $filterLabel = 'Last 7 Days';
}

// Stats from DB
$totalEmployees = $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active'")->fetchColumn();
$presentToday = $pdo->query("SELECT COUNT(*) FROM attendance WHERE date = CURDATE() AND status = 'present'")->fetchColumn();
$absentToday = $pdo->query("SELECT COUNT(*) FROM attendance WHERE date = CURDATE() AND status = 'absent'")->fetchColumn();
$pendingLeave = $pdo->query("SELECT COUNT(*) FROM leaves WHERE status = 'pending'")->fetchColumn();
$payrollMonth = $pdo->query("SELECT COALESCE(SUM(gross_pay), 0) FROM payroll WHERE MONTH(period_start) = MONTH(CURDATE()) AND YEAR(period_start) = YEAR(CURDATE())")->fetchColumn();

// Get attendance data for chart based on filter
if ($filter === '1year') {
    // Group by month for yearly view
    $attendanceData = $pdo->query("
        SELECT 
            DATE_FORMAT(date, '%b %Y') as label,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
            SUM(CASE WHEN status = 'half_day' THEN 1 ELSE 0 END) as half_count
        FROM attendance 
        WHERE date >= '$startDate'
        GROUP BY DATE_FORMAT(date, '%Y-%m')
        ORDER BY MIN(date)
    ")->fetchAll();
} elseif ($filter === '1month') {
    // Group by week for monthly view
    $attendanceData = $pdo->query("
        SELECT 
            CONCAT('Week ', FLOOR(DATEDIFF(date, '$startDate') / 7) + 1) as label,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
            SUM(CASE WHEN status = 'half_day' THEN 1 ELSE 0 END) as half_count
        FROM attendance 
        WHERE date >= '$startDate'
        GROUP BY FLOOR(DATEDIFF(date, '$startDate') / 7)
        ORDER BY MIN(date)
    ")->fetchAll();
} else {
    // Group by day for 7 days view
    $attendanceData = $pdo->query("
        SELECT 
            DATE_FORMAT(date, '%a') as label,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
            SUM(CASE WHEN status = 'half_day' THEN 1 ELSE 0 END) as half_count
        FROM attendance 
        WHERE date >= '$startDate'
        GROUP BY date
        ORDER BY date
    ")->fetchAll();
}

// Get department distribution
$departmentData = $pdo->query("
    SELECT d.name, COUNT(e.id) as employee_count
    FROM departments d
    LEFT JOIN employees e ON d.id = e.department_id
    GROUP BY d.id, d.name
    ORDER BY d.name
")->fetchAll();

// Get latest audit logs
$latestAudits = $pdo->query("
    SELECT al.*, u.email as user_email
    FROM audit_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 1
")->fetchAll();

// Get unpaid attendance count and recent records
$unpaidCount = $pdo->query("SELECT COUNT(*) FROM attendance WHERE pay_status = 'unpaid'")->fetchColumn();
$unpaidAttendance = $pdo->query("
    SELECT a.*, e.first_name, e.last_name, e.employee_id
    FROM attendance a
    JOIN employees e ON a.employee_id = e.id
    WHERE a.pay_status = 'unpaid'
    ORDER BY a.date DESC
    LIMIT 5
")->fetchAll();
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
                class="bg-green-400 hover:bg-green-500 text-white rounded-lg d-inline-flex align-items-center gap-2 px-4 py-2">
                <span class="material-symbols-outlined text-lg">time_to_leave</span>
                File Leave
            </a>
            <a href="<?= BASE_URL ?>/admin/add-employee" class="bg-green-400 hover:bg-green-500 text-white rounded-lg d-inline-flex align-items-center gap-2 px-4 py-2">
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
                    ₱<?= number_format($payrollMonth, 2) ?></h3>
            </div>
        </div>
    </div>

    <!-- Attendance Overview Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h4 class="font-headline-md text-headline-md">Attendance Overview</h4>
                        <p class="text-label-sm text-secondary"><?= $filterLabel ?></p>
                    </div>
                    <div class="flex gap-2">
                        <a href="?filter=7days" class="px-3 py-1.5 rounded-lg text-label-sm font-bold transition-colors <?= $filter === '7days' ? 'bg-primary text-white' : 'bg-surface-muted text-secondary hover:bg-primary/10' ?>">7 Days</a>
                        <a href="?filter=1month" class="px-3 py-1.5 rounded-lg text-label-sm font-bold transition-colors <?= $filter === '1month' ? 'bg-primary text-white' : 'bg-surface-muted text-secondary hover:bg-primary/10' ?>">1 Month</a>
                        <a href="?filter=1year" class="px-3 py-1.5 rounded-lg text-label-sm font-bold transition-colors <?= $filter === '1year' ? 'bg-primary text-white' : 'bg-surface-muted text-secondary hover:bg-primary/10' ?>">1 Year</a>
                    </div>
                </div>
                <div class="h-48">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>

            <!-- Unpaid Attendance -->
            <div class="bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center">
                    <span class="material-symbols-outlined text-orange-500">pending_actions</span>
                </div>
                <div>
                    <h4 class="font-headline-md text-headline-md">Unpaid Attendance</h4>
                    <p class="text-label-sm text-secondary"><?= number_format($unpaidCount) ?> records pending payment</p>
                </div>
            </div>
            <a href="<?= BASE_URL ?>/admin/attendance" class="btn bg-orange-100 text-orange-700 hover:bg-orange-200 px-4 py-2 rounded-lg font-bold text-label-sm">
                View All
            </a>
        </div>
        <?php if (!empty($unpaidAttendance)): ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-border-subtle">
                        <th class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-wider font-bold">Employee</th>
                        <th class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-wider font-bold">Employee ID</th>
                        <th class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-wider font-bold">Date</th>
                        <th class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-wider font-bold">Status</th>
                        <th class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-wider font-bold">Hours Worked</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($unpaidAttendance as $record): ?>
                    <tr class="hover:bg-surface-muted transition-colors border-b border-border-subtle/50">
                        <td class="px-4 py-3 text-body-sm">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                                    <span class="text-label-sm font-bold text-primary"><?= strtoupper(substr($record['first_name'], 0, 1)) ?></span>
                                </div>
                                <span class="font-medium"><?= h($record['first_name'] . ' ' . $record['last_name']) ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-body-sm text-secondary"><?= h($record['employee_id']) ?></td>
                        <td class="px-4 py-3 text-body-sm"><?= date('M d, Y', strtotime($record['date'])) ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold <?php
                                echo match($record['status']) {
                                    'present' => 'bg-green-100 text-green-700',
                                    'absent' => 'bg-red-100 text-red-700',
                                    'late' => 'bg-yellow-100 text-yellow-700',
                                    'half_day' => 'bg-orange-100 text-orange-700',
                                    default => 'bg-gray-100 text-gray-700'
                                };
                            ?>"><?= ucfirst(h($record['status'])) ?></span>
                        </td>
                        <td class="px-4 py-3 text-body-sm"><?= $record['hours_worked'] ? number_format($record['hours_worked'], 1) . ' hrs' : '--' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-8 text-secondary">
            <span class="material-symbols-outlined text-4xl text-green-400 mb-2">check_circle</span>
            <p class="text-body-md">All attendance records have been paid!</p>
        </div>
        <?php endif; ?>
    </div>
</div>
        <div class="flex flex-col gap-4 h-full">
            <!-- Distribution -->
            <div class="flex-1 bg-surface-container-lowest p-4 rounded-2xl shadow-sm border border-border-subtle flex flex-col">
                <h4 class="font-headline-md text-headline-md mb-3">Distribution</h4>
                <div class="flex-1 flex flex-col items-center justify-center">
                    <div class="w-32 h-32">
                        <canvas id="departmentPieChart"></canvas>
                    </div>
                    <div id="deptLegend" class="mt-3 flex flex-wrap justify-center gap-2"></div>
                </div>
            </div>
            <!-- Recent Activity -->
            <div class="flex-1 bg-surface-container-lowest p-5 rounded-2xl shadow-sm border border-border-subtle flex flex-col">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-label-md text-label-md uppercase tracking-wider font-bold">Recent Activity</h4>
                    <a class="text-primary text-xs font-bold hover:underline" href="<?= BASE_URL ?>/admin/audit-logs">View All</a>
                </div>
                <div class="flex-1 space-y-2">
                    <?php if (!empty($latestAudits)): ?>
                    <?php foreach ($latestAudits as $audit): ?>
                    <div class="flex gap-3 p-3 hover:bg-surface-muted rounded-lg transition-colors w-full">
                        <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-primary text-sm">history</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-on-surface truncate"><strong><?= h($audit['user_email'] ?? 'System') ?></strong> <?= h($audit['action']) ?></p>
                            <p class="text-xs text-secondary/70"><?= date('M d, h:i A', strtotime($audit['created_at'])) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-center text-secondary py-8 text-sm w-full">No recent activity</div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Upcoming Event -->
            <div class="flex-1 bg-gradient-to-br from-primary to-on-primary-container p-6 rounded-2xl shadow-md text-white flex flex-col min-h-[140px]">
                <div class="flex items-center gap-2 mb-3">
                    <img src="<?= BASE_URL ?>/public/emojis/party-popper_1f389.png" class="w-7 h-7" alt="present">
                    <span class="text-sm uppercase tracking-wider font-bold opacity-80">Upcoming Holiday</span>
                </div>
                <div class="flex-1 flex items-center">
                    <div class="flex justify-between items-start w-full">
                        <div>
                            <h5 class="font-headline-lg text-headline-lg">Labor Day</h5>
                            <p class="text-sm opacity-90 mt-1">Monday, May 1, 2024</p>
                        </div>
                        <div class="text-right">
                            <span class="text-display-lg font-headline-lg block leading-none">12</span>
                            <span class="text-sm uppercase tracking-widest font-bold opacity-80">Days Left</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    // Stats card hover effect
    const statsCards = document.querySelectorAll(".stats-card");
    statsCards.forEach(card => {
        card.addEventListener("mousemove", (e) => {
            const rect = card.getBoundingClientRect();
            card.style.setProperty("--mouse-x", `${e.clientX - rect.left}px`);
            card.style.setProperty("--mouse-y", `${e.clientY - rect.top}px`);
        });
    });

    // Attendance Bar Chart
    const attendanceCtx = document.getElementById('attendanceChart');
    if (attendanceCtx) {
        const attendanceData = <?= json_encode(array_values($attendanceData)) ?>;
        const labels = attendanceData.map(d => d.label);
        const presentData = attendanceData.map(d => parseInt(d.present_count) || 0);
        const absentData = attendanceData.map(d => parseInt(d.absent_count) || 0);
        const lateData = attendanceData.map(d => parseInt(d.late_count) || 0);
        const halfData = attendanceData.map(d => parseInt(d.half_count) || 0);
        
        new Chart(attendanceCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Present',
                        data: presentData,
                        backgroundColor: '#6366f1',
                        borderRadius: 6,
                        barThickness: 24,
                    },
                    {
                        label: 'Late',
                        data: lateData,
                        backgroundColor: '#facc15',
                        borderRadius: 6,
                        barThickness: 24,
                    },
                    {
                        label: 'Half Day',
                        data: halfData,
                        backgroundColor: '#fb923c',
                        borderRadius: 6,
                        barThickness: 24,
                    },
                    {
                        label: 'Absent',
                        data: absentData,
                        backgroundColor: '#fca5a5',
                        borderRadius: 6,
                        barThickness: 24,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8,
                            padding: 20,
                            font: { size: 12, weight: '500' }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { 
                            font: { size: 11 },
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    // Department Pie Chart
    const departmentCtx = document.getElementById('departmentPieChart');
    if (departmentCtx) {
        const departmentData = <?= json_encode(array_values($departmentData)) ?>;
        const deptLabels = departmentData.map(d => d.name);
        const deptCounts = departmentData.map(d => parseInt(d.employee_count));
        const deptColors = departmentData.map(() => '#' + Math.floor(Math.random()*16777215).toString(16).padStart(6, '0'));
        
        new Chart(departmentCtx, {
            type: 'doughnut',
            data: {
                labels: deptLabels,
                datasets: [{
                    data: deptCounts,
                    backgroundColor: deptColors,
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '65%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.raw / total) * 100).toFixed(1);
                                return `${context.label}: ${context.raw} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        const legendEl = document.getElementById('deptLegend');
        deptLabels.forEach(function(label, i) {
            const div = document.createElement('div');
            div.className = 'flex items-center gap-1.5';
            div.innerHTML = '<span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color:' + deptColors[i] + '"></span><span class="text-xs text-secondary">' + label + ' (' + deptCounts[i] + ')</span>';
            legendEl.appendChild(div);
        });
    }
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
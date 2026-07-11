<?php
requireLogin();
requireAdmin();
$pageTitle = 'Attendance Management | HRMS Core';
$currentPage = 'attendance';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/pagination.php';

$msg = $error = '';
$today = date('Y-m-d');

// Auto-migrate: add minutes_late column if missing
try { $pdo->query("SELECT minutes_late FROM attendance LIMIT 0"); }
catch (Exception $e) { $pdo->exec("ALTER TABLE attendance ADD COLUMN minutes_late INT DEFAULT 0 AFTER hours_worked"); }

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save'])) {
        $date = $_POST['att_date'] ?? $today;
        $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, clock_in, clock_out, hours_worked, minutes_late, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE clock_in=VALUES(clock_in), clock_out=VALUES(clock_out), hours_worked=VALUES(hours_worked), minutes_late=VALUES(minutes_late), status=VALUES(status), notes=VALUES(notes)");
        foreach ($_POST['employees'] as $empId => $data) {
            if (!empty($data['status'])) {
                $ci = null;
                $co = null;
                $ml = (int)($data['minutes_late'] ?? 0);
                if ($data['status'] === 'absent') {
                    $hw = 0;
                    $ml = 0;
                } else {
                    $ci = $data['clock_in'] ?: null;
                    $co = $data['clock_out'] ?: null;
                    $hw = null;
                    if ($ci && $co) {
                        $s = new DateTime($ci);
                        $e = new DateTime($co);
                        if ($e <= $s) $e->modify('+1 day');
                        $hw = round(($e->getTimestamp() - $s->getTimestamp()) / 3600, 2);
                        $hw = max(0, $hw - ($ml / 60));
                    }
                }
                $stmt->execute([$empId, $date, $ci, $co, $hw, $ml, $data['status'], $data['notes'] ?? null]);
            }
        }
        logAudit('update', 'attendance', null, "Saved attendance for $date");
        $msg = 'Attendance saved.';
    }
}

// Filters
$filterDate = $_GET['date'] ?? $today;
$filterDept = $_GET['department_id'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterSearch = $_GET['search'] ?? '';
$resetFilters = isset($_GET['reset']);
if ($resetFilters) {
    $filterDate = $today;
    $filterDept = '';
    $filterStatus = '';
    $filterSearch = '';
}

$deptList = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();

// Summary
$totalActiveVal = $pdo->query("SELECT COUNT(*) FROM employees e LEFT JOIN users u ON e.user_id = u.id WHERE e.status='active' AND (u.role IS NULL OR u.role != 'admin')")->fetchColumn();
$presentVal = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE date=? AND status='present'");
$presentVal->execute([$today]);
$absentVal = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE date=? AND status='absent'");
$absentVal->execute([$today]);
$lateVal = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE date=? AND status='late'");
$lateVal->execute([$today]);
$halfVal = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE date=? AND status='half_day'");
$halfVal->execute([$today]);
$onLeaveVal = $pdo->query("SELECT COUNT(*) FROM employees e LEFT JOIN users u ON e.user_id = u.id WHERE e.status='on_leave' AND (u.id IS NULL OR u.role != 'admin')")->fetchColumn();

// Build query
$where = ["a.date = ?"];
$params = [$filterDate];
if ($filterDept) {
    $where[] = "e.department_id = ?";
    $params[] = $filterDept;
}
if ($filterStatus) {
    $where[] = "a.status = ?";
    $params[] = $filterStatus;
}
if ($filterSearch) {
    $where[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_id LIKE ?)";
    $s = "%$filterSearch%";
    $params[] = $s;
    $params[] = $s;
    $params[] = $s;
}

$where[] = "(u.id IS NULL OR u.role != 'admin')";
$attQuery = $pdo->prepare("
    SELECT a.*, e.first_name, e.last_name, e.employee_id as emp_code, e.department_id, d.name as department_name,
           TIMEDIFF(a.clock_out, a.clock_in) as working_hours
    FROM attendance a
    JOIN employees e ON a.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN users u ON e.user_id = u.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY e.last_name ASC
");
$attQuery->execute($params);
$records = $attQuery->fetchAll();

$recordedIds = array_column($records, 'employee_id');
$extraWhere = $filterDept ? " AND e.department_id = " . (int) $filterDept : '';
$unrecorded = $pdo->prepare("SELECT e.id, e.first_name, e.last_name, e.employee_id, e.status FROM employees e LEFT JOIN users u ON e.user_id = u.id WHERE (u.id IS NULL OR u.role != 'admin')$extraWhere AND e.id NOT IN (" . ($recordedIds ? implode(',', $recordedIds) : '0') . ") ORDER BY e.last_name");
$unrecorded->execute();
$unrecordedEmps = $unrecorded->fetchAll();

// History
$hView = $_GET['h_view'] ?? $_SESSION['history_view'] ?? '';
if ($hView)
    $_SESSION['history_view'] = $hView;
$historyRows = [];

// History pagination
$histPerPage = 20;
$histCurrentPage = max(1, (int)($_GET['h_page'] ?? 1));
$histOffset = ($histCurrentPage - 1) * $histPerPage;

if ($hView === 'date') {
    $hDateFrom = $_GET['h_date_from'] ?? $_SESSION['history_date_from'] ?? '';
    $hDateTo = $_GET['h_date_to'] ?? $_SESSION['history_date_to'] ?? '';
    if ($hDateFrom || $hDateTo) {
        $_SESSION['history_date_from'] = $hDateFrom;
        $_SESSION['history_date_to'] = $hDateTo;
        $w = [];
        $p = [];
        if ($hDateFrom) {
            $w[] = "a.date >= ?";
            $p[] = $hDateFrom;
        }
        if ($hDateTo) {
            $w[] = "a.date <= ?";
            $p[] = $hDateTo;
        }
        $whereClause = implode(' AND ', $w);
        // Count total
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance a JOIN employees e ON a.employee_id = e.id LEFT JOIN users u ON e.user_id = u.id WHERE $whereClause AND (u.id IS NULL OR u.role != 'admin')");
        $countStmt->execute($p);
        $histTotalCount = $countStmt->fetchColumn();
        // Fetch with pagination
        $stmt = $pdo->prepare("SELECT a.*, e.first_name, e.last_name, e.employee_id as emp_code, d.name as department_name, TIMEDIFF(a.clock_out, a.clock_in) as wh FROM attendance a JOIN employees e ON a.employee_id = e.id LEFT JOIN departments d ON e.department_id = d.id LEFT JOIN users u ON e.user_id = u.id WHERE $whereClause AND (u.id IS NULL OR u.role != 'admin') ORDER BY a.date DESC, e.last_name LIMIT ? OFFSET ?");
        $histParams = array_merge($p, [$histPerPage, $histOffset]);
        $stmt->execute($histParams);
        $historyRows = $stmt->fetchAll();
    }
} elseif ($hView === 'emp') {
    if (isset($_GET['h_emp'])) {
        $_SESSION['history_emp'] = $_GET['h_emp'];
        $_SESSION['history_date_from'] = $_GET['h_date_from'] ?? '';
        $_SESSION['history_date_to'] = $_GET['h_date_to'] ?? '';
    }
    $historyEmp = $_SESSION['history_emp'] ?? [];
    $hDateFrom = $_SESSION['history_date_from'] ?? '';
    $hDateTo = $_SESSION['history_date_to'] ?? '';
    if (!empty($historyEmp)) {
        $w = ["a.employee_id IN (" . implode(',', array_fill(0, count($historyEmp), '?')) . ")"];
        $p = array_map('intval', $historyEmp);
        if ($hDateFrom) {
            $w[] = "a.date >= ?";
            $p[] = $hDateFrom;
        }
        if ($hDateTo) {
            $w[] = "a.date <= ?";
            $p[] = $hDateTo;
        }
        $whereClause = implode(' AND ', $w);
        // Count total
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance a JOIN employees e ON a.employee_id = e.id LEFT JOIN users u ON e.user_id = u.id WHERE $whereClause AND (u.id IS NULL OR u.role != 'admin')");
        $countStmt->execute($p);
        $histTotalCount = $countStmt->fetchColumn();
        // Fetch with pagination
        $stmt = $pdo->prepare("SELECT a.*, e.first_name, e.last_name, e.employee_id as emp_code, d.name as department_name, TIMEDIFF(a.clock_out, a.clock_in) as wh FROM attendance a JOIN employees e ON a.employee_id = e.id LEFT JOIN departments d ON e.department_id = d.id LEFT JOIN users u ON e.user_id = u.id WHERE $whereClause AND (u.id IS NULL OR u.role != 'admin') ORDER BY a.date DESC, e.last_name LIMIT ? OFFSET ?");
        $histParams = array_merge($p, [$histPerPage, $histOffset]);
        $stmt->execute($histParams);
        $historyRows = $stmt->fetchAll();
    }
}

$histPagination = paginate($histCurrentPage, $histTotalCount ?? 0, $histPerPage, $_SERVER['REQUEST_URI'], 'h_page');
$histPagination['base_url'] = preg_replace('/[?&]h_page=\d+/', '', $histPagination['base_url']);

// Export CSV
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="attendance_' . $filterDate . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Employee ID', 'Name', 'Department', 'Clock In', 'Clock Out', 'Status', 'Working Hours', 'Notes']);
    foreach ($records as $r) {
        fputcsv($out, [$r['emp_code'], $r['first_name'] . ' ' . $r['last_name'], $r['department_name'] ?? 'N/A', $r['clock_in'] ?? '', $r['clock_out'] ?? '', $r['status'], $r['working_hours'] ?? '', $r['notes'] ?? '']);
    }
    fclose($out);
    exit;
}
?>
<script>
    function confirmDeleteDepartment(id) {
        Swal.fire({ title: 'Delete?', text: 'This will remove all employees from this department.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Yes, delete' }).then(r => { if (r.isConfirmed) document.getElementById('del-dept-' + id).submit() });
    }
    function confirmDeletePayroll(id) {
        Swal.fire({ title: 'Delete draft?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Delete' }).then(r => { if (r.isConfirmed) document.getElementById('del-pay-' + id).submit() });
    }
</script>

<div class="max-w-7xl mx-auto space-y-8">
    <div class="flex justify-between items-end">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface flex items-center gap-2"><img src="<?= BASE_URL ?>/public/emojis/Title%20emojis/attendance.png" class="w-8 h-8" alt=""> Attendance Management</h2>
            <p class="text-text-body font-body-md">Track daily employee attendance records.</p>
        </div>
    </div>

    <?php if ($msg): ?>
        <script>Swal.fire({ icon: 'success', title: '<?= h($msg) ?>', timer: 1500, showConfirmButton: false })</script>
    <?php endif; ?>
    <?php if ($error): ?>
        <script>Swal.fire({ icon: 'error', title: '<?= h($error) ?>' })</script><?php endif; ?>

    <!-- Summary Cards -->
    <?php
    $presentCount = $presentVal->fetchColumn();
    $absentCount = $absentVal->fetchColumn();
    $lateCount = $lateVal->fetchColumn();
    $halfCount = $halfVal->fetchColumn();
    ?>
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
        <div class="stats-card bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center">
                    <img src="<?= BASE_URL ?>/public/emojis/attendance/active.png" class="w-6 h-6" alt="">
                </div>
                <span class="text-label-sm text-primary font-bold bg-primary-container/20 px-2 py-0.5 rounded"><?= $totalActiveVal ?></span>
            </div>
            <div>
                <p class="text-label-md text-secondary uppercase tracking-wider font-bold">Total Active</p>
                <h3 class="font-headline-lg text-headline-lg mt-1"><?= $totalActiveVal ?></h3>
            </div>
        </div>
        <div class="stats-card bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center">
                    <img src="<?= BASE_URL ?>/public/emojis/attendance/present.png" class="w-6 h-6" alt="">
                </div>
                <span class="text-label-sm text-green-600 font-bold bg-green-100 px-2 py-0.5 rounded"><?= $totalActiveVal ? round($presentCount / $totalActiveVal * 100) : 0 ?>%</span>
            </div>
            <div>
                <p class="text-label-md text-secondary uppercase tracking-wider font-bold">Present Today</p>
                <h3 class="font-headline-lg text-headline-lg mt-1"><?= $presentCount ?></h3>
            </div>
        </div>
        <div class="stats-card bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center">
                    <img src="<?= BASE_URL ?>/public/emojis/attendance/absent.png" class="w-6 h-6" alt="">
                </div>
            </div>
            <div>
                <p class="text-label-md text-secondary uppercase tracking-wider font-bold">Absent Today</p>
                <h3 class="font-headline-lg text-headline-lg mt-1"><?= $absentCount ?></h3>
            </div>
        </div>
        <div class="stats-card bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center">
                    <img src="<?= BASE_URL ?>/public/emojis/attendance/late.png" class="w-6 h-6" alt="">
                </div>
            </div>
            <div>
                <p class="text-label-md text-secondary uppercase tracking-wider font-bold">Late Today</p>
                <h3 class="font-headline-lg text-headline-lg mt-1"><?= $lateCount ?></h3>
            </div>
        </div>
        <div class="stats-card bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center">
                    <img src="<?= BASE_URL ?>/public/emojis/attendance/on-leave.png" class="w-6 h-6" alt="">
                </div>
            </div>
            <div>
                <p class="text-label-md text-secondary uppercase tracking-wider font-bold">On Leave</p>
                <h3 class="font-headline-lg text-headline-lg mt-1"><?= $onLeaveVal ?></h3>
            </div>
        </div>
    </div>

    <!-- Attendance Record -->
    <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle overflow-hidden">
        <div class="p-5 border-b border-border-subtle flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-xl text-primary">edit_calendar</span>
                <h3 class="font-headline-md text-headline-md">Attendance Record —
                    <?= h(date('F d, Y', strtotime($filterDate))) ?>
                </h3>
            </div>
            <div class="flex gap-2">
                <a href="?export=1&date=<?= h($filterDate) ?>&department_id=<?= h($filterDept) ?>&status=<?= h($filterStatus) ?>&search=<?= h($filterSearch) ?>"
                    class="h-10 px-4 border border-border-subtle rounded-lg text-sm text-secondary flex items-center gap-1.5 hover:bg-surface-muted transition-colors">
                    <span class="material-symbols-outlined text-lg">download</span> CSV</a>
            </div>
        </div>

        <div class="px-5 py-4 bg-surface-muted/30 border-b border-border-subtle">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <div class="space-y-1">
                    <label class="text-[11px] text-secondary font-semibold uppercase tracking-wider">Date</label>
                    <input type="date" name="date" value="<?= h($filterDate) ?>"
                        class="h-9 px-3 bg-surface-container-lowest border border-border-subtle rounded-lg text-sm focus:outline-none focus:border-primary">
                </div>
                <div class="space-y-1">
                    <label class="text-[11px] text-secondary font-semibold uppercase tracking-wider">Department</label>
                    <select name="department_id"
                        class="h-9 px-3 bg-surface-container-lowest border border-border-subtle rounded-lg text-sm focus:outline-none focus:border-primary">
                        <option value="">All Departments</option>
                        <?php foreach ($deptList as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= $filterDept == $d['id'] ? 'selected' : '' ?>><?= h($d['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-[11px] text-secondary font-semibold uppercase tracking-wider">Status</label>
                    <select name="status" class="h-9 px-3 bg-surface-container-lowest border border-border-subtle rounded-lg text-sm focus:outline-none focus:border-primary">
                        <option value="">All</option>
                        <option value="present" <?= $filterStatus === 'present' ? 'selected' : '' ?>>Present</option>
                        <option value="late" <?= $filterStatus === 'late' ? 'selected' : '' ?>>Late</option>
                        <option value="absent" <?= $filterStatus === 'absent' ? 'selected' : '' ?>>Absent</option>
                        <option value="half_day" <?= $filterStatus === 'half_day' ? 'selected' : '' ?>>Half Day</option>
                    </select>
                </div>
                <div class="space-y-1 flex-1 min-w-[160px]">
                    <label class="text-[11px] text-secondary font-semibold uppercase tracking-wider">Search</label>
                    <input type="text" name="search" value="<?= h($filterSearch) ?>" placeholder="Name or ID..."
                        class="h-9 px-3 bg-surface-container-lowest border border-border-subtle rounded-lg text-sm w-full focus:outline-none focus:border-primary">
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="h-9 px-4 bg-primary-container text-white font-bold rounded-lg text-sm hover:brightness-95 transition-all flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-lg">search</span> Filter</button>
                    <a href="?reset=1"
                        class="h-9 px-4 border border-border-subtle rounded-lg text-sm text-secondary flex items-center gap-1.5 hover:bg-surface-container-lowest transition-colors">
                        <span class="material-symbols-outlined text-lg">refresh</span> Reset</a>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-surface-muted border-b border-border-subtle">
                            <th class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">Employee</th>
                            <th class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">Department</th>
                            <th class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">Clock In</th>
                            <th class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">Clock Out</th>
                            <th class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">Hours</th>
                            <th class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">Late</th>
                            <th class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">Status</th>
                            <th class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records) && empty($unrecordedEmps)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-16 text-center">
                                    <span class="material-symbols-outlined text-4xl text-border-subtle mb-2">calendar_month</span>
                                    <p class="text-body-sm text-secondary">No attendance records for this date.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $r):
                                $hw = $r['hours_worked'] ?? null;
                                $ml = $r['minutes_late'] ?? 0;
                            ?>
                                <tr class="attendance-row hover:bg-surface-muted/50 transition-colors border-b border-border-subtle cursor-pointer"
                                    data-emp-id="<?= $r['employee_id'] ?>"
                                    data-name="<?= h($r['first_name'] . ' ' . $r['last_name']) ?>"
                                    data-clock-in="<?= h($r['clock_in'] ?? '') ?>"
                                    data-clock-out="<?= h($r['clock_out'] ?? '') ?>"
                                    data-status="<?= h($r['status']) ?>"
                                    data-minutes-late="<?= $ml ?>"
                                    data-notes="<?= h($r['notes'] ?? '') ?>"
                                    onclick="openEditModal(this)">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-full bg-primary-container flex items-center justify-center text-xs font-bold text-on-primary-container">
                                                <?= strtoupper(substr($r['first_name'], 0, 1) . substr($r['last_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-body-sm text-on-surface"><?= h($r['first_name'] . ' ' . $r['last_name']) ?></p>
                                                <p class="text-xs text-secondary"><?= h($r['emp_code']) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-body-sm text-secondary"><?= h($r['department_name'] ?? 'N/A') ?></td>
                                    <td class="px-4 py-3 text-body-sm font-mono"><?= h($r['clock_in'] ?? '—') ?></td>
                                    <td class="px-4 py-3 text-body-sm font-mono"><?= h($r['clock_out'] ?? '—') ?></td>
                                    <td class="px-4 py-3 text-body-sm font-mono text-secondary"><?= $hw !== null ? number_format($hw, 1) : '—' ?></td>
                                    <td class="px-4 py-3 text-body-sm text-secondary"><?= $ml ? $ml . 'm' : '—' ?></td>
                                    <td class="px-4 py-3">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase <?= $r['status'] === 'present' ? 'bg-green-100 text-green-700' : ($r['status'] === 'late' ? 'bg-yellow-100 text-yellow-700' : ($r['status'] === 'half_day' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700')) ?>"><?= ucfirst($r['status']) ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-body-sm text-secondary max-w-[120px] truncate"><?= h($r['notes'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php foreach ($unrecordedEmps as $u): ?>
                                <tr class="attendance-row hover:bg-surface-muted/50 transition-colors border-b border-border-subtle bg-amber-50/30 cursor-pointer"
                                    data-emp-id="<?= $u['id'] ?>"
                                    data-name="<?= h($u['first_name'] . ' ' . $u['last_name']) ?>"
                                    data-clock-in=""
                                    data-clock-out=""
                                    data-status="present"
                                    data-minutes-late="0"
                                    data-notes=""
                                    onclick="openEditModal(this)">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-full bg-amber-100 flex items-center justify-center text-xs font-bold text-amber-700">
                                                <?= strtoupper(substr($u['first_name'], 0, 1) . substr($u['last_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-body-sm text-on-surface"><?= h($u['first_name'] . ' ' . $u['last_name']) ?></p>
                                                <p class="text-xs text-secondary"><?= h($u['employee_id']) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-body-sm text-secondary">—</td>
                                    <td class="px-4 py-3 text-body-sm text-secondary">—</td>
                                    <td class="px-4 py-3 text-body-sm text-secondary">—</td>
                                    <td class="px-4 py-3 text-body-sm text-secondary">—</td>
                                    <td class="px-4 py-3 text-body-sm text-secondary">—</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase <?= $u['status'] === 'active' ? 'bg-green-100 text-green-700' : ($u['status'] === 'on_leave' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-500') ?>"><?= ucfirst(str_replace('_', ' ', $u['status'] ?? 'new')) ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-body-sm text-secondary">—</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
    </div>

    <!-- Edit Attendance Modal -->
    <div id="edit-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40" onclick="closeEditModal(event)">
        <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md mx-4" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between mb-5">
                <h4 class="font-headline-md text-headline-md flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">edit</span> Edit Attendance
                </h4>
                <button onclick="closeEditModal()" class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface-muted transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="att_date" value="<?= h($filterDate) ?>">
                <input type="hidden" name="employees_index" id="edit-emp-id">
                <div class="p-3 bg-surface-muted rounded-lg">
                    <p class="text-xs text-secondary">Employee</p>
                    <p id="edit-emp-name" class="font-semibold text-body-sm text-on-surface mt-0.5"></p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-xs text-secondary font-semibold">Clock In</label>
                        <input type="time" name="clock_in" id="edit-clock-in" onchange="syncModalFields()"
                            class="w-full h-10 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm focus:outline-none focus:border-primary">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-secondary font-semibold">Clock Out</label>
                        <input type="time" name="clock_out" id="edit-clock-out" onchange="syncModalFields()"
                            class="w-full h-10 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm focus:outline-none focus:border-primary">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-xs text-secondary font-semibold">Status</label>
                        <select name="status" id="edit-status" onchange="toggleEditFields(); syncModalFields()"
                            class="w-full h-10 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm focus:outline-none focus:border-primary">
                            <option value="present">Present</option>
                            <option value="late">Late</option>
                            <option value="absent">Absent</option>
                            <option value="half_day">Half Day</option>
                        </select>
                    </div>
                    <div class="space-y-1" id="edit-minutes-group">
                        <label class="text-xs text-secondary font-semibold">Minutes Late</label>
                        <input type="number" name="minutes_late" id="edit-minutes-late" value="0" min="0" max="480" oninput="syncModalFields()"
                            class="w-full h-10 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm focus:outline-none focus:border-primary">
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="text-xs text-secondary font-semibold">Notes</label>
                    <textarea name="notes" id="edit-notes" rows="2" oninput="syncModalFields()"
                        class="w-full px-3 py-2 bg-surface-muted border border-border-subtle rounded-lg text-sm focus:outline-none focus:border-primary resize-none"></textarea>
                </div>
                <!-- Hidden fields for PHP handler -->
                <div id="edit-hidden-fields"></div>
                <div class="flex gap-2 justify-end pt-2">
                    <button type="button" onclick="closeEditModal()"
                        class="h-10 px-5 border border-border-subtle rounded-lg text-sm text-secondary hover:bg-surface-muted transition-colors">Cancel</button>
                    <button type="submit" name="save"
                        class="h-10 px-6 bg-primary text-white font-bold rounded-lg text-sm hover:brightness-90 transition-all shadow-sm flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-lg">save</span> Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Employee Attendance History -->
    <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle overflow-hidden">
        <div class="p-5 border-b border-border-subtle flex items-center justify-between flex-wrap gap-3">
            <h3 class="font-headline-md text-headline-md">Attendance History</h3>
            <div class="flex gap-2">
                <button onclick="openModal('date-modal')"
                    class="h-10 px-5 bg-primary-container text-white font-bold rounded-lg text-sm hover:brightness-95">By
                    Date</button>
                <button onclick="openModal('emp-modal')"
                    class="h-10 px-5 bg-primary-container text-white font-bold rounded-lg text-sm hover:brightness-95">By
                    Employee</button>
            </div>
        </div>
        <div class="p-5">
            <?php if ($hView && empty($historyRows)): ?>
                <p class="text-secondary text-sm">No attendance records found.</p>
            <?php endif; ?>
            <?php if ($historyRows): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-surface-muted border-b border-border-subtle">
                                <?php if ($hView === 'date' || $hView === 'emp'): ?>
                                    <th class="text-left px-4 py-3 text-label-sm uppercase tracking-widest font-bold">Employee
                                    </th>
                                    <th class="text-left px-4 py-3 text-label-sm uppercase tracking-widest font-bold">Department
                                    </th>
                                <?php endif; ?>
                                <th class="text-left px-4 py-3 text-label-sm uppercase tracking-widest font-bold">Date</th>
                                <th class="text-left px-4 py-3 text-label-sm uppercase tracking-widest font-bold">Clock In
                                </th>
                                <th class="text-left px-4 py-3 text-label-sm uppercase tracking-widest font-bold">Clock Out
                                </th>
                                <th class="text-left px-4 py-3 text-label-sm uppercase tracking-widest font-bold">Hours</th>
                                <th class="text-left px-4 py-3 text-label-sm uppercase tracking-widest font-bold">Status
                                </th>
                                <th class="text-left px-4 py-3 text-label-sm uppercase tracking-widest font-bold">Pay</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historyRows as $h): ?>
                                <tr class="border-b border-border-subtle hover:bg-surface-muted">
                                    <?php if ($hView === 'date' || $hView === 'emp'): ?>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-9 h-9 rounded-full bg-primary-container flex items-center justify-center text-xs font-bold">
                                                    <?= strtoupper(substr($h['first_name'], 0, 1) . substr($h['last_name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-body-sm">
                                                        <?= h($h['first_name'] . ' ' . $h['last_name']) ?>
                                                    </p>
                                                    <p class="text-xs text-secondary"><?= h($h['emp_code']) ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-body-sm"><?= h($h['department_name'] ?? 'N/A') ?></td>
                                    <?php endif; ?>
                                    <td class="px-4 py-3"><?= h($h['date']) ?></td>
                                    <td class="px-4 py-3"><?= h($h['clock_in'] ?? '--') ?></td>
                                    <td class="px-4 py-3"><?= h($h['clock_out'] ?? '--') ?></td>
                                    <td class="px-4 py-3 font-mono"><?= h($h['wh'] ?? '--') ?></td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase <?= $h['status'] === 'present' ? 'bg-green-100 text-green-700' : ($h['status'] === 'late' ? 'bg-yellow-100 text-yellow-700' : ($h['status'] === 'half_day' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700')) ?>"><?= ucfirst($h['status']) ?></span>
                                    </td>
                                    <td class="px-4 py-3"><?php if (($h['pay_status'] ?? 'unpaid') === 'paid'): ?><span
                                                class="px-2 py-1 rounded-full text-[10px] font-extrabold uppercase bg-green-100 text-green-700">Paid</span><?php else: ?><span
                                                class="px-2 py-1 rounded-full text-[10px] font-extrabold uppercase bg-gray-100 text-gray-500">Unpaid</span><?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= renderPaginationCompact($histPagination) ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- By Date Modal -->
    <div id="date-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40"
        onclick="closeModal('date-modal', event)">
        <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-sm mx-4" onclick="event.stopPropagation()">
            <h4 class="font-headline-md text-headline-md mb-4">Attendance by Date</h4>
            <form method="GET" class="space-y-4">
                <input type="hidden" name="h_view" value="date">
                <div class="space-y-1">
                    <label class="text-xs text-secondary font-semibold">From</label>
                    <input type="date" name="h_date_from"
                        class="h-10 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm w-full">
                </div>
                <div class="space-y-1">
                    <label class="text-xs text-secondary font-semibold">To</label>
                    <input type="date" name="h_date_to"
                        class="h-10 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm w-full">
                </div>
                <div class="flex gap-2 justify-end">
                    <button type="button" onclick="document.getElementById('date-modal').classList.add('hidden')"
                        class="h-10 px-4 border border-border-subtle rounded-lg text-sm text-secondary">Cancel</button>
                    <button type="submit"
                        class="h-10 px-5 bg-primary-container text-white font-bold rounded-lg text-sm hover:brightness-95">View</button>
                </div>
            </form>
        </div>
    </div>

    <!-- By Employee Modal -->
    <div id="emp-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40"
        onclick="closeModal('emp-modal', event)">
        <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md mx-4" onclick="event.stopPropagation()">
            <h4 class="font-headline-md text-headline-md mb-4">Attendance by Employee</h4>
            <form method="GET" class="space-y-4">
                <input type="hidden" name="h_view" value="emp">
                <div class="space-y-1">
                    <label class="text-xs text-secondary font-semibold">Employees</label>
                    <input type="text" id="emp-search" placeholder="Search employees..."
                        class="h-10 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm w-full mb-1"
                        oninput="filterEmp()">
                    <select name="h_emp[]" id="emp-list" multiple
                        class="h-40 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm w-full">
                        <?php $allEmps = $pdo->query("SELECT e.id, e.first_name, e.last_name, e.employee_id FROM employees e LEFT JOIN users u ON e.user_id = u.id WHERE u.id IS NULL OR u.role != 'admin' ORDER BY e.last_name")->fetchAll();
                        foreach ($allEmps as $e): ?>
                            <option value="<?= $e['id'] ?>">
                                <?= h($e['first_name'] . ' ' . $e['last_name'] . ' (' . $e['employee_id'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-[10px] text-secondary">Ctrl+click to select multiple</p>
                </div>
                <div class="space-y-1">
                    <label class="text-xs text-secondary font-semibold">From</label>
                    <input type="date" name="h_date_from"
                        class="h-10 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm w-full">
                </div>
                <div class="space-y-1">
                    <label class="text-xs text-secondary font-semibold">To</label>
                    <input type="date" name="h_date_to"
                        class="h-10 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm w-full">
                </div>
                <div class="flex gap-2 justify-end">
                    <button type="button" onclick="document.getElementById('emp-modal').classList.add('hidden')"
                        class="h-10 px-4 border border-border-subtle rounded-lg text-sm text-secondary">Cancel</button>
                    <button type="submit"
                        class="h-10 px-5 bg-primary-container text-white font-bold rounded-lg text-sm hover:brightness-95">View</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) { document.getElementById(id).classList.remove('hidden'); document.getElementById(id).classList.add('flex'); }
        function closeModal(id, e) { if (!e || e.target === e.currentTarget) { document.getElementById(id).classList.add('hidden'); document.getElementById(id).classList.remove('flex'); } }
        function filterEmp() {
            var q = document.getElementById('emp-search').value.toLowerCase();
            document.querySelectorAll('#emp-list option').forEach(function (o) {
                o.style.display = o.text.toLowerCase().includes(q) ? '' : 'none';
            });
        }
        function openEditModal(row) {
            document.getElementById('edit-emp-id').value = row.dataset.empId;
            document.getElementById('edit-emp-name').textContent = row.dataset.name;
            document.getElementById('edit-clock-in').value = row.dataset.clockIn;
            document.getElementById('edit-clock-out').value = row.dataset.clockOut;
            document.getElementById('edit-status').value = row.dataset.status;
            document.getElementById('edit-minutes-late').value = row.dataset.minutesLate || 0;
            document.getElementById('edit-notes').value = row.dataset.notes;
            toggleEditFields();
            syncModalFields();
            openModal('edit-modal');
        }
        function closeEditModal() { closeModal('edit-modal'); }
        function toggleEditFields() {
            var status = document.getElementById('edit-status').value;
            document.getElementById('edit-minutes-group').style.display = status === 'late' ? '' : 'none';
            document.getElementById('edit-clock-in').disabled = status === 'absent';
            document.getElementById('edit-clock-out').disabled = status === 'absent';
        }
        function syncModalFields() {
            var id = document.getElementById('edit-emp-id').value;
            var h = document.getElementById('edit-hidden-fields');
            var ci = document.getElementById('edit-clock-in').value;
            var co = document.getElementById('edit-clock-out').value;
            var st = document.getElementById('edit-status').value;
            var ml = document.getElementById('edit-minutes-late').value;
            var nt = document.getElementById('edit-notes').value;
            h.innerHTML =
                '<input type="hidden" name="employees[' + id + '][clock_in]" value="' + ci + '">' +
                '<input type="hidden" name="employees[' + id + '][clock_out]" value="' + co + '">' +
                '<input type="hidden" name="employees[' + id + '][status]" value="' + st + '">' +
                '<input type="hidden" name="employees[' + id + '][minutes_late]" value="' + ml + '">' +
                '<input type="hidden" name="employees[' + id + '][notes]" value="' + nt.replace(/"/g, '&quot;') + '">';
        }
    </script>
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
.cursor-pointer { cursor: pointer; }
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
<style>main{background:linear-gradient(rgba(255,255,255,0.92),rgba(255,255,255,0.92)),url('<?= BASE_URL ?>/public/background/dashboard.jpeg') center/cover no-repeat fixed;min-height:100vh}</style>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<?php
requireLogin();
requireAdmin();
$pageTitle = 'Attendance Management | HRMS Core';
$currentPage = 'attendance';
require_once __DIR__ . '/../../includes/header.php';

$msg = $error = '';
$today = date('Y-m-d');

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save'])) {
        $date = $_POST['att_date'] ?? $today;
        $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, clock_in, clock_out, status, notes) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE clock_in=VALUES(clock_in), clock_out=VALUES(clock_out), status=VALUES(status), notes=VALUES(notes)");
        foreach ($_POST['employees'] as $empId => $data) {
            if (!empty($data['status'])) {
                $stmt->execute([$empId, $date, $data['clock_in'] ?: null, $data['clock_out'] ?: null, $data['status'], $data['notes'] ?? null]);
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
if ($resetFilters) { $filterDate = $today; $filterDept = ''; $filterStatus = ''; $filterSearch = ''; }

$deptList = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();

// Summary
$totalActive = $pdo->query("SELECT COUNT(*) FROM employees WHERE status='active'")->fetchColumn();
$presentToday = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE date=? AND status='present'");
$presentToday->execute([$today]);
$absentToday = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE date=? AND status='absent'");
$absentToday->execute([$today]);
$lateToday = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE date=? AND status='late'");
$lateToday->execute([$today]);
$halfToday = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE date=? AND status='half_day'");
$halfToday->execute([$today]);
$onLeave = $pdo->query("SELECT COUNT(*) FROM employees WHERE status='on_leave'")->fetchColumn();

// Build query
$where = ["a.date = ?"];
$params = [$filterDate];
if ($filterDept) { $where[] = "e.department_id = ?"; $params[] = $filterDept; }
if ($filterStatus) { $where[] = "a.status = ?"; $params[] = $filterStatus; }
if ($filterSearch) { $where[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_id LIKE ?)"; $s = "%$filterSearch%"; $params[] = $s; $params[] = $s; $params[] = $s; }

$attQuery = $pdo->prepare("
    SELECT a.*, e.first_name, e.last_name, e.employee_id as emp_code, e.department_id, d.name as department_name,
           TIMEDIFF(a.clock_out, a.clock_in) as working_hours
    FROM attendance a
    JOIN employees e ON a.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY e.last_name ASC
");
$attQuery->execute($params);
$records = $attQuery->fetchAll();

$recordedIds = array_column($records, 'employee_id');
$extraWhere = $filterDept ? " AND department_id = " . (int)$filterDept : '';
$unrecorded = $pdo->prepare("SELECT id, first_name, last_name, employee_id FROM employees WHERE status='active'$extraWhere AND id NOT IN (" . ($recordedIds ? implode(',', $recordedIds) : '0') . ") ORDER BY last_name");
$unrecorded->execute();
$unrecordedEmps = $unrecorded->fetchAll();

// History
$hView = $_GET['h_view'] ?? $_SESSION['history_view'] ?? '';
if ($hView) $_SESSION['history_view'] = $hView;
$historyRows = [];

if ($hView === 'date') {
    $hDateFrom = $_GET['h_date_from'] ?? $_SESSION['history_date_from'] ?? '';
    $hDateTo = $_GET['h_date_to'] ?? $_SESSION['history_date_to'] ?? '';
    if ($hDateFrom || $hDateTo) {
        $_SESSION['history_date_from'] = $hDateFrom;
        $_SESSION['history_date_to'] = $hDateTo;
        $w = []; $p = [];
        if ($hDateFrom) { $w[] = "a.date >= ?"; $p[] = $hDateFrom; }
        if ($hDateTo) { $w[] = "a.date <= ?"; $p[] = $hDateTo; }
        $stmt = $pdo->prepare("SELECT a.*, e.first_name, e.last_name, e.employee_id as emp_code, d.name as department_name, TIMEDIFF(a.clock_out, a.clock_in) as wh FROM attendance a JOIN employees e ON a.employee_id = e.id LEFT JOIN departments d ON e.department_id = d.id WHERE " . implode(' AND ', $w) . " ORDER BY a.date DESC, e.last_name");
        $stmt->execute($p);
        $historyRows = $stmt->fetchAll();
    }
} elseif ($hView === 'emp') {
    if (isset($_GET['h_emp'])) {
        $_SESSION['history_emp'] = (int)$_GET['h_emp'];
        $_SESSION['history_days'] = $_GET['h_days'] ?? '';
        $_SESSION['history_date'] = $_GET['h_date'] ?? '';
    }
    $historyEmp = $_SESSION['history_emp'] ?? null;
    $historyDays = $_SESSION['history_days'] ?? '';
    $historyDate = $_SESSION['history_date'] ?? '';
    if ($historyEmp) {
        if ($historyDate && $historyDays !== '') {
            $start = date('Y-m-d', strtotime("$historyDate -{$historyDays} days"));
            $stmt = $pdo->prepare("SELECT a.*, e.first_name, e.last_name, e.employee_id as emp_code, TIMEDIFF(a.clock_out, a.clock_in) as wh FROM attendance a JOIN employees e ON a.employee_id = e.id WHERE a.employee_id = ? AND a.date BETWEEN ? AND ? ORDER BY a.date DESC");
            $stmt->execute([$historyEmp, $start, $historyDate]);
        } elseif ($historyDate) {
            $stmt = $pdo->prepare("SELECT a.*, e.first_name, e.last_name, e.employee_id as emp_code, TIMEDIFF(a.clock_out, a.clock_in) as wh FROM attendance a JOIN employees e ON a.employee_id = e.id WHERE a.employee_id = ? AND a.date = ? ORDER BY a.date DESC");
            $stmt->execute([$historyEmp, $historyDate]);
        } else {
            $days = $historyDays !== '' ? (int)$historyDays : 30;
            $stmt = $pdo->prepare("SELECT a.*, e.first_name, e.last_name, e.employee_id as emp_code, TIMEDIFF(a.clock_out, a.clock_in) as wh FROM attendance a JOIN employees e ON a.employee_id = e.id WHERE a.employee_id = ? ORDER BY a.date DESC LIMIT ?");
            $stmt->execute([$historyEmp, $days]);
        }
        $historyRows = $stmt->fetchAll();
    }
}

// Export CSV
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="attendance_'.$filterDate.'.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Employee ID','Name','Department','Clock In','Clock Out','Status','Working Hours','Notes']);
    foreach ($records as $r) {
        fputcsv($out, [$r['emp_code'], $r['first_name'].' '.$r['last_name'], $r['department_name'] ?? 'N/A', $r['clock_in'] ?? '', $r['clock_out'] ?? '', $r['status'], $r['working_hours'] ?? '', $r['notes'] ?? '']);
    }
    fclose($out);
    exit;
}
?>
<script>
function confirmDeleteDepartment(id) {
    Swal.fire({ title:'Delete?', text:'This will also delete all positions.', icon:'warning', showCancelButton:true, confirmButtonColor:'#d33', confirmButtonText:'Yes, delete' }).then(r=>{ if(r.isConfirmed) document.getElementById('del-dept-'+id).submit() });
}
function confirmDeletePayroll(id) {
    Swal.fire({ title:'Delete draft?', icon:'warning', showCancelButton:true, confirmButtonColor:'#d33', confirmButtonText:'Delete' }).then(r=>{ if(r.isConfirmed) document.getElementById('del-pay-'+id).submit() });
}
</script>

<div class="space-y-6">
    <div class="flex justify-between items-end">
        <div><h2 class="font-headline-lg text-headline-lg text-on-surface">Attendance Management</h2><p class="text-text-body font-body-md">Track daily employee attendance records.</p></div>
    </div>

    <?php if ($msg): ?><script>Swal.fire({icon:'success',title:'<?= h($msg) ?>',timer:1500,showConfirmButton:false})</script><?php endif; ?>
    <?php if ($error): ?><script>Swal.fire({icon:'error',title:'<?= h($error) ?>'})</script><?php endif; ?>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-5 text-center">
            <div class="text-3xl font-bold text-primary"><?= $totalActive ?></div>
            <div class="text-label-sm text-secondary mt-1">Total Active</div>
        </div>
        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-5 text-center">
            <div class="text-3xl font-bold text-green-600"><?= $presentToday->fetchColumn() ?></div>
            <div class="text-label-sm text-secondary mt-1">Present Today</div>
        </div>
        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-5 text-center">
            <div class="text-3xl font-bold text-red-600"><?= $absentToday->fetchColumn() ?></div>
            <div class="text-label-sm text-secondary mt-1">Absent Today</div>
        </div>
        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-5 text-center">
            <div class="text-3xl font-bold text-yellow-600"><?= $lateToday->fetchColumn() ?></div>
            <div class="text-label-sm text-secondary mt-1">Late Today</div>
        </div>
        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-5 text-center">
            <div class="text-3xl font-bold text-blue-600"><?= $onLeave ?></div>
            <div class="text-label-sm text-secondary mt-1">On Leave</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-5">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="space-y-1">
                <label class="text-xs text-secondary font-semibold">Date</label>
                <input type="date" name="date" value="<?= h($filterDate) ?>" class="h-10 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm">
            </div>
            <div class="space-y-1">
                <label class="text-xs text-secondary font-semibold">Department</label>
                <select name="department_id" class="h-10 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm">
                    <option value="">All Departments</option>
                    <?php foreach ($deptList as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $filterDept == $d['id'] ? 'selected' : '' ?>><?= h($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="space-y-1">
                <label class="text-xs text-secondary font-semibold">Status</label>
                <select name="status" class="h-10 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm">
                    <option value="">All Status</option>
                    <option value="present" <?= $filterStatus==='present'?'selected':'' ?>>Present</option>
                    <option value="late" <?= $filterStatus==='late'?'selected':'' ?>>Late</option>
                    <option value="absent" <?= $filterStatus==='absent'?'selected':'' ?>>Absent</option>
                    <option value="half_day" <?= $filterStatus==='half_day'?'selected':'' ?>>Half Day</option>
                </select>
            </div>
            <div class="space-y-1 flex-1 min-w-[160px]">
                <label class="text-xs text-secondary font-semibold">Search Employee</label>
                <input type="text" name="search" value="<?= h($filterSearch) ?>" placeholder="Name or ID..." class="h-10 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm w-full">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="h-10 px-5 bg-primary-container text-white font-bold rounded-lg text-sm hover:brightness-95">Filter</button>
                <a href="?reset=1" class="h-10 px-5 border border-border-subtle rounded-lg text-sm text-secondary flex items-center hover:bg-surface-muted">Reset</a>
            </div>
        </form>
    </div>

    <!-- Mark Attendance Form -->
    <form method="POST" id="attendance-form">
        <input type="hidden" name="att_date" value="<?= h($filterDate) ?>">
        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle overflow-hidden">
            <div class="p-5 border-b border-border-subtle flex items-center justify-between flex-wrap gap-3">
                <h3 class="font-headline-md text-headline-md">Attendance — <?= h(date('F d, Y', strtotime($filterDate))) ?></h3>
                <div class="flex gap-2">
                    <a href="?export=1&date=<?= h($filterDate) ?>&department_id=<?= h($filterDept) ?>&status=<?= h($filterStatus) ?>&search=<?= h($filterSearch) ?>" class="h-10 px-4 border border-border-subtle rounded-lg text-sm text-secondary flex items-center hover:bg-surface-muted">📥 CSV</a>
                    <button type="submit" name="save" class="h-10 px-6 bg-primary-container text-white font-bold rounded-lg text-sm hover:brightness-95">Save</button>
                </div>
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
                            <th class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">Status</th>
                            <th class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records) && empty($unrecordedEmps)): ?>
                        <tr><td colspan="7" class="px-6 py-12 text-center text-secondary">No attendance records for this date.</td></tr>
                        <?php else: ?>
                        <?php foreach ($records as $r): ?>
                        <tr class="hover:bg-surface-muted transition-colors border-b border-border-subtle">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-primary-container flex items-center justify-center text-xs font-bold"><?= strtoupper(substr($r['first_name'],0,1).substr($r['last_name'],0,1)) ?></div>
                                    <div><p class="font-semibold text-body-sm"><?= h($r['first_name'].' '.$r['last_name']) ?></p><p class="text-xs text-secondary"><?= h($r['emp_code']) ?></p></div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-body-sm"><?= h($r['department_name'] ?? 'N/A') ?></td>
                            <td class="px-4 py-3"><input name="employees[<?= $r['employee_id'] ?>][clock_in]" value="<?= h($r['clock_in'] ?? '') ?>" type="time" class="h-9 px-2 bg-surface-muted border border-border-subtle rounded-lg text-sm w-28"></td>
                            <td class="px-4 py-3"><input name="employees[<?= $r['employee_id'] ?>][clock_out]" value="<?= h($r['clock_out'] ?? '') ?>" type="time" class="h-9 px-2 bg-surface-muted border border-border-subtle rounded-lg text-sm w-28"></td>
                            <td class="px-4 py-3 text-body-sm font-mono"><?= h($r['working_hours'] ?? '--') ?></td>
                            <td class="px-4 py-3">
                                <select name="employees[<?= $r['employee_id'] ?>][status]" class="h-9 px-2 bg-surface-muted border border-border-subtle rounded-lg text-sm">
                                    <option value="present" <?= $r['status']==='present'?'selected':'' ?>>Present</option>
                                    <option value="late" <?= $r['status']==='late'?'selected':'' ?>>Late</option>
                                    <option value="absent" <?= $r['status']==='absent'?'selected':'' ?>>Absent</option>
                                    <option value="half_day" <?= $r['status']==='half_day'?'selected':'' ?>>Half Day</option>
                                </select>
                            </td>
                            <td class="px-4 py-3"><input name="employees[<?= $r['employee_id'] ?>][notes]" value="<?= h($r['notes'] ?? '') ?>" class="h-9 px-2 bg-surface-muted border border-border-subtle rounded-lg text-sm w-24"></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php foreach ($unrecordedEmps as $u): ?>
                        <tr class="hover:bg-surface-muted transition-colors border-b border-border-subtle bg-surface-muted/30">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-surface-container flex items-center justify-center text-xs font-bold"><?= strtoupper(substr($u['first_name'],0,1).substr($u['last_name'],0,1)) ?></div>
                                    <div><p class="font-semibold text-body-sm"><?= h($u['first_name'].' '.$u['last_name']) ?></p><p class="text-xs text-secondary"><?= h($u['employee_id']) ?></p></div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-body-sm text-secondary">—</td>
                            <td class="px-4 py-3"><input name="employees[<?= $u['id'] ?>][clock_in]" value="08:00" type="time" class="h-9 px-2 bg-surface-muted border border-border-subtle rounded-lg text-sm w-28"></td>
                            <td class="px-4 py-3"><input name="employees[<?= $u['id'] ?>][clock_out]" value="17:00" type="time" class="h-9 px-2 bg-surface-muted border border-border-subtle rounded-lg text-sm w-28"></td>
                            <td class="px-4 py-3 text-body-sm text-secondary">—</td>
                            <td class="px-4 py-3">
                                <select name="employees[<?= $u['id'] ?>][status]" class="h-9 px-2 bg-surface-muted border border-border-subtle rounded-lg text-sm">
                                    <option value="present">Present</option>
                                    <option value="late">Late</option>
                                    <option value="absent">Absent</option>
                                    <option value="half_day">Half Day</option>
                                </select>
                            </td>
                            <td class="px-4 py-3"><input name="employees[<?= $u['id'] ?>][notes]" class="h-9 px-2 bg-surface-muted border border-border-subtle rounded-lg text-sm w-24"></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    <!-- Employee Attendance History -->
    <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle overflow-hidden">
        <div class="p-5 border-b border-border-subtle flex items-center justify-between flex-wrap gap-3">
            <h3 class="font-headline-md text-headline-md">Attendance History</h3>
            <div class="flex gap-2">
                <button onclick="openModal('date-modal')" class="h-10 px-5 bg-primary-container text-white font-bold rounded-lg text-sm hover:brightness-95">By Date</button>
                <button onclick="openModal('emp-modal')" class="h-10 px-5 bg-primary-container text-white font-bold rounded-lg text-sm hover:brightness-95">By Employee</button>
            </div>
        </div>
        <div class="p-5">
            <?php if ($hView && empty($historyRows)): ?>
            <p class="text-secondary text-sm">No attendance records found.</p>
            <?php endif; ?>
            <?php if ($historyRows): ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead><tr class="bg-surface-muted border-b border-border-subtle">
                        <?php if ($hView === 'date'): ?>
                        <th class="text-left px-4 py-3 text-label-sm uppercase tracking-widest font-bold">Employee</th>
                        <th class="text-left px-4 py-3 text-label-sm uppercase tracking-widest font-bold">Department</th>
                        <?php endif; ?>
                        <th class="text-left px-4 py-3 text-label-sm uppercase tracking-widest font-bold">Date</th>
                        <th class="text-left px-4 py-3 text-label-sm uppercase tracking-widest font-bold">Clock In</th>
                        <th class="text-left px-4 py-3 text-label-sm uppercase tracking-widest font-bold">Clock Out</th>
                        <th class="text-left px-4 py-3 text-label-sm uppercase tracking-widest font-bold">Hours</th>
                        <th class="text-left px-4 py-3 text-label-sm uppercase tracking-widest font-bold">Status</th>
                    </tr></thead>
                    <tbody>
                        <?php foreach ($historyRows as $h): ?>
                        <tr class="border-b border-border-subtle hover:bg-surface-muted">
                            <?php if ($hView === 'date'): ?>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-primary-container flex items-center justify-center text-xs font-bold"><?= strtoupper(substr($h['first_name'],0,1).substr($h['last_name'],0,1)) ?></div>
                                    <div><p class="font-semibold text-body-sm"><?= h($h['first_name'].' '.$h['last_name']) ?></p><p class="text-xs text-secondary"><?= h($h['emp_code']) ?></p></div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-body-sm"><?= h($h['department_name'] ?? 'N/A') ?></td>
                            <?php endif; ?>
                            <td class="px-4 py-3"><?= h($h['date']) ?></td>
                            <td class="px-4 py-3"><?= h($h['clock_in'] ?? '--') ?></td>
                            <td class="px-4 py-3"><?= h($h['clock_out'] ?? '--') ?></td>
                            <td class="px-4 py-3 font-mono"><?= h($h['wh'] ?? '--') ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase <?= $h['status']==='present'?'bg-green-100 text-green-700':($h['status']==='late'?'bg-yellow-100 text-yellow-700':($h['status']==='half_day'?'bg-blue-100 text-blue-700':'bg-red-100 text-red-700')) ?>"><?= ucfirst($h['status']) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- By Date Modal -->
    <div id="date-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40" onclick="closeModal('date-modal', event)">
        <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-sm mx-4" onclick="event.stopPropagation()">
            <h4 class="font-headline-md text-headline-md mb-4">Attendance by Date</h4>
            <form method="GET" class="space-y-4">
                <input type="hidden" name="h_view" value="date">
                <div class="space-y-1">
                    <label class="text-xs text-secondary font-semibold">From</label>
                    <input type="date" name="h_date_from" class="h-10 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm w-full">
                </div>
                <div class="space-y-1">
                    <label class="text-xs text-secondary font-semibold">To</label>
                    <input type="date" name="h_date_to" class="h-10 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm w-full">
                </div>
                <div class="flex gap-2 justify-end">
                    <button type="button" onclick="document.getElementById('date-modal').classList.add('hidden')" class="h-10 px-4 border border-border-subtle rounded-lg text-sm text-secondary">Cancel</button>
                    <button type="submit" class="h-10 px-5 bg-primary-container text-white font-bold rounded-lg text-sm hover:brightness-95">View</button>
                </div>
            </form>
        </div>
    </div>

    <!-- By Employee Modal -->
    <div id="emp-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40" onclick="closeModal('emp-modal', event)">
        <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-sm mx-4" onclick="event.stopPropagation()">
            <h4 class="font-headline-md text-headline-md mb-4">Attendance by Employee</h4>
            <form method="GET" class="space-y-4">
                <input type="hidden" name="h_view" value="emp">
                <div class="space-y-1">
                    <label class="text-xs text-secondary font-semibold">Employee</label>
                    <select name="h_emp" required class="h-10 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm w-full">
                        <option value="">Select Employee</option>
                        <?php $allEmps = $pdo->query("SELECT id, first_name, last_name, employee_id FROM employees ORDER BY last_name")->fetchAll();
                        foreach ($allEmps as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= h($e['first_name'].' '.$e['last_name'].' ('.$e['employee_id'].')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-xs text-secondary font-semibold">Date</label>
                    <input type="date" name="h_date" class="h-10 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm w-full">
                </div>
                <div class="space-y-1">
                    <label class="text-xs text-secondary font-semibold">Or Days</label>
                    <select name="h_days" class="h-10 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm w-full">
                        <option value="">—</option>
                        <option value="7">7 days</option>
                        <option value="15">15 days</option>
                        <option value="30" selected>30 days</option>
                        <option value="60">60 days</option>
                        <option value="90">90 days</option>
                    </select>
                </div>
                <div class="flex gap-2 justify-end">
                    <button type="button" onclick="document.getElementById('emp-modal').classList.add('hidden')" class="h-10 px-4 border border-border-subtle rounded-lg text-sm text-secondary">Cancel</button>
                    <button type="submit" class="h-10 px-5 bg-primary-container text-white font-bold rounded-lg text-sm hover:brightness-95">View</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openModal(id) { document.getElementById(id).classList.remove('hidden'); document.getElementById(id).classList.add('flex'); }
    function closeModal(id, e) { if (!e || e.target === e.currentTarget) { document.getElementById(id).classList.add('hidden'); document.getElementById(id).classList.remove('flex'); } }
    </script>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

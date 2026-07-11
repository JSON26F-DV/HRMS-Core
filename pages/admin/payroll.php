<?php
requireLogin();
requireHrOrAdmin();
$pageTitle = 'Payroll Management | HRMS Core';
$currentPage = 'payroll';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/pagination.php';

$msg = $error = '';

$perPage = 15;
$currentPageNum = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($currentPageNum - 1) * $perPage;

$totalCount = $pdo->query("SELECT COUNT(*) FROM payroll")->fetchColumn();

$pagination = paginate($currentPageNum, $totalCount, $perPage, $_SERVER['REQUEST_URI'], 'page');
$pagination['base_url'] = preg_replace('/[?&]page=\d+/', '', $pagination['base_url']);

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Generate payroll from unpaid attendance
    if (isset($_POST['generate'])) {
        $start = $_POST['period_start'] ?? date('Y-m-01');
        $end = $_POST['period_end'] ?? date('Y-m-t');
        $empIds = $_POST['employee_ids'] ?? [];

        if (empty($empIds)) {
            $error = 'No employees selected.';
        } else {
            $inserted = 0;
            foreach ($empIds as $eid) {
                $atts = $pdo->prepare("SELECT a.*, e.daily_rate FROM attendance a JOIN employees e ON a.employee_id = e.id WHERE a.employee_id = ? AND a.payroll_id IS NULL AND a.date BETWEEN ? AND ?");
                $atts->execute([$eid, $start, $end]);
                $rows = $atts->fetchAll();
                if (empty($rows))
                    continue;

                $gross = 0;
                $totalHrs = 0;
                $days = 0;
                $totalLate = 0;
                $attIds = [];
                $statusCounts = [];
                foreach ($rows as $r) {
                    $rate = $r['daily_rate'] ?? 0;
                    $hours = (float) $r['hours_worked'];
                    $minutesLate = (int) ($r['minutes_late'] ?? 0);
                    $totalHrs += $hours;
                    $totalLate += $minutesLate;
                    $dayPay = calcDayPay($r['status'], $rate, $minutesLate);
                    $gross += $dayPay;
                    if ($dayPay > 0)
                        $days++;
                    $attIds[] = $r['id'];
                    $s = $r['status'];
                    $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
                }
                $attSummary = implode(', ', array_map(function ($k, $v) {
                    return "$k: $v";
                }, array_keys($statusCounts), $statusCounts));
                $deductions = round($gross * 0.1, 2);
                $net = $gross - $deductions;

                $stmt = $pdo->prepare("INSERT INTO payroll (employee_id, period_start, period_end, gross_pay, deductions, net_pay, days_worked, total_hours, total_late_minutes, attendance_summary, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')");
                $stmt->execute([$eid, $start, $end, $gross, $deductions, $net, $days, $totalHrs, $totalLate, $attSummary]);
                $payId = $pdo->lastInsertId();

                $upd = $pdo->prepare("UPDATE attendance SET pay_status = 'paid', payroll_id = ? WHERE id = ?");
                foreach ($attIds as $aid) {
                    $upd->execute([$payId, $aid]);
                }
                $inserted++;
            }
            logAudit('generate', 'payroll', null, "Generated $inserted payroll records for $start → $end");
            $msg = "Generated $inserted payroll records from unpaid attendance.";
        }
    }
    // Approve
    if (isset($_POST['approve'])) {
        $pdo->prepare("UPDATE payroll SET status = 'approved' WHERE id = ?")->execute([(int) $_POST['id']]);
        logAudit('approve', 'payroll', (int) $_POST['id'], 'Payroll approved');
        $msg = 'Payroll approved.';
    }
    // Mark paid
    if (isset($_POST['pay'])) {
        $pdo->prepare("UPDATE payroll SET status = 'paid', paid_at = NOW() WHERE id = ?")->execute([(int) $_POST['id']]);
        logAudit('pay', 'payroll', (int) $_POST['id'], 'Payroll marked as paid');
        $msg = 'Payroll marked as paid.';
    }
    // Delete draft
    if (isset($_POST['delete'])) {
        $id = (int) $_POST['id'];
        $pdo->prepare("UPDATE attendance SET pay_status = 'unpaid', payroll_id = NULL WHERE payroll_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM payroll WHERE id = ? AND status = 'draft'")->execute([$id]);
        $msg = 'Draft deleted, attendance reverted to unpaid.';
    }
}

// Print
if (isset($_GET['print'])) {
    $stmt = $pdo->prepare("SELECT p.*, e.first_name, e.last_name, e.employee_id, e.department_id, d.name as dept_name FROM payroll p JOIN employees e ON p.employee_id = e.id LEFT JOIN departments d ON e.department_id = d.id WHERE p.id = ?");
    $stmt->execute([(int) $_GET['print']]);
    $p = $stmt->fetch();
    if (!$p) {
        header('Location: ' . BASE_URL . '/admin/payroll');
        exit;
    }
    ?><!DOCTYPE html>
    <html>

    <head>
        <meta charset="utf-8">
        <title>Payslip</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                padding: 40px;
                color: #333
            }

            .header {
                text-align: center;
                border-bottom: 2px solid #006d43;
                padding-bottom: 20px;
                margin-bottom: 30px
            }

            .header h1 {
                color: #006d43;
                margin: 0 0 5px
            }

            .grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
                margin-bottom: 30px
            }

            .field {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #eee
            }

            .field strong {
                color: #555
            }

            .summary {
                display: flex;
                justify-content: space-around;
                margin-bottom: 20px;
                padding: 15px;
                background: #f8fafc;
                border-radius: 8px
            }

            .summary div {
                text-align: center
            }

            .summary div span {
                display: block;
                font-size: 12px;
                color: #888;
                margin-bottom: 4px
            }

            .summary div strong {
                font-size: 18px
            }

            .total {
                background: #f0fdf4;
                padding: 15px;
                border-radius: 8px;
                font-size: 18px;
                text-align: center;
                margin-top: 20px
            }

            .total strong {
                color: #006d43;
                font-size: 24px
            }

            .footer {
                text-align: center;
                margin-top: 40px;
                color: #999;
                font-size: 12px;
                border-top: 1px solid #eee;
                padding-top: 15px
            }

            @media print {
                body {
                    padding: 20px
                }

                .no-print {
                    display: none
                }
            }
        </style>
    </head>

    <body>
        <div class="header">
            <h1>HRMS Core</h1>
            <p>Payslip</p>
        </div>
        <div class="grid">
            <div>
                <div class="field"><strong>Employee:</strong>
                    <span><?= h($p['first_name'] . ' ' . $p['last_name']) ?></span>
                </div>
                <div class="field"><strong>Employee ID:</strong> <span><?= h($p['employee_id']) ?></span></div>
                <div class="field"><strong>Department:</strong> <span><?= h($p['dept_name'] ?? 'N/A') ?></span></div>
            </div>
            <div>
                <div class="field"><strong>Period:</strong> <span><?= h($p['period_start']) ?> →
                        <?= h($p['period_end']) ?></span></div>
                <div class="field"><strong>Status:</strong> <span><?= ucfirst($p['status']) ?></span></div>
                <div class="field"><strong>Date:</strong>
                    <span><?= h($p['paid_at'] ? date('Y-m-d', strtotime($p['paid_at'])) : date('Y-m-d')) ?></span>
                </div>
            </div>
        </div>
        <div class="summary">
            <div><span>Days Worked</span><strong><?= $p['days_worked'] ?: 0 ?></strong></div>
            <div><span>Total Hours</span><strong><?= number_format($p['total_hours'] ?: 0, 1) ?></strong></div>
            <div><span>Gross Pay</span><strong>₱<?= number_format($p['gross_pay'], 2) ?></strong></div>
        </div>
        <div class="field"><strong>Gross Pay:</strong> <span>₱<?= number_format($p['gross_pay'], 2) ?></span></div>
        <div class="field"><strong>Deductions (10%):</strong> <span>₱<?= number_format($p['deductions'], 2) ?></span></div>
        <div class="total"><strong>Net Pay: ₱<?= number_format($p['net_pay'], 2) ?></strong></div>
        <div class="footer">
            <p>This is a computer-generated document.</p>
        </div>
        <div class="no-print" style="text-align:center;margin-top:20px"><button onclick="window.print()"
                style="padding:10px 30px;background:#006d43;color:#fff;border:none;border-radius:6px;cursor:pointer">Print</button>
            <a href="<?= BASE_URL ?>/admin/payroll"
                style="padding:10px 30px;background:#eee;color:#333;border:none;border-radius:6px;text-decoration:none">Back</a>
        </div>
    </body>

    </html>
    <?php exit;
}

function calcDayPay($status, $rate, $minutes_late = 0)
{
    if ($status === 'absent')
        return 0;
    if ($status === 'half_day')
        return round($rate / 2, 2);
    if ($status === 'late') {
        $ml = $minutes_late > 0 ? $minutes_late : 30;
        $deduction = ($rate / 8) * ($ml / 60);
        return round(max(0, $rate - $deduction), 2);
    }
    if ($minutes_late > 0) {
        $deduction = ($rate / 8) * ($minutes_late / 60);
        return round(max(0, $rate - $deduction), 2);
    }
    return $rate;
}

// Filters for unpaid attendance
$uStart = $_GET['u_start'] ?? '';
$uEnd = $_GET['u_end'] ?? '';

// Get all employees with daily_rate for the table
$allemps = $pdo->query("SELECT id, first_name, last_name, employee_id, daily_rate FROM employees WHERE status = 'active' AND daily_rate > 0 ORDER BY last_name")->fetchAll();

// Unpaid attendance data (only records not yet linked to any payroll)
$unpaidWhere = ["a.payroll_id IS NULL"];
$unpaidParams = [];
if ($uStart) {
    $unpaidWhere[] = "a.date >= ?";
    $unpaidParams[] = $uStart;
}
if ($uEnd) {
    $unpaidWhere[] = "a.date <= ?";
    $unpaidParams[] = $uEnd;
}
$unpaidSql = "SELECT a.*, e.first_name, e.last_name, e.employee_id as emp_code, e.daily_rate FROM attendance a JOIN employees e ON a.employee_id = e.id WHERE " . implode(' AND ', $unpaidWhere) . " ORDER BY e.last_name, a.date";
$unpaidStmt = $pdo->prepare($unpaidSql);
$unpaidStmt->execute($unpaidParams);
$unpaidRows = $unpaidStmt->fetchAll();

// Group unpaid by employee
$unpaidByEmp = [];
foreach ($unpaidRows as $r) {
    $eid = $r['employee_id'];
    if (!isset($unpaidByEmp[$eid])) {
        $unpaidByEmp[$eid] = ['name' => $r['first_name'] . ' ' . $r['last_name'], 'code' => $r['emp_code'], 'rate' => $r['daily_rate'], 'rows' => []];
    }
    $unpaidByEmp[$eid]['rows'][] = $r;
}

// Payroll records
$pStart = $_GET['p_start'] ?? date('Y-01-01');
$pEnd = $_GET['p_end'] ?? date('Y-12-31');
$payrolls = $pdo->prepare("SELECT p.*, e.first_name, e.last_name, e.employee_id FROM payroll p JOIN employees e ON p.employee_id = e.id ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset");
$payrolls->bindValue('limit', $perPage, PDO::PARAM_INT);
$payrolls->bindValue('offset', $offset, PDO::PARAM_INT);
$payrolls->execute();
$prows = $payrolls->fetchAll();
?>
<div class="max-w-7xl mx-auto space-y-8">
    <div class="flex justify-between items-end">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface flex items-center gap-2"><img
                    src="<?= BASE_URL ?>/public/emojis/Title%20emojis/payroll.png" class="w-8 h-8" alt=""> Payroll
                Management</h2>
            <p class="text-text-body font-body-md">Generate payroll from attendance records.</p>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="p-4 bg-primary-container/20 text-on-primary-container rounded-lg font-semibold"><?= h($msg) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="p-4 bg-error-container text-on-error-container rounded-lg font-semibold"><?= h($error) ?></div>
    <?php endif; ?>

    <!-- Unpaid Attendance -->
    <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle overflow-hidden">
        <div class="p-5 border-b border-border-subtle flex items-center justify-between flex-wrap gap-3">
            <h3 class="font-headline-md text-headline-md">Unpaid Attendance</h3>
            <form method="GET" class="flex items-center gap-2">
                <input type="date" name="u_start" value="<?= h($uStart) ?>"
                    class="h-9 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm" placeholder="From">
                <input type="date" name="u_end" value="<?= h($uEnd) ?>"
                    class="h-9 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm" placeholder="To">
                <button type="submit"
                    class="h-9 px-4 bg-primary-container text-white font-bold rounded-lg text-sm hover:brightness-95">Filter</button>
                <?php if ($uStart || $uEnd): ?><a href="?"
                        class="h-9 px-4 border border-border-subtle rounded-lg text-sm text-secondary flex items-center hover:bg-surface-muted">Clear</a><?php endif; ?>
            </form>
        </div>
        <?php if (empty($unpaidByEmp)): ?>
            <div class="p-8 text-center text-secondary">No unpaid attendance records found.</div>
        <?php else: ?>
            <form method="POST" id="payroll-form">
                <input type="hidden" name="period_start"
                    value="<?= h($uStart ?: date('Y-m-d', strtotime('first day of this month'))) ?>">
                <input type="hidden" name="period_end"
                    value="<?= h($uEnd ?: date('Y-m-d', strtotime('last day of this month'))) ?>">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-surface-muted border-b border-border-subtle">
                                <th
                                    class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold w-10">
                                    <input type="checkbox" id="select-all" onchange="toggleAll()">
                                </th>
                                <th
                                    class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">
                                    Employee</th>
                                <th
                                    class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">
                                    Date</th>
                                <th
                                    class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">
                                    Status</th>
                                <th
                                    class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">
                                    In</th>
                                <th
                                    class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">
                                    Out</th>
                                <th
                                    class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">
                                    Late</th>
                                <th
                                    class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">
                                    Daily Pay</th>
                                <th
                                    class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">
                                    Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $empIdx = 0;
                            foreach ($unpaidByEmp as $eid => $eg): ?>
                                <?php $totalP = 0;
                                $totalD = 0;
                                $totalLate = 0; ?>
                                <tr class="bg-surface-container-low border-b border-border-subtle">
                                    <td class="px-4 py-2" colspan="9">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" class="emp-group" data-emp="<?= $eid ?>"
                                                onchange="toggleGroup(<?= $eid ?>)">
                                            <span class="font-bold text-body-sm"><?= h($eg['name']) ?>
                                                (<?= h($eg['code']) ?>)</span>
                                            <span class="text-xs text-secondary">— Rate:
                                                ₱<?= number_format($eg['rate'] ?? 0, 2) ?>/day</span>
                                        </label>
                                    </td>
                                </tr>
                                <?php foreach ($eg['rows'] as $r):
                                    $rate = (float) $r['daily_rate'];
                                    $minutesLate = (int) ($r['minutes_late'] ?? 0);
                                    $dayPay = calcDayPay($r['status'], $rate, $minutesLate);
                                    $totalP += $dayPay;
                                    if ($dayPay > 0)
                                        $totalD++;
                                    $totalLate += $minutesLate;
                                    ?>
                                    <tr class="hover:bg-surface-muted transition-colors border-b border-border-subtle">
                                        <td class="px-4 py-2"><input type="checkbox" name="employee_ids[]" value="<?= $eid ?>"
                                                class="emp-cb emp-<?= $eid ?>"></td>
                                        <td class="px-4 py-2"></td>
                                        <td class="px-4 py-2 text-body-sm"><?= h($r['date']) ?></td>
                                        <td class="px-4 py-2">
                                            <span
                                                class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase <?= $r['status'] === 'present' ? 'bg-green-100 text-green-700' : ($r['status'] === 'late' ? 'bg-yellow-100 text-yellow-700' : ($r['status'] === 'half_day' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700')) ?>"><?= ucfirst($r['status']) ?></span>
                                        </td>
                                        <td class="px-4 py-2 font-mono text-body-sm"><?= h($r['clock_in'] ?? '--') ?></td>
                                        <td class="px-4 py-2 font-mono text-body-sm"><?= h($r['clock_out'] ?? '--') ?></td>
                                        <td class="px-4 py-2 font-mono text-body-sm"><?= $minutesLate ?>m
                                        </td>
                                        <td class="px-4 py-2 font-mono text-body-sm">₱<?= number_format($rate, 2) ?></td>
                                        <td class="px-4 py-2 font-mono text-body-sm">₱<?= number_format($dayPay, 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="border-b border-border-subtle bg-surface-muted/50">
                                    <td class="px-4 py-2" colspan="4"></td>
                                    <td class="px-4 py-2 font-mono font-bold text-body-sm">Sub: <?= $totalD ?>d</td>
                                    <td class="px-4 py-2"></td>
                                    <td class="px-4 py-2 font-mono font-bold text-body-sm"><?= $totalLate ?>m
                                    </td>
                                    <td class="px-4 py-2"></td>
                                    <td class="px-4 py-2 font-mono font-bold text-body-sm">₱<?= number_format($totalP, 2) ?>
                                    </td>
                                </tr>
                                <?php $empIdx++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-border-subtle flex justify-between items-center">
                    <span class="text-sm text-secondary" id="selected-count">0 employees selected</span>
                    <button type="submit" name="generate"
                        class="h-10 px-6 bg-primary-container text-white font-bold rounded-lg text-sm hover:brightness-95">Generate
                        Payroll for Selected</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <!-- Payroll Records -->
    <div
        class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle overflow-hidden pb-10 px-4">
        <div class="p-5 border-b border-border-subtle">
            <h3 class="font-headline-md text-headline-md">Payroll Records</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-surface-muted border-b border-border-subtle">
                        <th
                            class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">
                            Employee</th>
                        <th
                            class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">
                            Period</th>
                        <th
                            class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">
                            Attendance</th>
                        <th
                            class="text-right px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">
                            Days</th>
                        <th
                            class="text-right px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">
                            Hours</th>
                        <th
                            class="text-right px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">
                            Late</th>
                        <th
                            class="text-right px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">
                            Deductions</th>
                        <th
                            class="text-right px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">
                            Net</th>
                        <th
                            class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">
                            Status</th>
                        <th
                            class="text-right px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">
                            Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($prows)): ?>
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-secondary">No payroll records yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($prows as $p): ?>
                            <tr class="hover:bg-surface-muted transition-colors border-b border-border-subtle">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-9 h-9 rounded-full bg-primary-container flex items-center justify-center text-xs font-bold">
                                            <?= strtoupper(substr($p['first_name'], 0, 1) . substr($p['last_name'], 0, 1)) ?>
                                        </div>
                                        <span
                                            class="font-semibold text-body-sm"><?= h($p['first_name'] . ' ' . $p['last_name']) ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-body-sm whitespace-nowrap"><?= h($p['period_start']) ?> →
                                    <?= h($p['period_end']) ?>
                                </td>
                                <td class="px-6 py-4 text-body-sm"><?= h($p['attendance_summary'] ?? '') ?></td>
                                <td class="px-6 py-4 text-right font-mono"><?= $p['days_worked'] ?: 0 ?></td>
                                <td class="px-6 py-4 text-right font-mono"><?= number_format($p['total_hours'] ?: 0, 1) ?></td>
                                <td class="px-6 py-4 text-right font-mono">
                                    <?= ($p['total_late_minutes'] ?? 0) ? $p['total_late_minutes'] . 'm' : '—' ?>
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-red-600">
                                    ₱<?= number_format($p['deductions'], 2) ?></td>
                                <td class="px-6 py-4 text-right font-mono font-bold">₱<?= number_format($p['net_pay'], 2) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest <?= $p['status'] === 'paid' ? 'bg-green-100 text-green-700' : ($p['status'] === 'approved' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700') ?>"><?= ucfirst($p['status']) ?></span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="?print=<?= $p['id'] ?>" target="_blank"
                                            class="w-8 h-8 rounded-lg border border-border-subtle flex items-center justify-center text-secondary hover:bg-primary-container transition-all"
                                            title="Print">🖨</a>
                                        <?php if ($p['status'] === 'draft'): ?>
                                            <form method="POST" class="inline"><input type="hidden" name="id"
                                                    value="<?= $p['id'] ?>"><button type="submit" name="approve"
                                                    class="w-8 h-8 rounded-lg border border-border-subtle flex items-center justify-center text-secondary hover:bg-blue-100 transition-all"
                                                    title="Approve">✓</button></form>
                                            <form method="POST" class="inline"><input type="hidden" name="id"
                                                    value="<?= $p['id'] ?>"><button type="submit" name="delete"
                                                    onclick="return confirm('Delete? This will revert attendance to unpaid.')"
                                                    class="w-8 h-8 rounded-lg border border-border-subtle flex items-center justify-center text-secondary hover:bg-red-100 transition-all"
                                                    title="Delete">✕</button></form>
                                        <?php elseif ($p['status'] === 'approved'): ?>
                                            <form method="POST" class="inline"><input type="hidden" name="id"
                                                    value="<?= $p['id'] ?>"><button type="submit" name="pay"
                                                    class="w-8 h-8 rounded-lg border border-border-subtle flex items-center justify-center text-secondary hover:bg-green-100 transition-all"
                                                    title="Mark Paid">$</button></form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= renderPaginationWithInfo($pagination) ?>
    </div>
</div>

<script>
    function toggleAll() {
        var all = document.getElementById('select-all').checked;
        document.querySelectorAll('.emp-cb').forEach(function (cb) { cb.checked = all; });
        updateCount();
    }
    function toggleGroup(eid) {
        var checked = document.querySelector('.emp-group[data-emp="' + eid + '"]').checked;
        document.querySelectorAll('.emp-' + eid).forEach(function (cb) { cb.checked = checked; });
        updateCount();
    }
    function updateCount() {
        var checked = document.querySelectorAll('.emp-cb:checked').length;
        var emps = new Set();
        document.querySelectorAll('.emp-cb:checked').forEach(function (cb) { emps.add(cb.value); });
        document.getElementById('selected-count').textContent = emps.size + ' employee(s), ' + checked + ' day(s) selected';
    }
    document.querySelectorAll('.emp-cb').forEach(function (cb) { cb.addEventListener('change', updateCount); });
    updateCount();
</script>

<style>
    main {
        background: linear-gradient(rgba(255, 255, 255, 0.92), rgba(255, 255, 255, 0.92)), url('<?= BASE_URL ?>/public/background/dashboard.jpeg') center/cover no-repeat fixed;
        min-height: 100vh
    }
</style>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
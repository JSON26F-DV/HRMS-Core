<?php
requireLogin();
requireAdmin();
$pageTitle = 'Payroll Management | HRMS Core';
$currentPage = 'payroll';
require_once __DIR__ . '/../../includes/header.php';

$msg = $error = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Generate payroll
    if (isset($_POST['generate'])) {
        $start = $_POST['period_start'] ?? date('Y-m-01');
        $end = $_POST['period_end'] ?? date('Y-m-t');
        $employees = $pdo->query("SELECT id, salary FROM employees WHERE status = 'active' AND salary > 0")->fetchAll();
        $inserted = 0;
        $stmt = $pdo->prepare("INSERT IGNORE INTO payroll (employee_id, period_start, period_end, gross_pay, deductions, net_pay, status) VALUES (?, ?, ?, ?, ?, ?, 'draft')");
        foreach ($employees as $e) {
            $deductions = round($e['salary'] * 0.1, 2); // 10% default deduction
            $net = $e['salary'] - $deductions;
            $stmt->execute([$e['id'], $start, $end, $e['salary'], $deductions, $net]);
            if ($stmt->rowCount()) $inserted++;
        }
        logAudit('generate', 'payroll', null, "Generated $inserted payroll records for $start → $end");
        $msg = "Generated $inserted payroll records.";
    }
    // Approve
    if (isset($_POST['approve'])) {
        $pdo->prepare("UPDATE payroll SET status = 'approved' WHERE id = ?")->execute([(int)$_POST['id']]);
        logAudit('approve', 'payroll', (int)$_POST['id'], 'Payroll approved');
        $msg = 'Payroll approved.';
    }
    // Mark paid
    if (isset($_POST['pay'])) {
        $pdo->prepare("UPDATE payroll SET status = 'paid', paid_at = NOW() WHERE id = ?")->execute([(int)$_POST['id']]);
        logAudit('pay', 'payroll', (int)$_POST['id'], 'Payroll marked as paid');
        $msg = 'Payroll marked as paid.';
    }
    // Delete draft
    if (isset($_POST['delete'])) {
        $pdo->prepare("DELETE FROM payroll WHERE id = ? AND status = 'draft'")->execute([(int)$_POST['id']]);
        $msg = 'Draft deleted.';
    }
}

// Print single payslip
if (isset($_GET['print'])) {
    $stmt = $pdo->prepare("SELECT p.*, e.first_name, e.last_name, e.employee_id, e.department_id, d.name as dept_name FROM payroll p JOIN employees e ON p.employee_id = e.id LEFT JOIN departments d ON e.department_id = d.id WHERE p.id = ?");
    $stmt->execute([(int)$_GET['print']]);
    $p = $stmt->fetch();
    if (!$p) { header('Location: '.BASE_URL.'/admin/payroll'); exit; }
    ?><!DOCTYPE html><html><head><meta charset="utf-8"><title>Payslip</title><style>
        body{font-family:Arial,sans-serif;padding:40px;color:#333}
        .header{text-align:center;border-bottom:2px solid #006d43;padding-bottom:20px;margin-bottom:30px}
        .header h1{color:#006d43;margin:0 0 5px}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:30px}
        .field{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eee}
        .field strong{color:#555}
        .total{background:#f0fdf4;padding:15px;border-radius:8px;font-size:18px;text-align:center;margin-top:20px}
        .total strong{color:#006d43;font-size:24px}
        .footer{text-align:center;margin-top:40px;color:#999;font-size:12px;border-top:1px solid #eee;padding-top:15px}
        @media print{body{padding:20px}.no-print{display:none}}
    </style></head><body>
        <div class="header"><h1>HRMS Core</h1><p>Payslip</p></div>
        <div class="grid">
            <div><div class="field"><strong>Employee:</strong> <span><?= h($p['first_name'].' '.$p['last_name']) ?></span></div>
            <div class="field"><strong>Employee ID:</strong> <span><?= h($p['employee_id']) ?></span></div>
            <div class="field"><strong>Department:</strong> <span><?= h($p['dept_name'] ?? 'N/A') ?></span></div></div>
            <div><div class="field"><strong>Period:</strong> <span><?= h($p['period_start']) ?> → <?= h($p['period_end']) ?></span></div>
            <div class="field"><strong>Status:</strong> <span><?= ucfirst($p['status']) ?></span></div>
            <div class="field"><strong>Date:</strong> <span><?= h($p['paid_at'] ? date('Y-m-d',strtotime($p['paid_at'])) : date('Y-m-d')) ?></span></div></div>
        </div>
        <div class="field"><strong>Gross Pay:</strong> <span>$<?= number_format($p['gross_pay'],2) ?></span></div>
        <div class="field"><strong>Deductions:</strong> <span>$<?= number_format($p['deductions'],2) ?></span></div>
        <div class="total"><strong>Net Pay: $<?= number_format($p['net_pay'],2) ?></strong></div>
        <div class="footer"><p>This is a computer-generated document.</p></div>
        <div class="no-print" style="text-align:center;margin-top:20px"><button onclick="window.print()" style="padding:10px 30px;background:#006d43;color:#fff;border:none;border-radius:6px;cursor:pointer">Print</button> <a href="<?= BASE_URL ?>/admin/payroll" style="padding:10px 30px;background:#eee;color:#333;border:none;border-radius:6px;text-decoration:none">Back</a></div>
    </body></html><?php exit;
}

$periodStart = $_GET['period_start'] ?? date('Y-m-01');
$periodEnd = $_GET['period_end'] ?? date('Y-m-t');
$payrolls = $pdo->prepare("SELECT p.*, e.first_name, e.last_name, e.employee_id FROM payroll p JOIN employees e ON p.employee_id = e.id WHERE p.period_start >= ? AND p.period_end <= ? ORDER BY p.created_at DESC");
$payrolls->execute([$periodStart, $periodEnd]);
$rows = $payrolls->fetchAll();
?>
<div class="space-y-8">
    <div class="flex justify-between items-end">
        <div><h2 class="font-headline-lg text-headline-lg text-on-surface">Payroll Management</h2><p class="text-text-body font-body-md">Manage employee compensation and payroll processing.</p></div>
    </div>

    <?php if ($msg): ?><div class="p-4 bg-primary-container/20 text-on-primary-container rounded-lg font-semibold"><?= h($msg) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="p-4 bg-error-container text-on-error-container rounded-lg font-semibold"><?= h($error) ?></div><?php endif; ?>

    <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-6">
        <h3 class="font-headline-md text-headline-md mb-4">Generate Payroll</h3>
        <form method="POST" class="flex items-end gap-4">
            <div class="space-y-1"><label class="text-sm text-secondary">Period Start</label><input type="date" name="period_start" value="<?= h($periodStart) ?>" class="h-10 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm"></div>
            <div class="space-y-1"><label class="text-sm text-secondary">Period End</label><input type="date" name="period_end" value="<?= h($periodEnd) ?>" class="h-10 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm"></div>
            <button type="submit" name="generate" class="h-10 px-6 bg-primary-container text-on-primary-container font-bold rounded-lg hover:brightness-95">Generate for All Active</button>
        </form>
    </div>

    <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle overflow-hidden">
        <div class="p-4 border-b border-border-subtle flex items-center justify-between">
            <h3 class="font-headline-md text-headline-md">Payroll Records</h3>
            <form method="GET" class="flex items-center gap-2">
                <input type="month" name="period" value="<?= h(substr($periodStart,0,7)) ?>" class="h-9 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm" onchange="this.form.submit()">
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-surface-muted border-b border-border-subtle">
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Employee</th>
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Period</th>
                        <th class="text-right px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Gross</th>
                        <th class="text-right px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Deductions</th>
                        <th class="text-right px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Net</th>
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Status</th>
                        <th class="text-right px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="px-6 py-12 text-center text-secondary">No payroll records for this period.</td></tr>
                    <?php else: ?>
                    <?php foreach ($rows as $p): ?>
                    <tr class="hover:bg-surface-muted transition-colors border-b border-border-subtle">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-primary-container flex items-center justify-center text-xs font-bold"><?= strtoupper(substr($p['first_name'],0,1).substr($p['last_name'],0,1)) ?></div>
                                <span class="font-semibold text-body-sm"><?= h($p['first_name'].' '.$p['last_name']) ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-body-sm whitespace-nowrap"><?= h($p['period_start']) ?> → <?= h($p['period_end']) ?></td>
                        <td class="px-6 py-4 text-right font-mono">$<?= number_format($p['gross_pay'],2) ?></td>
                        <td class="px-6 py-4 text-right font-mono text-red-600">$<?= number_format($p['deductions'],2) ?></td>
                        <td class="px-6 py-4 text-right font-mono font-bold">$<?= number_format($p['net_pay'],2) ?></td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest <?= $p['status']==='paid'?'bg-green-100 text-green-700':($p['status']==='approved'?'bg-blue-100 text-blue-700':'bg-yellow-100 text-yellow-700') ?>"><?= ucfirst($p['status']) ?></span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="?print=<?= $p['id'] ?>" target="_blank" class="w-8 h-8 rounded-lg border border-border-subtle flex items-center justify-center text-secondary hover:bg-primary-container transition-all" title="Print">🖨</a>
                                <?php if ($p['status'] === 'draft'): ?>
                                <form method="POST" class="inline"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button type="submit" name="approve" class="w-8 h-8 rounded-lg border border-border-subtle flex items-center justify-center text-secondary hover:bg-blue-100 transition-all" title="Approve">✓</button></form>
                                <form method="POST" class="inline"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button type="submit" name="delete" onclick="return confirm('Delete?')" class="w-8 h-8 rounded-lg border border-border-subtle flex items-center justify-center text-secondary hover:bg-red-100 transition-all" title="Delete">✕</button></form>
                                <?php elseif ($p['status'] === 'approved'): ?>
                                <form method="POST" class="inline"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button type="submit" name="pay" class="w-8 h-8 rounded-lg border border-border-subtle flex items-center justify-center text-secondary hover:bg-green-100 transition-all" title="Mark Paid">$</button></form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

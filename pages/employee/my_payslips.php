<?php
requireLogin();
$pageTitle = 'My Payslips | HRMS Core';
$currentPage = 'my_payslips';
require_once __DIR__ . '/../../includes/header.php';

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
$stmt->execute([$userId]);
$emp = $stmt->fetch();

$filterStart = $_GET['start'] ?? '';
$filterEnd = $_GET['end'] ?? '';

$payslips = [];
if ($emp) {
    $sql = "SELECT * FROM payroll WHERE employee_id = ?";
    $params = [$emp['id']];
    if ($filterStart) {
        $sql .= " AND period_start >= ?";
        $params[] = $filterStart;
    }
    if ($filterEnd) {
        $sql .= " AND period_end <= ?";
        $params[] = $filterEnd;
    }
    $sql .= " ORDER BY period_start DESC";
    $p = $pdo->prepare($sql);
    $p->execute($params);
    $payslips = $p->fetchAll();
}
?>
<div class="max-w-7xl mx-auto space-y-8">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface">My Payslips</h2>
            <p class="text-text-body font-body-md">View and download your payroll history.</p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            <input type="date" name="start" value="<?= h($filterStart) ?>"
                class="h-9 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm">
            <input type="date" name="end" value="<?= h($filterEnd) ?>"
                class="h-9 px-3 bg-surface-muted border border-border-subtle rounded-lg text-sm">
            <button type="submit"
                class="h-9 px-4 bg-primary text-white font-bold rounded-lg text-sm hover:brightness-95">Filter</button>
            <?php if ($filterStart || $filterEnd): ?>
            <a href="<?= BASE_URL ?>/employee/my-payslips"
                class="h-9 px-4 border border-border-subtle rounded-lg text-sm text-secondary flex items-center hover:bg-surface-muted">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-surface-muted border-b border-border-subtle">
                        <th class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">Period</th>
                        <th class="text-right px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">Days</th>
                        <th class="text-right px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">Hours</th>
                        <th class="text-right px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">Late</th>
                        <th class="text-right px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">Gross</th>
                        <th class="text-right px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">Deductions</th>
                        <th class="text-right px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">Net</th>
                        <th class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">Status</th>
                        <th class="text-left px-4 py-3 text-label-sm text-secondary uppercase tracking-widest font-bold">Paid At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payslips)): ?>
                    <tr><td colspan="9" class="px-6 py-12 text-center text-secondary">No payslips available yet.</td></tr>
                    <?php else: ?>
                    <?php foreach ($payslips as $p): ?>
                    <tr class="hover:bg-surface-muted transition-colors border-b border-border-subtle">
                        <td class="px-4 py-3 text-body-sm"><?= h($p['period_start']) ?> → <?= h($p['period_end']) ?></td>
                        <td class="px-4 py-3 text-right font-mono text-body-sm"><?= (int)$p['days_worked'] ?></td>
                        <td class="px-4 py-3 text-right font-mono text-body-sm"><?= number_format($p['total_hours'], 1) ?></td>
                        <td class="px-4 py-3 text-right font-mono text-body-sm"><?= (int)$p['total_late_minutes'] ?>m</td>
                        <td class="px-4 py-3 text-right font-mono text-body-sm">₱<?= number_format($p['gross_pay'], 2) ?></td>
                        <td class="px-4 py-3 text-right font-mono text-body-sm text-red-600">₱<?= number_format($p['deductions'], 2) ?></td>
                        <td class="px-4 py-3 text-right font-mono text-body-sm font-bold">₱<?= number_format($p['net_pay'], 2) ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase <?= $p['status'] === 'paid' ? 'bg-green-100 text-green-700' : ($p['status'] === 'approved' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700') ?>">
                                <?= ucfirst($p['status']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-body-sm text-secondary"><?= $p['paid_at'] ? date('M d, Y', strtotime($p['paid_at'])) : '--' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $pageScripts = '
<style>
main {
    background: linear-gradient(rgba(255,255,255,0.92), rgba(255,255,255,0.92)), url("' . BASE_URL . '/public/background/dashboard.jpeg") center/cover no-repeat fixed;
    min-height: 100vh;
}
</style>';
require_once __DIR__ . '/../../includes/footer.php'; ?>

<?php
requireLogin();
$pageTitle = 'My Payslips | HRMS Core';
$currentPage = 'my_payslips';
require_once __DIR__ . '/../../includes/header.php';

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
$stmt->execute([$userId]);
$emp = $stmt->fetch();

$payslips = [];
if ($emp) {
    $p = $pdo->prepare("SELECT * FROM payroll WHERE employee_id = ? ORDER BY period_start DESC");
    $p->execute([$emp['id']]);
    $payslips = $p->fetchAll();
}
?>
<div class="max-w-4xl mx-auto space-y-8">
    <div>
        <h2 class="font-headline-lg text-headline-lg text-on-surface">My Payslips</h2>
        <p class="text-text-body font-body-md">View and download your payroll history.</p>
    </div>

    <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-surface-muted border-b border-border-subtle">
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Period</th>
                        <th class="text-right px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Gross Pay</th>
                        <th class="text-right px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Deductions</th>
                        <th class="text-right px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Net Pay</th>
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payslips)): ?>
                    <tr><td colspan="5" class="px-6 py-12 text-center text-secondary">No payslips available yet.</td></tr>
                    <?php else: ?>
                    <?php foreach ($payslips as $p): ?>
                    <tr class="hover:bg-surface-muted transition-colors border-b border-border-subtle">
                        <td class="px-6 py-4 text-body-sm"><?= h($p['period_start']) ?> → <?= h($p['period_end']) ?></td>
                        <td class="px-6 py-4 text-right font-mono">$<?= number_format($p['gross_pay'], 2) ?></td>
                        <td class="px-6 py-4 text-right font-mono text-red-600">$<?= number_format($p['deductions'], 2) ?></td>
                        <td class="px-6 py-4 text-right font-mono font-bold">$<?= number_format($p['net_pay'], 2) ?></td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest <?= $p['status'] === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                                <?= ucfirst($p['status']) ?>
                            </span>
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

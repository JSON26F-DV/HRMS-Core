<?php
requireLogin();
requireAdmin();
$pageTitle = 'Leave Management | HRMS Core';
$currentPage = 'leave_management';
require_once __DIR__ . '/../../includes/header.php';

$leaves = $pdo->query("
    SELECT l.*, e.first_name, e.last_name, e.employee_id, d.name as department_name
    FROM leaves l
    JOIN employees e ON l.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    ORDER BY l.created_at DESC
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['leave_id'])) {
    $newStatus = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    $stmt = $pdo->prepare("UPDATE leaves SET status = ?, approved_by = ? WHERE id = ?");
    $stmt->execute([$newStatus, $_SESSION['user_id'], $_POST['leave_id']]);
    header('Location: ' . BASE_URL . '/admin/leave-management');
    exit;
}
?>
<div class="space-y-8">
    <div class="flex justify-between items-end">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface">Leave Management</h2>
            <p class="text-text-body font-body-md">Review and manage employee leave requests.</p>
        </div>
    </div>

    <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-surface-muted border-b border-border-subtle">
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Employee</th>
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Type</th>
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Dates</th>
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Status</th>
                        <th class="text-right px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leaves)): ?>
                    <tr><td colspan="5" class="px-6 py-12 text-center text-secondary">No leave requests found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($leaves as $l): ?>
                    <tr class="hover:bg-surface-muted transition-colors border-b border-border-subtle">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-primary-container flex items-center justify-center text-xs font-bold text-on-primary-container">
                                    <?= strtoupper(substr($l['first_name'], 0, 1) . substr($l['last_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <p class="font-semibold text-on-surface text-body-sm"><?= h($l['first_name'] . ' ' . $l['last_name']) ?></p>
                                    <p class="text-xs text-secondary"><?= h($l['employee_id']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4"><span class="capitalize"><?= h($l['type']) ?></span></td>
                        <td class="px-6 py-4 text-body-sm"><?= h($l['start_date']) ?> → <?= h($l['end_date']) ?></td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest inline-flex items-center gap-1
                                <?= $l['status'] === 'approved' ? 'bg-green-100 text-green-700' : ($l['status'] === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') ?>">
                                <?= ucfirst($l['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <?php if ($l['status'] === 'pending'): ?>
                            <div class="flex items-center justify-end gap-2">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="leave_id" value="<?= $l['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button class="px-3 py-1.5 bg-green-100 text-green-700 rounded-lg text-xs font-bold hover:bg-green-200">Approve</button>
                                </form>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="leave_id" value="<?= $l['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button class="px-3 py-1.5 bg-red-100 text-red-700 rounded-lg text-xs font-bold hover:bg-red-200">Reject</button>
                                </form>
                            </div>
                            <?php endif; ?>
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

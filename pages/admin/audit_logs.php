<?php
requireLogin();
requireAdmin();
$pageTitle = 'Audit Logs | HRMS Core';
$currentPage = 'audit_logs';
require_once __DIR__ . '/../../includes/header.php';

$logs = $pdo->query("
    SELECT al.*, u.email as user_email
    FROM audit_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 100
")->fetchAll();
?>
<div class="max-w-7xl mx-auto space-y-8">
    <div class="flex justify-between items-end">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface flex items-center gap-2"><img src="<?= BASE_URL ?>/public/emojis/Title%20emojis/audit.png" class="w-8 h-8" alt=""> Audit Logs</h2>
            <p class="text-text-body font-body-md">Track all system activities and changes.</p>
        </div>
    </div>

    <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-surface-muted border-b border-border-subtle">
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Timestamp</th>
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">User</th>
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Action</th>
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Entity</th>
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr><td colspan="5" class="px-6 py-12 text-center text-secondary">No audit logs found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr class="hover:bg-surface-muted transition-colors border-b border-border-subtle">
                        <td class="px-6 py-4 text-body-sm whitespace-nowrap"><?= h($log['created_at']) ?></td>
                        <td class="px-6 py-4 text-body-sm"><?= h($log['user_email'] ?? 'System') ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 bg-surface-muted rounded-lg text-xs font-bold"><?= h($log['action']) ?></span>
                        </td>
                        <td class="px-6 py-4 text-body-sm"><?= h($log['entity_type'] ?? '--') ?> #<?= h($log['entity_id'] ?? '--') ?></td>
                        <td class="px-6 py-4 text-body-sm text-secondary max-w-xs truncate"><?= h($log['details'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<style>main{background:linear-gradient(rgba(255,255,255,0.92),rgba(255,255,255,0.92)),url('<?= BASE_URL ?>/public/background/dashboard.jpeg') center/cover no-repeat fixed;min-height:100vh}</style>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

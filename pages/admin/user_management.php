<?php
requireLogin();
requireAdmin();
$pageTitle = 'User Management | HRMS Core';
$currentPage = 'user_management';
require_once __DIR__ . '/../../includes/header.php';

$users = $pdo->query("
    SELECT u.*, e.first_name, e.last_name, e.employee_id
    FROM users u
    LEFT JOIN employees e ON u.employee_id = e.id
    ORDER BY u.created_at DESC
")->fetchAll();
?>
<div class="space-y-8">
    <div class="flex justify-between items-end">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface">User Management</h2>
            <p class="text-text-body font-body-md">Manage system users and access control.</p>
        </div>
    </div>

    <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-surface-muted border-b border-border-subtle">
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">User</th>
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Email</th>
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Role</th>
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Status</th>
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Last Login</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr><td colspan="5" class="px-6 py-12 text-center text-secondary">No users found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-surface-muted transition-colors border-b border-border-subtle">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-primary-container flex items-center justify-center text-xs font-bold text-on-primary-container">
                                    <?= strtoupper(substr($u['first_name'] ?? $u['email'], 0, 1) . (isset($u['last_name']) ? substr($u['last_name'], 0, 1) : substr($u['email'], 1, 1))) ?>
                                </div>
                                <span class="font-semibold text-body-sm"><?= h(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '') ?: $u['email']) ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-body-sm"><?= h($u['email']) ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold <?= $u['role'] === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' ?>">
                                <?= ucfirst($u['role']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1 <?= $u['is_active'] ? 'text-green-600' : 'text-red-600' ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?= $u['is_active'] ? 'bg-green-600' : 'bg-red-600' ?>"></span>
                                <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-body-sm"><?= h($u['last_login'] ?? 'Never') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

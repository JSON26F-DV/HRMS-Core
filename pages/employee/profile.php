<?php
requireLogin();
$pageTitle = 'My Profile | HRMS Core';
$currentPage = 'employee_profile';
require_once __DIR__ . '/../../includes/header.php';

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT e.*, d.name as department_name, e.position as position_title, u.email as email
    FROM employees e
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN users u ON e.user_id = u.id
    WHERE e.user_id = ?
");
$stmt->execute([$userId]);
$emp = $stmt->fetch();
?>
<div class="max-w-4xl mx-auto space-y-8">
    <div>
        <h2 class="font-headline-lg text-headline-lg text-on-surface">My Profile</h2>
        <p class="text-text-body font-body-md">Your personal and employment information.</p>
    </div>

    <?php if ($emp): ?>
    <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-8">
        <div class="flex items-center gap-6">
            <div class="w-20 h-20 rounded-full bg-primary-container flex items-center justify-center text-on-primary-container text-2xl font-bold">
                <?= strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1)) ?>
            </div>
            <div>
                <h3 class="font-headline-md text-headline-md text-on-surface"><?= h($emp['first_name'] . ' ' . $emp['last_name']) ?></h3>
                <p class="text-body-md text-secondary"><?= h($emp['position_title'] ?? 'N/A') ?> · <?= h($emp['department_name'] ?? 'N/A') ?></p>
                <p class="text-label-sm text-secondary mt-1">Employee ID: <?= h($emp['employee_id']) ?></p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-6">
            <h4 class="font-label-md text-label-md uppercase tracking-wider font-bold text-secondary mb-4">Contact</h4>
            <div class="space-y-3">
                <div class="flex items-center gap-3 text-body-sm">
                    <span class="material-symbols-outlined text-secondary">mail</span>
                    <span><?= h($emp['email']) ?></span>
                </div>
                <div class="flex items-center gap-3 text-body-sm">
                    <span class="material-symbols-outlined text-secondary">call</span>
                    <span><?= h($emp['phone'] ?? 'N/A') ?></span>
                </div>
            </div>
        </div>
        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-6">
            <h4 class="font-label-md text-label-md uppercase tracking-wider font-bold text-secondary mb-4">Employment</h4>
            <div class="space-y-3">
                <div class="flex justify-between text-body-sm">
                    <span class="text-secondary">Department</span>
                    <span class="font-semibold"><?= h($emp['department_name'] ?? 'N/A') ?></span>
                </div>
                <div class="flex justify-between text-body-sm">
                    <span class="text-secondary">Position</span>
                    <span class="font-semibold"><?= h($emp['position_title'] ?? 'N/A') ?></span>
                </div>
                <div class="flex justify-between text-body-sm">
                    <span class="text-secondary">Hire Date</span>
                    <span class="font-semibold"><?= h($emp['hire_date'] ?? 'N/A') ?></span>
                </div>
                <div class="flex justify-between text-body-sm">
                    <span class="text-secondary">Status</span>
                    <span class="font-semibold capitalize"><?= h($emp['status']) ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-12 text-center">
        <span class="material-symbols-outlined text-4xl text-secondary mb-4">person_off</span>
        <p class="text-secondary">No employee profile linked to your account. Contact your administrator.</p>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<?php
requireLogin();
requireAdmin();
$pageTitle = 'Employee Profile | HRMS Core';
$currentPage = 'employee_profile';
require_once __DIR__ . '/../../includes/header.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("
    SELECT e.*, d.name as department_name, p.title as position_title
    FROM employees e
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN positions p ON e.position_id = p.id
    WHERE e.id = ?
");
$stmt->execute([$id]);
$emp = $stmt->fetch();
if (!$emp) { header('Location: ' . BASE_URL . '/admin/employees'); exit; }
?>
<div class="max-w-5xl mx-auto space-y-8">
    <div class="flex items-center gap-4">
        <a href="<?= BASE_URL ?>/admin/employees" class="p-2 rounded-lg hover:bg-surface-container transition-colors">
            <span class="material-symbols-outlined text-secondary">arrow_back</span>
        </a>
        <h2 class="font-headline-lg text-headline-lg text-on-surface">Employee Profile</h2>
    </div>

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
            <div class="ml-auto">
                <a href="<?= BASE_URL ?>/admin/edit-employee?id=<?= $emp['id'] ?>" class="px-6 py-2.5 bg-primary-container text-on-primary-container font-bold rounded-lg hover:brightness-95 transition-all shadow-sm">Edit Record</a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-6">
            <h4 class="font-label-md text-label-md uppercase tracking-wider font-bold text-secondary mb-4">Contact Information</h4>
            <div class="space-y-3">
                <div class="flex items-center gap-3 text-body-sm">
                    <span class="material-symbols-outlined text-secondary">mail</span>
                    <span><?= h($emp['email']) ?></span>
                </div>
                <div class="flex items-center gap-3 text-body-sm">
                    <span class="material-symbols-outlined text-secondary">call</span>
                    <span><?= h($emp['phone'] ?? 'N/A') ?></span>
                </div>
                <div class="flex items-center gap-3 text-body-sm">
                    <span class="material-symbols-outlined text-secondary">home</span>
                    <span><?= h($emp['address'] ?? 'N/A') ?></span>
                </div>
            </div>
        </div>
        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-6">
            <h4 class="font-label-md text-label-md uppercase tracking-wider font-bold text-secondary mb-4">Employment Details</h4>
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
            </div>
        </div>
        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-6">
            <h4 class="font-label-md text-label-md uppercase tracking-wider font-bold text-secondary mb-4">Compensation</h4>
            <div class="space-y-3">
                <div class="flex justify-between text-body-sm">
                    <span class="text-secondary">Salary</span>
                    <span class="font-semibold">$<?= number_format($emp['salary'] ?? 0, 2) ?></span>
                </div>
                <div class="flex justify-between text-body-sm">
                    <span class="text-secondary">Status</span>
                    <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-bold uppercase"><?= ucfirst($emp['status']) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

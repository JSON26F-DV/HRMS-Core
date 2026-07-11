<?php
requireLogin();
requireAdmin();
$pageTitle = 'Employee Directory | HRMS Core';
$currentPage = 'employees';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/pagination.php';

$perPage = 10;
$currentPageNum = max(1, (int)($_GET['page'] ?? 1));
$offset = ($currentPageNum - 1) * $perPage;

$totalCount = $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active'")->fetchColumn();

$stmt = $pdo->prepare("
    SELECT e.*, d.name as department_name, p.title as position_title
    FROM employees e
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN positions p ON e.position_id = p.id
    WHERE e.status = 'active'
    ORDER BY e.last_name ASC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$employees = $stmt->fetchAll();

$pagination = paginate($currentPageNum, $totalCount, $perPage, $_SERVER['REQUEST_URI'], 'page');
// Remove page param from base URL to avoid duplicates
$pagination['base_url'] = preg_replace('/[?&]page=\d+/', '', $pagination['base_url']);
?>
<div class="employees-page max-w-7xl mx-auto">
    <div class="flex justify-between items-end mb-8">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface tracking-tight flex items-center gap-2"><img
                    src="<?= BASE_URL ?>/public/emojis/Title%20emojis/employees.png" class="w-8 h-8" alt=""> Employee
                Directory</h2>
            <p class="text-text-body font-body-md">Manage your global workforce and personnel records.</p>
        </div>
        <a href="<?= BASE_URL ?>/admin/add-employee"
            class="bg-primary-container text-white text-on-primary-container px-6 py-3 rounded-lg font-bold flex items-center gap-2 hover:brightness-95 transition-all shadow-sm">
            <span class="material-symbols-outlined">person_add</span>
            Add Employee
        </a>
    </div>

    <div class="bg-surface-container-lowest rounded-2xl shadow-sm overflow-hidden border border-border-subtle">
        <div class="overflow-x-auto">
            <table class="w-full employee-table border-collapse">
                <thead>
                    <tr class="bg-surface-muted border-b border-border-subtle">
                        <th
                            class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">
                            Employee</th>
                        <th
                            class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">
                            Role & Dept</th>
                        <th
                            class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">
                            Status</th>
                        <th
                            class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">
                            Contact</th>
                        <th
                            class="text-right px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">
                            Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employees)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-secondary">No employees found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($employees as $emp): ?>
                            <tr class="hover:bg-surface-muted transition-colors border-b border-border-subtle group">
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="w-12 h-12 rounded-full bg-primary-container flex items-center justify-center text-on-primary-container font-bold">
                                            <?= strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-on-surface text-body-md">
                                                <?= h($emp['first_name'] . ' ' . $emp['last_name']) ?></p>
                                            <p class="text-secondary text-xs">ID: <?= h($emp['employee_id']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <div>
                                        <p class="font-semibold text-on-surface text-body-sm">
                                            <?= h($emp['position_title'] ?? 'N/A') ?></p>
                                        <span
                                            class="text-secondary text-xs uppercase font-bold"><?= h($emp['department_name'] ?? 'N/A') ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <span
                                        class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-extrabold uppercase tracking-widest inline-flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 bg-green-700 rounded-full animate-pulse"></span>
                                        <?= ucfirst($emp['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="text-xs text-secondary space-y-0.5">
                                        <p class="flex items-center gap-2"><span
                                                class="material-symbols-outlined text-[14px]">mail</span>
                                            <?= h($emp['email']) ?></p>
                                        <p class="flex items-center gap-2"><span
                                                class="material-symbols-outlined text-[14px]">call</span>
                                            <?= h($emp['phone'] ?? 'N/A') ?></p>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="<?= BASE_URL ?>/admin/employee-profile?id=<?= $emp['id'] ?>"
                                            class="w-9 h-9 rounded-lg border border-border-subtle flex items-center justify-center text-secondary hover:bg-primary-container hover:text-on-primary-container transition-all"
                                            title="View Profile">
                                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                                        </a>
                                        <a href="<?= BASE_URL ?>/admin/edit-employee?id=<?= $emp['id'] ?>"
                                            class="w-9 h-9 rounded-lg border border-border-subtle flex items-center justify-center text-secondary hover:bg-primary-container hover:text-on-primary-container transition-all"
                                            title="Edit Record">
                                            <span class="material-symbols-outlined text-[18px]">edit</span>
                                        </a>
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
<style>
    main {
        background: linear-gradient(rgba(255, 255, 255, 0.92), rgba(255, 255, 255, 0.92)), url('<?= BASE_URL ?>/public/background/dashboard.jpeg') center/cover no-repeat fixed;
        min-height: 100vh
    }
</style>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
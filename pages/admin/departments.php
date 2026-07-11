<?php
requireLogin();
requireAdmin();
$pageTitle = 'Departments | HRMS Core';
$currentPage = 'departments';
require_once __DIR__ . '/../../includes/header.php';

$msg = $error = '';
$editDept = null;
$manageDept = null;

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add
    if (isset($_POST['add'])) {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if (!$name) {
            $error = 'Department name is required.';
        } else {
            $pdo->prepare("INSERT INTO departments (name, description) VALUES (?, ?)")->execute([$name, $desc]);
            logAudit('create', 'department', $pdo->lastInsertId(), 'Created department: '.$name);
            $msg = 'Department created.';
        }
    }
    // Update
    if (isset($_POST['update'])) {
        $id = (int) $_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if (!$name) {
            $error = 'Department name is required.';
        } else {
            $pdo->prepare("UPDATE departments SET name = ?, description = ? WHERE id = ?")->execute([$name, $desc, $id]);
            logAudit('update', 'department', $id, 'Updated department: '.$name);
            $msg = 'Department updated.';
        }
    }
    // Delete
    if (isset($_POST['delete'])) {
        $id = (int) $_POST['id'];
        $empCount = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE department_id = ?");
        $empCount->execute([$id]);
        if ($empCount->fetchColumn() > 0) {
            $error = 'Cannot delete department with assigned employees.';
        } else {
            $pdo->prepare("DELETE FROM positions WHERE department_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM departments WHERE id = ?")->execute([$id]);
            logAudit('delete', 'department', $id, 'Deleted department');
            $msg = 'Department deleted.';
        }
    }
    // Assign employee
    if (isset($_POST['assign'])) {
        $deptId = (int) $_POST['dept_id'];
        $empId = (int) $_POST['employee_id'];
        $pdo->prepare("UPDATE employees SET department_id = ? WHERE id = ?")->execute([$deptId, $empId]);
        $msg = 'Employee assigned.';
    }
    // Unassign employee
    if (isset($_POST['unassign'])) {
        $empId = (int) $_POST['employee_id'];
        $pdo->prepare("UPDATE employees SET department_id = NULL WHERE id = ?")->execute([$empId]);
        $msg = 'Employee unassigned.';
    }
}

// Edit mode
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
    $stmt->execute([(int) $_GET['edit']]);
    $editDept = $stmt->fetch();
}

// Manage mode
if (isset($_GET['manage'])) {
    $stmt = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
    $stmt->execute([(int) $_GET['manage']]);
    $manageDept = $stmt->fetch();
}

$departments = $pdo->query("SELECT d.*, COUNT(e.id) as employee_count FROM departments d LEFT JOIN employees e ON e.department_id = d.id GROUP BY d.id ORDER BY d.name")->fetchAll();
?>
<div class="max-w-7xl mx-auto space-y-8">
    <div class="flex justify-between items-end">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface flex items-center gap-2"><img src="<?= BASE_URL ?>/public/emojis/Title%20emojis/department.png" class="w-8 h-8" alt=""> Departments</h2>
            <p class="text-text-body font-body-md">Manage your organization departments.</p>
        </div>

    </div>

    <?php if ($msg): ?>
        <div class="p-4 bg-primary-container/20 text-on-primary-container rounded-lg font-semibold"><?= h($msg) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="p-4 bg-error-container text-on-error-container rounded-lg font-semibold"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if ($manageDept): ?>
    <?php
    $assigned = $pdo->prepare("SELECT id, first_name, last_name, employee_id FROM employees WHERE department_id = ? ORDER BY last_name");
    $assigned->execute([$manageDept['id']]);
    $unassigned = $pdo->prepare("SELECT id, first_name, last_name, employee_id FROM employees WHERE department_id IS NULL OR department_id = 0 ORDER BY last_name");
    $unassigned->execute();
    ?>
    <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="font-headline-md text-headline-md"><?= h($manageDept['name']) ?></h3>
                <p class="text-text-body font-body-md">Manage employee assignments.</p>
            </div>
            <a href="<?= BASE_URL ?>/admin/departments" class="px-4 py-2 border border-border-subtle rounded-lg text-secondary hover:bg-surface-muted transition-all">← Back</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <h4 class="font-label-md text-label-md uppercase tracking-wider font-bold text-secondary mb-4">Assigned Employees</h4>
                <?php $assignedList = $assigned->fetchAll(); if (empty($assignedList)): ?>
                <p class="text-secondary text-body-sm">No employees assigned.</p>
                <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($assignedList as $emp): ?>
                    <div class="flex items-center justify-between px-4 py-3 bg-surface-muted rounded-lg">
                        <span class="text-body-sm"><?= h($emp['first_name'] . ' ' . $emp['last_name']) ?> (<?= h($emp['employee_id']) ?>)</span>
                        <form method="POST" class="inline">
                            <input type="hidden" name="employee_id" value="<?= $emp['id'] ?>">
                            <button type="submit" name="unassign" class="text-sm text-error hover:underline">Unassign</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div>
                <h4 class="font-label-md text-label-md uppercase tracking-wider font-bold text-secondary mb-4">Unassigned Employees</h4>
                <?php $unassignedList = $unassigned->fetchAll(); if (empty($unassignedList)): ?>
                <p class="text-secondary text-body-sm">All employees are assigned.</p>
                <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($unassignedList as $emp): ?>
                    <div class="flex items-center justify-between px-4 py-3 bg-surface-muted rounded-lg">
                        <span class="text-body-sm"><?= h($emp['first_name'] . ' ' . $emp['last_name']) ?> (<?= h($emp['employee_id']) ?>)</span>
                        <form method="POST" class="inline">
                            <input type="hidden" name="dept_id" value="<?= $manageDept['id'] ?>">
                            <input type="hidden" name="employee_id" value="<?= $emp['id'] ?>">
                            <button type="submit" name="assign" class="text-sm text-primary hover:underline">Assign</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div>
            <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-6">
                <h3 class="font-headline-md text-headline-md mb-4">
                    <?= $editDept ? 'Edit Department' : 'New Department' ?></h3>
                <form method="POST" class="space-y-4">
                    <?php if ($editDept): ?>
                        <input type="hidden" name="id" value="<?= $editDept['id'] ?>">
                    <?php endif; ?>
                    <div class="space-y-1.5">
                        <label class="font-label-md text-label-md text-on-surface-variant">Name</label>
                        <input name="name" value="<?= h($editDept['name'] ?? '') ?>" required
                            class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container">
                    </div>
                    <div class="space-y-1.5">
                        <label class="font-label-md text-label-md text-on-surface-variant">Description</label>
                        <textarea name="description" rows="3"
                            class="w-full px-4 py-3 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container"><?= h($editDept['description'] ?? '') ?></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" name="<?= $editDept ? 'update' : 'add' ?>"
                            class="px-6 py-3 bg-primary-container text-on-primary-container font-bold rounded-lg hover:brightness-95 transition-all shadow-sm">
                            <?= $editDept ? 'Update' : 'Create' ?>
                        </button>
                        <?php if ($editDept): ?>
                            <a href="<?= BASE_URL ?>/admin/departments"
                                class="px-6 py-3 border border-border-subtle rounded-lg font-bold text-secondary hover:bg-surface-muted transition-all">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="bg-surface-muted border-b border-border-subtle">
                            <th
                                class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">
                                Department</th>
                            <th
                                class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">
                                Description</th>
                            <th
                                class="text-center px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">
                                Employees</th>
                            <th
                                class="text-right px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($departments)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-secondary">No departments yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($departments as $d): ?>
                                <tr class="hover:bg-surface-muted transition-colors border-b border-border-subtle">
                                    <td class="px-6 py-4 font-semibold"><?= h($d['name']) ?></td>
                                    <td class="px-6 py-4 text-secondary text-body-sm"><?= h($d['description'] ?: '—') ?></td>
                                    <td class="px-6 py-4 text-center"><?= $d['employee_count'] ?></td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="?edit=<?= $d['id'] ?>"
                                                class="w-9 h-9 rounded-lg border border-border-subtle flex items-center justify-center text-secondary hover:bg-primary-container hover:text-on-primary-container transition-all"
                                                title="Edit">
                                                <span class="material-symbols-outlined text-lg">edit</span>
                                            </a>
                                            <a href="?manage=<?= $d['id'] ?>"
                                                class="w-9 h-9 rounded-lg border border-border-subtle flex items-center justify-center text-secondary hover:bg-primary-container hover:text-on-primary-container transition-all"
                                                title="Manage Employees">
                                                <span class="material-symbols-outlined text-lg">group</span>
                                            </a>
                                            <form method="POST" class="inline"
                                                onsubmit="return confirm('Delete this department?')">
                                                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                                <button type="submit" name="delete"
                                                    class="w-9 h-9 rounded-lg border border-border-subtle flex items-center justify-center text-secondary hover:bg-error-container hover:text-error transition-all"
                                                    title="Delete">
                                                    <span class="material-symbols-outlined text-lg">delete</span>
                                                </button>
                                            </form>
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
    <?php endif; ?>
</div>
<style>main{background:linear-gradient(rgba(255,255,255,0.92),rgba(255,255,255,0.92)),url('<?= BASE_URL ?>/public/background/dashboard.jpeg') center/cover no-repeat fixed;min-height:100vh}</style>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
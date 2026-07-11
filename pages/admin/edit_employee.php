<?php
requireLogin();
requireAdmin();
$pageTitle = 'Edit Employee | HRMS Core';
$currentPage = 'edit_employee';
require_once __DIR__ . '/../../includes/header.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$id]);
$emp = $stmt->fetch();
if (!$emp) { header('Location: ' . BASE_URL . '/admin/employees'); exit; }

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$positions = $pdo->query("SELECT p.*, d.name as dept_name FROM positions p LEFT JOIN departments d ON p.department_id = d.id ORDER BY p.title")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE employees SET first_name=?, last_name=?, email=?, phone=?, position_id=?, department_id=?, hire_date=?, salary=?, address=?, status=? WHERE id=?");
    $stmt->execute([
        $_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone'],
        $_POST['position_id'] ?: null, $_POST['department_id'] ?: null,
        $_POST['hire_date'] ?: null, $_POST['salary'] ?: null,
        $_POST['address'] ?? '', $_POST['status'] ?? 'active', $id
    ]);
    logAudit('update', 'employee', $id, 'Updated employee: '.$_POST['first_name'].' '.$_POST['last_name']);
    header('Location: ' . BASE_URL . '/admin/employees');
    exit;
}
?>
<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-4 mb-8">
        <a href="<?= BASE_URL ?>/admin/employees" class="p-2 rounded-lg hover:bg-surface-container transition-colors">
            <span class="material-symbols-outlined text-secondary">arrow_back</span>
        </a>
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface">Edit Employee</h2>
            <p class="text-text-body font-body-md">Update employee information for <?= h($emp['first_name'] . ' ' . $emp['last_name']) ?></p>
        </div>
    </div>

    <form method="POST" class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-8 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">First Name</label>
                <input name="first_name" value="<?= h($emp['first_name']) ?>" required class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10">
            </div>
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Last Name</label>
                <input name="last_name" value="<?= h($emp['last_name']) ?>" required class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10">
            </div>
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Email</label>
                <input name="email" type="email" value="<?= h($emp['email']) ?>" required class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10">
            </div>
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Phone</label>
                <input name="phone" value="<?= h($emp['phone'] ?? '') ?>" class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10">
            </div>
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Department</label>
                <select name="department_id" class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $dept): ?>
                    <option value="<?= $dept['id'] ?>" <?= $dept['id'] == $emp['department_id'] ? 'selected' : '' ?>><?= h($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Position</label>
                <select name="position_id" id="position_id" class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10">
                    <option value="">Select Department First</option>
                    <?php foreach ($positions as $pos): ?>
                    <option value="<?= $pos['id'] ?>" data-dept="<?= $pos['department_id'] ?>" <?= $pos['id'] == $emp['position_id'] ? 'selected' : '' ?>><?= h($pos['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Hire Date</label>
                <input name="hire_date" type="date" value="<?= h($emp['hire_date'] ?? '') ?>" class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10">
            </div>
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Salary</label>
                <input name="salary" type="number" step="0.01" value="<?= h($emp['salary'] ?? '') ?>" class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10">
            </div>
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Status</label>
                <select name="status" class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10">
                    <option value="active" <?= $emp['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="on_leave" <?= $emp['status'] === 'on_leave' ? 'selected' : '' ?>>On Leave</option>
                    <option value="terminated" <?= $emp['status'] === 'terminated' ? 'selected' : '' ?>>Terminated</option>
                </select>
            </div>
        </div>
        <div class="space-y-1.5">
            <label class="font-label-md text-label-md text-on-surface-variant">Address</label>
            <textarea name="address" rows="3" class="w-full px-4 py-3 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10"><?= h($emp['address'] ?? '') ?></textarea>
        </div>
        <div class="flex gap-4 pt-4">
            <button type="submit" class="px-8 py-3 bg-primary-container text-on-primary-container font-bold rounded-lg hover:brightness-95 transition-all shadow-sm">Update Employee</button>
            <a href="<?= BASE_URL ?>/admin/employees" class="px-8 py-3 border border-border-subtle rounded-lg font-bold text-secondary hover:bg-surface-muted transition-all">Cancel</a>
        </div>
    </form>
</div>
<script>
document.querySelector('[name="department_id"]').addEventListener('change', function() {
    const dept = this.value;
    document.querySelectorAll('#position_id option').forEach(opt => {
        if (opt.value === '') { opt.text = dept ? 'Select Position' : 'Select Department First'; return; }
        opt.style.display = !dept || opt.dataset.dept === dept ? '' : 'none';
    });
});
document.querySelector('[name="department_id"]').dispatchEvent(new Event('change'));
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

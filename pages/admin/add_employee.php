<?php
requireLogin();
requireAdmin();
$pageTitle = 'Add New Employee | HRMS Core';
$currentPage = 'add_employee';
require_once __DIR__ . '/../../includes/header.php';

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$positions = $pdo->query("SELECT p.*, d.name as dept_name FROM positions p LEFT JOIN departments d ON p.department_id = d.id ORDER BY p.title")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO employees (employee_id, first_name, last_name, email, phone, position_id, department_id, hire_date, salary, daily_rate, address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
    $empId = 'EMP-' . date('Y') . '-' . str_pad(mt_rand(0, 999), 3, '0', STR_PAD_LEFT);
    $stmt->execute([
        $empId,
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['email'],
        $_POST['phone'],
        $_POST['position_id'] ?: null,
        $_POST['department_id'] ?: null,
        $_POST['hire_date'] ?: null,
        $_POST['salary'] ?: null,
        $_POST['daily_rate'] ?: null,
        $_POST['address'] ?? '',
    ]);
    logAudit('create', 'employee', $pdo->lastInsertId(), 'Created employee: '.$_POST['first_name'].' '.$_POST['last_name']);
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
            <h2 class="font-headline-lg text-headline-lg text-on-surface flex items-center gap-2">➕ Add New Employee</h2>
            <p class="text-text-body font-body-md">Create a new employee record in the system.</p>
        </div>
    </div>

    <form method="POST" class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-8 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">First Name</label>
                <input name="first_name" required class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10" placeholder="John">
            </div>
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Last Name</label>
                <input name="last_name" required class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10" placeholder="Doe">
            </div>
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Email</label>
                <input name="email" type="email" required class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10" placeholder="john.doe@company.com">
            </div>
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Phone</label>
                <input name="phone" class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10" placeholder="+63 912 345 6789">
            </div>
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Department</label>
                <select name="department_id" class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $dept): ?>
                    <option value="<?= $dept['id'] ?>"><?= h($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Position</label>
                <select name="position_id" id="position_id" class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10">
                    <option value="">Select Department First</option>
                    <?php foreach ($positions as $pos): ?>
                    <option value="<?= $pos['id'] ?>" data-dept="<?= $pos['department_id'] ?>"><?= h($pos['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Hire Date</label>
                <input name="hire_date" type="date" class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10">
            </div>
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Salary</label>
                <input name="salary" type="number" step="0.01" class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10" placeholder="75000.00">
            </div>
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Daily Rate</label>
                <input name="daily_rate" type="number" step="0.01" class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10" placeholder="2500.00">
            </div>
        </div>
        <div class="space-y-1.5">
            <label class="font-label-md text-label-md text-on-surface-variant">Address</label>
            <textarea name="address" rows="3" class="w-full px-4 py-3 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10"></textarea>
        </div>
        <div class="flex gap-4 pt-4">
            <button type="submit" class="px-8 py-3 bg-primary-container text-on-primary-container font-bold rounded-lg hover:brightness-95 transition-all shadow-sm">Save Employee</button>
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
    document.getElementById('position_id').value = '';
});
</script>
<style>main{background:linear-gradient(rgba(255,255,255,0.92),rgba(255,255,255,0.92)),url('<?= BASE_URL ?>/public/background/dashboard.jpeg') center/cover no-repeat fixed;min-height:100vh}</style>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

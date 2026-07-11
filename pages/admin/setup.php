<?php
requireLogin();
requireAdmin();
$pageTitle = 'Setup Wizard | HRMS Core';
$currentPage = 'departments';
require_once __DIR__ . '/../../includes/header.php';

$step = max(1, min(4, (int)($_GET['step'] ?? 1)));
$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$msg = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_dept'])) {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if (!$name) {
            $error = 'Department name is required.';
        } else {
            $pdo->prepare("INSERT INTO departments (name, description) VALUES (?, ?)")->execute([$name, $desc]);
            $departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
            $msg = 'Department created.';
            if (isset($_POST['next'])) $step = 2;
        }
    }

    if (isset($_POST['add_position'])) {
        $title = trim($_POST['title'] ?? '');
        $deptId = $_POST['department_id'] ?? '';
        if (!$title || !$deptId) {
            $error = 'Position title and department are required.';
        } else {
            $pdo->prepare("INSERT INTO positions (title, department_id) VALUES (?, ?)")->execute([$title, $deptId]);
            $msg = 'Position created.';
            if (isset($_POST['next'])) $step = 3;
        }
    }

    if (isset($_POST['add_staff'])) {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $deptId = $_POST['department_id'] ?? '';
        $positionId = $_POST['position_id'] ?? '';

        if (!$firstName || !$lastName || !$email || !$password) {
            $error = 'All fields are required.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            try {
                $pdo->beginTransaction();
                $empId = 'EMP-' . date('Y') . '-' . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $stmt = $pdo->prepare("INSERT INTO employees (employee_id, first_name, last_name, email, department_id, position_id, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
                $stmt->execute([$empId, $firstName, $lastName, $email, $deptId ?: null, $positionId ?: null]);
                $empIdInserted = $pdo->lastInsertId();

                $stmt = $pdo->prepare("INSERT INTO users (employee_id, email, password_hash, role) VALUES (?, ?, ?, 'employee')");
                $stmt->execute([$empIdInserted, $email, password_hash($password, PASSWORD_DEFAULT)]);
                $pdo->commit();
                $msg = 'HR Staff account created.';
                if (isset($_POST['next'])) $step = 4;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }

    if (isset($_POST['done'])) {
        header('Location: ' . BASE_URL . '/admin/dashboard');
        exit;
    }
}
?>
<div class="max-w-2xl mx-auto space-y-8">
    <div class="text-center">
        <div class="flex items-center justify-center gap-2 mb-4">
            <span class="w-3 h-3 rounded-full <?= $step >= 1 ? 'bg-primary' : 'bg-border-subtle' ?>"></span>
            <span class="w-16 h-0.5 <?= $step >= 2 ? 'bg-primary' : 'bg-border-subtle' ?>"></span>
            <span class="w-3 h-3 rounded-full <?= $step >= 2 ? 'bg-primary' : 'bg-border-subtle' ?>"></span>
            <span class="w-16 h-0.5 <?= $step >= 3 ? 'bg-primary' : 'bg-border-subtle' ?>"></span>
            <span class="w-3 h-3 rounded-full <?= $step >= 3 ? 'bg-primary' : 'bg-border-subtle' ?>"></span>
            <span class="w-16 h-0.5 <?= $step >= 4 ? 'bg-primary' : 'bg-border-subtle' ?>"></span>
            <span class="w-3 h-3 rounded-full <?= $step >= 4 ? 'bg-primary' : 'bg-border-subtle' ?>"></span>
        </div>
        <h2 class="font-headline-lg text-headline-lg text-on-surface">
            <?= ['', 'Create Department', 'Create Position', 'Create HR Staff Account', 'System Ready'][$step] ?>
        </h2>
    </div>

    <?php if ($msg): ?>
    <div class="p-4 bg-primary-container/20 text-on-primary-container rounded-lg text-center font-semibold"><?= h($msg) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="p-4 bg-error-container text-on-error-container rounded-lg text-center font-semibold"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if ($step === 1): ?>
    <form method="POST" class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-8 space-y-6">
        <div class="space-y-1.5">
            <label class="font-label-md text-label-md text-on-surface-variant">Department Name</label>
            <input name="name" required class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container" placeholder="e.g. Human Resources">
        </div>
        <div class="space-y-1.5">
            <label class="font-label-md text-label-md text-on-surface-variant">Description (optional)</label>
            <textarea name="description" rows="3" class="w-full px-4 py-3 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container" placeholder="Department description"></textarea>
        </div>
        <div class="flex gap-4">
            <button type="submit" name="add_dept" class="px-8 py-3 bg-primary-container text-on-primary-container font-bold rounded-lg hover:brightness-95 transition-all shadow-sm">Create Department</button>
            <button type="submit" name="next" value="1" class="px-8 py-3 bg-primary text-white font-bold rounded-lg hover:brightness-95 transition-all shadow-sm">Skip to Next →</button>
        </div>
    </form>
    <?php endif; ?>

    <?php if ($step === 2): ?>
    <form method="POST" class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-8 space-y-6">
        <div class="space-y-1.5">
            <label class="font-label-md text-label-md text-on-surface-variant">Position Title</label>
            <input name="title" required class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container" placeholder="e.g. HR Manager">
        </div>
        <div class="space-y-1.5">
            <label class="font-label-md text-label-md text-on-surface-variant">Department</label>
            <select name="department_id" required class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container">
                <option value="">Select Department</option>
                <?php foreach ($departments as $d): ?>
                <option value="<?= $d['id'] ?>"><?= h($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex gap-4">
            <button type="submit" name="add_position" class="px-8 py-3 bg-primary-container text-on-primary-container font-bold rounded-lg hover:brightness-95 transition-all shadow-sm">Create Position</button>
            <button type="submit" name="next" value="1" class="px-8 py-3 bg-primary text-white font-bold rounded-lg hover:brightness-95 transition-all shadow-sm">Skip to Next →</button>
        </div>
    </form>
    <?php endif; ?>

    <?php if ($step === 3): ?>
    <form method="POST" class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-8 space-y-6">
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">First Name</label>
                <input name="first_name" required class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container">
            </div>
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Last Name</label>
                <input name="last_name" required class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container">
            </div>
        </div>
        <div class="space-y-1.5">
            <label class="font-label-md text-label-md text-on-surface-variant">Email</label>
            <input name="email" type="email" required class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container">
        </div>
        <div class="space-y-1.5">
            <label class="font-label-md text-label-md text-on-surface-variant">Password</label>
            <input name="password" type="password" required class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container">
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Department</label>
                <select name="department_id" class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container">
                    <option value="">Select</option>
                    <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= h($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Position</label>
                <select name="position_id" class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container">
                    <option value="">Select</option>
                    <?php
                    $positions = $pdo->query("SELECT p.*, d.name as dept_name FROM positions p LEFT JOIN departments d ON p.department_id = d.id ORDER BY p.title")->fetchAll();
                    foreach ($positions as $pos): ?>
                    <option value="<?= $pos['id'] ?>"><?= h($pos['title']) ?><?= $pos['dept_name'] ? ' (' . h($pos['dept_name']) . ')' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="flex gap-4">
            <button type="submit" name="add_staff" class="px-8 py-3 bg-primary-container text-on-primary-container font-bold rounded-lg hover:brightness-95 transition-all shadow-sm">Create Staff</button>
            <button type="submit" name="next" value="1" class="px-8 py-3 bg-primary text-white font-bold rounded-lg hover:brightness-95 transition-all shadow-sm">Skip to Next →</button>
        </div>
    </form>
    <?php endif; ?>

    <?php if ($step === 4): ?>
    <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-8 text-center space-y-6">
        <div class="w-16 h-16 mx-auto bg-primary-container rounded-full flex items-center justify-center">
            <span class="material-symbols-outlined text-3xl text-on-primary-container" style="font-variation-settings: 'FILL' 1;">check_circle</span>
        </div>
        <h3 class="font-headline-md text-headline-md">System Ready!</h3>
        <p class="text-text-body font-body-md">Your HRMS is fully set up. You can now manage employees, track attendance, process payroll, and more.</p>
        <form method="POST">
            <button type="submit" name="done" class="px-10 py-3 bg-primary text-white font-bold rounded-lg hover:brightness-95 transition-all shadow-sm">Go to Dashboard</button>
        </form>
        <div class="pt-4 text-sm text-secondary space-y-2">
            <p>👥 <a href="<?= BASE_URL ?>/admin/departments" class="text-primary hover:underline">Manage Departments</a></p>
            <p>📋 <a href="<?= BASE_URL ?>/admin/add-employee" class="text-primary hover:underline">Add More Employees</a></p>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

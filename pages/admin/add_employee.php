<?php
requireLogin();
requireHrOrAdmin();
$pageTitle = 'Add New Employee | HRMS Core';
$currentPage = 'add_employee';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    try {
        $empCode = 'EMP-' . date('Y') . '-' . str_pad(mt_rand(0, 999), 3, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("INSERT INTO employees (employee_id, first_name, last_name, phone, position, department_id, hire_date, salary, daily_rate, address, status, avatar_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $empCode,
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['phone'],
            $_POST['position'] ?? null,
            $_POST['department_id'] ?: null,
            $_POST['hire_date'] ?: null,
            $_POST['salary'] ?: null,
            $_POST['daily_rate'] ?: null,
            $_POST['address'] ?? '',
            $_POST['status'] ?? 'active',
            $_POST['avatar_url'] ?? null,
        ]);
        $empId = $pdo->lastInsertId();

        $userCode = $_POST['code'] ? password_hash($_POST['code'], PASSWORD_DEFAULT) : null;
        $role = $_POST['role'] ?? 'employee';
        if (isHr() && $role !== 'employee') {
            $role = 'employee';
        }
        $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role, is_active, code) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['email'],
            password_hash($_POST['password'], PASSWORD_DEFAULT),
            $role,
            isset($_POST['is_active']) ? 1 : 1,
            $userCode,
        ]);
        $userId = $pdo->lastInsertId();
        $pdo->prepare("UPDATE employees SET user_id = ? WHERE id = ?")->execute([$userId, $empId]);
        $pdo->commit();
        try { logAudit('create', 'employee', $empId, 'Created employee: '.$_POST['first_name'].' '.$_POST['last_name']); } catch (Exception $e) {}
        $_SESSION['_flash'] = ['success' => 'Employee created successfully.'];
        header('Location: ' . BASE_URL . '/admin/employees');
        exit;
    } catch (Exception $e) {
        try { $pdo->rollBack(); } catch (Exception $e2) {}
        $_SESSION['_flash'] = ['error' => 'Failed to create employee. Check your input and try again.'];
        header('Location: ' . BASE_URL . '/admin/employees');
        exit;
    }
}

require_once __DIR__ . '/../../includes/header.php';
$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
?>
<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-4 mb-8">
        <a href="<?= BASE_URL ?>/admin/employees" class="p-2 rounded-lg hover:bg-surface-container transition-colors">
            <span class="material-symbols-outlined text-secondary">arrow_back</span>
        </a>
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface flex items-center gap-2">➕ Add New Employee</h2>
            <p class="text-text-body font-body-md">Create employee record and user account.</p>
        </div>
    </div>

    <form method="POST" class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-8 space-y-6">
        <h3 class="font-headline-md text-headline-md border-b border-border-subtle pb-4">Employee Information</h3>
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
                <select name="position" id="position" class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10">
                    <option value="">Select Department First</option>
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
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Status</label>
                <select name="status" class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10">
                    <option value="active">Active</option>
                    <option value="on_leave">On Leave</option>
                    <option value="terminated">Terminated</option>
                </select>
            </div>
        </div>
        <div class="space-y-1.5">
            <label class="font-label-md text-label-md text-on-surface-variant">Address</label>
            <textarea name="address" rows="3" class="w-full px-4 py-3 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10"></textarea>
        </div>
        <div class="space-y-1.5">
            <label class="font-label-md text-label-md text-on-surface-variant">Avatar URL</label>
            <input name="avatar_url" class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10" placeholder="https://example.com/avatar.jpg">
        </div>

        <h3 class="font-headline-md text-headline-md border-b border-border-subtle pb-4 pt-4">User Account</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Password</label>
                <div class="relative">
                    <input name="password" id="password" type="password" required class="w-full h-12 pr-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10" placeholder="Min 6 characters">
                    <button type="button" onclick="togglePass('password','pass-icon')" class="absolute right-3 top-1/2 -translate-y-1/2 text-outline hover:text-primary transition-colors">
                        <span class="material-symbols-outlined text-xl" id="pass-icon">visibility</span>
                    </button>
                </div>
            </div>
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Confirm Password</label>
                <div class="relative">
                    <input name="confirm_password" id="confirm_password" type="password" required class="w-full h-12 pr-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10" placeholder="Repeat password">
                    <button type="button" onclick="togglePass('confirm_password','confirm-pass-icon')" class="absolute right-3 top-1/2 -translate-y-1/2 text-outline hover:text-primary transition-colors">
                        <span class="material-symbols-outlined text-xl" id="confirm-pass-icon">visibility</span>
                    </button>
                </div>
            </div>
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Role</label>
                <select name="role" class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10">
                    <option value="employee">Employee</option>
                    <?php if (isAdmin()): ?>
                    <option value="admin">Admin</option>
                    <option value="hr">HR</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Recovery Code</label>
                <input name="code" class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10" placeholder="Optional - for password reset">
            </div>
            <div class="space-y-1.5 flex items-end pb-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" checked class="w-4 h-4 rounded border-border-subtle text-primary focus:ring-primary-container">
                    <span class="font-label-md text-label-md text-on-surface-variant">Active</span>
                </label>
            </div>
        </div>

        <div class="flex gap-4 pt-4">
            <button type="button" onclick="confirmSave()" class="px-8 py-3 bg-primary-container text-on-primary-container font-bold rounded-lg hover:brightness-95 transition-all shadow-sm">Save Employee</button>
            <a href="<?= BASE_URL ?>/admin/employees" class="px-8 py-3 border border-border-subtle rounded-lg font-bold text-secondary hover:bg-surface-muted transition-all">Cancel</a>
        </div>
    </form>
</div>
<script>
const deptPositions = <?= json_encode(array_column($departments, 'positions', 'id')) ?>;
document.querySelector('[name="department_id"]').addEventListener('change', function() {
    const dept = this.value;
    const sel = document.getElementById('position');
    sel.innerHTML = '<option value="">' + (dept ? 'Select Position' : 'Select Department First') + '</option>';
    if (dept && deptPositions[dept]) {
        JSON.parse(deptPositions[dept]).forEach(function(p) {
            if (p) sel.innerHTML += '<option value="' + p.replace(/"/g,'&quot;') + '">' + p + '</option>';
        });
    }
});
document.querySelector('[name="department_id"]').dispatchEvent(new Event('change'));
function togglePass(id, iconId) {
    const inp = document.getElementById(id);
    const icon = document.getElementById(iconId);
    if (inp.type === 'password') { inp.type = 'text'; icon.innerText = 'visibility_off'; }
    else { inp.type = 'password'; icon.innerText = 'visibility'; }
}
function confirmSave() {
    const pw = document.getElementById('password').value;
    const cp = document.getElementById('confirm_password').value;
    if (pw !== cp) {
        Swal.fire({ icon: 'error', title: 'Passwords do not match.' });
        return;
    }
    if (pw.length < 6) {
        Swal.fire({ icon: 'error', title: 'Password must be at least 6 characters.' });
        return;
    }
    Swal.fire({
        title: 'Save employee?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Save',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#006d43',
    }).then(r => { if (r.isConfirmed) document.querySelector('form').submit(); });
}
</script>
<style>main{background:linear-gradient(rgba(255,255,255,0.92),rgba(255,255,255,0.92)),url('<?= BASE_URL ?>/public/background/dashboard.jpeg') center/cover no-repeat fixed;min-height:100vh}</style>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

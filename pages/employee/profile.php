<?php
requireLogin();
$pageTitle = 'My Profile | HRMS Core';
$currentPage = 'employee_profile';
require_once __DIR__ . '/../../includes/header.php';

$userId = $_SESSION['user_id'];
$msg = $error = '';

$stmt = $pdo->prepare("
    SELECT e.*, d.name as department_name, e.position as position_title, u.email, u.role, u.is_active, u.last_login, u.created_at as user_created, u.code
    FROM employees e
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN users u ON e.user_id = u.id
    WHERE e.user_id = ?
");
$stmt->execute([$userId]);
$emp = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['change_password'])) {
        $secret = $_POST['secret_code'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $uCode = $emp['code'] ?? '';

        if (!$uCode || !password_verify($secret, $uCode)) {
            $error = 'Wrong code.';
        } elseif ($newPass !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif (strlen($newPass) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([password_hash($newPass, PASSWORD_DEFAULT), $userId]);
            $msg = 'Password changed successfully.';
        }
    }

    if (isset($_POST['change_email'])) {
        $secret = $_POST['secret_code_email'] ?? '';
        $newEmail = $_POST['new_email'] ?? '';
        $uCode = $emp['code'] ?? '';

        if (!$uCode || !password_verify($secret, $uCode)) {
            $error = 'Wrong code.';
        } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } else {
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check->execute([$newEmail, $userId]);
            if ($check->fetch()) {
                $error = 'Email already in use.';
            } else {
                $pdo->prepare("UPDATE users SET email = ? WHERE id = ?")->execute([$newEmail, $userId]);
                $msg = 'Email changed successfully.';
                $emp['email'] = $newEmail;
            }
        }
    }

    if (isset($_POST['set_code'])) {
        $code = $_POST['new_code'] ?? '';
        if (strlen($code) < 4) {
            $error = 'Code must be at least 4 characters.';
        } else {
            $pdo->prepare("UPDATE users SET code = ? WHERE id = ?")->execute([password_hash($code, PASSWORD_DEFAULT), $userId]);
            $msg = 'Code set successfully.';
        }
    }
}
?>
<div class="max-w-4xl mx-auto space-y-8">
    <div>
        <h2 class="font-headline-lg text-headline-lg text-on-surface">My Profile</h2>
        <p class="text-text-body font-body-md">Your personal and employment information.</p>
    </div>

    <?php if ($msg): ?>
        <div class="p-4 bg-green-100 text-green-700 rounded-lg font-semibold"><?= h($msg) ?></div><?php endif; ?>
    <?php if ($error): ?>
        <div class="p-4 bg-red-100 text-red-700 rounded-lg font-semibold"><?= h($error) ?></div><?php endif; ?>

    <?php if ($emp): ?>
        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-8">
            <div class="flex items-center gap-6">
                <div
                    class="w-20 h-20 rounded-full bg-primary-container flex items-center justify-center text-on-primary-container text-2xl font-bold">
                    <?= strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1)) ?>
                </div>
                <div>
                    <h3 class="font-headline-md text-headline-md text-on-surface">
                        <?= h($emp['first_name'] . ' ' . $emp['last_name']) ?>
                    </h3>
                    <p class="text-body-md text-secondary"><?= h($emp['position_title'] ?? 'N/A') ?> ·
                        <?= h($emp['department_name'] ?? 'N/A') ?>
                    </p>
                    <p class="text-label-sm text-secondary mt-1">Employee ID: <?= h($emp['employee_id']) ?></p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-6">
                <h4 class="font-label-md text-label-md uppercase tracking-wider font-bold text-secondary mb-4">Contact</h4>
                <div class="space-y-3 text-body-sm">
                    <div class="flex items-center gap-3"><span
                            class="material-symbols-outlined text-secondary">mail</span><span><?= h($emp['email']) ?></span>
                    </div>
                    <div class="flex items-center gap-3"><span
                            class="material-symbols-outlined text-secondary">call</span><span><?= h($emp['phone'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex items-center gap-3"><span
                            class="material-symbols-outlined text-secondary">home</span><span><?= h($emp['address'] ?? 'N/A') ?></span>
                    </div>
                </div>
            </div>
            <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-6">
                <h4 class="font-label-md text-label-md uppercase tracking-wider font-bold text-secondary mb-4">Employment
                </h4>
                <div class="space-y-3 text-body-sm">
                    <div class="flex justify-between"><span class="text-secondary">Employee ID</span><span
                            class="font-semibold"><?= h($emp['employee_id']) ?></span></div>
                    <div class="flex justify-between"><span class="text-secondary">Department</span><span
                            class="font-semibold"><?= h($emp['department_name'] ?? 'N/A') ?></span></div>
                    <div class="flex justify-between"><span class="text-secondary">Position</span><span
                            class="font-semibold"><?= h($emp['position_title'] ?? 'N/A') ?></span></div>
                    <div class="flex justify-between"><span class="text-secondary">Salary</span><span
                            class="font-semibold">₱<?= number_format($emp['salary'] ?? 0, 2) ?></span></div>
                    <div class="flex justify-between"><span class="text-secondary">Daily Rate</span><span
                            class="font-semibold">₱<?= number_format($emp['daily_rate'] ?? 0, 2) ?></span></div>
                    <div class="flex justify-between"><span class="text-secondary">Hire Date</span><span
                            class="font-semibold"><?= h($emp['hire_date'] ?? 'N/A') ?></span></div>
                    <div class="flex justify-between"><span class="text-secondary">Status</span><span
                            class="font-semibold capitalize"><?= h($emp['status']) ?></span></div>
                </div>
            </div>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-6">
            <h4 class="font-label-md text-label-md uppercase tracking-wider font-bold text-secondary mb-4">Account</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-3 text-body-sm">
                    <div class="flex justify-between"><span class="text-secondary">Email</span><span
                            class="font-semibold"><?= h($emp['email']) ?></span></div>
                    <div class="flex justify-between"><span class="text-secondary">Role</span><span
                            class="font-semibold capitalize"><?= h($emp['role']) ?></span></div>
                    <div class="flex justify-between"><span class="text-secondary">Active</span><span
                            class="font-semibold"><?= $emp['is_active'] ? 'Yes' : 'No' ?></span></div>
                </div>
                <div class="space-y-3 text-body-sm">
                    <div class="flex justify-between"><span class="text-secondary">Last Login</span><span
                            class="font-semibold"><?= $emp['last_login'] ? date('M d, Y h:i A', strtotime($emp['last_login'])) : 'Never' ?></span>
                    </div>
                    <div class="flex justify-between"><span class="text-secondary">Member Since</span><span
                            class="font-semibold"><?= date('M d, Y', strtotime($emp['user_created'])) ?></span></div>
                    <div class="flex justify-between"><span class="text-secondary">Employee Created</span><span
                            class="font-semibold"><?= date('M d, Y', strtotime($emp['created_at'])) ?></span></div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-4">
            <button onclick="openModal('password-modal')"
                class="px-6 py-3 bg-primary text-white font-bold rounded-lg hover:brightness-95 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined">lock</span> Change Password
            </button>
            <button onclick="openModal('email-modal')"
                class="px-6 py-3 bg-primary text-white font-bold rounded-lg hover:brightness-95 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined">mail</span> Change Email
            </button>
            <a href="<?= BASE_URL ?>/logout"
                class="px-6 py-3 bg-red-500 text-white font-bold rounded-lg hover:brightness-95 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined">logout</span> Logout
            </a>
        </div>

    <?php else: ?>
        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-12 text-center">
            <span class="material-symbols-outlined text-4xl text-secondary mb-4">person_off</span>
            <p class="text-secondary">No employee profile linked to your account. Contact your administrator.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Change Password Modal -->
<div id="password-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 overflow-hidden" onclick="event.stopPropagation()">
        <div class="p-6 border-b border-border-subtle flex items-center justify-between">
            <h3 class="font-headline-md text-headline-md">Change Password</h3>
            <button onclick="closeModal('password-modal')"
                class="w-8 h-8 rounded-lg border border-border-subtle flex items-center justify-center text-secondary hover:bg-surface-muted"><span
                    class="material-symbols-outlined text-lg">close</span></button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <div>
                <label class="text-xs text-secondary uppercase tracking-wider font-bold mb-1 block">Code</label>
                <input type="password" name="secret_code" required
                    class="w-full h-11 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary text-sm">
            </div>
            <div>
                <label class="text-xs text-secondary uppercase tracking-wider font-bold mb-1 block">New Password</label>
                <input type="password" name="new_password" required
                    class="w-full h-11 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary text-sm">
            </div>
            <div>
                <label class="text-xs text-secondary uppercase tracking-wider font-bold mb-1 block">Confirm
                    Password</label>
                <input type="password" name="confirm_password" required
                    class="w-full h-11 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary text-sm">
            </div>
            <button type="submit" name="change_password"
                class="w-full py-3 bg-primary text-white font-bold rounded-lg hover:brightness-95 transition-all">Change
                Password</button>
        </form>
    </div>
</div>

<!-- Change Email Modal -->
<div id="email-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 overflow-hidden" onclick="event.stopPropagation()">
        <div class="p-6 border-b border-border-subtle flex items-center justify-between">
            <h3 class="font-headline-md text-headline-md">Change Email</h3>
            <button onclick="closeModal('email-modal')"
                class="w-8 h-8 rounded-lg border border-border-subtle flex items-center justify-center text-secondary hover:bg-surface-muted"><span
                    class="material-symbols-outlined text-lg">close</span></button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <div>
                <label class="text-xs text-secondary uppercase tracking-wider font-bold mb-1 block">Current
                    Email</label>
                <div
                    class="w-full h-11 px-4 bg-surface-muted border border-border-subtle rounded-lg flex items-center text-sm">
                    <?= h($emp['email']) ?>
                </div>
            </div>
            <div>
                <label class="text-xs text-secondary uppercase tracking-wider font-bold mb-1 block">New Email</label>
                <input type="email" name="new_email" required
                    class="w-full h-11 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary text-sm">
            </div>
            <div>
                <label class="text-xs text-secondary uppercase tracking-wider font-bold mb-1 block">Code</label>
                <input type="password" name="secret_code_email" required
                    class="w-full h-11 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary text-sm">
            </div>
            <button type="submit" name="change_email"
                class="w-full py-3 bg-primary text-white font-bold rounded-lg hover:brightness-95 transition-all">Change
                Email</button>
        </form>
    </div>
</div>

<?php $pageScripts = '
<style>
main { background: linear-gradient(rgba(255,255,255,0.92), rgba(255,255,255,0.92)), url("' . BASE_URL . '/public/background/dashboard.jpeg") center/cover no-repeat fixed; min-height: 100vh; }
</style>
<script>
function openModal(id) { document.getElementById(id).classList.remove("hidden"); document.getElementById(id).classList.add("flex"); }
function closeModal(id) { document.getElementById(id).classList.add("hidden"); document.getElementById(id).classList.remove("flex"); }
document.querySelectorAll(".fixed.inset-0").forEach(el => { el.addEventListener("click", function(e) { if (e.target === this) closeModal(this.id); }); });
</script>';
require_once __DIR__ . '/../../includes/footer.php'; ?>
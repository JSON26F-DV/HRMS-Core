<?php
requireLogin();
requireAdmin();
$pageTitle = 'Settings | HRMS Core';
$currentPage = 'system_settings';
require_once __DIR__ . '/../../includes/header.php';

$userId = $_SESSION['user_id'];
$msg = '';

$settings = $pdo->query("SELECT * FROM system_settings ORDER BY setting_key")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_settings'])) {
        $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
        foreach ($_POST['settings'] as $key => $value) {
            $stmt->execute([$value, $key]);
        }
        $msg = 'Settings saved.';
    }

    if (isset($_POST['change_password'])) {
        $secret = $_POST['secret_code'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $user = $pdo->prepare("SELECT code FROM users WHERE id = ?");
        $user->execute([$userId]);
        $u = $user->fetch();

        if (!$u['code'] || !password_verify($secret, $u['code'])) {
            $msg = 'Wrong code.';
        } elseif ($newPass !== $confirm) {
            $msg = 'Passwords do not match.';
        } elseif (strlen($newPass) < 6) {
            $msg = 'Password must be at least 6 characters.';
        } else {
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([password_hash($newPass, PASSWORD_DEFAULT), $userId]);
            $msg = 'Password changed.';
        }
    }

    if (isset($_POST['set_code'])) {
        $code = $_POST['new_code'] ?? '';
        if (strlen($code) < 4) {
            $msg = 'Code must be at least 4 characters.';
        } else {
            $pdo->prepare("UPDATE users SET code = ? WHERE id = ?")->execute([password_hash($code, PASSWORD_DEFAULT), $userId]);
            $msg = 'Code set.';
        }
    }
}
?>
<div class="max-w-2xl mx-auto space-y-8">
    <div class="text-center">
        <h2 class="font-headline-lg text-headline-lg text-on-surface flex items-center gap-2"><img src="<?= BASE_URL ?>/public/emojis/Title%20emojis/settings.png" class="w-8 h-8" alt=""> System Settings</h2>
        <p class="text-text-body font-body-md">Configure system-wide settings and preferences.</p>
    </div>

    <?php if ($msg): ?>
        <div class="p-4 bg-primary-container/20 text-on-primary-container rounded-lg text-center font-semibold">
            <?= h($msg) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="max-w-2xl mx-auto">
        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-8 space-y-6">
            <h3 class="font-headline-md text-headline-md">General Settings</h3>
            <?php foreach ($settings as $s): ?>
                <div class="space-y-1.5">
                    <label
                        class="font-label-md text-label-md text-on-surface-variant capitalize"><?= h(str_replace('_', ' ', $s['setting_key'])) ?></label>
                    <input name="settings[<?= h($s['setting_key']) ?>]" value="<?= h($s['setting_value']) ?>"
                        class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container">
                </div>
            <?php endforeach; ?>
            <button type="submit" name="save_settings"
                class="px-8 py-3 bg-primary-container text-on-primary-container font-bold rounded-lg hover:brightness-95 transition-all shadow-sm">Save
                Settings</button>
        </div>
    </form>

    <div
        class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-8 space-y-6 max-w-2xl mx-auto">
        <h3 class="font-headline-md text-headline-md">Change Password</h3>
        <p class="text-text-body font-body-md">Enter your code to change your password.</p>
        <form method="POST" class="space-y-4">
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Code</label>
                <input type="password" name="secret_code"
                    class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container">
            </div>
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">New Password</label>
                <input type="password" name="new_password"
                    class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container">
            </div>
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">Confirm Password</label>
                <input type="password" name="confirm_password"
                    class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container">
            </div>
            <button type="submit" name="change_password"
                class="px-8 py-3 bg-primary-container text-on-primary-container font-bold rounded-lg hover:brightness-95 transition-all shadow-sm">Change
                Password</button>
        </form>
    </div>

    <div
        class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-8 space-y-6 max-w-2xl mx-auto">
        <h3 class="font-headline-md text-headline-md">Set Code</h3>
        <p class="text-text-body font-body-md">Set or update your code for password recovery.</p>
        <form method="POST" class="space-y-4">
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant">New Code</label>
                <input type="password" name="new_code"
                    class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container">
            </div>
            <button type="submit" name="set_code"
                class="px-8 py-3 bg-primary-container text-on-primary-container font-bold rounded-lg hover:brightness-95 transition-all shadow-sm">Save
                Code</button>
        </form>
    </div>

    <div class="text-center pb-8">
        <a href="<?= BASE_URL ?>/logout"
            class="btn btn-outline-danger d-inline-flex align-items-center gap-2 px-5 py-2.5">
            <span class="material-symbols-outlined text-lg">logout</span>
            Logout
        </a>
    </div>
</div>
<style>main{background:linear-gradient(rgba(255,255,255,0.92),rgba(255,255,255,0.92)),url('<?= BASE_URL ?>/public/background/dashboard.jpeg') center/cover no-repeat fixed;min-height:100vh}</style>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<?php
$pageTitle = 'Forgot Password | HRMS Core';
require_once __DIR__ . '/../../includes/config.php';

$msg = $error = '';
$step = $_GET['step'] ?? 'email';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_code'])) {
        $email = $_POST['email'] ?? '';
        $stmt = $pdo->prepare("SELECT id, code FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_user_id'] = $user['id'];
            $step = 'code';
        } else {
            $error = 'Email not found.';
        }
    }

    if (isset($_POST['verify_code'])) {
        $enteredCode = $_POST['code'] ?? '';
        $email = $_SESSION['reset_email'] ?? '';
        $stmt = $pdo->prepare("SELECT code FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && $user['code'] && password_verify($enteredCode, $user['code'])) {
            $step = 'reset';
        } else {
            $error = 'Invalid code.';
        }
    }

    if (isset($_POST['reset_password'])) {
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $email = $_SESSION['reset_email'] ?? '';
        if ($newPass !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif (strlen($newPass) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?")->execute([password_hash($newPass, PASSWORD_DEFAULT), $email]);
            unset($_SESSION['reset_email'], $_SESSION['reset_user_id']);
            $msg = 'Password reset. <a href="' . BASE_URL . '/login" class="text-primary font-bold hover:underline">Login</a>';
            $step = 'done';
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password | HRMS Core</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Inter', sans-serif; background-color: #F8FAFC; }
    .card { box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); }
    .input-transition { transition: all 0.2s ease-in-out; }
</style>
<script>
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                "surface-container-lowest": "#ffffff", "primary-fixed-dim": "#00e290",
                "surface-muted": "#F8FAFC", "secondary-container": "#d1e5f8",
                "surface-container": "#e5eeff", "secondary": "#4e6070",
                "background": "#f8f9ff", "error-container": "#ffdad6",
                "primary": "#006d43", "primary-container": "#2ff29e",
                "on-primary-container": "#006a41", "text-body": "#475569",
                "border-subtle": "#E2E8F0", "text-heading": "#0F172A",
                "on-surface": "#0b1c30", "on-surface-variant": "#3b4a40",
                "outline": "#6b7b6f"
            },
            borderRadius: { lg: "0.5rem", xl: "0.75rem", "2xl": "1rem", full: "9999px" },
            spacing: { "stack-lg": "32px", "stack-md": "16px", "stack-sm": "8px", "margin-desktop": "32px", "margin-mobile": "16px", "gutter": "24px" },
            fontFamily: { "headline-md": ["Inter"], "label-md": ["Inter"], "body-md": ["Inter"], "body-sm": ["Inter"], "label-sm": ["Inter"] }
        }
    }
};
</script>
</head>
<body class="min-h-screen flex items-center justify-center p-4 md:p-8 bg-background">
    <div class="w-full max-w-[440px] bg-surface-container-lowest rounded-2xl p-8 md:p-10 card">
        <?php if ($error): ?>
        <div class="mb-4 p-3 bg-error-container text-on-error-container rounded-lg text-sm font-medium"><?= h($error) ?></div>
        <?php endif; ?>
        <?php if ($msg): ?>
        <div class="mb-4 p-3 bg-primary-container/20 text-on-primary-container rounded-lg text-sm font-medium"><?= $msg ?></div>
        <?php endif; ?>

        <?php if ($step === 'email'): ?>
        <div class="flex flex-col items-center mb-8">
            <div class="w-14 h-14 bg-primary-container rounded-xl flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-primary text-2xl" style="font-variation-settings: 'FILL' 1;">lock_reset</span>
            </div>
            <h1 class="font-headline-md text-headline-md text-on-surface mb-1">Forgot Password</h1>
            <p class="font-body-sm text-body-sm text-secondary text-center">Enter your email to find your account.</p>
        </div>
        <form class="space-y-5" method="POST">
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant" for="email">Email Address</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">mail</span>
                    <input class="w-full h-12 pl-12 pr-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10 input-transition" id="email" name="email" placeholder="e.g. name@company.com" required type="email">
                </div>
            </div>
            <button class="w-full h-12 bg-primary-container text-on-primary-container font-bold rounded-lg hover:brightness-95 transition-all" type="submit" name="send_code">
                <span class="flex items-center justify-center gap-2">
                    <span>Continue</span>
                    <span class="material-symbols-outlined">arrow_forward</span>
                </span>
            </button>
        </form>

        <?php elseif ($step === 'code'): ?>
        <div class="flex flex-col items-center mb-8">
            <div class="w-14 h-14 bg-primary-container rounded-xl flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-primary text-2xl" style="font-variation-settings: 'FILL' 1;">pin</span>
            </div>
            <h1 class="font-headline-md text-headline-md text-on-surface mb-1">Enter Code</h1>
            <p class="font-body-sm text-body-sm text-secondary text-center">Enter the code associated with your account.</p>
        </div>
        <form class="space-y-5" method="POST">
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant" for="code">Code</label>
                <input class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10 input-transition text-center text-lg tracking-widest" id="code" name="code" placeholder="Enter your code" required>
            </div>
            <button class="w-full h-12 bg-primary-container text-on-primary-container font-bold rounded-lg hover:brightness-95 transition-all" type="submit" name="verify_code">
                <span class="flex items-center justify-center gap-2">
                    <span>Verify</span>
                    <span class="material-symbols-outlined">check</span>
                </span>
            </button>
        </form>

        <?php elseif ($step === 'reset'): ?>
        <div class="flex flex-col items-center mb-8">
            <div class="w-14 h-14 bg-primary-container rounded-xl flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-primary text-2xl" style="font-variation-settings: 'FILL' 1;">lock_open</span>
            </div>
            <h1 class="font-headline-md text-headline-md text-on-surface mb-1">Reset Password</h1>
            <p class="font-body-sm text-body-sm text-secondary text-center">Choose a new password.</p>
        </div>
        <form class="space-y-5" method="POST">
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant" for="new_password">New Password</label>
                <input class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10 input-transition" id="new_password" name="new_password" placeholder="Min 6 characters" required type="password">
            </div>
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant" for="confirm_password">Confirm Password</label>
                <input class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10 input-transition" id="confirm_password" name="confirm_password" placeholder="Repeat password" required type="password">
            </div>
            <button class="w-full h-12 bg-primary-container text-on-primary-container font-bold rounded-lg hover:brightness-95 transition-all" type="submit" name="reset_password">
                <span class="flex items-center justify-center gap-2">
                    <span>Reset Password</span>
                    <span class="material-symbols-outlined">check</span>
                </span>
            </button>
        </form>

        <?php elseif ($step === 'done'): ?>
        <div class="flex flex-col items-center mb-8">
            <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-green-600 text-2xl" style="font-variation-settings: 'FILL' 1;">check_circle</span>
            </div>
            <h1 class="font-headline-md text-headline-md text-on-surface mb-1">Done!</h1>
            <p class="font-body-sm text-body-sm text-secondary text-center">Your password has been reset.</p>
        </div>
        <?php endif; ?>

        <div class="mt-6 text-center">
            <a class="text-primary font-semibold text-sm hover:underline" href="<?= BASE_URL ?>/login">
                <span class="flex items-center justify-center gap-1">
                    <span class="material-symbols-outlined text-sm">arrow_back</span>
                    Back to Login
                </span>
            </a>
        </div>
    </div>
</body>
</html>

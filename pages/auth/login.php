<?php
$pageTitle = 'Login | HRMS Core';
redirectIfLoggedIn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        $stmt = $pdo->prepare("SELECT e.first_name, e.last_name FROM employees e WHERE e.user_id = ? LIMIT 1");
        $stmt->execute([$user['id']]);
        $emp = $stmt->fetch();
        $_SESSION['user_name'] = $emp ? $emp['first_name'] . ' ' . $emp['last_name'] : ($user['email']);

        $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

        $path = in_array($user['role'], ['admin', 'hr']) ? '/admin/dashboard' : '/employee/dashboard';
        header('Location: ' . BASE_URL . $path);
        exit;
    }

    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/public/logo.png">
    <title>Login | HRMS Core</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "surface-container-lowest": "#ffffff", "primary-fixed-dim": "#00e290",
                        "surface-muted": "#F8FAFC", "secondary-container": "#d1e5f8",
                        "surface-container": "#e5eeff", "secondary": "#4e6070",
                        "on-secondary-fixed-variant": "#374958", "background": "#f8f9ff",
                        "on-tertiary-container": "#765700", "error-container": "#ffdad6",
                        "on-error": "#ffffff", "on-tertiary": "#ffffff",
                        "inverse-primary": "#00e290", "on-primary-fixed-variant": "#005231",
                        "on-secondary-container": "#546677", "on-surface-variant": "#3b4a40",
                        "on-background": "#0b1c30", "on-secondary": "#ffffff",
                        "tertiary-fixed": "#ffdea1", "surface": "#f8f9ff",
                        "surface-variant": "#d3e4fe", "on-surface": "#0b1c30",
                        "outline": "#6b7b6f", "surface-container-highest": "#d3e4fe",
                        "error": "#ba1a1a", "on-tertiary-fixed": "#261900",
                        "primary": "#006d43", "inverse-on-surface": "#eaf1ff",
                        "on-secondary-fixed": "#091d2b", "surface-tint": "#006d43",
                        "on-primary-fixed": "#002111", "inverse-surface": "#213145",
                        "secondary-fixed-dim": "#b5c9db", "on-tertiary-fixed-variant": "#5c4300",
                        "surface-container-high": "#dce9ff", "outline-variant": "#bacbbd",
                        "surface-container-low": "#eff4ff", "tertiary": "#7a5900",
                        "primary-container": "#2ff29e", "secondary-fixed": "#d1e5f8",
                        "on-primary-container": "#006a41", "tertiary-fixed-dim": "#efc05a",
                        "text-body": "#475569", "surface-background": "#FFFFFF",
                        "border-subtle": "#E2E8F0", "primary-fixed": "#52ffac",
                        "tertiary-container": "#ffce67", "surface-bright": "#f8f9ff",
                        "on-primary": "#ffffff", "on-error-container": "#93000a",
                        "text-heading": "#0F172A", "surface-dim": "#cbdbf5"
                    },
                    borderRadius: { DEFAULT: "0.25rem", lg: "0.5rem", xl: "0.75rem", "2xl": "1rem", full: "9999px" },
                    spacing: { "container-max": "1440px", "stack-lg": "32px", "gutter": "24px", "margin-desktop": "32px", "stack-sm": "8px", "stack-md": "16px", "margin-mobile": "16px", "unit": "8px" },
                    fontFamily: { "display-lg": ["Inter"], "label-md": ["Inter"], "body-md": ["Inter"], "label-sm": ["Inter"], "headline-md": ["Inter"], "headline-lg": ["Inter"], "body-lg": ["Inter"], "headline-lg-mobile": ["Inter"], "body-sm": ["Inter"] }
                }
            }
        };
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F8FAFC;
        }

        .login-card {
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }

        .input-transition {
            transition: all 0.2s ease-in-out;
        }

        .glass-accent {
            background: rgba(47, 242, 158, 0.05);
            backdrop-filter: blur(8px);
        }

        .cssbuttons-io-button {
            background: #75eea5;
            color: white;
            font-family: inherit;
            padding: 0.35em;
            padding-left: 1.2em;
            font-size: 17px;
            font-weight: 500;
            border-radius: 0.9em;
            border: none;
            letter-spacing: 0.05em;
            display: flex;
            align-items: center;
            box-shadow: inset 0 0 1.6em -0.6em #006d43;
            overflow: hidden;
            position: relative;
            height: 2.8em;
            padding-right: 3.3em;
            cursor: pointer;
        }

        .cssbuttons-io-button .icon {
            background: white;
            margin-left: 1em;
            position: absolute;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 2.2em;
            width: 2.2em;
            border-radius: 0.7em;
            box-shadow: 0.1em 0.1em 0.6em 0.2em #006d43;
            right: 0.3em;
            transition: all 0.3s;
        }

        .cssbuttons-io-button:hover .icon {
            width: calc(100% - 0.6em);
        }

        .cssbuttons-io-button .icon svg {
            width: 1.1em;
            transition: transform 0.3s;
            color: #006d43;
        }

        .cssbuttons-io-button:hover .icon svg {
            transform: translateX(0.1em);
        }

        .cssbuttons-io-button:active .icon {
            transform: scale(0.95);
        }
    </style>
</head>

<body
    class="login-page min-h-screen flex items-center justify-center p-margin-mobile md:p-margin-desktop overflow-hidden relative">
    <div
        class="absolute top-[-10%] right-[-5%] w-[400px] h-[400px] rounded-full bg-primary-container opacity-10 blur-[100px] pointer-events-none">
    </div>
    <div
        class="absolute bottom-[-10%] left-[-5%] w-[300px] h-[300px] rounded-full bg-surface-variant opacity-20 blur-[80px] pointer-events-none">
    </div>
    <div
        class="login-card w-full max-w-[440px] bg-surface-container-lowest rounded-2xl p-stack-lg md:p-10 relative z-10">
        <div class="flex flex-col items-center mb-stack-lg">
            <img src="<?= BASE_URL ?>/public/logo.png" class="h-16 w-auto rounded-lg mb-stack-md" alt="HRMS Core">
            <h1 class="font-headline-md text-headline-md text-on-surface mb-1">HRMS Core</h1>
            <p class="font-body-sm text-body-sm text-secondary">Management Portal Login</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="mb-4 p-3 bg-error-container text-on-error-container rounded-lg text-sm font-medium"><?= h($error) ?>
            </div>
        <?php endif; ?>

        <form class="login-form space-y-stack-md" method="POST" action="<?= BASE_URL ?>/login">
            <div class="space-y-1.5">
                <label class="font-label-md text-label-md text-on-surface-variant px-1" for="email">Email
                    Address</label>
                <div class="relative">
                    <span
                        class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline text-[20px]">mail</span>
                    <input
                        class="w-full h-12 pl-12 pr-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10 input-transition font-body-md text-body-md"
                        id="email" name="email" placeholder="e.g. name@company.com" required type="email">
                </div>
            </div>
            <div class="space-y-1.5">
                <div class="flex justify-between items-center px-1">
                    <label class="font-label-md text-label-md text-on-surface-variant" for="password">Password</label>
                    <a class="font-label-sm text-label-sm text-primary hover:underline transition-all"
                        href="<?= BASE_URL ?>/forgot-password">Forgot Password?</a>
                </div>
                <div class="relative">
                    <span
                        class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline text-[20px]">lock</span>
                    <input
                        class="w-full h-12 pl-12 pr-12 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10 input-transition font-body-md text-body-md"
                        id="password" name="password" placeholder="Enter your password" required type="password">
                    <button
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-outline hover:text-primary transition-colors"
                        onclick="togglePassword()" type="button">
                        <span class="material-symbols-outlined text-[20px]" id="pass-icon">visibility</span>
                    </button>
                </div>
            </div>
            <div class="flex items-center space-x-2 py-1">
                <input
                    class="w-4 h-4 rounded border-border-subtle text-primary focus:ring-primary-container cursor-pointer"
                    id="remember" name="remember" type="checkbox">
                <label class="font-body-sm text-body-sm text-secondary cursor-pointer select-none"
                    for="remember">Remember this device</label>
            </div>
            <button class="cssbuttons-io-button w-full mt-stack-sm p-5! flex items-center justify-center" type="submit">
                <span>Login to Dashboard</span>
                <div class="icon">
                    <svg height="24" width="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0 0h24v24H0z" fill="none"></path>
                        <path d="M16.172 11l-5.364-5.364 1.414-1.414L20 12l-7.778 7.778-1.414-1.414L16.172 13H4v-2z"
                            fill="currentColor"></path>
                    </svg>
                </div>
            </button>
        </form>
        <div class="mt-stack-lg pt-stack-lg border-t border-border-subtle text-center">
            <p class="font-body-sm text-body-sm text-secondary">
                New to HRMS Core? <a class="text-primary font-semibold hover:underline" href="#">Contact
                    Administrator</a>
            </p>
        </div>
    </div>
    <div class="hidden lg:block fixed right-0 top-0 h-full w-[35%] overflow-hidden pointer-events-none">
        <div class="w-full h-full bg-cover bg-center grayscale-[0.2] opacity-50"
            style="background-image: url('https://images.unsplash.com/photo-1497366216548-37526070297c?w=800&q=80')">
        </div>
        <div class="absolute inset-0 bg-gradient-to-l from-transparent to-background"></div>
    </div>
    <script>
        function togglePassword() {
            const passInput = document.getElementById('password');
            const icon = document.getElementById('pass-icon');
            if (passInput.type === 'password') {
                passInput.type = 'text';
                icon.innerText = 'visibility_off';
            } else {
                passInput.type = 'password';
                icon.innerText = 'visibility';
            }
        }
    </script>
</body>

</html>
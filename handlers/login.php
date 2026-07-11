<?php
require_once __DIR__ . '/../includes/config.php';

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password_hash'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['role'] = $user['role'];

    $stmt = $pdo->prepare("SELECT first_name, last_name FROM employees WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user['id']]);
    $emp = $stmt->fetch();
    $_SESSION['user_name'] = $emp ? $emp['first_name'] . ' ' . $emp['last_name'] : $user['email'];

        $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
        logAudit('login', 'user', $user['id'], 'User logged in');

        $path = $user['role'] === 'admin' ? '/admin/dashboard' : '/employee/dashboard';
    header('Location: ' . BASE_URL . $path);
} else {
    $_SESSION['_errors']['login'] = 'Invalid email or password.';
    header('Location: ' . BASE_URL . '/login');
}
exit;

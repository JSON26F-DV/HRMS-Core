<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
requireAdmin();

$stmt = $pdo->prepare("SELECT id, email FROM users WHERE deleted_at IS NOT NULL AND deleted_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stmt->execute();
$expired = $stmt->fetchAll();

foreach ($expired as $user) {
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user['id']]);
}

$_SESSION['_flash'] = ['success' => count($expired) . ' expired employee(s) permanently deleted.'];
header('Location: ' . BASE_URL . '/admin/employees?show=deleted');
exit;

<?php
require_once __DIR__ . '/../includes/config.php';
logAudit('logout', 'user', $_SESSION['user_id'] ?? null, 'User logged out');
session_destroy();
header('Location: ' . BASE_URL . '/login');
exit;

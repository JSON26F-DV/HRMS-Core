<?php
require_once __DIR__ . '/../includes/config.php';
try { logAudit('logout', 'user', $_SESSION['user_id'] ?? null, 'User logged out'); } catch (Exception $e) {}
session_destroy();
header('Location: ' . BASE_URL . '/login');
exit;

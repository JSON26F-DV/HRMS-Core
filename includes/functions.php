<?php
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return ($_SESSION['role'] ?? '') === 'admin';
}

function isHr(): bool {
    return ($_SESSION['role'] ?? '') === 'hr';
}

function isHrOrAdmin(): bool {
    return isAdmin() || isHr();
}

function isEmployee(): bool {
    return ($_SESSION['role'] ?? '') === 'employee';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . '/dashboard');
        exit;
    }
}

function requireHrOrAdmin(): void {
    requireLogin();
    if (!isHrOrAdmin()) {
        header('Location: ' . BASE_URL . '/dashboard');
        exit;
    }
}

function redirectIfLoggedIn(): void {
    if (isLoggedIn()) {
        $role = $_SESSION['role'] ?? 'employee';
        $path = in_array($role, ['admin', 'hr']) ? '/admin/dashboard' : '/employee/dashboard';
        header('Location: ' . BASE_URL . $path);
        exit;
    }
}

function h(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function asset(string $path): string {
    return BASE_URL . '/public/' . ltrim($path, '/');
}

function old(string $key, ?string $default = ''): string {
    return h($_SESSION['_old'][$key] ?? $default);
}

function error(string $key): string {
    return $_SESSION['_errors'][$key] ?? '';
}

function flash(string $key): string {
    $val = $_SESSION['_flash'][$key] ?? '';
    unset($_SESSION['_flash'][$key]);
    return $val;
}

function logAudit(string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void {
    global $pdo;
    if (!$pdo) return;
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'] ?? null, $action, $entityType, $entityId, $details, $_SERVER['REMOTE_ADDR'] ?? null]);
}

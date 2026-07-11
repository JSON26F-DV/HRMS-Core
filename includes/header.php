<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle ?? 'HRMS Core') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Inter', sans-serif; background-color: #f8f9ff; }
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; vertical-align: middle; }
    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: #f1f5f9; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
<script>
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                "surface-container-lowest": "#ffffff",
                "surface-muted": "#F8FAFC",
                "surface-container": "#e5eeff",
                "surface-container-low": "#eff4ff",
                "secondary": "#4e6070",
                "background": "#f8f9ff",
                "on-surface": "#0b1c30",
                "error": "#ba1a1a",
                "primary": "#006d43",
                "on-primary": "#ffffff",
                "primary-container": "#2ff29e",
                "on-primary-container": "#006a41",
                "text-body": "#475569",
                "text-heading": "#0F172A",
                "border-subtle": "#E2E8F0",
            },
            borderRadius: {
                DEFAULT: "0.25rem", lg: "0.5rem", xl: "0.75rem", "2xl": "1rem", full: "9999px"
            },
            fontFamily: {
                "display-lg": ["Inter"], "headline-md": ["Inter"], "headline-lg": ["Inter"],
                "body-md": ["Inter"], "body-sm": ["Inter"], "label-sm": ["Inter"]
            },
            fontSize: {
                "display-lg": ["48px", { lineHeight: "56px", letterSpacing: "-0.04em", fontWeight: "700" }],
                "headline-md": ["24px", { lineHeight: "32px", letterSpacing: "-0.01em", fontWeight: "600" }],
                "headline-lg": ["32px", { lineHeight: "40px", letterSpacing: "-0.02em", fontWeight: "600" }],
                "body-md": ["16px", { lineHeight: "24px", fontWeight: "400" }],
                "body-sm": ["14px", { lineHeight: "20px", fontWeight: "400" }],
                "label-sm": ["12px", { lineHeight: "16px", letterSpacing: "0.05em", fontWeight: "600" }]
            }
        }
    }
};
</script>
<style>
.bg-neutral-primary { background-color: #ffffff; }
.bg-neutral-primary-medium { background-color: #f8fafc; }
.bg-neutral-secondary-soft { background-color: #f1f5f9; }
.neutral-primary-soft { background-color: #f8fafc; }
.neutral-secondary-medium { background-color: #e2e8f0; }
.neutral-tertiary { background-color: #f1f5f9; }
.neutral-tertiary-medium { background-color: #e2e8f0; }
.default { border-color: #e2e8f0; }
.default-medium { border-color: #cbd5e1; }
.fg-brand { color: #006d43; }
.text-fg-brand { color: #006d43; }
.fg-disabled { color: #94a3b8; }
.fg-danger-strong { color: #dc2626; }
.danger-soft { background-color: #fef2f2; }
.danger-subtle { border-color: #fecaca; }
.heading { color: #0f172a; }
.body { color: #475569; }
.rounded-base { border-radius: 0.5rem; }
.bg-brand { background-color: #006d43; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var btn = document.querySelector('[data-drawer-toggle="default-sidebar"]');
    var sidebar = document.getElementById('default-sidebar');
    if (btn && sidebar) {
        btn.addEventListener('click', function() {
            sidebar.classList.toggle('-translate-x-full');
        });
    }
});
</script>
<?php if (isset($pageStyles)) echo $pageStyles; ?>
</head>
<body class="bg-background text-on-surface antialiased">
<?php if (isLoggedIn()): ?>
<?php
$role = $_SESSION['role'] ?? '';
require_once __DIR__ . '/topbar.php';
if ($role === 'admin') {
    require_once __DIR__ . '/sidebar_admin.php';
} elseif ($role === 'employee') {
    require_once __DIR__ . '/sidebar_employee.php';
}
?>
<?php endif; ?>
<div class="<?= isLoggedIn() ? 'sm:ml-64 pt-16' : '' ?>">
<main class="<?= isLoggedIn() ? 'p-8' : '' ?>">

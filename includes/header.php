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
    .glass-card { background: rgba(255,255,255,0.8); backdrop-filter: blur(8px); border: 1px solid #E2E8F0; }
    .stats-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .stats-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); }
    .soft-elevated { box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); }
</style>
<script>
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                "surface-container-lowest": "#ffffff",
                "primary-fixed-dim": "#00e290",
                "surface-muted": "#F8FAFC",
                "secondary-container": "#d1e5f8",
                "surface-container": "#e5eeff",
                "secondary": "#4e6070",
                "on-secondary-fixed-variant": "#374958",
                "background": "#f8f9ff",
                "on-tertiary-container": "#765700",
                "error-container": "#ffdad6",
                "on-error": "#ffffff",
                "on-tertiary": "#ffffff",
                "inverse-primary": "#00e290",
                "on-primary-fixed-variant": "#005231",
                "on-secondary-container": "#546677",
                "on-surface-variant": "#3b4a40",
                "on-background": "#0b1c30",
                "on-secondary": "#ffffff",
                "tertiary-fixed": "#ffdea1",
                "surface": "#f8f9ff",
                "surface-variant": "#d3e4fe",
                "on-surface": "#0b1c30",
                "outline": "#6b7b6f",
                "surface-container-highest": "#d3e4fe",
                "error": "#ba1a1a",
                "on-tertiary-fixed": "#261900",
                "primary": "#006d43",
                "inverse-on-surface": "#eaf1ff",
                "on-secondary-fixed": "#091d2b",
                "surface-tint": "#006d43",
                "on-primary-fixed": "#002111",
                "inverse-surface": "#213145",
                "secondary-fixed-dim": "#b5c9db",
                "on-tertiary-fixed-variant": "#5c4300",
                "surface-container-high": "#dce9ff",
                "outline-variant": "#bacbbd",
                "surface-container-low": "#eff4ff",
                "tertiary": "#7a5900",
                "primary-container": "#2ff29e",
                "secondary-fixed": "#d1e5f8",
                "on-primary-container": "#006a41",
                "tertiary-fixed-dim": "#efc05a",
                "text-body": "#475569",
                "surface-background": "#FFFFFF",
                "border-subtle": "#E2E8F0",
                "primary-fixed": "#52ffac",
                "tertiary-container": "#ffce67",
                "surface-bright": "#f8f9ff",
                "on-primary": "#ffffff",
                "on-error-container": "#93000a",
                "text-heading": "#0F172A",
                "surface-dim": "#cbdbf5"
            },
            borderRadius: {
                DEFAULT: "0.25rem", lg: "0.5rem", xl: "0.75rem", "2xl": "1rem", full: "9999px"
            },
            spacing: {
                "container-max": "1440px", "stack-lg": "32px", "gutter": "24px",
                "margin-desktop": "32px", "stack-sm": "8px", "stack-md": "16px",
                "margin-mobile": "16px", "unit": "8px"
            },
            fontFamily: {
                "display-lg": ["Inter"], "label-md": ["Inter"], "body-md": ["Inter"],
                "label-sm": ["Inter"], "headline-md": ["Inter"], "headline-lg": ["Inter"],
                "body-lg": ["Inter"], "headline-lg-mobile": ["Inter"], "body-sm": ["Inter"]
            },
            fontSize: {
                "display-lg": ["48px", { lineHeight: "56px", letterSpacing: "-0.04em", fontWeight: "700" }],
                "label-md": ["14px", { lineHeight: "20px", letterSpacing: "0.01em", fontWeight: "500" }],
                "body-md": ["16px", { lineHeight: "24px", fontWeight: "400" }],
                "label-sm": ["12px", { lineHeight: "16px", letterSpacing: "0.05em", fontWeight: "600" }],
                "headline-md": ["24px", { lineHeight: "32px", letterSpacing: "-0.01em", fontWeight: "600" }],
                "headline-lg": ["32px", { lineHeight: "40px", letterSpacing: "-0.02em", fontWeight: "600" }],
                "body-lg": ["18px", { lineHeight: "28px", fontWeight: "400" }],
                "body-sm": ["14px", { lineHeight: "20px", fontWeight: "400" }]
            }
        }
    }
};
</script>
<?php if (isset($pageStyles)) echo $pageStyles; ?>
</head>
<body class="bg-background text-on-surface antialiased">
<?php
$role = $_SESSION['role'] ?? '';
if ($role === 'admin') {
    require_once __DIR__ . '/sidebar_admin.php';
} elseif ($role === 'employee') {
    require_once __DIR__ . '/sidebar_employee.php';
}
?>
<div class="<?= isLoggedIn() ? 'ml-72' : '' ?>">
<?php if (isLoggedIn()) require_once __DIR__ . '/topbar.php'; ?>
<main class="<?= isLoggedIn() ? 'p-8' : '' ?>">

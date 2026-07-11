<?php
$pageTitle = '404 Not Found | HRMS Core';
http_response_code(404);
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>404 Not Found | HRMS Core</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Inter', sans-serif; background-color: #f8f9ff; }
    .glass-card { background: rgba(255,255,255,0.8); backdrop-filter: blur(8px); border: 1px solid #E2E8F0; }
</style>
<script>
tailwind.config = {
    darkMode: "class",
    theme: { extend: { colors: { "surface-container-lowest": "#ffffff", "primary": "#006d43", "primary-container": "#2ff29e", "on-primary-container": "#006a41", "secondary": "#4e6070", "background": "#f8f9ff", "border-subtle": "#E2E8F0", "text-heading": "#0F172A", "text-body": "#475569", "on-surface": "#0b1c30" }, fontFamily: { "headline-lg": ["Inter"], "body-md": ["Inter"], "label-sm": ["Inter"] } } }
};
</script>
</head>
<body class="bg-background text-on-surface min-h-screen flex items-center justify-center p-4">
    <div class="text-center max-w-md">
        <div class="w-24 h-24 bg-primary-container/20 rounded-full flex items-center justify-center mx-auto mb-8">
            <span class="material-symbols-outlined text-primary text-5xl">search_off</span>
        </div>
        <h1 class="font-headline-lg text-headline-lg text-text-heading mb-3">Page Not Found</h1>
        <p class="text-body-md text-text-body mb-8">The page you're looking for doesn't exist or has been moved.</p>
        <div class="flex gap-4 justify-center">
            <a href="<?= BASE_URL ?>/login" class="px-6 py-3 bg-primary-container text-on-primary-container font-bold rounded-lg hover:brightness-95 transition-all">Go Home</a>
            <a href="javascript:history.back()" class="px-6 py-3 border border-border-subtle rounded-lg font-bold text-secondary hover:bg-surface-container-low transition-all">Go Back</a>
        </div>
    </div>
</body>
</html>

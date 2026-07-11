<header class="h-16 sticky top-0 z-40 bg-surface-container-lowest border-b border-border-subtle shadow-sm flex justify-between items-center px-gutter" style="margin-left: 288px; max-width: calc(100% - 288px);">
    <div class="flex items-center bg-surface-container-low rounded-lg px-3 py-1.5 w-96">
        <span class="material-symbols-outlined text-secondary text-lg">search</span>
        <input class="bg-transparent border-none focus:ring-0 text-label-md w-full ml-2 text-on-surface-variant" placeholder="Search employees or reports..." type="text">
    </div>
    <div class="flex items-center gap-6">
        <div class="flex items-center gap-4">
            <button class="relative p-2 text-secondary hover:bg-surface-container-low rounded-lg transition-colors">
                <span class="material-symbols-outlined">notifications</span>
                <span class="absolute top-2 right-2 w-2 h-2 bg-error rounded-full"></span>
            </button>
            <button class="p-2 text-secondary hover:bg-surface-container-low rounded-lg transition-colors">
                <span class="material-symbols-outlined">help</span>
            </button>
        </div>
        <div class="h-8 w-px bg-border-subtle"></div>
        <div class="flex items-center gap-3 cursor-pointer group">
            <div class="text-right">
                <p class="font-label-md text-label-md text-on-surface"><?= h($_SESSION['user_name'] ?? 'Admin User') ?></p>
                <p class="text-[10px] text-secondary font-bold uppercase tracking-wider"><?= h($_SESSION['role'] ?? 'User') ?></p>
            </div>
            <div class="w-10 h-10 rounded-full border-2 border-primary-container bg-primary-container flex items-center justify-center text-on-primary-container font-bold">
                <?= strtoupper(substr($_SESSION['user_name'] ?? 'AU', 0, 2)) ?>
            </div>
        </div>
    </div>
</header>

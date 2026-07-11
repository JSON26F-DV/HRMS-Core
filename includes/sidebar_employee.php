<aside class="h-screen w-72 fixed left-0 top-0 bg-surface-container-lowest border-r border-border-subtle flex flex-col py-6 px-4 z-50">
    <div class="mb-10 px-4">
        <h1 class="font-headline-md text-headline-md font-bold text-on-surface">HRMS Core</h1>
        <p class="text-secondary text-sm">Employee Portal</p>
    </div>
    <nav class="flex-grow space-y-1 overflow-y-auto no-scrollbar">
        <a class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 <?= $currentPage === 'employee_dashboard' ? 'text-primary font-bold bg-surface-container-low border-r-4 border-primary' : 'text-secondary hover:bg-surface-container hover:text-primary' ?>" href="<?= BASE_URL ?>/employee/dashboard">
            <span class="material-symbols-outlined mr-3 <?= $currentPage === 'employee_dashboard' ? "style='font-variation-settings: \"FILL\" 1;'" : '' ?>">dashboard</span>
            <span class="font-body-md">Dashboard</span>
        </a>
        <a class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 <?= $currentPage === 'my_payslips' ? 'text-primary font-bold bg-surface-container-low border-r-4 border-primary' : 'text-secondary hover:bg-surface-container hover:text-primary' ?>" href="<?= BASE_URL ?>/employee/my_payslips">
            <span class="material-symbols-outlined mr-3 <?= $currentPage === 'my_payslips' ? "style='font-variation-settings: \"FILL\" 1;'" : '' ?>">payments</span>
            <span class="font-body-md">My Payslips</span>
        </a>
        <a class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 <?= $currentPage === 'employee_profile' ? 'text-primary font-bold bg-surface-container-low border-r-4 border-primary' : 'text-secondary hover:bg-surface-container hover:text-primary' ?>" href="<?= BASE_URL ?>/employee/profile">
            <span class="material-symbols-outlined mr-3 <?= $currentPage === 'employee_profile' ? "style='font-variation-settings: \"FILL\" 1;'" : '' ?>">person</span>
            <span class="font-body-md">My Profile</span>
        </a>
    </nav>
    <div class="mt-auto pt-6 border-t border-border-subtle">
        <a href="<?= BASE_URL ?>/logout" class="flex items-center px-4 py-3 text-secondary hover:text-error transition-colors rounded-lg">
            <span class="material-symbols-outlined mr-3">logout</span>
            <span class="font-body-md">Logout</span>
        </a>
    </div>
</aside>

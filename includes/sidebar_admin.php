<aside
    class="h-screen w-72 fixed left-0 top-0 bg-surface-container-lowest border-r border-border-subtle flex flex-col py-gutter px-4 z-50 overflow-y-auto">
    <div class="mb-8 px-2 flex items-center gap-3">
        <div class="w-10 h-10 bg-primary-container rounded-lg flex items-center justify-center">
            <span class="material-symbols-outlined text-on-primary-container"
                style="font-variation-settings: 'FILL' 1;">domain</span>
        </div>
        <div>
            <h1 class="font-headline-md text-headline-md font-bold text-on-surface">HRMS Core</h1>
            <p class="text-label-sm text-secondary">Management Portal</p>
        </div>
    </div>
    <nav class="space-y-1">
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 <?= $currentPage === 'dashboard' ? 'text-primary font-bold bg-surface-container-low border-r-4 border-primary' : 'text-secondary hover:bg-surface-container hover:text-primary' ?>"
            href="<?= BASE_URL ?>/admin/dashboard">
            <span
                class="material-symbols-outlined <?= $currentPage === 'dashboard' ? "style='font-variation-settings: \"FILL\" 1;'" : '' ?>">dashboard</span>
            <span class="font-body-md text-body-md">Dashboard</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 <?= in_array($currentPage, ['employees', 'add_employee', 'edit_employee', 'employee_profile']) ? 'text-primary font-bold bg-surface-container-low border-r-4 border-primary' : 'text-secondary hover:bg-surface-container hover:text-primary' ?>"
            href="<?= BASE_URL ?>/admin/employees">
            <span
                class="material-symbols-outlined <?= in_array($currentPage, ['employees', 'add_employee', 'edit_employee', 'employee_profile']) ? "style='font-variation-settings: \"FILL\" 1;'" : '' ?>">badge</span>
            <span class="font-body-md text-body-md">Employees</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 <?= $currentPage === 'departments' ? 'text-primary font-bold bg-surface-container-low border-r-4 border-primary' : 'text-secondary hover:bg-surface-container hover:text-primary' ?>"
            href="<?= BASE_URL ?>/admin/departments">
            <span
                class="material-symbols-outlined <?= $currentPage === 'departments' ? "style='font-variation-settings: \"FILL\" 1;'" : '' ?>">domain</span>
            <span class="font-body-md text-body-md">Departments</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 <?= $currentPage === 'attendance' ? 'text-primary font-bold bg-surface-container-low border-r-4 border-primary' : 'text-secondary hover:bg-surface-container hover:text-primary' ?>"
            href="<?= BASE_URL ?>/admin/attendance">
            <span
                class="material-symbols-outlined <?= $currentPage === 'attendance' ? "style='font-variation-settings: \"FILL\" 1;'" : '' ?>">event_available</span>
            <span class="font-body-md text-body-md">Attendance</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 <?= $currentPage === 'leave_management' ? 'text-primary font-bold bg-surface-container-low border-r-4 border-primary' : 'text-secondary hover:bg-surface-container hover:text-primary' ?>"
            href="<?= BASE_URL ?>/admin/leave-management">
            <span
                class="material-symbols-outlined <?= $currentPage === 'leave_management' ? "style='font-variation-settings: \"FILL\" 1;'" : '' ?>">event_busy</span>
            <span class="font-body-md text-body-md">Leave</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 <?= $currentPage === 'payroll' ? 'text-primary font-bold bg-surface-container-low border-r-4 border-primary' : 'text-secondary hover:bg-surface-container hover:text-primary' ?>"
            href="<?= BASE_URL ?>/admin/payroll">
            <span
                class="material-symbols-outlined <?= $currentPage === 'payroll' ? "style='font-variation-settings: \"FILL\" 1;'" : '' ?>">payments</span>
            <span class="font-body-md text-body-md">Payroll</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 <?= $currentPage === 'performance_reports' ? 'text-primary font-bold bg-surface-container-low border-r-4 border-primary' : 'text-secondary hover:bg-surface-container hover:text-primary' ?>"
            href="<?= BASE_URL ?>/admin/performance-reports">
            <span
                class="material-symbols-outlined <?= $currentPage === 'performance_reports' ? "style='font-variation-settings: \"FILL\" 1;'" : '' ?>">trending_up</span>
            <span class="font-body-md text-body-md">Performance</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 <?= $currentPage === 'audit_logs' ? 'text-primary font-bold bg-surface-container-low border-r-4 border-primary' : 'text-secondary hover:bg-surface-container hover:text-primary' ?>"
            href="<?= BASE_URL ?>/admin/audit-logs">
            <span
                class="material-symbols-outlined <?= $currentPage === 'audit_logs' ? "style='font-variation-settings: \"FILL\" 1;'" : '' ?>">assessment</span>
            <span class="font-body-md text-body-md">Audit Logs</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 <?= $currentPage === 'user_management' ? 'text-primary font-bold bg-surface-container-low border-r-4 border-primary' : 'text-secondary hover:bg-surface-container hover:text-primary' ?>"
            href="<?= BASE_URL ?>/admin/user-management">
            <span
                class="material-symbols-outlined <?= $currentPage === 'user_management' ? "style='font-variation-settings: \"FILL\" 1;'" : '' ?>">group</span>
            <span class="font-body-md text-body-md">Users</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 <?= $currentPage === 'system_settings' ? 'text-primary font-bold bg-surface-container-low border-r-4 border-primary' : 'text-secondary hover:bg-surface-container hover:text-primary' ?>"
            href="<?= BASE_URL ?>/admin/system-settings">
            <span
                class="material-symbols-outlined <?= $currentPage === 'system_settings' ? "style='font-variation-settings: \"FILL\" 1;'" : '' ?>">settings</span>
            <span class="font-body-md text-body-md">Settings</span>
        </a>
    </nav>
</aside>
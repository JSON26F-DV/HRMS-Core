<?php
require_once __DIR__ . '/includes/config.php';

$basePath = dirname($_SERVER['SCRIPT_NAME']);
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($basePath !== '/' && strpos($request, $basePath) === 0) {
    $request = substr($request, strlen($basePath));
}
$request = rtrim($request, '/');
if ($request === '') $request = '/';

$routes = [
    '/'                             => 'pages/auth/login.php',
    '/login'                        => 'pages/auth/login.php',
    '/forgot-password'              => 'pages/auth/forgot_password.php',
    '/logout'                       => 'handlers/logout.php',

    '/admin/dashboard'              => 'pages/admin/dashboard.php',
    '/admin/employees'              => 'pages/admin/employees.php',
    '/admin/add-employee'           => 'pages/admin/add_employee.php',
    '/admin/edit-employee'          => 'pages/admin/edit_employee.php',
    '/admin/employee-profile'       => 'pages/admin/employee_profile.php',
    '/admin/departments'            => 'pages/admin/departments.php',
    '/admin/departments/setup'      => 'pages/admin/setup.php',
    '/admin/events'                 => 'pages/admin/events.php',
    '/admin/attendance'             => 'pages/admin/attendance.php',
    '/admin/leave-management'       => 'pages/admin/leave_management.php',
    '/admin/payroll'                => 'pages/admin/payroll.php',
    '/admin/performance-reports'    => 'pages/admin/performance_reports.php',
    '/admin/audit-logs'             => 'pages/admin/audit_logs.php',
    '/admin/system-settings'        => 'pages/admin/system_settings.php',
    '/admin/cleanup-deleted'        => 'handlers/cleanup_deleted.php',

    '/employee/dashboard'           => 'pages/employee/dashboard.php',
    '/employee/my-payslips'         => 'pages/employee/my_payslips.php',
    '/employee/profile'             => 'pages/employee/profile.php',
    '/employee/request-leave'       => 'pages/employee/request_leave.php',
    '/employee/performance'         => 'pages/employee/performance.php',
];

if (isset($routes[$request])) {
    require __DIR__ . '/' . $routes[$request];
} else {
    http_response_code(404);
    require __DIR__ . '/pages/404.php';
}

CREATE DATABASE IF NOT EXISTS hrms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hrms_db;

CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    positions TEXT DEFAULT NULL COMMENT 'JSON array of position titles',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO departments (name, description, positions) VALUES
('IT Department', 'Information Technology', '["Frontend Developer","Backend Developer","Full Stack Developer"]'),
('Human Resources', 'HR Department', '["HR Director","HR Coordinator"]'),
('Marketing', 'Marketing and Communications', '["Marketing Manager"]'),
('Finance', 'Finance and Accounting', '["Finance Manager"]');

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'employee') NOT NULL DEFAULT 'employee',
    is_active TINYINT(1) DEFAULT 1,
    code VARCHAR(255) DEFAULT NULL,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    INDEX (email),
    INDEX (role),
    INDEX (deleted_at)
);

INSERT INTO users (email, password_hash, role) VALUES
('admin@hrmscore.io', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('employee@hrmscore.io', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee');
-- password: password

CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    employee_id VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(50),
    department_id INT,
    position VARCHAR(255) DEFAULT NULL,
    hire_date DATE,
    salary DECIMAL(12,2),
    daily_rate DECIMAL(12,2) DEFAULT NULL,
    status ENUM('active', 'on_leave', 'terminated') DEFAULT 'active',
    address TEXT,
    avatar_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    employee_id VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(50),
    department_id INT,
    position VARCHAR(255) DEFAULT NULL,
    hire_date DATE,
    salary DECIMAL(12,2),
    daily_rate DECIMAL(12,2) DEFAULT NULL,
    status ENUM('active', 'on_leave', 'terminated') DEFAULT 'active',
    address TEXT,
    avatar_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    INDEX (employee_id),
    INDEX (status),
    INDEX (department_id)
);

INSERT INTO employees (employee_id, first_name, last_name, phone, position, department_id, hire_date, salary, status) VALUES
('EMP-2024-089', 'Jason', 'Verzosa', '+63 917 555 0123', 'Frontend Developer', 1, '2024-01-15', 75000.00, 'active'),
('EMP-2024-001', 'Alex', 'Rivera', '+63 917 555 0001', 'Backend Developer', 2, '2023-06-01', 95000.00, 'active');

CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    clock_in TIME,
    clock_out TIME,
    hours_worked DECIMAL(6,2) DEFAULT NULL,
    minutes_late INT DEFAULT 0,
    pay_status ENUM('unpaid', 'paid') DEFAULT 'unpaid',
    payroll_id INT DEFAULT NULL,
    status ENUM('present', 'absent', 'late', 'half_day') DEFAULT 'present',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY (employee_id, date),
    INDEX (date),
    INDEX (status)
);

CREATE TABLE leaves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    type ENUM('annual', 'sick', 'personal', 'maternity', 'paternity') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reason TEXT,
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (status),
    INDEX (employee_id)
);

CREATE TABLE payroll (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    gross_pay DECIMAL(12,2) NOT NULL,
    deductions DECIMAL(12,2) DEFAULT 0,
    net_pay DECIMAL(12,2) NOT NULL,
    days_worked INT DEFAULT 0,
    total_hours DECIMAL(8,2) DEFAULT 0,
    attendance_summary VARCHAR(255) DEFAULT NULL,
    status ENUM('draft', 'approved', 'paid') DEFAULT 'draft',
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX (employee_id, status)
);

CREATE TABLE performance_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    reviewer_id INT,
    review_date DATE NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comments TEXT,
    type ENUM('quarterly', 'annual', 'probation') DEFAULT 'quarterly',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (employee_id)
);

CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (user_id),
    INDEX (action),
    INDEX (created_at)
);

CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO system_settings (setting_key, setting_value) VALUES
('company_name', 'HRMS Core'),
('company_email', 'admin@hrmscore.io'),
('timezone', 'Asia/Manila'),
('payroll_cycle', 'monthly');

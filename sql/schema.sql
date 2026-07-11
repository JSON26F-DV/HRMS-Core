-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 11, 2026 at 05:12 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hrms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `clock_in` time DEFAULT NULL,
  `clock_out` time DEFAULT NULL,
  `hours_worked` decimal(6,2) DEFAULT NULL,
  `minutes_late` int(11) DEFAULT 0,
  `pay_status` enum('unpaid','paid') DEFAULT 'unpaid',
  `payroll_id` int(11) DEFAULT NULL,
  `status` enum('present','absent','late','half_day') DEFAULT 'present',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `employee_id`, `date`, `clock_in`, `clock_out`, `hours_worked`, `minutes_late`, `pay_status`, `payroll_id`, `status`, `notes`, `created_at`) VALUES
(17, 16, '2026-07-11', '20:00:00', '17:00:00', 20.50, 30, 'paid', 1, 'late', '', '2026-07-11 12:23:25'),
(18, 19, '2026-07-11', '08:00:00', '12:00:00', 4.00, 0, 'paid', 2, 'half_day', '', '2026-07-11 12:23:51'),
(20, 17, '2026-07-11', '08:00:00', '17:00:00', 8.80, 12, 'paid', 5, 'late', '', '2026-07-11 12:25:38'),
(21, 20, '2026-07-11', NULL, NULL, 0.00, 0, 'paid', 3, 'absent', '', '2026-07-11 12:25:48'),
(22, 18, '2026-07-11', '08:00:00', '17:00:00', 9.00, 0, 'paid', 4, 'present', '', '2026-07-11 12:26:16'),
(24, 16, '2026-07-10', '08:00:00', '17:00:00', 9.00, 0, 'paid', 1, 'present', '', '2026-07-11 12:47:19'),
(25, 16, '2026-07-12', '08:00:00', '17:00:00', 9.00, 0, 'unpaid', NULL, 'present', '', '2026-07-11 13:09:33'),
(26, 19, '2026-07-12', '08:00:00', '17:00:00', 9.00, 0, 'unpaid', NULL, 'present', '', '2026-07-11 13:09:50'),
(27, 20, '2026-07-12', '08:00:00', '17:00:00', 8.80, 12, 'unpaid', NULL, 'late', '', '2026-07-11 13:11:01');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `details`, `ip_address`, `created_at`) VALUES
(1, NULL, 'update', 'attendance', NULL, 'Saved attendance for 2026-07-11', '::1', '2026-07-11 02:46:28'),
(2, NULL, 'update', 'attendance', NULL, 'Saved attendance for 2026-07-10', '::1', '2026-07-11 02:55:24'),
(3, NULL, 'update', 'attendance', NULL, 'Saved attendance for 2026-07-11', '::1', '2026-07-11 08:00:02'),
(4, NULL, 'update', 'attendance', NULL, 'Saved attendance for 2026-07-11', '::1', '2026-07-11 08:00:19'),
(5, NULL, 'update', 'attendance', NULL, 'Saved attendance for 2026-07-11', '::1', '2026-07-11 08:01:40'),
(6, NULL, 'update', 'attendance', NULL, 'Saved attendance for 2026-07-11', '::1', '2026-07-11 08:03:24'),
(7, NULL, 'update', 'attendance', NULL, 'Saved attendance for 2026-07-11', '::1', '2026-07-11 08:03:47'),
(8, NULL, 'update', 'attendance', NULL, 'Saved attendance for 2026-07-11', '::1', '2026-07-11 08:04:07'),
(9, NULL, 'update', 'attendance', NULL, 'Saved attendance for 2026-07-11', '::1', '2026-07-11 08:04:28'),
(10, NULL, 'update', 'attendance', NULL, 'Saved attendance for 2026-07-11', '::1', '2026-07-11 08:04:35'),
(11, NULL, 'update', 'attendance', NULL, 'Saved attendance for 2026-07-11', '::1', '2026-07-11 08:14:35'),
(12, NULL, 'update', 'attendance', NULL, 'Saved attendance for 2026-07-11', '::1', '2026-07-11 08:15:01'),
(13, NULL, 'update', 'attendance', NULL, 'Saved attendance for 2026-07-11', '::1', '2026-07-11 08:17:44'),
(14, NULL, 'logout', 'user', 1, 'User logged out', '::1', '2026-07-11 08:20:28'),
(15, NULL, 'logout', 'user', 2, 'User logged out', '::1', '2026-07-11 08:20:53'),
(55, 9, 'update', 'attendance', NULL, 'Saved attendance for 2026-07-12', '::1', '2026-07-11 13:09:33'),
(56, 9, 'update', 'attendance', NULL, 'Saved attendance for 2026-07-12', '::1', '2026-07-11 13:09:50'),
(57, 9, 'update', 'attendance', NULL, 'Saved attendance for 2026-07-12', '::1', '2026-07-11 13:11:01'),
(58, 9, 'logout', 'user', 9, 'User logged out', '::1', '2026-07-11 13:11:57'),
(59, 11, 'logout', 'user', 11, 'User logged out', '::1', '2026-07-11 13:20:25'),
(60, 11, 'logout', 'user', 11, 'User logged out', '::1', '2026-07-11 13:36:00'),
(61, 9, 'logout', 'user', 9, 'User logged out', '::1', '2026-07-11 13:38:44'),
(62, 11, 'logout', 'user', 11, 'User logged out', '::1', '2026-07-11 13:43:05'),
(63, 9, 'logout', 'user', 9, 'User logged out', '::1', '2026-07-11 13:44:22'),
(64, 11, 'logout', 'user', 11, 'User logged out', '::1', '2026-07-11 14:04:44'),
(65, 9, 'logout', 'user', 9, 'User logged out', '::1', '2026-07-11 14:05:45'),
(66, 11, 'logout', 'user', 11, 'User logged out', '::1', '2026-07-11 14:09:12'),
(67, 11, 'logout', 'user', 11, 'User logged out', '::1', '2026-07-11 14:09:30'),
(68, 11, 'logout', 'user', 11, 'User logged out', '::1', '2026-07-11 14:17:10'),
(69, 9, 'logout', 'user', 9, 'User logged out', '::1', '2026-07-11 14:17:34'),
(70, 9, 'logout', 'user', 9, 'User logged out', '::1', '2026-07-11 14:18:12'),
(71, 9, 'logout', 'user', 9, 'User logged out', '::1', '2026-07-11 14:32:05'),
(72, 14, 'logout', 'user', 14, 'User logged out', '::1', '2026-07-11 14:33:50'),
(73, 14, 'logout', 'user', 14, 'User logged out', '::1', '2026-07-11 14:36:06'),
(74, 14, 'logout', 'user', 14, 'User logged out', '::1', '2026-07-11 14:39:11'),
(75, 14, 'logout', 'user', 14, 'User logged out', '::1', '2026-07-11 14:46:12'),
(76, 14, 'logout', 'user', 14, 'User logged out', '::1', '2026-07-11 14:51:28'),
(77, 11, 'logout', 'user', 11, 'User logged out', '::1', '2026-07-11 14:58:25'),
(78, 9, 'logout', 'user', 9, 'User logged out', '::1', '2026-07-11 14:59:19'),
(79, 9, 'logout', 'user', 9, 'User logged out', '::1', '2026-07-11 15:00:54'),
(80, 9, 'logout', 'user', 9, 'User logged out', '::1', '2026-07-11 15:11:08');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `positions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `description`, `positions`, `created_at`) VALUES
(1, 'IT Department', 'Information Technology', '[\"Frontend Developer\",\"Backend Developer\",\"Full Stack Developer\"]', '2026-07-11 02:45:59'),
(2, 'Human Resources', 'HR Department', '[\"HR Director\",\"HR Coordinator\"]', '2026-07-11 02:45:59'),
(3, 'Marketing', 'Marketing and Communications', '[\"Marketing Manager\"]', '2026-07-11 02:45:59'),
(4, 'Finance', 'Finance and Accounting', '[\"Finance Manager\"]', '2026-07-11 02:45:59'),
(5, 'Administration', 'Administrative and Executive Management', '[\"Administrator\",\"Office Administrator\",\"Administrative Assistant\",\"Executive Assistant\",\"Operations Manager\",\"Office Manager\"]', '2026-07-11 11:45:54');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `employee_id` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `salary` decimal(12,2) DEFAULT NULL,
  `daily_rate` decimal(12,2) DEFAULT NULL,
  `status` enum('active','on_leave','terminated') DEFAULT 'active',
  `address` text DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `user_id`, `employee_id`, `first_name`, `last_name`, `phone`, `department_id`, `position`, `hire_date`, `salary`, `daily_rate`, `status`, `address`, `avatar_url`, `created_at`, `updated_at`) VALUES
(14, 9, 'EMP-2026-560', 'airang', 'mapagmahal', '+1 (932) 931-8042', 5, 'Administrator', '2006-04-11', 800.00, 800.00, 'active', 'Velit optio consequ', 'https://www.pinterest.com/pin/tv-show-reaction-picture-spongebob--83809243058481487/', '2026-07-11 10:54:34', '2026-07-11 11:46:14'),
(16, 11, 'EMP-2026-407', 'Jason', 'Begornia', '+639945646355', 1, 'Full Stack Developer', '2002-11-11', 800.00, 800.00, 'active', 'Block 34 Lot 3, Cluster 4 Trieste Street, Bella Vista, Brgy. Santiago', 'Voluptas modi simili', '2026-07-11 12:00:59', '2026-07-11 12:00:59'),
(17, 12, 'EMP-2026-599', 'Triple', 'T', '+1 (992) 137-6286', 3, 'Marketing Manager', '1990-04-11', 60.00, 57.00, 'active', 'Ex illo rem neque to', 'Sit officiis vero v', '2026-07-11 12:02:03', '2026-07-11 12:24:26'),
(18, 13, 'EMP-2026-735', 'Damon', 'Suarez', '+1 (974) 885-4692', 4, 'Finance Manager', '1973-02-13', 49.00, 67.00, 'terminated', 'Sunt illum omnis mi', 'Et et ipsam in eos v', '2026-07-11 12:02:49', '2026-07-11 12:02:49'),
(19, 14, 'EMP-2026-645', ' Tim', 'Cheese', '+1 (327) 832-9147', 2, 'HR Coordinator', '1993-08-14', 55.00, 64.00, 'on_leave', 'Velit ducimus non e', 'Officia nesciunt iu', '2026-07-11 12:03:55', '2026-07-11 12:08:28'),
(20, 15, 'EMP-2026-300', 'Zena', 'Johns', '+1 (111) 711-7257', 3, 'Marketing Manager', '2024-02-10', 51.00, 30.00, 'terminated', 'Repudiandae molestia', 'Eiusmod molestiae lo', '2026-07-11 12:13:18', '2026-07-11 12:13:18');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('holiday','event','meeting') DEFAULT 'event',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `event_date`, `description`, `type`, `created_at`) VALUES
(1, 'New Year\'s Day', '2026-07-12', 'Public Holiday', 'holiday', '2026-07-11 13:17:34'),
(2, 'Labor Day', '2026-05-01', 'Public Holiday', 'holiday', '2026-07-11 13:17:34'),
(3, 'Independence Day', '2026-06-12', 'Public Holiday', 'holiday', '2026-07-11 13:17:34'),
(4, 'Christmas Day', '2026-12-25', 'Public Holiday', 'holiday', '2026-07-11 13:17:34');

-- --------------------------------------------------------

--
-- Table structure for table `leaves`
--

CREATE TABLE `leaves` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `type` enum('annual','sick','personal','maternity','paternity') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leaves`
--

INSERT INTO `leaves` (`id`, `employee_id`, `type`, `start_date`, `end_date`, `status`, `reason`, `approved_by`, `created_at`) VALUES
(2, 16, 'annual', '2026-07-11', '2026-07-12', 'pending', 'im out son\r\n', NULL, '2026-07-11 13:42:59');

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `gross_pay` decimal(12,2) NOT NULL,
  `deductions` decimal(12,2) DEFAULT 0.00,
  `net_pay` decimal(12,2) NOT NULL,
  `days_worked` int(11) DEFAULT 0,
  `total_hours` decimal(8,2) DEFAULT 0.00,
  `total_late_minutes` int(11) DEFAULT 0,
  `attendance_summary` varchar(255) DEFAULT NULL,
  `status` enum('draft','approved','paid') DEFAULT 'draft',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll`
--

INSERT INTO `payroll` (`id`, `employee_id`, `period_start`, `period_end`, `gross_pay`, `deductions`, `net_pay`, `days_worked`, `total_hours`, `total_late_minutes`, `attendance_summary`, `status`, `paid_at`, `created_at`) VALUES
(1, 16, '2026-07-01', '2026-07-31', 1550.00, 155.00, 1395.00, 2, 29.50, 30, 'present: 1, late: 1', 'paid', '2026-07-11 12:59:00', '2026-07-11 12:58:48'),
(2, 19, '2026-07-01', '2026-07-31', 32.00, 3.20, 28.80, 1, 4.00, 0, 'half_day: 1', 'draft', NULL, '2026-07-11 12:58:48'),
(3, 20, '2026-07-01', '2026-07-31', 0.00, 0.00, 0.00, 0, 0.00, 0, 'absent: 1', 'draft', NULL, '2026-07-11 12:58:48'),
(4, 18, '2026-07-01', '2026-07-31', 67.00, 6.70, 60.30, 1, 9.00, 0, 'present: 1', 'draft', NULL, '2026-07-11 12:58:48'),
(5, 17, '2026-07-01', '2026-07-31', 55.58, 5.56, 50.02, 1, 8.80, 12, 'late: 1', 'draft', NULL, '2026-07-11 12:58:48');

-- --------------------------------------------------------

--
-- Table structure for table `performance_reviews`
--

CREATE TABLE `performance_reviews` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `reviewer_id` int(11) DEFAULT NULL,
  `review_date` date NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comments` text DEFAULT NULL,
  `type` enum('quarterly','annual','probation') DEFAULT 'quarterly',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `performance_reviews`
--

INSERT INTO `performance_reviews` (`id`, `employee_id`, `reviewer_id`, `review_date`, `rating`, `comments`, `type`, `created_at`) VALUES
(1, 16, 9, '2026-07-11', 4, '', 'annual', '2026-07-11 14:05:27'),
(2, 16, 9, '2026-07-10', 5, '', 'quarterly', '2026-07-11 14:05:42');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'company_name', 'HRMS Core', '2026-07-11 02:45:59'),
(2, 'company_email', 'admin@hrmscore.io', '2026-07-11 02:45:59'),
(3, 'timezone', 'Asia/Manila', '2026-07-11 02:45:59'),
(4, 'payroll_cycle', 'monthly', '2026-07-11 02:45:59');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','employee','hr') NOT NULL DEFAULT 'employee',
  `is_active` tinyint(1) DEFAULT 1,
  `code` varchar(255) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `role`, `is_active`, `code`, `last_login`, `created_at`, `updated_at`, `deleted_at`) VALUES
(7, 'wexazytar@mailinator.com', '$2y$10$yS1qp89We8grr7HDuMhl2uDXER3mZQu.3WPiv2XZTznSyA73W67va', 'employee', 1, '$2y$10$Dzvgot8p2f.F5OvZFrbv7Oh91N/B5tc/U4hFZMSUBGJnZPFepYn06', NULL, '2026-07-11 10:40:42', '2026-07-11 11:47:08', NULL),
(8, 'macyjyj@mailinator.com', '$2y$10$D6khYp7RBkKiO5OnrE.PXeXJBq/F.2Cs6NJw/t4PJKbS6nSSzV7lm', 'employee', 1, '$2y$10$vBjs41RxAP8LyQFrkPKID.8etU4b0Fm9djljFwi2hRTXMb7OtEK6K', NULL, '2026-07-11 10:45:01', '2026-07-11 11:46:49', NULL),
(9, 'airamapagmahal@admin.com', '$2y$10$2bXkV4DTp5.RP3bt7ifVEOqoL7Xnn6j5z.EobqQ5dzrlW4jBWFSKe', 'admin', 1, '$2y$10$e6ZSsHEdNoin9nTkwdTNOe0/N33fV2JNfPactsnoFg5.zEbiZS8Au', '2026-07-11 15:09:22', '2026-07-11 10:54:35', '2026-07-11 15:09:22', NULL),
(11, 'jasonbegornia57@gmail.com', '$2y$10$jlMR5tN2i3o90kLYYjnukO1NBBMQ4R8gtkMXxJlp1/iufOHD0jU8G', 'employee', 1, '$2y$10$pjt0CDlhUrMTNKnbYI2ZhevoMPtGl.IB6UogobD3gf/wU9wlrpOw6', '2026-07-11 14:57:21', '2026-07-11 12:00:59', '2026-07-11 14:57:21', NULL),
(12, 'tungtungsahur@gmail.com', '$2y$10$bLgF1eFqRpoF0.c7FdHDI.1ILZ72G9RWE9HmbRSq8eTT18izb7so.', 'employee', 1, '$2y$10$LyvO3L6kxFisUWDZ5EUw3.f2D0xp710HU7CD6VZhFW0xfzTCbDPEK', NULL, '2026-07-11 12:02:03', '2026-07-11 12:02:03', NULL),
(13, 'kidare@mailinator.com', '$2y$10$U8I3xrJ42Aw1P8ifhNZcDOCCA3L4ENEw43aw2gTMdDNWyo19wx5Hq', 'employee', 1, '$2y$10$NAjqUuOSma7AK8bUAazMNOtCGwXsTw5oNk1M5U29L82zD70m03RCK', NULL, '2026-07-11 12:02:49', '2026-07-11 12:02:49', NULL),
(14, 'TimChess@tralala.io', '$2y$10$2bXkV4DTp5.RP3bt7ifVEOqoL7Xnn6j5z.EobqQ5dzrlW4jBWFSKe', 'hr', 1, '$2y$10$I.Iu0vReIuV7wfWKbMw7BO83WoGyBpUDpVkuae4B9ga9DUrYmGAf2', '2026-07-11 14:48:29', '2026-07-11 12:03:55', '2026-07-11 14:48:29', NULL),
(15, 'xorygy@mailinator.com', '$2y$10$BJc/YisTNXTgjTRwIEthKOhYUfV.BpQjwOKqlPmcUkpzzLB3jOJdG', 'employee', 1, '$2y$10$Mdlu.wdoylWaNueJFe42NeVa44beAdaWBeAzBHsZw8Emsmk9xK8Du', NULL, '2026-07-11 12:13:18', '2026-07-11 12:13:18', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`,`date`),
  ADD KEY `date` (`date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `action` (`action`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `employee_id_2` (`employee_id`),
  ADD KEY `status` (`status`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `employees_ibfk_1` (`user_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_date` (`event_date`);

--
-- Indexes for table `leaves`
--
ALTER TABLE `leaves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `status` (`status`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`,`status`);

--
-- Indexes for table `performance_reviews`
--
ALTER TABLE `performance_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reviewer_id` (`reviewer_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `email_2` (`email`),
  ADD KEY `role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `leaves`
--
ALTER TABLE `leaves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `performance_reviews`
--
ALTER TABLE `performance_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employees_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `leaves`
--
ALTER TABLE `leaves`
  ADD CONSTRAINT `leaves_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leaves_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `performance_reviews`
--
ALTER TABLE `performance_reviews`
  ADD CONSTRAINT `performance_reviews_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `performance_reviews_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 31, 2026 at 12:00 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ultra_hospital`
--

-- --------------------------------------------------------

--
-- Table structure for table `add_events`
--

CREATE TABLE `add_events` (
  `id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `add_events`
--

INSERT INTO `add_events` (`id`, `event_name`, `event_date`, `created_at`) VALUES
(1, 'aa', '2026-07-04', '2026-07-03 11:29:04'),
(2, 'aa', '2026-07-04', '2026-07-03 11:37:01'),
(3, 'aa', '2026-07-04', '2026-07-03 11:48:00'),
(4, 'aa', '2026-07-04', '2026-07-03 11:48:19'),
(5, 'aa', '2026-07-05', '2026-07-03 12:15:57'),
(6, 'aahhhhhhh', '2026-06-22', '2026-07-03 12:16:59'),
(7, 'aa', '2026-07-07', '2026-07-03 12:53:36'),
(8, 'appointment', '2026-07-01', '2026-07-04 05:38:25');

-- --------------------------------------------------------

--
-- Table structure for table `admin_profile`
--

CREATE TABLE `admin_profile` (
  `admin_id` int(11) NOT NULL,
  `register_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delete_flag` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_profile`
--

INSERT INTO `admin_profile` (`admin_id`, `register_id`, `full_name`, `mobile`, `profile_image`, `created_at`, `updated_at`, `delete_flag`) VALUES
(5, 1006, 'Super Admin', '7261998814', '../../documents/admin/images/1785325370_Abhishek.png', '2026-07-19 16:43:52', '2026-07-29 11:42:50', 0),
(6, 1008, 'Dr. Sanket Pawar', '7894512458', 'documents/admin/images/1784720492_Abhishek.png', '2026-07-20 05:59:41', '2026-07-22 11:41:32', 0),
(7, 1009, 'Abhishek mandhare', '', 'documents/admin/images/Ultra_Rohan.PNG', '2026-07-20 07:46:59', '2026-07-20 10:49:01', 0),
(8, 1011, 'Dr. Sam Dapal', '', 'documents/admin/images/rohan.jpeg', '2026-07-20 12:48:53', '2026-07-20 12:49:09', 0),
(10, 1017, '234rtgvfc', NULL, NULL, '2026-07-20 17:56:36', '2026-07-20 17:56:36', 0),
(12, 1022, 'Eshwar Pawar', NULL, NULL, '2026-07-20 19:00:37', '2026-07-20 19:00:37', 0),
(13, 1028, 'Dr Ayush Nipane', NULL, NULL, '2026-07-21 07:37:54', '2026-07-21 07:37:54', 0),
(14, 1040, 'Rahul Namya', NULL, NULL, '2026-07-21 10:09:00', '2026-07-21 10:09:00', 0);

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `appointment_no` varchar(100) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `department` varchar(200) NOT NULL,
  `appointment_type` varchar(200) NOT NULL,
  `opd_ipd_type` enum('OPD','IPD') NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Scheduled','Confirmed','Completed','Cancelled') DEFAULT 'Scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delete_flag` tinyint(1) DEFAULT 0,
  `hospital_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `appointment_no`, `patient_id`, `doctor_id`, `department`, `appointment_type`, `opd_ipd_type`, `appointment_date`, `appointment_time`, `duration`, `reason`, `status`, `notes`, `created_at`, `modified_at`, `delete_flag`, `hospital_id`) VALUES
(7, 'APP-20260722141708-6a60b4c46ab5b', 28, 9, 'Cardiology', 'Follow-up', 'IPD', '2026-07-22', '05:00:00', '15', 'Fever', 'Completed', '', '2026-07-22 12:18:23', '2026-07-30 06:23:59', 0, 6),
(10, 'APP-20260722153531-6a60c7233209c', 6, 8, 'Cardiology', 'Check-up', 'OPD', '2026-07-22', '11:00:00', '15', 'Fever', 'Completed', '', '2026-07-22 13:36:31', '2026-07-30 06:23:41', 0, 5),
(12, 'APP-5016', 28, 8, 'Cardiology', 'Follow-up', 'IPD', '2026-07-28', '11:30:00', '15', 'Diabetes Checkup', 'Scheduled', '', '2026-07-28 06:04:16', '2026-07-28 06:14:25', 0, 5),
(18, 'APP-20260728113437-8d8001', 6, 8, 'Cardiology', 'Follow-up', 'OPD', '2026-08-01', '10:00:00', '15', 'Follow-up Visit', 'Scheduled', 'Auto Follow-up Appointment', '2026-07-28 09:34:37', '2026-07-28 09:34:37', 0, 5),
(19, 'APP-20260728124742-1f49d3', 28, 8, 'Cardiology', 'Follow-up', 'OPD', '2026-08-01', '10:00:00', '15', 'Follow-up Visit', 'Scheduled', 'Auto Follow-up Appointment', '2026-07-28 10:47:42', '2026-07-28 10:47:42', 0, 5),
(20, 'APP-4912', 6, 5, 'Pediatricss', 'Procedure', 'OPD', '2026-08-07', '03:03:00', '15', 'Diabetes Checkup', 'Cancelled', '', '2026-07-30 06:40:02', '2026-07-30 09:34:34', 0, 5);

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL,
  `hospital_id` int(11) DEFAULT NULL,
  `register_id` int(11) NOT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `user_role` varchar(100) DEFAULT NULL,
  `action_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `module` varchar(100) NOT NULL,
  `action` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `browser` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `delete_flag` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`log_id`, `hospital_id`, `register_id`, `user_name`, `user_role`, `action_type`, `description`, `module`, `action`, `ip_address`, `user_agent`, `browser`, `created_at`, `delete_flag`) VALUES
(1, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:02:42', 0),
(2, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:03:05', 0),
(3, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Updated hospital: UltraHospital', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:03:38', 0),
(4, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Updated hospital: UltraHospital', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:04:04', 0),
(5, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:04:08', 0),
(6, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:08:09', 0),
(7, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:08:17', 0),
(8, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:09:20', 0),
(9, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:09:42', 0),
(10, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:09:45', 0),
(11, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:09:49', 0),
(12, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:11:09', 0),
(13, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:11:12', 0),
(14, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:11:23', 0),
(15, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:11:31', 0),
(16, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:11:35', 0),
(17, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:12:42', 0),
(18, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:13:31', 0),
(19, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:13:34', 0),
(20, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:13:39', 0),
(21, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:13:40', 0),
(22, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:13:46', 0),
(23, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:14:14', 0),
(24, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:14:26', 0),
(25, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:15:14', 0),
(26, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:15:16', 0),
(27, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:15:19', 0),
(28, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:15:26', 0),
(29, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:15:36', 0),
(30, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:15:43', 0),
(31, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:16:23', 0),
(32, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:16:57', 0),
(33, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:20:52', 0),
(34, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:20:54', 0),
(35, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:22:58', 0),
(36, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:24:33', 0),
(37, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:24:41', 0),
(38, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:25:04', 0),
(39, NULL, 2, 'Dr. Rahul Kumbhars', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:26:03', 0),
(40, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:26:43', 0),
(41, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:26:46', 0),
(42, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:27:34', 0),
(43, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:27:39', 0),
(44, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:28:31', 0),
(45, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:28:33', 0),
(46, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:28:35', 0),
(47, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:28:55', 0),
(48, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:29:33', 0),
(49, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:29:54', 0),
(50, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:30:10', 0),
(51, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:30:29', 0),
(52, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:32:47', 0),
(53, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:33:07', 0),
(54, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:33:21', 0),
(55, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:33:27', 0),
(56, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:34:19', 0),
(57, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:35:00', 0),
(58, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:35:01', 0),
(59, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:36:41', 0),
(60, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:37:28', 0),
(61, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:37:45', 0),
(62, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Added new hospital: City Hospital (ID: 4)', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:38:45', 0),
(63, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital Admin', 'Added new hospital admin: Dr. Sam Dapal (ID: 1007) for hospital: City Hospital', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:38:46', 0),
(64, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:50:45', 0),
(65, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:50:49', 0),
(66, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:50:53', 0),
(67, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:51:42', 0),
(68, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:51:42', 0),
(69, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:51:43', 0),
(70, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:51:45', 0),
(71, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:51:46', 0),
(72, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:51:58', 0),
(73, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:53:33', 0),
(74, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:53:37', 0),
(75, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:53:58', 0),
(76, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:54:12', 0),
(77, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:55:27', 0),
(78, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:55:29', 0),
(79, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:55:31', 0),
(80, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:56:01', 0),
(81, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Added new hospital: City Hospital (ID: 5)', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:57:32', 0),
(82, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital Admin', 'Added new hospital admin: Dr. Sanket Pawar (ID: 1008) for hospital: City Hospital', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:57:32', 0),
(83, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 05:58:12', 0),
(84, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 06:00:11', 0),
(85, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 06:01:28', 0),
(86, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 06:01:28', 0),
(87, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 06:01:45', 0),
(88, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 06:04:51', 0),
(89, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 06:04:54', 0),
(90, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Updated status of Hospital ID 5 to Inactive', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 06:04:54', 0),
(91, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 06:04:54', 0),
(92, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 06:05:29', 0),
(93, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 06:05:34', 0),
(94, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Updated status of Hospital ID 5 to Active', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 06:05:34', 0),
(95, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 06:05:34', 0),
(96, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 06:06:15', 0),
(97, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Updated status of Hospital ID 5 to Inactive', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 06:06:15', 0),
(98, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 06:06:15', 0),
(99, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 06:09:19', 0),
(100, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 06:12:39', 0),
(101, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Updated hospital: City Hospital', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 06:13:07', 0),
(102, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 06:16:44', 0),
(103, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 06:33:33', 0),
(104, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 06:50:26', 0),
(105, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 06:53:04', 0),
(106, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 06:53:06', 0),
(107, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 06:53:12', 0),
(108, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 06:57:35', 0),
(109, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 06:58:36', 0),
(110, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 06:58:45', 0),
(111, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 07:07:54', 0),
(112, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Updated status of Hospital ID 5 to Inactive', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 07:07:54', 0),
(113, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 07:07:54', 0),
(114, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 07:07:57', 0),
(115, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Updated status of Hospital ID 5 to Active', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 07:07:57', 0),
(116, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 07:07:57', 0),
(117, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 07:11:31', 0),
(118, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 07:11:40', 0),
(119, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Updated status of Hospital ID 5 to Inactive', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 07:11:40', 0),
(120, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 07:11:40', 0),
(121, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 07:12:19', 0),
(122, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 07:16:05', 0),
(123, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Updated hospital: City Hospital', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 07:16:24', 0),
(124, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 07:16:26', 0),
(125, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Updated status of Hospital ID 5 to Active', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 07:16:26', 0),
(126, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 07:16:26', 0),
(127, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 07:16:33', 0),
(128, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 07:16:54', 0),
(129, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 07:17:37', 0),
(130, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 07:31:05', 0),
(131, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 07:34:53', 0),
(132, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 07:34:55', 0),
(133, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 07:35:40', 0),
(134, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 07:35:47', 0),
(135, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Added new hospital: KadamsHospital (ID: 6)', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 07:36:19', 0),
(136, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital Admin', 'Added new hospital admin: Abhishek mandhare (ID: 1009) for hospital: KadamsHospital', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 07:36:19', 0),
(137, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 07:44:13', 0),
(138, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 07:45:40', 0),
(139, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 07:47:22', 0),
(140, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 07:47:47', 0),
(141, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 10:17:38', 0),
(142, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 10:18:26', 0),
(143, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 10:21:59', 0),
(144, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 10:24:24', 0),
(145, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 10:38:37', 0),
(146, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 10:44:49', 0),
(147, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 10:44:58', 0),
(148, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 10:55:19', 0),
(149, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 10:55:21', 0),
(150, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 10:55:22', 0),
(151, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Updated hospital: KadamsHospital', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 10:56:07', 0),
(152, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 11:13:26', 0),
(153, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 11:13:53', 0),
(154, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 11:14:21', 0),
(155, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 11:19:39', 0),
(156, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 11:19:46', 0),
(157, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 11:20:03', 0),
(158, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 11:22:03', 0),
(159, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 11:25:17', 0),
(160, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 11:25:31', 0),
(161, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 11:27:23', 0),
(162, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', '', '', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 11:29:50', 0),
(163, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 11:31:15', 0),
(164, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 11:31:24', 0),
(165, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 11:31:27', 0),
(166, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 11:31:28', 0),
(167, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 11:32:39', 0),
(168, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 11:32:45', 0),
(169, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 11:32:57', 0),
(170, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 11:33:53', 0),
(171, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 11:39:26', 0),
(172, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 11:39:47', 0),
(173, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 11:40:07', 0),
(174, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 11:42:34', 0),
(175, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 11:43:31', 0),
(176, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 11:44:25', 0),
(177, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 11:44:54', 0),
(178, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-20 11:48:04', 0),
(179, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Added new hospital: Saygaonkar Hospital (ID: 7)', 'Hospital', 'Added new hospital: Saygaonkar Hospital (ID: 7)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 11:49:54', 0),
(180, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital Admin', 'Added new hospital admin: Dr. Sam Dapal (ID: 1011) for hospital: Saygaonkar Hospital', 'Hospital Admin', 'Added new hospital admin: Dr. Sam Dapal (ID: 1011) for hospital: Saygaonkar Hospital', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 11:49:54', 0),
(181, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 12:03:14', 0),
(182, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 12:04:01', 0);
INSERT INTO `audit_logs` (`log_id`, `hospital_id`, `register_id`, `user_name`, `user_role`, `action_type`, `description`, `module`, `action`, `ip_address`, `user_agent`, `browser`, `created_at`, `delete_flag`) VALUES
(183, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 12:04:29', 0),
(184, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 12:05:23', 0),
(185, NULL, 1006, 'Super Admin', 'Super Admin', 'User', 'Updated role of User ID 1012 to Patient', 'User', 'Updated role of User ID 1012 to Patient', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 12:41:07', 0),
(186, NULL, 1006, 'Super Admin', 'Super Admin', 'User', 'Updated role of User ID 1013 to Ward Boy', 'User', 'Updated role of User ID 1013 to Ward Boy', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 12:41:14', 0),
(187, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 12:42:00', 0),
(188, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 12:43:44', 0),
(189, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 12:52:14', 0),
(190, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 12:57:51', 0),
(191, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 13:02:39', 0),
(192, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 13:04:02', 0),
(193, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 13:10:45', 0),
(194, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 14:44:56', 0),
(195, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Added new hospital: 123 (ID: 8)', 'Hospital', 'Added new hospital: 123 (ID: 8)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 14:50:11', 0),
(196, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital Admin', 'Added new hospital admin: 234rtgvfc (ID: 1017) for hospital: 123', 'Hospital Admin', 'Added new hospital admin: 234rtgvfc (ID: 1017) for hospital: 123', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 14:50:11', 0),
(197, 8, 1006, NULL, NULL, NULL, NULL, 'Hospital', 'Hospital deleted by Super Admin', NULL, NULL, NULL, '2026-07-20 14:50:20', 0),
(198, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Added new hospital: Satara Ruby (ID: 9)', 'Hospital', 'Added new hospital: Satara Ruby (ID: 9)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 15:09:44', 0),
(199, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital Admin', 'Added new hospital admin: Dr. Saket Kadam (ID: 1018) for hospital: Satara Ruby', 'Hospital Admin', 'Added new hospital admin: Dr. Saket Kadam (ID: 1018) for hospital: Satara Ruby', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 15:09:45', 0),
(200, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 16:35:55', 0),
(201, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 16:58:38', 0),
(202, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 16:59:20', 0),
(203, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:02:21', 0),
(204, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:03:02', 0),
(205, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:05:17', 0),
(206, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:05:32', 0),
(207, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:05:33', 0),
(208, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:05:41', 0),
(209, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:05:49', 0),
(210, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:05:54', 0),
(211, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:06:06', 0),
(212, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:06:06', 0),
(213, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:06:06', 0),
(214, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:06:07', 0),
(215, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:06:07', 0),
(216, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:06:07', 0),
(217, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:06:07', 0),
(218, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:06:23', 0),
(219, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:06:42', 0),
(220, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:09:59', 0),
(221, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:11:43', 0),
(222, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:12:17', 0),
(223, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:12:31', 0),
(224, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:13:11', 0),
(225, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:13:45', 0),
(226, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:15:40', 0),
(227, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:15:47', 0),
(228, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:15:51', 0),
(229, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:16:36', 0),
(230, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:16:38', 0),
(231, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:16:40', 0),
(232, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:16:58', 0),
(233, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:17:21', 0),
(234, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:17:48', 0),
(235, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:17:53', 0),
(236, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:18:46', 0),
(237, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:19:25', 0),
(238, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:19:34', 0),
(239, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:19:48', 0),
(240, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:19:59', 0),
(241, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:20:07', 0),
(242, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:20:10', 0),
(243, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:20:15', 0),
(244, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:20:15', 0),
(245, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:20:18', 0),
(246, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:20:25', 0),
(247, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:20:42', 0),
(248, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:25:54', 0),
(249, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:33:47', 0),
(250, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:33:57', 0),
(251, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:35:30', 0),
(252, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:35:51', 0),
(253, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:36:02', 0),
(254, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:36:10', 0),
(255, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:36:19', 0),
(256, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:36:47', 0),
(257, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:36:50', 0),
(258, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:38:08', 0),
(259, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:38:11', 0),
(260, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:39:32', 0),
(261, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:39:39', 0),
(262, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:39:42', 0),
(263, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:40:08', 0),
(264, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:40:50', 0),
(265, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:40:58', 0),
(266, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:41:04', 0),
(267, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:42:49', 0),
(268, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:45:42', 0),
(269, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:45:57', 0),
(270, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:46:12', 0),
(271, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:46:27', 0),
(272, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:46:30', 0),
(273, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:46:43', 0),
(274, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:47:13', 0),
(275, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:47:16', 0),
(276, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:47:48', 0),
(277, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:47:58', 0),
(278, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-20 17:57:22', 0),
(279, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 02:50:51', 0),
(280, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 04:45:44', 0),
(281, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 06:04:36', 0),
(282, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 06:18:49', 0),
(283, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 06:26:16', 0),
(284, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 06:26:21', 0),
(285, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 06:26:26', 0),
(286, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 06:26:27', 0),
(287, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 06:35:04', 0),
(288, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 06:59:32', 0),
(289, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 06:59:35', 0),
(290, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 06:59:41', 0),
(291, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 06:59:52', 0),
(292, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 08:00:02', 0),
(293, NULL, 1006, 'Super Admin', 'Super Admin', 'User', 'Updated role of User ID 1028 to Doctor', 'User', 'Updated role of User ID 1028 to Doctor', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 08:00:26', 0),
(294, 5, 1028, 'Dr Ayush Nipane', 'Doctor', 'Doctor Dashboard', 'Doctor accessed dashboard', 'Doctor Dashboard', 'Doctor accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 08:01:07', 0),
(295, 5, 1028, 'Dr Ayush Nipane', 'Doctor', 'Doctor Dashboard', 'Doctor accessed dashboard', 'Doctor Dashboard', 'Doctor accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 08:11:47', 0),
(296, NULL, 1006, 'Super Admin', 'Super Admin', 'User', 'Updated role of User ID 1022 to Patient', 'User', 'Updated role of User ID 1022 to Patient', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 09:53:43', 0),
(297, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 10:31:14', 0),
(298, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 10:42:21', 0),
(299, NULL, 1006, 'Super Admin', 'Super Admin', 'User', 'Updated role of User ID 1040 to Lab Technician', 'User', 'Updated role of User ID 1040 to Lab Technician', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 10:43:47', 0),
(300, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 10:49:03', 0),
(301, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 10:56:17', 0),
(302, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 10:56:27', 0),
(303, NULL, 1006, 'Super Admin', 'Super Admin', 'Permission', 'Added new permission: Referral View (Slug: referral-view)', 'Permission', 'Added new permission: Referral View (Slug: referral-view)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 11:18:04', 0),
(304, NULL, 1006, 'Super Admin', 'Super Admin', 'Permission', 'Added new permission: Call Patient View (Slug: call-patient-view)', 'Permission', 'Added new permission: Call Patient View (Slug: call-patient-view)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 11:19:59', 0),
(305, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 11:23:40', 0),
(306, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 11:27:31', 0),
(307, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 12:04:14', 0),
(308, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 12:04:25', 0),
(309, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 12:06:43', 0),
(310, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 12:08:24', 0),
(311, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 12:14:00', 0),
(312, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-21 12:14:03', 0),
(313, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 05:12:46', 0),
(314, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 05:21:19', 0),
(315, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 05:24:19', 0),
(316, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 05:29:29', 0),
(317, NULL, 1006, 'Super Admin', 'Super Admin', 'User', 'Updated role of User ID 1029 to Doctor', 'User', 'Updated role of User ID 1029 to Doctor', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 05:29:43', 0),
(318, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 05:34:24', 0),
(319, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 05:35:42', 0),
(320, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 05:48:45', 0),
(321, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 05:48:52', 0),
(322, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 06:53:33', 0),
(323, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 07:34:39', 0),
(324, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 07:35:57', 0),
(325, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 07:36:35', 0),
(326, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 09:00:59', 0),
(327, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 09:01:20', 0),
(328, NULL, 1006, 'Super Admin', 'Super Admin', 'Permission', 'Added new permission: Lab Master View (Slug: lab-master-view)', 'Permission', 'Added new permission: Lab Master View (Slug: lab-master-view)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 09:25:33', 0),
(329, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 11:20:17', 0),
(330, NULL, 1006, 'Super Admin', 'Super Admin', 'Permission', 'Added new permission: Surgery View (Slug: surgery-view)', 'Permission', 'Added new permission: Surgery View (Slug: surgery-view)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 11:22:15', 0),
(331, NULL, 1006, 'Super Admin', 'Super Admin', 'Permission', 'Added new permission: Surgery Create (Slug: surgery-create)', 'Permission', 'Added new permission: Surgery Create (Slug: surgery-create)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 11:22:39', 0),
(332, NULL, 1006, 'Super Admin', 'Super Admin', 'Permission', 'Added new permission: Surgery Edit (Slug: surgery-edit)', 'Permission', 'Added new permission: Surgery Edit (Slug: surgery-edit)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 11:23:06', 0),
(333, NULL, 1006, 'Super Admin', 'Super Admin', 'Permission', 'Added new permission: Surgery Delete (Slug: surgery-delete)', 'Permission', 'Added new permission: Surgery Delete (Slug: surgery-delete)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 11:23:51', 0),
(334, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 13:45:28', 0),
(335, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 13:46:10', 0),
(336, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 13:46:19', 0),
(337, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 13:46:30', 0),
(338, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 13:46:42', 0),
(339, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 13:57:52', 0),
(340, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-23 05:51:14', 0),
(341, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-23 05:53:44', 0),
(342, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-23 05:58:02', 0),
(343, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-23 06:10:43', 0),
(344, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-23 14:58:13', 0),
(345, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-24 06:22:29', 0),
(346, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-24 06:52:25', 0);
INSERT INTO `audit_logs` (`log_id`, `hospital_id`, `register_id`, `user_name`, `user_role`, `action_type`, `description`, `module`, `action`, `ip_address`, `user_agent`, `browser`, `created_at`, `delete_flag`) VALUES
(347, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-24 06:52:49', 0),
(348, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-24 06:52:57', 0),
(349, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-24 06:53:05', 0),
(350, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-24 06:53:06', 0),
(351, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-24 06:53:15', 0),
(352, NULL, 1006, 'Super Admin', 'Super Admin', 'User', 'Updated role of User ID 1040 to Lab Technician', 'User', 'Updated role of User ID 1040 to Lab Technician', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-24 06:58:39', 0),
(353, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-24 07:11:51', 0),
(354, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-24 07:20:43', 0),
(355, NULL, 1006, 'Super Admin', 'Super Admin', 'User', 'Updated role of User ID 1045 to Patient', 'User', 'Updated role of User ID 1045 to Patient', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-24 07:27:06', 0),
(356, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-24 07:31:01', 0),
(357, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-24 07:31:04', 0),
(358, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-24 07:32:26', 0),
(359, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-24 07:35:08', 0),
(360, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-24 07:35:52', 0),
(361, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-24 07:36:08', 0),
(362, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Updated status of Hospital ID 7 to Inactive', 'Hospital', 'Updated status of Hospital ID 7 to Inactive', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-24 07:36:08', 0),
(363, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-24 07:36:08', 0),
(364, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-24 12:07:18', 0),
(365, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-24 12:14:02', 0),
(366, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-27 06:44:47', 0),
(367, 5, 1040, 'Rahul Namya', 'Lab Technician', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-27 06:45:14', 0),
(368, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-27 06:46:24', 0),
(369, 6, 1029, 'Dr Shivatej Katkar', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-27 06:52:49', 0),
(370, 5, 1028, 'Dr Ayush Nipane', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-27 06:53:01', 0),
(371, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-27 06:54:19', 0),
(372, 5, 1015, 'Chaitanya Patil', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-27 06:59:28', 0),
(373, 6, 1029, 'Dr Shivatej Katkar', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-27 07:00:50', 0),
(374, 5, 1015, 'Chaitanya Patil', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-27 07:05:33', 0),
(375, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-27 07:08:33', 0),
(376, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-27 07:09:06', 0),
(377, 5, 1040, 'Rahul Namya', 'Lab Technician', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-27 07:11:00', 0),
(378, 6, 1009, 'Abhishek mandhare', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-27 07:26:25', 0),
(379, 5, 1028, 'Dr Ayush Nipane', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-27 08:07:08', 0),
(380, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-27 08:24:33', 0),
(381, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-27 08:32:26', 0),
(382, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-27 08:32:36', 0),
(383, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-27 08:32:50', 0),
(384, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-27 08:33:01', 0),
(385, NULL, 1006, 'Super Admin', 'Super Admin', 'Role', 'Deleted role: Driver (ID: 14)', 'Role', 'Deleted role: Driver (ID: 14)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-27 09:04:00', 0),
(386, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-27 09:14:37', 0),
(387, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-27 09:14:46', 0),
(388, 5, 1028, 'Dr Ayush Nipane', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-27 09:17:30', 0),
(389, 6, 1009, 'Abhishek mandhare', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-27 09:33:29', 0),
(390, NULL, 1006, 'Super Admin', 'Super Admin', 'User', 'Updated role of User ID 1052 to Doctor', 'User', 'Updated role of User ID 1052 to Doctor', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-27 09:34:22', 0),
(391, 6, 1052, 'Mohan Joshi', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-27 09:41:24', 0),
(392, 6, 1009, 'Abhishek mandhare', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-28 05:03:22', 0),
(393, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 05:05:44', 0),
(394, 5, 1015, 'Chaitanya Patil', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 05:10:16', 0),
(395, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 06:03:26', 0),
(396, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 06:03:32', 0),
(397, 6, 1052, 'Mohan Joshi', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 06:25:14', 0),
(398, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-28 06:25:30', 0),
(399, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-28 06:30:07', 0),
(400, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-28 06:30:23', 0),
(401, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 06:38:20', 0),
(402, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 07:06:30', 0),
(403, 5, 1028, 'Dr Ayush Nipane', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 07:10:10', 0),
(404, 5, 1028, 'Dr Ayush Nipane', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 07:10:14', 0),
(405, 5, 1028, 'Dr Ayush Nipanes', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 07:24:51', 0),
(406, 5, 1028, 'Dr Ayush Nipanes', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 07:27:03', 0),
(407, 6, 1009, 'Abhishek mandhare', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-28 07:27:22', 0),
(408, 5, 1028, 'Dr Ayush Nipanes', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 07:27:23', 0),
(409, 5, 1028, 'Dr Ayush Nipanes', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 07:29:02', 0),
(410, 5, 1028, 'Dr Ayush Nipanes', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 07:29:23', 0),
(411, 5, 1028, 'Dr Ayush Nipanes', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 07:31:34', 0),
(412, 5, 1028, 'Dr Ayush Nipanes', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 07:33:24', 0),
(413, 5, 1028, 'Dr Ayush Nipanes', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 07:33:39', 0),
(414, 5, 1028, 'Dr Ayush Nipanes', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 07:35:01', 0),
(415, 5, 1028, 'Dr Ayush Nipanes', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 07:36:00', 0),
(416, 5, 1028, 'Dr Ayush Nipanes', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 07:36:20', 0),
(417, 5, 1028, 'Ayush Nipane', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 07:39:46', 0),
(418, 5, 1028, 'Ayush Nipane', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 07:40:41', 0),
(419, 5, 1028, 'Ayush Nipane', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 07:41:19', 0),
(420, 5, 1028, 'Ayush Nipane', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 07:42:27', 0),
(421, 5, 1028, 'Ayush Nipane', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 07:44:04', 0),
(422, 5, 1028, 'Ayush Nipane', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 07:44:28', 0),
(423, 6, 1029, 'Dr Shivatej Katkar', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 07:44:46', 0),
(424, 5, 1028, 'Ayush Nipane', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 07:47:37', 0),
(425, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 07:49:17', 0),
(426, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 07:49:54', 0),
(427, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 08:24:38', 0),
(428, 5, 1028, 'Ayush Nipane', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 08:29:01', 0),
(429, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 08:29:35', 0),
(430, 6, 1009, 'Abhishek mandhare', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-28 10:04:18', 0),
(431, 5, 1028, 'Ayush Nipane', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-28 10:04:34', 0),
(432, 5, 1028, 'Ayush Nipane', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 10:05:07', 0),
(433, 5, 1028, 'Ayush Nipane', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 10:05:20', 0),
(434, 5, 1028, 'Ayush Nipane', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 10:11:03', 0),
(435, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 10:28:19', 0),
(436, 5, 1028, 'Ayush Nipane', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 10:49:02', 0),
(437, 5, 1028, 'Ayush Nipane', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-28 11:02:03', 0),
(438, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-28 11:02:18', 0),
(439, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-28 11:02:38', 0),
(440, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:03:45', 0),
(441, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:04:13', 0),
(442, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:07:24', 0),
(443, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:08:48', 0),
(444, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:09:02', 0),
(445, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Updated status of Hospital ID 7 to Active', 'Hospital', 'Updated status of Hospital ID 7 to Active', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:09:02', 0),
(446, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:09:02', 0),
(447, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:09:52', 0),
(448, 5, 1028, 'Ayush Nipane', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:11:01', 0),
(449, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:11:06', 0),
(450, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:13:41', 0),
(451, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:14:40', 0),
(452, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:16:28', 0),
(453, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:16:38', 0),
(454, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:16:42', 0),
(455, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:17:36', 0),
(456, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:17:42', 0),
(457, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:20:19', 0),
(458, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:20:37', 0),
(459, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:21:28', 0),
(460, NULL, 1006, 'Super Admin', 'Super Admin', 'Department', 'Super Admin accessed department management', 'Department', 'Super Admin accessed department management', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:21:30', 0),
(461, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:23:26', 0),
(462, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:24:02', 0),
(463, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:24:32', 0),
(464, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:25:00', 0),
(465, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:25:20', 0),
(466, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:25:36', 0),
(467, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:26:06', 0),
(468, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Updated status of Hospital ID 9 to Inactive', 'Hospital', 'Updated status of Hospital ID 9 to Inactive', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:26:06', 0),
(469, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:26:06', 0),
(470, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:26:17', 0),
(471, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Updated status of Hospital ID 9 to Active', 'Hospital', 'Updated status of Hospital ID 9 to Active', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:26:17', 0),
(472, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:26:17', 0),
(473, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:26:32', 0),
(474, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:26:46', 0),
(475, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:26:53', 0),
(476, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:26:58', 0),
(477, NULL, 1006, 'Super Admin', 'Super Admin', 'User', 'Updated role of User ID 1022 to Patient', 'User', 'Updated role of User ID 1022 to Patient', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:32:51', 0),
(478, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:34:57', 0),
(479, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:35:06', 0),
(480, 6, 1009, 'Abhishek mandhare', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:35:23', 0),
(481, 6, 1009, 'Abhishek mandhare', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:35:56', 0),
(482, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:40:29', 0),
(483, 5, 1028, 'Ayush Nipane', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-28 11:43:12', 0),
(484, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-28 11:47:39', 0),
(485, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-28 11:47:43', 0),
(486, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:48:06', 0),
(487, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 11:48:16', 0),
(488, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-28 11:49:17', 0),
(489, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-28 11:49:24', 0),
(490, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-28 11:50:22', 0),
(491, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-28 11:51:11', 0),
(492, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-28 11:51:22', 0),
(493, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-28 11:51:26', 0),
(494, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:05:35', 0),
(495, NULL, 1006, 'Super Admin', 'Super Admin', 'Department', 'Added new department: Sexiology', 'Department', 'Added new department: Sexiology', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:05:56', 0),
(496, 6, 1009, 'Abhishek mandhare', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-28 12:06:13', 0),
(497, NULL, 1006, 'Super Admin', 'Super Admin', 'Department', 'Added new department: Sexiology', 'Department', 'Added new department: Sexiology', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:07:23', 0),
(498, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:07:31', 0),
(499, NULL, 1006, 'Super Admin', 'Super Admin', 'Department', 'Added new department: asdfghj', 'Department', 'Added new department: asdfghj', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:09:24', 0),
(500, NULL, 1006, 'Super Admin', 'Super Admin', 'Department', 'Added new department: asdfghj', 'Department', 'Added new department: asdfghj', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:09:30', 0),
(501, NULL, 1006, 'Super Admin', 'Super Admin', 'Department', 'Added new department: asdfghj', 'Department', 'Added new department: asdfghj', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:09:35', 0),
(502, NULL, 1006, 'Super Admin', 'Super Admin', 'Department', 'Added new department: asdfghj', 'Department', 'Added new department: asdfghj', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:11:16', 0),
(503, NULL, 1006, 'Super Admin', 'Super Admin', 'Department', 'Added new department: Biology', 'Department', 'Added new department: Biology', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:18:04', 0),
(504, NULL, 1006, 'Super Admin', 'Super Admin', 'Department', 'Soft deleted Department ID 13', 'Department', 'Soft deleted Department ID 13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:20:17', 0),
(505, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:20:21', 0),
(506, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:21:04', 0),
(507, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:21:13', 0),
(508, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:21:28', 0),
(509, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:21:57', 0),
(510, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-28 12:22:02', 0),
(511, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:22:05', 0),
(512, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:22:10', 0),
(513, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:22:17', 0),
(514, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:22:22', 0),
(515, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:22:27', 0),
(516, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:22:38', 0),
(517, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:23:10', 0),
(518, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:23:34', 0),
(519, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:23:38', 0);
INSERT INTO `audit_logs` (`log_id`, `hospital_id`, `register_id`, `user_name`, `user_role`, `action_type`, `description`, `module`, `action`, `ip_address`, `user_agent`, `browser`, `created_at`, `delete_flag`) VALUES
(520, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:23:48', 0),
(521, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:23:50', 0),
(522, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:23:52', 0),
(523, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:25:19', 0),
(524, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:25:27', 0),
(525, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:25:45', 0),
(526, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:26:56', 0),
(527, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:27:12', 0),
(528, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:27:30', 0),
(529, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:27:54', 0),
(530, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:27:58', 0),
(531, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:29:38', 0),
(532, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:30:09', 0),
(533, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:30:13', 0),
(534, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:30:17', 0),
(535, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:30:26', 0),
(536, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:30:36', 0),
(537, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:30:39', 0),
(538, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-28 12:30:43', 0),
(539, 6, 1009, 'Abhishek mandhare', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 05:20:02', 0),
(540, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 05:20:11', 0),
(541, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 05:20:16', 0),
(542, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 05:20:18', 0),
(543, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 05:21:14', 0),
(544, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 05:24:18', 0),
(545, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 05:24:38', 0),
(546, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 05:26:05', 0),
(547, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 05:28:20', 0),
(548, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 06:13:39', 0),
(549, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 06:14:37', 0),
(550, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 06:14:52', 0),
(551, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 06:16:24', 0),
(552, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 06:20:10', 0),
(553, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 06:31:11', 0),
(554, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 06:41:23', 0),
(555, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Updated status of Hospital ID 9 to Inactive', 'Hospital', 'Updated status of Hospital ID 9 to Inactive', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 06:41:23', 0),
(556, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 06:41:23', 0),
(557, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 06:41:40', 0),
(558, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 06:47:45', 0),
(559, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 06:47:55', 0),
(560, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 06:51:00', 0),
(561, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 06:51:01', 0),
(562, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 06:53:55', 0),
(563, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 06:53:56', 0),
(564, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 06:53:59', 0),
(565, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 06:56:33', 0),
(566, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 06:56:35', 0),
(567, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 06:56:36', 0),
(568, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 07:03:38', 0),
(569, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 07:03:39', 0),
(570, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 07:10:57', 0),
(571, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 07:13:02', 0),
(572, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 07:13:08', 0),
(573, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 07:13:30', 0),
(574, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 09:10:15', 0),
(575, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 09:10:40', 0),
(576, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 09:20:41', 0),
(577, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=2, Doc=10, Staff=10', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=2, Doc=10, Staff=10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 09:21:14', 0),
(578, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=1, Doc=10, Staff=10', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=1, Doc=10, Staff=10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 09:21:24', 0),
(579, NULL, 1006, 'Super Admin', 'Super Admin', 'Department', 'Soft deleted Department ID 14', 'Department', 'Soft deleted Department ID 14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 09:24:49', 0),
(580, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-29 09:29:55', 0),
(581, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 09:31:57', 0),
(582, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=2, Doc=10, Staff=10', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=2, Doc=10, Staff=10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 09:32:59', 0),
(583, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=3, Doc=10, Staff=10', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=3, Doc=10, Staff=10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 09:33:45', 0),
(584, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=0, Doc=10, Staff=10', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=0, Doc=10, Staff=10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 09:34:11', 0),
(585, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=1, Doc=10, Staff=10', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=1, Doc=10, Staff=10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 09:34:38', 0),
(586, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=1, Doc=10, Staff=3', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=1, Doc=10, Staff=3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 09:36:43', 0),
(587, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=1, Doc=3, Staff=3', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=1, Doc=3, Staff=3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 09:39:39', 0),
(588, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=1, Doc=3, Staff=3', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=1, Doc=3, Staff=3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 09:39:41', 0),
(589, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=1, Doc=2, Staff=3', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=1, Doc=2, Staff=3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 09:40:24', 0),
(590, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=1, Doc=2, Staff=3', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=1, Doc=2, Staff=3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 09:57:40', 0),
(591, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:03:22', 0),
(592, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:04:38', 0),
(593, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:04:40', 0),
(594, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:05:05', 0),
(595, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:05:06', 0),
(596, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:05:06', 0),
(597, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:05:06', 0),
(598, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:05:47', 0),
(599, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:06:36', 0),
(600, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:08:12', 0),
(601, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:08:53', 0),
(602, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:11:35', 0),
(603, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:11:53', 0),
(604, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:12:03', 0),
(605, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:12:15', 0),
(606, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:12:59', 0),
(607, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:15:26', 0),
(608, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:15:51', 0),
(609, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:16:17', 0),
(610, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:16:29', 0),
(611, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:16:45', 0),
(612, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:17:07', 0),
(613, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:17:21', 0),
(614, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:17:48', 0),
(615, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:18:28', 0),
(616, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:18:58', 0),
(617, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:19:57', 0),
(618, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:20:11', 0),
(619, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:21:07', 0),
(620, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:21:12', 0),
(621, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Updated status of Hospital ID 9 to Active', 'Hospital', 'Updated status of Hospital ID 9 to Active', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:21:12', 0),
(622, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:21:12', 0),
(623, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:21:25', 0),
(624, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:21:29', 0),
(625, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Updated status of Hospital ID 9 to Inactive', 'Hospital', 'Updated status of Hospital ID 9 to Inactive', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:21:29', 0),
(626, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:21:29', 0),
(627, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:21:54', 0),
(628, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:23:52', 0),
(629, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:25:18', 0),
(630, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:25:23', 0),
(631, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:25:52', 0),
(632, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:26:02', 0),
(633, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:26:54', 0),
(634, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:27:29', 0),
(635, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=2, Doc=2, Staff=3', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=2, Doc=2, Staff=3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:27:39', 0),
(636, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:28:24', 0),
(637, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=2, Doc=2, Staff=5', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=2, Doc=2, Staff=5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:28:32', 0),
(638, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:30:18', 0),
(639, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=2, Doc=5, Staff=5', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=2, Doc=5, Staff=5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:30:25', 0),
(640, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:36:26', 0),
(641, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=1, Doc=5, Staff=5', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=1, Doc=5, Staff=5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:36:37', 0),
(642, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=1, Doc=5, Staff=5', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=1, Doc=5, Staff=5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:36:40', 0),
(643, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=1, Doc=5, Staff=5', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=1, Doc=5, Staff=5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:52:04', 0),
(644, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 10:52:14', 0),
(645, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 11:17:16', 0),
(646, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 11:17:52', 0),
(647, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 11:18:10', 0),
(648, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 11:22:25', 0),
(649, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 11:23:06', 0),
(650, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 11:28:24', 0),
(651, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 11:28:33', 0),
(652, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 11:29:30', 0),
(653, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 11:30:44', 0),
(654, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 11:31:22', 0),
(655, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 11:35:20', 0),
(656, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 11:36:46', 0),
(657, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 11:36:51', 0),
(658, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 11:38:05', 0),
(659, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 11:38:45', 0),
(660, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 11:38:58', 0),
(661, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 11:39:13', 0),
(662, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 11:39:15', 0),
(663, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 11:39:28', 0),
(664, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 11:42:24', 0),
(665, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 11:42:58', 0),
(666, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 11:43:39', 0),
(667, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-29 11:44:31', 0),
(668, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 05:07:28', 0),
(669, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 05:07:46', 0),
(670, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 05:07:57', 0),
(671, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 05:09:34', 0),
(672, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 05:12:45', 0),
(673, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 05:14:46', 0),
(674, NULL, 1006, 'Super Admin', 'Super Admin', 'User', 'Updated role of User ID 1023 to Patient', 'User', 'Updated role of User ID 1023 to Patient', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 05:16:00', 0),
(675, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 05:26:23', 0);
INSERT INTO `audit_logs` (`log_id`, `hospital_id`, `register_id`, `user_name`, `user_role`, `action_type`, `description`, `module`, `action`, `ip_address`, `user_agent`, `browser`, `created_at`, `delete_flag`) VALUES
(676, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 05:40:06', 0),
(677, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 05:54:12', 0),
(678, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 05:54:15', 0),
(679, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 05:55:54', 0),
(680, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 05:57:44', 0),
(681, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 05:59:00', 0),
(682, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 06:03:23', 0),
(683, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 06:04:03', 0),
(684, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 06:06:55', 0),
(685, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 06:07:17', 0),
(686, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 06:08:57', 0),
(687, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 06:11:40', 0),
(688, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 06:16:10', 0),
(689, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 06:31:49', 0),
(690, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 06:31:50', 0),
(691, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Added new hospital: Datara (ID: 10)', 'Hospital', 'Added new hospital: Datara (ID: 10)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 06:34:06', 0),
(692, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital Admin', 'Added new hospital admin: Dr.Rahulm Kumbhar (ID: 1059) for hospital: Datara', 'Hospital Admin', 'Added new hospital admin: Dr.Rahulm Kumbhar (ID: 1059) for hospital: Datara', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 06:34:06', 0),
(693, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 06:36:31', 0),
(694, NULL, 1006, 'Super Admin', 'Super Admin', 'Role', 'Deleted role: Driver (ID: 15)', 'Role', 'Deleted role: Driver (ID: 15)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 06:41:30', 0),
(695, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 06:48:16', 0),
(696, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 06:48:17', 0),
(697, NULL, 1006, 'Super Admin', 'Super Admin', 'Role', 'Deleted role: Driver (ID: 17)', 'Role', 'Deleted role: Driver (ID: 17)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 06:49:35', 0),
(698, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 06:54:42', 0),
(699, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 06:54:43', 0),
(700, NULL, 1006, 'Super Admin', 'Super Admin', 'Hospital', 'Updated hospital: Satara Ruby', 'Hospital', 'Updated hospital: Satara Ruby', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 07:07:06', 0),
(701, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 07:07:11', 0),
(702, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 07:07:14', 0),
(703, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 07:10:50', 0),
(704, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 07:11:09', 0),
(705, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 07:11:23', 0),
(706, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 07:11:25', 0),
(707, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 07:11:33', 0),
(708, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 07:12:00', 0),
(709, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 07:12:01', 0),
(710, 5, 1040, 'Rahul Namya', 'Lab Technician', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 07:12:12', 0),
(711, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 07:12:17', 0),
(712, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 07:12:44', 0),
(713, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 07:12:49', 0),
(714, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 07:12:58', 0),
(715, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 07:15:12', 0),
(716, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 07:15:23', 0),
(717, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 07:17:11', 0),
(718, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 07:17:13', 0),
(719, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 07:17:28', 0),
(720, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 07:17:39', 0),
(721, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 07:22:14', 0),
(722, NULL, 1006, 'Super Admin', 'Super Admin', 'User', 'Updated role of User ID 1060 to Doctor', 'User', 'Updated role of User ID 1060 to Doctor', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 07:26:38', 0),
(723, 5, 1023, 'Niraj Bhute', 'Patient', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 07:26:45', 0),
(724, 5, 1060, 'Prakash Machale', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 07:28:08', 0),
(725, NULL, 1006, 'Super Admin', 'Super Admin', 'User', 'Updated role of User ID 1060 to Doctor', 'User', 'Updated role of User ID 1060 to Doctor', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 07:28:27', 0),
(726, NULL, 1006, 'Super Admin', 'Super Admin', 'User', 'Updated role of User ID 1060 to Accountant', 'User', 'Updated role of User ID 1060 to Accountant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 07:29:39', 0),
(727, NULL, 1006, 'Super Admin', 'Super Admin', 'User', 'Updated role of User ID 1060 to Admin', 'User', 'Updated role of User ID 1060 to Admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 07:29:45', 0),
(728, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 07:32:07', 0),
(729, 5, 1028, 'Ayush Nipane', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 07:32:25', 0),
(730, NULL, 1006, 'Super Admin', 'Super Admin', 'User', 'Updated role of User ID 1060 to Doctor', 'User', 'Updated role of User ID 1060 to Doctor', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 07:32:45', 0),
(731, NULL, 1006, 'Super Admin', 'Super Admin', 'User', 'Updated role of User ID 1061 to Doctor', 'User', 'Updated role of User ID 1061 to Doctor', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 07:33:51', 0),
(732, 5, 1023, 'Niraj Bhutes', 'Patient', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 07:35:34', 0),
(733, 5, 1028, 'Ayush Nipanes', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 07:35:56', 0),
(734, 5, 1023, 'Niraj Bhutes', 'Patient', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 07:37:08', 0),
(735, 5, 1060, 'Prakash Machale', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 07:39:48', 0),
(736, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 07:40:56', 0),
(737, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 07:43:36', 0),
(738, NULL, 1006, 'Super Admin', 'Super Admin', 'User', 'Updated role of User ID 1063 to Doctor (assigned by )', 'User', 'Updated role of User ID 1063 to Doctor (assigned by )', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 07:44:14', 0),
(739, NULL, 1006, 'Super Admin', 'Super Admin', 'User', 'Updated role of User ID 1062 to Nurse (assigned by )', 'User', 'Updated role of User ID 1062 to Nurse (assigned by )', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 07:44:38', 0),
(740, 5, 1023, 'Niraj Bhutes', 'Patient', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 07:45:16', 0),
(741, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 08:02:08', 0),
(742, 5, 1023, 'Niraj Bhutes', 'Patient', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 08:36:58', 0),
(743, 5, 1028, 'Ayush Nipanes', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 09:37:13', 0),
(744, 5, 1040, 'Rahul Namya', 'Lab Technician', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 09:38:28', 0),
(745, 5, 1023, 'Niraj Bhutes', 'Patient', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 09:39:06', 0),
(746, 5, 1040, 'Rahul Namya', 'Lab Technician', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 09:42:19', 0),
(747, 5, 1028, 'Ayush Nipanes', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 09:43:15', 0),
(748, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 10:31:18', 0),
(749, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 10:31:33', 0),
(750, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 10:31:35', 0),
(751, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 10:31:49', 0),
(752, NULL, 1006, 'Super Admin', 'Super Admin', 'User', 'Updated role of User ID 1058 to Receptionist (assigned by )', 'User', 'Updated role of User ID 1058 to Receptionist (assigned by )', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 10:38:14', 0),
(753, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 10:49:09', 0),
(754, 6, 1009, 'Abhishek mandhare', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 10:50:15', 0),
(755, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 10:50:21', 0),
(756, NULL, 1006, 'Super Admin', 'Super Admin', 'User', 'Updated role of User ID 1064 to Receptionist (assigned by )', 'User', 'Updated role of User ID 1064 to Receptionist (assigned by )', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 10:50:34', 0),
(757, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 11:21:51', 0),
(758, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 11:23:15', 0),
(759, 6, 1064, 'Shiva Tevar', 'Receptionist', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 11:23:18', 0),
(760, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 11:23:23', 0),
(761, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 11:26:09', 0),
(762, 5, 1028, 'Ayush Nipanes', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 11:26:49', 0),
(763, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 11:30:52', 0),
(764, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 12:13:40', 0),
(765, 5, 1028, 'Ayush Nipanes', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 12:20:46', 0),
(766, 5, 1023, 'Niraj Bhutes', 'Patient', 'Logout', 'User logged out', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 12:21:25', 0),
(767, 6, 1064, 'Shiva Tevar', 'Receptionist', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 12:26:29', 0),
(768, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 12:44:11', 0),
(769, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 12:44:27', 0),
(770, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 12:46:06', 0),
(771, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 12:47:09', 0),
(772, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 12:47:10', 0),
(773, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 12:49:25', 0),
(774, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 12:50:11', 0),
(775, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 12:50:22', 0),
(776, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 12:50:25', 0),
(777, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 12:50:52', 0),
(778, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 12:53:09', 0),
(779, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 12:53:47', 0),
(780, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 12:53:59', 0),
(781, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 16:13:56', 0),
(782, NULL, 1006, 'Super Admin', 'Super Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 16:18:01', 0),
(783, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 16:18:03', 0),
(784, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 16:18:56', 0),
(785, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 16:19:00', 0),
(786, 6, 1009, 'Abhishek mandhare', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 16:19:17', 0),
(787, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 16:19:33', 0),
(788, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 16:19:42', 0),
(789, 6, 1009, 'Abhishek mandhare', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 16:26:48', 0),
(790, 6, 1009, 'Abhishek mandhare', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-30 16:42:01', 0),
(791, NULL, 1006, 'Super Admin', 'Super Admin', 'User', 'Updated role of User ID 1061 to Doctor (assigned by )', 'User', 'Updated role of User ID 1061 to Doctor (assigned by )', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 16:42:19', 0),
(792, NULL, 1006, 'Super Admin', 'Super Admin', 'User', 'Updated role of User ID 1066 to Doctor (assigned by )', 'User', 'Updated role of User ID 1066 to Doctor (assigned by )', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 16:42:24', 0),
(793, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 17:02:49', 0),
(794, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 17:24:52', 0),
(795, 5, 1068, 'Priya Joshi', 'Doctor', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-30 17:25:26', 0),
(796, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 06:04:00', 0),
(797, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 06:04:18', 0),
(798, 6, 1009, 'Abhishek mandhare', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-31 06:07:34', 0),
(799, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-31 06:09:53', 0),
(800, 6, 1009, 'Abhishek mandhare', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-31 06:23:25', 0),
(801, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:05:40', 0),
(802, NULL, 1006, 'Super Admin', 'Super Admin', 'Audit Logs', 'User viewed audit logs', 'Audit Logs', 'User viewed audit logs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:07:02', 0),
(803, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:07:11', 0),
(804, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:07:53', 0),
(805, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:09:09', 0),
(806, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:14:28', 0),
(807, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:14:30', 0),
(808, NULL, 1006, 'Super Admin', 'Super Admin', 'Staff', 'Deleted Staff ID 5', 'Staff', 'Deleted Staff ID 5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:16:35', 0),
(809, NULL, 1006, 'Super Admin', 'Super Admin', 'Staff', 'Updated Staff ID 21', 'Staff', 'Updated Staff ID 21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:18:19', 0),
(810, NULL, 1006, 'Super Admin', 'Super Admin', 'Staff', 'Updated Staff ID 21', 'Staff', 'Updated Staff ID 21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:18:29', 0),
(811, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:18:35', 0),
(812, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:18:59', 0),
(813, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:19:01', 0),
(814, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:23:07', 0),
(815, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=2, Doc=5, Staff=5', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=2, Doc=5, Staff=5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:23:59', 0),
(816, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=2, Doc=3, Staff=5', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=2, Doc=3, Staff=5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:24:29', 0),
(817, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=2, Doc=2, Staff=5', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=2, Doc=2, Staff=5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:24:34', 0),
(818, 5, 1008, 'Dr. Sanket Pawar', 'Admin', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-31 09:31:28', 0),
(819, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=2, Doc=2, Staff=5', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=2, Doc=2, Staff=5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:34:26', 0),
(820, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=2, Doc=2, Staff=5', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=2, Doc=2, Staff=5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:35:38', 0),
(821, 5, 1058, 'abhishek mandhare', 'Receptionist', 'Logout', 'User logged out', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Chrome', '2026-07-31 09:45:38', 0),
(822, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=2, Doc=2, Staff=5', 'Subscription', 'Super Admin updated limits for Hospital ID 5: Dep=2, Doc=2, Staff=5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:49:03', 0),
(823, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin applied bulk limits: Dep=2, Doc=10, Staff=10 to 1 hospitals', 'Subscription', 'Super Admin applied bulk limits: Dep=2, Doc=10, Staff=10 to 1 hospitals', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:49:16', 0),
(824, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin applied bulk limits: Dep=1, Doc=10, Staff=10 to 1 hospitals', 'Subscription', 'Super Admin applied bulk limits: Dep=1, Doc=10, Staff=10 to 1 hospitals', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:49:25', 0),
(825, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin applied bulk limits: Dep=2, Doc=6, Staff=15 to 1 hospitals', 'Subscription', 'Super Admin applied bulk limits: Dep=2, Doc=6, Staff=15 to 1 hospitals', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:49:42', 0),
(826, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin applied bulk limits: Dep=2, Doc=6, Staff=15 to 1 hospitals', 'Subscription', 'Super Admin applied bulk limits: Dep=2, Doc=6, Staff=15 to 1 hospitals', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:50:07', 0),
(827, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin applied bulk limits: Dep=2, Doc=10, Staff=9 to 1 hospitals', 'Subscription', 'Super Admin applied bulk limits: Dep=2, Doc=10, Staff=9 to 1 hospitals', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:50:13', 0),
(828, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin applied bulk limits: Dep=2, Doc=10, Staff=9 to 1 hospitals', 'Subscription', 'Super Admin applied bulk limits: Dep=2, Doc=10, Staff=9 to 1 hospitals', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:50:18', 0),
(829, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin updated limits for Hospital ID 9: Dep=2, Doc=21, Staff=10', 'Subscription', 'Super Admin updated limits for Hospital ID 9: Dep=2, Doc=21, Staff=10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:52:14', 0),
(830, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin applied bulk limits: Dep=6, Doc=19, Staff=15 to 4 hospitals', 'Subscription', 'Super Admin applied bulk limits: Dep=6, Doc=19, Staff=15 to 4 hospitals', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:52:29', 0),
(831, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin updated limits for Hospital ID 7: Dep=6, Doc=19, Staff=12', 'Subscription', 'Super Admin updated limits for Hospital ID 7: Dep=6, Doc=19, Staff=12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:52:38', 0),
(832, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin applied bulk limits: Dep=1, Doc=5, Staff=5 to 4 hospitals', 'Subscription', 'Super Admin applied bulk limits: Dep=1, Doc=5, Staff=5 to 4 hospitals', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:52:55', 0),
(833, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:53:01', 0),
(834, NULL, 1006, 'Super Admin', 'Super Admin', 'Subscription', 'Super Admin applied bulk limits: Dep=2, Doc=5, Staff=5 to 4 hospitals', 'Subscription', 'Super Admin applied bulk limits: Dep=2, Doc=5, Staff=5 to 4 hospitals', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-31 09:58:29', 0);

-- --------------------------------------------------------

--
-- Table structure for table `bed_allocation`
--

CREATE TABLE `bed_allocation` (
  `allocation_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `bed_id` int(11) NOT NULL,
  `admit_date` datetime DEFAULT current_timestamp(),
  `discharge_date` datetime DEFAULT NULL,
  `status` enum('Occupied','Discharged') DEFAULT 'Occupied',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bed_master`
--

CREATE TABLE `bed_master` (
  `bed_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `bed_no` varchar(20) NOT NULL,
  `bed_type` varchar(50) DEFAULT NULL,
  `status` enum('Available','Occupied','Maintenance') DEFAULT 'Available',
  `delete_flag` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bed_master`
--

INSERT INTO `bed_master` (`bed_id`, `hospital_id`, `room_id`, `bed_no`, `bed_type`, `status`, `delete_flag`, `created_at`, `modified_at`) VALUES
(5, 5, 4, 'Bed-1', 'ICU', 'Available', 0, '2026-07-23 05:36:11', '2026-07-23 05:36:11');

-- --------------------------------------------------------

--
-- Table structure for table `billing`
--

CREATE TABLE `billing` (
  `id` int(11) NOT NULL,
  `bill_no` varchar(50) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `bill_date` date DEFAULT NULL,
  `service_name` varchar(200) DEFAULT NULL,
  `qty` int(11) DEFAULT 1,
  `rate` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT NULL,
  `paid_amount` decimal(10,2) DEFAULT NULL,
  `pending_amount` decimal(10,2) DEFAULT NULL,
  `payment_mode` enum('Cash','Card','UPI','Bank') DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delete_flag` tinyint(1) DEFAULT 0,
  `hospital_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delete_flag` tinyint(1) DEFAULT 0,
  `hospital_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`id`, `department_name`, `description`, `status`, `created_at`, `modified_at`, `delete_flag`, `hospital_id`) VALUES
(4, 'Pediatricss', 'Provides medical care for infants, children, and adolescents, including immunization, growth monitoring, and treatment of childhood illnesses.', 'Active', '2026-07-20 10:54:33', '2026-07-20 10:54:33', 0, 6),
(5, 'Orthopedics', 'Specializes in treating bone, joint, muscle, ligament, and spine disorders, including fractures and joint replacement surgeries.', 'Active', '2026-07-20 10:54:56', '2026-07-22 13:18:49', 0, 6),
(6, 'Cardiology', 'sdfv', 'Active', '2026-07-20 19:02:52', '2026-07-22 07:44:26', 0, 5),
(7, 'Sexiology', '', 'Active', '2026-07-28 12:05:56', '2026-07-28 12:17:35', 1, 5),
(8, 'Sexiology', '', 'Active', '2026-07-28 12:07:23', '2026-07-28 12:17:33', 1, 5),
(9, 'asdfghj', '', 'Active', '2026-07-28 12:09:24', '2026-07-28 12:17:31', 1, 5),
(10, 'asdfghj', '', 'Active', '2026-07-28 12:09:30', '2026-07-28 12:17:28', 1, 5),
(11, 'asdfghj', '', 'Active', '2026-07-28 12:09:35', '2026-07-28 12:17:24', 1, 5),
(12, 'asdfghj', '', 'Active', '2026-07-28 12:11:16', '2026-07-28 12:17:20', 1, 5),
(13, 'Biology', '', 'Active', '2026-07-28 12:18:04', '2026-07-28 12:20:17', 1, 5),
(14, 'Heart', '', 'Active', '2026-07-29 09:21:53', '2026-07-29 09:24:49', 1, 5),
(15, 'Heart', '', 'Active', '2026-07-29 09:28:23', '2026-07-29 09:29:52', 1, 5),
(16, 'Heart', '', 'Active', '2026-07-29 09:33:07', '2026-07-29 09:33:13', 1, 5),
(17, 'Heart', '', 'Active', '2026-07-29 09:33:19', '2026-07-29 09:34:05', 1, 5),
(18, 'Biology', '', 'Active', '2026-07-29 09:33:58', '2026-07-29 09:34:02', 1, 5),
(19, 'Emergency & Trauma', '', 'Active', '2026-07-31 09:24:05', '2026-07-31 09:24:05', 0, 5);

-- --------------------------------------------------------

--
-- Table structure for table `discharge_summary`
--

CREATE TABLE `discharge_summary` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `admission_date` date DEFAULT NULL,
  `discharge_date` date DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment_given` text DEFAULT NULL,
  `patient_condition` text DEFAULT NULL,
  `discharge_medicine` text DEFAULT NULL,
  `followup_date` date DEFAULT NULL,
  `doctor_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delete_flag` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor`
--

CREATE TABLE `doctor` (
  `doctor_id` int(11) NOT NULL,
  `register_id` int(11) NOT NULL,
  `doctor_name` varchar(150) NOT NULL,
  `doctor_image` varchar(255) DEFAULT NULL,
  `mobile` varchar(15) NOT NULL,
  `email` varchar(150) NOT NULL,
  `department` varchar(100) NOT NULL,
  `qualification` varchar(150) NOT NULL,
  `specialization` varchar(150) NOT NULL,
  `experience` int(11) NOT NULL,
  `consultation_fee` decimal(10,2) NOT NULL,
  `timing` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delete_flag` int(2) NOT NULL DEFAULT 0,
  `hospital_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor`
--

INSERT INTO `doctor` (`doctor_id`, `register_id`, `doctor_name`, `doctor_image`, `mobile`, `email`, `department`, `qualification`, `specialization`, `experience`, `consultation_fee`, `timing`, `address`, `status`, `created_at`, `modified_at`, `delete_flag`, `hospital_id`) VALUES
(5, 1015, 'Chaitanya Patil', '', '', 'wchaitanyapatil@gmail.com', 'Pediatricss', '', '', 0, 0.00, '', 'Powai Naka', 'Active', '2026-07-20 13:08:12', '2026-07-23 14:56:08', 0, 5),
(8, 1028, 'Ayush Nipanes', 'documents/patients/images/1784726240_images.jpg', '7058094949', 'ayushhnipane@gmail.com', 'Cardiology', 'BTech', 'MBBS', 8, 300.00, '9:00 to 5:00', 'Ambedare, Dhanavadewadi', 'Active', '2026-07-21 06:45:02', '2026-07-30 07:35:49', 0, 5),
(9, 1029, 'Dr Shivatej Katkar', '', '4654617845', 'shivatejk033@gmail.com', 'Cardiology', 'hvh', 'Btech', 5, 5609.00, '', '', 'Active', '2026-07-21 06:51:12', '2026-07-23 14:56:03', 0, 6),
(10, 1052, 'Mohan Joshi', 'documents/doctors/images/WhatsApp Image 2025-04-25 at 7.23.13 PM.jpeg', '7261998814', 'mohan@gmail.com', 'Pediatricss', '', '', 0, 0.00, '', 'Satara', 'Active', '2026-07-27 09:33:11', '2026-07-27 09:33:11', 0, 6),
(13, 1061, 'Rohan Kurne', '', '7261998814', 'rohan@gmail.com', 'Cardiology', '', '', 0, 0.00, '', 'NA', 'Active', '2026-07-30 07:32:59', '2026-07-30 07:42:26', 1, 5),
(16, 1066, 'Vedant Mohite', '', '', 'abhimandhare@gmail.com', '', '', '', 0, 0.00, '', '', 'Active', '2026-07-30 16:41:11', '2026-07-30 16:41:11', 0, 6),
(18, 1068, 'Priya Joshi', '', '', 'priya@gmail.com', 'Cardiology', '', '', 0, 0.00, '', '', 'Active', '2026-07-30 17:24:32', '2026-07-30 17:24:32', 0, 5);

-- --------------------------------------------------------

--
-- Table structure for table `email_templates`
--

CREATE TABLE `email_templates` (
  `template_id` int(11) NOT NULL,
  `template_name` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delete_flag` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_templates`
--

INSERT INTO `email_templates` (`template_id`, `template_name`, `subject`, `body`, `status`, `created_at`, `updated_at`, `delete_flag`) VALUES
(2, 'reset_password', 'Ultra Hospital - Password Reset Request', 'Dear {user_name},\n\nWe received a request to reset your Ultra Hospital account password.\n\nYour One-Time Password (OTP) is:\n\n{otp}\n\nThis OTP is valid for {expiry_time} minutes.\n\nIf you did not request a password reset, please ignore this email or contact the hospital administrator immediately.\n\nRegards,\nUltra Hospital Team', 'Active', '2026-07-16 02:26:36', '2026-07-28 12:06:15', 0),
(4, 'successful_registration', 'Welcome to {UltraHospital} - Your Hospital Registration is Successful', '<html>\n<head>\n    <title>UltraHospital Notification</title>\n</head>\n\n<body style=\"margin:0;padding:30px;background:#f4f6f9;font-family:Arial,Helvetica,sans-serif;\">\n\n<table width=\"650\" align=\"center\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid #dddddd;\">\n\n    <!-- Header -->\n    <tr>\n        <td style=\"background:#0d6efd;padding:30px;text-align:center;\">\n\n            {hospital_logo}\n\n            <h1 style=\"margin:15px 0 5px 0;color:#ffffff;font-size:30px;font-weight:bold;\">\n                {hospital_name}\n            </h1>\n\n            <p style=\"margin:0;color:#dbeafe;font-size:15px;\">\n                UltraHospital Management System\n            </p>\n\n        </td>\n    </tr>\n\n    <!-- Body -->\n    <tr>\n        <td style=\"padding:35px;\">\n\n            <p style=\"font-size:16px;color:#333;\">\n                Dear <strong>{admin_name}</strong>,\n            </p>\n\n            <p style=\"font-size:15px;color:#555;line-height:24px;\">\n                {message}\n            </p>\n\n            <table width=\"100%\" cellpadding=\"10\" cellspacing=\"0\" style=\"margin-top:25px;border-collapse:collapse;border:1px solid #e5e5e5;\">\n\n                <tr style=\"background:#f8f9fa;\">\n                    <td width=\"40%\"><strong>Hospital Name</strong></td>\n                    <td>{hospital_name}</td>\n                </tr>\n\n                <tr>\n                    <td><strong>Hospital Code</strong></td>\n                    <td>{hospital_code}</td>\n                </tr>\n\n                <tr style=\"background:#f8f9fa;\">\n                    <td><strong>Email Address</strong></td>\n                    <td>{email}</td>\n                </tr>\n\n                <tr>\n                    <td><strong>Temporary Password</strong></td>\n                    <td>{password}</td>\n                </tr>\n\n            </table>\n\n            <p style=\"margin-top:30px;color:#555;\">\n                Click the button below to login to your account.\n            </p>\n\n            <div style=\"text-align:center;margin:35px 0;\">\n\n                <a href=\"{login_link}\"\n                   style=\"background:#0d6efd;\n                          color:#ffffff;\n                          padding:14px 30px;\n                          text-decoration:none;\n                          border-radius:6px;\n                          font-size:16px;\n                          font-weight:bold;\n                          display:inline-block;\">\n\n                    Login to UltraHospital\n\n                </a>\n\n            </div>\n\n            <div style=\"background:#f8f9fa;border-left:5px solid #0d6efd;padding:18px;border-radius:5px;\">\n\n                <strong style=\"color:#0d6efd;\">Important</strong>\n\n                <ul style=\"margin-top:10px;color:#555;line-height:24px;\">\n                    <li>This password is temporary.</li>\n                    <li>Please change your password immediately after your first login.</li>\n                    <li>Keep your login credentials secure.</li>\n                </ul>\n\n            </div>\n\n            <p style=\"margin-top:25px;color:#555;\">\n                If you did not request this account, please contact your hospital administrator immediately.\n            </p>\n\n            <br>\n\n            <p style=\"color:#555;\">\n                Regards,<br>\n                <strong>UltraHospital Team</strong>\n            </p>\n\n        </td>\n    </tr>\n\n    <!-- Footer -->\n    <tr>\n        <td style=\"background:#f8f9fa;padding:20px;text-align:center;border-top:1px solid #e5e5e5;\">\n\n            <p style=\"margin:0;font-size:13px;color:#666;\">\n                © {year} {hospital_name}. All Rights Reserved.\n            </p>\n\n            <p style=\"margin:8px 0 0 0;font-size:12px;color:#999;\">\n                Powered by UltraHospital Management System\n            </p>\n\n        </td>\n    </tr>\n\n</table>\n\n</body>\n</html>', 'Active', '2026-07-16 02:42:11', '2026-07-20 18:39:43', 0);

-- --------------------------------------------------------

--
-- Table structure for table `hospital_admin`
--

CREATE TABLE `hospital_admin` (
  `admin_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `register_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delete_flag` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hospital_admin`
--

INSERT INTO `hospital_admin` (`admin_id`, `hospital_id`, `register_id`, `full_name`, `mobile`, `email`, `created_at`, `modified_at`, `delete_flag`) VALUES
(7, 5, 1008, 'Dr. Sanket Pawar', '7894512588', 'rohankurne12@gmail.com', '2026-07-20 05:57:32', '2026-07-22 11:27:28', 0),
(8, 6, 1009, 'Abhishek mandhare', '07261998814', 'abhimandhare469@gmail.com', '2026-07-20 07:36:19', '2026-07-20 10:56:07', 0),
(9, 7, 1011, 'Dr. Sam Dapal', '', 'rahulkumbhar2801@gmail.com', '2026-07-20 11:49:54', '2026-07-20 11:49:54', 0);

-- --------------------------------------------------------

--
-- Table structure for table `hospital_master`
--

CREATE TABLE `hospital_master` (
  `hospital_id` int(11) NOT NULL,
  `hospital_name` varchar(255) NOT NULL,
  `hospital_code` varchar(50) NOT NULL,
  `hospital_logo` varchar(255) DEFAULT NULL,
  `hospital_type` varchar(100) DEFAULT NULL,
  `registration_number` varchar(100) DEFAULT NULL,
  `gst_number` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `pincode` varchar(10) DEFAULT NULL,
  `established_year` varchar(4) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `website` varchar(150) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delete_flag` tinyint(1) DEFAULT 0,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hospital_master`
--

INSERT INTO `hospital_master` (`hospital_id`, `hospital_name`, `hospital_code`, `hospital_logo`, `hospital_type`, `registration_number`, `gst_number`, `address`, `city`, `state`, `country`, `pincode`, `established_year`, `phone`, `website`, `status`, `created_at`, `modified_at`, `delete_flag`, `email`) VALUES
(5, 'City Hospital', 'B43PEU', 'documents/hospital/hospital_1784527052_6a5db8cca46e5.jpg', 'Multi-Speciality', 'HOSP/2026/001234', '', 'satara', 'Satara', 'Maharashtra', 'India', '454858', '', '9309038170', '', 'Active', '2026-07-20 05:57:32', '2026-07-22 11:06:03', 0, ''),
(6, 'Kadam Hospital', 'EFRKGZ', 'documents/hospital/1785478902_applemacbook.webp', 'Multi-Speciality', 'HOSP/2026/1234', '27AAACP0165G3ZN', 'Pune', 'Satara', 'Maharashtra', 'India', '415019', '', '9876543212', '', 'Active', '2026-07-20 07:36:19', '2026-07-31 06:21:42', 0, ''),
(7, 'Saygaonkar Hospital', 'CW74RP', '', 'Multi-Speciality', '', '', '', 'Pune', 'Maharashtra', 'India', '', NULL, '7058094949', '', 'Active', '2026-07-20 11:49:54', '2026-07-28 11:09:02', 0, NULL),
(9, 'Satara Ruby', 'ZSQRZQ', '', 'Multi-Speciality', '', '', '', 'awsdfghb', 'asdfgb', 'India', '123456', '', '1234567890', '', 'Active', '2026-07-20 15:09:44', '2026-07-30 07:07:06', 0, '');

-- --------------------------------------------------------

--
-- Table structure for table `ipd_admissions`
--

CREATE TABLE `ipd_admissions` (
  `id` int(11) NOT NULL,
  `admission_no` varchar(50) DEFAULT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `appointment_type` varchar(100) DEFAULT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `department` varchar(150) DEFAULT NULL,
  `ward_id` int(11) DEFAULT NULL,
  `room_no` varchar(50) DEFAULT NULL,
  `bed_no` varchar(50) DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `appointment_time` time DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `disease_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `since_when` varchar(100) DEFAULT NULL,
  `severity` enum('Mild','Moderate','Severe') DEFAULT NULL,
  `previous_history` text DEFAULT NULL,
  `current_medicines` text DEFAULT NULL,
  `prescription_file` varchar(255) DEFAULT NULL,
  `lab_report_file` varchar(255) DEFAULT NULL,
  `xray_file` varchar(255) DEFAULT NULL,
  `mri_file` varchar(255) DEFAULT NULL,
  `ctscan_file` varchar(255) DEFAULT NULL,
  `other_document` varchar(255) DEFAULT NULL,
  `status` enum('Admitted','Discharged') DEFAULT 'Admitted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delete_flag` tinyint(1) DEFAULT 0,
  `hospital_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ipd_admissions`
--

INSERT INTO `ipd_admissions` (`id`, `admission_no`, `appointment_id`, `appointment_type`, `patient_id`, `doctor_id`, `department`, `ward_id`, `room_no`, `bed_no`, `admission_date`, `appointment_time`, `duration`, `disease_reason`, `notes`, `symptoms`, `since_when`, `severity`, `previous_history`, `current_medicines`, `prescription_file`, `lab_report_file`, `xray_file`, `mri_file`, `ctscan_file`, `other_document`, `status`, `created_at`, `modified_at`, `delete_flag`, `hospital_id`) VALUES
(1, 'IPD-20260722-7033', 7, 'Follow-up', 6, 9, 'Cardiology', 1, '', 'BED-1', '2026-07-22', '05:00:00', '15', 'Fever', '', '', '', '', '', '', '', '', '', '', '', '', 'Admitted', '2026-07-22 12:18:23', '2026-07-22 12:18:23', 0, 5);

-- --------------------------------------------------------

--
-- Table structure for table `ipd_treatment_master`
--

CREATE TABLE `ipd_treatment_master` (
  `treatment_master_id` int(11) NOT NULL,
  `ipd_id` int(11) NOT NULL,
  `status` enum('Active','Completed') DEFAULT 'Active',
  `delete_flag` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_bill`
--

CREATE TABLE `lab_bill` (
  `bill_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `bill_no` varchar(30) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT NULL,
  `final_amount` decimal(10,2) DEFAULT NULL,
  `payment_status` enum('Pending','Paid','Partial') DEFAULT NULL,
  `payment_mode` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_orders`
--

CREATE TABLE `lab_orders` (
  `order_id` int(11) NOT NULL,
  `order_no` varchar(20) DEFAULT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `order_date` date DEFAULT NULL,
  `document_category` varchar(100) NOT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `clinical_notes` text DEFAULT NULL,
  `payment_status` enum('Pending','Partial','Paid') DEFAULT 'Pending',
  `order_status` enum('Pending','Assigned','Sample Collected','In Process','Completed','Cancelled') DEFAULT 'Pending',
  `technician_id` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delete_flag` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_orders`
--

INSERT INTO `lab_orders` (`order_id`, `order_no`, `patient_id`, `doctor_id`, `hospital_id`, `order_date`, `document_category`, `total_amount`, `clinical_notes`, `payment_status`, `order_status`, `technician_id`, `remarks`, `created_by`, `updated_by`, `created_at`, `updated_at`, `delete_flag`) VALUES
(17, 'LAB202607300001', 6, 1028, 5, '2026-07-30', '', 300.00, '', 'Pending', 'Completed', 11, NULL, 1028, NULL, '2026-07-30 10:06:01', '2026-07-30 10:06:13', 0),
(18, 'LAB202607300002', 28, 8, 5, '2026-07-30', '', 150.00, '', 'Pending', 'Completed', 11, NULL, 1028, NULL, '2026-07-30 10:22:41', '2026-07-30 10:29:06', 0),
(19, 'LAB202607300003', 6, 1028, 5, '2026-07-30', '', 250.00, '', 'Pending', 'Assigned', 11, NULL, 1028, NULL, '2026-07-30 12:38:14', '2026-07-30 12:38:14', 0),
(20, 'LAB202607300004', 28, 1028, 5, '2026-07-30', 'pre-ot', 100.00, '', 'Pending', 'Assigned', 11, NULL, 1028, NULL, '2026-07-30 12:40:32', '2026-07-30 12:40:32', 0),
(21, 'LAB202607300005', 28, 1028, 5, '2026-07-30', 'Operation Theater', 150.00, '', 'Pending', 'Completed', 11, NULL, 1028, NULL, '2026-07-30 12:42:16', '2026-07-30 12:45:00', 0);

-- --------------------------------------------------------

--
-- Table structure for table `lab_order_details`
--

CREATE TABLE `lab_order_details` (
  `detail_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `report_status` enum('Pending','In Process','Completed') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `delete_flag` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_order_details`
--

INSERT INTO `lab_order_details` (`detail_id`, `order_id`, `test_id`, `price`, `report_status`, `created_at`, `delete_flag`) VALUES
(30, 17, 21, 150.00, 'Pending', '2026-07-30 10:06:01', 0),
(31, 17, 20, 150.00, 'Pending', '2026-07-30 10:06:01', 0),
(32, 18, 22, 150.00, 'Pending', '2026-07-30 10:22:41', 0),
(33, 19, 19, 100.00, 'Pending', '2026-07-30 12:38:14', 0),
(34, 19, 21, 150.00, 'Pending', '2026-07-30 12:38:14', 0),
(35, 20, 19, 100.00, 'Pending', '2026-07-30 12:40:32', 0),
(36, 21, 22, 150.00, 'Pending', '2026-07-30 12:42:16', 0);

-- --------------------------------------------------------

--
-- Table structure for table `lab_reports`
--

CREATE TABLE `lab_reports` (
  `report_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `detail_id` int(11) DEFAULT NULL,
  `hospital_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `technician_id` int(11) DEFAULT NULL,
  `report_no` varchar(30) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `report_file` varchar(255) DEFAULT NULL,
  `report_status` enum('Draft','Completed','Corrected') DEFAULT 'Draft',
  `corrected_report_file` varchar(255) DEFAULT NULL,
  `corrected_by` int(11) DEFAULT NULL,
  `corrected_date` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `delete_flag` tinyint(11) NOT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_reports`
--

INSERT INTO `lab_reports` (`report_id`, `order_id`, `detail_id`, `hospital_id`, `patient_id`, `doctor_id`, `technician_id`, `report_no`, `report_date`, `report_file`, `report_status`, `corrected_report_file`, `corrected_by`, `corrected_date`, `remarks`, `created_at`, `delete_flag`, `updated_at`) VALUES
(28, 17, 30, 5, 6, 1028, 11, 'RPT202607300030', '2026-07-30', '', 'Completed', NULL, NULL, NULL, 'Result: 5', '2026-07-30 10:06:28', 0, '2026-07-30 10:06:28'),
(29, 17, 31, 5, 6, 1028, 11, 'RPT202607300031', '2026-07-30', '', 'Completed', NULL, NULL, NULL, 'Result: 5000', '2026-07-30 10:06:28', 0, '2026-07-30 10:06:28'),
(30, 18, 32, 5, 28, 8, 11, 'RPT202607300032', '2026-07-30', '', 'Completed', NULL, NULL, NULL, 'Result: 80', '2026-07-30 10:29:25', 0, '2026-07-30 10:29:25'),
(31, 21, 36, 5, 28, 8, 11, 'RPT202607300036', '2026-07-30', '', 'Completed', NULL, NULL, NULL, 'Result: 80', '2026-07-30 13:00:33', 0, '2026-07-30 13:00:33');

-- --------------------------------------------------------

--
-- Table structure for table `lab_technicians`
--

CREATE TABLE `lab_technicians` (
  `id` int(11) NOT NULL,
  `register_id` int(11) DEFAULT NULL,
  `hospital_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_technicians`
--

INSERT INTO `lab_technicians` (`id`, `register_id`, `hospital_id`, `name`, `email`, `phone`, `specialization`, `status`, `created_at`) VALUES
(1, 1040, 5, 'Rahul Namya', 'rahul@gmail.com', NULL, NULL, 'active', '2026-07-24 07:03:53'),
(2, 1057, 5, 'Harshad Nikam', 'harshad@gmail.com', NULL, NULL, 'active', '2026-07-29 10:31:19');

-- --------------------------------------------------------

--
-- Table structure for table `lab_tests`
--

CREATE TABLE `lab_tests` (
  `test_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `test_code` varchar(20) NOT NULL,
  `test_name` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `normal_range` varchar(100) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `sample_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `hospital_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delete_flag` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_tests`
--

INSERT INTO `lab_tests` (`test_id`, `category_id`, `test_code`, `test_name`, `price`, `normal_range`, `unit`, `sample_type`, `description`, `status`, `hospital_id`, `created_by`, `updated_by`, `created_at`, `updated_at`, `delete_flag`) VALUES
(19, 15, 'LAB0001', 'Hemoglobin', 100.00, '13.5–17.5', 'g/dL', 'Blood', 'Hemoglobin Test', 'Active', 5, 1008, NULL, '2026-07-28 08:36:00', '2026-07-28 08:36:00', 0),
(20, 15, 'LAB0020', 'WBC', 150.00, '4,000–11,000', 'cells/µL', 'Blood', 'White Blood Cell Count', 'Active', 5, 1008, NULL, '2026-07-28 08:37:03', '2026-07-28 08:37:03', 0),
(21, 15, 'LAB0021', 'RBC', 150.00, '4.7–6.1', 'million/µL', 'Blood', 'Red Blood Cell', 'Active', 5, 1008, NULL, '2026-07-28 08:38:16', '2026-07-28 08:38:16', 0),
(22, 16, 'LAB0022', 'Blood Sugar (Fasting)', 150.00, '70–99', 'mg/dL', 'Blood', 'Fasting Blood Sugar', 'Active', 5, 1008, NULL, '2026-07-28 08:41:22', '2026-07-28 08:41:22', 0),
(23, 16, 'LAB0023', 'HbA1c', 500.00, 'Below 5.7%', '%', 'Blood', 'Average Blood Sugar', 'Inactive', 5, 1008, NULL, '2026-07-28 08:42:41', '2026-07-29 10:25:55', 0);

-- --------------------------------------------------------

--
-- Table structure for table `lab_test_categories`
--

CREATE TABLE `lab_test_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `hospital_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delete_flag` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_test_categories`
--

INSERT INTO `lab_test_categories` (`category_id`, `category_name`, `description`, `status`, `hospital_id`, `created_by`, `updated_by`, `created_at`, `updated_at`, `delete_flag`) VALUES
(15, 'Blood', 'blood tests', 'Active', 5, 1008, 1008, '2026-07-28 08:33:30', '2026-07-28 08:38:43', 0),
(16, 'Diabetes', 'diabetes tests', 'Active', 5, 1008, 1008, '2026-07-28 08:39:21', '2026-07-31 09:30:22', 0);

-- --------------------------------------------------------

--
-- Table structure for table `lab_test_results`
--

CREATE TABLE `lab_test_results` (
  `result_id` int(11) NOT NULL,
  `order_detail_id` int(11) NOT NULL,
  `result_value` varchar(255) DEFAULT NULL,
  `normal_range` varchar(100) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `report_status` enum('Pending','Completed','Corrected') DEFAULT 'Pending',
  `entered_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_test_results`
--

INSERT INTO `lab_test_results` (`result_id`, `order_detail_id`, `result_value`, `normal_range`, `unit`, `remarks`, `report_status`, `entered_by`, `created_at`, `updated_at`) VALUES
(6, 30, '5', '4.7–6.1', 'million/µL', 'normal', 'Completed', 11, '2026-07-30 10:06:28', '2026-07-30 10:06:28'),
(7, 31, '5000', '4,000–11,000', 'cells/µL', 'normal', 'Completed', 11, '2026-07-30 10:06:28', '2026-07-30 10:06:28'),
(8, 32, '80', '70–99', 'mg/dL', 'normal', 'Completed', 11, '2026-07-30 10:29:25', '2026-07-30 10:29:25'),
(9, 36, '80', '70–99', 'mg/dL', 'normal', 'Completed', 11, '2026-07-30 13:00:32', '2026-07-30 13:00:32');

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `login_id` int(11) NOT NULL,
  `register_id` int(11) NOT NULL,
  `hospital_id` int(11) DEFAULT NULL,
  `login_time` datetime DEFAULT current_timestamp(),
  `logout_time` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `browser` varchar(255) DEFAULT NULL,
  `device` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`login_id`, `register_id`, `hospital_id`, `login_time`, `logout_time`, `ip_address`, `browser`, `device`) VALUES
(1, 1006, NULL, '2026-07-30 12:42:00', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(2, 1040, 5, '2026-07-30 12:42:03', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(3, 1008, 5, '2026-07-30 12:42:10', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(4, 1040, 5, '2026-07-30 12:42:46', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(5, 1006, NULL, '2026-07-30 12:42:49', '2026-07-30 13:32:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(6, 1008, 5, '2026-07-30 12:44:55', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(7, 1006, NULL, '2026-07-30 12:45:23', '2026-07-30 12:47:11', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(8, 1028, 5, '2026-07-30 12:46:15', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(9, 1006, NULL, '2026-07-30 12:47:12', '2026-07-30 12:47:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(10, 1008, 5, '2026-07-30 12:47:33', '2026-07-30 12:47:39', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(11, 1008, 5, '2026-07-30 12:51:03', '2026-07-30 12:52:14', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(12, 1008, 5, '2026-07-30 12:52:23', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(16, 1023, 5, '2026-07-30 12:58:31', '2026-07-30 13:05:34', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(17, 1008, 5, '2026-07-30 13:01:56', '2026-07-30 13:02:07', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(18, 1028, 5, '2026-07-30 13:02:14', '2026-07-30 13:02:25', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(19, 1008, 5, '2026-07-30 13:02:34', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(20, 1061, 5, '2026-07-30 13:03:41', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(21, 1061, 5, '2026-07-30 13:03:58', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(22, 1028, 5, '2026-07-30 13:05:41', '2026-07-30 13:05:56', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(23, 1023, 5, '2026-07-30 13:06:04', '2026-07-30 13:07:08', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(24, 1023, 5, '2026-07-30 13:07:23', '2026-07-30 13:15:16', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(25, 1008, 5, '2026-07-30 13:09:51', '2026-07-30 13:10:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(27, 1008, 5, '2026-07-30 13:11:19', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(28, 1008, 5, '2026-07-30 13:11:29', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(29, 1008, 5, '2026-07-30 13:11:39', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(30, 1008, 5, '2026-07-30 13:11:51', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(31, 1008, 5, '2026-07-30 13:12:18', '2026-07-30 13:13:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(35, 1008, 5, '2026-07-30 13:14:27', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(36, 1008, 5, '2026-07-30 13:14:45', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(37, 1008, 5, '2026-07-30 13:14:54', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(38, 1023, 5, '2026-07-30 13:15:24', '2026-07-30 14:06:58', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(39, 1008, 5, '2026-07-30 13:24:59', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(40, 1023, 5, '2026-07-30 14:07:33', '2026-07-30 15:09:06', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(41, 1040, 5, '2026-07-30 15:02:16', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(42, 1028, 5, '2026-07-30 15:06:17', '2026-07-30 15:07:13', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(43, 1040, 5, '2026-07-30 15:07:25', '2026-07-30 15:08:28', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(44, 1008, 5, '2026-07-30 15:08:43', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(45, 1040, 5, '2026-07-30 15:09:13', '2026-07-30 15:12:19', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(46, 1028, 5, '2026-07-30 15:12:31', '2026-07-30 15:13:15', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(47, 1023, 5, '2026-07-30 15:13:21', '2026-07-30 17:51:25', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(48, 1008, 5, '2026-07-30 15:21:04', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(49, 1028, 5, '2026-07-30 15:32:21', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(50, 1006, NULL, '2026-07-30 16:01:17', '2026-07-30 16:01:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(51, 1006, NULL, '2026-07-30 16:01:35', '2026-07-30 16:19:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(52, 1058, 5, '2026-07-30 16:08:34', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(53, 1058, 5, '2026-07-30 16:10:35', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(54, 1058, 5, '2026-07-30 16:17:39', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(55, 1058, 5, '2026-07-30 16:18:20', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(56, 1058, 5, '2026-07-30 16:18:36', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(57, 1009, 6, '2026-07-30 16:19:17', '2026-07-30 16:20:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(58, 1006, NULL, '2026-07-30 16:20:21', '2026-07-30 17:43:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(59, 1008, 5, '2026-07-30 16:20:40', '2026-07-30 16:53:15', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(63, 1028, 5, '2026-07-30 16:35:48', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(64, 1008, 5, '2026-07-30 16:53:21', '2026-07-30 16:53:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(65, 1028, 5, '2026-07-30 16:53:33', '2026-07-30 16:56:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(67, 1008, 5, '2026-07-30 16:57:48', '2026-07-30 17:00:52', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(68, 1028, 5, '2026-07-30 17:01:01', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(70, 1028, 5, '2026-07-30 17:44:06', '2026-07-30 17:50:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(71, 1008, 5, '2026-07-30 17:50:53', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(72, 1008, 5, '2026-07-30 17:51:36', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(73, 1023, 5, '2026-07-30 17:51:51', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(74, 1008, 5, '2026-07-30 17:55:27', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(75, 1023, 5, '2026-07-30 17:56:39', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(76, 1006, NULL, '2026-07-30 18:14:11', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(77, 1008, 5, '2026-07-30 18:14:23', '2026-07-30 18:14:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(78, 1028, 5, '2026-07-30 18:14:34', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(79, 1006, NULL, '2026-07-30 21:43:56', '2026-07-30 21:48:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(80, 1006, NULL, '2026-07-30 21:48:03', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(81, 1009, 6, '2026-07-30 21:48:44', '2026-07-30 21:49:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(82, 1009, 6, '2026-07-30 21:55:49', '2026-07-30 21:56:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(83, 1009, 6, '2026-07-30 22:10:51', '2026-07-30 22:12:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(86, 1009, 6, '2026-07-30 22:28:01', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(87, 1009, 6, '2026-07-30 22:28:08', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(88, 1009, 6, '2026-07-30 22:53:10', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(89, 1008, 5, '2026-07-30 22:53:58', '2026-07-30 22:54:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(90, 1068, 5, '2026-07-30 22:54:59', '2026-07-30 22:55:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(91, 1008, 5, '2026-07-30 22:55:39', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(92, 1071, 5, '2026-07-30 23:20:25', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(93, 1040, 5, '2026-07-31 11:26:37', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(94, 1006, NULL, '2026-07-31 11:34:00', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(95, 1009, 6, '2026-07-31 11:36:00', '2026-07-31 11:37:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(96, 1028, 5, '2026-07-31 11:36:13', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(97, 1008, 5, '2026-07-31 11:38:09', '2026-07-31 11:39:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(98, 1009, 6, '2026-07-31 11:39:56', '2026-07-31 11:53:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(99, 1008, 5, '2026-07-31 11:53:47', '2026-07-31 15:01:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(100, 1028, 5, '2026-07-31 14:51:50', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(101, 1058, 5, '2026-07-31 15:02:16', '2026-07-31 15:15:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(102, 1040, 5, '2026-07-31 15:15:53', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(103, 1040, 5, '2026-07-31 15:27:56', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop');

-- --------------------------------------------------------

--
-- Table structure for table `opd`
--

CREATE TABLE `opd` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_no` varchar(100) NOT NULL,
  `department` varchar(200) DEFAULT NULL,
  `appointment_type` varchar(200) DEFAULT NULL,
  `appointment_date` date DEFAULT NULL,
  `appointment_time` time DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `since_when` varchar(100) DEFAULT NULL,
  `severity` enum('Mild','Moderate','Severe') DEFAULT NULL,
  `previous_history` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `current_medicines` text DEFAULT NULL,
  `prescription_file` varchar(255) DEFAULT NULL,
  `lab_report_file` varchar(255) DEFAULT NULL,
  `xray_file` varchar(255) DEFAULT NULL,
  `mri_file` varchar(255) DEFAULT NULL,
  `ctscan_file` varchar(255) DEFAULT NULL,
  `other_document` varchar(255) DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `bp` varchar(30) DEFAULT NULL,
  `pulse` varchar(30) DEFAULT NULL,
  `weight` varchar(30) DEFAULT NULL,
  `temperature` varchar(30) DEFAULT NULL,
  `doctor_note` text DEFAULT NULL,
  `visit_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delete_flag` tinyint(1) DEFAULT 0,
  `hospital_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `register_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `patient_name` varchar(150) NOT NULL,
  `patient_image` varchar(255) DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `age` int(11) DEFAULT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `address` text DEFAULT NULL,
  `emergency_contact` varchar(15) DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `allergy` text DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `email` varchar(150) DEFAULT NULL,
  `mobile` varchar(15) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delete_flag` int(11) DEFAULT NULL,
  `hospital_id` int(11) DEFAULT NULL,
  `patient_admission_type` enum('Call','OPD','IPD','Referral') NOT NULL DEFAULT 'OPD',
  `call_source` varchar(100) DEFAULT NULL,
  `previous_hospital` varchar(255) DEFAULT NULL,
  `referred_doctor` varchar(255) DEFAULT NULL,
  `referred_hospital` varchar(255) DEFAULT NULL,
  `referral_reason` text DEFAULT NULL,
  `admission_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `register_id`, `doctor_id`, `patient_name`, `patient_image`, `date_of_birth`, `age`, `blood_group`, `gender`, `address`, `emergency_contact`, `medical_history`, `allergy`, `status`, `email`, `mobile`, `created_at`, `modified_at`, `delete_flag`, `hospital_id`, `patient_admission_type`, `call_source`, `previous_hospital`, `referred_doctor`, `referred_hospital`, `referral_reason`, `admission_reason`) VALUES
(6, 1023, 8, 'Niraj Bhutes', 'documents/patients/images/bg.jpg', '2026-07-10', 23, 'A+', 'Male', 'Jakatwadi, Satara', '7523452345', 'Cough, Fever', 'Smoke, Running', 'Active', 'nirajbhute3@gmail.com', '911234567890', '2026-07-21 06:00:52', '2026-07-30 07:35:28', 0, 5, 'Call', NULL, NULL, NULL, NULL, NULL, NULL),
(28, 1045, 8, 'Pratik Kadam', 'documents/patients/images/1784726143_ayushphoto.jpg', '0000-00-00', 23, '', 'Male', 'asdfghjk', '', 'asdfg,awerfh', 'qwert,qwerty', 'Active', 'pratiksitsolutions@gmail.com', '12345656', '2026-07-22 05:32:20', '2026-07-31 06:10:28', 0, 5, 'OPD', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `patient_alerts`
--

CREATE TABLE `patient_alerts` (
  `alert_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `alert_type` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `severity` enum('Low','Medium','High','Critical') DEFAULT 'Medium',
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_by` varchar(300) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delete_flag` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_alerts`
--

INSERT INTO `patient_alerts` (`alert_id`, `patient_id`, `hospital_id`, `alert_type`, `description`, `severity`, `status`, `created_by`, `created_at`, `modified_at`, `delete_flag`) VALUES
(2, 6, 5, 'Medication', 'Blood Thinner Active', 'High', 'Active', '1', '2026-07-22 05:17:08', '2026-07-22 05:19:42', 0),
(3, 6, 5, 'Condition', 'Diabetic', 'Medium', 'Active', '1', '2026-07-22 05:17:08', '2026-07-22 05:19:47', 0),
(16, 28, 5, 'Allergy', 'qwert,qwerty', 'Medium', 'Active', 'Admin', '2026-07-22 13:15:43', '2026-07-22 13:15:43', 0),
(17, 28, 5, 'Medical History', 'asdfg,awerfh', 'Medium', 'Active', 'Admin', '2026-07-22 13:15:43', '2026-07-22 13:15:43', 0),
(20, 6, 5, 'Allergy', 'Smoke, Running', 'Medium', 'Active', 'Admin', '2026-07-22 13:17:21', '2026-07-22 13:17:21', 0),
(21, 6, 5, 'Medical History', 'Cough, Fever', 'Medium', 'Active', 'Admin', '2026-07-22 13:17:21', '2026-07-22 13:17:21', 0);

-- --------------------------------------------------------

--
-- Table structure for table `patient_documents`
--

CREATE TABLE `patient_documents` (
  `document_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `document_name` varchar(150) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `document_category` varchar(50) DEFAULT 'General',
  `document_sub_category` varchar(100) DEFAULT NULL,
  `upload_file` varchar(255) NOT NULL,
  `file_size` varchar(20) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `document_tags` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `document_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delete_flag` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_documents`
--

INSERT INTO `patient_documents` (`document_id`, `patient_id`, `document_name`, `document_type`, `document_category`, `document_sub_category`, `upload_file`, `file_size`, `uploaded_by`, `note`, `document_tags`, `is_verified`, `verified_by`, `verified_at`, `document_date`, `created_at`, `modified_at`, `delete_flag`) VALUES
(4, 6, 'Internship letter', 'Lab Result', 'Pre-Operation', 'Radiology', 'upload/documents/1785413161_eMaestro_Joining_Letter-RohanKurne-July2026-v21.pdf', '182777', 1023, '', '@340 Leb Reports', 0, NULL, NULL, '2026-07-30', '2026-07-30 12:06:01', '2026-07-30 12:06:01', 0);

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `permission_id` int(11) NOT NULL,
  `permission_group` varchar(100) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `permission_name` varchar(100) NOT NULL,
  `permission_slug` varchar(100) NOT NULL,
  `permission_icon` varchar(50) DEFAULT NULL,
  `menu_order` int(11) DEFAULT 0,
  `is_sidebar` tinyint(1) DEFAULT 1,
  `is_dashboard` tinyint(1) DEFAULT 1,
  `description` text DEFAULT NULL,
  `is_system` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delete_flag` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`permission_id`, `permission_group`, `parent_id`, `permission_name`, `permission_slug`, `permission_icon`, `menu_order`, `is_sidebar`, `is_dashboard`, `description`, `is_system`, `sort_order`, `created_at`, `modified_at`, `delete_flag`) VALUES
(1, 'Dashboard', NULL, 'Dashboard View', 'dashboard-view', 'fa-chart-pie', 1, 1, 1, NULL, 0, 1, '2026-07-16 04:14:11', '2026-07-18 08:50:38', 0),
(2, 'Dashboard', NULL, 'Dashboard Analytics', 'dashboard-analytics', 'fa-chart-pie', 1, 1, 1, NULL, 0, 2, '2026-07-16 04:14:11', '2026-07-18 08:50:38', 0),
(5, 'Hospital Management', NULL, 'Hospital View', 'hospital-view', 'fa-hospital', 0, 1, 1, NULL, 0, 10, '2026-07-16 04:15:12', '2026-07-16 04:15:12', 0),
(6, 'Hospital Management', NULL, 'Hospital Create', 'hospital-create', 'fa-plus-circle', 0, 1, 1, NULL, 0, 11, '2026-07-16 04:15:12', '2026-07-16 04:15:12', 0),
(7, 'Hospital Management', NULL, 'Hospital Edit', 'hospital-edit', 'fa-edit', 0, 1, 1, NULL, 0, 12, '2026-07-16 04:15:12', '2026-07-16 04:15:12', 0),
(8, 'Hospital Management', NULL, 'Hospital Delete', 'hospital-delete', 'fa-trash', 0, 1, 1, NULL, 0, 13, '2026-07-16 04:15:12', '2026-07-16 04:15:12', 0),
(9, 'Hospital Management', NULL, 'Hospital Settings', 'hospital-settings', 'fa-cog', 0, 1, 1, NULL, 0, 14, '2026-07-16 04:15:12', '2026-07-16 04:15:12', 0),
(10, 'Masters', NULL, 'Department View', 'department-view', 'fa-building', 0, 1, 1, NULL, 0, 20, '2026-07-16 04:15:23', '2026-07-16 04:15:23', 0),
(11, 'Masters', NULL, 'Department Create', 'department-create', 'fa-plus', 0, 1, 1, NULL, 0, 21, '2026-07-16 04:15:23', '2026-07-16 04:15:23', 0),
(12, 'Masters', NULL, 'Department Edit', 'department-edit', 'fa-pen', 0, 1, 1, NULL, 0, 22, '2026-07-16 04:15:23', '2026-07-16 04:15:23', 0),
(13, 'Masters', NULL, 'Department Delete', 'department-delete', 'fa-trash-alt', 0, 1, 1, NULL, 0, 23, '2026-07-16 04:15:23', '2026-07-16 04:15:23', 0),
(14, 'Masters', NULL, 'Doctor View', 'doctor-view', 'fa-user-md', 0, 1, 1, NULL, 0, 25, '2026-07-16 04:15:37', '2026-07-16 04:15:37', 0),
(15, 'Masters', NULL, 'Doctor Create', 'doctor-create', 'fa-user-plus', 0, 1, 1, NULL, 0, 26, '2026-07-16 04:15:37', '2026-07-16 04:15:37', 0),
(16, 'Masters', NULL, 'Doctor Edit', 'doctor-edit', 'fa-user-edit', 0, 1, 1, NULL, 0, 27, '2026-07-16 04:15:37', '2026-07-16 04:15:37', 0),
(17, 'Masters', NULL, 'Doctor Delete', 'doctor-delete', 'fa-user-times', 0, 1, 1, NULL, 0, 28, '2026-07-16 04:15:37', '2026-07-16 04:15:37', 0),
(18, 'Masters', NULL, 'Staff View', 'staff-view', 'fa-users', 0, 1, 1, NULL, 0, 30, '2026-07-16 04:15:46', '2026-07-16 04:15:46', 0),
(19, 'Masters', NULL, 'Staff Create', 'staff-create', 'fa-user-plus', 0, 1, 1, NULL, 0, 31, '2026-07-16 04:15:46', '2026-07-16 04:15:46', 0),
(20, 'Masters', NULL, 'Staff Edit', 'staff-edit', 'fa-user-edit', 0, 1, 1, NULL, 0, 32, '2026-07-16 04:15:46', '2026-07-16 04:15:46', 0),
(21, 'Masters', NULL, 'Staff Delete', 'staff-delete', 'fa-user-times', 0, 1, 1, NULL, 0, 33, '2026-07-16 04:15:46', '2026-07-16 04:15:46', 0),
(22, 'Masters', NULL, 'Ward View', 'ward-view', 'fa-bed', 0, 1, 1, NULL, 0, 35, '2026-07-16 04:15:55', '2026-07-16 04:15:55', 0),
(23, 'Masters', NULL, 'Ward Create', 'ward-create', 'fa-plus', 0, 1, 1, NULL, 0, 36, '2026-07-16 04:15:55', '2026-07-16 04:15:55', 0),
(24, 'Masters', NULL, 'Ward Edit', 'ward-edit', 'fa-edit', 0, 1, 1, NULL, 0, 37, '2026-07-16 04:15:55', '2026-07-16 04:15:55', 0),
(25, 'Masters', NULL, 'Ward Delete', 'ward-delete', 'fa-trash', 0, 1, 1, NULL, 0, 38, '2026-07-16 04:15:55', '2026-07-16 04:15:55', 0),
(26, 'Masters', NULL, 'Room View', 'room-view', 'fa-door-open', 0, 1, 1, NULL, 0, 40, '2026-07-16 04:16:04', '2026-07-16 04:16:04', 0),
(27, 'Masters', NULL, 'Room Create', 'room-create', 'fa-plus', 0, 1, 1, NULL, 0, 41, '2026-07-16 04:16:04', '2026-07-16 04:16:04', 0),
(28, 'Masters', NULL, 'Room Edit', 'room-edit', 'fa-edit', 0, 1, 1, NULL, 0, 42, '2026-07-16 04:16:04', '2026-07-16 04:16:04', 0),
(29, 'Masters', NULL, 'Room Delete', 'room-delete', 'fa-trash', 0, 1, 1, NULL, 0, 43, '2026-07-16 04:16:04', '2026-07-16 04:16:04', 0),
(30, 'Masters', NULL, 'Bed View', 'bed-view', 'fa-bed', 0, 1, 1, NULL, 0, 45, '2026-07-16 04:16:14', '2026-07-16 04:16:14', 0),
(31, 'Masters', NULL, 'Bed Create', 'bed-create', 'fa-plus', 0, 1, 1, NULL, 0, 46, '2026-07-16 04:16:14', '2026-07-16 04:16:14', 0),
(32, 'Masters', NULL, 'Bed Edit', 'bed-edit', 'fa-edit', 0, 1, 1, NULL, 0, 47, '2026-07-16 04:16:14', '2026-07-16 04:16:14', 0),
(33, 'Masters', NULL, 'Bed Delete', 'bed-delete', 'fa-trash', 0, 1, 1, NULL, 0, 48, '2026-07-16 04:16:14', '2026-07-16 04:16:14', 0),
(34, 'Laboratory', NULL, 'Lab Test View', 'lab-test-view', 'fa-flask', 60, 1, 1, NULL, 0, 50, '2026-07-16 04:16:24', '2026-07-18 08:50:38', 0),
(35, 'Laboratory', NULL, 'Lab Test Create', 'lab-test-create', 'fa-flask', 60, 1, 1, NULL, 0, 51, '2026-07-16 04:16:24', '2026-07-18 08:50:38', 0),
(36, 'Laboratory', NULL, 'Lab Test Edit', 'lab-test-edit', 'fa-flask', 60, 1, 1, NULL, 0, 52, '2026-07-16 04:16:24', '2026-07-18 08:50:38', 0),
(37, 'Laboratory', NULL, 'Lab Test Delete', 'lab-test-delete', 'fa-flask', 60, 1, 1, NULL, 0, 53, '2026-07-16 04:16:24', '2026-07-18 08:50:38', 0),
(38, 'Masters', NULL, 'Medicine View', 'medicine-view', 'fa-pills', 0, 1, 1, NULL, 0, 55, '2026-07-16 04:16:33', '2026-07-16 04:16:33', 0),
(39, 'Masters', NULL, 'Medicine Create', 'medicine-create', 'fa-plus', 0, 1, 1, NULL, 0, 56, '2026-07-16 04:16:33', '2026-07-16 04:16:33', 0),
(40, 'Masters', NULL, 'Medicine Edit', 'medicine-edit', 'fa-edit', 0, 1, 1, NULL, 0, 57, '2026-07-16 04:16:33', '2026-07-16 04:16:33', 0),
(41, 'Masters', NULL, 'Medicine Delete', 'medicine-delete', 'fa-trash', 0, 1, 1, NULL, 0, 58, '2026-07-16 04:16:33', '2026-07-16 04:16:33', 0),
(42, 'Patients', NULL, 'Patient View', 'patient-view', 'fa-users', 10, 1, 1, NULL, 0, 60, '2026-07-16 04:16:41', '2026-07-18 08:50:38', 0),
(43, 'Patients', NULL, 'Patient Create', 'patient-create', 'fa-users', 10, 1, 1, NULL, 0, 61, '2026-07-16 04:16:41', '2026-07-18 08:50:38', 0),
(44, 'Patients', NULL, 'Patient Edit', 'patient-edit', 'fa-users', 10, 1, 1, NULL, 0, 62, '2026-07-16 04:16:41', '2026-07-18 08:50:38', 0),
(45, 'Patients', NULL, 'Patient Delete', 'patient-delete', 'fa-users', 10, 1, 1, NULL, 0, 63, '2026-07-16 04:16:41', '2026-07-18 08:50:38', 0),
(46, 'Patients', NULL, 'Patient History', 'patient-history', 'fa-users', 10, 1, 1, NULL, 0, 64, '2026-07-16 04:16:41', '2026-07-18 08:50:38', 0),
(47, 'Appointments', NULL, 'Appointment View', 'appointment-view', 'fa-calendar-check', 20, 1, 1, NULL, 0, 70, '2026-07-16 04:16:49', '2026-07-18 08:50:38', 0),
(48, 'Appointments', NULL, 'Appointment Create', 'appointment-create', 'fa-calendar-check', 20, 1, 1, NULL, 0, 71, '2026-07-16 04:16:49', '2026-07-18 08:50:38', 0),
(49, 'Appointments', NULL, 'Appointment Edit', 'appointment-edit', 'fa-calendar-check', 20, 1, 1, NULL, 0, 72, '2026-07-16 04:16:49', '2026-07-18 08:50:38', 0),
(50, 'Appointments', NULL, 'Appointment Delete', 'appointment-delete', 'fa-calendar-check', 20, 1, 1, NULL, 0, 73, '2026-07-16 04:16:49', '2026-07-18 08:50:38', 0),
(51, 'OPD', NULL, 'OPD Visit View', 'opd-visit-view', 'fa-stethoscope', 30, 1, 1, NULL, 0, 75, '2026-07-16 04:16:56', '2026-07-18 08:50:38', 0),
(52, 'OPD', NULL, 'OPD Visit Create', 'opd-visit-create', 'fa-stethoscope', 30, 1, 1, NULL, 0, 76, '2026-07-16 04:16:56', '2026-07-18 08:50:38', 0),
(53, 'OPD', NULL, 'OPD Visit Edit', 'opd-visit-edit', 'fa-stethoscope', 30, 1, 1, NULL, 0, 77, '2026-07-16 04:16:56', '2026-07-18 08:50:38', 0),
(54, 'OPD', NULL, 'OPD Visit Delete', 'opd-visit-delete', 'fa-stethoscope', 30, 1, 1, NULL, 0, 78, '2026-07-16 04:16:56', '2026-07-18 08:50:38', 0),
(55, 'Prescriptions', NULL, 'Prescription View', 'prescription-view', 'fa-prescription', 50, 1, 1, NULL, 0, 80, '2026-07-16 04:16:56', '2026-07-18 08:50:38', 0),
(56, 'Prescriptions', NULL, 'Prescription Create', 'prescription-create', 'fa-prescription', 50, 1, 1, NULL, 0, 81, '2026-07-16 04:16:56', '2026-07-18 08:50:38', 0),
(57, 'Prescriptions', NULL, 'Prescription Edit', 'prescription-edit', 'fa-prescription', 50, 1, 1, NULL, 0, 82, '2026-07-16 04:16:56', '2026-07-18 08:50:38', 0),
(58, 'Prescriptions', NULL, 'Prescription Delete', 'prescription-delete', 'fa-prescription', 50, 1, 1, NULL, 0, 83, '2026-07-16 04:16:56', '2026-07-18 08:50:38', 0),
(59, 'IPD', NULL, 'IPD Admission View', 'ipd-admission-view', 'fa-hospital-user', 40, 1, 1, NULL, 0, 85, '2026-07-16 04:17:05', '2026-07-18 08:50:38', 0),
(60, 'IPD', NULL, 'IPD Admission Create', 'ipd-admission-create', 'fa-hospital-user', 40, 1, 1, NULL, 0, 86, '2026-07-16 04:17:05', '2026-07-18 08:50:38', 0),
(61, 'IPD', NULL, 'IPD Admission Edit', 'ipd-admission-edit', 'fa-hospital-user', 40, 1, 1, NULL, 0, 87, '2026-07-16 04:17:05', '2026-07-18 08:50:38', 0),
(62, 'IPD', NULL, 'IPD Admission Delete', 'ipd-admission-delete', 'fa-hospital-user', 40, 1, 1, NULL, 0, 88, '2026-07-16 04:17:05', '2026-07-18 08:50:38', 0),
(63, 'IPD', NULL, 'IPD Treatment View', 'ipd-treatment-view', 'fa-hospital-user', 40, 1, 1, NULL, 0, 90, '2026-07-16 04:17:14', '2026-07-18 08:50:38', 0),
(64, 'IPD', NULL, 'IPD Treatment Create', 'ipd-treatment-create', 'fa-hospital-user', 40, 1, 1, NULL, 0, 91, '2026-07-16 04:17:14', '2026-07-18 08:50:38', 0),
(65, 'IPD', NULL, 'IPD Treatment Edit', 'ipd-treatment-edit', 'fa-hospital-user', 40, 1, 1, NULL, 0, 92, '2026-07-16 04:17:14', '2026-07-18 08:50:38', 0),
(66, 'IPD', NULL, 'IPD Treatment Delete', 'ipd-treatment-delete', 'fa-hospital-user', 40, 1, 1, NULL, 0, 93, '2026-07-16 04:17:14', '2026-07-18 08:50:38', 0),
(67, 'IPD', NULL, 'Discharge Summary View', 'discharge-summary-view', 'fa-file-medical', 0, 1, 1, NULL, 0, 95, '2026-07-16 04:17:14', '2026-07-16 04:17:14', 0),
(68, 'IPD', NULL, 'Discharge Summary Create', 'discharge-summary-create', 'fa-plus', 0, 1, 1, NULL, 0, 96, '2026-07-16 04:17:14', '2026-07-16 04:17:14', 0),
(69, 'IPD', NULL, 'Discharge Summary Edit', 'discharge-summary-edit', 'fa-edit', 0, 1, 1, NULL, 0, 97, '2026-07-16 04:17:14', '2026-07-16 04:17:14', 0),
(70, 'IPD', NULL, 'Discharge Summary Delete', 'discharge-summary-delete', 'fa-trash', 0, 1, 1, NULL, 0, 98, '2026-07-16 04:17:14', '2026-07-16 04:17:14', 0),
(71, 'Laboratory', NULL, 'Lab Orders View', 'lab-orders-view', 'fa-flask', 60, 1, 1, NULL, 0, 100, '2026-07-16 04:17:22', '2026-07-18 08:50:38', 0),
(72, 'Laboratory', NULL, 'Lab Orders Create', 'lab-orders-create', 'fa-flask', 60, 1, 1, NULL, 0, 101, '2026-07-16 04:17:22', '2026-07-18 08:50:38', 0),
(73, 'Laboratory', NULL, 'Lab Orders Edit', 'lab-orders-edit', 'fa-flask', 60, 1, 1, NULL, 0, 102, '2026-07-16 04:17:22', '2026-07-18 08:50:38', 0),
(74, 'Laboratory', NULL, 'Lab Orders Delete', 'lab-orders-delete', 'fa-flask', 60, 1, 1, NULL, 0, 103, '2026-07-16 04:17:22', '2026-07-18 08:50:38', 0),
(75, 'Laboratory', NULL, 'Lab Reports View', 'lab-reports-view', 'fa-flask', 60, 1, 1, NULL, 0, 105, '2026-07-16 04:17:22', '2026-07-18 08:50:38', 0),
(76, 'Laboratory', NULL, 'Lab Reports Create', 'lab-reports-create', 'fa-flask', 60, 1, 1, NULL, 0, 106, '2026-07-16 04:17:22', '2026-07-18 08:50:38', 0),
(77, 'Laboratory', NULL, 'Lab Reports Edit', 'lab-reports-edit', 'fa-flask', 60, 1, 1, NULL, 0, 107, '2026-07-16 04:17:22', '2026-07-18 08:50:38', 0),
(78, 'Laboratory', NULL, 'Lab Reports Delete', 'lab-reports-delete', 'fa-flask', 60, 1, 1, NULL, 0, 108, '2026-07-16 04:17:22', '2026-07-18 08:50:38', 0),
(79, 'Pharmacy', NULL, 'Medicine Sales View', 'medicine-sales-view', 'fa-cash-register', 0, 1, 1, NULL, 0, 110, '2026-07-16 04:17:30', '2026-07-16 04:17:30', 0),
(80, 'Pharmacy', NULL, 'Medicine Sales Create', 'medicine-sales-create', 'fa-plus', 0, 1, 1, NULL, 0, 111, '2026-07-16 04:17:30', '2026-07-16 04:17:30', 0),
(81, 'Pharmacy', NULL, 'Medicine Sales Edit', 'medicine-sales-edit', 'fa-edit', 0, 1, 1, NULL, 0, 112, '2026-07-16 04:17:30', '2026-07-16 04:17:30', 0),
(82, 'Pharmacy', NULL, 'Medicine Sales Delete', 'medicine-sales-delete', 'fa-trash', 0, 1, 1, NULL, 0, 113, '2026-07-16 04:17:30', '2026-07-16 04:17:30', 0),
(83, 'Pharmacy', NULL, 'Stock View', 'stock-view', 'fa-boxes', 0, 1, 1, NULL, 0, 115, '2026-07-16 04:17:30', '2026-07-16 04:17:30', 0),
(84, 'Pharmacy', NULL, 'Stock Create', 'stock-create', 'fa-plus', 0, 1, 1, NULL, 0, 116, '2026-07-16 04:17:30', '2026-07-16 04:17:30', 0),
(85, 'Pharmacy', NULL, 'Stock Edit', 'stock-edit', 'fa-edit', 0, 1, 1, NULL, 0, 117, '2026-07-16 04:17:30', '2026-07-16 04:17:30', 0),
(86, 'Pharmacy', NULL, 'Stock Delete', 'stock-delete', 'fa-trash', 0, 1, 1, NULL, 0, 118, '2026-07-16 04:17:30', '2026-07-16 04:17:30', 0),
(87, 'OPD', NULL, 'OPD Billing View', 'opd-billing-view', 'fa-stethoscope', 30, 1, 1, NULL, 0, 120, '2026-07-16 04:17:37', '2026-07-18 08:50:38', 0),
(88, 'OPD', NULL, 'OPD Billing Create', 'opd-billing-create', 'fa-stethoscope', 30, 1, 1, NULL, 0, 121, '2026-07-16 04:17:37', '2026-07-18 08:50:38', 0),
(89, 'OPD', NULL, 'OPD Billing Edit', 'opd-billing-edit', 'fa-stethoscope', 30, 1, 1, NULL, 0, 122, '2026-07-16 04:17:37', '2026-07-18 08:50:38', 0),
(90, 'OPD', NULL, 'OPD Billing Delete', 'opd-billing-delete', 'fa-stethoscope', 30, 1, 1, NULL, 0, 123, '2026-07-16 04:17:37', '2026-07-18 08:50:38', 0),
(91, 'IPD', NULL, 'IPD Billing View', 'ipd-billing-view', 'fa-hospital-user', 40, 1, 1, NULL, 0, 125, '2026-07-16 04:17:37', '2026-07-18 08:50:38', 0),
(92, 'IPD', NULL, 'IPD Billing Create', 'ipd-billing-create', 'fa-hospital-user', 40, 1, 1, NULL, 0, 126, '2026-07-16 04:17:37', '2026-07-18 08:50:38', 0),
(93, 'IPD', NULL, 'IPD Billing Edit', 'ipd-billing-edit', 'fa-hospital-user', 40, 1, 1, NULL, 0, 127, '2026-07-16 04:17:37', '2026-07-18 08:50:38', 0),
(94, 'IPD', NULL, 'IPD Billing Delete', 'ipd-billing-delete', 'fa-hospital-user', 40, 1, 1, NULL, 0, 128, '2026-07-16 04:17:37', '2026-07-18 08:50:38', 0),
(95, 'Billing', NULL, 'Payments View', 'payments-view', 'fa-file-invoice-dollar', 80, 1, 1, NULL, 0, 130, '2026-07-16 04:17:37', '2026-07-18 08:50:38', 0),
(96, 'Billing', NULL, 'Payments Create', 'payments-create', 'fa-file-invoice-dollar', 80, 1, 1, NULL, 0, 131, '2026-07-16 04:17:37', '2026-07-18 08:50:38', 0),
(97, 'Billing', NULL, 'Payments Edit', 'payments-edit', 'fa-file-invoice-dollar', 80, 1, 1, NULL, 0, 132, '2026-07-16 04:17:37', '2026-07-18 08:50:38', 0),
(98, 'Billing', NULL, 'Payments Delete', 'payments-delete', 'fa-file-invoice-dollar', 80, 1, 1, NULL, 0, 133, '2026-07-16 04:17:37', '2026-07-18 08:50:38', 0),
(99, 'Reports', NULL, 'Reports View', 'reports-view', 'fa-chart-bar', 90, 1, 1, NULL, 0, 135, '2026-07-16 04:17:49', '2026-07-18 08:50:38', 0),
(201, 'Patients', NULL, 'Referral View', 'referral-view', 'fa-circle', 0, 1, 1, '', 0, 0, '2026-07-21 11:18:04', '2026-07-21 11:18:04', 0),
(202, 'Patients', NULL, 'Call Patient View', 'call-patient-view', 'fa-phone', 0, 1, 1, '', 0, 0, '2026-07-21 11:19:58', '2026-07-21 11:19:58', 0),
(203, 'Laboratory', NULL, 'Lab Master View', 'lab-master-view', 'fa-prescription', 0, 1, 1, '', 0, 0, '2026-07-22 09:25:33', '2026-07-22 09:25:33', 0),
(204, 'Operation Theatre', NULL, 'Surgery View', 'surgery-view', 'fa-exclamation-circle', 0, 1, 1, '', 0, 0, '2026-07-22 11:22:15', '2026-07-22 11:22:15', 0),
(205, 'Operation Theatre', NULL, 'Surgery Create', 'surgery-create', 'fa-circle', 0, 1, 1, '', 0, 0, '2026-07-22 11:22:39', '2026-07-22 11:22:39', 0),
(206, 'Operation Theatre', NULL, 'Surgery Edit', 'surgery-edit', 'fa-user-times', 0, 1, 1, '', 0, 0, '2026-07-22 11:23:06', '2026-07-22 11:23:06', 0),
(207, 'Operation Theatre', NULL, 'Surgery Delete', 'surgery-delete', 'fa-trash', 0, 1, 1, '', 0, 0, '2026-07-22 11:23:51', '2026-07-22 11:23:51', 0);

-- --------------------------------------------------------

--
-- Table structure for table `prescription_details`
--

CREATE TABLE `prescription_details` (
  `detail_id` int(11) NOT NULL,
  `prescription_id` int(11) NOT NULL,
  `medicine_name` varchar(255) DEFAULT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `frequency` varchar(100) DEFAULT NULL,
  `days` int(11) DEFAULT NULL,
  `timing` varchar(100) DEFAULT NULL,
  `advice` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescription_details`
--

INSERT INTO `prescription_details` (`detail_id`, `prescription_id`, `medicine_name`, `dosage`, `frequency`, `days`, `timing`, `advice`, `created_at`) VALUES
(1, 1, 'Paracetomol', '300mg', 'Twice a day', 5, 'M-N', '', '2026-07-28 14:13:07'),
(2, 1, 'ibuprofin', '300mg', 'Twice a day', 5, 'M-N', '', '2026-07-28 14:13:07'),
(16, 9, 'Ibuprofin', '300mg', 'Thrice a day', 4, 'M-A-N', 'Take after Meal', '2026-07-28 16:13:59'),
(17, 9, 'Paracetomol', '300mg', 'Twice a day', 4, 'M-N', 'Take before Meal', '2026-07-28 16:13:59'),
(18, 10, 'Paracetomol', '300mg', 'Oncea day', 4, 'Night', 'Take after Meal', '2026-07-28 16:17:42');

-- --------------------------------------------------------

--
-- Table structure for table `prescription_master`
--

CREATE TABLE `prescription_master` (
  `prescription_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `complaint` text DEFAULT NULL,
  `doctor_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `followup_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `modified_at` datetime DEFAULT NULL,
  `delete_flag` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescription_master`
--

INSERT INTO `prescription_master` (`prescription_id`, `patient_id`, `complaint`, `doctor_id`, `hospital_id`, `followup_date`, `created_at`, `modified_at`, `delete_flag`) VALUES
(1, 28, NULL, 8, 5, '2026-07-30', '2026-07-28 14:13:07', NULL, 0),
(9, 6, 'Ajari hys ka? re bhadya', 8, 5, '2026-08-01', '2026-07-28 15:04:37', '2026-07-28 16:13:59', 0),
(10, 6, 'Ajarpan Alay', 8, 5, '2026-08-01', '2026-07-28 16:17:42', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `register`
--

CREATE TABLE `register` (
  `id` int(11) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `name` varchar(800) NOT NULL,
  `email` varchar(200) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_by` varchar(200) NOT NULL,
  `modified_by` varchar(200) NOT NULL,
  `delete_flag` int(2) NOT NULL DEFAULT 0,
  `reg_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('Super Admin','Admin','Doctor','Nurse','Ward Boy','Lab Technician','Patient','Billing Staff','Accountant','Pharmacist','Staff','Receptionist') DEFAULT NULL,
  `hospital_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `register`
--

INSERT INTO `register` (`id`, `role_id`, `name`, `email`, `password`, `created_by`, `modified_by`, `delete_flag`, `reg_date`, `role`, `hospital_id`) VALUES
(1006, 1, 'Super Admin', 'superadmin@gmail.com', '1234', 'System', '1006', 0, '2026-07-29 11:42:50', 'Super Admin', NULL),
(1008, 2, 'Dr. Sanket Pawar', 'rohankurne12@gmail.com', 'Roh@1234', 'Super Admin', '1008', 0, '2026-07-22 11:41:32', 'Admin', 5),
(1009, 2, 'Abhishek mandhare', 'abhimandhare469@gmail.com', '$2y$10$5R0i1xosT40WQWjvxBHf..fnNRUbXtca6PU78UazijBOh8iCFt64y', 'Super Admin', 'Super Admin', 0, '2026-07-20 07:36:19', 'Admin', 6),
(1011, 2, 'Dr. Sam Dapal', 'rahulkumbhar2801@gmail.com', '$2y$10$NiLbbxHv1DY7iLKBaNQpYOo6rGXJzJzkSntQgvWct/3/MWz6n8fLW', 'Super Admin', 'Super Admin', 0, '2026-07-20 11:49:54', 'Admin', 7),
(1012, 8, 'Shiva Tevar', 'shiv@gmail.com', '12345678', 'Admin', 'Admin', 0, '2026-07-20 11:55:05', 'Patient', 6),
(1013, 6, 'Shivatej Katkar', 'shivatejkk@gmail.com', '$2y$10$u/ktNJcSyx9Xnmwo56PCm.o2xjLamOwafc/HTl3I6zRv6KaOofuz.', 'Admin', 'Admin', 0, '2026-07-20 12:17:47', 'Ward Boy', 6),
(1015, 3, 'Chaitanya Patil', 'wchaitanyapatil@gmail.com', '12345678', 'Admin', 'Admin', 0, '2026-07-20 13:08:12', 'Doctor', 5),
(1017, 2, '234rtgvfc', 'awerty@gmail.com', '$2y$10$6mgLTD96/yvZYeLG7vQZ6OsQqD.In1VpXsrdAyZs1opmy6qSBtg6e', 'Super Admin', 'Super Admin', 0, '2026-07-20 14:50:11', 'Admin', 7),
(1022, 8, 'Eshwar Pawar', 'rohankurne16@gmail.com', 'Roh@1234', 'Admin', 'Admin', 0, '2026-07-20 18:59:58', 'Patient', 5),
(1023, 8, 'Niraj Bhutes', 'nirajbhute3@gmail.com', '$2y$10$ZuSEWjWWvbaVVldpvcIxT.FnoFLyc69TUWKhryKEViis8i8JJO5bi', 'Admin', 'Patient', 0, '2026-07-21 06:00:52', 'Patient', 5),
(1028, 3, 'Ayush Nipanes', 'ayushhnipane@gmail.com', 'Roh@1234', 'Admin', 'admin', 0, '2026-07-21 06:45:02', 'Doctor', 5),
(1029, 3, 'Dr Shivatej Katkar', 'shivatejk033@gmail.com', 'Roh@1234', 'Admin', 'admin', 0, '2026-07-21 06:51:12', 'Doctor', 6),
(1040, 7, 'Rahul Namya', 'rahul@gmail.com', '$2y$10$3iQZhKgCloz1mV92jtgRiOwqbNzV.Ayy7kceRVVBTBgjQqYOFj8oi', 'Admin', 'Admin', 0, '2026-07-21 10:08:13', 'Lab Technician', 5),
(1045, 8, 'Pratik Kadam', 'pratiksitsolutions@gmail.com', 'Roh@1234', 'Admin', 'Admin', 0, '2026-07-22 05:32:20', 'Patient', 5),
(1052, 3, 'Mohan Joshi', 'mohan@gmail.com', 'Roh@12345', 'Admin', 'Admin', 0, '2026-07-27 09:33:11', 'Doctor', 6),
(1058, 13, 'abhishek mandhare', 'rohankurn@gmail.com', '$2y$10$2hU0ssCV8jcBCXxqajsOieKv9fYLuByAF2483nTxnPF/3pKrB3IDu', 'Admin', 'Admin', 0, '2026-07-29 10:37:28', 'Receptionist', 5),
(1059, 2, 'Dr.Rahulm Kumbhar', 'asd@gmail.com', '$2y$10$NCweiAqgtIzXfERQHiMhKeDb4ln9VpzlPeoxMJDGCBmyC9P3DJt/e', 'Super Admin', 'Super Admin', 0, '2026-07-30 06:34:06', 'Admin', NULL),
(1061, 3, 'Rohan Kurne', 'rohan@gmail.com', 'Roh@1234', 'Admin', 'Admin', 0, '2026-07-30 07:32:59', 'Doctor', 5),
(1066, 3, 'Vedant Mohite', 'abhimandhare@gmail.com', 'Roh@1234', 'Admin', 'Admin', 0, '2026-07-30 16:41:11', 'Doctor', 6),
(1068, 3, 'Priya Joshi', 'priya@gmail.com', 'Roh@1234', 'Admin', 'Admin', 0, '2026-07-30 17:24:32', 'Doctor', 5),
(1071, 11, 'Shiva Tevar', 'shivvv@gmail.com', '$2y$10$nr5McYQrm.nLRpWpyAt2m.Fqb41/7Oyv28Ipe5/YFYod4t8XzDCzK', 'Admin', 'Admin', 0, '2026-07-30 17:49:36', 'Pharmacist', 5);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `hospital_id` int(11) DEFAULT NULL,
  `role_name` varchar(100) NOT NULL,
  `role_slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_system` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delete_flag` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `hospital_id`, `role_name`, `role_slug`, `description`, `is_system`, `created_by`, `created_at`, `modified_at`, `delete_flag`) VALUES
(1, NULL, 'Super Admin', 'superadmin', 'Super Administrator', 1, 999, '2026-07-17 04:47:04', '2026-07-19 16:42:02', 0),
(2, NULL, 'Admin', 'admin', 'Hospital Administrator', 1, 999, '2026-07-20 05:37:06', '2026-07-20 05:37:06', 0),
(3, NULL, 'Doctor', 'doctor', 'Doctor', 1, 999, '2026-07-17 04:47:04', '2026-07-17 04:47:04', 0),
(4, NULL, 'Nurse', 'nurse', 'Nurse', 1, 999, '2026-07-17 04:47:04', '2026-07-17 04:47:04', 0),
(6, NULL, 'Ward Boy', 'wardboy', 'Ward Boy', 1, 999, '2026-07-17 04:47:04', '2026-07-17 04:47:04', 0),
(7, NULL, 'Lab Technician', 'labtechnician', 'Lab Technician', 1, 999, '2026-07-17 04:47:04', '2026-07-17 04:47:04', 0),
(8, NULL, 'Patient', 'patient', 'Patient', 1, 999, '2026-07-17 04:47:04', '2026-07-17 04:47:04', 0),
(9, NULL, 'Billing Staff', 'billingstaff', 'Billing Staff', 1, 999, '2026-07-17 04:47:04', '2026-07-17 04:47:04', 0),
(10, NULL, 'Accountant', 'accountant', 'Accountant', 1, 999, '2026-07-17 04:47:04', '2026-07-17 04:47:04', 0),
(11, NULL, 'Pharmacist', 'pharmacist', 'Pharmacist', 1, 999, '2026-07-17 04:47:04', '2026-07-17 04:47:04', 0),
(12, NULL, 'Staff', 'staff', 'General Staff', 1, 999, '2026-07-17 04:47:04', '2026-07-18 09:56:39', 0),
(13, NULL, 'Receptionist', 'receptionist', 'Receptionist', 1, 999, '2026-07-17 04:54:39', '2026-07-17 04:54:39', 0),
(17, NULL, 'Driver', 'driver', '', 0, 1006, '2026-07-30 06:49:29', '2026-07-30 06:49:35', 1);

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `hospital_id`, `permission_id`, `created_at`) VALUES
(10954, 7, 5, 1, '2026-07-24 06:50:20'),
(10955, 7, 5, 76, '2026-07-24 06:50:20'),
(10956, 7, 5, 78, '2026-07-24 06:50:20'),
(10957, 7, 5, 77, '2026-07-24 06:50:20'),
(10958, 7, 5, 75, '2026-07-24 06:50:20'),
(11647, 3, 6, 48, '2026-07-27 06:52:10'),
(11648, 3, 6, 50, '2026-07-27 06:52:10'),
(11649, 3, 6, 49, '2026-07-27 06:52:10'),
(11650, 3, 6, 47, '2026-07-27 06:52:10'),
(11651, 3, 6, 96, '2026-07-27 06:52:10'),
(11652, 3, 6, 98, '2026-07-27 06:52:10'),
(11653, 3, 6, 97, '2026-07-27 06:52:10'),
(11654, 3, 6, 95, '2026-07-27 06:52:10'),
(11655, 3, 6, 2, '2026-07-27 06:52:10'),
(11656, 3, 6, 1, '2026-07-27 06:52:10'),
(11657, 3, 6, 68, '2026-07-27 06:52:10'),
(11658, 3, 6, 70, '2026-07-27 06:52:10'),
(11659, 3, 6, 69, '2026-07-27 06:52:10'),
(11660, 3, 6, 67, '2026-07-27 06:52:10'),
(11661, 3, 6, 60, '2026-07-27 06:52:10'),
(11662, 3, 6, 62, '2026-07-27 06:52:10'),
(11663, 3, 6, 61, '2026-07-27 06:52:10'),
(11664, 3, 6, 59, '2026-07-27 06:52:10'),
(11665, 3, 6, 92, '2026-07-27 06:52:10'),
(11666, 3, 6, 94, '2026-07-27 06:52:10'),
(11667, 3, 6, 93, '2026-07-27 06:52:10'),
(11668, 3, 6, 91, '2026-07-27 06:52:10'),
(11669, 3, 6, 64, '2026-07-27 06:52:10'),
(11670, 3, 6, 66, '2026-07-27 06:52:10'),
(11671, 3, 6, 65, '2026-07-27 06:52:10'),
(11672, 3, 6, 63, '2026-07-27 06:52:10'),
(11673, 3, 6, 72, '2026-07-27 06:52:10'),
(11674, 3, 6, 71, '2026-07-27 06:52:10'),
(11675, 3, 6, 75, '2026-07-27 06:52:10'),
(11676, 3, 6, 34, '2026-07-27 06:52:10'),
(11677, 3, 6, 16, '2026-07-27 06:52:10'),
(11678, 3, 6, 14, '2026-07-27 06:52:10'),
(11679, 3, 6, 88, '2026-07-27 06:52:10'),
(11680, 3, 6, 90, '2026-07-27 06:52:10'),
(11681, 3, 6, 89, '2026-07-27 06:52:10'),
(11682, 3, 6, 87, '2026-07-27 06:52:10'),
(11683, 3, 6, 52, '2026-07-27 06:52:10'),
(11684, 3, 6, 54, '2026-07-27 06:52:10'),
(11685, 3, 6, 53, '2026-07-27 06:52:10'),
(11686, 3, 6, 51, '2026-07-27 06:52:10'),
(11687, 3, 6, 205, '2026-07-27 06:52:10'),
(11688, 3, 6, 207, '2026-07-27 06:52:10'),
(11689, 3, 6, 206, '2026-07-27 06:52:10'),
(11690, 3, 6, 204, '2026-07-27 06:52:10'),
(11691, 3, 6, 202, '2026-07-27 06:52:10'),
(11692, 3, 6, 43, '2026-07-27 06:52:10'),
(11693, 3, 6, 46, '2026-07-27 06:52:10'),
(11694, 3, 6, 42, '2026-07-27 06:52:10'),
(11695, 3, 6, 201, '2026-07-27 06:52:10'),
(11696, 3, 6, 56, '2026-07-27 06:52:10'),
(11697, 3, 6, 58, '2026-07-27 06:52:10'),
(11698, 3, 6, 57, '2026-07-27 06:52:10'),
(11699, 3, 6, 55, '2026-07-27 06:52:10'),
(11700, 3, 6, 99, '2026-07-27 06:52:10'),
(11701, 3, 0, 48, '2026-07-27 08:26:08'),
(11702, 3, 0, 50, '2026-07-27 08:26:08'),
(11703, 3, 0, 49, '2026-07-27 08:26:08'),
(11704, 3, 0, 47, '2026-07-27 08:26:08'),
(11705, 3, 0, 96, '2026-07-27 08:26:08'),
(11706, 3, 0, 98, '2026-07-27 08:26:08'),
(11707, 3, 0, 97, '2026-07-27 08:26:08'),
(11708, 3, 0, 95, '2026-07-27 08:26:08'),
(11709, 3, 0, 2, '2026-07-27 08:26:08'),
(11710, 3, 0, 1, '2026-07-27 08:26:08'),
(11711, 3, 0, 68, '2026-07-27 08:26:08'),
(11712, 3, 0, 72, '2026-07-27 08:26:08'),
(11713, 3, 0, 71, '2026-07-27 08:26:08'),
(11714, 3, 0, 75, '2026-07-27 08:26:08'),
(11715, 3, 0, 34, '2026-07-27 08:26:08'),
(11716, 3, 0, 205, '2026-07-27 08:26:08'),
(11717, 3, 0, 207, '2026-07-27 08:26:08'),
(11718, 3, 0, 206, '2026-07-27 08:26:08'),
(11719, 3, 0, 204, '2026-07-27 08:26:08'),
(11720, 3, 0, 202, '2026-07-27 08:26:08'),
(11721, 3, 0, 43, '2026-07-27 08:26:08'),
(11722, 3, 0, 45, '2026-07-27 08:26:08'),
(11723, 3, 0, 44, '2026-07-27 08:26:08'),
(11724, 3, 0, 46, '2026-07-27 08:26:08'),
(11725, 3, 0, 42, '2026-07-27 08:26:08'),
(11726, 3, 0, 201, '2026-07-27 08:26:08'),
(11727, 3, 0, 56, '2026-07-27 08:26:08'),
(11728, 3, 0, 58, '2026-07-27 08:26:08'),
(11729, 3, 0, 57, '2026-07-27 08:26:08'),
(11730, 3, 0, 55, '2026-07-27 08:26:08'),
(11731, 3, 0, 99, '2026-07-27 08:26:08'),
(12001, 2, 9, 48, '2026-07-27 08:58:38'),
(12002, 2, 9, 50, '2026-07-27 08:58:38'),
(12003, 2, 9, 49, '2026-07-27 08:58:38'),
(12004, 2, 9, 47, '2026-07-27 08:58:38'),
(12005, 2, 9, 96, '2026-07-27 08:58:38'),
(12006, 2, 9, 98, '2026-07-27 08:58:38'),
(12007, 2, 9, 97, '2026-07-27 08:58:38'),
(12008, 2, 9, 95, '2026-07-27 08:58:38'),
(12009, 2, 9, 2, '2026-07-27 08:58:38'),
(12010, 2, 9, 1, '2026-07-27 08:58:38'),
(12011, 2, 9, 6, '2026-07-27 08:58:38'),
(12012, 2, 9, 8, '2026-07-27 08:58:38'),
(12013, 2, 9, 7, '2026-07-27 08:58:38'),
(12014, 2, 9, 9, '2026-07-27 08:58:38'),
(12015, 2, 9, 5, '2026-07-27 08:58:38'),
(12016, 2, 9, 68, '2026-07-27 08:58:38'),
(12017, 2, 9, 70, '2026-07-27 08:58:38'),
(12018, 2, 9, 69, '2026-07-27 08:58:38'),
(12019, 2, 9, 67, '2026-07-27 08:58:38'),
(12020, 2, 9, 60, '2026-07-27 08:58:38'),
(12021, 2, 9, 62, '2026-07-27 08:58:38'),
(12022, 2, 9, 61, '2026-07-27 08:58:38'),
(12023, 2, 9, 59, '2026-07-27 08:58:38'),
(12024, 2, 9, 92, '2026-07-27 08:58:38'),
(12025, 2, 9, 94, '2026-07-27 08:58:38'),
(12026, 2, 9, 93, '2026-07-27 08:58:38'),
(12027, 2, 9, 91, '2026-07-27 08:58:38'),
(12028, 2, 9, 64, '2026-07-27 08:58:38'),
(12029, 2, 9, 66, '2026-07-27 08:58:38'),
(12030, 2, 9, 65, '2026-07-27 08:58:38'),
(12031, 2, 9, 63, '2026-07-27 08:58:38'),
(12032, 2, 9, 203, '2026-07-27 08:58:38'),
(12033, 2, 9, 72, '2026-07-27 08:58:38'),
(12034, 2, 9, 74, '2026-07-27 08:58:38'),
(12035, 2, 9, 73, '2026-07-27 08:58:38'),
(12036, 2, 9, 71, '2026-07-27 08:58:38'),
(12037, 2, 9, 76, '2026-07-27 08:58:38'),
(12038, 2, 9, 78, '2026-07-27 08:58:38'),
(12039, 2, 9, 77, '2026-07-27 08:58:38'),
(12040, 2, 9, 75, '2026-07-27 08:58:38'),
(12041, 2, 9, 35, '2026-07-27 08:58:38'),
(12042, 2, 9, 37, '2026-07-27 08:58:38'),
(12043, 2, 9, 36, '2026-07-27 08:58:38'),
(12044, 2, 9, 34, '2026-07-27 08:58:38'),
(12045, 2, 9, 31, '2026-07-27 08:58:38'),
(12046, 2, 9, 33, '2026-07-27 08:58:38'),
(12047, 2, 9, 32, '2026-07-27 08:58:38'),
(12048, 2, 9, 30, '2026-07-27 08:58:38'),
(12049, 2, 9, 11, '2026-07-27 08:58:38'),
(12050, 2, 9, 13, '2026-07-27 08:58:38'),
(12051, 2, 9, 12, '2026-07-27 08:58:38'),
(12052, 2, 9, 10, '2026-07-27 08:58:38'),
(12053, 2, 9, 15, '2026-07-27 08:58:38'),
(12054, 2, 9, 17, '2026-07-27 08:58:38'),
(12055, 2, 9, 16, '2026-07-27 08:58:38'),
(12056, 2, 9, 14, '2026-07-27 08:58:38'),
(12057, 2, 9, 39, '2026-07-27 08:58:38'),
(12058, 2, 9, 41, '2026-07-27 08:58:38'),
(12059, 2, 9, 40, '2026-07-27 08:58:38'),
(12060, 2, 9, 38, '2026-07-27 08:58:38'),
(12061, 2, 9, 27, '2026-07-27 08:58:38'),
(12062, 2, 9, 29, '2026-07-27 08:58:38'),
(12063, 2, 9, 28, '2026-07-27 08:58:38'),
(12064, 2, 9, 26, '2026-07-27 08:58:38'),
(12065, 2, 9, 19, '2026-07-27 08:58:38'),
(12066, 2, 9, 21, '2026-07-27 08:58:38'),
(12067, 2, 9, 20, '2026-07-27 08:58:38'),
(12068, 2, 9, 18, '2026-07-27 08:58:38'),
(12069, 2, 9, 23, '2026-07-27 08:58:38'),
(12070, 2, 9, 25, '2026-07-27 08:58:38'),
(12071, 2, 9, 24, '2026-07-27 08:58:38'),
(12072, 2, 9, 22, '2026-07-27 08:58:38'),
(12073, 2, 9, 88, '2026-07-27 08:58:38'),
(12074, 2, 9, 90, '2026-07-27 08:58:38'),
(12075, 2, 9, 89, '2026-07-27 08:58:38'),
(12076, 2, 9, 87, '2026-07-27 08:58:38'),
(12077, 2, 9, 52, '2026-07-27 08:58:38'),
(12078, 2, 9, 54, '2026-07-27 08:58:38'),
(12079, 2, 9, 53, '2026-07-27 08:58:38'),
(12080, 2, 9, 51, '2026-07-27 08:58:38'),
(12081, 2, 9, 205, '2026-07-27 08:58:38'),
(12082, 2, 9, 207, '2026-07-27 08:58:38'),
(12083, 2, 9, 206, '2026-07-27 08:58:38'),
(12084, 2, 9, 204, '2026-07-27 08:58:38'),
(12085, 2, 9, 202, '2026-07-27 08:58:38'),
(12086, 2, 9, 43, '2026-07-27 08:58:38'),
(12087, 2, 9, 45, '2026-07-27 08:58:38'),
(12088, 2, 9, 44, '2026-07-27 08:58:38'),
(12089, 2, 9, 46, '2026-07-27 08:58:38'),
(12090, 2, 9, 42, '2026-07-27 08:58:38'),
(12091, 2, 9, 201, '2026-07-27 08:58:38'),
(12092, 2, 9, 80, '2026-07-27 08:58:38'),
(12093, 2, 9, 82, '2026-07-27 08:58:38'),
(12094, 2, 9, 81, '2026-07-27 08:58:38'),
(12095, 2, 9, 79, '2026-07-27 08:58:38'),
(12096, 2, 9, 84, '2026-07-27 08:58:38'),
(12097, 2, 9, 86, '2026-07-27 08:58:38'),
(12098, 2, 9, 85, '2026-07-27 08:58:38'),
(12099, 2, 9, 83, '2026-07-27 08:58:38'),
(12100, 2, 9, 56, '2026-07-27 08:58:38'),
(12101, 2, 9, 58, '2026-07-27 08:58:38'),
(12102, 2, 9, 57, '2026-07-27 08:58:38'),
(12103, 2, 9, 55, '2026-07-27 08:58:38'),
(12104, 2, 9, 99, '2026-07-27 08:58:38'),
(12177, 3, 5, 48, '2026-07-28 08:26:15'),
(12178, 3, 5, 50, '2026-07-28 08:26:15'),
(12179, 3, 5, 49, '2026-07-28 08:26:15'),
(12180, 3, 5, 47, '2026-07-28 08:26:15'),
(12181, 3, 5, 96, '2026-07-28 08:26:15'),
(12182, 3, 5, 98, '2026-07-28 08:26:15'),
(12183, 3, 5, 97, '2026-07-28 08:26:15'),
(12184, 3, 5, 95, '2026-07-28 08:26:15'),
(12185, 3, 5, 2, '2026-07-28 08:26:15'),
(12186, 3, 5, 1, '2026-07-28 08:26:15'),
(12187, 3, 5, 67, '2026-07-28 08:26:15'),
(12188, 3, 5, 60, '2026-07-28 08:26:15'),
(12189, 3, 5, 59, '2026-07-28 08:26:15'),
(12190, 3, 5, 92, '2026-07-28 08:26:15'),
(12191, 3, 5, 91, '2026-07-28 08:26:15'),
(12192, 3, 5, 64, '2026-07-28 08:26:15'),
(12193, 3, 5, 63, '2026-07-28 08:26:15'),
(12194, 3, 5, 72, '2026-07-28 08:26:15'),
(12195, 3, 5, 71, '2026-07-28 08:26:15'),
(12196, 3, 5, 76, '2026-07-28 08:26:15'),
(12197, 3, 5, 75, '2026-07-28 08:26:15'),
(12198, 3, 5, 35, '2026-07-28 08:26:15'),
(12199, 3, 5, 34, '2026-07-28 08:26:15'),
(12200, 3, 5, 52, '2026-07-28 08:26:15'),
(12201, 3, 5, 51, '2026-07-28 08:26:15'),
(12202, 3, 5, 205, '2026-07-28 08:26:15'),
(12203, 3, 5, 207, '2026-07-28 08:26:15'),
(12204, 3, 5, 206, '2026-07-28 08:26:15'),
(12205, 3, 5, 204, '2026-07-28 08:26:15'),
(12206, 3, 5, 202, '2026-07-28 08:26:15'),
(12207, 3, 5, 43, '2026-07-28 08:26:15'),
(12208, 3, 5, 45, '2026-07-28 08:26:15'),
(12209, 3, 5, 44, '2026-07-28 08:26:15'),
(12210, 3, 5, 46, '2026-07-28 08:26:15'),
(12211, 3, 5, 42, '2026-07-28 08:26:15'),
(12212, 3, 5, 201, '2026-07-28 08:26:15'),
(12213, 3, 5, 56, '2026-07-28 08:26:15'),
(12214, 3, 5, 58, '2026-07-28 08:26:15'),
(12215, 3, 5, 57, '2026-07-28 08:26:15'),
(12216, 3, 5, 55, '2026-07-28 08:26:15'),
(12217, 3, 5, 99, '2026-07-28 08:26:15'),
(14237, 2, 5, 48, '2026-07-29 07:03:36'),
(14238, 2, 5, 50, '2026-07-29 07:03:36'),
(14239, 2, 5, 49, '2026-07-29 07:03:36'),
(14240, 2, 5, 47, '2026-07-29 07:03:36'),
(14241, 2, 5, 96, '2026-07-29 07:03:36'),
(14242, 2, 5, 98, '2026-07-29 07:03:36'),
(14243, 2, 5, 97, '2026-07-29 07:03:36'),
(14244, 2, 5, 95, '2026-07-29 07:03:36'),
(14245, 2, 5, 2, '2026-07-29 07:03:36'),
(14246, 2, 5, 1, '2026-07-29 07:03:36'),
(14247, 2, 5, 6, '2026-07-29 07:03:36'),
(14248, 2, 5, 8, '2026-07-29 07:03:36'),
(14249, 2, 5, 7, '2026-07-29 07:03:36'),
(14250, 2, 5, 9, '2026-07-29 07:03:36'),
(14251, 2, 5, 5, '2026-07-29 07:03:36'),
(14252, 2, 5, 68, '2026-07-29 07:03:36'),
(14253, 2, 5, 70, '2026-07-29 07:03:36'),
(14254, 2, 5, 69, '2026-07-29 07:03:36'),
(14255, 2, 5, 67, '2026-07-29 07:03:36'),
(14256, 2, 5, 60, '2026-07-29 07:03:36'),
(14257, 2, 5, 62, '2026-07-29 07:03:36'),
(14258, 2, 5, 61, '2026-07-29 07:03:36'),
(14259, 2, 5, 59, '2026-07-29 07:03:36'),
(14260, 2, 5, 92, '2026-07-29 07:03:36'),
(14261, 2, 5, 94, '2026-07-29 07:03:36'),
(14262, 2, 5, 93, '2026-07-29 07:03:36'),
(14263, 2, 5, 91, '2026-07-29 07:03:36'),
(14264, 2, 5, 64, '2026-07-29 07:03:36'),
(14265, 2, 5, 66, '2026-07-29 07:03:36'),
(14266, 2, 5, 65, '2026-07-29 07:03:36'),
(14267, 2, 5, 63, '2026-07-29 07:03:36'),
(14268, 2, 5, 203, '2026-07-29 07:03:36'),
(14269, 2, 5, 75, '2026-07-29 07:03:36'),
(14270, 2, 5, 31, '2026-07-29 07:03:36'),
(14271, 2, 5, 33, '2026-07-29 07:03:36'),
(14272, 2, 5, 32, '2026-07-29 07:03:36'),
(14273, 2, 5, 30, '2026-07-29 07:03:36'),
(14274, 2, 5, 11, '2026-07-29 07:03:36'),
(14275, 2, 5, 13, '2026-07-29 07:03:36'),
(14276, 2, 5, 12, '2026-07-29 07:03:36'),
(14277, 2, 5, 10, '2026-07-29 07:03:36'),
(14278, 2, 5, 15, '2026-07-29 07:03:36'),
(14279, 2, 5, 17, '2026-07-29 07:03:36'),
(14280, 2, 5, 16, '2026-07-29 07:03:36'),
(14281, 2, 5, 14, '2026-07-29 07:03:36'),
(14282, 2, 5, 39, '2026-07-29 07:03:36'),
(14283, 2, 5, 41, '2026-07-29 07:03:36'),
(14284, 2, 5, 40, '2026-07-29 07:03:36'),
(14285, 2, 5, 38, '2026-07-29 07:03:36'),
(14286, 2, 5, 27, '2026-07-29 07:03:36'),
(14287, 2, 5, 29, '2026-07-29 07:03:36'),
(14288, 2, 5, 28, '2026-07-29 07:03:36'),
(14289, 2, 5, 26, '2026-07-29 07:03:36'),
(14290, 2, 5, 19, '2026-07-29 07:03:36'),
(14291, 2, 5, 21, '2026-07-29 07:03:36'),
(14292, 2, 5, 20, '2026-07-29 07:03:36'),
(14293, 2, 5, 18, '2026-07-29 07:03:36'),
(14294, 2, 5, 23, '2026-07-29 07:03:36'),
(14295, 2, 5, 25, '2026-07-29 07:03:36'),
(14296, 2, 5, 24, '2026-07-29 07:03:36'),
(14297, 2, 5, 22, '2026-07-29 07:03:36'),
(14298, 2, 5, 88, '2026-07-29 07:03:36'),
(14299, 2, 5, 90, '2026-07-29 07:03:36'),
(14300, 2, 5, 89, '2026-07-29 07:03:36'),
(14301, 2, 5, 87, '2026-07-29 07:03:36'),
(14302, 2, 5, 52, '2026-07-29 07:03:36'),
(14303, 2, 5, 54, '2026-07-29 07:03:36'),
(14304, 2, 5, 53, '2026-07-29 07:03:36'),
(14305, 2, 5, 51, '2026-07-29 07:03:36'),
(14306, 2, 5, 205, '2026-07-29 07:03:36'),
(14307, 2, 5, 207, '2026-07-29 07:03:36'),
(14308, 2, 5, 206, '2026-07-29 07:03:36'),
(14309, 2, 5, 204, '2026-07-29 07:03:36'),
(14310, 2, 5, 202, '2026-07-29 07:03:36'),
(14311, 2, 5, 43, '2026-07-29 07:03:36'),
(14312, 2, 5, 45, '2026-07-29 07:03:36'),
(14313, 2, 5, 44, '2026-07-29 07:03:36'),
(14314, 2, 5, 46, '2026-07-29 07:03:36'),
(14315, 2, 5, 42, '2026-07-29 07:03:36'),
(14316, 2, 5, 201, '2026-07-29 07:03:36'),
(14317, 2, 5, 80, '2026-07-29 07:03:36'),
(14318, 2, 5, 82, '2026-07-29 07:03:36'),
(14319, 2, 5, 81, '2026-07-29 07:03:36'),
(14320, 2, 5, 79, '2026-07-29 07:03:36'),
(14321, 2, 5, 84, '2026-07-29 07:03:36'),
(14322, 2, 5, 86, '2026-07-29 07:03:36'),
(14323, 2, 5, 85, '2026-07-29 07:03:36'),
(14324, 2, 5, 83, '2026-07-29 07:03:36'),
(14325, 2, 5, 56, '2026-07-29 07:03:36'),
(14326, 2, 5, 58, '2026-07-29 07:03:36'),
(14327, 2, 5, 57, '2026-07-29 07:03:36'),
(14328, 2, 5, 55, '2026-07-29 07:03:36'),
(14329, 2, 5, 99, '2026-07-29 07:03:36'),
(14330, 2, 6, 48, '2026-07-29 07:03:36'),
(14331, 2, 6, 50, '2026-07-29 07:03:36'),
(14332, 2, 6, 49, '2026-07-29 07:03:36'),
(14333, 2, 6, 47, '2026-07-29 07:03:36'),
(14334, 2, 6, 96, '2026-07-29 07:03:36'),
(14335, 2, 6, 98, '2026-07-29 07:03:36'),
(14336, 2, 6, 97, '2026-07-29 07:03:36'),
(14337, 2, 6, 95, '2026-07-29 07:03:36'),
(14338, 2, 6, 2, '2026-07-29 07:03:36'),
(14339, 2, 6, 1, '2026-07-29 07:03:36'),
(14340, 2, 6, 6, '2026-07-29 07:03:36'),
(14341, 2, 6, 8, '2026-07-29 07:03:36'),
(14342, 2, 6, 7, '2026-07-29 07:03:36'),
(14343, 2, 6, 9, '2026-07-29 07:03:36'),
(14344, 2, 6, 5, '2026-07-29 07:03:36'),
(14345, 2, 6, 68, '2026-07-29 07:03:36'),
(14346, 2, 6, 70, '2026-07-29 07:03:36'),
(14347, 2, 6, 69, '2026-07-29 07:03:36'),
(14348, 2, 6, 67, '2026-07-29 07:03:36'),
(14349, 2, 6, 60, '2026-07-29 07:03:36'),
(14350, 2, 6, 62, '2026-07-29 07:03:36'),
(14351, 2, 6, 61, '2026-07-29 07:03:36'),
(14352, 2, 6, 59, '2026-07-29 07:03:36'),
(14353, 2, 6, 92, '2026-07-29 07:03:36'),
(14354, 2, 6, 94, '2026-07-29 07:03:36'),
(14355, 2, 6, 93, '2026-07-29 07:03:36'),
(14356, 2, 6, 91, '2026-07-29 07:03:36'),
(14357, 2, 6, 64, '2026-07-29 07:03:36'),
(14358, 2, 6, 66, '2026-07-29 07:03:36'),
(14359, 2, 6, 65, '2026-07-29 07:03:36'),
(14360, 2, 6, 63, '2026-07-29 07:03:36'),
(14361, 2, 6, 203, '2026-07-29 07:03:36'),
(14362, 2, 6, 75, '2026-07-29 07:03:36'),
(14363, 2, 6, 31, '2026-07-29 07:03:36'),
(14364, 2, 6, 33, '2026-07-29 07:03:36'),
(14365, 2, 6, 32, '2026-07-29 07:03:36'),
(14366, 2, 6, 30, '2026-07-29 07:03:36'),
(14367, 2, 6, 11, '2026-07-29 07:03:36'),
(14368, 2, 6, 13, '2026-07-29 07:03:36'),
(14369, 2, 6, 12, '2026-07-29 07:03:36'),
(14370, 2, 6, 10, '2026-07-29 07:03:36'),
(14371, 2, 6, 15, '2026-07-29 07:03:36'),
(14372, 2, 6, 17, '2026-07-29 07:03:36'),
(14373, 2, 6, 16, '2026-07-29 07:03:36'),
(14374, 2, 6, 14, '2026-07-29 07:03:36'),
(14375, 2, 6, 39, '2026-07-29 07:03:36'),
(14376, 2, 6, 41, '2026-07-29 07:03:36'),
(14377, 2, 6, 40, '2026-07-29 07:03:36'),
(14378, 2, 6, 38, '2026-07-29 07:03:36'),
(14379, 2, 6, 27, '2026-07-29 07:03:36'),
(14380, 2, 6, 29, '2026-07-29 07:03:36'),
(14381, 2, 6, 28, '2026-07-29 07:03:36'),
(14382, 2, 6, 26, '2026-07-29 07:03:36'),
(14383, 2, 6, 19, '2026-07-29 07:03:36'),
(14384, 2, 6, 21, '2026-07-29 07:03:36'),
(14385, 2, 6, 20, '2026-07-29 07:03:36'),
(14386, 2, 6, 18, '2026-07-29 07:03:36'),
(14387, 2, 6, 23, '2026-07-29 07:03:36'),
(14388, 2, 6, 25, '2026-07-29 07:03:36'),
(14389, 2, 6, 24, '2026-07-29 07:03:36'),
(14390, 2, 6, 22, '2026-07-29 07:03:36'),
(14391, 2, 6, 88, '2026-07-29 07:03:36'),
(14392, 2, 6, 90, '2026-07-29 07:03:36'),
(14393, 2, 6, 89, '2026-07-29 07:03:36'),
(14394, 2, 6, 87, '2026-07-29 07:03:36'),
(14395, 2, 6, 52, '2026-07-29 07:03:36'),
(14396, 2, 6, 54, '2026-07-29 07:03:36'),
(14397, 2, 6, 53, '2026-07-29 07:03:36'),
(14398, 2, 6, 51, '2026-07-29 07:03:36'),
(14399, 2, 6, 205, '2026-07-29 07:03:36'),
(14400, 2, 6, 207, '2026-07-29 07:03:36'),
(14401, 2, 6, 206, '2026-07-29 07:03:36'),
(14402, 2, 6, 204, '2026-07-29 07:03:36'),
(14403, 2, 6, 202, '2026-07-29 07:03:36'),
(14404, 2, 6, 43, '2026-07-29 07:03:36'),
(14405, 2, 6, 45, '2026-07-29 07:03:36'),
(14406, 2, 6, 44, '2026-07-29 07:03:36'),
(14407, 2, 6, 46, '2026-07-29 07:03:36'),
(14408, 2, 6, 42, '2026-07-29 07:03:36'),
(14409, 2, 6, 201, '2026-07-29 07:03:36'),
(14410, 2, 6, 80, '2026-07-29 07:03:36'),
(14411, 2, 6, 82, '2026-07-29 07:03:36'),
(14412, 2, 6, 81, '2026-07-29 07:03:36'),
(14413, 2, 6, 79, '2026-07-29 07:03:36'),
(14414, 2, 6, 84, '2026-07-29 07:03:36'),
(14415, 2, 6, 86, '2026-07-29 07:03:36'),
(14416, 2, 6, 85, '2026-07-29 07:03:36'),
(14417, 2, 6, 83, '2026-07-29 07:03:36'),
(14418, 2, 6, 56, '2026-07-29 07:03:36'),
(14419, 2, 6, 58, '2026-07-29 07:03:36'),
(14420, 2, 6, 57, '2026-07-29 07:03:36'),
(14421, 2, 6, 55, '2026-07-29 07:03:36'),
(14422, 2, 6, 99, '2026-07-29 07:03:36'),
(14423, 2, 7, 48, '2026-07-29 07:03:36'),
(14424, 2, 7, 50, '2026-07-29 07:03:36'),
(14425, 2, 7, 49, '2026-07-29 07:03:36'),
(14426, 2, 7, 47, '2026-07-29 07:03:36'),
(14427, 2, 7, 96, '2026-07-29 07:03:36'),
(14428, 2, 7, 98, '2026-07-29 07:03:36'),
(14429, 2, 7, 97, '2026-07-29 07:03:36'),
(14430, 2, 7, 95, '2026-07-29 07:03:36'),
(14431, 2, 7, 2, '2026-07-29 07:03:36'),
(14432, 2, 7, 1, '2026-07-29 07:03:36'),
(14433, 2, 7, 6, '2026-07-29 07:03:36'),
(14434, 2, 7, 8, '2026-07-29 07:03:36'),
(14435, 2, 7, 7, '2026-07-29 07:03:36'),
(14436, 2, 7, 9, '2026-07-29 07:03:36'),
(14437, 2, 7, 5, '2026-07-29 07:03:36'),
(14438, 2, 7, 68, '2026-07-29 07:03:36'),
(14439, 2, 7, 70, '2026-07-29 07:03:36'),
(14440, 2, 7, 69, '2026-07-29 07:03:36'),
(14441, 2, 7, 67, '2026-07-29 07:03:36'),
(14442, 2, 7, 60, '2026-07-29 07:03:36'),
(14443, 2, 7, 62, '2026-07-29 07:03:36'),
(14444, 2, 7, 61, '2026-07-29 07:03:36'),
(14445, 2, 7, 59, '2026-07-29 07:03:36'),
(14446, 2, 7, 92, '2026-07-29 07:03:36'),
(14447, 2, 7, 94, '2026-07-29 07:03:36'),
(14448, 2, 7, 93, '2026-07-29 07:03:36'),
(14449, 2, 7, 91, '2026-07-29 07:03:36'),
(14450, 2, 7, 64, '2026-07-29 07:03:36'),
(14451, 2, 7, 66, '2026-07-29 07:03:36'),
(14452, 2, 7, 65, '2026-07-29 07:03:36'),
(14453, 2, 7, 63, '2026-07-29 07:03:36'),
(14454, 2, 7, 203, '2026-07-29 07:03:36'),
(14455, 2, 7, 75, '2026-07-29 07:03:36'),
(14456, 2, 7, 31, '2026-07-29 07:03:36'),
(14457, 2, 7, 33, '2026-07-29 07:03:36'),
(14458, 2, 7, 32, '2026-07-29 07:03:36'),
(14459, 2, 7, 30, '2026-07-29 07:03:36'),
(14460, 2, 7, 11, '2026-07-29 07:03:36'),
(14461, 2, 7, 13, '2026-07-29 07:03:36'),
(14462, 2, 7, 12, '2026-07-29 07:03:36'),
(14463, 2, 7, 10, '2026-07-29 07:03:36'),
(14464, 2, 7, 15, '2026-07-29 07:03:36'),
(14465, 2, 7, 17, '2026-07-29 07:03:36'),
(14466, 2, 7, 16, '2026-07-29 07:03:36'),
(14467, 2, 7, 14, '2026-07-29 07:03:36'),
(14468, 2, 7, 39, '2026-07-29 07:03:36'),
(14469, 2, 7, 41, '2026-07-29 07:03:36'),
(14470, 2, 7, 40, '2026-07-29 07:03:36'),
(14471, 2, 7, 38, '2026-07-29 07:03:36'),
(14472, 2, 7, 27, '2026-07-29 07:03:36'),
(14473, 2, 7, 29, '2026-07-29 07:03:36'),
(14474, 2, 7, 28, '2026-07-29 07:03:36'),
(14475, 2, 7, 26, '2026-07-29 07:03:36'),
(14476, 2, 7, 19, '2026-07-29 07:03:36'),
(14477, 2, 7, 21, '2026-07-29 07:03:36'),
(14478, 2, 7, 20, '2026-07-29 07:03:36'),
(14479, 2, 7, 18, '2026-07-29 07:03:36'),
(14480, 2, 7, 23, '2026-07-29 07:03:36'),
(14481, 2, 7, 25, '2026-07-29 07:03:36'),
(14482, 2, 7, 24, '2026-07-29 07:03:36'),
(14483, 2, 7, 22, '2026-07-29 07:03:36'),
(14484, 2, 7, 88, '2026-07-29 07:03:36'),
(14485, 2, 7, 90, '2026-07-29 07:03:36'),
(14486, 2, 7, 89, '2026-07-29 07:03:36'),
(14487, 2, 7, 87, '2026-07-29 07:03:36'),
(14488, 2, 7, 52, '2026-07-29 07:03:36'),
(14489, 2, 7, 54, '2026-07-29 07:03:36'),
(14490, 2, 7, 53, '2026-07-29 07:03:36'),
(14491, 2, 7, 51, '2026-07-29 07:03:36'),
(14492, 2, 7, 205, '2026-07-29 07:03:36'),
(14493, 2, 7, 207, '2026-07-29 07:03:36'),
(14494, 2, 7, 206, '2026-07-29 07:03:36'),
(14495, 2, 7, 204, '2026-07-29 07:03:36'),
(14496, 2, 7, 202, '2026-07-29 07:03:36'),
(14497, 2, 7, 43, '2026-07-29 07:03:36'),
(14498, 2, 7, 45, '2026-07-29 07:03:36'),
(14499, 2, 7, 44, '2026-07-29 07:03:36'),
(14500, 2, 7, 46, '2026-07-29 07:03:36'),
(14501, 2, 7, 42, '2026-07-29 07:03:36'),
(14502, 2, 7, 201, '2026-07-29 07:03:36'),
(14503, 2, 7, 80, '2026-07-29 07:03:36'),
(14504, 2, 7, 82, '2026-07-29 07:03:36'),
(14505, 2, 7, 81, '2026-07-29 07:03:36'),
(14506, 2, 7, 79, '2026-07-29 07:03:36'),
(14507, 2, 7, 84, '2026-07-29 07:03:36'),
(14508, 2, 7, 86, '2026-07-29 07:03:36'),
(14509, 2, 7, 85, '2026-07-29 07:03:36'),
(14510, 2, 7, 83, '2026-07-29 07:03:36'),
(14511, 2, 7, 56, '2026-07-29 07:03:36'),
(14512, 2, 7, 58, '2026-07-29 07:03:36'),
(14513, 2, 7, 57, '2026-07-29 07:03:36'),
(14514, 2, 7, 55, '2026-07-29 07:03:36'),
(14515, 2, 7, 99, '2026-07-29 07:03:36'),
(14585, 13, 5, 48, '2026-07-30 10:54:01'),
(14586, 13, 5, 49, '2026-07-30 10:54:01'),
(14587, 13, 5, 47, '2026-07-30 10:54:01'),
(14588, 13, 5, 96, '2026-07-30 10:54:01'),
(14589, 13, 5, 97, '2026-07-30 10:54:01'),
(14590, 13, 5, 95, '2026-07-30 10:54:01'),
(14591, 13, 5, 2, '2026-07-30 10:54:01'),
(14592, 13, 5, 1, '2026-07-30 10:54:01'),
(14593, 13, 5, 68, '2026-07-30 10:54:01'),
(14594, 13, 5, 70, '2026-07-30 10:54:01'),
(14595, 13, 5, 69, '2026-07-30 10:54:01'),
(14596, 13, 5, 67, '2026-07-30 10:54:01'),
(14597, 13, 5, 60, '2026-07-30 10:54:01'),
(14598, 13, 5, 62, '2026-07-30 10:54:01'),
(14599, 13, 5, 61, '2026-07-30 10:54:01'),
(14600, 13, 5, 59, '2026-07-30 10:54:01'),
(14601, 13, 5, 92, '2026-07-30 10:54:01'),
(14602, 13, 5, 94, '2026-07-30 10:54:01'),
(14603, 13, 5, 93, '2026-07-30 10:54:01'),
(14604, 13, 5, 91, '2026-07-30 10:54:01'),
(14605, 13, 5, 64, '2026-07-30 10:54:01'),
(14606, 13, 5, 66, '2026-07-30 10:54:01'),
(14607, 13, 5, 65, '2026-07-30 10:54:01'),
(14608, 13, 5, 63, '2026-07-30 10:54:01'),
(14609, 13, 5, 14, '2026-07-30 10:54:01'),
(14610, 13, 5, 88, '2026-07-30 10:54:01'),
(14611, 13, 5, 90, '2026-07-30 10:54:01'),
(14612, 13, 5, 89, '2026-07-30 10:54:01'),
(14613, 13, 5, 87, '2026-07-30 10:54:01'),
(14614, 13, 5, 52, '2026-07-30 10:54:01'),
(14615, 13, 5, 54, '2026-07-30 10:54:01'),
(14616, 13, 5, 53, '2026-07-30 10:54:01'),
(14617, 13, 5, 51, '2026-07-30 10:54:01'),
(14618, 13, 5, 205, '2026-07-30 10:54:01'),
(14619, 13, 5, 206, '2026-07-30 10:54:01'),
(14620, 13, 5, 204, '2026-07-30 10:54:01'),
(14621, 13, 5, 202, '2026-07-30 10:54:01'),
(14622, 13, 5, 43, '2026-07-30 10:54:01'),
(14623, 13, 5, 45, '2026-07-30 10:54:01'),
(14624, 13, 5, 44, '2026-07-30 10:54:01'),
(14625, 13, 5, 46, '2026-07-30 10:54:01'),
(14626, 13, 5, 42, '2026-07-30 10:54:01'),
(14627, 13, 5, 201, '2026-07-30 10:54:01'),
(14628, 13, 5, 57, '2026-07-30 10:54:01'),
(14629, 13, 5, 55, '2026-07-30 10:54:01'),
(14630, 13, 5, 99, '2026-07-30 10:54:01'),
(14631, 13, 6, 48, '2026-07-30 10:54:01'),
(14632, 13, 6, 49, '2026-07-30 10:54:01'),
(14633, 13, 6, 47, '2026-07-30 10:54:01'),
(14634, 13, 6, 96, '2026-07-30 10:54:01'),
(14635, 13, 6, 97, '2026-07-30 10:54:01'),
(14636, 13, 6, 95, '2026-07-30 10:54:01'),
(14637, 13, 6, 2, '2026-07-30 10:54:01'),
(14638, 13, 6, 1, '2026-07-30 10:54:01'),
(14639, 13, 6, 68, '2026-07-30 10:54:01'),
(14640, 13, 6, 70, '2026-07-30 10:54:01'),
(14641, 13, 6, 69, '2026-07-30 10:54:01'),
(14642, 13, 6, 67, '2026-07-30 10:54:01'),
(14643, 13, 6, 60, '2026-07-30 10:54:01'),
(14644, 13, 6, 62, '2026-07-30 10:54:01'),
(14645, 13, 6, 61, '2026-07-30 10:54:01'),
(14646, 13, 6, 59, '2026-07-30 10:54:01'),
(14647, 13, 6, 92, '2026-07-30 10:54:01'),
(14648, 13, 6, 94, '2026-07-30 10:54:01'),
(14649, 13, 6, 93, '2026-07-30 10:54:01'),
(14650, 13, 6, 91, '2026-07-30 10:54:01'),
(14651, 13, 6, 64, '2026-07-30 10:54:01'),
(14652, 13, 6, 66, '2026-07-30 10:54:01'),
(14653, 13, 6, 65, '2026-07-30 10:54:01'),
(14654, 13, 6, 63, '2026-07-30 10:54:01'),
(14655, 13, 6, 14, '2026-07-30 10:54:01'),
(14656, 13, 6, 88, '2026-07-30 10:54:01'),
(14657, 13, 6, 90, '2026-07-30 10:54:01'),
(14658, 13, 6, 89, '2026-07-30 10:54:01'),
(14659, 13, 6, 87, '2026-07-30 10:54:01'),
(14660, 13, 6, 52, '2026-07-30 10:54:01'),
(14661, 13, 6, 54, '2026-07-30 10:54:01'),
(14662, 13, 6, 53, '2026-07-30 10:54:01'),
(14663, 13, 6, 51, '2026-07-30 10:54:01'),
(14664, 13, 6, 205, '2026-07-30 10:54:01'),
(14665, 13, 6, 206, '2026-07-30 10:54:01'),
(14666, 13, 6, 204, '2026-07-30 10:54:01'),
(14667, 13, 6, 202, '2026-07-30 10:54:01'),
(14668, 13, 6, 43, '2026-07-30 10:54:01'),
(14669, 13, 6, 45, '2026-07-30 10:54:01'),
(14670, 13, 6, 44, '2026-07-30 10:54:01'),
(14671, 13, 6, 46, '2026-07-30 10:54:01'),
(14672, 13, 6, 42, '2026-07-30 10:54:01'),
(14673, 13, 6, 201, '2026-07-30 10:54:01'),
(14674, 13, 6, 57, '2026-07-30 10:54:01'),
(14675, 13, 6, 55, '2026-07-30 10:54:01'),
(14676, 13, 6, 99, '2026-07-30 10:54:01'),
(14677, 13, 7, 48, '2026-07-30 10:54:01'),
(14678, 13, 7, 49, '2026-07-30 10:54:01'),
(14679, 13, 7, 47, '2026-07-30 10:54:01'),
(14680, 13, 7, 96, '2026-07-30 10:54:01'),
(14681, 13, 7, 97, '2026-07-30 10:54:01'),
(14682, 13, 7, 95, '2026-07-30 10:54:01'),
(14683, 13, 7, 2, '2026-07-30 10:54:01'),
(14684, 13, 7, 1, '2026-07-30 10:54:01'),
(14685, 13, 7, 68, '2026-07-30 10:54:01'),
(14686, 13, 7, 70, '2026-07-30 10:54:01'),
(14687, 13, 7, 69, '2026-07-30 10:54:01'),
(14688, 13, 7, 67, '2026-07-30 10:54:01'),
(14689, 13, 7, 60, '2026-07-30 10:54:01'),
(14690, 13, 7, 62, '2026-07-30 10:54:01'),
(14691, 13, 7, 61, '2026-07-30 10:54:01'),
(14692, 13, 7, 59, '2026-07-30 10:54:01'),
(14693, 13, 7, 92, '2026-07-30 10:54:01'),
(14694, 13, 7, 94, '2026-07-30 10:54:01'),
(14695, 13, 7, 93, '2026-07-30 10:54:01'),
(14696, 13, 7, 91, '2026-07-30 10:54:01'),
(14697, 13, 7, 64, '2026-07-30 10:54:01'),
(14698, 13, 7, 66, '2026-07-30 10:54:01'),
(14699, 13, 7, 65, '2026-07-30 10:54:01'),
(14700, 13, 7, 63, '2026-07-30 10:54:01'),
(14701, 13, 7, 14, '2026-07-30 10:54:01'),
(14702, 13, 7, 88, '2026-07-30 10:54:01'),
(14703, 13, 7, 90, '2026-07-30 10:54:01'),
(14704, 13, 7, 89, '2026-07-30 10:54:01'),
(14705, 13, 7, 87, '2026-07-30 10:54:01'),
(14706, 13, 7, 52, '2026-07-30 10:54:01'),
(14707, 13, 7, 54, '2026-07-30 10:54:01'),
(14708, 13, 7, 53, '2026-07-30 10:54:01'),
(14709, 13, 7, 51, '2026-07-30 10:54:01'),
(14710, 13, 7, 205, '2026-07-30 10:54:01'),
(14711, 13, 7, 206, '2026-07-30 10:54:01'),
(14712, 13, 7, 204, '2026-07-30 10:54:01'),
(14713, 13, 7, 202, '2026-07-30 10:54:01'),
(14714, 13, 7, 43, '2026-07-30 10:54:01'),
(14715, 13, 7, 45, '2026-07-30 10:54:01'),
(14716, 13, 7, 44, '2026-07-30 10:54:01'),
(14717, 13, 7, 46, '2026-07-30 10:54:01'),
(14718, 13, 7, 42, '2026-07-30 10:54:01'),
(14719, 13, 7, 201, '2026-07-30 10:54:01'),
(14720, 13, 7, 57, '2026-07-30 10:54:01'),
(14721, 13, 7, 55, '2026-07-30 10:54:01'),
(14722, 13, 7, 99, '2026-07-30 10:54:01'),
(14723, 13, 9, 48, '2026-07-30 10:54:01'),
(14724, 13, 9, 49, '2026-07-30 10:54:01'),
(14725, 13, 9, 47, '2026-07-30 10:54:01'),
(14726, 13, 9, 96, '2026-07-30 10:54:01'),
(14727, 13, 9, 97, '2026-07-30 10:54:01'),
(14728, 13, 9, 95, '2026-07-30 10:54:01'),
(14729, 13, 9, 2, '2026-07-30 10:54:01'),
(14730, 13, 9, 1, '2026-07-30 10:54:01'),
(14731, 13, 9, 68, '2026-07-30 10:54:01'),
(14732, 13, 9, 70, '2026-07-30 10:54:01'),
(14733, 13, 9, 69, '2026-07-30 10:54:01'),
(14734, 13, 9, 67, '2026-07-30 10:54:01'),
(14735, 13, 9, 60, '2026-07-30 10:54:01'),
(14736, 13, 9, 62, '2026-07-30 10:54:01'),
(14737, 13, 9, 61, '2026-07-30 10:54:01'),
(14738, 13, 9, 59, '2026-07-30 10:54:01'),
(14739, 13, 9, 92, '2026-07-30 10:54:01'),
(14740, 13, 9, 94, '2026-07-30 10:54:01'),
(14741, 13, 9, 93, '2026-07-30 10:54:01'),
(14742, 13, 9, 91, '2026-07-30 10:54:01'),
(14743, 13, 9, 64, '2026-07-30 10:54:01'),
(14744, 13, 9, 66, '2026-07-30 10:54:01'),
(14745, 13, 9, 65, '2026-07-30 10:54:01'),
(14746, 13, 9, 63, '2026-07-30 10:54:01'),
(14747, 13, 9, 14, '2026-07-30 10:54:01'),
(14748, 13, 9, 88, '2026-07-30 10:54:01'),
(14749, 13, 9, 90, '2026-07-30 10:54:01'),
(14750, 13, 9, 89, '2026-07-30 10:54:01'),
(14751, 13, 9, 87, '2026-07-30 10:54:01'),
(14752, 13, 9, 52, '2026-07-30 10:54:01'),
(14753, 13, 9, 54, '2026-07-30 10:54:01'),
(14754, 13, 9, 53, '2026-07-30 10:54:01'),
(14755, 13, 9, 51, '2026-07-30 10:54:01'),
(14756, 13, 9, 205, '2026-07-30 10:54:01'),
(14757, 13, 9, 206, '2026-07-30 10:54:01'),
(14758, 13, 9, 204, '2026-07-30 10:54:01'),
(14759, 13, 9, 202, '2026-07-30 10:54:01'),
(14760, 13, 9, 43, '2026-07-30 10:54:01'),
(14761, 13, 9, 45, '2026-07-30 10:54:01'),
(14762, 13, 9, 44, '2026-07-30 10:54:01'),
(14763, 13, 9, 46, '2026-07-30 10:54:01'),
(14764, 13, 9, 42, '2026-07-30 10:54:01'),
(14765, 13, 9, 201, '2026-07-30 10:54:01'),
(14766, 13, 9, 57, '2026-07-30 10:54:01'),
(14767, 13, 9, 55, '2026-07-30 10:54:01'),
(14768, 13, 9, 99, '2026-07-30 10:54:01'),
(14769, 8, 5, 48, '2026-07-30 10:54:29'),
(14770, 8, 5, 50, '2026-07-30 10:54:29'),
(14771, 8, 5, 49, '2026-07-30 10:54:29'),
(14772, 8, 5, 47, '2026-07-30 10:54:29'),
(14773, 8, 5, 2, '2026-07-30 10:54:29'),
(14774, 8, 5, 1, '2026-07-30 10:54:29'),
(14775, 8, 5, 75, '2026-07-30 10:54:29'),
(14776, 8, 5, 34, '2026-07-30 10:54:29'),
(14777, 8, 5, 204, '2026-07-30 10:54:29'),
(14778, 8, 5, 55, '2026-07-30 10:54:29'),
(14779, 8, 6, 48, '2026-07-30 10:54:29'),
(14780, 8, 6, 50, '2026-07-30 10:54:29'),
(14781, 8, 6, 49, '2026-07-30 10:54:29'),
(14782, 8, 6, 47, '2026-07-30 10:54:29'),
(14783, 8, 6, 2, '2026-07-30 10:54:29'),
(14784, 8, 6, 1, '2026-07-30 10:54:29'),
(14785, 8, 6, 75, '2026-07-30 10:54:29'),
(14786, 8, 6, 34, '2026-07-30 10:54:29'),
(14787, 8, 6, 204, '2026-07-30 10:54:29'),
(14788, 8, 6, 55, '2026-07-30 10:54:29'),
(14789, 8, 7, 48, '2026-07-30 10:54:29'),
(14790, 8, 7, 50, '2026-07-30 10:54:29'),
(14791, 8, 7, 49, '2026-07-30 10:54:29'),
(14792, 8, 7, 47, '2026-07-30 10:54:29'),
(14793, 8, 7, 2, '2026-07-30 10:54:29'),
(14794, 8, 7, 1, '2026-07-30 10:54:29'),
(14795, 8, 7, 75, '2026-07-30 10:54:29'),
(14796, 8, 7, 34, '2026-07-30 10:54:29'),
(14797, 8, 7, 204, '2026-07-30 10:54:29'),
(14798, 8, 7, 55, '2026-07-30 10:54:29'),
(14799, 8, 9, 48, '2026-07-30 10:54:29'),
(14800, 8, 9, 50, '2026-07-30 10:54:29'),
(14801, 8, 9, 49, '2026-07-30 10:54:29'),
(14802, 8, 9, 47, '2026-07-30 10:54:29'),
(14803, 8, 9, 2, '2026-07-30 10:54:29'),
(14804, 8, 9, 1, '2026-07-30 10:54:29'),
(14805, 8, 9, 75, '2026-07-30 10:54:29'),
(14806, 8, 9, 34, '2026-07-30 10:54:29'),
(14807, 8, 9, 204, '2026-07-30 10:54:29'),
(14808, 8, 9, 55, '2026-07-30 10:54:29');

-- --------------------------------------------------------

--
-- Table structure for table `room_master`
--

CREATE TABLE `room_master` (
  `room_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `ward_id` int(11) NOT NULL,
  `room_no` varchar(20) NOT NULL,
  `capacity` int(11) DEFAULT 1,
  `status` enum('Available','Occupied','Maintenance') DEFAULT 'Available',
  `delete_flag` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_master`
--

INSERT INTO `room_master` (`room_id`, `hospital_id`, `ward_id`, `room_no`, `capacity`, `status`, `delete_flag`, `created_at`, `modified_at`) VALUES
(4, 5, 3, 'R106', 56, 'Available', 0, '2026-07-23 05:32:42', '2026-07-28 10:25:48');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `register_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL,
  `address` text DEFAULT NULL,
  `profile_image` varchar(500) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delete_flag` tinyint(1) DEFAULT 0,
  `hospital_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `register_id`, `name`, `mobile`, `email`, `role`, `address`, `profile_image`, `status`, `created_at`, `updated_at`, `delete_flag`, `hospital_id`) VALUES
(5, 1013, 'Shivatej Katkar', '', 'shivatejkk@gmail.com', 'Ward_Boy', '', 'documents/staff/images/scanner1.jpeg', 'Active', '2026-07-20 12:17:47', '2026-07-31 09:16:35', 1, 6),
(11, 1040, 'Rahul Namya', '7894562144', 'rahul@gmail.com', 'Lab Technician', '', 'documents/staff/images/Department Master.png', 'Active', '2026-07-21 10:08:13', '2026-07-22 10:52:01', 0, 5),
(16, 1058, 'abhishek mandhare', '', 'rohankurn@gmail.com', 'Nurse', '', '', 'Active', '2026-07-29 10:37:28', '2026-07-29 10:50:40', 1, 5),
(21, 1071, 'Shiva Tevar', '', 'shivvv@gmail.com', 'Receptionist', '', '', 'Active', '2026-07-30 17:49:36', '2026-07-31 09:18:29', 0, 5);

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `subscription_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delete_flag` tinyint(1) DEFAULT 0,
  `max_departments` int(11) DEFAULT 2,
  `max_doctors` int(11) DEFAULT 10,
  `max_staff` int(11) DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`subscription_id`, `hospital_id`, `amount`, `created_at`, `modified_at`, `delete_flag`, `max_departments`, `max_doctors`, `max_staff`) VALUES
(1, 5, 0.00, '2026-07-29 09:21:14', '2026-07-31 09:58:29', 0, 2, 5, 5),
(2, 9, 0.00, '2026-07-31 09:52:14', '2026-07-31 09:58:29', 0, 2, 5, 5),
(3, 6, 0.00, '2026-07-31 09:52:29', '2026-07-31 09:58:29', 0, 2, 5, 5),
(4, 7, 0.00, '2026-07-31 09:52:29', '2026-07-31 09:58:29', 0, 2, 5, 5);

-- --------------------------------------------------------

--
-- Table structure for table `surgeries`
--

CREATE TABLE `surgeries` (
  `surgery_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `hospital_id` int(11) DEFAULT NULL,
  `surgery_no` varchar(50) NOT NULL,
  `surgery_title` varchar(200) NOT NULL,
  `surgery_full_name` varchar(200) NOT NULL,
  `surgery_date` date NOT NULL,
  `surgery_time` time NOT NULL,
  `surgery_duration` varchar(50) DEFAULT NULL,
  `hospital_location` varchar(255) DEFAULT NULL,
  `surgeon_name` varchar(150) DEFAULT NULL,
  `assistant_surgeon` varchar(150) DEFAULT NULL,
  `anesthetist` varchar(150) DEFAULT NULL,
  `surgery_type` enum('Major','Minor','Emergency','Elective') DEFAULT NULL,
  `surgery_category` varchar(100) DEFAULT NULL,
  `diagnosis_before_surgery` text DEFAULT NULL,
  `procedure_details` text DEFAULT NULL,
  `findings` text DEFAULT NULL,
  `complications` text DEFAULT NULL,
  `blood_loss` varchar(50) DEFAULT NULL,
  `status` enum('Scheduled','In Progress','Completed','Cancelled','Postponed') DEFAULT 'Scheduled',
  `recovery_notes` text DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delete_flag` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `surgeries`
--

INSERT INTO `surgeries` (`surgery_id`, `patient_id`, `doctor_id`, `hospital_id`, `surgery_no`, `surgery_title`, `surgery_full_name`, `surgery_date`, `surgery_time`, `surgery_duration`, `hospital_location`, `surgeon_name`, `assistant_surgeon`, `anesthetist`, `surgery_type`, `surgery_category`, `diagnosis_before_surgery`, `procedure_details`, `findings`, `complications`, `blood_loss`, `status`, `recovery_notes`, `follow_up_date`, `notes`, `created_at`, `modified_at`, `delete_flag`) VALUES
(4, 6, 8, 5, 'SUR20260722104831', 'Appendoncy', 'Laparesomic Apanedancey', '2026-07-23', '14:18:00', '4 Hours', 'City Hospital', 'Dr Ayush Nipane', 'NIraj Bhute', 'Niram', '', 'Orthopedic', 'asdfgh', 'QAWDFGH', '23456', 'asdfgh', '230 ml', 'Scheduled', '2345ywerghj', '2026-08-07', 'asdfghjkiuytre2345', '2026-07-22 08:48:39', '2026-07-22 10:15:05', 0),
(6, 6, 9, 5, 'SUR20260722121521', 'Appendoncy', 'Mutkhada', '2026-07-24', '15:48:00', '5 Hours', 'City Hospital', 'Dr Shivatej Katkar', 'NIraj Bhute', 'Nirma', 'Minor', 'Orthopedic', 'Fever', 'golya kha ', 'ky nko khau', 'poricha nad sod', '140 ml', 'Scheduled', 'ok', '0000-00-00', 'horn ok please', '2026-07-22 10:15:58', '2026-07-22 12:50:02', 1),
(7, 6, 8, 5, 'SUR20260722121757', 'Heart Surgerys', '', '2026-07-30', '17:48:00', '4 Hours', 'City Hospital', 'Dr Ayush Nipane', '', 'Niram', 'Major', 'ENT', 'Heart', 'Heart', '', '', '', 'Scheduled', '', '0000-00-00', '', '2026-07-22 10:20:26', '2026-07-28 07:01:03', 1);

-- --------------------------------------------------------

--
-- Table structure for table `wards`
--

CREATE TABLE `wards` (
  `ward_id` int(11) NOT NULL,
  `ward_name` varchar(100) DEFAULT NULL,
  `room_no` varchar(50) DEFAULT NULL,
  `bed_no` varchar(50) DEFAULT NULL,
  `bed_type` varchar(100) DEFAULT NULL,
  `charges_per_day` decimal(10,2) DEFAULT NULL,
  `status` enum('Available','Occupied','Maintenance') DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delete_flag` tinyint(1) DEFAULT 0,
  `hospital_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wards`
--

INSERT INTO `wards` (`ward_id`, `ward_name`, `room_no`, `bed_no`, `bed_type`, `charges_per_day`, `status`, `created_at`, `modified_at`, `delete_flag`, `hospital_id`) VALUES
(1, 'general', '309', '10', 'General', 20.00, 'Available', '2026-07-03 18:01:10', '2026-07-16 03:10:08', 1, 4);

-- --------------------------------------------------------

--
-- Table structure for table `ward_master`
--

CREATE TABLE `ward_master` (
  `ward_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `ward_name` varchar(100) NOT NULL,
  `ward_type` varchar(50) DEFAULT NULL,
  `floor_no` int(11) DEFAULT NULL,
  `status` enum('Available','Occupied') DEFAULT 'Available',
  `delete_flag` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ward_master`
--

INSERT INTO `ward_master` (`ward_id`, `hospital_id`, `ward_name`, `ward_type`, `floor_no`, `status`, `delete_flag`, `created_at`, `modified_at`) VALUES
(1, 5, 'ICU', '345t\\][poiuygfv', 2456, 'Available', 0, '2026-07-20 19:06:34', '2026-07-22 12:32:38'),
(2, 6, 'General Ward', 'General', 2, 'Available', 0, '2026-07-21 07:33:12', '2026-07-21 07:33:12'),
(3, 5, 'General Ward', 'Gen-1', 2, 'Available', 0, '2026-07-23 05:11:00', '2026-07-23 05:11:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `add_events`
--
ALTER TABLE `add_events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_profile`
--
ALTER TABLE `admin_profile`
  ADD PRIMARY KEY (`admin_id`),
  ADD KEY `fk_admin_register` (`register_id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD UNIQUE KEY `appointment_no` (`appointment_no`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `hospital_id` (`hospital_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `hospital_id` (`hospital_id`),
  ADD KEY `register_id` (`register_id`);

--
-- Indexes for table `bed_allocation`
--
ALTER TABLE `bed_allocation`
  ADD PRIMARY KEY (`allocation_id`),
  ADD KEY `hospital_id` (`hospital_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `bed_id` (`bed_id`);

--
-- Indexes for table `bed_master`
--
ALTER TABLE `bed_master`
  ADD PRIMARY KEY (`bed_id`),
  ADD KEY `hospital_id` (`hospital_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `billing`
--
ALTER TABLE `billing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_billing_patient` (`patient_id`),
  ADD KEY `hospital_id` (`hospital_id`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hospital_id` (`hospital_id`);

--
-- Indexes for table `discharge_summary`
--
ALTER TABLE `discharge_summary`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `doctor`
--
ALTER TABLE `doctor`
  ADD PRIMARY KEY (`doctor_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_doctor_register` (`register_id`),
  ADD KEY `hospital_id` (`hospital_id`);

--
-- Indexes for table `email_templates`
--
ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`template_id`),
  ADD UNIQUE KEY `uk_template_name` (`template_name`);

--
-- Indexes for table `hospital_admin`
--
ALTER TABLE `hospital_admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD KEY `hospital_id` (`hospital_id`),
  ADD KEY `register_id` (`register_id`);

--
-- Indexes for table `hospital_master`
--
ALTER TABLE `hospital_master`
  ADD PRIMARY KEY (`hospital_id`);

--
-- Indexes for table `ipd_admissions`
--
ALTER TABLE `ipd_admissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ipd_patient` (`patient_id`),
  ADD KEY `fk_ipd_doctor` (`doctor_id`),
  ADD KEY `hospital_id` (`hospital_id`);

--
-- Indexes for table `ipd_treatment_master`
--
ALTER TABLE `ipd_treatment_master`
  ADD PRIMARY KEY (`treatment_master_id`),
  ADD KEY `ipd_id` (`ipd_id`);

--
-- Indexes for table `lab_bill`
--
ALTER TABLE `lab_bill`
  ADD PRIMARY KEY (`bill_id`);

--
-- Indexes for table `lab_orders`
--
ALTER TABLE `lab_orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `order_no` (`order_no`);

--
-- Indexes for table `lab_order_details`
--
ALTER TABLE `lab_order_details`
  ADD PRIMARY KEY (`detail_id`);

--
-- Indexes for table `lab_reports`
--
ALTER TABLE `lab_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD UNIQUE KEY `report_no` (`report_no`);

--
-- Indexes for table `lab_technicians`
--
ALTER TABLE `lab_technicians`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lab_tests`
--
ALTER TABLE `lab_tests`
  ADD PRIMARY KEY (`test_id`),
  ADD UNIQUE KEY `test_code` (`test_code`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `lab_test_categories`
--
ALTER TABLE `lab_test_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `lab_test_results`
--
ALTER TABLE `lab_test_results`
  ADD PRIMARY KEY (`result_id`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`login_id`),
  ADD KEY `register_id` (`register_id`),
  ADD KEY `hospital_id` (`hospital_id`);

--
-- Indexes for table `opd`
--
ALTER TABLE `opd`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `appointment_id` (`appointment_id`),
  ADD UNIQUE KEY `appointment_no` (`appointment_no`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `hospital_id` (`hospital_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD KEY `fk_patient_register` (`register_id`),
  ADD KEY `fk_patient_doctor` (`doctor_id`),
  ADD KEY `hospital_id` (`hospital_id`);

--
-- Indexes for table `patient_alerts`
--
ALTER TABLE `patient_alerts`
  ADD PRIMARY KEY (`alert_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `hospital_id` (`hospital_id`);

--
-- Indexes for table `patient_documents`
--
ALTER TABLE `patient_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_document_type` (`document_type`),
  ADD KEY `idx_document_category` (`document_category`),
  ADD KEY `idx_delete_flag` (`delete_flag`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `permission_slug` (`permission_slug`);

--
-- Indexes for table `prescription_details`
--
ALTER TABLE `prescription_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `prescription_id` (`prescription_id`);

--
-- Indexes for table `prescription_master`
--
ALTER TABLE `prescription_master`
  ADD PRIMARY KEY (`prescription_id`);

--
-- Indexes for table `register`
--
ALTER TABLE `register`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `hospital_id` (`hospital_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_slug` (`role_slug`),
  ADD KEY `idx_roles_hospital_id` (`hospital_id`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `room_master`
--
ALTER TABLE `room_master`
  ADD PRIMARY KEY (`room_id`),
  ADD KEY `hospital_id` (`hospital_id`),
  ADD KEY `ward_id` (`ward_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_staff_register` (`register_id`),
  ADD KEY `hospital_id` (`hospital_id`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`subscription_id`),
  ADD KEY `hospital_id` (`hospital_id`);

--
-- Indexes for table `surgeries`
--
ALTER TABLE `surgeries`
  ADD PRIMARY KEY (`surgery_id`),
  ADD UNIQUE KEY `surgery_no` (`surgery_no`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `hospital_id` (`hospital_id`);

--
-- Indexes for table `wards`
--
ALTER TABLE `wards`
  ADD PRIMARY KEY (`ward_id`);

--
-- Indexes for table `ward_master`
--
ALTER TABLE `ward_master`
  ADD PRIMARY KEY (`ward_id`),
  ADD KEY `hospital_id` (`hospital_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `add_events`
--
ALTER TABLE `add_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `admin_profile`
--
ALTER TABLE `admin_profile`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=835;

--
-- AUTO_INCREMENT for table `bed_allocation`
--
ALTER TABLE `bed_allocation`
  MODIFY `allocation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `bed_master`
--
ALTER TABLE `bed_master`
  MODIFY `bed_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `billing`
--
ALTER TABLE `billing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `discharge_summary`
--
ALTER TABLE `discharge_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor`
--
ALTER TABLE `doctor`
  MODIFY `doctor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `email_templates`
--
ALTER TABLE `email_templates`
  MODIFY `template_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `hospital_admin`
--
ALTER TABLE `hospital_admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `hospital_master`
--
ALTER TABLE `hospital_master`
  MODIFY `hospital_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `ipd_admissions`
--
ALTER TABLE `ipd_admissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ipd_treatment_master`
--
ALTER TABLE `ipd_treatment_master`
  MODIFY `treatment_master_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_bill`
--
ALTER TABLE `lab_bill`
  MODIFY `bill_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `lab_orders`
--
ALTER TABLE `lab_orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `lab_order_details`
--
ALTER TABLE `lab_order_details`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `lab_reports`
--
ALTER TABLE `lab_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `lab_technicians`
--
ALTER TABLE `lab_technicians`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `lab_tests`
--
ALTER TABLE `lab_tests`
  MODIFY `test_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `lab_test_categories`
--
ALTER TABLE `lab_test_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `lab_test_results`
--
ALTER TABLE `lab_test_results`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `login_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `opd`
--
ALTER TABLE `opd`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `patient_alerts`
--
ALTER TABLE `patient_alerts`
  MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `patient_documents`
--
ALTER TABLE `patient_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=208;

--
-- AUTO_INCREMENT for table `prescription_details`
--
ALTER TABLE `prescription_details`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `prescription_master`
--
ALTER TABLE `prescription_master`
  MODIFY `prescription_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `register`
--
ALTER TABLE `register`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1072;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14809;

--
-- AUTO_INCREMENT for table `room_master`
--
ALTER TABLE `room_master`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `subscription_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `surgeries`
--
ALTER TABLE `surgeries`
  MODIFY `surgery_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `wards`
--
ALTER TABLE `wards`
  MODIFY `ward_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ward_master`
--
ALTER TABLE `ward_master`
  MODIFY `ward_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_profile`
--
ALTER TABLE `admin_profile`
  ADD CONSTRAINT `fk_admin_register` FOREIGN KEY (`register_id`) REFERENCES `register` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`doctor_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appointments_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospital_master` (`hospital_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `bed_allocation`
--
ALTER TABLE `bed_allocation`
  ADD CONSTRAINT `fk_allocation_bed` FOREIGN KEY (`bed_id`) REFERENCES `bed_master` (`bed_id`) ON DELETE CASCADE;

--
-- Constraints for table `bed_master`
--
ALTER TABLE `bed_master`
  ADD CONSTRAINT `fk_bed_room` FOREIGN KEY (`room_id`) REFERENCES `room_master` (`room_id`) ON DELETE CASCADE;

--
-- Constraints for table `billing`
--
ALTER TABLE `billing`
  ADD CONSTRAINT `fk_billing_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospital_master` (`hospital_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_billing_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `department`
--
ALTER TABLE `department`
  ADD CONSTRAINT `fk_department_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospital_master` (`hospital_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `doctor`
--
ALTER TABLE `doctor`
  ADD CONSTRAINT `fk_doctor_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospital_master` (`hospital_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_doctor_register` FOREIGN KEY (`register_id`) REFERENCES `register` (`id`);

--
-- Constraints for table `hospital_admin`
--
ALTER TABLE `hospital_admin`
  ADD CONSTRAINT `fk_hospital_admin_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospital_master` (`hospital_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hospital_admin_register` FOREIGN KEY (`register_id`) REFERENCES `register` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ipd_admissions`
--
ALTER TABLE `ipd_admissions`
  ADD CONSTRAINT `fk_ipd_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`doctor_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ipd_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospital_master` (`hospital_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ipd_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ipd_treatment_master`
--
ALTER TABLE `ipd_treatment_master`
  ADD CONSTRAINT `ipd_treatment_master_ibfk_1` FOREIGN KEY (`ipd_id`) REFERENCES `ipd_admissions` (`id`);

--
-- Constraints for table `lab_tests`
--
ALTER TABLE `lab_tests`
  ADD CONSTRAINT `lab_tests_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `lab_test_categories` (`category_id`);

--
-- Constraints for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD CONSTRAINT `fk_login_logs_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospital_master` (`hospital_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_login_logs_register` FOREIGN KEY (`register_id`) REFERENCES `register` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `opd`
--
ALTER TABLE `opd`
  ADD CONSTRAINT `fk_opd_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospital_master` (`hospital_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `opd_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `opd_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `opd_ibfk_3` FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`doctor_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `fk_patient_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`doctor_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_patient_register` FOREIGN KEY (`register_id`) REFERENCES `register` (`id`),
  ADD CONSTRAINT `fk_patients_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospital_master` (`hospital_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `patient_alerts`
--
ALTER TABLE `patient_alerts`
  ADD CONSTRAINT `patient_alerts_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `patient_alerts_ibfk_2` FOREIGN KEY (`hospital_id`) REFERENCES `hospital_master` (`hospital_id`);

--
-- Constraints for table `patient_documents`
--
ALTER TABLE `patient_documents`
  ADD CONSTRAINT `fk_patient_documents_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `patient_documents_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE;

--
-- Constraints for table `prescription_details`
--
ALTER TABLE `prescription_details`
  ADD CONSTRAINT `prescription_details_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescription_master` (`prescription_id`) ON DELETE CASCADE;

--
-- Constraints for table `register`
--
ALTER TABLE `register`
  ADD CONSTRAINT `fk_register_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospital_master` (`hospital_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_register_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `roles`
--
ALTER TABLE `roles`
  ADD CONSTRAINT `fk_roles_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospital_master` (`hospital_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `room_master`
--
ALTER TABLE `room_master`
  ADD CONSTRAINT `fk_room_ward` FOREIGN KEY (`ward_id`) REFERENCES `ward_master` (`ward_id`) ON DELETE CASCADE;

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `fk_staff_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospital_master` (`hospital_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_staff_register` FOREIGN KEY (`register_id`) REFERENCES `register` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `fk_subscription_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospital_master` (`hospital_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `surgeries`
--
ALTER TABLE `surgeries`
  ADD CONSTRAINT `fk_surgery_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`doctor_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_surgery_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospital_master` (`hospital_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_surgery_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3307
-- Generation Time: Jul 22, 2026 at 03:50 PM
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
(5, 1006, 'Super Admin', NULL, NULL, '2026-07-19 16:43:52', '2026-07-19 16:43:52', 0),
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
(6, 'APP-20260721111051-6a5f379b03d3a', 6, 8, 'Cardiology', 'Check-up', 'OPD', '2026-07-20', '12:00:00', '15', 'Cough', 'Confirmed', '', '2026-07-22 09:11:17', '2026-07-22 12:39:27', 0, 5),
(7, 'APP-20260722141708-6a60b4c46ab5b', 6, 9, 'Cardiology', 'Follow-up', 'IPD', '2026-07-22', '05:00:00', '15', 'Fever', 'Confirmed', '', '2026-07-22 12:18:23', '2026-07-22 12:18:23', 0, 5),
(8, 'APP-20260722142003-6a60b573b3c16', 5, 9, 'Cardiology', 'Consultation', 'IPD', '2026-07-22', '04:00:00', '15', 'Fever', 'Confirmed', '', '2026-07-22 12:22:32', '2026-07-22 12:22:32', 0, 5),
(9, 'APP-20260722142610-6a60b6e257693', 12, 8, 'Cardiology', 'Follow-up', 'IPD', '2026-07-22', '02:30:00', '15', 'Cough', 'Confirmed', '', '2026-07-22 12:26:48', '2026-07-22 12:26:48', 0, 5),
(10, 'APP-20260722153531-6a60c7233209c', 6, 8, 'Cardiology', 'Check-up', 'OPD', '2026-07-22', '11:00:00', '15', 'Fever', 'Scheduled', '', '2026-07-22 13:36:31', '2026-07-22 13:37:51', 1, 5);

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
(338, NULL, 1006, 'Super Admin', 'Super Admin', 'Dashboard', 'Super Admin accessed dashboard', 'Dashboard', 'Super Admin accessed dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Chrome', '2026-07-22 13:46:42', 0);

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

--
-- Dumping data for table `bed_allocation`
--

INSERT INTO `bed_allocation` (`allocation_id`, `hospital_id`, `patient_id`, `bed_id`, `admit_date`, `discharge_date`, `status`, `created_at`, `modified_at`) VALUES
(1, 5, 23, 1, '2026-07-21 11:26:53', NULL, 'Occupied', '2026-07-21 09:26:53', '2026-07-21 09:26:53'),
(2, 0, 6, 1, '2026-07-22 17:48:23', NULL, 'Occupied', '2026-07-22 12:18:23', '2026-07-22 12:18:23'),
(3, 0, 5, 1, '2026-07-22 17:52:32', NULL, 'Occupied', '2026-07-22 12:22:32', '2026-07-22 12:22:32'),
(4, 5, 12, 2, '2026-07-22 17:56:48', NULL, 'Occupied', '2026-07-22 12:26:48', '2026-07-22 12:26:48');

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
(1, 5, 1, 'BED-1', 'ICU', 'Available', 0, '2026-07-21 07:53:30', '2026-07-22 12:59:01'),
(2, 5, 1, 'Bed-12', 'ICU', 'Occupied', 0, '2026-07-22 12:07:54', '2026-07-22 12:26:48');

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
(6, 'Cardiology', 'sdfv', 'Active', '2026-07-20 19:02:52', '2026-07-22 07:44:26', 0, 5);

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
(5, 1015, 'Chaitanya Patil', '', '', 'wchaitanyapatil@gmail.com', 'Pediatricss', '', '', 0, 0.00, '', 'Powai Naka', 'Active', '2026-07-20 13:08:12', '2026-07-20 13:08:12', 0, 6),
(8, 1028, 'Dr Ayush Nipane', '', '', 'ayushhnipane@gmail.com', 'Cardiology', '', '', 0, 0.00, '', '', 'Active', '2026-07-21 06:45:02', '2026-07-21 06:45:02', 0, 5),
(9, 1029, 'Dr Shivatej Katkar', '', '4654617845', 'shivatejk033@gmail.com', 'Cardiology', 'hvh', 'Btech', 5, 5609.00, '', '', 'Active', '2026-07-21 06:51:12', '2026-07-22 12:12:12', 0, 5);

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
(2, 'reset_password', 'Ultra Hospital - Password Reset Request', 'Dear {user_name},\r\n\r\nWe received a request to reset your Ultra Hospital account password.\r\n\r\nYour One-Time Password (OTP) is:\r\n\r\n{otp}\r\n\r\nThis OTP is valid for {expiry_time} minutes.\r\n\r\nIf you did not request a password reset, please ignore this email or contact the hospital administrator immediately.\r\n\r\nRegards,\r\nUltra Hospital Team', 'Active', '2026-07-16 02:26:36', '2026-07-16 02:26:36', 0),
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
(6, 'Kadam Hospital', 'EFRKGZ', 'documents/hospital/guide.jpeg', 'Multi-Speciality', '', '', 'NA', 'Satara', 'Maharashtra', 'India', '415019', '', '9876543212', '', 'Active', '2026-07-20 07:36:19', '2026-07-20 11:54:45', 0, ''),
(7, 'Saygaonkar Hospital', 'CW74RP', '', 'Multi-Speciality', '', '', '', 'Pune', 'Maharashtra', 'India', '', NULL, '7058094949', '', 'Active', '2026-07-20 11:49:54', '2026-07-20 11:49:54', 0, NULL),
(9, 'Satara Ruby', 'ZSQRZQ', '', 'Multi-Speciality', '', '', '', 'awsdfghb', 'asdfgb', 'India', '123456', NULL, '1234567890', '', 'Active', '2026-07-20 15:09:44', '2026-07-20 15:09:44', 0, NULL);

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
(1, 'IPD-20260722-7033', 7, 'Follow-up', 6, 9, 'Cardiology', 1, '', 'BED-1', '2026-07-22', '05:00:00', '15', 'Fever', '', '', '', '', '', '', '', '', '', '', '', '', 'Admitted', '2026-07-22 12:18:23', '2026-07-22 12:18:23', 0, 5),
(2, 'IPD-20260722-7552', 8, 'Consultation', 5, 9, 'Cardiology', 1, '', 'BED-1', '2026-07-22', '04:00:00', '15', 'Fever', '', '', '', '', '', '', '', '', '', '', '', '', 'Admitted', '2026-07-22 12:22:32', '2026-07-22 12:22:32', 0, 5),
(3, 'IPD-20260722-2873', 9, 'Follow-up', 12, 8, 'Cardiology', 1, '', 'Bed-12', '2026-07-22', '02:30:00', '15', 'Cough', '', '', '', '', '', '', '', '', '', '', '', '', 'Admitted', '2026-07-22 12:26:48', '2026-07-22 12:26:48', 0, 5);

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

INSERT INTO `lab_orders` (`order_id`, `order_no`, `patient_id`, `doctor_id`, `hospital_id`, `order_date`, `total_amount`, `clinical_notes`, `payment_status`, `order_status`, `technician_id`, `remarks`, `created_by`, `updated_by`, `created_at`, `updated_at`, `delete_flag`) VALUES
(6, 'LAB202607220001', 28, 1029, 5, '2026-07-22', 100.00, '', 'Pending', 'Assigned', 11, NULL, 1029, NULL, '2026-07-22 11:30:54', '2026-07-22 11:30:54', 0);

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_order_details`
--

INSERT INTO `lab_order_details` (`detail_id`, `order_id`, `test_id`, `price`, `report_status`, `created_at`) VALUES
(39, 6, 7, 100.00, 'Pending', '2026-07-22 11:30:54');

-- --------------------------------------------------------

--
-- Table structure for table `lab_reports`
--

CREATE TABLE `lab_reports` (
  `report_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_technician`
--

CREATE TABLE `lab_technician` (
  `technician_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(7, 6, 'LAB0001', 'cbc', 100.00, '', '', '', '', 'Active', 5, 1008, NULL, '2026-07-22 11:24:21', '2026-07-22 13:20:54', 1),
(8, 6, 'LAB0008', 'cbct', 123.00, '', '', '', '', 'Active', 5, 1008, 1008, '2026-07-22 13:21:18', '2026-07-22 13:39:19', 0);

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
(6, 'blood', '', 'Active', 5, 1008, NULL, '2026-07-22 11:15:37', '2026-07-22 11:15:37', 0);

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
(277, 1006, NULL, '2026-07-19 21:58:06', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(278, 1006, NULL, '2026-07-19 21:59:43', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(281, 1006, NULL, '2026-07-19 22:02:07', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(284, 1006, NULL, '2026-07-19 22:02:42', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(285, 1006, NULL, '2026-07-19 22:13:50', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(286, 1006, NULL, '2026-07-19 22:14:16', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(287, 1006, NULL, '2026-07-19 22:15:19', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(288, 1006, NULL, '2026-07-19 22:15:41', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(289, 1006, NULL, '2026-07-19 22:16:05', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(290, 1006, NULL, '2026-07-19 22:16:13', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(291, 1006, NULL, '2026-07-19 22:16:39', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(292, 1006, NULL, '2026-07-19 22:17:07', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(293, 1006, NULL, '2026-07-19 22:17:38', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(297, 1006, NULL, '2026-07-20 10:17:34', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(301, 1006, NULL, '2026-07-20 10:27:41', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(303, 1006, NULL, '2026-07-20 10:30:04', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(304, 1006, NULL, '2026-07-20 10:30:11', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(305, 1006, NULL, '2026-07-20 10:46:23', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(309, 1006, NULL, '2026-07-20 10:59:33', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(310, 1006, NULL, '2026-07-20 11:26:01', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(311, 1008, 5, '2026-07-20 11:29:40', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(312, 1006, NULL, '2026-07-20 11:30:11', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(313, 1008, 5, '2026-07-20 11:31:43', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(314, 1008, 5, '2026-07-20 11:38:05', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(315, 1006, NULL, '2026-07-20 11:39:19', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(316, 1008, 5, '2026-07-20 11:40:40', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(317, 1008, 5, '2026-07-20 11:42:15', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(318, 1006, NULL, '2026-07-20 11:42:39', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(319, 1008, 5, '2026-07-20 11:45:55', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(320, 1006, NULL, '2026-07-20 11:46:44', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(322, 1008, 5, '2026-07-20 12:03:01', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(323, 1006, NULL, '2026-07-20 12:03:33', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(324, 1008, 5, '2026-07-20 12:04:00', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(325, 1008, 5, '2026-07-20 12:20:10', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(326, 1006, NULL, '2026-07-20 12:20:26', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(328, 1006, NULL, '2026-07-20 12:27:35', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(329, 1008, 5, '2026-07-20 12:28:05', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(330, 1006, NULL, '2026-07-20 12:28:36', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(331, 1006, NULL, '2026-07-20 12:28:45', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(332, 1008, 5, '2026-07-20 12:36:25', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(333, 1008, 5, '2026-07-20 12:45:47', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(334, 1006, NULL, '2026-07-20 12:46:05', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(335, 1008, 5, '2026-07-20 12:46:45', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(336, 1006, NULL, '2026-07-20 12:46:54', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(337, 1008, 5, '2026-07-20 12:47:17', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(338, 1006, NULL, '2026-07-20 12:47:37', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(339, 1008, 5, '2026-07-20 12:58:02', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(340, 1008, 5, '2026-07-20 12:58:18', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(341, 1008, 5, '2026-07-20 12:58:45', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(342, 1006, NULL, '2026-07-20 12:59:02', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(343, 1008, 5, '2026-07-20 13:00:00', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(344, 1008, 5, '2026-07-20 13:00:08', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(345, 1008, 5, '2026-07-20 13:00:39', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(346, 1008, 5, '2026-07-20 13:00:58', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(347, 1008, 5, '2026-07-20 13:01:38', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(348, 1006, NULL, '2026-07-20 13:04:53', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(349, 1009, 6, '2026-07-20 13:06:48', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(350, 1009, 6, '2026-07-20 13:07:18', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(351, 1009, 6, '2026-07-20 13:07:44', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(352, 1009, 6, '2026-07-20 13:13:50', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(353, 1006, NULL, '2026-07-20 13:14:13', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(354, 1009, 6, '2026-07-20 13:14:48', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(355, 1008, 5, '2026-07-20 13:15:07', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(356, 1006, NULL, '2026-07-20 13:15:40', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(357, 1008, 5, '2026-07-20 13:16:05', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(358, 1009, 6, '2026-07-20 13:16:59', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(359, 1006, NULL, '2026-07-20 13:17:22', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(360, 1009, 6, '2026-07-20 13:18:25', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(361, 1009, 6, '2026-07-20 13:18:53', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(362, 1008, 5, '2026-07-20 13:19:15', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(363, 1009, 6, '2026-07-20 13:21:49', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(364, 1008, 5, '2026-07-20 13:22:08', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(365, 1008, 5, '2026-07-20 13:22:23', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(366, 1008, 5, '2026-07-20 15:47:12', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(367, 1006, NULL, '2026-07-20 15:47:38', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(368, 1006, NULL, '2026-07-20 15:48:26', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(369, 1006, NULL, '2026-07-20 15:51:59', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(371, 1006, NULL, '2026-07-20 15:54:24', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(372, 1008, 5, '2026-07-20 15:58:55', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(373, 1009, 6, '2026-07-20 15:59:32', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(374, 1008, 5, '2026-07-20 16:02:55', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(375, 1009, 6, '2026-07-20 16:04:36', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(376, 1009, 6, '2026-07-20 16:14:29', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(378, 1008, 5, '2026-07-20 16:46:06', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(379, 1009, 6, '2026-07-20 16:46:16', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(380, 1009, 6, '2026-07-20 16:53:24', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(381, 1009, 6, '2026-07-20 16:53:55', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(382, 1009, 6, '2026-07-20 16:54:36', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(383, 1006, NULL, '2026-07-20 17:01:24', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(384, 1008, 5, '2026-07-20 17:01:49', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(385, 1009, 6, '2026-07-20 17:01:58', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(386, 1006, NULL, '2026-07-20 17:02:57', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(387, 1008, 5, '2026-07-20 17:03:43', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(388, 1008, 5, '2026-07-20 17:06:39', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(389, 1009, 6, '2026-07-20 17:06:50', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(390, 1009, 6, '2026-07-20 17:07:03', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(391, 1009, 6, '2026-07-20 17:08:22', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(392, 1006, NULL, '2026-07-20 17:12:34', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(393, 1006, NULL, '2026-07-20 17:14:25', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(394, 1006, NULL, '2026-07-20 17:18:04', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(395, 1008, 5, '2026-07-20 17:18:15', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(396, 1008, 5, '2026-07-20 17:20:52', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(397, 1011, 7, '2026-07-20 17:21:33', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(398, 1011, 7, '2026-07-20 17:22:45', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(399, 1009, 6, '2026-07-20 17:24:30', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(400, 1008, 5, '2026-07-20 17:51:59', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(401, 1011, 7, '2026-07-20 18:17:51', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(402, 1006, NULL, '2026-07-20 18:22:14', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(403, 1008, 5, '2026-07-20 18:27:03', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(404, 1006, NULL, '2026-07-20 18:27:51', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(405, 1011, 7, '2026-07-20 18:28:52', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(406, 1009, 6, '2026-07-20 18:31:10', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(407, 1008, 5, '2026-07-20 18:41:31', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(408, 1008, 5, '2026-07-20 19:11:10', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(409, 1006, NULL, '2026-07-20 20:14:56', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(414, 1008, 5, '2026-07-20 21:57:20', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(415, 1009, 6, '2026-07-20 21:57:29', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(416, 1009, 6, '2026-07-20 21:59:15', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(418, 1008, 5, '2026-07-20 22:16:01', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(419, 1006, NULL, '2026-07-20 22:28:38', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(420, 1008, 5, '2026-07-20 22:36:03', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(421, 1006, NULL, '2026-07-20 22:45:40', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(422, 1009, 6, '2026-07-20 22:51:57', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(423, 1017, NULL, '2026-07-20 23:26:35', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(424, 1006, NULL, '2026-07-20 23:27:22', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(427, 1009, 6, '2026-07-21 00:06:37', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(429, 1008, 5, '2026-07-21 00:13:19', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(430, 1017, NULL, '2026-07-21 00:18:20', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(431, 1017, 7, '2026-07-21 00:19:37', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(432, 1022, 5, '2026-07-21 00:30:25', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(433, 1008, 5, '2026-07-21 00:30:52', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(434, 1008, 5, '2026-07-21 08:20:13', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(435, 1006, NULL, '2026-07-21 08:20:51', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(436, 1009, 6, '2026-07-21 08:25:42', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(437, 1021, 5, '2026-07-21 09:07:40', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(438, 1008, 5, '2026-07-21 09:07:56', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(439, 1008, 5, '2026-07-21 09:40:53', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(440, 1006, NULL, '2026-07-21 10:15:44', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(441, 1008, 5, '2026-07-21 10:19:56', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(442, 1008, 5, '2026-07-21 10:22:50', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(443, 1008, 5, '2026-07-21 10:41:07', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(444, 1009, 6, '2026-07-21 10:56:59', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(445, 1009, 6, '2026-07-21 11:02:11', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(446, 1009, 6, '2026-07-21 11:05:47', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(447, 1008, 5, '2026-07-21 11:06:21', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(448, 1008, 5, '2026-07-21 12:08:37', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(449, 1008, 5, '2026-07-21 12:23:51', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(450, 1009, 6, '2026-07-21 12:24:06', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(451, 1009, 6, '2026-07-21 12:24:52', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(452, 1008, 5, '2026-07-21 12:38:20', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(453, 1008, 5, '2026-07-21 12:55:19', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(454, 1008, 5, '2026-07-21 12:56:03', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(455, 1028, 5, '2026-07-21 12:56:45', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(456, 1028, 5, '2026-07-21 13:01:12', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(457, 1028, 5, '2026-07-21 13:02:38', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(458, 1028, 5, '2026-07-21 13:03:04', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(459, 1028, 5, '2026-07-21 13:03:20', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(460, 1028, 5, '2026-07-21 13:05:08', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(461, 1028, 5, '2026-07-21 13:06:58', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(462, 1028, 5, '2026-07-21 13:07:12', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(463, 1008, 5, '2026-07-21 13:08:14', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(464, 1008, 5, '2026-07-21 13:08:31', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(465, 1028, 5, '2026-07-21 13:09:20', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(466, 1028, 5, '2026-07-21 13:10:20', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(467, 1028, 5, '2026-07-21 13:12:26', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(468, 1028, 5, '2026-07-21 13:12:55', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(469, 1028, 5, '2026-07-21 13:13:23', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(470, 1028, 5, '2026-07-21 13:14:44', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(471, 1028, 5, '2026-07-21 13:15:29', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(472, 1028, 5, '2026-07-21 13:16:26', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(473, 1028, 5, '2026-07-21 13:18:17', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(474, 1028, 5, '2026-07-21 13:18:35', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(475, 1028, 5, '2026-07-21 13:20:34', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(476, 1028, 5, '2026-07-21 13:20:51', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(477, 1028, 5, '2026-07-21 13:25:32', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(478, 1028, 5, '2026-07-21 13:31:07', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(479, 1028, 5, '2026-07-21 13:32:35', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(480, 1028, 5, '2026-07-21 13:36:17', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(481, 1028, 5, '2026-07-21 13:39:53', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(482, 1028, 5, '2026-07-21 13:41:14', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(483, 1028, 5, '2026-07-21 13:41:47', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(484, 1008, 5, '2026-07-21 14:40:29', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(485, 1008, 5, '2026-07-21 14:40:41', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(486, 1028, 5, '2026-07-21 14:45:02', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(487, 1028, 5, '2026-07-21 14:46:16', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(488, 1028, 5, '2026-07-21 14:54:27', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(489, 1028, 5, '2026-07-21 14:56:55', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(490, 1008, 5, '2026-07-21 14:57:45', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(491, 1028, 5, '2026-07-21 15:13:41', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(492, 1008, 5, '2026-07-21 15:14:59', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(493, 1008, 5, '2026-07-21 15:27:18', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(494, 1040, 5, '2026-07-21 15:39:00', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(495, 1040, 5, '2026-07-21 15:40:43', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(496, 1040, 5, '2026-07-21 15:43:15', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(497, 1040, 5, '2026-07-21 15:51:22', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(498, 1028, 5, '2026-07-21 15:54:39', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(499, 1040, 5, '2026-07-21 15:55:53', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(500, 1040, 5, '2026-07-21 15:56:15', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(501, 1028, 5, '2026-07-21 15:56:29', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(502, 1028, 5, '2026-07-21 15:57:06', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(503, 1028, 5, '2026-07-21 15:57:25', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(504, 1040, 5, '2026-07-21 15:57:44', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(505, 1040, 5, '2026-07-21 16:12:23', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(506, 1040, 5, '2026-07-21 16:12:31', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(507, 1008, 5, '2026-07-21 16:14:33', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(508, 1006, NULL, '2026-07-21 16:19:02', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(509, 1008, 5, '2026-07-21 16:22:33', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(510, 1008, 5, '2026-07-21 16:30:27', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(511, 1008, 5, '2026-07-21 16:33:53', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(512, 1008, 5, '2026-07-21 16:35:32', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(513, 1040, 5, '2026-07-21 16:51:07', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(514, 1040, 5, '2026-07-21 16:59:05', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(515, 1008, 5, '2026-07-21 16:59:25', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(516, 1008, 5, '2026-07-21 17:14:12', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(517, 1006, NULL, '2026-07-21 17:34:14', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(518, 1008, 5, '2026-07-21 17:36:14', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(519, 1008, 5, '2026-07-22 09:57:22', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(520, 1008, 5, '2026-07-22 10:20:17', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(521, 1006, NULL, '2026-07-22 10:42:46', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(522, 1029, 5, '2026-07-22 10:49:29', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(523, 1040, 5, '2026-07-22 10:50:31', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(524, 1029, 5, '2026-07-22 10:50:44', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(525, 1006, NULL, '2026-07-22 10:51:19', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(526, 1008, 5, '2026-07-22 10:54:09', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(527, 1006, NULL, '2026-07-22 10:54:19', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(528, 1040, 5, '2026-07-22 10:54:36', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(529, 1008, 5, '2026-07-22 10:56:24', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(530, 1029, 5, '2026-07-22 10:56:30', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(531, 1029, 5, '2026-07-22 10:59:11', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(532, 1006, NULL, '2026-07-22 10:59:29', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(533, 1029, 5, '2026-07-22 11:00:01', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(534, 1045, 5, '2026-07-22 11:03:41', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(535, 1008, 5, '2026-07-22 11:15:53', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(536, 1008, 5, '2026-07-22 11:17:24', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(537, 1045, 5, '2026-07-22 11:20:14', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(538, 1045, 5, '2026-07-22 11:20:14', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(539, 1008, 5, '2026-07-22 11:20:49', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(540, 1008, 5, '2026-07-22 11:22:11', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(541, 1008, 5, '2026-07-22 11:23:09', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(542, 1040, 5, '2026-07-22 11:56:28', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(543, 1040, 5, '2026-07-22 11:56:32', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'Desktop'),
(544, 1009, 6, '2026-07-22 11:58:27', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(545, 1008, 5, '2026-07-22 12:08:32', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(546, 1008, 5, '2026-07-22 12:08:43', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(547, 1008, 5, '2026-07-22 12:08:52', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(548, 1008, 5, '2026-07-22 12:08:57', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(549, 1008, 5, '2026-07-22 12:09:08', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(550, 1008, 5, '2026-07-22 12:09:10', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(551, 1008, 5, '2026-07-22 12:09:23', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(552, 1008, 5, '2026-07-22 12:09:46', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(553, 1040, 5, '2026-07-22 12:09:56', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(554, 1008, 5, '2026-07-22 12:10:28', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(555, 1008, 5, '2026-07-22 12:10:38', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(556, 1008, 5, '2026-07-22 12:10:46', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(557, 1008, 5, '2026-07-22 12:10:48', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(558, 1008, 5, '2026-07-22 12:11:15', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(559, 1008, 5, '2026-07-22 12:12:13', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(560, 1008, 5, '2026-07-22 12:28:26', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(561, 1008, 5, '2026-07-22 13:23:20', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(562, 1008, 5, '2026-07-22 14:19:32', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(563, 1008, 5, '2026-07-22 14:20:01', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(564, 1008, 5, '2026-07-22 14:33:20', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(565, 1029, 5, '2026-07-22 14:35:26', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(566, 1008, 5, '2026-07-22 14:37:01', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(567, 1008, 5, '2026-07-22 14:40:54', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(568, 1008, 5, '2026-07-22 14:41:00', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(569, 1029, 5, '2026-07-22 14:41:05', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(570, 1029, 5, '2026-07-22 14:42:00', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(571, 1029, 5, '2026-07-22 14:43:31', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(572, 1029, 5, '2026-07-22 14:45:27', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(573, 1008, 5, '2026-07-22 14:53:33', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(574, 1008, 5, '2026-07-22 15:10:30', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(575, 1008, 5, '2026-07-22 16:20:04', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(576, 1006, NULL, '2026-07-22 16:50:17', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop');
INSERT INTO `login_logs` (`login_id`, `register_id`, `hospital_id`, `login_time`, `logout_time`, `ip_address`, `browser`, `device`) VALUES
(577, 1008, 5, '2026-07-22 16:55:23', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(578, 1008, 5, '2026-07-22 16:57:39', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(579, 1008, 5, '2026-07-22 17:34:15', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(580, 1008, 5, '2026-07-22 18:10:20', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(581, 1008, 5, '2026-07-22 18:10:27', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(582, 1008, 5, '2026-07-22 18:16:23', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(583, 1008, 5, '2026-07-22 18:16:42', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(584, 1009, 6, '2026-07-22 18:47:58', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(585, 1008, 5, '2026-07-22 18:49:08', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(586, 1008, 5, '2026-07-22 18:50:32', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(587, 1008, 5, '2026-07-22 18:57:24', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(588, 1006, NULL, '2026-07-22 19:15:28', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop'),
(589, 1008, 5, '2026-07-22 19:18:23', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'Desktop');

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
(4, 1012, NULL, 'Shiva Tevar', 'documents/patients/images/scanner1.jpeg', '2026-07-01', 23, '', 'Male', '', '', '', '', 'Active', 'shiv@gmail.com', '', '2026-07-20 11:55:05', '2026-07-20 12:40:15', 0, 6, 'OPD', NULL, NULL, NULL, NULL, NULL, NULL),
(5, 1022, NULL, 'Eshwar Pawar', '', '0000-00-00', 85, '', 'Male', '', '', '', '', 'Active', 'rohankurne16@gmail.com', '', '2026-07-20 18:59:58', '2026-07-21 09:28:49', 0, 5, 'OPD', NULL, NULL, NULL, NULL, NULL, NULL),
(6, 1023, NULL, 'Niraj Bhute', 'documents/patients/images/1784726240_images.jpg', '2026-07-10', 23, 'A+', 'Male', 'Jakatwadi, Satara', '7523452345', 'Cough, Fever', 'Smoke, Running', 'Active', 'nirajbhute3@gmail.com', '911234567890', '2026-07-21 06:00:52', '2026-07-22 13:17:21', 0, 5, 'Call', NULL, NULL, NULL, NULL, NULL, NULL),
(12, 1024, NULL, 'Ayur Machhle', 'documents/patients/images/1784726180_user.png', '0000-00-00', 0, '', 'Female', '', '', '', '', 'Active', 'ayur@gmail.com', '', '2026-07-21 06:07:26', '2026-07-22 13:16:20', 0, 5, 'Call', NULL, NULL, NULL, NULL, NULL, NULL),
(13, 1025, NULL, 'Tejas Danawale', '', '0000-00-00', 0, '', '', '', '', '', '', 'Active', 'tejas@gmail.com', '', '2026-07-21 06:08:15', '2026-07-21 06:08:15', 0, 5, 'OPD', NULL, NULL, NULL, NULL, NULL, NULL),
(23, 1039, NULL, 'Rohan Kurne', '', '0000-00-00', 0, '', '', '', '', '', '', 'Active', 'rohankurne125@gmail.com', '', '2026-07-21 09:26:53', '2026-07-21 11:50:36', 0, 5, 'Referral', NULL, 'Sai Amrut', NULL, NULL, NULL, 'Disease'),
(24, 1041, NULL, 'Rohan Khade', '', '0000-00-00', 0, '', '', '', '', '', '', 'Active', 'rohankurne1256@gmail.com', '', '2026-07-21 10:17:52', '2026-07-21 10:17:52', 0, 5, 'OPD', 'Civil Hospital', NULL, NULL, NULL, NULL, NULL),
(25, 1042, NULL, 'Chaitanya Patil ', '', '0000-00-00', 0, '', 'Other', 'Ambedare, Dhanawadewadi , Satara', '', '', '', 'Active', 'chaitanyapatil@gmail.com', '', '2026-07-21 11:36:28', '2026-07-22 04:35:36', 0, 5, 'IPD', '', NULL, 'Dr Raghav Shastri', 'Sawarkar Hospital', 'Cant do in our hospital', NULL),
(26, 1043, NULL, 'Ram Kakade', '', '0000-00-00', 0, '', 'Male', '', '', 'Ajari , Sardi, Taap', 'Corona, Viral Disease', 'Inactive', 'rohankurne1246@gmail.com', '', '2026-07-21 11:48:27', '2026-07-22 13:39:56', 0, 5, 'Referral', '', NULL, 'Magna Karta', 'mangodb', 'yz doctor', NULL),
(27, 1044, 8, 'Harshad Nikam', 'documents/patients/images/1784726220_ChatGPTImageJun5202612_52_55PM.png', '2026-07-15', 0, 'B+', 'Male', 'Karad', '1234567890', '', '', 'Active', 'pratikkadam1620@gmail.com', '', '2026-07-22 05:29:24', '2026-07-22 13:17:00', 0, 5, 'OPD', NULL, NULL, NULL, NULL, NULL, NULL),
(28, 1045, NULL, 'Pratik Kadam', 'documents/patients/images/1784726143_ayushphoto.jpg', '0000-00-00', 0, '', 'Male', '', '', 'asdfg,awerfh', 'qwert,qwerty', 'Active', 'pratiksitsolutions@gmail.com', '', '2026-07-22 05:32:20', '2026-07-22 13:15:43', 0, 5, 'OPD', NULL, NULL, NULL, NULL, NULL, NULL),
(29, 1046, NULL, 'Rohan Kurne', 'documents/patients/images/1784726121_rahul2.jpg', '0000-00-00', 120, '', 'Male', '', '', 'Diabetes, Opus', 'Diabetic, Corona, Touch Screen', 'Active', 'rohankurq234ne12@gmail.com', '9876543252', '2026-07-22 06:26:24', '2026-07-22 13:15:21', 0, 5, 'Call', 'Civil Hospital', NULL, NULL, NULL, NULL, NULL),
(30, 1047, NULL, 'dfgh', '', '0000-00-00', 0, '', '', '', '', '', '', 'Active', 'rohank@gmail.com', '', '2026-07-22 06:50:48', '2026-07-22 06:51:23', 1, 5, 'OPD', NULL, NULL, NULL, NULL, NULL, NULL);

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
(14, 29, 5, 'Allergy', 'Diabetic, Corona, Touch Screen', 'Medium', 'Active', 'Admin', '2026-07-22 13:15:21', '2026-07-22 13:15:21', 0),
(15, 29, 5, 'Medical History', 'Diabetes, Opus', 'Medium', 'Active', 'Admin', '2026-07-22 13:15:21', '2026-07-22 13:15:21', 0),
(16, 28, 5, 'Allergy', 'qwert,qwerty', 'Medium', 'Active', 'Admin', '2026-07-22 13:15:43', '2026-07-22 13:15:43', 0),
(17, 28, 5, 'Medical History', 'asdfg,awerfh', 'Medium', 'Active', 'Admin', '2026-07-22 13:15:43', '2026-07-22 13:15:43', 0),
(20, 6, 5, 'Allergy', 'Smoke, Running', 'Medium', 'Active', 'Admin', '2026-07-22 13:17:21', '2026-07-22 13:17:21', 0),
(21, 6, 5, 'Medical History', 'Cough, Fever', 'Medium', 'Active', 'Admin', '2026-07-22 13:17:21', '2026-07-22 13:17:21', 0),
(24, 26, 5, 'Allergy', 'Corona, Viral Disease', 'Medium', 'Active', 'Admin', '2026-07-22 13:39:56', '2026-07-22 13:39:56', 0),
(25, 26, 5, 'Medical History', 'Ajari , Sardi, Taap', 'Medium', 'Active', 'Admin', '2026-07-22 13:39:56', '2026-07-22 13:39:56', 0);

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
(3, 6, 'report', 'Pre-Operation', 'Pre-Operation', NULL, 'uploads/documents/1784635752_Add Apointment1.png', '91.28 KB', 1008, '', NULL, 0, NULL, NULL, '2026-07-21', '2026-07-21 12:09:12', '2026-07-22 07:32:25', 0),
(4, 6, 'sdfghjkl', 'Post-Operation', 'OT', NULL, 'uploads/documents/1784635829_Patient Registration.png', '63.54 KB', 1008, '', NULL, 0, NULL, NULL, '2026-07-21', '2026-07-21 12:10:29', '2026-07-22 07:32:30', 0),
(5, 6, 'asddasASass', 'Pre-Operation', 'Pre-Operation', NULL, 'uploads/documents/1784636118_Add Department.png', '123.63 KB', 1008, '', NULL, 0, NULL, NULL, '2026-07-21', '2026-07-21 12:15:18', '2026-07-22 07:32:33', 0),
(6, 6, 'asddasASass', 'Pre-Operation', 'Pre-Operation', NULL, 'uploads/documents/1784636281_Department Master.png', '174.73 KB', 1008, '', NULL, 0, NULL, NULL, '2026-07-21', '2026-07-21 12:18:01', '2026-07-22 07:32:37', 0),
(7, 6, 'assq', 'Post-Operation', 'OT', NULL, 'uploads/documents/1784636309_Doctors.png', '181.47 KB', 1008, '', NULL, 0, NULL, NULL, '2026-07-21', '2026-07-21 12:18:29', '2026-07-22 07:32:41', 0),
(8, 6, 'q', 'Post-Operation', 'OT', NULL, 'uploads/documents/1784636377_Patient Registration.png', '63.54 KB', 1008, '', NULL, 0, NULL, NULL, '2026-07-21', '2026-07-21 12:19:37', '2026-07-22 07:32:48', 0);

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
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `medicine_name` varchar(150) NOT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `frequency` varchar(100) DEFAULT NULL,
  `days` int(11) DEFAULT NULL,
  `timing` varchar(100) DEFAULT NULL,
  `advice` text DEFAULT NULL,
  `followup_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delete_flag` tinyint(1) DEFAULT 0,
  `hospital_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`id`, `patient_id`, `doctor_id`, `medicine_name`, `dosage`, `frequency`, `days`, `timing`, `advice`, `followup_date`, `created_at`, `modified_at`, `delete_flag`, `hospital_id`) VALUES
(3, 6, 8, 'Paracetomol', '1 Tablet', 'Dails', 5, '1-1-1', 'Take dosage regularly', '2026-07-31', '2026-07-22 07:16:03', '2026-07-22 07:16:03', 0, 5);

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
(1006, 1, 'Super Admin', 'superadmin@gmail.com', '1234', 'System', 'System', 0, '2026-07-19 16:27:52', 'Super Admin', NULL),
(1008, 2, 'Dr. Sanket Pawar', 'rohankurne12@gmail.com', 'Roh@1234', 'Super Admin', '1008', 0, '2026-07-22 11:41:32', 'Admin', 5),
(1009, 2, 'Abhishek mandhare', 'abhimandhare469@gmail.com', '$2y$10$5R0i1xosT40WQWjvxBHf..fnNRUbXtca6PU78UazijBOh8iCFt64y', 'Super Admin', 'Super Admin', 0, '2026-07-20 07:36:19', 'Admin', 6),
(1011, 2, 'Dr. Sam Dapal', 'rahulkumbhar2801@gmail.com', '$2y$10$NiLbbxHv1DY7iLKBaNQpYOo6rGXJzJzkSntQgvWct/3/MWz6n8fLW', 'Super Admin', 'Super Admin', 0, '2026-07-20 11:49:54', 'Admin', 7),
(1012, 8, 'Shiva Tevar', 'shiv@gmail.com', '12345678', 'Admin', 'Admin', 0, '2026-07-20 11:55:05', 'Patient', 6),
(1013, 6, 'Shivatej Katkar', 'shivatejkk@gmail.com', '$2y$10$u/ktNJcSyx9Xnmwo56PCm.o2xjLamOwafc/HTl3I6zRv6KaOofuz.', 'Admin', 'Admin', 0, '2026-07-20 12:17:47', 'Ward Boy', 6),
(1015, NULL, 'Chaitanya Patil', 'wchaitanyapatil@gmail.com', '12345678', 'Admin', 'Admin', 0, '2026-07-20 13:08:12', 'Doctor', NULL),
(1017, 2, '234rtgvfc', 'awerty@gmail.com', '$2y$10$6mgLTD96/yvZYeLG7vQZ6OsQqD.In1VpXsrdAyZs1opmy6qSBtg6e', 'Super Admin', 'Super Admin', 0, '2026-07-20 14:50:11', 'Admin', 7),
(1021, NULL, 'Ram Shinde', '9146556657abc@gmail.com', '$2y$10$yYwY7at4pGvUOSICmruZ7OZeL4OscFA4HrgX/tSeu5IYin391LQiO', 'Admin', 'Admin', 0, '2026-07-20 18:49:42', 'Nurse', 5),
(1022, 8, 'Eshwar Pawar', 'rohankurne16@gmail.com', 'Roh@1234', 'Admin', 'Admin', 0, '2026-07-20 18:59:58', 'Patient', 5),
(1023, NULL, 'Niraj Bhute', 'nirajbhute3@gmail.com', 'Roh@1234', 'Admin', 'Admin', 0, '2026-07-21 06:00:52', 'Patient', 5),
(1024, NULL, 'Ayur Machhle', 'ayur@gmail.com', 'Roh@1234', 'Admin', 'Admin', 0, '2026-07-21 06:07:26', 'Patient', 5),
(1025, NULL, 'Tejas Danawale', 'tejas@gmail.com', 'Roh@1234', 'Admin', 'Admin', 0, '2026-07-21 06:08:15', 'Patient', 5),
(1026, NULL, 'UltraHospital', 'ultr@gmail.com', '$2y$10$jzBM16zPG/tLazuuReB7iedzMEYPLDa93lzdtjXOnSo585jcK3.Ri', 'Admin', 'Admin', 0, '2026-07-21 06:09:22', 'Nurse', 5),
(1027, NULL, 'Sayagavkar', 'sayagav@gmail.conm', '$2y$10$Nir6Tg4QMaX6I5r8QmZ1sODzFKBgrKwHoKKOaAhrVU.AraKUluyuO', 'Admin', 'Admin', 0, '2026-07-21 06:13:10', 'Nurse', 5),
(1028, 3, 'Dr Ayush Nipane', 'ayushhnipane@gmail.com', 'Roh@1234', 'Admin', 'Admin', 0, '2026-07-21 06:45:02', 'Doctor', 5),
(1029, 3, 'Dr Shivatej Katkar', 'shivatejk033@gmail.com', 'Roh@1234', 'Admin', 'admin', 0, '2026-07-21 06:51:12', 'Doctor', 5),
(1039, NULL, 'Rohan Kurne', 'rohankurne125@gmail.com', 'Roh@1234', 'Admin', 'Admin', 0, '2026-07-21 09:26:53', 'Patient', 5),
(1040, 7, 'Rahul Namya', 'rahul@gmail.com', '$2y$10$3iQZhKgCloz1mV92jtgRiOwqbNzV.Ayy7kceRVVBTBgjQqYOFj8oi', 'Admin', 'Admin', 0, '2026-07-21 10:08:13', 'Lab Technician', 5),
(1041, NULL, 'Rohan Khade', 'rohankurne1256@gmail.com', 'Roh@1234', 'Admin', 'Admin', 0, '2026-07-21 10:17:52', 'Patient', 5),
(1042, NULL, 'Chaitanya Patil ', 'chaitanyapatil@gmail.com', 'Roh@1234', 'Admin', 'Admin', 0, '2026-07-21 11:36:28', 'Patient', 5),
(1043, NULL, 'Ram Kakade', 'rohankurne1246@gmail.com', 'Roh@1234', 'Admin', 'Admin', 0, '2026-07-21 11:48:27', 'Patient', 5),
(1044, NULL, 'Harshad Nikam', 'pratikkadam1620@gmail.com', 'Roh@1234', 'Admin', 'Admin', 0, '2026-07-22 05:29:24', 'Patient', 5),
(1045, NULL, 'Pratik Kadam', 'pratiksitsolutions@gmail.com', 'Roh@1234', 'Admin', 'Admin', 0, '2026-07-22 05:32:20', 'Patient', 5),
(1046, NULL, 'Rohan Kurne', 'rohankurq234ne12@gmail.com', 'Roh@1234', 'Admin', 'Admin', 0, '2026-07-22 06:26:24', 'Patient', 5),
(1047, NULL, 'dfgh', 'rohank@gmail.com', 'Roh@1234', 'Admin', 'Admin', 0, '2026-07-22 06:50:48', 'Patient', 5);

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
(13, NULL, 'Receptionist', 'receptionist', 'Receptionist', 1, 999, '2026-07-17 04:54:39', '2026-07-17 04:54:39', 0);

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
(7975, 2, 6, 48, '2026-07-20 16:29:21'),
(7976, 2, 6, 50, '2026-07-20 16:29:21'),
(7977, 2, 6, 49, '2026-07-20 16:29:21'),
(7978, 2, 6, 47, '2026-07-20 16:29:21'),
(7979, 2, 6, 96, '2026-07-20 16:29:21'),
(7980, 2, 6, 98, '2026-07-20 16:29:21'),
(7981, 2, 6, 97, '2026-07-20 16:29:21'),
(7982, 2, 6, 95, '2026-07-20 16:29:21'),
(7983, 2, 6, 2, '2026-07-20 16:29:21'),
(7984, 2, 6, 1, '2026-07-20 16:29:21'),
(7985, 2, 6, 6, '2026-07-20 16:29:21'),
(7986, 2, 6, 8, '2026-07-20 16:29:21'),
(7987, 2, 6, 7, '2026-07-20 16:29:21'),
(7988, 2, 6, 9, '2026-07-20 16:29:21'),
(7989, 2, 6, 5, '2026-07-20 16:29:21'),
(7990, 2, 6, 68, '2026-07-20 16:29:21'),
(7991, 2, 6, 70, '2026-07-20 16:29:21'),
(7992, 2, 6, 69, '2026-07-20 16:29:21'),
(7993, 2, 6, 67, '2026-07-20 16:29:21'),
(7994, 2, 6, 60, '2026-07-20 16:29:21'),
(7995, 2, 6, 62, '2026-07-20 16:29:21'),
(7996, 2, 6, 61, '2026-07-20 16:29:21'),
(7997, 2, 6, 59, '2026-07-20 16:29:21'),
(7998, 2, 6, 92, '2026-07-20 16:29:21'),
(7999, 2, 6, 94, '2026-07-20 16:29:21'),
(8000, 2, 6, 93, '2026-07-20 16:29:21'),
(8001, 2, 6, 91, '2026-07-20 16:29:21'),
(8002, 2, 6, 64, '2026-07-20 16:29:21'),
(8003, 2, 6, 66, '2026-07-20 16:29:21'),
(8004, 2, 6, 65, '2026-07-20 16:29:21'),
(8005, 2, 6, 63, '2026-07-20 16:29:21'),
(8006, 2, 6, 72, '2026-07-20 16:29:21'),
(8007, 2, 6, 74, '2026-07-20 16:29:21'),
(8008, 2, 6, 73, '2026-07-20 16:29:21'),
(8009, 2, 6, 71, '2026-07-20 16:29:21'),
(8010, 2, 6, 76, '2026-07-20 16:29:21'),
(8011, 2, 6, 78, '2026-07-20 16:29:21'),
(8012, 2, 6, 77, '2026-07-20 16:29:21'),
(8013, 2, 6, 75, '2026-07-20 16:29:21'),
(8014, 2, 6, 35, '2026-07-20 16:29:21'),
(8015, 2, 6, 37, '2026-07-20 16:29:21'),
(8016, 2, 6, 36, '2026-07-20 16:29:21'),
(8017, 2, 6, 34, '2026-07-20 16:29:21'),
(8018, 2, 6, 31, '2026-07-20 16:29:21'),
(8019, 2, 6, 33, '2026-07-20 16:29:21'),
(8020, 2, 6, 32, '2026-07-20 16:29:21'),
(8021, 2, 6, 30, '2026-07-20 16:29:21'),
(8022, 2, 6, 11, '2026-07-20 16:29:21'),
(8023, 2, 6, 13, '2026-07-20 16:29:21'),
(8024, 2, 6, 12, '2026-07-20 16:29:21'),
(8025, 2, 6, 10, '2026-07-20 16:29:21'),
(8026, 2, 6, 15, '2026-07-20 16:29:21'),
(8027, 2, 6, 17, '2026-07-20 16:29:21'),
(8028, 2, 6, 16, '2026-07-20 16:29:21'),
(8029, 2, 6, 14, '2026-07-20 16:29:21'),
(8030, 2, 6, 39, '2026-07-20 16:29:21'),
(8031, 2, 6, 41, '2026-07-20 16:29:21'),
(8032, 2, 6, 40, '2026-07-20 16:29:21'),
(8033, 2, 6, 38, '2026-07-20 16:29:21'),
(8034, 2, 6, 27, '2026-07-20 16:29:21'),
(8035, 2, 6, 29, '2026-07-20 16:29:21'),
(8036, 2, 6, 28, '2026-07-20 16:29:21'),
(8037, 2, 6, 26, '2026-07-20 16:29:21'),
(8038, 2, 6, 19, '2026-07-20 16:29:21'),
(8039, 2, 6, 21, '2026-07-20 16:29:21'),
(8040, 2, 6, 20, '2026-07-20 16:29:21'),
(8041, 2, 6, 18, '2026-07-20 16:29:21'),
(8042, 2, 6, 23, '2026-07-20 16:29:21'),
(8043, 2, 6, 25, '2026-07-20 16:29:21'),
(8044, 2, 6, 24, '2026-07-20 16:29:21'),
(8045, 2, 6, 22, '2026-07-20 16:29:21'),
(8046, 2, 6, 88, '2026-07-20 16:29:21'),
(8047, 2, 6, 90, '2026-07-20 16:29:21'),
(8048, 2, 6, 89, '2026-07-20 16:29:21'),
(8049, 2, 6, 87, '2026-07-20 16:29:21'),
(8050, 2, 6, 52, '2026-07-20 16:29:21'),
(8051, 2, 6, 54, '2026-07-20 16:29:21'),
(8052, 2, 6, 53, '2026-07-20 16:29:21'),
(8053, 2, 6, 51, '2026-07-20 16:29:21'),
(8054, 2, 6, 43, '2026-07-20 16:29:21'),
(8055, 2, 6, 45, '2026-07-20 16:29:21'),
(8056, 2, 6, 44, '2026-07-20 16:29:21'),
(8057, 2, 6, 46, '2026-07-20 16:29:21'),
(8058, 2, 6, 42, '2026-07-20 16:29:21'),
(8059, 2, 6, 80, '2026-07-20 16:29:21'),
(8060, 2, 6, 82, '2026-07-20 16:29:21'),
(8061, 2, 6, 81, '2026-07-20 16:29:21'),
(8062, 2, 6, 79, '2026-07-20 16:29:21'),
(8063, 2, 6, 84, '2026-07-20 16:29:21'),
(8064, 2, 6, 86, '2026-07-20 16:29:21'),
(8065, 2, 6, 85, '2026-07-20 16:29:21'),
(8066, 2, 6, 83, '2026-07-20 16:29:21'),
(8067, 2, 6, 56, '2026-07-20 16:29:21'),
(8068, 2, 6, 58, '2026-07-20 16:29:21'),
(8069, 2, 6, 57, '2026-07-20 16:29:21'),
(8070, 2, 6, 55, '2026-07-20 16:29:21'),
(8071, 2, 6, 99, '2026-07-20 16:29:21'),
(8553, 2, 9, 48, '2026-07-20 18:25:24'),
(8554, 2, 9, 50, '2026-07-20 18:25:24'),
(8555, 2, 9, 49, '2026-07-20 18:25:24'),
(8556, 2, 9, 47, '2026-07-20 18:25:24'),
(8557, 2, 9, 96, '2026-07-20 18:25:24'),
(8558, 2, 9, 98, '2026-07-20 18:25:24'),
(8559, 2, 9, 97, '2026-07-20 18:25:24'),
(8560, 2, 9, 95, '2026-07-20 18:25:24'),
(8561, 2, 9, 2, '2026-07-20 18:25:24'),
(8562, 2, 9, 1, '2026-07-20 18:25:24'),
(8563, 2, 9, 6, '2026-07-20 18:25:24'),
(8564, 2, 9, 8, '2026-07-20 18:25:24'),
(8565, 2, 9, 7, '2026-07-20 18:25:24'),
(8566, 2, 9, 9, '2026-07-20 18:25:24'),
(8567, 2, 9, 5, '2026-07-20 18:25:24'),
(8568, 2, 9, 68, '2026-07-20 18:25:24'),
(8569, 2, 9, 70, '2026-07-20 18:25:24'),
(8570, 2, 9, 69, '2026-07-20 18:25:24'),
(8571, 2, 9, 67, '2026-07-20 18:25:24'),
(8572, 2, 9, 60, '2026-07-20 18:25:24'),
(8573, 2, 9, 62, '2026-07-20 18:25:24'),
(8574, 2, 9, 61, '2026-07-20 18:25:24'),
(8575, 2, 9, 59, '2026-07-20 18:25:24'),
(8576, 2, 9, 92, '2026-07-20 18:25:24'),
(8577, 2, 9, 94, '2026-07-20 18:25:24'),
(8578, 2, 9, 93, '2026-07-20 18:25:24'),
(8579, 2, 9, 91, '2026-07-20 18:25:24'),
(8580, 2, 9, 64, '2026-07-20 18:25:24'),
(8581, 2, 9, 66, '2026-07-20 18:25:24'),
(8582, 2, 9, 65, '2026-07-20 18:25:24'),
(8583, 2, 9, 63, '2026-07-20 18:25:24'),
(8584, 2, 9, 72, '2026-07-20 18:25:24'),
(8585, 2, 9, 74, '2026-07-20 18:25:24'),
(8586, 2, 9, 73, '2026-07-20 18:25:24'),
(8587, 2, 9, 71, '2026-07-20 18:25:24'),
(8588, 2, 9, 76, '2026-07-20 18:25:24'),
(8589, 2, 9, 78, '2026-07-20 18:25:24'),
(8590, 2, 9, 77, '2026-07-20 18:25:24'),
(8591, 2, 9, 75, '2026-07-20 18:25:24'),
(8592, 2, 9, 35, '2026-07-20 18:25:24'),
(8593, 2, 9, 37, '2026-07-20 18:25:24'),
(8594, 2, 9, 36, '2026-07-20 18:25:24'),
(8595, 2, 9, 34, '2026-07-20 18:25:24'),
(8596, 2, 9, 31, '2026-07-20 18:25:24'),
(8597, 2, 9, 33, '2026-07-20 18:25:24'),
(8598, 2, 9, 32, '2026-07-20 18:25:24'),
(8599, 2, 9, 30, '2026-07-20 18:25:24'),
(8600, 2, 9, 11, '2026-07-20 18:25:24'),
(8601, 2, 9, 13, '2026-07-20 18:25:24'),
(8602, 2, 9, 12, '2026-07-20 18:25:24'),
(8603, 2, 9, 10, '2026-07-20 18:25:24'),
(8604, 2, 9, 15, '2026-07-20 18:25:24'),
(8605, 2, 9, 17, '2026-07-20 18:25:24'),
(8606, 2, 9, 16, '2026-07-20 18:25:24'),
(8607, 2, 9, 14, '2026-07-20 18:25:24'),
(8608, 2, 9, 39, '2026-07-20 18:25:24'),
(8609, 2, 9, 41, '2026-07-20 18:25:24'),
(8610, 2, 9, 40, '2026-07-20 18:25:24'),
(8611, 2, 9, 38, '2026-07-20 18:25:24'),
(8612, 2, 9, 27, '2026-07-20 18:25:24'),
(8613, 2, 9, 29, '2026-07-20 18:25:24'),
(8614, 2, 9, 28, '2026-07-20 18:25:24'),
(8615, 2, 9, 26, '2026-07-20 18:25:24'),
(8616, 2, 9, 19, '2026-07-20 18:25:24'),
(8617, 2, 9, 21, '2026-07-20 18:25:24'),
(8618, 2, 9, 20, '2026-07-20 18:25:24'),
(8619, 2, 9, 18, '2026-07-20 18:25:24'),
(8620, 2, 9, 23, '2026-07-20 18:25:24'),
(8621, 2, 9, 25, '2026-07-20 18:25:24'),
(8622, 2, 9, 24, '2026-07-20 18:25:24'),
(8623, 2, 9, 22, '2026-07-20 18:25:24'),
(8624, 2, 9, 88, '2026-07-20 18:25:24'),
(8625, 2, 9, 90, '2026-07-20 18:25:24'),
(8626, 2, 9, 89, '2026-07-20 18:25:24'),
(8627, 2, 9, 87, '2026-07-20 18:25:24'),
(8628, 2, 9, 52, '2026-07-20 18:25:24'),
(8629, 2, 9, 54, '2026-07-20 18:25:24'),
(8630, 2, 9, 53, '2026-07-20 18:25:24'),
(8631, 2, 9, 51, '2026-07-20 18:25:24'),
(8632, 2, 9, 43, '2026-07-20 18:25:24'),
(8633, 2, 9, 45, '2026-07-20 18:25:24'),
(8634, 2, 9, 44, '2026-07-20 18:25:24'),
(8635, 2, 9, 46, '2026-07-20 18:25:24'),
(8636, 2, 9, 42, '2026-07-20 18:25:24'),
(8637, 2, 9, 80, '2026-07-20 18:25:24'),
(8638, 2, 9, 82, '2026-07-20 18:25:24'),
(8639, 2, 9, 81, '2026-07-20 18:25:24'),
(8640, 2, 9, 79, '2026-07-20 18:25:24'),
(8641, 2, 9, 84, '2026-07-20 18:25:24'),
(8642, 2, 9, 86, '2026-07-20 18:25:24'),
(8643, 2, 9, 85, '2026-07-20 18:25:24'),
(8644, 2, 9, 83, '2026-07-20 18:25:24'),
(8645, 2, 9, 56, '2026-07-20 18:25:24'),
(8646, 2, 9, 58, '2026-07-20 18:25:24'),
(8647, 2, 9, 57, '2026-07-20 18:25:24'),
(8648, 2, 9, 55, '2026-07-20 18:25:24'),
(8649, 2, 9, 99, '2026-07-20 18:25:24'),
(8650, 2, 7, 48, '2026-07-20 18:50:05'),
(8651, 2, 7, 50, '2026-07-20 18:50:05'),
(8652, 2, 7, 49, '2026-07-20 18:50:05'),
(8653, 2, 7, 47, '2026-07-20 18:50:05'),
(8654, 2, 7, 96, '2026-07-20 18:50:05'),
(8655, 2, 7, 98, '2026-07-20 18:50:05'),
(8656, 2, 7, 97, '2026-07-20 18:50:05'),
(8657, 2, 7, 95, '2026-07-20 18:50:05'),
(8658, 2, 7, 2, '2026-07-20 18:50:05'),
(8659, 2, 7, 1, '2026-07-20 18:50:05'),
(8660, 2, 7, 6, '2026-07-20 18:50:05'),
(8661, 2, 7, 8, '2026-07-20 18:50:05'),
(8662, 2, 7, 7, '2026-07-20 18:50:05'),
(8663, 2, 7, 9, '2026-07-20 18:50:05'),
(8664, 2, 7, 5, '2026-07-20 18:50:05'),
(8665, 2, 7, 68, '2026-07-20 18:50:05'),
(8666, 2, 7, 70, '2026-07-20 18:50:05'),
(8667, 2, 7, 69, '2026-07-20 18:50:05'),
(8668, 2, 7, 67, '2026-07-20 18:50:05'),
(8669, 2, 7, 60, '2026-07-20 18:50:05'),
(8670, 2, 7, 62, '2026-07-20 18:50:05'),
(8671, 2, 7, 61, '2026-07-20 18:50:05'),
(8672, 2, 7, 59, '2026-07-20 18:50:05'),
(8673, 2, 7, 92, '2026-07-20 18:50:05'),
(8674, 2, 7, 94, '2026-07-20 18:50:05'),
(8675, 2, 7, 93, '2026-07-20 18:50:05'),
(8676, 2, 7, 91, '2026-07-20 18:50:05'),
(8677, 2, 7, 64, '2026-07-20 18:50:05'),
(8678, 2, 7, 66, '2026-07-20 18:50:05'),
(8679, 2, 7, 65, '2026-07-20 18:50:05'),
(8680, 2, 7, 63, '2026-07-20 18:50:05'),
(8681, 2, 7, 72, '2026-07-20 18:50:05'),
(8682, 2, 7, 74, '2026-07-20 18:50:05'),
(8683, 2, 7, 73, '2026-07-20 18:50:05'),
(8684, 2, 7, 71, '2026-07-20 18:50:05'),
(8685, 2, 7, 76, '2026-07-20 18:50:05'),
(8686, 2, 7, 78, '2026-07-20 18:50:05'),
(8687, 2, 7, 77, '2026-07-20 18:50:05'),
(8688, 2, 7, 75, '2026-07-20 18:50:05'),
(8689, 2, 7, 35, '2026-07-20 18:50:05'),
(8690, 2, 7, 37, '2026-07-20 18:50:05'),
(8691, 2, 7, 36, '2026-07-20 18:50:05'),
(8692, 2, 7, 34, '2026-07-20 18:50:05'),
(8693, 2, 7, 31, '2026-07-20 18:50:05'),
(8694, 2, 7, 33, '2026-07-20 18:50:05'),
(8695, 2, 7, 32, '2026-07-20 18:50:05'),
(8696, 2, 7, 30, '2026-07-20 18:50:05'),
(8697, 2, 7, 11, '2026-07-20 18:50:05'),
(8698, 2, 7, 13, '2026-07-20 18:50:05'),
(8699, 2, 7, 12, '2026-07-20 18:50:05'),
(8700, 2, 7, 10, '2026-07-20 18:50:05'),
(8701, 2, 7, 15, '2026-07-20 18:50:05'),
(8702, 2, 7, 17, '2026-07-20 18:50:05'),
(8703, 2, 7, 16, '2026-07-20 18:50:05'),
(8704, 2, 7, 14, '2026-07-20 18:50:05'),
(8705, 2, 7, 39, '2026-07-20 18:50:05'),
(8706, 2, 7, 41, '2026-07-20 18:50:05'),
(8707, 2, 7, 40, '2026-07-20 18:50:05'),
(8708, 2, 7, 38, '2026-07-20 18:50:05'),
(8709, 2, 7, 27, '2026-07-20 18:50:05'),
(8710, 2, 7, 29, '2026-07-20 18:50:05'),
(8711, 2, 7, 28, '2026-07-20 18:50:05'),
(8712, 2, 7, 26, '2026-07-20 18:50:05'),
(8713, 2, 7, 19, '2026-07-20 18:50:05'),
(8714, 2, 7, 21, '2026-07-20 18:50:05'),
(8715, 2, 7, 20, '2026-07-20 18:50:05'),
(8716, 2, 7, 18, '2026-07-20 18:50:05'),
(8717, 2, 7, 23, '2026-07-20 18:50:05'),
(8718, 2, 7, 25, '2026-07-20 18:50:05'),
(8719, 2, 7, 24, '2026-07-20 18:50:05'),
(8720, 2, 7, 22, '2026-07-20 18:50:05'),
(8721, 2, 7, 88, '2026-07-20 18:50:05'),
(8722, 2, 7, 90, '2026-07-20 18:50:05'),
(8723, 2, 7, 89, '2026-07-20 18:50:05'),
(8724, 2, 7, 87, '2026-07-20 18:50:05'),
(8725, 2, 7, 52, '2026-07-20 18:50:05'),
(8726, 2, 7, 54, '2026-07-20 18:50:05'),
(8727, 2, 7, 53, '2026-07-20 18:50:05'),
(8728, 2, 7, 51, '2026-07-20 18:50:05'),
(8729, 2, 7, 43, '2026-07-20 18:50:05'),
(8730, 2, 7, 45, '2026-07-20 18:50:05'),
(8731, 2, 7, 44, '2026-07-20 18:50:05'),
(8732, 2, 7, 46, '2026-07-20 18:50:05'),
(8733, 2, 7, 42, '2026-07-20 18:50:05'),
(8734, 2, 7, 80, '2026-07-20 18:50:05'),
(8735, 2, 7, 82, '2026-07-20 18:50:05'),
(8736, 2, 7, 81, '2026-07-20 18:50:05'),
(8737, 2, 7, 79, '2026-07-20 18:50:05'),
(8738, 2, 7, 84, '2026-07-20 18:50:05'),
(8739, 2, 7, 86, '2026-07-20 18:50:05'),
(8740, 2, 7, 85, '2026-07-20 18:50:05'),
(8741, 2, 7, 83, '2026-07-20 18:50:05'),
(8742, 2, 7, 56, '2026-07-20 18:50:05'),
(8743, 2, 7, 58, '2026-07-20 18:50:05'),
(8744, 2, 7, 57, '2026-07-20 18:50:05'),
(8745, 2, 7, 55, '2026-07-20 18:50:05'),
(8746, 2, 7, 99, '2026-07-20 18:50:05'),
(10208, 3, 5, 48, '2026-07-22 09:26:10'),
(10209, 3, 5, 50, '2026-07-22 09:26:10'),
(10210, 3, 5, 49, '2026-07-22 09:26:10'),
(10211, 3, 5, 47, '2026-07-22 09:26:10'),
(10212, 3, 5, 96, '2026-07-22 09:26:10'),
(10213, 3, 5, 98, '2026-07-22 09:26:10'),
(10214, 3, 5, 97, '2026-07-22 09:26:10'),
(10215, 3, 5, 95, '2026-07-22 09:26:10'),
(10216, 3, 5, 2, '2026-07-22 09:26:10'),
(10217, 3, 5, 1, '2026-07-22 09:26:10'),
(10218, 3, 5, 6, '2026-07-22 09:26:10'),
(10219, 3, 5, 8, '2026-07-22 09:26:10'),
(10220, 3, 5, 7, '2026-07-22 09:26:10'),
(10221, 3, 5, 9, '2026-07-22 09:26:10'),
(10222, 3, 5, 5, '2026-07-22 09:26:10'),
(10223, 3, 5, 68, '2026-07-22 09:26:10'),
(10224, 3, 5, 70, '2026-07-22 09:26:10'),
(10225, 3, 5, 69, '2026-07-22 09:26:10'),
(10226, 3, 5, 67, '2026-07-22 09:26:10'),
(10227, 3, 5, 60, '2026-07-22 09:26:10'),
(10228, 3, 5, 62, '2026-07-22 09:26:10'),
(10229, 3, 5, 61, '2026-07-22 09:26:10'),
(10230, 3, 5, 59, '2026-07-22 09:26:10'),
(10231, 3, 5, 92, '2026-07-22 09:26:10'),
(10232, 3, 5, 94, '2026-07-22 09:26:10'),
(10233, 3, 5, 93, '2026-07-22 09:26:10'),
(10234, 3, 5, 91, '2026-07-22 09:26:10'),
(10235, 3, 5, 64, '2026-07-22 09:26:10'),
(10236, 3, 5, 66, '2026-07-22 09:26:10'),
(10237, 3, 5, 65, '2026-07-22 09:26:10'),
(10238, 3, 5, 63, '2026-07-22 09:26:10'),
(10239, 3, 5, 72, '2026-07-22 09:26:10'),
(10240, 3, 5, 74, '2026-07-22 09:26:10'),
(10241, 3, 5, 73, '2026-07-22 09:26:10'),
(10242, 3, 5, 71, '2026-07-22 09:26:10'),
(10243, 3, 5, 76, '2026-07-22 09:26:10'),
(10244, 3, 5, 78, '2026-07-22 09:26:10'),
(10245, 3, 5, 77, '2026-07-22 09:26:10'),
(10246, 3, 5, 75, '2026-07-22 09:26:10'),
(10247, 3, 5, 35, '2026-07-22 09:26:10'),
(10248, 3, 5, 37, '2026-07-22 09:26:10'),
(10249, 3, 5, 36, '2026-07-22 09:26:10'),
(10250, 3, 5, 34, '2026-07-22 09:26:10'),
(10251, 3, 5, 31, '2026-07-22 09:26:10'),
(10252, 3, 5, 33, '2026-07-22 09:26:10'),
(10253, 3, 5, 32, '2026-07-22 09:26:10'),
(10254, 3, 5, 30, '2026-07-22 09:26:10'),
(10255, 3, 5, 11, '2026-07-22 09:26:10'),
(10256, 3, 5, 13, '2026-07-22 09:26:10'),
(10257, 3, 5, 12, '2026-07-22 09:26:10'),
(10258, 3, 5, 10, '2026-07-22 09:26:10'),
(10259, 3, 5, 15, '2026-07-22 09:26:10'),
(10260, 3, 5, 17, '2026-07-22 09:26:10'),
(10261, 3, 5, 16, '2026-07-22 09:26:10'),
(10262, 3, 5, 14, '2026-07-22 09:26:10'),
(10263, 3, 5, 39, '2026-07-22 09:26:10'),
(10264, 3, 5, 41, '2026-07-22 09:26:10'),
(10265, 3, 5, 40, '2026-07-22 09:26:10'),
(10266, 3, 5, 38, '2026-07-22 09:26:10'),
(10267, 3, 5, 27, '2026-07-22 09:26:10'),
(10268, 3, 5, 29, '2026-07-22 09:26:10'),
(10269, 3, 5, 28, '2026-07-22 09:26:10'),
(10270, 3, 5, 26, '2026-07-22 09:26:10'),
(10271, 3, 5, 19, '2026-07-22 09:26:10'),
(10272, 3, 5, 21, '2026-07-22 09:26:10'),
(10273, 3, 5, 20, '2026-07-22 09:26:10'),
(10274, 3, 5, 18, '2026-07-22 09:26:10'),
(10275, 3, 5, 23, '2026-07-22 09:26:10'),
(10276, 3, 5, 25, '2026-07-22 09:26:10'),
(10277, 3, 5, 24, '2026-07-22 09:26:10'),
(10278, 3, 5, 22, '2026-07-22 09:26:10'),
(10279, 3, 5, 88, '2026-07-22 09:26:10'),
(10280, 3, 5, 90, '2026-07-22 09:26:10'),
(10281, 3, 5, 89, '2026-07-22 09:26:10'),
(10282, 3, 5, 87, '2026-07-22 09:26:10'),
(10283, 3, 5, 52, '2026-07-22 09:26:10'),
(10284, 3, 5, 54, '2026-07-22 09:26:10'),
(10285, 3, 5, 53, '2026-07-22 09:26:10'),
(10286, 3, 5, 51, '2026-07-22 09:26:10'),
(10287, 3, 5, 202, '2026-07-22 09:26:10'),
(10288, 3, 5, 43, '2026-07-22 09:26:10'),
(10289, 3, 5, 45, '2026-07-22 09:26:10'),
(10290, 3, 5, 44, '2026-07-22 09:26:10'),
(10291, 3, 5, 46, '2026-07-22 09:26:10'),
(10292, 3, 5, 42, '2026-07-22 09:26:10'),
(10293, 3, 5, 201, '2026-07-22 09:26:10'),
(10294, 3, 5, 80, '2026-07-22 09:26:10'),
(10295, 3, 5, 82, '2026-07-22 09:26:10'),
(10296, 3, 5, 81, '2026-07-22 09:26:10'),
(10297, 3, 5, 79, '2026-07-22 09:26:10'),
(10298, 3, 5, 84, '2026-07-22 09:26:10'),
(10299, 3, 5, 86, '2026-07-22 09:26:10'),
(10300, 3, 5, 85, '2026-07-22 09:26:10'),
(10301, 3, 5, 83, '2026-07-22 09:26:10'),
(10302, 3, 5, 56, '2026-07-22 09:26:10'),
(10303, 3, 5, 58, '2026-07-22 09:26:10'),
(10304, 3, 5, 57, '2026-07-22 09:26:10'),
(10305, 3, 5, 55, '2026-07-22 09:26:10'),
(10306, 3, 5, 99, '2026-07-22 09:26:10'),
(10411, 7, 5, 1, '2026-07-22 09:27:32'),
(10412, 7, 5, 76, '2026-07-22 09:27:32'),
(10413, 7, 5, 78, '2026-07-22 09:27:32'),
(10414, 7, 5, 77, '2026-07-22 09:27:32'),
(10415, 7, 5, 75, '2026-07-22 09:27:32'),
(10614, 2, 5, 48, '2026-07-22 11:58:19'),
(10615, 2, 5, 50, '2026-07-22 11:58:19'),
(10616, 2, 5, 49, '2026-07-22 11:58:19'),
(10617, 2, 5, 47, '2026-07-22 11:58:19'),
(10618, 2, 5, 96, '2026-07-22 11:58:19'),
(10619, 2, 5, 98, '2026-07-22 11:58:19'),
(10620, 2, 5, 97, '2026-07-22 11:58:19'),
(10621, 2, 5, 95, '2026-07-22 11:58:19'),
(10622, 2, 5, 2, '2026-07-22 11:58:19'),
(10623, 2, 5, 1, '2026-07-22 11:58:19'),
(10624, 2, 5, 6, '2026-07-22 11:58:19'),
(10625, 2, 5, 8, '2026-07-22 11:58:19'),
(10626, 2, 5, 7, '2026-07-22 11:58:19'),
(10627, 2, 5, 9, '2026-07-22 11:58:19'),
(10628, 2, 5, 5, '2026-07-22 11:58:19'),
(10629, 2, 5, 68, '2026-07-22 11:58:19'),
(10630, 2, 5, 70, '2026-07-22 11:58:19'),
(10631, 2, 5, 69, '2026-07-22 11:58:19'),
(10632, 2, 5, 67, '2026-07-22 11:58:19'),
(10633, 2, 5, 60, '2026-07-22 11:58:19'),
(10634, 2, 5, 62, '2026-07-22 11:58:19'),
(10635, 2, 5, 61, '2026-07-22 11:58:19'),
(10636, 2, 5, 59, '2026-07-22 11:58:19'),
(10637, 2, 5, 92, '2026-07-22 11:58:19'),
(10638, 2, 5, 94, '2026-07-22 11:58:19'),
(10639, 2, 5, 93, '2026-07-22 11:58:19'),
(10640, 2, 5, 91, '2026-07-22 11:58:19'),
(10641, 2, 5, 64, '2026-07-22 11:58:19'),
(10642, 2, 5, 66, '2026-07-22 11:58:19'),
(10643, 2, 5, 65, '2026-07-22 11:58:19'),
(10644, 2, 5, 63, '2026-07-22 11:58:19'),
(10645, 2, 5, 203, '2026-07-22 11:58:19'),
(10646, 2, 5, 72, '2026-07-22 11:58:19'),
(10647, 2, 5, 74, '2026-07-22 11:58:19'),
(10648, 2, 5, 73, '2026-07-22 11:58:19'),
(10649, 2, 5, 71, '2026-07-22 11:58:19'),
(10650, 2, 5, 76, '2026-07-22 11:58:19'),
(10651, 2, 5, 78, '2026-07-22 11:58:19'),
(10652, 2, 5, 77, '2026-07-22 11:58:19'),
(10653, 2, 5, 75, '2026-07-22 11:58:19'),
(10654, 2, 5, 35, '2026-07-22 11:58:19'),
(10655, 2, 5, 37, '2026-07-22 11:58:19'),
(10656, 2, 5, 36, '2026-07-22 11:58:19'),
(10657, 2, 5, 34, '2026-07-22 11:58:19'),
(10658, 2, 5, 31, '2026-07-22 11:58:19'),
(10659, 2, 5, 33, '2026-07-22 11:58:19'),
(10660, 2, 5, 32, '2026-07-22 11:58:19'),
(10661, 2, 5, 30, '2026-07-22 11:58:19'),
(10662, 2, 5, 11, '2026-07-22 11:58:19'),
(10663, 2, 5, 13, '2026-07-22 11:58:19'),
(10664, 2, 5, 12, '2026-07-22 11:58:19'),
(10665, 2, 5, 10, '2026-07-22 11:58:19'),
(10666, 2, 5, 15, '2026-07-22 11:58:19'),
(10667, 2, 5, 17, '2026-07-22 11:58:19'),
(10668, 2, 5, 16, '2026-07-22 11:58:19'),
(10669, 2, 5, 14, '2026-07-22 11:58:19'),
(10670, 2, 5, 39, '2026-07-22 11:58:19'),
(10671, 2, 5, 41, '2026-07-22 11:58:19'),
(10672, 2, 5, 40, '2026-07-22 11:58:19'),
(10673, 2, 5, 38, '2026-07-22 11:58:19'),
(10674, 2, 5, 27, '2026-07-22 11:58:19'),
(10675, 2, 5, 29, '2026-07-22 11:58:19'),
(10676, 2, 5, 28, '2026-07-22 11:58:19'),
(10677, 2, 5, 26, '2026-07-22 11:58:19'),
(10678, 2, 5, 19, '2026-07-22 11:58:19'),
(10679, 2, 5, 21, '2026-07-22 11:58:19'),
(10680, 2, 5, 20, '2026-07-22 11:58:19'),
(10681, 2, 5, 18, '2026-07-22 11:58:19'),
(10682, 2, 5, 23, '2026-07-22 11:58:19'),
(10683, 2, 5, 25, '2026-07-22 11:58:19'),
(10684, 2, 5, 24, '2026-07-22 11:58:19'),
(10685, 2, 5, 22, '2026-07-22 11:58:19'),
(10686, 2, 5, 88, '2026-07-22 11:58:19'),
(10687, 2, 5, 90, '2026-07-22 11:58:19'),
(10688, 2, 5, 89, '2026-07-22 11:58:19'),
(10689, 2, 5, 87, '2026-07-22 11:58:19'),
(10690, 2, 5, 52, '2026-07-22 11:58:19'),
(10691, 2, 5, 54, '2026-07-22 11:58:19'),
(10692, 2, 5, 53, '2026-07-22 11:58:19'),
(10693, 2, 5, 51, '2026-07-22 11:58:19'),
(10694, 2, 5, 205, '2026-07-22 11:58:19'),
(10695, 2, 5, 207, '2026-07-22 11:58:19'),
(10696, 2, 5, 206, '2026-07-22 11:58:19'),
(10697, 2, 5, 204, '2026-07-22 11:58:19'),
(10698, 2, 5, 202, '2026-07-22 11:58:19'),
(10699, 2, 5, 43, '2026-07-22 11:58:19'),
(10700, 2, 5, 45, '2026-07-22 11:58:19'),
(10701, 2, 5, 44, '2026-07-22 11:58:19'),
(10702, 2, 5, 46, '2026-07-22 11:58:19'),
(10703, 2, 5, 42, '2026-07-22 11:58:19'),
(10704, 2, 5, 201, '2026-07-22 11:58:19'),
(10705, 2, 5, 80, '2026-07-22 11:58:19'),
(10706, 2, 5, 82, '2026-07-22 11:58:19'),
(10707, 2, 5, 81, '2026-07-22 11:58:19'),
(10708, 2, 5, 79, '2026-07-22 11:58:19'),
(10709, 2, 5, 84, '2026-07-22 11:58:19'),
(10710, 2, 5, 86, '2026-07-22 11:58:19'),
(10711, 2, 5, 85, '2026-07-22 11:58:19'),
(10712, 2, 5, 83, '2026-07-22 11:58:19'),
(10713, 2, 5, 56, '2026-07-22 11:58:19'),
(10714, 2, 5, 58, '2026-07-22 11:58:19'),
(10715, 2, 5, 57, '2026-07-22 11:58:19'),
(10716, 2, 5, 55, '2026-07-22 11:58:19'),
(10717, 2, 5, 99, '2026-07-22 11:58:19');

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
(1, 0, 1, '126', 24, 'Available', 0, '2026-07-20 19:08:09', '2026-07-22 12:32:32');

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
(5, 1013, 'Shivatej Katkar', '', 'shivatejkk@gmail.com', 'Ward_Boy', '', 'documents/staff/images/scanner1.jpeg', 'Active', '2026-07-20 12:17:47', '2026-07-20 12:35:28', 0, 6),
(8, 1021, 'Ram Shinde', '1234563456', '9146556657abc@gmail.com', 'Nurse', '', '', 'Active', '2026-07-20 18:49:42', '2026-07-20 18:49:42', 0, 5),
(9, 1026, 'UltraHospital', '7845895125', 'ultr@gmail.com', 'Nurse', '', '', 'Active', '2026-07-21 06:09:22', '2026-07-22 10:49:16', 0, 5),
(10, 1027, 'Sayagavkar', '', 'sayagav@gmail.conm', 'Nurse', '', '', 'Active', '2026-07-21 06:13:10', '2026-07-21 06:13:10', 0, 5),
(11, 1040, 'Rahul Namya', '7894562144', 'rahul@gmail.com', 'Lab Technician', '', 'documents/staff/images/Department Master.png', 'Active', '2026-07-21 10:08:13', '2026-07-22 10:52:01', 0, 5);

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `subscription_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `plan_name` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `status` enum('Active','Expired','Cancelled') DEFAULT 'Active',
  `amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delete_flag` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(3, 29, 8, 5, 'SUR20260722104534', 'Appendoncy', 'awfghjkjyt43234567654\\32', '2026-07-22', '14:15:00', '4 Hours', 'City Hospital', 'Dr Ayush Nipane', 'NIraj Bhute', 'Niram', '', 'Orthopedic', 'aqweryhj,', 'qwertyhuj', '1234tyuk', 'ASDFGHNM', '230 ml', 'Scheduled', 'sdfbn', '2026-07-31', 'ASDFG', '2026-07-22 08:46:29', '2026-07-22 09:57:47', 0),
(4, 6, 8, 5, 'SUR20260722104831', 'Appendoncy', 'Laparesomic Apanedancey', '2026-07-23', '14:18:00', '4 Hours', 'City Hospital', 'Dr Ayush Nipane', 'NIraj Bhute', 'Niram', '', 'Orthopedic', 'asdfgh', 'QAWDFGH', '23456', 'asdfgh', '230 ml', 'Scheduled', '2345ywerghj', '2026-08-07', 'asdfghjkiuytre2345', '2026-07-22 08:48:39', '2026-07-22 10:15:05', 0),
(5, 25, 8, 5, 'SUR20260722115517', 'Appendoncy', 'Laparesomic Apanedancey', '2026-07-23', '15:26:00', '4 Hours', 'City Hospital', 'Dr Ayush Nipane', 'NIraj Bhute', 'Niram', '', 'Neuro', 'asdfg', 'qwert', 'sdfgh', 'adfgh', '140 ml', 'Scheduled', 'asdf', '2026-07-31', 'sdfgh', '2026-07-22 09:56:20', '2026-07-22 09:56:20', 0),
(6, 6, 9, 5, 'SUR20260722121521', 'Appendoncy', 'Mutkhada', '2026-07-24', '15:48:00', '5 Hours', 'City Hospital', 'Dr Shivatej Katkar', 'NIraj Bhute', 'Nirma', 'Minor', 'Orthopedic', 'Fever', 'golya kha ', 'ky nko khau', 'poricha nad sod', '140 ml', 'Scheduled', 'ok', '0000-00-00', 'horn ok please', '2026-07-22 10:15:58', '2026-07-22 12:50:02', 1),
(7, 6, 8, 5, 'SUR20260722121757', 'Heart Surgery ', '', '2026-07-24', '05:48:00', '4 Hours', 'City Hospital', 'Dr Ayush Nipane', '', 'Niram', 'Major', 'ENT', 'Heart', 'Heart', '', '', '', 'Scheduled', '', '0000-00-00', '', '2026-07-22 10:20:26', '2026-07-22 13:16:50', 0),
(8, 29, 8, 5, 'SUR20260722143104', 'Opration', 'Mutkhada', '2026-07-22', '18:06:00', '4 Hours', 'City Hospital', 'Dr Ayush Nipane', 'NIraj Bhute', 'Niram', 'Emergency', 'Urology', 'ertghj', 'wertgh', 'sdfg', 'dfg', '', 'Scheduled', '', '0000-00-00', '', '2026-07-22 12:35:25', '2026-07-22 12:35:25', 0);

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
(2, 6, 'General Ward', 'General', 2, 'Available', 0, '2026-07-21 07:33:12', '2026-07-21 07:33:12');

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
-- Indexes for table `lab_technician`
--
ALTER TABLE `lab_technician`
  ADD PRIMARY KEY (`technician_id`);

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
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prescription_patient` (`patient_id`),
  ADD KEY `fk_prescription_doctor` (`doctor_id`);

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
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=339;

--
-- AUTO_INCREMENT for table `bed_allocation`
--
ALTER TABLE `bed_allocation`
  MODIFY `allocation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `bed_master`
--
ALTER TABLE `bed_master`
  MODIFY `bed_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `billing`
--
ALTER TABLE `billing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `discharge_summary`
--
ALTER TABLE `discharge_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor`
--
ALTER TABLE `doctor`
  MODIFY `doctor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `email_templates`
--
ALTER TABLE `email_templates`
  MODIFY `template_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `hospital_admin`
--
ALTER TABLE `hospital_admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `hospital_master`
--
ALTER TABLE `hospital_master`
  MODIFY `hospital_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `lab_order_details`
--
ALTER TABLE `lab_order_details`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `lab_reports`
--
ALTER TABLE `lab_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `lab_technician`
--
ALTER TABLE `lab_technician`
  MODIFY `technician_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_tests`
--
ALTER TABLE `lab_tests`
  MODIFY `test_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `lab_test_categories`
--
ALTER TABLE `lab_test_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `lab_test_results`
--
ALTER TABLE `lab_test_results`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `login_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=590;

--
-- AUTO_INCREMENT for table `opd`
--
ALTER TABLE `opd`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `patient_alerts`
--
ALTER TABLE `patient_alerts`
  MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `patient_documents`
--
ALTER TABLE `patient_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=208;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `register`
--
ALTER TABLE `register`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1048;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10718;

--
-- AUTO_INCREMENT for table `room_master`
--
ALTER TABLE `room_master`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `subscription_id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `ward_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `fk_prescription_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`doctor_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_prescription_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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

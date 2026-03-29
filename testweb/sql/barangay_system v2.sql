-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 28, 2026 at 08:51 AM
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
-- Database: `barangay_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `about_sections`
--

CREATE TABLE `about_sections` (
  `id` int(11) NOT NULL,
  `page_id` int(11) DEFAULT NULL,
  `section_title` varchar(200) NOT NULL,
  `section_content` text NOT NULL,
  `section_type` varchar(50) DEFAULT 'general',
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `about_sections`
--

INSERT INTO `about_sections` (`id`, `page_id`, `section_title`, `section_content`, `section_type`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Our History', 'Our barangay was established in 1950 with a vision to serve the community. Over the years, we have grown and developed into a progressive community that values unity, cooperation, and development.', 'history', 1, 1, '2026-03-28 04:24:28', '2026-03-28 04:24:28'),
(2, NULL, 'Our Mission', 'To provide efficient and effective public service, promote the welfare of our residents, and foster a safe, healthy, and progressive community.', 'mission', 2, 1, '2026-03-28 04:24:28', '2026-03-28 04:24:28'),
(3, NULL, 'Our Vision', 'A model barangay known for its good governance, empowered citizens, and sustainable development that serves as an inspiration to others.', 'vision', 3, 1, '2026-03-28 04:24:28', '2026-03-28 04:24:28');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `admin_type` enum('main_admin','staff_admin') NOT NULL DEFAULT 'staff_admin',
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `admin_type`, `email`, `first_name`, `last_name`, `profile_image`, `last_login`, `is_active`, `created_at`) VALUES
(4, 'mainadmin', '$2y$10$cOLGvSZOvr/LnaydBagWHObkSdpg1Q6KvBfGqzJGIub4QfmQHSOTS', 'main_admin', 'mainadmin@barangay.com', 'Main', 'Admin', NULL, '2026-03-28 07:48:17', 1, '2026-03-13 05:14:04'),
(5, 'staffadmin', '$2y$10$skALAOJSGUESzeFMb92c2.LhSR5bIFaMdu82zCQGmCrzxhMsnEG2.', 'staff_admin', 'staff@barangay.com', 'Staff', 'Admin', NULL, '2026-03-26 07:13:27', 1, '2026-03-13 05:14:04'),
(6, 'subadmin', '$2y$10$E6DcPwgD7drnes8pDYWqR.l6nmG7uCGiviMo3OtU6dSoyr33bOzrG', 'staff_admin', 'sub@barangay.com', 'Sub', 'Admin', NULL, '2026-03-28 06:50:57', 1, '2026-03-13 05:14:04');

-- --------------------------------------------------------

--
-- Table structure for table `admins_backup`
--

CREATE TABLE `admins_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `role` enum('Main Admin','Staff Admin','Sub Admin') NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins_backup`
--

INSERT INTO `admins_backup` (`id`, `username`, `password`, `role_id`, `email`, `first_name`, `last_name`, `role`, `profile_image`, `last_login`, `is_active`, `created_at`) VALUES
(4, 'mainadmin', '$2y$10$cOLGvSZOvr/LnaydBagWHObkSdpg1Q6KvBfGqzJGIub4QfmQHSOTS', 1, 'mainadmin@barangay.com', 'Main', 'Admin', 'Main Admin', NULL, '2026-03-23 06:41:26', 1, '2026-03-13 05:14:04'),
(5, 'staffadmin', '$2y$10$skALAOJSGUESzeFMb92c2.LhSR5bIFaMdu82zCQGmCrzxhMsnEG2.', 2, 'staff@barangay.com', 'Staff', 'Admin', 'Staff Admin', NULL, '2026-03-15 09:21:49', 1, '2026-03-13 05:14:04'),
(6, 'subadmin', '$2y$10$E6DcPwgD7drnes8pDYWqR.l6nmG7uCGiviMo3OtU6dSoyr33bOzrG', 3, 'sub@barangay.com', 'Sub', 'Admin', 'Sub Admin', NULL, '2026-03-15 09:41:36', 1, '2026-03-13 05:14:04');

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 4, 'LOGIN', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-13 05:14:19'),
(2, 4, 'LOGIN', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 08:16:47'),
(3, 4, 'QR_SCAN', 'Scanned QR for user ID: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 08:44:56'),
(4, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 08:53:07'),
(5, 4, 'LOGIN', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 08:53:24'),
(6, 4, 'ADD_SERVICE', 'Added service: hi', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 09:14:37'),
(7, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 09:19:43'),
(8, 4, 'LOGIN', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 09:19:52'),
(9, 4, 'UPDATE_REQUEST', 'Updated request #1 to approved', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 09:27:06'),
(10, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 09:39:00'),
(11, 5, 'LOGIN', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 09:39:36'),
(12, 5, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 09:39:52'),
(13, 6, 'LOGIN', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 09:40:54'),
(14, 6, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 09:41:04'),
(15, 4, 'LOGIN', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 09:41:08'),
(16, 4, 'QR_SCAN', 'Scanned QR for user ID: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 09:44:38'),
(17, 4, 'DELETE_RESIDENT', 'Deleted resident:  ', '::1', NULL, '2026-03-14 09:57:39'),
(18, 4, 'DELETE_RESIDENT', 'Deleted resident:  ', '::1', NULL, '2026-03-14 09:57:45'),
(19, 4, 'DELETE_RESIDENT', 'Deleted resident:  ', '::1', NULL, '2026-03-14 09:58:22'),
(20, 4, 'DELETE_RESIDENT', 'Deleted resident:  ', '::1', NULL, '2026-03-14 09:58:37'),
(21, 4, 'DELETE_RESIDENT', 'Deleted resident:  ', '::1', NULL, '2026-03-14 09:59:01'),
(22, 4, 'UPDATE_REQUEST', 'Updated request #1 to status: approved', '::1', NULL, '2026-03-14 10:02:28'),
(23, 4, 'DELETE_RESIDENT', 'Deleted resident:  ', '::1', NULL, '2026-03-14 10:02:40'),
(24, 4, 'UPDATE_PAGE', 'Updated page ID: 1 - Welcome to Barangay Cabuco', '::1', NULL, '2026-03-14 10:39:41'),
(25, 4, 'TOGGLE_SERVICE', 'Service ID: 3 deactivated', '::1', NULL, '2026-03-14 10:55:19'),
(26, 4, 'TOGGLE_SERVICE', 'Service ID: 5 deactivated', '::1', NULL, '2026-03-14 10:55:25'),
(27, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 11:33:18'),
(28, 4, 'LOGIN', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 11:33:22'),
(29, 4, 'GENERATE_QR', 'Generated QR code for resident: John Rey Mabale', '::1', NULL, '2026-03-14 11:35:49'),
(30, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 15:35:00'),
(31, 4, 'LOGIN', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 15:35:05'),
(32, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 15:43:35'),
(33, 4, 'LOGIN', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 15:43:40'),
(34, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 15:48:15'),
(35, 4, 'LOGIN', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 15:48:21'),
(36, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 15:55:28'),
(37, 4, 'LOGIN', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 15:55:31'),
(38, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 16:27:15'),
(39, 6, 'LOGIN', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 16:27:20'),
(40, 6, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 16:37:15'),
(41, 5, 'LOGIN', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 16:37:22'),
(42, 5, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 16:37:26'),
(43, 4, 'LOGIN', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 16:37:28'),
(44, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 16:47:56'),
(45, 4, 'LOGIN', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 16:48:00'),
(46, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 17:39:07'),
(47, 5, 'LOGIN', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 17:39:11'),
(48, 5, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 17:39:15'),
(49, 4, 'LOGIN', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 17:39:19'),
(50, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 17:47:55'),
(51, 5, 'LOGIN', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 17:47:57'),
(52, 5, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 17:49:07'),
(53, 4, 'LOGIN', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 17:49:11'),
(54, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 18:09:47'),
(55, 5, 'LOGIN', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 18:10:00'),
(56, 5, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 18:10:17'),
(57, 6, 'LOGIN', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 18:10:25'),
(58, 6, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 18:10:39'),
(59, 4, 'LOGIN', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 18:10:43'),
(60, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 18:17:24'),
(61, 6, 'LOGIN', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 18:17:29'),
(62, 6, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 18:17:40'),
(63, 4, 'LOGIN', 'Admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 18:17:47'),
(64, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 19:08:19'),
(65, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-14 19:08:24'),
(66, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-14 19:33:39'),
(67, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-14 19:33:42'),
(68, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-15 08:14:43'),
(69, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-15 08:14:46'),
(70, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-15 08:26:33'),
(71, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-15 08:26:39'),
(72, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-15 08:59:48'),
(73, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-15 09:01:19'),
(74, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-15 09:01:23'),
(75, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-15 09:01:28'),
(76, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-15 09:20:40'),
(77, 6, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-15 09:20:44'),
(78, 6, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-15 09:21:04'),
(79, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-15 09:21:07'),
(80, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-15 09:21:27'),
(81, 6, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-15 09:21:32'),
(82, 6, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-15 09:21:45'),
(83, 5, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-15 09:21:49'),
(84, 5, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-15 09:21:54'),
(85, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-15 09:21:58'),
(86, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-15 09:29:03'),
(87, 6, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-15 09:29:06'),
(88, 6, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-15 09:29:39'),
(89, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-15 09:29:45'),
(90, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-15 09:29:58'),
(91, 6, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-15 09:30:02'),
(92, 6, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-15 09:30:09'),
(93, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-15 09:30:12'),
(94, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-15 09:41:32'),
(95, 6, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-15 09:41:36'),
(96, 6, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-15 09:41:40'),
(97, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-15 09:41:44'),
(98, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-23 06:41:26'),
(99, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-23 07:02:53'),
(100, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-23 07:02:59'),
(101, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-23 07:03:29'),
(102, 5, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-23 07:03:33'),
(103, 5, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-23 07:03:50'),
(104, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-23 07:03:54'),
(105, 4, 'LOGOUT', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-23 07:11:21'),
(106, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-23 07:11:23'),
(107, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-23 07:37:28'),
(108, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-23 07:41:01'),
(109, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-23 07:42:54'),
(110, 4, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-23 08:01:41'),
(111, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-23 08:07:25'),
(112, 4, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-23 08:07:51'),
(113, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-23 08:08:01'),
(114, 4, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-23 08:08:08'),
(115, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-23 08:11:09'),
(116, 4, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-23 08:18:10'),
(117, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-23 08:18:13'),
(118, 4, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-23 08:37:15'),
(119, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-23 08:37:18'),
(120, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-23 08:50:34'),
(121, 4, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-23 08:52:01'),
(122, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-23 08:52:03'),
(123, 4, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-23 09:10:48'),
(124, 5, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-23 09:10:51'),
(125, 5, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-23 09:11:06'),
(126, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-23 09:11:19'),
(127, 4, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-23 09:33:43'),
(128, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-23 09:33:46'),
(129, 4, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-23 09:58:20'),
(130, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-23 09:58:23'),
(131, 4, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-23 10:06:09'),
(132, 5, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-23 10:06:12'),
(133, 5, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-23 10:06:17'),
(134, 5, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-23 10:06:23'),
(135, 5, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-23 10:06:30'),
(136, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-23 10:06:34'),
(137, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-25 23:30:44'),
(138, 4, 'TOGGLE_SERVICE', 'Service ID: 3 activated', '::1', NULL, '2026-03-25 23:44:51'),
(139, 4, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-26 00:17:59'),
(140, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-26 00:18:05'),
(141, 4, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-26 00:37:21'),
(142, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-26 00:37:23'),
(143, 4, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-26 01:05:20'),
(144, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-26 02:09:31'),
(145, 4, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-26 02:13:56'),
(146, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-26 02:20:15'),
(147, 4, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-26 02:40:21'),
(148, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-26 02:40:31'),
(149, 4, 'ADD_ANNOUNCEMENT', 'Added new announcement: hi', '::1', NULL, '2026-03-26 02:46:23'),
(150, 4, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-26 03:48:19'),
(151, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-26 03:48:23'),
(152, 4, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-26 07:05:01'),
(153, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-26 07:05:04'),
(154, 4, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-26 07:06:48'),
(155, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-26 07:09:42'),
(156, 4, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-26 07:13:24'),
(157, 5, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-26 07:13:27'),
(158, 5, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-26 07:13:58'),
(159, 6, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-26 07:14:03'),
(160, 6, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-26 07:14:11'),
(161, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-26 07:16:49'),
(162, 4, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-26 08:33:43'),
(163, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-26 09:11:23'),
(164, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-28 04:07:54'),
(165, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-28 04:46:01'),
(166, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-28 05:08:58'),
(167, 4, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-28 05:09:07'),
(168, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-28 05:09:11'),
(169, 4, 'UPDATE_REQUEST', 'Updated request #3 to status: rejected', '::1', NULL, '2026-03-28 05:33:01'),
(170, 4, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-28 06:18:13'),
(171, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-28 06:18:53'),
(172, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-28 06:31:06'),
(173, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-28 06:36:49'),
(174, 6, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-28 06:50:57'),
(175, 6, 'LOGOUT', 'Admin logged out', '::1', NULL, '2026-03-28 07:10:53'),
(176, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-28 07:10:56'),
(177, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-28 07:48:17');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `posted_by` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `image`, `posted_by`, `status`, `created_at`) VALUES
(1, 'hi', 'yow', NULL, 4, 'active', '2026-03-26 02:46:23');

-- --------------------------------------------------------

--
-- Table structure for table `carousel_images`
--

CREATE TABLE `carousel_images` (
  `id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carousel_images`
--

INSERT INTO `carousel_images` (`id`, `image_path`, `title`, `caption`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '/testweb/assets/uploads/carousel/carousel_1774676872_69c76b886d1f3.jpg', '', '', 0, 1, '2026-03-28 05:47:52', '2026-03-28 05:47:52'),
(2, '/testweb/assets/uploads/carousel/carousel_1774677196_69c76ccc8ca76.png', '', '', 0, 1, '2026-03-28 05:53:16', '2026-03-28 05:53:16');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `success` tinyint(1) DEFAULT 0,
  `attempt_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `success`, `attempt_time`) VALUES
(1, 'johnreyfernandez41@gmail.com', '::1', 0, '2026-03-26 09:50:18'),
(2, 'johnreyfernandez42@gmail.com', '::1', 0, '2026-03-26 09:51:24');

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `page_name` varchar(50) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`id`, `page_name`, `title`, `content`, `meta_description`, `featured_image`, `updated_by`, `last_updated`) VALUES
(1, 'home', 'Welcome to Barangay Cabuco', '<h1>Welcome to Our Barangay</h1><p>This is the official website of Barangay [Name]. We are committed to serving our community with excellence and transparency.</p>', '', NULL, 4, '2026-03-14 10:39:41'),
(2, 'announcements', 'Barangay Announcements', '<p>Stay updated with the latest news and announcements from your Barangay.</p>', NULL, NULL, NULL, '2026-03-13 04:28:56'),
(3, 'services', 'Barangay Services', '<p>We offer various services to cater to the needs of our residents.</p>', NULL, NULL, NULL, '2026-03-13 04:28:56'),
(4, 'about', 'About Us', '<p>Learn more about Barangay [Name], its history, mission, and vision.</p>', NULL, NULL, NULL, '2026-03-13 04:28:56');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`, `used`, `created_at`) VALUES
(1, 2, '000e441b1da7b7b8f5d0b2fb6d0b6a7602df2793ef2b837607dd3d48c8ea0b37', '2026-03-26 04:02:31', 0, '2026-03-26 10:02:31'),
(4, 1, '26a474357a87e4f533a7a8c1070d513140cecd5ebefbd8ae7eca32867bbf6df0', '2026-03-28 08:44:29', 0, '2026-03-28 14:44:29');

-- --------------------------------------------------------

--
-- Table structure for table `qr_codes`
--

CREATE TABLE `qr_codes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `qr_code_data` text DEFAULT NULL,
  `qr_code_image` varchar(255) DEFAULT NULL,
  `generated_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qr_codes`
--

INSERT INTO `qr_codes` (`id`, `user_id`, `qr_code_data`, `qr_code_image`, `generated_date`, `expires_date`, `is_active`) VALUES
(1, 1, 'BARANGAY-RESIDENT-000001-20260314-69b54815ed21d', NULL, '2026-03-14 11:35:49', '2027-03-14', 1);

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `remember_tokens`
--

INSERT INTO `remember_tokens` (`id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(1, 2, '177582f4c81f6a23420f23289fb01f723a9aa8634746d828ddef40241d3fb3df', '2026-04-25 02:54:55', '2026-03-26 09:54:55'),
(2, 2, 'b9b495a1565acdc78baa4c887dc9f0d5e17fadda2d385c5d070539df07bb47a8', '2026-04-25 02:55:13', '2026-03-26 09:55:13');

-- --------------------------------------------------------

--
-- Table structure for table `resident_info`
--

CREATE TABLE `resident_info` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `civil_status` enum('single','married','widowed','separated') DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `monthly_income` decimal(10,2) DEFAULT NULL,
  `household_count` int(11) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_number` varchar(20) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `middle_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `birth_place` varchar(255) DEFAULT NULL,
  `citizenship` varchar(100) DEFAULT 'Filipino',
  `contact_number` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `emergency_contact_relation` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resident_info`
--

INSERT INTO `resident_info` (`id`, `user_id`, `birth_date`, `age`, `gender`, `civil_status`, `occupation`, `monthly_income`, `household_count`, `emergency_contact_name`, `emergency_contact_number`, `updated_at`, `middle_name`, `suffix`, `birth_place`, `citizenship`, `contact_number`, `address`, `barangay`, `city`, `province`, `zip_code`, `emergency_contact_relation`) VALUES
(2, 1, '2004-04-24', NULL, 'male', 'single', '', NULL, NULL, 'yuehan', '09625151044', '2026-03-28 06:33:58', 'Fernandez', '', 'Davao Del Norte', 'Filipino', '09625151044', 'blk 6 b lot 3 phase 3', 'Aguado', 'Trece Martires City', 'Cavite', '4109', 'Mother');

-- --------------------------------------------------------

--
-- Table structure for table `resident_reports`
--

CREATE TABLE `resident_reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `report_type` varchar(50) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `status` enum('pending','in-progress','resolved','closed') DEFAULT 'pending',
  `admin_remarks` text DEFAULT NULL,
  `reported_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_date` timestamp NULL DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resident_requests`
--

CREATE TABLE `resident_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `request_type` varchar(50) DEFAULT 'online',
  `details` text NOT NULL,
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `admin_remarks` text DEFAULT NULL,
  `qr_token` varchar(100) DEFAULT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_date` timestamp NULL DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resident_requests`
--

INSERT INTO `resident_requests` (`id`, `user_id`, `service_id`, `request_type`, `details`, `status`, `admin_remarks`, `qr_token`, `request_date`, `processed_date`, `processed_by`) VALUES
(3, 1, 1, 'walk-in', 'fhfg', 'rejected', '', 'REQ-000003-789e519a', '2026-03-28 05:30:27', '2026-03-28 05:33:01', 4);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `processing_time` varchar(100) DEFAULT NULL,
  `fee` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `service_name`, `description`, `requirements`, `processing_time`, `fee`, `is_active`, `created_at`) VALUES
(1, 'Barangay Clearance', 'Official document certifying a person\'s residency and good moral character', NULL, '30 minutes', 50.00, 1, '2026-03-13 04:28:56'),
(2, 'Certificate of Indigency', 'Certificate for residents belonging to low-income families', NULL, '30 minutes', 30.00, 1, '2026-03-13 04:28:56'),
(3, 'Business Clearance', 'Clearance for business permit application', NULL, '1 hour', 100.00, 1, '2026-03-13 04:28:56'),
(4, 'Residency Certificate', 'Proof of residency in the barangay', NULL, '30 minutes', 50.00, 1, '2026-03-13 04:28:56'),
(5, 'hi', 'hello', '123', '30', 50.00, 0, '2026-03-14 09:14:37');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Barangay System', '2026-03-14 08:23:28', '2026-03-14 08:23:28'),
(2, 'barangay_name', 'Barangay San Jose', '2026-03-14 08:23:28', '2026-03-14 08:23:28'),
(3, 'barangay_address', '123 Main Street, City, Province', '2026-03-14 08:23:28', '2026-03-14 08:23:28'),
(4, 'barangay_contact', '(123) 456-7890', '2026-03-14 08:23:28', '2026-03-14 08:23:28'),
(5, 'barangay_email', 'info@barangay.gov.ph', '2026-03-14 08:23:28', '2026-03-14 08:23:28'),
(6, 'system_version', '1.0.0', '2026-03-14 08:23:28', '2026-03-14 08:23:28');

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `position_category` enum('barangay_official','sk_official','staff','volunteer') DEFAULT 'barangay_official',
  `biography` text DEFAULT NULL,
  `contact_info` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `term_start` date DEFAULT NULL,
  `term_end` date DEFAULT NULL,
  `committee` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team_members`
--

INSERT INTO `team_members` (`id`, `full_name`, `position`, `position_category`, `biography`, `contact_info`, `profile_image`, `display_order`, `term_start`, `term_end`, `committee`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Juan Dela Cruz', 'Barangay Captain', 'barangay_official', '', '', 'team_1773490660_69b551e48bbb4.JPG', 3, NULL, NULL, '', 1, '2026-03-14 11:30:54', '2026-03-14 12:17:58'),
(2, 'Maria Santos', 'Barangay Kagawad', 'barangay_official', NULL, NULL, NULL, 0, NULL, NULL, NULL, 1, '2026-03-14 11:30:54', '2026-03-14 11:30:54'),
(3, 'Kevin Mercado', 'SK Chairman', 'sk_official', NULL, NULL, NULL, 0, NULL, NULL, NULL, 1, '2026-03-14 11:30:54', '2026-03-14 11:30:54'),
(4, 'Luzviminda Cruz', 'Administrative Aide', 'staff', NULL, NULL, NULL, 0, NULL, NULL, NULL, 1, '2026-03-14 11:30:54', '2026-03-14 11:30:54'),
(5, 'Rico Mercado', 'Barangay Tanod', 'volunteer', NULL, NULL, NULL, 0, NULL, NULL, NULL, 1, '2026-03-14 11:30:54', '2026-03-14 11:30:54');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `user_type` enum('resident','admin') DEFAULT 'resident',
  `is_online` tinyint(1) DEFAULT 0,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_verified` tinyint(1) DEFAULT 0,
  `status` varchar(20) DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `profile_pic`, `password`, `first_name`, `last_name`, `address`, `contact_number`, `user_type`, `is_online`, `last_activity`, `created_at`, `is_verified`, `status`, `last_login`, `last_login_ip`) VALUES
(1, 'john rey', 'johnreyfernandez41@gmail.com', 'user_1_1774678631.jpg', '$2y$10$jKr21pNJn/n/aJH9IGNwFOD5oAqAOTS7Hb8z9wsAQll6vP.QAFGcS', 'John Rey', 'Mabale', 'blk 6 b lot 3 phase 3, brgy, aguado trece martires', '09625151044', 'resident', 0, '2026-03-28 07:44:28', '2026-03-13 04:42:03', 0, 'active', '2026-03-28 15:44:28', NULL),
(2, 'Johnreymabale', 'mabalejohnreyf@gmail.com', NULL, '$2y$10$jM/8TfMz.6mx/K8cOgJS2e2u6/al1Rqr1fzjGu9HxQ5auHDFdjCq.', 'John Rey', 'Mabale', 'blk 6 b lot 3 phase 3, brgy, aguado trece martires', '09625151044', 'resident', 0, '2026-03-28 07:44:24', '2026-03-26 01:51:09', 0, 'active', '2026-03-28 15:43:50', '::1');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `about_sections`
--
ALTER TABLE `about_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `page_id` (`page_id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `posted_by` (`posted_by`);

--
-- Indexes for table `carousel_images`
--
ALTER TABLE `carousel_images`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_time` (`attempt_time`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `page_name` (`page_name`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `resident_info`
--
ALTER TABLE `resident_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `resident_reports`
--
ALTER TABLE `resident_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `resolved_by` (`resolved_by`),
  ADD KEY `idx_reports_status` (`status`),
  ADD KEY `idx_reports_priority` (`priority`),
  ADD KEY `idx_reports_date` (`reported_date`);

--
-- Indexes for table `resident_requests`
--
ALTER TABLE `resident_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `qr_token` (`qr_token`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_requests_status` (`status`),
  ADD KEY `idx_requests_date` (`request_date`),
  ADD KEY `idx_requests_user` (`user_id`),
  ADD KEY `idx_requests_service` (`service_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `about_sections`
--
ALTER TABLE `about_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=178;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `carousel_images`
--
ALTER TABLE `carousel_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `qr_codes`
--
ALTER TABLE `qr_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `resident_info`
--
ALTER TABLE `resident_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `resident_reports`
--
ALTER TABLE `resident_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resident_requests`
--
ALTER TABLE `resident_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `about_sections`
--
ALTER TABLE `about_sections`
  ADD CONSTRAINT `about_sections_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`);

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `admins` (`id`);

--
-- Constraints for table `pages`
--
ALTER TABLE `pages`
  ADD CONSTRAINT `pages_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`);

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD CONSTRAINT `qr_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `resident_info`
--
ALTER TABLE `resident_info`
  ADD CONSTRAINT `resident_info_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `resident_reports`
--
ALTER TABLE `resident_reports`
  ADD CONSTRAINT `resident_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `resident_reports_ibfk_2` FOREIGN KEY (`resolved_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `resident_requests`
--
ALTER TABLE `resident_requests`
  ADD CONSTRAINT `resident_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `resident_requests_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `resident_requests_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

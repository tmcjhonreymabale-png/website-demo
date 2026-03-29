-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 26, 2026 at 01:22 AM
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
(140, 4, 'LOGIN', 'Admin logged in', '::1', NULL, '2026-03-26 00:18:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

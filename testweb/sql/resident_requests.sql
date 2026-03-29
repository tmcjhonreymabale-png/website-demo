-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 28, 2026 at 02:59 PM
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
-- Table structure for table `resident_requests`
--

CREATE TABLE `resident_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `request_type` varchar(50) DEFAULT 'online',
  `preferred_day` varchar(20) DEFAULT NULL,
  `preferred_time` varchar(50) DEFAULT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `preferred_date` date DEFAULT NULL,
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

INSERT INTO `resident_requests` (`id`, `user_id`, `service_id`, `request_type`, `preferred_day`, `preferred_time`, `schedule_id`, `preferred_date`, `details`, `status`, `admin_remarks`, `qr_token`, `request_date`, `processed_date`, `processed_by`) VALUES
(5, 1, 3, 'walk-in', 'Saturday', '', NULL, '2026-04-04', 'erger', 'pending', NULL, 'REQ-000005-5eb3dfd9', '2026-03-28 10:01:26', NULL, NULL),
(6, 1, 1, 'online', 'Saturday', '', NULL, '2026-03-30', 'dfbdf', 'pending', NULL, 'REQ-000006-75ab9c77', '2026-03-28 10:20:26', NULL, NULL);

--
-- Indexes for dumped tables
--

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
  ADD KEY `idx_requests_service` (`service_id`),
  ADD KEY `idx_schedule` (`schedule_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `resident_requests`
--
ALTER TABLE `resident_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

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

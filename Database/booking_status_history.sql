-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 29, 2025 at 11:08 PM
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
-- Database: `rentcar`
--

-- --------------------------------------------------------

--
-- Table structure for table `booking_status_history`
--

CREATE TABLE `booking_status_history` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `new_status` enum('pending','approved','canceled') NOT NULL,
  `changed_by` int(11) NOT NULL,
  `admin_comment` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_status_history`
--

INSERT INTO `booking_status_history` (`id`, `booking_id`, `new_status`, `changed_by`, `admin_comment`, `created_at`) VALUES
(4, 3, 'approved', 1, '23swse', '2025-06-29 12:52:42'),
(5, 3, 'canceled', 1, '23swse', '2025-06-29 12:52:45'),
(6, 3, 'approved', 1, '23swse', '2025-06-29 12:54:49'),
(7, 3, 'canceled', 1, '23swse', '2025-06-29 13:09:28'),
(8, 3, 'approved', 1, '23swse', '2025-06-29 13:17:27'),
(9, 3, 'canceled', 1, 'you will get tomorrow', '2025-06-29 13:19:18'),
(10, 3, 'approved', 1, 'you will get tomorrow', '2025-06-29 13:19:20'),
(11, 3, 'canceled', 3, 'you will get tomorrow', '2025-06-29 20:03:27'),
(12, 3, 'approved', 3, 'you will get tomorrow', '2025-06-29 20:03:36'),
(13, 3, 'canceled', 3, 'you will get tomorrow', '2025-06-29 20:24:52'),
(14, 3, 'approved', 3, 'you will get tomorrow', '2025-06-29 20:34:42');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `booking_status_history`
--
ALTER TABLE `booking_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `booking_status_history`
--
ALTER TABLE `booking_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking_status_history`
--
ALTER TABLE `booking_status_history`
  ADD CONSTRAINT `booking_status_history_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_status_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

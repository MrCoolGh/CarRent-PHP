-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 29, 2025 at 04:43 PM
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
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `user_type` enum('customer','manager','admin') NOT NULL DEFAULT 'customer',
  `profile_image` varchar(500) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `email_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone`, `password`, `date_of_birth`, `user_type`, `profile_image`, `status`, `email_verified`, `created_at`, `updated_at`) VALUES
(1, 'Patrick', 'Dwamena', 'pd855000@gmail.com', '0543877778', '$2y$10$B4k0JoVUOyMAoU7beUW6euEaEI3a8X3jjXf6WhRIxwhuWH3MnRFqK', '1990-01-01', 'admin', '../assets/uploads/profiles/profile_6860945893113.png', 'active', 0, '2025-06-29 01:18:16', '2025-06-29 11:09:54'),
(2, 'Fatima', 'Ibrahim', 'fjssbfkvsm@gmail.com', '0534453781', '$2y$10$ABkU7kPyGDLNLD.2rdj2n.CSpBtibygkSLnaiB/jfpm4AuTuDcaPm', '2025-06-29', 'manager', NULL, 'active', 0, '2025-06-29 01:19:52', '2025-06-29 01:20:12'),
(3, 'COMFORT', 'DENKYI', 'comfortdenkyi855@gmail.com', '0543877778', '$2y$10$i0mTovLVSvLFsx0BoPyf7uniPEZMDzhJgMY9UO.N9EBuRGynvG/wm', '2025-06-30', 'customer', '../assets/uploads/profiles/profile_0_1751197144.png', 'active', 0, '2025-06-29 11:39:04', '2025-06-29 11:39:04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_user_type` (`user_type`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

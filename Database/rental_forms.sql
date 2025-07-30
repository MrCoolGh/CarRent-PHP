-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 29, 2025 at 10:04 PM
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
-- Table structure for table `rental_forms`
--

CREATE TABLE `rental_forms` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `dob` date NOT NULL,
  `id_number` varchar(100) NOT NULL,
  `license_number` varchar(100) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `address` varchar(255) NOT NULL,
  `emergency_name` varchar(255) NOT NULL,
  `emergency_phone` varchar(50) NOT NULL,
  `purpose` varchar(100) NOT NULL,
  `other_purpose` varchar(255) DEFAULT NULL,
  `id_upload` varchar(255) DEFAULT NULL,
  `license_upload` varchar(255) DEFAULT NULL,
  `other_docs` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','approved','not approved') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rental_forms`
--

INSERT INTO `rental_forms` (`id`, `full_name`, `dob`, `id_number`, `license_number`, `phone`, `address`, `emergency_name`, `emergency_phone`, `purpose`, `other_purpose`, `id_upload`, `license_upload`, `other_docs`, `user_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Patrick Dwamena', '2025-06-28', '23456783ewd', '2134567uyh', '+233543877778', 'Dansoman High Street,Accra', 'Patrick', '+233543877778', 'personal', '', 'Screenshot 2025-06-06 200948.png', 'Screenshot 2025-06-04 112827.png', '\"Screenshot 2025-06-06 200425.png\"', 3, 'pending', '2025-06-29 17:24:28', '2025-06-29 17:24:28'),
(2, 'Patrick Dwamena', '2025-06-28', '23456783ewd', '2134567uyh', '+233543877778', 'Dansoman High Street,Accra', 'Patrick', '+233543877778', 'personal', '', 'Screenshot 2025-06-06 200948.png', 'Screenshot 2025-06-04 112827.png', '\"Screenshot 2025-06-06 200425.png\"', 3, 'pending', '2025-06-29 17:24:37', '2025-06-29 17:24:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `rental_forms`
--
ALTER TABLE `rental_forms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `rental_forms`
--
ALTER TABLE `rental_forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `rental_forms`
--
ALTER TABLE `rental_forms`
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

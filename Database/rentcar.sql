-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 02, 2025 at 01:28 PM
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
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `pickup_location` varchar(255) NOT NULL,
  `pickup_date` date NOT NULL,
  `pickup_time` time NOT NULL DEFAULT '09:00:00',
  `dropoff_date` date NOT NULL,
  `dropoff_time` time NOT NULL DEFAULT '18:00:00',
  `total_days` int(11) NOT NULL,
  `price_per_day` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `customer_note` text DEFAULT NULL,
  `booking_status` enum('pending','approved','canceled') DEFAULT 'pending',
  `admin_message` text DEFAULT NULL,
  `booking_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `car_id`, `pickup_location`, `pickup_date`, `pickup_time`, `dropoff_date`, `dropoff_time`, `total_days`, `price_per_day`, `total_amount`, `customer_note`, `booking_status`, `admin_message`, `booking_date`, `updated_at`) VALUES
(16, 27, 18, 'Our Office', '2025-07-16', '09:00:00', '2025-08-09', '18:00:00', 25, 500.00, 12500.00, '', 'approved', 'we', '2025-07-01 23:27:27', '2025-07-02 09:01:54'),
(17, 27, 18, 'Our Office', '2025-08-29', '09:00:00', '2025-09-20', '18:00:00', 23, 500.00, 11500.00, '', 'approved', 'fghj', '2025-07-01 23:28:06', '2025-07-02 09:01:48');

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
(57, 17, 'approved', 29, 'fghj', '2025-07-01 23:29:02'),
(58, 17, 'canceled', 29, 'fghj', '2025-07-01 23:29:27'),
(59, 17, 'approved', 27, 'fghj', '2025-07-02 09:01:48'),
(60, 16, 'approved', 27, 'we', '2025-07-02 09:01:54');

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

CREATE TABLE `cars` (
  `car_id` int(11) NOT NULL,
  `car_name` varchar(255) NOT NULL,
  `transmission` enum('Automatic','Manual') NOT NULL,
  `fuel_type` enum('Petrol','Diesel','Electric') NOT NULL,
  `year` int(11) NOT NULL CHECK (`year` >= 1900),
  `mileage` int(11) NOT NULL,
  `people_capacity` int(11) NOT NULL CHECK (`people_capacity` > 0),
  `price_per_day` decimal(10,2) NOT NULL CHECK (`price_per_day` > 0),
  `description` text NOT NULL,
  `main_image` varchar(255) DEFAULT NULL,
  `extra_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extra_images`)),
  `added_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`car_id`, `car_name`, `transmission`, `fuel_type`, `year`, `mileage`, `people_capacity`, `price_per_day`, `description`, `main_image`, `extra_images`, `added_by`, `created_at`, `updated_at`) VALUES
(18, 'Toyota Yaris', 'Automatic', 'Diesel', 2021, 12345, 5, 500.00, 'A good car description for sale should be clear, concise, and informative, highlighting both the positives and any potential downsides of the vehicle. It should include key details like the make, model, year, mileage, and condition, as well as any unique features or service history. Transparency about the car\'s history and a call to action for potential buyers are also crucial.', 'assets/uploads/cars/1751409181_main_6864621d63380.jpg', '[\"assets\\/uploads\\/cars\\/1751409181_extra_6864621d63c6c.jpg\",\"assets\\/uploads\\/cars\\/1751409181_extra_6864621d6559a.jpg\",\"assets\\/uploads\\/cars\\/1751409181_extra_6864621d6602c.jpg\",\"assets\\/uploads\\/cars\\/1751409181_extra_6864621d66add.jpg\"]', 27, '2025-07-01 22:33:01', '2025-07-01 22:33:01'),
(19, 'Toyota Vitz', 'Automatic', 'Petrol', 2021, 65432, 4, 400.00, 'A good car description for sale should be clear, concise, and informative, highlighting both the positives and any potential downsides of the vehicle. It should include key details like the make, model, year, mileage, and condition, as well as any unique features or service history. Transparency about the car\'s history and a call to action for potential buyers are also crucial.', 'assets/uploads/cars/1751409344_main_686462c099670.jpg', '[\"assets\\/uploads\\/cars\\/1751409344_extra_686462c09a009.jpg\",\"assets\\/uploads\\/cars\\/1751409344_extra_686462c09aa9e.jpg\",\"assets\\/uploads\\/cars\\/1751409344_extra_686462c09bbf2.jpg\"]', 27, '2025-07-01 22:35:44', '2025-07-01 22:35:44'),
(20, 'VELAE', 'Automatic', 'Petrol', 2009, 6, 3, 300.00, 'A good car description for sale should be clear, concise, and informative, highlighting both the positives and any potential downsides of the vehicle. It should include key details like the make, model, year, mileage, and condition, as well as any unique features or service history. Transparency about the car\'s history and a call to action for potential buyers are also crucial.', 'assets/uploads/cars/1751411751_main_68646c2752f56.jpg', '[\"assets\\/uploads\\/cars\\/1751412329_extra_68646e69ccc74.jpg\",\"assets\\/uploads\\/cars\\/1751412329_extra_68646e69ce83a.jpg\",\"assets\\/uploads\\/cars\\/1751412329_extra_68646e69cf459.jpg\"]', 29, '2025-07-01 23:13:54', '2025-07-01 23:25:29');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `message_text`, `sent_at`, `is_read`) VALUES
(79, 29, 27, 'sup', '2025-07-01 22:45:40', 1),
(80, 27, 29, 'Okay man', '2025-07-01 22:45:54', 1);

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone`, `password`, `date_of_birth`, `user_type`, `profile_image`, `status`, `email_verified`, `created_at`, `updated_at`, `reset_token`, `token_expires`) VALUES
(25, 'Pat', 'Dwa', 'patrickdwamena123@gmail.com', '0509548113', '$2y$10$4MMVA1/f067O9a4.o7PMn.056VbS9CWtlhMeL1Elg4CE.2s0jzaWK', '1990-01-27', 'manager', '../assets/uploads/profiles/profile_25_1751446315.jpg', 'active', 0, '2025-07-01 12:24:13', '2025-07-02 08:51:55', NULL, NULL),
(27, 'Patrick', 'Dwamena', 'pd855000@gmail.com', '0543877778', '$2y$10$WzWhRjZZjEtysNTMB1LiA.ekd4Fr0Xl/0uYll1jno9tFXyG..CeUu', '2025-07-04', 'manager', '../assets/uploads/profiles/profile_27_1751411640.jpg', 'active', 0, '2025-07-01 17:39:56', '2025-07-02 08:51:31', NULL, NULL),
(29, 'Senior', 'Dev', 'jjrrichardson@st.ug.edu.gh', '0544453130', '$2y$10$BAbQ0Qj/pCqnynK9D7Efb.j/eDfj/HXpJk53G0QDqYrKadi5Oijyq', '1990-01-01', 'admin', '../assets/uploads/profiles/profile_29_1751411060.jpg', 'active', 0, '2025-07-01 22:25:31', '2025-07-01 23:18:06', NULL, NULL),
(30, 'Fatima', 'Ibrahim', 'fjssbfkvsm@gmail.com', '0534453781', '$2y$10$EUgIbrTgIXgOCCYrU4k6YOs/MMh.ou/Mb39YXQ2C5Xx.YktEuNdeO', '2025-07-26', 'customer', '../assets/uploads/profiles/profile_0_1751446492.jpg', 'active', 0, '2025-07-02 08:54:52', '2025-07-02 08:54:52', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `car_id` (`car_id`);

--
-- Indexes for table `booking_status_history`
--
ALTER TABLE `booking_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`car_id`),
  ADD KEY `added_by` (`added_by`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sender` (`sender_id`),
  ADD KEY `fk_receiver` (`receiver_id`);

--
-- Indexes for table `rental_forms`
--
ALTER TABLE `rental_forms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

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
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `booking_status_history`
--
ALTER TABLE `booking_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `car_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `rental_forms`
--
ALTER TABLE `rental_forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`car_id`) REFERENCES `cars` (`car_id`) ON DELETE CASCADE;

--
-- Constraints for table `booking_status_history`
--
ALTER TABLE `booking_status_history`
  ADD CONSTRAINT `booking_status_history_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_status_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cars`
--
ALTER TABLE `cars`
  ADD CONSTRAINT `cars_ibfk_1` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rental_forms`
--
ALTER TABLE `rental_forms`
  ADD CONSTRAINT `rental_forms_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

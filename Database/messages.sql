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
(26, 3, 1, 'hi', '2025-06-29 15:38:30', 1),
(27, 3, 1, 'hi', '2025-06-29 15:38:30', 1),
(28, 3, 1, 'hi', '2025-06-29 15:38:31', 1),
(29, 3, 1, 'hi', '2025-06-29 15:38:31', 1),
(30, 3, 1, 'hi', '2025-06-29 15:38:32', 1),
(31, 3, 1, 'hi', '2025-06-29 15:38:32', 1),
(32, 3, 1, 'hi', '2025-06-29 15:38:32', 1),
(33, 3, 1, 'hi', '2025-06-29 15:38:32', 1),
(34, 3, 1, 'hi', '2025-06-29 15:38:32', 1),
(35, 3, 1, 'hi', '2025-06-29 15:38:33', 1),
(36, 3, 1, 'hi', '2025-06-29 15:38:33', 1),
(37, 3, 1, 'hi', '2025-06-29 15:38:33', 1),
(38, 3, 1, 'hi', '2025-06-29 15:38:34', 1),
(39, 3, 1, 'hi', '2025-06-29 15:38:34', 1),
(40, 3, 1, 'hi', '2025-06-29 15:38:34', 1),
(41, 3, 1, 'hi', '2025-06-29 15:38:34', 1),
(42, 3, 1, 'hi', '2025-06-29 15:38:35', 1),
(43, 1, 3, 'hiii', '2025-06-29 15:43:41', 1),
(44, 1, 3, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', '2025-06-29 15:43:55', 1),
(45, 3, 1, 'hello how are you', '2025-06-29 15:46:09', 1),
(46, 1, 3, 'im okay', '2025-06-29 15:47:31', 1),
(47, 1, 2, 'Hello', '2025-06-29 16:01:09', 0),
(48, 1, 2, 'coem oemc', '2025-06-29 16:02:05', 0),
(49, 1, 3, 'hi', '2025-06-29 16:03:12', 1),
(50, 1, 3, 'hi', '2025-06-29 16:06:53', 1),
(51, 3, 1, 'Hello come come come', '2025-06-29 16:08:38', 1),
(52, 1, 3, 'hi', '2025-06-29 16:34:59', 1),
(53, 1, 3, 'how', '2025-06-29 16:35:01', 1),
(54, 3, 1, 'okay okay', '2025-06-29 16:35:49', 1),
(55, 1, 3, 'hello', '2025-06-29 16:39:25', 1),
(56, 1, 3, 'How are you doing', '2025-06-29 16:39:29', 1),
(57, 1, 3, 'please please', '2025-06-29 16:39:36', 1),
(58, 3, 2, 'Hi', '2025-06-29 20:25:25', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sender` (`sender_id`),
  ADD KEY `fk_receiver` (`receiver_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

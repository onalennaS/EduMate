-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 20, 2025 at 05:03 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `edumate_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('student','teacher','admin') NOT NULL,
  `grade` int(2) DEFAULT NULL,
  `accessibility_needs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`accessibility_needs`)),
  `bio` text DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `first_name`, `last_name`, `email`, `phone`, `password`, `user_type`, `grade`, `accessibility_needs`, `bio`, `department`, `profile_picture`, `full_name`, `created_at`, `last_login`) VALUES
(2, 'ona', 'onalenna student', 'Hamese', 'onalennahamese07@gmail.com', NULL, '$2y$10$W6cu3XqNbxhdI7Pd5DRuLe1hCO2um1Myz9xVq83E3Uh8NtU1St6Nm', 'student', 12, NULL, NULL, NULL, 'uploads/profile_pictures/student_2_1758292138.png', NULL, '2025-09-06 10:49:30', '2025-09-19 15:03:50'),
(4, 'Teacher', 'Teacher Onalenna', 'Hamese', 'onalennahamese@gmail.com', NULL, '$2y$10$6gNHEqw.kbsq22UC7LlXeuDcaA3SuykOLGSYzbAFARYeE.V48ZnSy', 'teacher', NULL, NULL, 'i am me dah', 'Teacher', 'uploads/profile_pictures/teacher_4_1758292227.png', NULL, '2025-09-09 23:09:43', '2025-09-19 15:09:37'),
(6, 'onalenna', NULL, NULL, 'onalennahames@gmail.com', NULL, '$2y$10$iJNUbBJ0qRg6gwGJoOq.he0z5JqOobH/fXJ/zJEhnAzYCaDAyR/aa', 'student', 8, NULL, NULL, NULL, NULL, NULL, '2025-09-20 13:36:22', '2025-09-20 13:36:39'),
(10, 'admin', NULL, NULL, 'admin@edumate.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-20 14:00:54', '2025-09-20 14:08:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

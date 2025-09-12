-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 12, 2025 at 08:38 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
-- Table structure for table `accessibility_preferences`
--

CREATE TABLE `accessibility_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `visual_impairment` tinyint(1) DEFAULT 0,
  `hearing_impairment` tinyint(1) DEFAULT 0,
  `physical_disability` tinyint(1) DEFAULT 0,
  `cognitive_support` tinyint(1) DEFAULT 0,
  `font_size` enum('small','medium','large','extra-large') DEFAULT 'medium',
  `high_contrast` tinyint(1) DEFAULT 0,
  `screen_reader` tinyint(1) DEFAULT 0,
  `audio_descriptions` tinyint(1) DEFAULT 0,
  `sign_language` tinyint(1) DEFAULT 0,
  `slow_animations` tinyint(1) DEFAULT 0,
  `keyboard_navigation` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `assignment_type` enum('homework','test','project','quiz','experiment') NOT NULL,
  `due_date` datetime NOT NULL,
  `total_marks` int(4) DEFAULT 100,
  `instructions` text DEFAULT NULL,
  `accessibility_features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`accessibility_features`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `grade_id` int(11) NOT NULL,
  `course_name` varchar(200) NOT NULL,
  `course_description` text DEFAULT NULL,
  `course_code` varchar(20) NOT NULL,
  `enrollment_key` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `max_students` int(4) DEFAULT 50,
  `accessibility_features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`accessibility_features`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_materials`
--

CREATE TABLE `course_materials` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `material_title` varchar(200) NOT NULL,
  `material_type` enum('document','video','audio','link','interactive') NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `external_url` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `accessibility_features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`accessibility_features`)),
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `order_index` int(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive','completed','dropped') DEFAULT 'active',
  `final_grade` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `grade_number` int(2) NOT NULL,
  `grade_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`id`, `grade_number`, `grade_name`, `description`, `created_at`) VALUES
(1, 7, 'Grade 7', 'Senior Phase - Foundation year', '2025-09-09 21:45:19'),
(2, 8, 'Grade 8', 'Senior Phase - Intermediate year', '2025-09-09 21:45:19'),
(3, 9, 'Grade 9', 'Senior Phase - Final year', '2025-09-09 21:45:19'),
(4, 10, 'Grade 10', 'FET Phase - First year', '2025-09-09 21:45:19'),
(5, 11, 'Grade 11', 'FET Phase - Second year', '2025-09-09 21:45:19'),
(6, 12, 'Grade 12', 'FET Phase - Matric year', '2025-09-09 21:45:19');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(10) NOT NULL,
  `category` enum('core','elective','practical') NOT NULL DEFAULT 'core',
  `applicable_grades` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`applicable_grades`)),
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_name`, `subject_code`, `category`, `applicable_grades`, `description`, `is_active`, `created_at`) VALUES
(1, 'English Home Language', 'EHL', 'core', '[7,8,9,10,11,12]', 'Primary language of instruction', 1, '2025-09-09 21:45:20'),
(2, 'English First Additional Language', 'EFAL', 'core', '[7,8,9,10,11,12]', 'English as first additional language', 1, '2025-09-09 21:45:20'),
(3, 'Afrikaans Home Language', 'AHL', 'core', '[7,8,9,10,11,12]', 'Afrikaans as home language', 1, '2025-09-09 21:45:20'),
(4, 'Afrikaans First Additional Language', 'AFAL', 'core', '[7,8,9,10,11,12]', 'Afrikaans as first additional language', 1, '2025-09-09 21:45:20'),
(5, 'Mathematics', 'MATH', 'core', '[7,8,9,10,11,12]', 'Pure Mathematics', 1, '2025-09-09 21:45:20'),
(6, 'Mathematical Literacy', 'MATLIT', 'core', '[10,11,12]', 'Applied mathematics for daily life', 1, '2025-09-09 21:45:20'),
(7, 'Life Orientation', 'LO', 'core', '[7,8,9,10,11,12]', 'Personal development and life skills', 1, '2025-09-09 21:45:20'),
(8, 'Natural Sciences', 'NS', 'core', '[7,8,9]', 'Integrated science for grades 7-9', 1, '2025-09-09 21:45:20'),
(9, 'Physical Sciences', 'PS', 'elective', '[10,11,12]', 'Physics and Chemistry', 1, '2025-09-09 21:45:20'),
(10, 'Life Sciences', 'LS', 'elective', '[10,11,12]', 'Biology and life processes', 1, '2025-09-09 21:45:20'),
(11, 'Social Sciences', 'SS', 'core', '[7,8,9]', 'History and Geography integrated', 1, '2025-09-09 21:45:20'),
(12, 'History', 'HIST', 'elective', '[10,11,12]', 'Historical studies', 1, '2025-09-09 21:45:20'),
(13, 'Geography', 'GEO', 'elective', '[10,11,12]', 'Physical and human geography', 1, '2025-09-09 21:45:20'),
(14, 'Economic and Management Sciences', 'EMS', 'core', '[7,8,9]', 'Business and economic foundation', 1, '2025-09-09 21:45:20'),
(15, 'Accounting', 'ACC', 'elective', '[10,11,12]', 'Financial accounting and management', 1, '2025-09-09 21:45:20'),
(16, 'Business Studies', 'BS', 'elective', '[10,11,12]', 'Business management and entrepreneurship', 1, '2025-09-09 21:45:20'),
(17, 'Economics', 'ECON', 'elective', '[10,11,12]', 'Micro and macroeconomics', 1, '2025-09-09 21:45:20'),
(18, 'Arts and Culture', 'AC', 'core', '[7,8,9]', 'Creative arts and cultural studies', 1, '2025-09-09 21:45:20'),
(19, 'Visual Arts', 'VA', 'elective', '[10,11,12]', 'Drawing, painting, and design', 1, '2025-09-09 21:45:20'),
(20, 'Dramatic Arts', 'DA', 'elective', '[10,11,12]', 'Theatre and performance', 1, '2025-09-09 21:45:20'),
(21, 'Music', 'MUS', 'elective', '[10,11,12]', 'Music theory and performance', 1, '2025-09-09 21:45:20'),
(22, 'Technology', 'TECH', 'core', '[7,8,9]', 'Design and technology', 1, '2025-09-09 21:45:20'),
(23, 'Information Technology', 'IT', 'elective', '[10,11,12]', 'Computer applications and programming', 1, '2025-09-09 21:45:20'),
(24, 'Computer Applications Technology', 'CAT', 'elective', '[10,11,12]', 'Practical computer skills', 1, '2025-09-09 21:45:20'),
(25, 'isiZulu Home Language', 'ZULUHL', 'core', '[7,8,9,10,11,12]', 'isiZulu as home language', 1, '2025-09-09 21:45:20'),
(26, 'isiZulu First Additional Language', 'ZULUFAL', 'core', '[7,8,9,10,11,12]', 'isiZulu as additional language', 1, '2025-09-09 21:45:20'),
(27, 'isiXhosa Home Language', 'XHOSAHL', 'core', '[7,8,9,10,11,12]', 'isiXhosa as home language', 1, '2025-09-09 21:45:20'),
(28, 'isiXhosa First Additional Language', 'XHOSAFAL', 'core', '[7,8,9,10,11,12]', 'isiXhosa as additional language', 1, '2025-09-09 21:45:20'),
(29, 'Sesotho Home Language', 'SESOTHL', 'core', '[7,8,9,10,11,12]', 'Sesotho as home language', 1, '2025-09-09 21:45:20'),
(30, 'Sesotho First Additional Language', 'SESOTFAL', 'core', '[7,8,9,10,11,12]', 'Sesotho as additional language', 1, '2025-09-09 21:45:20'),
(31, 'kjjjkh', 'jijk', 'core', '[\"7\"]', '', 1, '2025-09-09 23:49:12');

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `submission_text` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `grade` decimal(5,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  `status` enum('submitted','graded','late','missing') DEFAULT 'submitted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_subjects`
--

CREATE TABLE `teacher_subjects` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_subjects`
--

INSERT INTO `teacher_subjects` (`id`, `teacher_id`, `subject_id`, `created_at`) VALUES
(7, 4, 15, '2025-09-10 00:40:47');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('student','teacher') NOT NULL,
  `grade` int(2) DEFAULT NULL,
  `accessibility_needs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`accessibility_needs`)),
  `profile_picture` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `user_type`, `grade`, `accessibility_needs`, `profile_picture`, `full_name`, `created_at`, `last_login`) VALUES
(2, 'ona', 'onalennahamese07@gmail.com', '$2y$10$/jvzwzKv7NV6hvQOMR6.KOIW/MWpe9cN/pKwmmhEmtwoqnCu8ed6C', 'student', 7, NULL, NULL, NULL, '2025-09-06 10:49:30', '2025-09-12 18:37:42'),
(4, 'Teacher', 'onalennahamese@gmail.com', '$2y$10$jOE4kObeYf1zvqlaMiIPt.vE8bN5TYVrHW85uBroiC8OznWdF7Yu6', 'teacher', NULL, NULL, NULL, NULL, '2025-09-09 23:09:43', '2025-09-10 00:41:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accessibility_preferences`
--
ALTER TABLE `accessibility_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `grade_id` (`grade_id`);

--
-- Indexes for table `course_materials`
--
ALTER TABLE `course_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`student_id`,`course_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_submission` (`assignment_id`,`student_id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_teacher_subject` (`teacher_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`);

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
-- AUTO_INCREMENT for table `accessibility_preferences`
--
ALTER TABLE `accessibility_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_materials`
--
ALTER TABLE `course_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accessibility_preferences`
--
ALTER TABLE `accessibility_preferences`
  ADD CONSTRAINT `accessibility_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `courses_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `courses_ibfk_3` FOREIGN KEY (`grade_id`) REFERENCES `grades` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_materials`
--
ALTER TABLE `course_materials`
  ADD CONSTRAINT `course_materials_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD CONSTRAINT `teacher_subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `teacher_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

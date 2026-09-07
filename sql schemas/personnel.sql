-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 26, 2026 at 04:15 PM
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
-- Database: `qpteo_directory`
--

-- --------------------------------------------------------

--
-- Table structure for table `personnel`
--

CREATE TABLE `personnel` (
  `id` int(11) NOT NULL,
  `personnel_code` varchar(20) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `nickname` varchar(100) DEFAULT NULL,
  `first_name` varchar(75) NOT NULL,
  `middle_name` varchar(75) DEFAULT '',
  `last_name` varchar(75) NOT NULL,
  `position` varchar(150) NOT NULL,
  `salary_grade` varchar(50) DEFAULT NULL,
  `salary_amount` decimal(12,2) DEFAULT NULL,
  `employment_nature` enum('Plantilla','Contract of Service') NOT NULL DEFAULT 'Plantilla',
  `designation` varchar(150) DEFAULT '',
  `office` varchar(100) NOT NULL DEFAULT 'QPTEO',
  `unit` varchar(100) NOT NULL DEFAULT 'Executive Office',
  `email` varchar(100) DEFAULT '',
  `contact_number` varchar(50) DEFAULT '',
  `birthday` date DEFAULT NULL,
  `photo_url` varchar(255) DEFAULT '',
  `job_description_path` varchar(1000) DEFAULT NULL,
  `tor_path` varchar(1000) DEFAULT NULL,
  `status` enum('Active','On Leave','Separated','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personnel`
--

INSERT INTO `personnel` (`id`, `personnel_code`, `full_name`, `nickname`, `first_name`, `middle_name`, `last_name`, `position`, `salary_grade`, `salary_amount`, `employment_nature`, `designation`, `office`, `unit`, `email`, `contact_number`, `birthday`, `photo_url`, `job_description_path`, `tor_path`, `status`, `created_at`, `updated_at`) VALUES
(1, '2601033', 'Ferdinand L. Rellorosa, CEPS', 'Iking', 'Ferdinand', 'L.', 'Rellorosa', 'Chief Education Program Specialist', 'SG 24', 102603.00, 'Plantilla', 'Chief', 'Quality Pre-Service Teacher Education Office', 'Executive Office', 'ferdinand.rellorosa@deped.gov.ph', '+63 917 123 4567', '1990-05-29', '', 'uploads/personnel_docs/[PLANTILLA] JD_QPTEO_CEPS.pdf', '', 'Active', '2026-08-23 07:33:35', '2026-08-23 08:18:09'),
(2, '2602062', 'Diane G. Francisco, PDO IV', 'Diane', 'Diane', 'G.', 'Francisco', 'Project Development Officer IV', 'SG 22', 81796.00, 'Contract of Service', 'Senior Quality Assurance Specialist', 'Quality Pre-Service Teacher Education Office', 'Quality Assurance', 'diane.francisco@deped.gov.ph', '+63 918 234 5678', '1983-12-15', '', 'uploads/personnel_docs/[COS] JD_QPTEO_PDO4.pdf', '', 'Active', '2026-08-23 07:33:35', '2026-08-23 08:18:09'),
(3, '2601012', 'Marie Kristel B. Corpin, SEPS', 'Kristel', 'Marie Kristel', 'B.', 'Corpin', 'Senior Education Program Specialist', 'SG 19', 59152.99, 'Plantilla', '', 'Quality Pre-Service Teacher Education Office', 'Programs & Operations', 'mariekristel.corpin@deped.gov.ph', '+63 919 345 6789', '1998-07-27', '', 'uploads/personnel_docs/[PLANTILLA] JD_QPTEO_SEPS.pdf', '', 'Active', '2026-08-23 07:33:35', '2026-08-23 08:32:04'),
(4, '2602089', 'Cristy A. Mendoza, PDO III', 'Cristy', 'Cristy', 'A.', 'Mendoza', 'Project Development Officer III', 'SG 18', 53818.00, 'Contract of Service', 'Research & Development Specialist', 'Quality Pre-Service Teacher Education Office', 'Programs & Operations', 'cristy.mendoza@deped.gov.ph', '+63 920 456 7890', '1991-01-29', '', 'uploads/personnel_docs/[COS] JD_QPTEO_PDO3.pdf', '', 'Active', '2026-08-23 07:33:35', '2026-08-23 08:18:09'),
(5, '2602070', 'Vernie Glojun T. Lasmarias, PDO III', 'VJ', 'Vernie Glojun', 'T.', 'Lasmarias', 'Project Development Officer III', 'SG 18', 53818.00, 'Contract of Service', 'Quality Assurance Specialist', 'QPTEO', 'Quality Assurance', 'vernie.lasmarias@deped.gov.ph', '+63 921 567 8901', '1993-09-06', '', 'uploads/personnel_docs/[COS] JD_QPTEO_PDO3QA.pdf', '', 'Active', '2026-08-23 07:33:35', '2026-08-23 08:26:05'),
(6, '2602013', 'Lester Dave G. Pua, PDO III', 'Dave', 'Lester Dave', 'G.', 'Pua', 'Project Development Officer III', 'SG 18', 53818.00, 'Contract of Service', 'Curriculum & Instruction Specialist', 'Quality Pre-Service Teacher Education Office', 'Programs & Operations', 'lesterdave.pua@deped.gov.ph', '+63 922 678 9012', '1999-11-27', '', 'uploads/personnel_docs/[COS] JD_QPTEO_PDO3_1.pdf', '', 'Active', '2026-08-23 07:33:35', '2026-08-23 08:18:09'),
(7, '2602047', 'Christopher E. Siscar, PDO I', 'Chris', 'Christopher', 'E.', 'Siscar', 'Project Development Officer I', 'SG 11', 31705.00, 'Contract of Service', 'Administrative & Operations Support', 'Quality Pre-Service Teacher Education Office', 'Finance & Administration', 'christopher.siscar@deped.gov.ph', '+63 923 789 0123', '1995-09-09', '', 'uploads/personnel_docs/[COS] JD_QPTEO_PDO1.pdf', '', 'Active', '2026-08-23 07:33:35', '2026-08-23 08:18:09'),
(8, '2601019', 'Clarence Jillian Villena, EPS I', 'Jillian', 'Clarence Jillian', 'M.', 'Villena', 'Education Program Specialist I', 'SG 12', 33947.00, 'Plantilla', '', 'Quality Pre-Service Teacher Education Office', 'Programs & Operations', 'clarence.villena@deped.gov.ph', '+63 924 890 1234', '1995-06-14', '', 'uploads/personnel_docs/[PLANTILLA] JD_QPTEO_EPS1.pdf', '', 'Active', '2026-08-23 07:33:35', '2026-08-23 08:32:15'),
(9, '2601029', 'Venus Mae D. Cabuñalda, ADAS I', 'Venus', 'Venus Mae', 'D.', 'Cabunalda', 'Administrative Assistant I', 'SG 7', 20914.00, 'Plantilla', 'Secretary II / Executive Assistant', 'Quality Pre-Service Teacher Education Office', 'Office of the Director', 'venusmae.cabunalda@deped.gov.ph', '+63 925 901 2345', '1998-12-18', '', 'uploads/personnel_docs/[PLANTILLA] JD_QPTEO_ADAS1.pdf', '', 'Active', '2026-08-23 07:33:35', '2026-08-23 08:18:09'),
(10, 'P010', 'Alexander Joerenz E. Escallente', 'Alex', 'Alexander Joerenz', 'E.', 'Escallente', 'Executive Assistant I, EA I', 'SG 14', 38000.00, 'Plantilla', 'Technical & Executive Operations Lead', 'Quality Pre-Service Teacher Education Office', 'Office of the Director', 'alexander.escallente@deped.gov.ph', '+63 926 012 3456', '2004-08-27', '', '', '', 'Inactive', '2026-08-23 07:33:35', '2026-08-23 08:18:09');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `personnel`
--
ALTER TABLE `personnel`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personnel_code` (`personnel_code`),
  ADD KEY `idx_personnel_code` (`personnel_code`),
  ADD KEY `idx_name` (`full_name`),
  ADD KEY `idx_unit` (`unit`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `personnel`
--
ALTER TABLE `personnel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

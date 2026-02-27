-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 22, 2026 at 07:05 AM
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
-- Database: `timetable_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `shift` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `name`, `shift`) VALUES
(1, 'I B.Sc CS', 'Shift 1'),
(2, 'I B.Sc CS', 'Shift 2'),
(3, 'II B.Sc CS', 'Shift 1'),
(4, 'II B.Sc CS', 'Shift 2'),
(5, 'III B.Sc CS', 'Shift 1'),
(6, 'III B.Sc CS', 'Shift 2'),
(7, 'I M.Sc CS', 'PG / Regular'),
(8, 'II M.Sc CS', 'PG / Regular');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `short_code` varchar(10) DEFAULT NULL,
  `Hours` tinyint(20) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `name`, `designation`, `qualification`, `short_code`, `Hours`) VALUES
(1, 'Dr. L. Robert', 'Associate Professor & Head', 'M.Sc., M.Phil., Ph.D', 'LR', 12),
(2, 'Dr. S. Chitra', 'Associate Professor', 'M.Sc., Ph.D', 'SC', 12),
(3, 'Dr. M. Devapriya', 'Associate Professor', 'M.Sc., M.Phil., Ph.D', 'MDP', 16),
(4, 'Dr. K. Saraswathi', 'Associate Professor', 'MCA, M.Phil., Ph.D', 'KS', 16),
(5, 'Dr. A. Malathi', 'Associate Professor', 'M.Sc., M.Phil., Ph.D', 'AM', 16),
(6, 'Dr. P. Balamurugan', 'Associate Professor', 'M.Sc., M.Phil., Ph.D', 'PB', 16),
(7, 'V. B. Buvaneswari', 'Assistant Professor', 'M.Sc., M.Phil., M.E', 'VBB', 16),
(8, 'Dr. S. K. Mahendran', 'Assistant Professor', 'MCA, M.Phil(CS), Ph.D', 'SKM', 16),
(9, 'Mr. S. Yuvaraj', 'Guest Lecturer', 'M.C.A., M.Phil., (Ph.D)', 'SY', 17),
(10, 'D. Rajasekar', 'Guest Lecturer', 'M.C.A., M.Phil., (Ph.D)', 'DR', 17),
(11, 'N. K. Rathika', 'Guest Lecturer', 'M.C.A., NET, (Ph.D)', 'NKR', 17),
(12, 'R. Anbazhagan', 'Guest Lecturer', 'M.Sc., M.Phil., NET, (Ph.D)', 'RA', 17);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `sub_code` varchar(50) DEFAULT NULL,
  `hours_per_week` int(11) DEFAULT NULL,
  `semester` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `program` enum('UG','PG') NOT NULL DEFAULT 'UG',
  `year` int(11) NOT NULL DEFAULT 1,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `title`, `sub_code`, `hours_per_week`, `semester`, `type`, `program`, `year`, `sort_order`) VALUES
(1, 'Tamil I', '23TAM11L', 4, 1, 'Common', 'UG', 1, 1),
(2, 'English I', '23ENG12L', 4, 1, 'Common', 'UG', 1, 2),
(3, 'Programming Methodology', '23BCS13C', 5, 1, 'Core', 'UG', 1, 3),
(4, 'Digital Computer Fundamentals', '23BCS14C', 5, 1, 'Core', 'UG', 1, 4),
(5, 'Programming Methodology Lab', '23BCS15P', 4, 1, 'Lab', 'UG', 1, 5),
(6, 'Statistics & Numerical Methods', '23BCS16A', 6, 1, 'Allied', 'UG', 1, 6),
(7, 'Environmental Studies', '23ENV1GE', 2, 1, 'Common', 'UG', 1, 7),
(16, 'Tamil II', '23TAM23L', 4, 2, 'Common', 'UG', 1, 16),
(17, 'English II', '23ENG22L', 4, 2, 'Common', 'UG', 1, 17),
(18, 'C++ Programming', '23BCS23C', 5, 2, 'Core', 'UG', 1, 18),
(19, 'Computer System Architecture', '23BCS24C', 4, 2, 'Core', 'UG', 1, 19),
(20, 'C++ Programming Lab', '23BCS25P', 3, 2, 'Lab', 'UG', 1, 20),
(21, 'Discrete Mathematics', '23BCS26A', 6, 2, 'Allied', 'UG', 1, 21),
(22, 'Naan Mudhalvan', '23NMN2AL', 2, 2, 'NM', 'UG', 1, 22),
(23, 'Value Education', '23VAL2GE', 2, 2, 'Common', 'UG', 1, 23),
(24, 'Tamil III', '23TAM31L', 4, 3, 'Common', 'UG', 2, 24),
(25, 'English III', '23ENG32L', 4, 3, 'Common', 'UG', 2, 25),
(26, 'Software Engineering', '23BCS33C', 4, 3, 'Core', 'UG', 2, 26),
(27, 'Data Structures', '23BCS34C', 4, 3, 'Core', 'UG', 2, 27),
(28, 'Programming in Java', '23BCS35C', 5, 3, 'Core', 'UG', 2, 28),
(29, 'Java Programming Lab', '23BCS36P', 3, 3, 'Lab', 'UG', 2, 29),
(30, 'Operations Research', '23BCS37A', 4, 3, 'Allied', 'UG', 2, 30),
(31, 'Naan Mudhalvan', '22NMN3CS', 2, 3, 'NM', 'UG', 2, 31),
(32, 'Tamil IV', '23TAM41L', 4, 4, 'Common', 'UG', 2, 32),
(33, 'English IV', '23ENG42L', 4, 4, 'Common', 'UG', 2, 33),
(34, 'Algorithms', '23BCS43C', 4, 4, 'Core', 'UG', 2, 34),
(35, 'Database Management System', '23BCS44C', 4, 4, 'Core', 'UG', 2, 35),
(36, 'Python Programming', '23BCS45C', 4, 4, 'Core', 'UG', 2, 36),
(37, 'Python Programming Lab', '23BCS46P', 2, 4, 'Lab', 'UG', 2, 37),
(38, 'DBMS Lab (SQL)', '23BCS47P', 2, 4, 'Lab', 'UG', 2, 38),
(39, 'Business Accounting', '23BCS48A', 4, 4, 'Allied', 'UG', 2, 39),
(40, 'Naan Mudhalvan', '23NMN4CS', 2, 4, 'NM', 'UG', 2, 40),
(41, 'Operating System', '23BCS51C', 5, 5, 'Core', 'UG', 3, 41),
(42, 'Computer Networks', '23BCS52C', 5, 5, 'Core', 'UG', 3, 42),
(43, 'Internet Technologies', '23BCS53C', 6, 5, 'Core', 'UG', 3, 43),
(44, 'Internet Technologies Lab', '23BCS54P', 3, 5, 'Lab', 'UG', 3, 44),
(45, 'Linux Shell Programming Lab', '23BCS55P', 2, 5, 'Lab', 'UG', 3, 45),
(46, 'Non Major Elective', '23BCS5EL', 3, 5, 'Non Major Elective', 'UG', 3, 46),
(47, 'Naan Mudhalvan', '23NMN5CS', 2, 5, 'NM', 'UG', 3, 47),
(48, 'SBS: Computer Graphics', '23BCS56S', 4, 5, 'Core', 'UG', 3, 48),
(49, 'C# Programming', '23BCS61C', 6, 6, 'Core', 'UG', 3, 49),
(50, 'AI and Machine Learning', '23BCS62C', 6, 6, 'Core', 'UG', 3, 50),
(51, 'C# Programming Lab', '23BCS63P', 3, 6, 'Lab', 'UG', 3, 51),
(52, 'Open Source Computing Lab', '23BCS64P', 2, 6, 'Lab', 'UG', 3, 52),
(53, 'Project & Viva Voce', '23BCS65V', 3, 6, 'Project', 'UG', 3, 53),
(54, 'SBS: Open Source Computing', '23BCS66S', 5, 6, 'Core', 'UG', 3, 54),
(55, 'Naan Mudhalvan', '23NMN6CS', 2, 6, 'NM', 'UG', 3, 55),
(56, 'Non Major Elective', '23BCS6EL', 3, 6, 'Non Major Elective', 'UG', 3, 56),
(57, 'Computer System Architecture', '21MCS11C', 5, 1, 'Core', 'PG', 1, 1),
(58, 'Data Structures and Algorithms', '21MCS12C', 5, 1, 'Core', '', 1, 58),
(59, 'Advanced Java Programming', '21MCS13C', 5, 1, 'Core', 'PG', 1, 3),
(60, 'Data Communication and Networks', '21MCS14C', 5, 1, 'Core', 'PG', 1, 4),
(61, 'Database Management Systems', '21MCS15C', 5, 1, 'Core', 'PG', 1, 5),
(62, 'Advanced Java Programming Lab', '21MCS16P', 3, 1, 'Lab', 'PG', 1, 6),
(63, 'DBMS Lab', '21MCS17P', 2, 1, 'Lab', 'PG', 1, 7),
(64, 'Data Mining with R', '21MCS21C', 5, 2, 'Core', 'PG', 1, 64),
(65, 'Operating Systems', '21MCS22C', 5, 2, 'Core', 'PG', 1, 65),
(66, 'Data Science using Python', '21MCS23C', 5, 2, 'Core', 'PG', 1, 66),
(67, 'Software Engineering Concepts', '21MCS24C', 5, 2, 'Core', 'PG', 1, 67),
(68, 'Elective I', '21MCS25E', 5, 2, 'Core', 'PG', 1, 68),
(69, 'Data Mining Lab', '21MCS26P', 3, 2, 'Lab', 'PG', 1, 69),
(70, 'Python Programming Lab', '21MCS27P', 2, 2, 'Lab', 'PG', 1, 70),
(71, 'Digital Image Processing', '21MCS31C', 5, 3, 'Core', 'PG', 2, 71),
(72, 'Cloud Computing', '21MCS32C', 5, 3, 'Core', 'PG', 2, 72),
(73, 'Web Programming Essentials', '21MCS33C', 5, 3, 'Core', 'PG', 2, 73),
(74, 'Mobile Application Development', '21MCS34C', 5, 3, 'Core', 'PG', 2, 74),
(75, 'Elective II', '21MCS35E', 5, 3, 'Core', 'PG', 2, 75),
(76, 'DIP Lab using Python', '21MCS36P', 3, 3, 'Lab', 'PG', 2, 76),
(77, 'Mobile Apps Development Lab', '21MCS37P', 2, 3, 'Lab', 'PG', 2, 77),
(78, 'Open Source Technology (PHP/MySQL)', '21MCS41C', 5, 4, 'Core', 'PG', 2, 78),
(79, 'Open Source Technology Lab', '21MCS42P', 5, 4, 'Lab', 'PG', 2, 79),
(80, 'Project Viva Voce', '21MCS43V', 20, 4, 'Project', 'PG', 2, 80),
(85, 'Data Structures and Algorithms', '21MCS12C', 5, 1, 'Core', 'PG', 1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE `timetable` (
  `id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `semester` int(11) DEFAULT NULL,
  `day_no` int(11) DEFAULT NULL,
  `hour_no` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `is_lab` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetable`
--

INSERT INTO `timetable` (`id`, `class_id`, `semester`, `day_no`, `hour_no`, `subject_id`, `staff_id`, `is_lab`) VALUES
(1, 1, NULL, NULL, 4, 1, NULL, NULL),
(2, 1, NULL, NULL, 4, 2, NULL, NULL),
(3, 1, NULL, NULL, 5, 3, 10, NULL),
(4, 1, NULL, NULL, 5, 4, 10, NULL),
(5, 1, NULL, NULL, 4, 5, 10, NULL),
(6, 1, NULL, NULL, 6, 6, NULL, NULL),
(7, 1, NULL, NULL, 2, 7, NULL, NULL),
(8, 1, NULL, NULL, 4, 24, NULL, NULL),
(9, 1, NULL, NULL, 4, 25, NULL, NULL),
(10, 1, NULL, NULL, 4, 26, 10, NULL),
(11, 1, NULL, NULL, 4, 27, 10, NULL),
(12, 1, NULL, NULL, 5, 28, 10, NULL),
(13, 1, NULL, NULL, 3, 29, 10, NULL),
(14, 1, NULL, NULL, 4, 30, NULL, NULL),
(15, 1, NULL, NULL, 2, 31, NULL, NULL),
(16, 1, NULL, NULL, 5, 41, 10, NULL),
(17, 1, NULL, NULL, 5, 42, 10, NULL),
(18, 1, NULL, NULL, 6, 43, 10, NULL),
(19, 1, NULL, NULL, 3, 44, 10, NULL),
(20, 1, NULL, NULL, 2, 45, 10, NULL),
(21, 1, NULL, NULL, 3, 46, 10, NULL),
(22, 1, NULL, NULL, 2, 47, NULL, NULL),
(23, 1, NULL, NULL, 4, 48, 10, NULL),
(24, 1, NULL, NULL, 4, 1, NULL, NULL),
(25, 1, NULL, NULL, 4, 2, NULL, NULL),
(26, 1, NULL, NULL, 5, 3, 10, NULL),
(27, 1, NULL, NULL, 5, 4, 10, NULL),
(28, 1, NULL, NULL, 4, 5, 10, NULL),
(29, 1, NULL, NULL, 6, 6, NULL, NULL),
(30, 1, NULL, NULL, 2, 7, NULL, NULL),
(31, 1, NULL, NULL, 4, 24, NULL, NULL),
(32, 1, NULL, NULL, 4, 25, NULL, NULL),
(33, 1, NULL, NULL, 4, 26, 10, NULL),
(34, 1, NULL, NULL, 4, 27, 10, NULL),
(35, 1, NULL, NULL, 5, 28, 10, NULL),
(36, 1, NULL, NULL, 3, 29, 10, NULL),
(37, 1, NULL, NULL, 4, 30, NULL, NULL),
(38, 1, NULL, NULL, 2, 31, NULL, NULL),
(39, 1, NULL, NULL, 5, 41, 10, NULL),
(40, 1, NULL, NULL, 5, 42, 10, NULL),
(41, 1, NULL, NULL, 6, 43, 10, NULL),
(42, 1, NULL, NULL, 3, 44, 10, NULL),
(43, 1, NULL, NULL, 2, 45, 10, NULL),
(44, 1, NULL, NULL, 3, 46, 10, NULL),
(45, 1, NULL, NULL, 2, 47, NULL, NULL),
(46, 1, NULL, NULL, 4, 48, 10, NULL),
(47, 2, NULL, NULL, 4, 1, NULL, NULL),
(48, 2, NULL, NULL, 4, 2, NULL, NULL),
(49, 2, NULL, NULL, 5, 3, 10, NULL),
(50, 2, NULL, NULL, 5, 4, 10, NULL),
(51, 2, NULL, NULL, 4, 5, 10, NULL),
(52, 2, NULL, NULL, 6, 6, NULL, NULL),
(53, 2, NULL, NULL, 2, 7, NULL, NULL),
(54, 2, NULL, NULL, 4, 24, NULL, NULL),
(55, 2, NULL, NULL, 4, 25, NULL, NULL),
(56, 2, NULL, NULL, 4, 26, 10, NULL),
(57, 2, NULL, NULL, 4, 27, 10, NULL),
(58, 2, NULL, NULL, 5, 28, 10, NULL),
(59, 2, NULL, NULL, 3, 29, 10, NULL),
(60, 2, NULL, NULL, 4, 30, NULL, NULL),
(61, 2, NULL, NULL, 2, 31, NULL, NULL),
(62, 2, NULL, NULL, 5, 41, 10, NULL),
(63, 2, NULL, NULL, 5, 42, 10, NULL),
(64, 2, NULL, NULL, 6, 43, 10, NULL),
(65, 2, NULL, NULL, 3, 44, 10, NULL),
(66, 2, NULL, NULL, 2, 45, 10, NULL),
(67, 2, NULL, NULL, 3, 46, 10, NULL),
(68, 2, NULL, NULL, 2, 47, NULL, NULL),
(69, 2, NULL, NULL, 4, 48, 10, NULL),
(70, 2, NULL, NULL, 4, 1, NULL, NULL),
(71, 2, NULL, NULL, 4, 2, NULL, NULL),
(72, 2, NULL, NULL, 5, 3, 10, NULL),
(73, 2, NULL, NULL, 5, 4, 10, NULL),
(74, 2, NULL, NULL, 4, 5, 10, NULL),
(75, 2, NULL, NULL, 6, 6, NULL, NULL),
(76, 2, NULL, NULL, 2, 7, NULL, NULL),
(77, 2, NULL, NULL, 4, 24, NULL, NULL),
(78, 2, NULL, NULL, 4, 25, NULL, NULL),
(79, 2, NULL, NULL, 4, 26, 10, NULL),
(80, 2, NULL, NULL, 4, 27, 10, NULL),
(81, 2, NULL, NULL, 5, 28, 10, NULL),
(82, 2, NULL, NULL, 3, 29, 10, NULL),
(83, 2, NULL, NULL, 4, 30, NULL, NULL),
(84, 2, NULL, NULL, 2, 31, NULL, NULL),
(85, 2, NULL, NULL, 5, 41, 10, NULL),
(86, 2, NULL, NULL, 5, 42, 10, NULL),
(87, 2, NULL, NULL, 6, 43, 10, NULL),
(88, 2, NULL, NULL, 3, 44, 10, NULL),
(89, 2, NULL, NULL, 2, 45, 10, NULL),
(90, 2, NULL, NULL, 3, 46, 10, NULL),
(91, 2, NULL, NULL, 2, 47, NULL, NULL),
(92, 2, NULL, NULL, 4, 48, 10, NULL),
(93, 3, NULL, NULL, 4, 1, NULL, NULL),
(94, 3, NULL, NULL, 4, 2, NULL, NULL),
(95, 3, NULL, NULL, 5, 3, 10, NULL),
(96, 3, NULL, NULL, 5, 4, 10, NULL),
(97, 3, NULL, NULL, 4, 5, 10, NULL),
(98, 3, NULL, NULL, 6, 6, NULL, NULL),
(99, 3, NULL, NULL, 2, 7, NULL, NULL),
(100, 3, NULL, NULL, 4, 24, NULL, NULL),
(101, 3, NULL, NULL, 4, 25, NULL, NULL),
(102, 3, NULL, NULL, 4, 26, 10, NULL),
(103, 3, NULL, NULL, 4, 27, 10, NULL),
(104, 3, NULL, NULL, 5, 28, 10, NULL),
(105, 3, NULL, NULL, 3, 29, 10, NULL),
(106, 3, NULL, NULL, 4, 30, NULL, NULL),
(107, 3, NULL, NULL, 2, 31, NULL, NULL),
(108, 3, NULL, NULL, 5, 41, 10, NULL),
(109, 3, NULL, NULL, 5, 42, 10, NULL),
(110, 3, NULL, NULL, 6, 43, 10, NULL),
(111, 3, NULL, NULL, 3, 44, 10, NULL),
(112, 3, NULL, NULL, 2, 45, 10, NULL),
(113, 3, NULL, NULL, 3, 46, 10, NULL),
(114, 3, NULL, NULL, 2, 47, NULL, NULL),
(115, 3, NULL, NULL, 4, 48, 10, NULL),
(116, 3, NULL, NULL, 4, 1, NULL, NULL),
(117, 3, NULL, NULL, 4, 2, NULL, NULL),
(118, 3, NULL, NULL, 5, 3, 10, NULL),
(119, 3, NULL, NULL, 5, 4, 10, NULL),
(120, 3, NULL, NULL, 4, 5, 10, NULL),
(121, 3, NULL, NULL, 6, 6, NULL, NULL),
(122, 3, NULL, NULL, 2, 7, NULL, NULL),
(123, 3, NULL, NULL, 4, 24, NULL, NULL),
(124, 3, NULL, NULL, 4, 25, NULL, NULL),
(125, 3, NULL, NULL, 4, 26, 10, NULL),
(126, 3, NULL, NULL, 4, 27, 10, NULL),
(127, 3, NULL, NULL, 5, 28, 10, NULL),
(128, 3, NULL, NULL, 3, 29, 10, NULL),
(129, 3, NULL, NULL, 4, 30, NULL, NULL),
(130, 3, NULL, NULL, 2, 31, NULL, NULL),
(131, 3, NULL, NULL, 5, 41, 10, NULL),
(132, 3, NULL, NULL, 5, 42, 10, NULL),
(133, 3, NULL, NULL, 6, 43, 10, NULL),
(134, 3, NULL, NULL, 3, 44, 10, NULL),
(135, 3, NULL, NULL, 2, 45, 10, NULL),
(136, 3, NULL, NULL, 3, 46, 10, NULL),
(137, 3, NULL, NULL, 2, 47, NULL, NULL),
(138, 3, NULL, NULL, 4, 48, 10, NULL),
(139, 1, NULL, NULL, 5, 57, 10, NULL),
(140, 1, NULL, NULL, 5, 58, 10, NULL),
(141, 1, NULL, NULL, 5, 59, 10, NULL),
(142, 1, NULL, NULL, 5, 60, 10, NULL),
(143, 1, NULL, NULL, 5, 61, 10, NULL),
(144, 1, NULL, NULL, 3, 62, 10, NULL),
(145, 1, NULL, NULL, 2, 63, 10, NULL),
(146, 1, NULL, NULL, 5, 71, 10, NULL),
(147, 1, NULL, NULL, 5, 72, 10, NULL),
(148, 1, NULL, NULL, 5, 73, 10, NULL),
(149, 1, NULL, NULL, 5, 74, 10, NULL),
(150, 1, NULL, NULL, 5, 75, 10, NULL),
(151, 1, NULL, NULL, 3, 76, 10, NULL),
(152, 1, NULL, NULL, 2, 77, 10, NULL),
(153, 2, NULL, NULL, 5, 57, 10, NULL),
(154, 2, NULL, NULL, 5, 58, 10, NULL),
(155, 2, NULL, NULL, 5, 59, 10, NULL),
(156, 2, NULL, NULL, 5, 60, 10, NULL),
(157, 2, NULL, NULL, 5, 61, 10, NULL),
(158, 2, NULL, NULL, 3, 62, 10, NULL),
(159, 2, NULL, NULL, 2, 63, 10, NULL),
(160, 2, NULL, NULL, 5, 71, 10, NULL),
(161, 2, NULL, NULL, 5, 72, 10, NULL),
(162, 2, NULL, NULL, 5, 73, 10, NULL),
(163, 2, NULL, NULL, 5, 74, 10, NULL),
(164, 2, NULL, NULL, 5, 75, 10, NULL),
(165, 2, NULL, NULL, 3, 76, 10, NULL),
(166, 2, NULL, NULL, 2, 77, 10, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `surname` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `surname`, `email`, `phone`, `password`) VALUES
(1, 'admin', 'a', 'admin@gmail.com', '9999988888', '$2y$10$tvzcekN2jhY4GXI4PNa/n.PeqlqEX5oESQsknDtgqQi2V1MjRrbJe'),
(2, 'Student', 'S', 'Student@gmail.com', '9876543210', '$2y$10$UPrBU2ZVjHX0Buvpl2av.uA7cGHtB8kievT345Ljs.rmMTG81lqjq');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=167;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `timetable`
--
ALTER TABLE `timetable`
  ADD CONSTRAINT `timetable_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  ADD CONSTRAINT `timetable_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  ADD CONSTRAINT `timetable_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 31, 2017 at 03:25 AM
-- Server version: 5.5.38-0ubuntu0.14.04.1
-- PHP Version: 5.5.9-1ubuntu4.5

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `lotka-volterra`
--
CREATE DATABASE IF NOT EXISTS `lotka-volterra` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
USE `lotka-volterra`;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `amount` int(11) NOT NULL,
  `size` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `preference` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `attested_id` int(11) DEFAULT NULL,
  `orderdate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

--
-- Table structure for table `shirts`
--

CREATE TABLE IF NOT EXISTS `shirts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `type_class` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `size` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `available` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=25 ;

--
-- Dumping data for table `shirts`
--

INSERT INTO `shirts` (`id`, `type`, `type_class`, `size`, `available`) VALUES
(1, 'Slim T-shirt', 'SLIM_TSHIRT', 'XS', 1),
(2, 'Slim T-shirt', 'SLIM_TSHIRT', 'S', 1),
(3, 'Slim T-shirt', 'SLIM_TSHIRT', 'M', 1),
(4, 'Slim T-shirt', 'SLIM_TSHIRT', 'L', 1),
(5, 'Slim T-shirt', 'SLIM_TSHIRT', 'XL', 1),
(6, 'Slim T-shirt', 'SLIM_TSHIRT', 'XXL', 1),
(7, 'Slim T-shirt', 'SLIM_TSHIRT', 'XXXL', 1),
(8, 'Slim T-shirt', 'SLIM_TSHIRT', 'XXXXL', 1),
(9, 'Regular Fit T-Shirt', 'REGULAR_TSHIRT', 'XS', 1),
(10, 'Regular Fit T-Shirt', 'REGULAR_TSHIRT', 'S', 1),
(11, 'Regular Fit T-Shirt', 'REGULAR_TSHIRT', 'M', 1),
(12, 'Regular Fit T-Shirt', 'REGULAR_TSHIRT', 'L', 1),
(13, 'Regular Fit T-Shirt', 'REGULAR_TSHIRT', 'XL', 1),
(14, 'Regular Fit T-Shirt', 'REGULAR_TSHIRT', 'XXL', 1),
(15, 'Regular Fit T-Shirt', 'REGULAR_TSHIRT', 'XXXL', 1),
(16, 'Regular Fit T-Shirt', 'REGULAR_TSHIRT', 'XXXXL', 1);

-- --------------------------------------------------------

--
-- Table structure for table `surnames`
--

CREATE TABLE IF NOT EXISTS `surnames` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `surname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `order_id` int(11) NOT NULL,
  `available` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=200 ;

--
-- Dumping data for table `surnames`
--

INSERT INTO `surnames` (`id`, `surname`, `order_id`, `available`) VALUES
(1, 'Aaron', 7, 1),
(2, 'Ackermann', 0, 0),
(3, 'Ali', 0, 1),
(4, 'Allard', 0, 1),
(5, 'Alvarez', 0, 1),
(6, 'Andrews', 0, 1),
(7, 'Apanowicz', 0, 1),
(8, 'Arriaga', 0, 1),
(9, 'Arshad', 0, 1),
(10, 'Banks', 0, 1),
(11, 'Bateman', 0, 1),
(12, 'Behrmann', 0, 1),
(13, 'Berger', 0, 1),
(14, 'Blair', 0, 1),
(15, 'Bodehouse', 0, 1),
(16, 'Brannigan', 0, 1),
(17, 'Brown', 0, 1),
(18, 'Bryant', 0, 1),
(19, 'Buinov', 0, 1),
(20, 'Cassidy', 0, 1),
(21, 'Castro', 0, 1),
(22, 'Chadha', 0, 1),
(23, 'Chernikov', 0, 1),
(24, 'Chesler', 0, 1),
(25, 'Church', 0, 1),
(26, 'Clavain', 0, 1),
(27, 'Clemens', 0, 1),
(28, 'Cody', 0, 1),
(29, 'Coleman', 0, 1),
(30, 'Conti', 0, 1),
(31, 'Cooke', 0, 0),
(32, 'Corneanu', 0, 1),
(33, 'Craig', 0, 1),
(34, 'Cressler', 0, 1),
(35, 'Crone', 0, 1),
(36, 'Cavis', 0, 1),
(37, 'de Gama', 0, 1),
(38, 'de Soto', 0, 1),
(39, 'Dean', 0, 1),
(40, 'Dietrich', 0, 1),
(41, 'Dillard', 0, 1),
(42, 'Dodd', 0, 1),
(43, 'Downing', 0, 1),
(44, 'Drake', 0, 0),
(45, 'Dubois', 0, 0),
(46, 'Ellis', 0, 1),
(47, 'Erdmann', 0, 1),
(48, 'Evans', 0, 1),
(49, 'Farrokzhad', 0, 1),
(50, 'Ferenc', 0, 1),
(51, 'Fessler', 0, 1),
(52, 'Firelli', 0, 1),
(53, 'Florian', 0, 1),
(54, 'Frazier', 0, 1),
(55, 'Galland', 0, 1),
(56, 'Garrett', 0, 1),
(57, 'Gibbons', 0, 1),
(58, 'Giles', 0, 1),
(59, 'Gobel', 0, 1),
(60, 'Golic', 0, 1),
(61, 'Golovin', 0, 1),
(62, 'Greer', 0, 1),
(63, 'Gromov', 0, 1),
(64, 'Gunn', 0, 1),
(65, 'Gupta', 0, 1),
(66, 'Haislip', 0, 1),
(67, 'Harding', 0, 1),
(68, 'Harkins', 0, 1),
(69, 'Hayashi', 0, 1),
(70, 'Heckler', 0, 1),
(71, 'Hernandez', 0, 1),
(72, 'Heydar', 0, 1),
(73, 'Higgs', 0, 0),
(74, 'Hines', 0, 1),
(75, 'Hodges', 0, 1),
(76, 'Holden', 0, 1),
(77, 'Holzmann', 0, 0),
(78, 'Hook', 0, 1),
(79, 'Horowitz', 0, 1),
(80, 'Hurst', 0, 1),
(81, 'Hussain', 0, 1),
(82, 'Ikeda', 0, 1),
(83, 'Isizzu', 0, 1),
(84, 'Ito', 0, 1),
(85, 'James', 0, 1),
(86, 'Julian', 0, 1),
(87, 'Kaneko', 0, 1),
(88, 'Kassmeyer', 0, 1),
(89, 'Khatri', 0, 1),
(90, 'Kilgore', 0, 1),
(91, 'Kirkland', 0, 1),
(92, 'Klein', 0, 1),
(93, 'Kokorin', 0, 1),
(94, 'Konyev', 0, 1),
(95, 'Kopek', 0, 1),
(96, 'Kovacs', 0, 1),
(97, 'Kruger', 0, 0),
(98, 'Lacroix', 0, 0),
(99, 'Lambert', 0, 1),
(100, 'Lange', 0, 1),
(101, 'Laporte', 0, 1),
(102, 'Leclair', 0, 1),
(103, 'Liszt', 0, 1),
(104, 'Malone', 0, 1),
(105, 'March', 0, 1),
(106, 'Massoud', 0, 1),
(107, 'May', 0, 1),
(108, 'Mcleod', 0, 0),
(109, 'Mercier', 0, 1),
(110, 'Milburn', 0, 1),
(111, 'Mitchell', 0, 1),
(112, 'Miyamoto', 0, 1),
(113, 'Modiano', 0, 1),
(114, 'Moore', 0, 1),
(115, 'Moorgrove', 0, 1),
(116, 'Morant', 0, 1),
(117, 'Moreau', 0, 1),
(118, 'Morse', 0, 1),
(119, 'Moshke', 0, 1),
(120, 'Murphy', 0, 0),
(121, 'Naser', 0, 1),
(122, 'Nazarov', 0, 1),
(123, 'Noel', 0, 1),
(124, 'North', 0, 1),
(125, 'Novak', 0, 0),
(126, 'Nunoz', 0, 1),
(127, 'O''neill', 0, 1),
(128, 'Ortega', 0, 1),
(129, 'Ortiz', 0, 1),
(130, 'Oswalt', 0, 1),
(131, 'Owen', 0, 1),
(132, 'Palmer', 0, 1),
(133, 'Parker', 0, 1),
(134, 'Parks', 0, 0),
(135, 'Patel', 0, 1),
(136, 'Pearson', 0, 1),
(137, 'Pereira', 0, 1),
(138, 'Peres', 0, 1),
(139, 'Pisani', 0, 1),
(140, 'Poe', 0, 1),
(141, 'Poole', 0, 1),
(142, 'Prescott', 0, 1),
(143, 'Pasztor', 0, 1),
(144, 'Radic', 0, 0),
(145, 'Rains', 0, 0),
(146, 'Reynolds', 0, 1),
(147, 'Rosenberg', 0, 1),
(148, 'Rowe', 0, 1),
(149, 'Ruffner', 0, 1),
(150, 'Ruiz', 0, 1),
(151, 'Russo', 0, 1),
(152, 'Rona', 0, 1),
(153, 'Saar', 0, 1),
(154, 'Said', 0, 1),
(155, 'Salvatore', 0, 1),
(156, 'Schaeffer', 0, 1),
(157, 'Sepideh', 0, 1),
(158, 'Severin', 0, 1),
(159, 'Severny', 0, 1),
(160, 'Sheridan', 0, 1),
(161, 'Short', 0, 1),
(162, 'Sikorski', 0, 1),
(163, 'Simon', 0, 1),
(164, 'Singh', 0, 1),
(165, 'Spence', 0, 1),
(166, 'Stepan', 0, 1),
(167, 'Stephenson', 0, 1),
(168, 'Stilwell', 0, 1),
(169, 'Stone', 0, 1),
(170, 'Sokolov', 0, 1),
(171, 'Sundstrom', 0, 1),
(172, 'Sylveste', 0, 1),
(173, 'Takahashi', 0, 1),
(174, 'Takemoto', 0, 1),
(175, 'Tamas', 0, 1),
(176, 'Tuttle', 0, 1),
(177, 'Vaszary', 0, 1),
(178, 'Vessey', 0, 1),
(179, 'Vickers', 0, 1),
(180, 'Walsh', 0, 1),
(181, 'Ward', 0, 1),
(182, 'Wendon', 0, 1),
(183, 'Westmoreland', 0, 1),
(184, 'Wiesel', 0, 1),
(185, 'Wilkers', 0, 1),
(186, 'Wilson', 0, 1),
(187, 'Yan', 0, 1),
(188, 'Young', 0, 1),
(189, 'Yu', 0, 1),
(190, 'Zheng', 0, 1),
(191, 'Reed', 0, 0),
(192, 'Gardner', 0, 0),
(193, 'van der Waal', 0, 0),
(194, 'Conway', 0, 0),
(195, 'Cox', 0, 0),
(196, 'Walker', 0, 0),
(197, 'Bishop', 0, 0),
(198, 'Scully', 0, 0),
(199, 'Mather', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE IF NOT EXISTS `teams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `available` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=6 ;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`id`, `type`, `name`, `available`) VALUES
(1, 'MAINT', 'Maintenance', 1),
(2, 'SURFOPS', 'Surface Operations', 1),
(3, 'COMMAND', 'Outpost Command', 1),
(4, 'MISCON', 'Mission Control', 1),
(5, 'OTHER', 'Other', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE IF NOT EXISTS `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `price` int(11) NOT NULL COMMENT 'price in öre',
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `statement_descriptor` varchar(22) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Shows up on participants account',
  `available` tinyint(1) NOT NULL DEFAULT '0',
  `img` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '100',
  `surname` tinyint(1) NOT NULL DEFAULT '0',
  `shirtType` tinyint(1) NOT NULL DEFAULT '0',
  `size` tinyint(1) NOT NULL DEFAULT '0',
  `teamPreference` tinyint(1) NOT NULL DEFAULT '0',
  `visible` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=7 ;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `sku`, `price`, `description`, `statement_descriptor`, `available`, `img`, `weight`, `surname`, `shirtType`, `size`, `teamPreference`, `visible`) VALUES
(1, 'SUPPORT', 360000, 'Support Ticket', 'Lotka-Volterra, Ticket', 1, '/img/LV-logo-ticket-icon-supporter-small-glitch-1.png', 1, 1, 1, 1, 1, 1),
(2, 'STANDARD', 260000, 'Standard Ticket', 'Lotka-Volterra, Ticket', 1, '/img/LV-logo-ticket-icon-standard-small-glitch-1.png', 2, 0, 0, 0, 0, 1),
(3, 'STD_1', 100000, 'Standard Ticket, Part', 'Lotka-Volterra, Ticket', 1, '/img/LV-logo-ticket-icon-partial-small-glitch-1.png', 3, 0, 0, 0, 0, 1),
(4, 'STD_2', 100000, 'Standard Ticket, second payment', 'Lotka-Volterra, Ticket', 1, '/img/LV-logo-ticket-icon-partial-small-glitch-2.png', 4, 0, 0, 0, 0, 0),
(5, 'SUBSIDIZED', 170000, 'Subsidized Ticket', 'Lotka-Volterra, Ticket', 1, '/img/LV-logo-ticket-icon-standard-small.png', 6, 0, 0, 0, 0, 0),
(6, 'STD_3', 60000, 'Standard Ticket, final payment', 'Lotka-Volterra, Ticket', 1, '/img/LV-logo-ticket-icon-partial-small-glitch-3.png', 5, 0, 0, 0, 0, 0);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

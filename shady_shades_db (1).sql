-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 26, 2025 at 09:02 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shady_shades_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(2, 'admin', '$2y$10$LyYvX1VEmMYniSL.blwrlO/GbQ4T.r0qHX26WGnLQ5V3YDgwRjggq'),
(3, 'admin1', '$2a$12$81RI9PbCZV8eyS0JPN99..X5eT1EUYkmPqL/oSGEGe415STIokz2W');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_date` datetime NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  `prescription` text DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `user_id`, `doctor_id`, `appointment_date`, `status`, `prescription`, `user_name`) VALUES
(101, 10, 13, '2025-05-22 15:00:00', 'completed', NULL, NULL),
(103, 10, 13, '2025-05-13 09:00:00', 'cancelled', NULL, NULL),
(104, 10, 6, '2025-05-25 17:00:00', 'completed', '{\"right_eye_sphere\":\"1\",\"right_eye_cylinder\":\"1\",\"right_eye_axis\":\"1\",\"left_eye_sphere\":\"1\",\"left_eye_cylinder\":\"1\",\"left_eye_axis\":\"1\"}', NULL),
(106, 10, 6, '2025-05-27 13:00:00', 'completed', '{\"right_eye_sphere\":\"1.00\",\"right_eye_cylinder\":\"1\",\"right_eye_axis\":\"1\",\"left_eye_sphere\":\"1\",\"left_eye_cylinder\":\"1\",\"left_eye_axis\":\"1\"}', NULL),
(110, 10, 6, '2025-05-26 09:00:00', 'cancelled', NULL, NULL),
(111, 10, 6, '2025-05-26 09:00:00', 'cancelled', NULL, NULL),
(112, 10, 6, '2025-05-26 09:00:00', 'cancelled', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `heading` varchar(255) NOT NULL,
  `subheading` varchar(255) DEFAULT NULL,
  `button_text` varchar(255) DEFAULT NULL,
  `button_link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `image_url`, `heading`, `subheading`, `button_text`, `button_link`) VALUES
(1, 'https://cdn2.blanxer.com/uploads/64205ca09ab9997729605f15/product_image-img_0317-1328.webp', 'Winter Stock Clearance', 'Stay Warm, Stay Protected.', 'See Products', 'products.php'),
(2, 'https://cdn2.blanxer.com/hero_image/64205ca09ab9997729605f15/67aef1dc3ee4e1127f52e8a1.webp', 'Exclusive Designer Collection', 'Luxury Glasses at the Best Prices.', 'Shop Now', 'brand-originals.php');

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `brand_name` varchar(255) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `brand_name`, `image_url`, `description`) VALUES
(1, 'Ray-Ban', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR4c1y_7gy2crn2Ll_ZSWzcqb0WDZFuBnFTeQ&s', 'Premium eyewear brand known for quality sunglasses.'),
(2, 'Oakley', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR4c1y_7gy2crn2Ll_ZSWzcqb0WDZFuBnFTeQ&s', 'Popular for sports sunglasses and durable frames.'),
(3, 'Gucci', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR4c1y_7gy2crn2Ll_ZSWzcqb0WDZFuBnFTeQ&s', 'Luxury brand offering high-end designer sunglasses.'),
(4, 'Versace', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR4c1y_7gy2crn2Ll_ZSWzcqb0WDZFuBnFTeQ&s', NULL),
(5, 'Prada', NULL, NULL),
(6, 'Armani', NULL, NULL),
(7, 'Tom Ford', NULL, NULL),
(8, 'Burberry', NULL, NULL),
(9, 'Louis Vuitton', NULL, NULL),
(10, 'Dolce & Gabbana', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `prescription_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `image_url` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `image_url`) VALUES
(1, 'Skull Rider', 'https://cdn2.blanxer.com/uploads/64205ca09ab9997729605f15/product_image-img_0317-1328.webp'),
(2, 'Premium Sunglasses', 'https://cdn2.blanxer.com/uploads/64205ca09ab9997729605f15/product_image-img_0713-5948.webp'),
(3, 'Affordable Sunglasses', 'https://cdn2.blanxer.com/uploads/64205ca09ab9997729605f15/product_image-img_0317-1328.webp'),
(4, 'Prescription Frames', 'https://cdn2.blanxer.com/uploads/64205ca09ab9997729605f15/product_image-img_0713-5948.webp');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `nmc_number` varchar(20) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `availability` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`availability`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `address` text DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `full_name`, `email`, `password`, `phone`, `nmc_number`, `specialization`, `availability`, `created_at`, `address`, `city`) VALUES
(6, 'Bibek Shrestha', 'mellowplays22@gmail.com', '$2y$10$nLp1zSySmNV9BQfPTVgSI.8XdCxI/pqyCUQVHEbEB8uAKXis94SCW', '9843641720', 'NMC123', 'Eye Specialist', '{\"Monday\":[\"9:00 AM\"],\"Tuesday\":[\"1:00 PM\"],\"Friday\":[\"9:00 AM\"],\"Sunday\":[\"9:00 AM\",\"2:00 PM\"]}', '2025-03-26 07:37:18', 'Tokha', 'Kathmandu'),
(13, 'Sumi Shrestha', 'sumi@gmail.com', '$2y$10$HD6a8prw7zbWNjDOxaXLlOONTUmb32XtRG5MLlkFcQjVPitao15Kq', '9826455863', 'testabc', 'Eye Specialist', '{\"Sunday\":[\"9:00 AM\"],\"Tuesday\":[\"9:00 AM\",\"2:00 PM\"]}', '2025-04-20 08:52:24', 'Tokha', 'Kathmandu'),
(28, 'Anju Khatri', 'test@gmail.com', '$2y$10$ShCwI8iS4ftVOJq5ul93fOAQw4TLbrbJGF2DgShcooJ0H9/joIMVm', '1234567890', 'jhk121', 'Eye Specialist', '{\"Monday\":[\"9:00 AM\"],\"Tuesday\":[\"11:00 AM\",\"5:00 PM\"],\"Wednesday\":[\"3:00 PM\",\"4:00 PM\",\"5:00 PM\"]}', '2025-05-11 05:39:49', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faqs`
--

INSERT INTO `faqs` (`id`, `question`, `answer`) VALUES
(1, 'What types of sunglasses do you offer?', 'We offer high-quality designer brands, affordable styles, and polarized sunglasses for enhanced UV protection.'),
(2, 'How do I know which sunglasses suit my face shape?', 'For round faces, angular frames work best. Square faces look great with round or oval frames.'),
(3, 'Are your sunglasses UV-protected?', 'Yes, all our sunglasses come with UV protection to shield your eyes from harmful UVA and UVB rays.'),
(4, 'Do you sell original branded sunglasses?', 'Yes, we carry a selection of authentic branded sunglasses from top international designers.'),
(5, 'Can I return or exchange my sunglasses?', 'Yes, we offer a hassle-free return and exchange policy within 7 days of purchase.');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Processing','Shipped','Delivered','Cancelled') DEFAULT NULL,
  `prescription_id` int(11) DEFAULT NULL,
  `order_note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `full_name`, `email`, `phone`, `address`, `city`, `payment_method`, `total_price`, `created_at`, `status`, `prescription_id`, `order_note`) VALUES
(154, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'khalti', 1600.00, '2025-05-24 07:56:03', 'Cancelled', NULL, NULL),
(159, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'khalti', 5300.00, '2025-05-24 08:57:48', 'Cancelled', NULL, NULL),
(166, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'cod', 5300.00, '2025-05-24 09:24:49', 'Cancelled', NULL, ''),
(167, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'cod', 5300.00, '2025-05-24 09:25:01', 'Cancelled', NULL, ''),
(168, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'khalti', 5300.00, '2025-05-24 09:34:20', 'Cancelled', NULL, NULL),
(169, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'khalti', 5300.00, '2025-05-24 09:35:18', NULL, NULL, NULL),
(170, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'khalti', 5300.00, '2025-05-24 09:39:03', NULL, NULL, NULL),
(171, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'cod', 5300.00, '2025-05-24 09:39:39', 'Pending', NULL, ''),
(172, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'khalti', 5300.00, '2025-05-24 09:42:21', NULL, NULL, NULL),
(173, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'khalti', 5300.00, '2025-05-24 09:44:25', NULL, NULL, NULL),
(174, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'khalti', 5300.00, '2025-05-24 09:45:09', NULL, NULL, NULL),
(175, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'khalti', 1600.00, '2025-05-24 09:46:55', NULL, NULL, NULL),
(176, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'khalti', 1000.00, '2025-05-24 09:49:34', NULL, NULL, NULL),
(177, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'khalti', 1600.00, '2025-05-24 09:51:50', NULL, NULL, NULL),
(178, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'khalti', 1600.00, '2025-05-24 09:53:12', NULL, NULL, NULL),
(179, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'khalti', 1600.00, '2025-05-24 09:54:54', NULL, NULL, NULL),
(180, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'cod', 1600.00, '2025-05-24 09:55:29', 'Pending', NULL, ''),
(181, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'khalti', 1600.00, '2025-05-24 09:57:03', NULL, NULL, NULL),
(182, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'khalti', 1600.00, '2025-05-24 09:58:23', NULL, NULL, NULL),
(183, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'khalti', 1600.00, '2025-05-24 10:00:13', NULL, NULL, NULL),
(184, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'khalti', 1600.00, '2025-05-24 10:01:52', NULL, NULL, NULL),
(185, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'khalti', 1600.00, '2025-05-24 10:15:55', 'Processing', NULL, ''),
(186, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'khalti', 5300.00, '2025-05-24 10:16:34', 'Processing', NULL, ''),
(187, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'cod', 1600.00, '2025-05-24 10:16:48', 'Pending', NULL, ''),
(188, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'khalti', 1050.00, '2025-05-24 10:17:22', 'Processing', NULL, ''),
(189, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'cod', 1050.00, '2025-05-24 10:17:38', 'Delivered', NULL, ''),
(197, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'cod', 1900.00, '2025-05-26 03:02:52', 'Cancelled', NULL, ''),
(198, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'cod', 4100.00, '2025-05-26 03:14:20', 'Cancelled', NULL, ''),
(199, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'cod', 10100.00, '2025-05-26 03:18:11', 'Cancelled', NULL, ''),
(200, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'cod', 2100.00, '2025-05-26 03:22:21', 'Cancelled', NULL, ''),
(201, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'cod', 2100.00, '2025-05-26 03:33:08', 'Cancelled', NULL, ''),
(202, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'cod', 4100.00, '2025-05-26 03:37:36', 'Cancelled', NULL, ''),
(203, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'cod', 4100.00, '2025-05-26 03:38:10', 'Cancelled', NULL, ''),
(204, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'cod', 4600.00, '2025-05-26 03:54:52', 'Cancelled', NULL, ''),
(205, 10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '9843641720', 'Tokha', 'Kathmandu', 'cod', 2000.00, '2025-05-26 03:59:09', 'Cancelled', NULL, '');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'cod'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `payment_method`) VALUES
(59, 154, 36, 1, 1500.00, 'cod'),
(61, 159, 1, 1, 5200.00, 'cod'),
(63, 166, 1, 1, 5200.00, '0'),
(64, 167, 1, 1, 5200.00, '0'),
(65, 168, 1, 1, 5200.00, 'cod'),
(66, 169, 1, 1, 5200.00, 'cod'),
(67, 170, 1, 1, 5200.00, 'cod'),
(68, 171, 1, 1, 5200.00, '0'),
(69, 172, 1, 1, 5200.00, 'cod'),
(70, 173, 1, 1, 5200.00, 'cod'),
(71, 174, 1, 1, 5200.00, 'cod'),
(72, 175, 36, 1, 1500.00, 'cod'),
(73, 176, 46, 1, 900.00, 'cod'),
(74, 177, 36, 1, 1500.00, 'cod'),
(75, 178, 36, 1, 1500.00, 'cod'),
(76, 179, 36, 1, 1500.00, 'cod'),
(77, 180, 36, 1, 1500.00, '0'),
(78, 181, 36, 1, 1500.00, 'cod'),
(79, 182, 36, 1, 1500.00, 'cod'),
(80, 183, 36, 1, 1500.00, 'cod'),
(81, 184, 36, 1, 1500.00, 'cod'),
(82, 185, 36, 1, 1500.00, '0'),
(83, 186, 1, 1, 5200.00, '0'),
(84, 187, 36, 1, 1500.00, '0'),
(85, 188, 14, 1, 950.00, '0'),
(86, 189, 14, 1, 950.00, '0'),
(94, 197, 46, 2, 900.00, '0'),
(95, 198, 47, 2, 2000.00, '0'),
(96, 199, 47, 5, 2000.00, '0'),
(97, 200, 47, 1, 2000.00, '0'),
(98, 201, 47, 1, 2000.00, '0'),
(99, 202, 47, 2, 2000.00, '0'),
(100, 203, 47, 2, 2000.00, '0'),
(101, 204, 36, 3, 1500.00, '0'),
(102, 205, 14, 1, 950.00, '0'),
(103, 205, 20, 1, 950.00, '0');

-- --------------------------------------------------------

--
-- Table structure for table `prescription_frames`
--

CREATE TABLE `prescription_frames` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `right_eye_sphere` decimal(4,2) DEFAULT NULL,
  `right_eye_cylinder` decimal(4,2) DEFAULT NULL,
  `right_eye_axis` int(11) DEFAULT NULL,
  `right_eye_pd` decimal(4,2) DEFAULT NULL,
  `left_eye_sphere` decimal(4,2) DEFAULT NULL,
  `left_eye_cylinder` decimal(4,2) DEFAULT NULL,
  `left_eye_axis` int(11) DEFAULT NULL,
  `left_eye_pd` decimal(4,2) DEFAULT NULL,
  `frame_model` varchar(100) DEFAULT NULL,
  `lens_type` enum('single_vision','bifocal','progressive','transition') DEFAULT 'single_vision',
  `coating_type` enum('anti_reflective','blue_light','scratch_resistant','uv_protection') DEFAULT 'anti_reflective',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescription_orders`
--

CREATE TABLE `prescription_orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `prescription_id` int(11) DEFAULT NULL,
  `right_eye_sphere` decimal(4,2) DEFAULT NULL,
  `right_eye_cylinder` decimal(4,2) DEFAULT NULL,
  `right_eye_axis` int(11) DEFAULT NULL,
  `right_eye_pd` decimal(4,2) DEFAULT NULL,
  `left_eye_sphere` decimal(4,2) DEFAULT NULL,
  `left_eye_cylinder` decimal(4,2) DEFAULT NULL,
  `left_eye_axis` int(11) DEFAULT NULL,
  `left_eye_pd` decimal(4,2) DEFAULT NULL,
  `lens_type` enum('single_vision','bifocal','progressive','transition') DEFAULT 'single_vision',
  `coating_type` enum('anti_reflective','blue_light','scratch_resistant','uv_protection') DEFAULT 'anti_reflective',
  `frame_color` varchar(50) DEFAULT NULL,
  `frame_size` enum('small','medium','large') DEFAULT NULL,
  `status` enum('draft','submitted','processing','shipped','delivered') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `order_type` enum('with_prescription','without_prescription') NOT NULL DEFAULT 'with_prescription'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescription_orders`
--

INSERT INTO `prescription_orders` (`id`, `user_id`, `product_id`, `prescription_id`, `right_eye_sphere`, `right_eye_cylinder`, `right_eye_axis`, `right_eye_pd`, `left_eye_sphere`, `left_eye_cylinder`, `left_eye_axis`, `left_eye_pd`, `lens_type`, `coating_type`, `frame_color`, `frame_size`, `status`, `created_at`, `updated_at`, `order_type`) VALUES
(7, 10, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'single_vision', 'anti_reflective', NULL, NULL, 'submitted', '2025-05-24 09:23:49', '2025-05-25 17:57:25', 'with_prescription'),
(8, 10, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'single_vision', 'anti_reflective', NULL, NULL, 'draft', '2025-05-24 09:24:25', '2025-05-25 17:57:25', 'with_prescription'),
(9, 10, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'single_vision', 'anti_reflective', NULL, NULL, 'submitted', '2025-05-24 09:24:49', '2025-05-25 17:57:25', 'with_prescription'),
(10, 10, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'single_vision', 'anti_reflective', NULL, NULL, 'draft', '2025-05-24 09:25:00', '2025-05-25 17:57:25', 'with_prescription'),
(11, 10, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'single_vision', 'anti_reflective', NULL, NULL, 'submitted', '2025-05-24 09:25:01', '2025-05-25 17:57:25', 'with_prescription'),
(12, 10, 14, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'single_vision', 'anti_reflective', NULL, NULL, 'submitted', '2025-05-24 10:17:22', '2025-05-25 17:57:25', 'with_prescription');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount_price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 10,
  `brand_id` int(11) DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`images`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category_id` int(11) DEFAULT NULL,
  `prescription_required` tinyint(1) DEFAULT 0,
  `frame_types_available` enum('normal','prescription','both') DEFAULT 'both',
  `facial_structure` enum('round','oval','square','heart','diamond','triangle','all') DEFAULT 'all'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `discount_price`, `stock`, `brand_id`, `images`, `created_at`, `category_id`, `prescription_required`, `frame_types_available`, `facial_structure`) VALUES
(1, 'Ray-Ban RB2210', 'Ray-Ban RB2210\r\nSize Details: 53🔲20 145\r\n\r\nLens Color: Green\r\n\r\nElevate your style with the Ray-Ban RB2210, a perfect fusion of fashion and functionality. Designed to provide optimal protection from harmful UV rays, these sunglasses offer not just comfort but also a statement piece to complete any look.\r\n\r\nKey Features:\r\nUV Protection: Safeguard your eyes with 100% UVA/UVB protection, perfect for sunny days.\r\n\r\nFrame Material: Crafted from Acetate, offering durability and a lightweight feel for all-day comfort.\r\n\r\nHigh-Quality Lenses: Engineered to reduce brightness while offering 100% UV protection, ensuring a clear and comfortable view.\r\n\r\nDesign: Sleek, modern, and timeless design that suits every face shape.\r\n\r\nWhether you\'re on the go or lounging outdoors, the Ray-Ban RB2210 is the ultimate accessory for both style and performance. Get yours today!', 5800.00, 5200.00, 19, 1, '[\"..\\/uploads\\/67f65bd4d8f0e_product_image-img_1051-0498 (1).webp\",\"..\\/uploads\\/67f65be3df3fd_image (4).webp\",\"..\\/uploads\\/67f65bf3266ee_product_image-img_1053-2559 (1).webp\",\"..\\/uploads\\/67f65c02600b8_product_image-img_1054-8632 (1).webp\"]', '2025-03-07 09:58:41', 2, 1, NULL, 'all'),
(2, 'Oakley Gascan Sunglasses for Men', 'Timeless Style Meets Ultimate Protection\r\n\r\nExperience the perfect combination of style, comfort, and performance with our expertly crafted sunglasses. Designed for every occasion, they offer unparalleled protection and timeless appeal.\r\n\r\nKey Highlights:\r\n100% UV Protection: Safeguard your eyes from harmful UVA and UVB rays.\r\n\r\nDurable Frames: Built with high quality acetate, ensuring a lightweight yet sturdy fit.\r\n\r\nHigh-Quality Lenses: Engineered to reduce brightness while offering 100% UV protection, ensuring a clear and comfortable view.\r\n\r\nVersatile Design: Flattering for all face shapes and ideal for both casual and formal wear.\r\n\r\nWhether you\'re hitting the beach, driving through scenic routes, or simply enjoying a sunny day, these sunglasses are the perfect accessory to elevate your look and protect your eyes.', 3500.00, 2800.00, 12, 2, '[\"https:\\/\\/cdn2.blanxer.com\\/uploads\\/64205ca09ab9997729605f15\\/product_image-img_0911-5820.webp\",\"https:\\/\\/cdn2.blanxer.com\\/uploads\\/64205ca09ab9997729605f15\\/product_image-img_0912-7064.webp\",\"https:\\/\\/cdn2.blanxer.com\\/uploads\\/64205ca09ab9997729605f15\\/product_image-img_0913-5505.webp\",\"https:\\/\\/cdn2.blanxer.com\\/uploads\\/64205ca09ab9997729605f15\\/product_image-img_0914-2828.webp\"]', '2025-03-07 09:58:41', 1, 0, NULL, 'all'),
(3, 'Gucci Square Unisex Leopard Print Sunglasses', 'Description\r\n\r\nGucci [GG1084S 008G] Sunglasses\r\n\r\nElevate your style with the Gucci [GG1084S 008G], a perfect fusion of fashion and functionality. Designed to provide optimal protection from harmful UV rays, these sunglasses offer not just comfort but also a statement piece to complete any look.\r\n\r\nKey Features:\r\nUV Protection: Safeguard your eyes with 100% UVA/UVB protection, perfect for sunny days.\r\n\r\nFrame Material: Crafted from premium acetate, offering durability and a lightweight feel for all-day comfort.\r\n\r\nLens Type: Equipped with gradient lenses to reduce glare and enhance visual clarity.\r\n\r\nDesign: Sleek, modern, and timeless design that suits every face shape.\r\n\r\nSize Details:\r\nLens Width: 53 mm\r\n\r\nBridge Width: 20 mm\r\n\r\nTemple Length: 145 mm\r\n\r\nWhether you\'re on the go or lounging outdoors, the Gucci [GG1084S 008G] is the ultimate accessory for both style and performance. Get yours today!', 5000.00, 2200.00, 10, 3, '[\"..\\/uploads\\/6833e92051ccb_image1.webp\",\"..\\/uploads\\/6833e920527c1_image2.webp\",\"..\\/uploads\\/6833e92052b3e_image3.webp\",\"..\\/uploads\\/6833e92052e66_image4.webp\",\"..\\/uploads\\/6833e920530fc_image5.webp\"]', '2025-03-07 09:58:41', 1, 0, NULL, 'all'),
(4, 'Versace Oval Women\'s Sunglasses', 'Description\n\nVersace Sunglasses\n\nElevate your style with the Versace Sunglass, a perfect fusion of fashion and functionality. Designed to provide optimal protection from harmful UV rays, these sunglasses offer not just comfort but also a statement piece to complete any look.\n\nKey Features:\nUV Protection: Safeguard your eyes with 100% UVA/UVB protection, perfect for sunny days.\n\nFrame Material: Crafted from premium polycarbonate, offering durability and a lightweight feel for all-day comfort.\n\nLens Type: Equipped with non-polarized lenses to reduce glare and enhance visual clarity.\n\nDesign: Sleek, modern, and timeless design that suits every face shape.\n\nWhether you\'re on the go or lounging outdoors, the Versace Sunglass is the ultimate accessory for both style and performance. Get yours today!', 1500.00, 0.00, 8, 4, '[\"https://cdn2.blanxer.com/uploads/64205ca09ab9997729605f15/product_image-img_0749-7065.webp\", \"https://cdn2.blanxer.com/uploads/64205ca09ab9997729605f15/product_image-img_0750-8111.webp\",\"https://cdn2.blanxer.com/uploads/64205ca09ab9997729605f15/product_image-img_0751-4585.webp\",\"https://cdn2.blanxer.com/uploads/64205ca09ab9997729605f15/product_image-img_0752-1313.webp\"]', '2025-03-07 09:58:41', NULL, 0, NULL, 'all'),
(5, 'Prada Symbole sunglasses\n', 'Prada Symbole sunglasses\n\nElevate your style with the Prada Symbole Sunglasses, a perfect fusion of fashion and functionality. Designed to provide optimal protection from harmful UV rays, these sunglasses offer not just comfort but also a statement piece to complete any look.\n\nKey Features:\nUV Protection: Safeguard your eyes with 100% UVA/UVB protection, perfect for sunny days.\n\nFrame Material: Crafted from premium acetate, offering durability and a lightweight feel for all-day comfort.\n\nHigh-Quality Lenses: Engineered to reduce brightness while offering 100% UV protection, ensuring a clear and comfortable view.\n\nDesign: Sleek, modern, and timeless design that suits every face shape.\nWhether you\'re on the go or lounging outdoors, the Prada Symbole sunglasses is the ultimate accessory for both style and performance. Get yours today!', 7200.00, 6500.00, 10, 5, '[\"https://cdn2.blanxer.com/uploads/64205ca09ab9997729605f15/product_image-img_0721-1809.webp\", \"https://cdn2.blanxer.com/uploads/64205ca09ab9997729605f15/product_image-img_0722-0291.webp\",\n\"https://cdn2.blanxer.com/uploads/64205ca09ab9997729605f15/product_image-img_0723-5250.webp\",\n\"https://cdn2.blanxer.com/uploads/64205ca09ab9997729605f15/product_image-img_0724-6530.webp\"]', '2025-03-07 09:58:41', NULL, 0, NULL, 'all'),
(6, 'Emporio Armani Unisex Sunglass\n', 'Description\n\nEmporio Armani [Model] Sunglasses\n\nElevate your style with the Emporio Armani [Model], a perfect fusion of fashion and functionality. Designed to provide optimal protection from harmful UV rays, these sunglasses offer not just comfort but also a statement piece to complete any look.\n\nKey Features:\nUV Protection: Safeguard your eyes with 100% UVA/UVB protection, perfect for sunny days.\n\nFrame Material: Crafted from premium acetate, offering durability and a lightweight feel for all-day comfort.\n\nLens Type: Equipped with gradient lenses to reduce glare and enhance visual clarity.\n\nDesign: Sleek, modern, and timeless design that suits every face shape.\n\nSize Details:\nLens Width: XX mm\n\nBridge Width: XX mm\n\nTemple Length: 145 mm\n\nWhether you\'re on the go or lounging outdoors, the Emporio Armani [Model] is the ultimate accessory for both style and performance. Get yours today!', 7000.00, 4500.00, 5, 6, '[\"https://cdn2.blanxer.com/uploads/64205ca09ab9997729605f15/product_image-img_0443-1219.webp\", \"https://cdn2.blanxer.com/uploads/64205ca09ab9997729605f15/product_image-img_0445-3477.webp\",\"https://cdn2.blanxer.com/uploads/64205ca09ab9997729605f15/product_image-img_0444-6658.webp\"]', '2025-03-07 09:58:41', NULL, 0, NULL, 'all'),
(7, 'Tom Ford Square Women\'s Sunglass\n', 'Description\n\nTom Ford [Model] Sunglasses\n\nElevate your style with the Tom Ford [Model], a perfect fusion of fashion and functionality. Designed to provide optimal protection from harmful UV rays, these sunglasses offer not just comfort but also a statement piece to complete any look.\n\nKey Features:\nUV Protection: Safeguard your eyes with 100% UVA/UVB protection, perfect for sunny days.\n\nFrame Material: Crafted from premium acetate, offering durability and a lightweight feel for all-day comfort.\n\nLens Type: Equipped with gradient lenses to reduce glare and enhance visual clarity.\n\nDesign: Sleek, modern, and timeless design that suits every face shape.\n\nSize Details:\nLens Width: XX mm\n\nBridge Width: XX mm\n\nTemple Length: 145 mm\n\nWhether you\'re on the go or lounging outdoors, the Tom Ford [Model] is the ultimate accessory for both style and performance. Get yours today!', 7000.00, 4500.00, 7, 7, '[\"https://cdn2.blanxer.com/uploads/64205ca09ab9997729605f15/product_image-img_0438-5827.webp\", \"https://cdn2.blanxer.com/uploads/64205ca09ab9997729605f15/product_image-img_0437-8356.webp\",\"https://cdn2.blanxer.com/uploads/64205ca09ab9997729605f15/product_image-img_0439-8971.webp\"]', '2025-03-07 09:58:41', NULL, 0, NULL, 'all'),
(8, 'Burberry Square Women\'s Sunglass\n', 'Description\n\nBurberry [Model] Sunglasses\n\nElevate your style with the Burberry [Model], a perfect fusion of fashion and functionality. Designed to provide optimal protection from harmful UV rays, these sunglasses offer not just comfort but also a statement piece to complete any look.\n\nKey Features:\nUV Protection: Safeguard your eyes with 100% UVA/UVB protection, perfect for sunny days.\n\nFrame Material: Crafted from premium acetate, offering durability and a lightweight feel for all-day comfort.\n\nLens Type: Equipped with gradient lenses to reduce glare and enhance visual clarity.\n\nDesign: Sleek, modern, and timeless design that suits every face shape.\n\nSize Details:\nLens Width: XX mm\n\nBridge Width: XX mm\n\nTemple Length: 145 mm\n\nWhether you\'re on the go or lounging outdoors, the Burberry [Model] is the ultimate accessory for both style and performance. Get yours today!', 7000.00, 4500.00, 9, 8, '[\"https://cdn2.blanxer.com/uploads/64205ca09ab9997729605f15/product_image-img_0434-7678.webp\", \"https://cdn2.blanxer.com/uploads/64205ca09ab9997729605f15/product_image-img_0436-8460.webp\",\"https://cdn2.blanxer.com/uploads/64205ca09ab9997729605f15/product_image-img_0435-8310.webp\"]', '2025-03-07 09:58:41', NULL, 0, NULL, 'all'),
(14, 'Magic TR Transparent, Blue-cut Frame (Small Cat-eye)', 'The Magic TR Transparent Prescription Frame offers a sleek, lightweight design with a modern, clear finish. Durable and comfortable, it provides a stylish yet subtle look for everyday wear. Perfect for those seeking both function and fashion in their eyewear.\r\n\r\nComes with:\r\n\r\nCleaning Cloth\r\n\r\nChain Cover', 1299.00, 950.00, 1, 1, '[\"..\\/uploads\\/67f65a9e486d7_product_image-img_0185-0987 (1).webp\"]', '2025-03-29 09:05:53', 4, 1, NULL, 'all'),
(20, 'Magic TR Transparent, Blue-cut Frame (Big Cat-eye)', 'The Magic TR Transparent Prescription Frame offers a sleek, lightweight design with a modern, clear finish. Durable and comfortable, it provides a stylish yet subtle look for everyday wear. Perfect for those seeking both function and fashion in their eyewear.\r\n\r\nComes with:\r\n\r\nCleaning Cloth\r\n\r\nChain Cove', 1299.00, 950.00, 1, 4, '[\"..\\/uploads\\/67f6586be8d5f_product_image-img_0169-9658 (1).webp\"]', '2025-03-29 09:05:53', 4, 1, NULL, 'oval'),
(36, 'Testing', 'The best Sunglasses ever', 20000.00, 1500.00, 3, 1, '[\"..\\/uploads\\/682f1704bfba2_product_image-img_1054-8632 (1).webp\"]', '2025-05-22 10:07:09', 1, 0, 'both', 'oval'),
(46, 'sfddsf', 'zdfgv;oihcvlijzcxlvijzdsflkj', 4900.00, 900.00, 3, 5, '[\"..\\/uploads\\/68314f77312c9_product_image-img_1052-6304.webp\",\"..\\/uploads\\/68314f7731c0c_product_image-img_1051-0498.webp\"]', '2025-05-24 04:42:57', 2, 0, 'both', 'round'),
(47, 'hgasdgasd', 'Hello this is good product', 20000.00, 2000.00, 5, 1, '[\"..\\/uploads\\/683348f231ed8_product_image-img_1054-8632.webp\"]', '2025-05-25 16:44:10', 1, 0, 'both', 'all');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `user_password` varchar(255) NOT NULL,
  `registration_date` datetime NOT NULL DEFAULT current_timestamp(),
  `phone` varchar(20) NOT NULL DEFAULT '',
  `address` text NOT NULL DEFAULT '',
  `city` varchar(100) NOT NULL DEFAULT '',
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_name`, `user_email`, `user_password`, `registration_date`, `phone`, `address`, `city`, `active`) VALUES
(10, 'Sumiran Shrestha', 'sumiranshrestha22@gmail.com', '$2y$10$hK9eP3lVeKi7cDUDzCyleu4pfOI8XcAeStS2A3gyRGJkmRuEwyYmi', '2025-04-20 10:14:42', '9843641720', 'Tokha', 'Kathmandu', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `brand_name` (`brand_name`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `prescription_id` (`prescription_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `nmc_number` (`nmc_number`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `prescription_id` (`prescription_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `prescription_frames`
--
ALTER TABLE `prescription_frames`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `prescription_orders`
--
ALTER TABLE `prescription_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `prescription_id` (`prescription_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_brand_id` (`brand_id`),
  ADD KEY `fk_category` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_email` (`user_email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=321;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=206;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `prescription_frames`
--
ALTER TABLE `prescription_frames`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `prescription_orders`
--
ALTER TABLE `prescription_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_3` FOREIGN KEY (`prescription_id`) REFERENCES `prescription_frames` (`id`),
  ADD CONSTRAINT `cart_ibfk_4` FOREIGN KEY (`prescription_id`) REFERENCES `prescription_frames` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`prescription_id`) REFERENCES `prescription_frames` (`id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`prescription_id`) REFERENCES `prescription_frames` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `prescription_frames`
--
ALTER TABLE `prescription_frames`
  ADD CONSTRAINT `prescription_frames_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `prescription_orders`
--
ALTER TABLE `prescription_orders`
  ADD CONSTRAINT `prescription_orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `prescription_orders_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `prescription_orders_ibfk_3` FOREIGN KEY (`prescription_id`) REFERENCES `prescription_frames` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

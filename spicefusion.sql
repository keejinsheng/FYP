-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 13, 2026 at 03:22 PM
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
-- Database: `spicefusion`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_user`
--

CREATE TABLE `admin_user` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `role` enum('Super Admin','Manager','Staff') DEFAULT 'Staff',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(100) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_user`
--

INSERT INTO `admin_user` (`admin_id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `is_active`, `created_at`, `updated_at`, `reset_token`, `token_expiry`) VALUES
(1, 'jason', 'jinsheng122@gmail.com', '$2y$10$lemN0FW3zBPYVhVoGSFE1e32KldP68stbCXlxGF/Spq1v1kdKGFhe', 'kee', 'jin sheng', 'Staff', 1, '2025-10-30 20:37:04', '2026-01-12 19:32:09', '296969', '2026-01-12 20:42:09'),
(2, 'Shaun', 'shaun123@gmail.com', '$2y$10$XNd.3RaVEzaS12M39AMsue.2ISG.fTSchCVoSpXSf8nVL9.QZq6Gm', 'Chua Shen', 'Lin Shaun', 'Staff', 1, '2025-11-03 17:20:25', '2026-01-13 09:24:39', NULL, NULL),
(3, 'SuperAdmin', 'zee271810@gmail.com', '$2y$10$MQxGOjuPMCR944.6nE3VwO2IreJ9.XMvMEyOh59rWYTmJrrl7Ku6e', 'Kee', 'Jin Sheng', 'Super Admin', 1, '2025-11-19 06:28:38', '2026-01-13 09:14:43', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`category_id`, `category_name`, `description`, `image`, `is_active`, `created_at`) VALUES
(1, 'Main Course', 'Delicious main dishes', NULL, 1, '2025-10-28 06:04:20'),
(2, 'Appetizers', 'Starters and snacks', NULL, 1, '2025-10-28 06:04:20'),
(3, 'Beverages', 'Drinks and refreshments', NULL, 1, '2025-10-28 06:04:20'),
(4, 'Desserts', 'Sweet treats', NULL, 1, '2025-10-28 06:04:20'),
(5, 'Rice Dishes', 'Various rice-based meals', NULL, 1, '2025-10-28 06:04:20'),
(6, 'Noodles', 'Noodle dishes', NULL, 1, '2025-10-28 06:04:20'),
(7, 'Soups', 'Warm and comforting soups', NULL, 1, '2025-10-28 06:04:20');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_address`
--

CREATE TABLE `delivery_address` (
  `address_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(100) DEFAULT 'Malaysia',
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_address`
--

INSERT INTO `delivery_address` (`address_id`, `user_id`, `address_line1`, `address_line2`, `city`, `state`, `postal_code`, `country`, `is_default`, `created_at`) VALUES
(1, 1, '21 Jalan Kejayaan 18', 'Taman Universiti', 'Skudai', 'Johor', '81300', 'Malaysia', 1, '2025-10-30 20:48:17'),
(2, 2, '28，jalan nusa bayu', '3/10 Taman nusa bayu', 'gelang patah', 'johor', '79200', 'Malaysia', 1, '2025-11-12 11:59:58');

-- --------------------------------------------------------

--
-- Table structure for table `dummy_bank`
--

CREATE TABLE `dummy_bank` (
  `bank_id` int(11) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `cardholder_name` varchar(100) NOT NULL,
  `card_number` varchar(19) NOT NULL COMMENT 'Full card number (will be stored for verification)',
  `expiry_date` varchar(5) NOT NULL COMMENT 'Format: MM/YY',
  `cvv` varchar(4) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1 COMMENT '1 = active, 0 = inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dummy_bank`
--

INSERT INTO `dummy_bank` (`bank_id`, `bank_name`, `cardholder_name`, `card_number`, `expiry_date`, `cvv`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Maybank', 'Kee Jin Sheng', '1234567890123456', '12/25', '123', 1, '2025-12-24 15:17:59', '2025-12-28 08:22:34'),
(2, 'CIMB', 'Chua Shen Lin Shaun', '9876543210987654', '06/26', '456', 1, '2025-12-24 15:17:59', '2025-12-28 08:22:54'),
(3, 'Public Bank', 'Lim Xing Yi', '5555666677778888', '09/27', '789', 1, '2025-12-24 15:17:59', '2025-12-28 08:23:06'),
(4, 'Hong Leong Bank', 'Alice Brown', '1111222233334444', '03/28', '321', 1, '2025-12-24 15:17:59', '2025-12-24 15:17:59'),
(5, 'RHB Bank', 'Charlie Wilson', '9999888877776666', '11/29', '654', 1, '2025-12-24 15:17:59', '2025-12-24 15:17:59');

-- --------------------------------------------------------

--
-- Table structure for table `order`
--

CREATE TABLE `order` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_id` int(11) DEFAULT NULL,
  `order_number` varchar(50) NOT NULL,
  `order_status` enum('Pending','Confirmed','Preparing','Ready','Out for Delivery','Delivered','Cancelled') DEFAULT 'Pending',
  `order_type` enum('Dine-in','Takeaway','Delivery') DEFAULT 'Delivery',
  `subtotal` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `delivery_fee` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `special_instructions` text DEFAULT NULL,
  `estimated_delivery_time` timestamp NULL DEFAULT NULL,
  `actual_delivery_time` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order`
--

INSERT INTO `order` (`order_id`, `user_id`, `address_id`, `order_number`, `order_status`, `order_type`, `subtotal`, `tax_amount`, `delivery_fee`, `total_amount`, `special_instructions`, `estimated_delivery_time`, `actual_delivery_time`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'SF202510306559', 'Delivered', 'Delivery', 14.90, 0.89, 5.00, 20.79, '', '2025-10-30 21:33:17', NULL, '2025-10-30 20:48:17', '2025-12-10 17:33:12'),
(2, 1, 1, 'SF202510309781', 'Delivered', 'Delivery', 17.80, 1.07, 5.00, 23.87, '', '2025-10-30 22:30:29', NULL, '2025-10-30 21:45:29', '2025-12-10 17:33:11'),
(3, 1, 1, 'SF202510303825', 'Pending', 'Delivery', 32.70, 1.96, 5.00, 39.66, '', '2025-10-30 22:38:34', NULL, '2025-10-30 21:53:34', '2025-12-10 17:33:10'),
(4, 1, 1, 'SF202510303929', 'Pending', 'Delivery', 21.80, 1.31, 5.00, 28.11, '', '2025-10-30 22:54:30', NULL, '2025-10-30 22:09:30', '2025-12-10 17:33:12'),
(5, 2, 2, 'SF202511124878', 'Delivered', 'Delivery', 37.80, 2.27, 5.00, 45.07, 'tak nak tofu', '2025-11-12 12:51:04', NULL, '2025-11-12 12:06:04', '2025-12-10 17:33:13'),
(6, 1, 1, 'SF202512249276', 'Pending', 'Delivery', 8.90, 0.53, 5.00, 14.43, '', '2025-12-24 16:09:46', NULL, '2025-12-24 15:24:46', '2025-12-24 15:24:46'),
(7, 1, 1, 'SF202512249356', 'Pending', 'Delivery', 14.90, 0.89, 5.00, 20.79, '', '2025-12-24 16:13:52', NULL, '2025-12-24 15:28:52', '2025-12-24 15:28:52'),
(8, 1, 1, 'SF202512244615', 'Delivered', 'Delivery', 34.70, 2.08, 5.00, 41.78, '', '2025-12-24 16:15:47', NULL, '2025-12-24 15:30:47', '2025-12-24 15:58:54'),
(11, 1, 1, 'SF202512245565', 'Pending', 'Delivery', 23.80, 1.43, 5.00, 30.23, '', '2025-12-24 17:06:58', NULL, '2025-12-24 16:21:58', '2025-12-24 16:21:58'),
(12, 1, 1, 'SF202512243582', 'Pending', 'Delivery', 23.80, 1.43, 5.00, 30.23, '', '2025-12-24 17:11:02', NULL, '2025-12-24 16:26:02', '2025-12-24 16:26:02'),
(13, 1, 1, 'SF202512240633', 'Pending', 'Delivery', 14.90, 0.89, 5.00, 20.79, '', '2025-12-24 17:11:45', NULL, '2025-12-24 16:26:45', '2025-12-24 16:26:45'),
(14, 1, 1, 'SF202512284027', 'Delivered', 'Delivery', 33.80, 2.03, 5.00, 40.83, '', '2025-12-28 09:09:07', NULL, '2025-12-28 08:24:07', '2025-12-28 08:31:23'),
(15, 1, 1, 'SF202512280994', 'Pending', 'Delivery', 32.70, 1.96, 5.00, 39.66, '', '2025-12-28 09:22:26', NULL, '2025-12-28 08:37:26', '2025-12-28 08:37:26'),
(16, 2, 2, 'SF202512282296', 'Pending', 'Delivery', 39.00, 2.34, 5.00, 46.34, '', '2025-12-28 09:30:29', NULL, '2025-12-28 08:45:29', '2025-12-28 08:45:29'),
(17, 1, 1, 'SF202601065308', 'Delivered', 'Delivery', 8.90, 0.53, 5.00, 14.43, '', '2026-01-06 06:42:22', NULL, '2026-01-06 05:57:22', '2026-01-06 11:44:20'),
(18, 1, 1, 'SF202601080595', 'Pending', 'Delivery', 73.10, 4.39, 5.00, 82.49, '', '2026-01-08 09:20:01', NULL, '2026-01-08 08:35:01', '2026-01-08 08:35:01'),
(19, 1, 1, 'SF202601096459', 'Pending', 'Delivery', 45.30, 2.72, 5.00, 53.02, 'ok', '2026-01-09 05:12:31', NULL, '2026-01-09 04:27:31', '2026-01-09 04:27:31'),
(20, 1, 1, 'SF202601094791', 'Pending', 'Delivery', 24.10, 1.45, 5.00, 30.55, '', '2026-01-09 06:06:55', NULL, '2026-01-09 05:21:55', '2026-01-09 05:21:55'),
(21, 1, 1, 'SF202601128039', 'Pending', 'Delivery', 37.40, 2.24, 5.00, 44.64, '', '2026-01-12 20:14:45', NULL, '2026-01-12 19:29:45', '2026-01-12 19:29:45');

-- --------------------------------------------------------

--
-- Table structure for table `order_item`
--

CREATE TABLE `order_item` (
  `item_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `special_instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_item`
--

INSERT INTO `order_item` (`item_id`, `order_id`, `product_id`, `quantity`, `unit_price`, `total_price`, `special_instructions`, `created_at`) VALUES
(1, 1, 14, 1, 14.90, 14.90, NULL, '2025-10-30 20:48:17'),
(2, 2, 4, 2, 8.90, 17.80, NULL, '2025-10-30 21:45:29'),
(3, 3, 6, 3, 10.90, 32.70, NULL, '2025-10-30 21:53:34'),
(4, 4, 14, 1, 14.90, 14.90, NULL, '2025-10-30 22:09:30'),
(6, 5, 3, 2, 18.90, 37.80, NULL, '2025-11-12 12:06:04'),
(7, 6, 4, 1, 8.90, 8.90, NULL, '2025-12-24 15:24:46'),
(8, 7, 14, 1, 14.90, 14.90, NULL, '2025-12-24 15:28:52'),
(9, 8, 6, 1, 10.90, 10.90, NULL, '2025-12-24 15:30:47'),
(10, 8, 4, 1, 8.90, 8.90, NULL, '2025-12-24 15:30:47'),
(11, 8, 14, 1, 14.90, 14.90, NULL, '2025-12-24 15:30:47'),
(12, 12, 14, 1, 14.90, 14.90, NULL, '2025-12-24 16:26:02'),
(13, 12, 4, 1, 8.90, 8.90, NULL, '2025-12-24 16:26:02'),
(14, 13, 14, 1, 14.90, 14.90, NULL, '2025-12-24 16:26:45'),
(15, 14, 6, 1, 10.90, 10.90, NULL, '2025-12-28 08:24:07'),
(16, 14, 2, 1, 22.90, 22.90, NULL, '2025-12-28 08:24:07'),
(17, 15, 4, 2, 8.90, 17.80, NULL, '2025-12-28 08:37:26'),
(18, 15, 14, 1, 14.90, 14.90, NULL, '2025-12-28 08:37:26'),
(19, 16, 18, 2, 7.60, 15.20, NULL, '2025-12-28 08:45:29'),
(20, 16, 4, 1, 8.90, 8.90, NULL, '2025-12-28 08:45:29'),
(21, 16, 14, 1, 14.90, 14.90, NULL, '2025-12-28 08:45:29'),
(22, 17, 4, 1, 8.90, 8.90, NULL, '2026-01-06 05:57:22'),
(23, 18, 9, 1, 7.90, 7.90, NULL, '2026-01-08 08:35:01'),
(24, 18, 2, 1, 22.90, 22.90, NULL, '2026-01-08 08:35:01'),
(25, 18, 6, 1, 10.90, 10.90, NULL, '2026-01-08 08:35:01'),
(26, 18, 18, 1, 7.60, 7.60, NULL, '2026-01-08 08:35:01'),
(27, 18, 4, 1, 8.90, 8.90, NULL, '2026-01-08 08:35:01'),
(28, 18, 14, 1, 14.90, 14.90, NULL, '2026-01-08 08:35:01'),
(29, 19, 9, 1, 7.90, 7.90, NULL, '2026-01-09 04:27:31'),
(30, 19, 18, 1, 7.60, 7.60, NULL, '2026-01-09 04:27:31'),
(31, 19, 14, 2, 14.90, 29.80, NULL, '2026-01-09 04:27:31'),
(32, 20, 18, 2, 7.60, 15.20, NULL, '2026-01-09 05:21:55'),
(33, 20, 4, 1, 8.90, 8.90, NULL, '2026-01-09 05:21:55'),
(34, 21, 18, 1, 7.60, 7.60, NULL, '2026-01-12 19:29:45'),
(35, 21, 14, 2, 14.90, 29.80, NULL, '2026-01-12 19:29:45');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` enum('Cash','Card','Credit Card','Debit Card','PayPal','Online Banking') NOT NULL,
  `payment_status` enum('Pending','Processing','Completed','Failed','Refunded') DEFAULT 'Pending',
  `amount` decimal(10,2) NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payment_id`, `order_id`, `payment_method`, `payment_status`, `amount`, `transaction_id`, `payment_date`) VALUES
(1, 1, 'Cash', 'Completed', 20.79, NULL, '2025-10-30 20:48:17'),
(2, 2, 'Credit Card', 'Completed', 23.87, NULL, '2025-10-30 21:45:29'),
(3, 3, 'Credit Card', 'Completed', 39.66, NULL, '2025-10-30 21:53:34'),
(4, 4, 'Cash', 'Completed', 28.11, NULL, '2025-10-30 22:09:30'),
(5, 5, 'Cash', 'Completed', 45.07, NULL, '2025-11-12 12:06:04'),
(6, 15, 'Online Banking', 'Completed', 39.66, NULL, '2025-12-28 08:37:26'),
(7, 16, 'Credit Card', 'Completed', 46.34, NULL, '2025-12-28 08:45:29'),
(8, 17, 'Credit Card', 'Completed', 14.43, NULL, '2026-01-06 05:57:22'),
(9, 18, 'Credit Card', 'Completed', 82.49, NULL, '2026-01-08 08:35:01'),
(10, 19, 'Online Banking', 'Completed', 53.02, NULL, '2026-01-09 04:27:31'),
(11, 20, 'Credit Card', 'Completed', 30.55, NULL, '2026-01-09 05:21:55'),
(12, 21, 'Online Banking', 'Completed', 44.64, NULL, '2026-01-12 19:29:45');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `product_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `stock_quantity` int(11) DEFAULT 0,
  `preparation_time` int(11) DEFAULT 15,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`product_id`, `category_id`, `product_name`, `description`, `price`, `image`, `is_available`, `stock_quantity`, `preparation_time`, `is_featured`, `created_at`, `updated_at`) VALUES
(1, 1, 'Rendang Beef', 'Tender beef cooked in rich coconut milk and spices', 25.90, 'rendang_beef.png', 1, 50, 15, 1, '2025-10-28 06:04:20', '2025-10-28 06:04:20'),
(2, 1, 'Kung Pao Chicken', 'Spicy diced chicken with peanuts and vegetables', 22.90, 'kung_pau_chciken.png', 1, 43, 15, 1, '2025-10-28 06:04:20', '2026-01-08 08:35:01'),
(3, 1, 'Mapo Tofu', 'Spicy tofu with minced meat in Sichuan sauce', 18.90, 'mapo_toufu.png', 1, 38, 15, 0, '2025-10-28 06:04:20', '2025-11-12 12:06:04'),
(4, 2, 'Char Siu Bao', 'Steamed BBQ pork buns', 8.90, 'char_siu_bao.png', 1, 21, 15, 1, '2025-10-28 06:04:20', '2026-01-09 05:21:55'),
(5, 2, 'Siew Mai', 'Steamed pork and shrimp dumplings', 12.90, 'siew_mai.png', 1, 35, 15, 0, '2025-10-28 06:04:20', '2025-10-28 06:04:20'),
(6, 2, 'Dumpling', 'Pan-fried dumplings with pork filling', 10.90, 'dumpling.png', 1, 19, 15, 0, '2025-10-28 06:04:20', '2026-01-08 08:35:01'),
(8, 3, 'Tea', 'Traditional Chinese tea', 5.90, 'tea.png', 1, 100, 15, 0, '2025-10-28 06:04:20', '2025-10-28 06:04:20'),
(9, 3, 'Lime Juice', 'Refreshing lime juice', 7.90, 'lime_juice.png', 1, 78, 15, 0, '2025-10-28 06:04:20', '2026-01-09 04:27:31'),
(10, 4, 'Taiyaki', 'Fish-shaped waffle with sweet filling', 8.90, 'taiyaki.png', 1, 20, 15, 1, '2025-10-28 06:04:20', '2025-10-28 06:04:20'),
(11, 4, 'Tang Yuan', 'Sweet glutinous rice balls', 9.90, 'tang_yuan.png', 1, 25, 15, 0, '2025-10-28 06:04:20', '2025-10-28 06:04:20'),
(12, 5, 'Nasi Ayam Geprek', 'Crispy chicken with rice and sambal', 16.90, 'nasi_ayam_geprek.png', 1, 40, 15, 1, '2025-10-28 06:04:20', '2025-10-28 06:04:20'),
(13, 5, 'Nasi Campur', 'Mixed rice with various side dishes', 18.90, 'nasi_campur.png', 1, 35, 15, 0, '2025-10-28 06:04:20', '2025-10-28 06:04:20'),
(14, 6, 'Char Kuey Teow', 'Stir-fried flat rice noodles', 14.90, 'char_kuey_teow.png', 1, 88, 15, 1, '2025-10-28 06:04:20', '2026-01-12 19:29:45'),
(15, 6, 'Mie Goreng', 'Indonesian fried noodles', 13.90, 'mie_goreng.png', 1, 30, 15, 0, '2025-10-28 06:04:20', '2025-10-28 06:04:20'),
(16, 7, 'Wantan Soup', 'Clear soup with wonton dumplings', 12.90, 'wantan_soup.png', 1, 25, 15, 0, '2025-10-28 06:04:20', '2025-10-28 06:04:20'),
(17, 2, 'test', 'test', 0.00, 'food_690973d78f0799.82262871.jpeg', 1, 0, 15, 0, '2025-11-04 03:32:39', '2025-11-04 03:33:12'),
(18, 3, 'Coffee', 'Freshly brewed. Bold taste. pure energy.', 7.60, 'food_6939af6a832ea0.16874181.png', 1, 92, 15, 0, '2025-12-10 17:35:38', '2026-01-12 19:29:45');

-- --------------------------------------------------------

--
-- Table structure for table `review`
--

CREATE TABLE `review` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `is_verified_purchase` tinyint(1) DEFAULT 0,
  `is_approved` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `review`
--

INSERT INTO `review` (`review_id`, `user_id`, `product_id`, `order_id`, `rating`, `comment`, `is_verified_purchase`, `is_approved`, `created_at`) VALUES
(1, 1, 4, 2, 5, 'nice！', 1, 1, '2025-12-24 14:51:46'),
(2, 1, 6, 8, 5, 'nice', 1, 1, '2025-12-24 15:30:59'),
(3, 1, 4, 8, 5, 'nice', 1, 1, '2025-12-24 15:31:07'),
(4, 1, 14, 8, 2, 'no to bad', 1, 1, '2025-12-24 15:31:19'),
(5, 1, 18, 19, 1, 'too expensive', 1, 1, '2026-01-09 04:28:58'),
(6, 1, 14, 19, 1, '', 1, 1, '2026-01-09 04:29:00');

-- --------------------------------------------------------

--
-- Table structure for table `security_questions`
--

CREATE TABLE `security_questions` (
  `id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `security_questions`
--

INSERT INTO `security_questions` (`id`, `question`) VALUES
(1, 'What is your mother\'s maiden name?'),
(2, 'What was the name of your primary school?'),
(3, 'What is your favorite food?'),
(4, 'What city were you born in?'),
(5, 'What was the name of your first pet?');

-- --------------------------------------------------------

--
-- Table structure for table `shopping_cart`
--

CREATE TABLE `shopping_cart` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `special_instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shopping_cart`
--

INSERT INTO `shopping_cart` (`cart_id`, `user_id`, `product_id`, `quantity`, `special_instructions`, `created_at`, `updated_at`) VALUES
(18, 2, 4, 1, NULL, '2025-12-28 13:04:24', '2025-12-28 13:04:24'),
(19, 2, 14, 3, NULL, '2025-12-28 13:04:27', '2025-12-28 13:04:27'),
(20, 2, 9, 1, NULL, '2025-12-28 13:04:29', '2025-12-28 13:04:29'),
(34, 1, 4, 1, NULL, '2026-01-13 06:09:01', '2026-01-13 06:09:01');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT 'user.jpg',
  `is_active` tinyint(1) DEFAULT 1,
  `is_verified` tinyint(1) DEFAULT 0,
  `security_question_id` int(11) DEFAULT NULL,
  `security_answer_hash` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(100) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `phone`, `date_of_birth`, `gender`, `profile_image`, `is_active`, `is_verified`, `security_question_id`, `security_answer_hash`, `created_at`, `updated_at`, `reset_token`, `token_expiry`) VALUES
(1, 'herry', 'jinsheng122@gmail.com', '$2y$10$fFOawS3ulpKKqiUuMBdXqOS7q8ptAq0TbaUtdqHS4jZMvozQ.iXoK', 'herry', 'potter', '0187826588', '2005-06-13', 'Male', 'avatar_696619eb78a85_1768298987.png', 1, 0, 1, '$2y$10$h/vsHVGMbHfsfmunlt6jC.Zw1GZb23ZQJPPgrOB0zgUegPxlEPll2', '2025-10-30 20:39:35', '2026-01-13 10:09:47', '126565', '2026-01-12 20:31:04'),
(2, 'Kai Shun', 'Jkee1306@gmail.com', '$2y$10$ywBf5DpI2bcWa.0rq2tE7uq1DEdCG4AiCAklT4.xuGRv17iGOqr0y', 'Kevin', 'Kek', '0167099992', '2005-03-04', 'Male', 'user.jpg', 1, 0, NULL, NULL, '2025-11-12 11:54:36', '2025-12-28 08:43:15', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_user`
--
ALTER TABLE `admin_user`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `delivery_address`
--
ALTER TABLE `delivery_address`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `dummy_bank`
--
ALTER TABLE `dummy_bank`
  ADD PRIMARY KEY (`bank_id`),
  ADD KEY `idx_bank_name` (`bank_name`),
  ADD KEY `idx_card_number` (`card_number`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `address_id` (`address_id`),
  ADD KEY `idx_order_user` (`user_id`),
  ADD KEY `idx_order_status` (`order_status`);

--
-- Indexes for table `order_item`
--
ALTER TABLE `order_item`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_order_item_order` (`order_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `idx_payment_order` (`order_id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `idx_product_category` (`category_id`),
  ADD KEY `idx_product_featured` (`is_featured`);

--
-- Indexes for table `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `idx_review_product` (`product_id`),
  ADD KEY `idx_review_user` (`user_id`);

--
-- Indexes for table `security_questions`
--
ALTER TABLE `security_questions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_cart_user` (`user_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_security_question` (`security_question_id`),
  ADD KEY `idx_user_email` (`email`),
  ADD KEY `idx_user_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_user`
--
ALTER TABLE `admin_user`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `delivery_address`
--
ALTER TABLE `delivery_address`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `dummy_bank`
--
ALTER TABLE `dummy_bank`
  MODIFY `bank_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order`
--
ALTER TABLE `order`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `order_item`
--
ALTER TABLE `order_item`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `review`
--
ALTER TABLE `review`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `security_questions`
--
ALTER TABLE `security_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `delivery_address`
--
ALTER TABLE `delivery_address`
  ADD CONSTRAINT `delivery_address_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `order`
--
ALTER TABLE `order`
  ADD CONSTRAINT `order_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_ibfk_2` FOREIGN KEY (`address_id`) REFERENCES `delivery_address` (`address_id`) ON DELETE SET NULL;

--
-- Constraints for table `order_item`
--
ALTER TABLE `order_item`
  ADD CONSTRAINT `order_item_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_item_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `review_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `order` (`order_id`) ON DELETE SET NULL;

--
-- Constraints for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD CONSTRAINT `shopping_cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shopping_cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `fk_security_question` FOREIGN KEY (`security_question_id`) REFERENCES `security_questions` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 20, 2025 at 06:05 AM
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_user`
--

INSERT INTO `admin_user` (`admin_id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 'jason', 'jinsheng122@gmail.com', '$2y$10$AEIJET1Tvy4m5Y8A5ypMHOzFNSe.H7zgYPuo.U.hS2AfUw4tLxHpW', 'kee', 'jin sheng', 'Staff', 1, '2025-10-30 20:37:04', '2025-11-19 06:35:19'),
(3, 'Shaun', 'shaun123@gmail.com', '$2y$10$XNd.3RaVEzaS12M39AMsue.2ISG.fTSchCVoSpXSf8nVL9.QZq6Gm', 'Chua Shen', 'Lin Shaun', 'Staff', 1, '2025-11-03 17:20:25', '2025-11-03 17:20:25'),
(4, 'SuperAdmin', 'zee271810@gmail.com', '$2y$10$UlkbY1NUlFlbZgOxO8E.3eCix1cl7nXBul7I1TTQWaODWwY4K8zva', 'Kee', 'Jin Sheng', 'Super Admin', 1, '2025-11-19 06:28:38', '2025-11-19 06:34:04');

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
(1, 1, 1, 'SF202510306559', 'Delivered', 'Delivery', 14.90, 0.89, 5.00, 20.79, '', '2025-10-30 21:33:17', NULL, '2025-10-30 20:48:17', '2025-10-30 20:52:49'),
(2, 1, 1, 'SF202510309781', 'Delivered', 'Delivery', 17.80, 1.07, 5.00, 23.87, '', '2025-10-30 22:30:29', NULL, '2025-10-30 21:45:29', '2025-10-30 21:59:00'),
(3, 1, 1, 'SF202510303825', 'Confirmed', 'Delivery', 32.70, 1.96, 5.00, 39.66, '', '2025-10-30 22:38:34', NULL, '2025-10-30 21:53:34', '2025-10-30 21:57:16'),
(4, 1, 1, 'SF202510303929', 'Pending', 'Delivery', 21.80, 1.31, 5.00, 28.11, '', '2025-10-30 22:54:30', NULL, '2025-10-30 22:09:30', '2025-10-30 22:09:30'),
(5, 2, 2, 'SF202511124878', 'Delivered', 'Delivery', 37.80, 2.27, 5.00, 45.07, 'tak nak tofu', '2025-11-12 12:51:04', NULL, '2025-11-12 12:06:04', '2025-11-17 17:27:15');

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
(6, 5, 3, 2, 18.90, 37.80, NULL, '2025-11-12 12:06:04');

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
(5, 5, 'Cash', 'Completed', 45.07, NULL, '2025-11-12 12:06:04');

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
(2, 1, 'Kung Pao Chicken', 'Spicy diced chicken with peanuts and vegetables', 22.90, 'kung_pau_chciken.png', 1, 45, 15, 1, '2025-10-28 06:04:20', '2025-10-28 06:04:20'),
(3, 1, 'Mapo Tofu', 'Spicy tofu with minced meat in Sichuan sauce', 18.90, 'mapo_toufu.png', 1, 38, 15, 0, '2025-10-28 06:04:20', '2025-11-12 12:06:04'),
(4, 2, 'Char Siu Bao', 'Steamed BBQ pork buns', 8.90, 'char_siu_bao.png', 1, 30, 15, 1, '2025-10-28 06:04:20', '2025-10-28 06:04:20'),
(5, 2, 'Siew Mai', 'Steamed pork and shrimp dumplings', 12.90, 'siew_mai.png', 1, 35, 15, 0, '2025-10-28 06:04:20', '2025-10-28 06:04:20'),
(6, 2, 'Dumpling', 'Pan-fried dumplings with pork filling', 10.90, 'dumpling.png', 1, 22, 15, 0, '2025-10-28 06:04:20', '2025-10-30 21:53:34'),
(8, 3, 'Tea', 'Traditional Chinese tea', 5.90, 'tea.png', 1, 100, 15, 0, '2025-10-28 06:04:20', '2025-10-28 06:04:20'),
(9, 3, 'Lime Juice', 'Refreshing lime juice', 7.90, 'lime_juice.png', 1, 80, 15, 0, '2025-10-28 06:04:20', '2025-10-28 06:04:20'),
(10, 4, 'Taiyaki', 'Fish-shaped waffle with sweet filling', 8.90, 'taiyaki.png', 1, 20, 15, 1, '2025-10-28 06:04:20', '2025-10-28 06:04:20'),
(11, 4, 'Tang Yuan', 'Sweet glutinous rice balls', 9.90, 'tang_yuan.png', 1, 25, 15, 0, '2025-10-28 06:04:20', '2025-10-28 06:04:20'),
(12, 5, 'Nasi Ayam Geprek', 'Crispy chicken with rice and sambal', 16.90, 'nasi_ayam_geprek.png', 1, 40, 15, 1, '2025-10-28 06:04:20', '2025-10-28 06:04:20'),
(13, 5, 'Nasi Campur', 'Mixed rice with various side dishes', 18.90, 'nasi_campur.png', 1, 35, 15, 0, '2025-10-28 06:04:20', '2025-10-28 06:04:20'),
(14, 6, 'Char Kuey Teow', 'Stir-fried flat rice noodles', 14.90, 'char_kuey_teow.png', 1, 99, 15, 1, '2025-10-28 06:04:20', '2025-11-12 12:13:44'),
(15, 6, 'Mie Goreng', 'Indonesian fried noodles', 13.90, 'mie_goreng.png', 1, 30, 15, 0, '2025-10-28 06:04:20', '2025-10-28 06:04:20'),
(16, 7, 'Wantan Soup', 'Clear soup with wonton dumplings', 12.90, 'wantan_soup.png', 1, 25, 15, 0, '2025-10-28 06:04:20', '2025-10-28 06:04:20'),
(17, 2, 'test', 'test', 0.00, 'food_690973d78f0799.82262871.jpeg', 1, 0, 15, 0, '2025-11-04 03:32:39', '2025-11-04 03:33:12');

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
(3, 1, 4, 1, NULL, '2025-11-17 17:09:05', '2025-11-17 17:14:35');

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
(1, 'herry', 'jinsheng122@gmail.com', '$2y$10$T8y70GVGmMMf6mAwXCbG/OPe9aRyr6Wq0B4UJOsanORYrHCxaBjhq', 'herry', 'potter', '0187826588', '2005-06-13', 'Male', 'user.jpg', 1, 0, 1, '$2y$10$h/vsHVGMbHfsfmunlt6jC.Zw1GZb23ZQJPPgrOB0zgUegPxlEPll2', '2025-10-30 20:39:35', '2025-11-17 17:00:08', NULL, NULL),
(2, 'Kai Shun', 'Jkee1306@gmail.com', '$2y$10$MPU4XBb471KvtllYY6lgq.UjlEq1pUkI/eF/fO1UEijRfUiQ1xKM6', 'Kevin', 'Kek', '0167099992', '2005-03-04', 'Male', 'user.jpg', 1, 0, NULL, NULL, '2025-11-12 11:54:36', '2025-11-17 17:25:07', NULL, NULL);

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
-- AUTO_INCREMENT for table `order`
--
ALTER TABLE `order`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_item`
--
ALTER TABLE `order_item`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `review`
--
ALTER TABLE `review`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_questions`
--
ALTER TABLE `security_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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

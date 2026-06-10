-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 11, 2026 at 01:31 AM
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
-- Database: `zonse`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `icon`, `display_order`) VALUES
(1, 'Electronics', 'fa-plug', 1),
(2, 'Books', 'fa-book-open', 2),
(3, 'Clothing', 'fa-tshirt', 3),
(4, 'Sports', 'fa-running', 4),
(6, 'Furniture', 'fa-couch', 0),
(7, 'Home Appliances', 'fa-tv', 0);

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `conversation_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`conversation_id`, `product_id`, `buyer_id`, `seller_id`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 1, '2026-06-10 23:25:08', '2026-06-10 23:25:08'),
(2, 8, 3, 2, '2026-06-10 23:27:08', '2026-06-10 23:29:11');

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `conversation_id`, `sender_id`, `message_text`, `is_read`, `created_at`) VALUES
(1, 2, 3, 'Is is still available', 1, '2026-06-10 23:27:22'),
(2, 2, 2, 'Yes it is', 1, '2026-06-10 23:29:11');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `condition_status` enum('new','like_new','good','fair') DEFAULT 'good',
  `location` varchar(100) DEFAULT NULL,
  `status` enum('active','sold','reserved','deleted') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `slug`, `seller_id`, `category_id`, `title`, `description`, `price`, `condition_status`, `location`, `status`, `created_at`, `updated_at`) VALUES
(1, 'samsung-s23-ultra', 1, 1, 'Samsung S23 Ultra', 'Well-maintained Samsung S26 Ultra with charger.', 9500.00, '', 'Kitwe', 'active', '2026-06-01 15:56:57', '2026-06-10 15:28:17'),
(2, 'hp-pavilion-gaming-laptop', 1, 2, 'HP Pavilion Gaming Laptop', 'Gaming laptop with 16GB RAM and GTX graphics.', 12500.00, '', 'Kitwe', 'active', '2026-06-01 15:56:57', '2026-06-01 15:56:57'),
(3, 'samsung-55-smart-tv', 1, 1, 'Samsung 55 Inch Smart TV', '4K UHD Smart TV in excellent condition.', 7200.00, '', 'Lusaka', 'active', '2026-06-01 15:56:57', '2026-06-01 15:56:57'),
(5, 'mini-washing-machine-2', 2, 3, 'Mini Washing Machine', 'Only 3 Washes Used still good as new', 700.00, '', 'Kitwe', 'active', '2026-06-10 17:32:37', '2026-06-10 17:32:37'),
(6, 'mini-washing-machine-3', 2, 3, 'Mini Washing Machine', 'Only done 3 Washes still good as new.', 700.00, '', 'Kitwe', 'active', '2026-06-10 17:57:21', '2026-06-10 17:57:21'),
(7, 'mini-washing-machine-4', 2, 3, 'Mini Washing Machine', 'Only done 3 Washes still good as new.', 700.00, '', 'Kitwe', 'active', '2026-06-10 18:04:28', '2026-06-10 18:04:28'),
(8, 'mini-washing-machine-5', 2, 3, 'Mini Washing Machine', 'Only done 3 Washes still good as new.', 700.00, '', 'Kitwe', 'active', '2026-06-10 18:16:54', '2026-06-10 18:44:55');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `image_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`image_id`, `product_id`, `image_path`, `is_primary`) VALUES
(1, 1, 'samsung-galaxy-s23-ultra-lavender.png', 1),
(6, 5, 'mini-washing-machine-2-1-6a299fb5dcc16.jpg', 1),
(7, 5, 'mini-washing-machine-2-2-6a299fb5decf8.jpg', 0),
(8, 5, 'mini-washing-machine-2-3-6a299fb5e35b7.jpg', 0),
(9, 5, 'mini-washing-machine-2-4-6a299fb5e5f56.jpg', 0),
(10, 6, 'mini-washing-machine-3-1-6a29a58155df9.jpg', 1),
(11, 6, 'mini-washing-machine-3-2-6a29a58156a36.jpg', 0),
(12, 6, 'mini-washing-machine-3-3-6a29a58157a56.jpg', 0),
(13, 6, 'mini-washing-machine-3-4-6a29a581588ae.jpg', 0),
(14, 7, 'mini-washing-machine-4-1-6a29a72cbc04a.jpg', 1),
(15, 7, 'mini-washing-machine-4-2-6a29a72cbd098.jpg', 0),
(16, 7, 'mini-washing-machine-4-3-6a29a72cbdd62.jpg', 0),
(17, 7, 'mini-washing-machine-4-4-6a29a72cc2ff2.jpg', 0),
(18, 8, 'mini-washing-machine-5-1-6a29aa169b233.jpg', 1),
(19, 8, 'mini-washing-machine-5-2-6a29aa169bc73.jpg', 0),
(20, 8, 'mini-washing-machine-5-3-6a29aa169c7cc.jpg', 0),
(21, 8, 'mini-washing-machine-5-4-6a29aa169d287.jpg', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(30) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT 'default.jpg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `full_name`, `phone`, `location`, `profile_image`, `created_at`, `role`) VALUES
(1, 'zulubaron', 'zulubaron6@gmail.com', '$2y$10$.r4wrMx8Dkny9Wfj6gXQiunAC8LGmpOpRGV1e9GTHn/N4Qx0apACS', 'Baron', 'Zulu', 'Baron Zulu', '', 'Kitwe', 'default.jpg', '2026-06-01 15:13:44', NULL),
(2, 'danielmundemba', 'danielmundemba@gmail.com', '$2y$10$Hq2id2i7tA3eKGp13sgv7./AAo2BjfKHTT2.yCFUP8Q989Gz1k1Vm', 'Daniel', 'Mundemba', 'Daniel Mundemba', '+260965777682', 'danielmundemba@gmail.com', 'default.jpg', '2026-06-10 16:39:26', 'admin'),
(3, 'mundembadaniel', 'mundembadaniel@gmail.com', '$2y$10$UkQdVHOV3WWXmSqUf9Pzse42JhZg16K9UDY4EtfB1e6Q9I2GXgnGi', 'Juma', 'Mundemba', 'Juma Mundemba', '+260965777682', 'Kitwe', 'default.jpg', '2026-06-10 23:26:44', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`conversation_id`),
  ADD UNIQUE KEY `unique_chat` (`product_id`,`buyer_id`,`seller_id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `idx_conversation` (`conversation_id`,`created_at`),
  ADD KEY `idx_unread` (`conversation_id`,`is_read`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_seller` (`seller_id`),
  ADD KEY `idx_status` (`status`);
ALTER TABLE `products` ADD FULLTEXT KEY `idx_search` (`title`,`description`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `conversation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversations_ibfk_3` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

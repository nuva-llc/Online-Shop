-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 30 يناير 2026 الساعة 19:31
-- إصدار الخادم: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `weapons_store`
--

-- --------------------------------------------------------

--
-- بنية الجدول `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `name` varchar(200) NOT NULL,
  `manufacturing_country` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `price` decimal(10,2) NOT NULL,
  `image_1` varchar(255) DEFAULT NULL,
  `image_2` varchar(255) DEFAULT NULL,
  `image_3` varchar(255) DEFAULT NULL,
  `image_4` varchar(255) DEFAULT NULL,
  `image_5` varchar(255) DEFAULT NULL,
  `manufacture_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `products`
--

INSERT INTO `products` (`id`, `brand`, `name`, `manufacturing_country`, `quantity`, `price`, `image_1`, `image_2`, `image_3`, `image_4`, `image_5`, `manufacture_date`, `description`, `category`, `created_at`) VALUES
(1, 'Glock', 'Glock 19 Gen5', 'Austria', 50, 550.00, '697100061ec1f_1769013254.jpg', NULL, NULL, NULL, NULL, NULL, 'Compact 9mm professional-grade sidearm. Extreme reliability.', 'Pistols', '2026-01-30 15:17:29'),
(2, 'Kalashnikov', 'AK-47 Classic', 'Russia', 20, 1200.00, '69710014844d3_1769013268.jpg', NULL, NULL, NULL, NULL, NULL, 'The legendary assault rifle. Chambers 7.62x39mm rounds.', 'Rifles', '2026-01-30 15:17:29'),
(3, 'Beretta', 'Beretta 92FS', 'Italy', 30, 650.00, '69710020b1888_1769013280.jpg', NULL, NULL, NULL, NULL, NULL, 'Standard issue tactical pistol. Exceptional precision.', 'Pistols', '2026-01-30 15:17:29'),
(4, 'Remington', 'Remington 870', 'USA', 15, 480.00, '6971002f4d5c6_1769013295.jpg', NULL, NULL, NULL, NULL, NULL, 'The gold standard of pump-action shotguns. Ideal for breaching.', 'Shotguns', '2026-01-30 15:17:29'),
(5, 'Colt', 'Colt M4 Carbine', 'USA', 10, 1650.00, '6971003aa6882_1769013306.jpg', NULL, NULL, NULL, NULL, NULL, 'Highly modular for multi-role tactical insertions.', 'Rifles', '2026-01-30 15:17:29'),
(6, 'Kalashnikov', 'AK-103', 'Russia', 53, 1350.00, '697101921eae7_1769013650.jpg', NULL, NULL, NULL, NULL, NULL, 'Modernized AK with 7.62mm stopping power.', 'Rifles', '2026-01-30 15:17:29'),
(7, 'Makarov', 'Makarov PM', 'Russia', 45, 420.00, '697108e82e62a_1769015528.jpg', NULL, NULL, NULL, NULL, NULL, 'Soviet-era classic sidearm. Rugged and compact.', 'Pistols', '2026-01-30 15:17:29');

-- --------------------------------------------------------

--
-- بنية الجدول `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `gender` enum('male','female','other') DEFAULT 'other',
  `pfp_img` varchar(255) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `user_type` enum('admin','user') DEFAULT 'user',
  `balance` decimal(10,2) DEFAULT 0.00,
  `activation` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `users`
--

INSERT INTO `users` (`id`, `username`, `name`, `email`, `password`, `gender`, `pfp_img`, `birth_date`, `user_type`, `balance`, `activation`, `created_at`) VALUES
(1, 'admin', 'Command Officer', 'admin@gmail.com', '$2y$10$yCQ6xciGZCemoWsveOBzKueYm93yrz1NKeqSS8xyltlCZj6.jE1Oy', 'male', '764b308cdd27d6bed4cbe350f24a9120.png', '0000-00-00', 'admin', 999999.00, 1, '2026-01-30 15:17:29'),
(2, 'user', 'Field Operator', 'user@nuva.com', '$2y$10$qZxQIVljDz8EmgbfOhqxSOuO5DPd9bpTh39InIrMPJib3rb42n9k.', 'male', 'b65592ed9d7306ee258d3c44159bbe9c.png', '0000-00-00', 'user', 5000.00, 1, '2026-01-30 15:17:29');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- قيود الجداول المُلقاة.
--

--
-- قيود الجداول `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

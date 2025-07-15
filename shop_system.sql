-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 15, 2025 at 07:36 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shop_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `image`, `created_at`) VALUES
(1, 'New AdiDoze Shoes', 'Bro check out my new shoes!!!', 788.00, '1752464926_AJitdas.jpg', '2025-07-14 03:48:46'),
(2, 'Shoe Jordin shoes', 'Bro my cousin sell me this Jordan shoes check it out!!!', 1150.00, '1752465031_4aedfddeacad6f33a15ef6a268ca15d5.jpg', '2025-07-14 03:50:31');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` text,
  `role` enum('admin','customer') DEFAULT 'customer',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `address`, `role`, `created_at`) VALUES
(1, 'Admin User', 'admin', '$2y$10$qWgepCg60JQ3Ga8L.uIIauM5gs9H6w5FEFqK/NJwFnLCv0128NT1q', 'Admin Address', 'admin', '2025-07-14 03:44:34'),
(2, 'Jamal Smith', 'Jamal', '$2y$10$W7dJfSEgahC6m.u8DPCYB.W9sqqOSh6s.WUXUYMRl6UTczoHQhEHe', '', 'customer', '2025-07-14 03:51:40'),
(3, 'Kodak Blacker', 'Kodak', '$2y$10$4OgTsvxzuqNvm4vCeoozn.MtGionmuU2NeSXGIYEYtYukUdM14wqu', '', 'customer', '2025-07-14 03:52:18'),
(4, 'Shanaynay', 'Shanaynay', '$2y$10$ra3vqDJzNIz0th113P3o3.mqJeAtpSc5Ey.POh3Yzvn/WY73SFXuS', '', 'customer', '2025-07-14 03:52:58'),
(5, 'Lataytay', 'Lataytay', '$2y$10$bzjsoZbfbLUAsbq98Zj7DuseCdIg32G4t4k/ee0JahTSAClCTynM6', '', 'customer', '2025-07-14 03:53:25'),
(6, 'Lilpeep', 'Lilpeep', '$2y$10$/ZRtJjhbuR/vXsvdAVEO7OcbD2ROykch5OCWJTMoaz2evkj8rIDmm', 'Ghetto 147/2', 'customer', '2025-07-14 03:55:15');

--
-- Indexes for dumped tables
--

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
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

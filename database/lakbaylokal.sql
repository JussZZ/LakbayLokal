-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 14, 2026 at 08:34 AM
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
-- Database: `lakbaylokal`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(60) NOT NULL,
  `details` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `action`, `details`, `created_at`) VALUES
(1, 1, 'user.register', 'New account registered: Justine', '2026-10-10 15:59:53'),
(2, 2, 'admin.login', 'Administrator signed in', '2026-10-10 16:26:54'),
(3, 2, 'destination.delete', 'Pampanga Food Trail', '2026-10-10 16:30:12'),
(4, 2, 'destination.update', 'Siargao Surf and Island Days', '2026-10-10 16:33:26'),
(5, 2, 'user.approve', 'Justine: pending -> approved', '2026-10-10 16:33:46'),
(6, 2, 'destination.update', 'Mount Pulag Summit Trek', '2026-10-10 16:34:42'),
(7, 2, 'destination.update', 'Vigan Heritage Village', '2026-10-10 16:35:26'),
(8, 2, 'destination.update', 'El Nido Island Hopping', '2026-10-10 16:36:56'),
(9, 2, 'destination.update', 'Banaue Rice Terraces', '2026-10-10 16:37:28'),
(10, 2, 'admin.login', 'Administrator signed in', '2026-10-10 16:47:54'),
(11, 2, 'destination.update', 'Siargao Surf and Island Days', '2026-10-10 16:48:27'),
(12, 2, 'destination.update', 'Mount Pulag Summit Trek', '2026-10-10 16:48:34'),
(13, 2, 'destination.update', 'Vigan Heritage Village', '2026-10-10 16:48:41'),
(14, 2, 'destination.update', 'El Nido Island Hopping', '2026-10-10 16:48:49'),
(15, 2, 'destination.update', 'Banaue Rice Terraces', '2026-10-10 16:48:56'),
(16, 2, 'admin.login', 'Administrator signed in', '2026-10-10 16:59:42'),
(17, 2, 'admin.login', 'Administrator signed in', '2026-10-10 17:03:14'),
(18, 2, 'destination.delete', 'Banaue Rice Terraces', '2026-10-10 17:14:32'),
(19, 2, 'admin.login', 'Administrator signed in', '2026-10-14 12:12:48'),
(20, 2, 'destination.delete', 'Mount Pulag Summit Trek', '2026-10-14 12:12:55'),
(21, 2, 'admin.login', 'Administrator signed in', '2026-10-14 13:06:51'),
(22, 1, 'booking.create', 'LL-261014-2EK6GH for El Nido Island Hopping', '2026-10-14 13:08:52'),
(23, 2, 'booking.confirmed', 'LL-261014-2EK6GH: pending -> confirmed', '2026-10-14 13:10:13'),
(24, 2, 'profile.update', 'Profile details updated', '2026-10-14 13:11:39'),
(25, 2, 'profile.update', 'Profile details updated', '2026-10-14 13:11:42'),
(26, 2, 'booking.cancelled', 'LL-261014-2EK6GH: confirmed -> cancelled', '2026-10-14 13:12:49'),
(27, 1, 'booking.create', 'LL-261014-RRCBXY for Siargao Surf and Island Days', '2026-10-14 13:18:33'),
(28, 2, 'booking.confirmed', 'LL-261014-RRCBXY: pending -> confirmed', '2026-10-14 13:19:04'),
(29, 2, 'admin.login', 'Administrator signed in', '2026-10-14 13:22:41'),
(30, 2, 'destination.create', 'Boracay Beach Paradise', '2026-10-14 13:44:09'),
(31, 2, 'destination.update', 'Boracay Beach Paradise', '2026-10-14 13:46:25'),
(32, 2, 'destination.update', 'El Nido Island Hopping', '2026-10-14 13:48:52'),
(33, 2, 'destination.create', 'Bohol Countryside Adventure', '2026-10-14 13:51:06'),
(34, 2, 'destination.update', 'Bohol Countryside Adventure', '2026-10-14 13:51:45'),
(35, 2, 'destination.create', 'Cebu Waterfall Adventure', '2026-10-14 13:54:09'),
(36, 2, 'destination.create', 'Banaue Rice Terraces Journey', '2026-10-14 13:56:29'),
(37, 2, 'destination.create', 'Baguio Mountain Retreat', '2026-10-14 13:59:04'),
(38, 1, 'booking.create', 'LL-261014-43SX7S for Baguio Mountain Retreat', '2026-10-14 14:02:47'),
(39, 1, 'booking.cancel', 'LL-261014-43SX7S cancelled by traveller', '2026-10-14 14:04:16'),
(40, 2, 'destination.create', 'Intramuros Historical Tour', '2026-10-14 14:07:24'),
(41, 2, 'destination.update', 'Baguio Mountain Retreat', '2026-10-14 14:09:22'),
(42, 2, 'destination.update', 'Baguio Mountain Retreat', '2026-10-14 14:10:06'),
(43, 2, 'destination.create', 'Sagada Highlands Escape', '2026-10-14 14:14:07'),
(44, 2, 'destination.update', 'Intramuros Historical Tour', '2026-10-14 14:15:12'),
(45, 2, 'destination.create', 'Mayon Volcano Scenic Tour', '2026-10-14 14:17:54'),
(46, 2, 'destination.create', 'Siquijor Island Getaway', '2026-10-14 14:19:17'),
(47, 2, 'destination.create', 'Iloilo Food and Heritage Trail', '2026-10-14 14:21:44'),
(48, 2, 'destination.create', 'Tagaytay Scenic Escape', '2026-10-14 14:23:58'),
(49, 2, 'destination.update', 'El Nido Island Hopping', '2026-10-14 14:24:46'),
(50, 2, 'destination.update', 'Siargao Surf and Island Days', '2026-10-14 14:25:02'),
(51, 2, 'destination.update', 'Vigan Heritage Village', '2026-10-14 14:25:19'),
(52, 2, 'destination.update', 'Bohol Countryside Adventure', '2026-10-14 14:27:34'),
(53, 2, 'destination.update', 'El Nido Island Hopping', '2026-10-14 14:28:32');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(10) UNSIGNED NOT NULL,
  `reference` varchar(24) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `travel_date` date NOT NULL,
  `travelers` int(10) UNSIGNED NOT NULL,
  `contact_name` varchar(120) NOT NULL,
  `contact_email` varchar(150) NOT NULL,
  `contact_phone` varchar(30) DEFAULT NULL,
  `special_requests` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `reference`, `user_id`, `item_id`, `travel_date`, `travelers`, `contact_name`, `contact_email`, `contact_phone`, `special_requests`, `status`, `created_at`, `updated_at`) VALUES
(1, 'LL-261014-2EK6GH', 1, 2, '2026-10-15', 2, 'Justine Salinas', 'justinesalinas1310@gmail.com', '09515947041', NULL, 'cancelled', '2026-10-14 13:08:52', '2026-10-14 13:12:49'),
(2, 'LL-261014-RRCBXY', 1, 5, '2026-10-15', 2, 'Justine Salinas', 'justinesalinas1310@gmail.com', '09515947041', NULL, 'confirmed', '2026-10-14 13:18:33', '2026-10-14 13:19:04'),
(3, 'LL-261014-43SX7S', 1, 11, '2026-10-15', 3, 'Justine Salinas', 'justinesalinas1310@gmail.com', '09515947041', NULL, 'cancelled', '2026-10-14 14:02:47', '2026-10-14 14:04:16');

-- --------------------------------------------------------

--
-- Table structure for table `booking_status_history`
--

CREATE TABLE `booking_status_history` (
  `history_id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `old_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) NOT NULL,
  `changed_by` int(10) UNSIGNED DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `changed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `item_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` text NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'bookable slots remaining',
  `status` varchar(20) NOT NULL DEFAULT 'draft' COMMENT 'published | draft',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `location` varchar(150) NOT NULL,
  `category` varchar(30) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `highlights` text DEFAULT NULL COMMENT 'one highlight / activity per line',
  `best_season` varchar(100) DEFAULT NULL,
  `price_per_person` decimal(10,2) NOT NULL DEFAULT 0.00,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`item_id`, `name`, `description`, `quantity`, `status`, `created_at`, `location`, `category`, `image_path`, `highlights`, `best_season`, `price_per_person`, `updated_at`) VALUES
(2, 'El Nido Island Hopping', 'Discover crystal-clear lagoons, limestone cliffs, and beautiful hidden beaches perfect for a tropical getaway.', 38, 'published', '2026-10-10 15:47:18', 'El Nido, Palawan', 'beach', 'assets/images/destinations/uploads/7d196a1cdbe48f6b5a3ff25b.jpg', 'Island Hopping\r\nKayaking\r\nSnorkeling\r\nLagoon Swimming\r\nPhotography', 'December–April', 3200.00, '2026-10-14 14:28:32'),
(3, 'Vigan Heritage Village', 'A preserved colonial-era town of cobblestone streets and ancestral houses. Explore on foot or by horse-drawn carriage and taste local Ilocano specialties.', 50, 'published', '2026-10-10 15:47:18', 'Ilocos Sur, Ilocos Region', 'heritage', 'assets/images/destinations/uploads/8876194edab8427d1e54577c.jpg', 'Calle Crisologo walk\r\nKalesa ride\r\nPottery and weaving workshops', 'Cooler months, November to February', 2299.98, '2026-10-14 14:25:19'),
(5, 'Siargao Surf and Island Days', 'Surf lessons for beginners, island tours and slow beach days. Rent a scooter, chase waves and explore the nearby islets.', 23, 'published', '2026-10-10 15:47:18', 'Surigao del Norte, Caraga', 'beach', 'assets/images/destinations/uploads/dab26a68c7efe3b1d32d133d.jpg', 'Surf lessons\r\nIsland tour\r\nTidal pool swimming', 'Surf season, August to November', 3100.00, '2026-10-14 14:25:02'),
(7, 'Boracay Beach Paradise', 'Enjoy powdery white sand, clear blue water, and unforgettable island sunsets.', 54, 'published', '2026-10-14 13:44:09', 'Boracay Island, Malay, Aklan', 'beach', 'assets/images/destinations/uploads/74d09b2f87e82cef8d3534b1.jpg', 'Swimming\r\nSunset sailing\r\nParasailing\r\nBeach relaxation\r\nNightlife', 'November–April', 2599.00, '2026-10-14 13:46:25'),
(8, 'Bohol Countryside Adventure', 'Explore Bohol\'s famous Chocolate Hills, lush countryside, and unique wildlife.', 27, 'published', '2026-10-14 13:51:06', 'Carmen and Loboc, Bohol', 'nature', 'assets/images/destinations/uploads/a26041566ea852c48817c80b.jpg', 'Chocolate Hills viewing\r\nCarsier sanctuary visit\r\nLoboc River cruise,\r\nCountryside sightseeing', 'December–May', 2200.00, '2026-10-14 14:27:34'),
(9, 'Cebu Waterfall Adventure', 'Experience Cebu\'s refreshing turquoise waterfalls surrounded by lush tropical forests.', 56, 'published', '2026-10-14 13:54:09', 'Badian, Cebu', 'nature', 'assets/images/destinations/uploads/1e5e0e37605107f405cf8f70.jpg', 'Waterfall Swimming\r\nCanyoneering\r\nTrekking\r\nNature Photography', 'December–May', 2800.00, '2026-10-14 13:54:09'),
(10, 'Banaue Rice Terraces Journey', 'Discover breathtaking mountain terraces shaped by generations of Ifugao farmers and local traditions.', 36, 'published', '2026-10-14 13:56:29', 'Banaue, Ifugao', 'nature', 'assets/images/destinations/uploads/0c33b0fb2573bae7004d8744.jpg', 'Terrace Sightseeing\r\nGuided Village Walks\r\nCultural Encounters\r\nLandscape Photography', 'March–May for generally drier hiking conditions', 1800.00, '2026-10-14 13:56:29'),
(11, 'Baguio Mountain Retreat', 'Escape to a cool mountain city known for pine trees, scenic parks, local markets, and cozy cafés.', 39, 'published', '2026-10-14 13:59:04', 'Baguio City, Benguet', 'food', 'assets/images/destinations/uploads/580d24b10a8f78411f6c9dd0.jpg', 'Burnham Park Boating\r\nMines View Park\r\nBotanical Garden\r\nStrawberry Sreats\r\nCafé Hopping', 'December–May', 1500.00, '2026-10-14 14:10:06'),
(12, 'Intramuros Historical Tour', 'Discover the country\'s history inside the Walled City, with historic churches, stone fortifications, and colonial-era streets.', 153, 'published', '2026-10-14 14:07:24', 'Manila, Metro Manila', 'heritage', 'assets/images/destinations/uploads/3f0d8221fe49288f050a737c.jpg', 'Fort Santiago\r\nSan Agustin Church\r\nHeritage Walking Tour\r\nMuseum Visits', 'November–February for relatively cooler weather', 800.00, '2026-10-14 14:15:12'),
(13, 'Sagada Highlands Escape', 'Experience misty mountain views, peaceful villages, caves, and the distinct cultural heritage of the Cordillera.', 35, 'published', '2026-10-14 14:14:07', 'Sagada, Mountain Province', 'adventure', 'assets/images/destinations/uploads/8434ff37062bc5605527901a.jpg', 'Cave Exploration\r\nMountain Hikes\r\nSunrise Viewpoints\r\nGuided Cultural Visits', 'November–May', 1800.00, '2026-10-14 14:14:07'),
(14, 'Mayon Volcano Scenic Tour', 'Witness the iconic cone-shaped Mayon Volcano and enjoy scenic countryside views around Bicol.', 89, 'published', '2026-10-14 14:17:54', 'Legazpi City, Albay', 'nature', 'assets/images/destinations/uploads/6077c6d7c7a97ee089b91a65.jpg', 'Cagsawa Ruins,\r\nVolcano Viewpoints \r\nLocal Food Tasting \r\nCountryside Photography', 'February–May, weather permitting', 1500.00, '2026-10-14 14:17:54'),
(15, 'Siquijor Island Getaway', 'Explore a peaceful island filled with waterfalls, coastal scenery, old churches, and relaxing swimming spots.', 45, 'published', '2026-10-14 14:19:17', 'Siquijor Island, Central Visayas', 'beach', 'assets/images/destinations/uploads/819c104daf1e27d7ca0437a2.jpg', 'Cambugahay Falls\r\nSalagdoong Beach\r\nHeritage Church Visits\r\nCoastal Sightseeing', 'December–May', 1700.00, '2026-10-14 14:19:17'),
(16, 'Iloilo Food and Heritage Trail', 'Discover a city celebrated for its local cuisine, historic architecture, and welcoming atmosphere.', 85, 'published', '2026-10-14 14:21:44', 'Iloilo City, Iloilo', 'food', 'assets/images/destinations/uploads/36f939b83ec558afc9d56896.jpg', 'La Paz Batchoy Tasting \r\nHeritage District Walks \r\nOld Churches\r\nLocal Markets\r\nTraditional Sweets', 'November–May', 1300.00, '2026-10-14 14:21:44'),
(17, 'Tagaytay Scenic Escape', 'Enjoy refreshing highland air, panoramic Taal Lake views, and relaxing cafés just a few hours from Metro Manila.', 56, 'published', '2026-10-14 14:23:58', 'Tagaytay City, Cavite', 'food', 'assets/images/destinations/uploads/80d495b768eaaf821f1291fa.jpg', 'Taal Viewpoint Stops\r\nPeople\'s Park in the Sky\r\nPicnic Spots\r\nCafé Hopping', 'November–February for cooler weather', 1200.00, '2026-10-14 14:23:58');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `transaction_date` datetime NOT NULL DEFAULT current_timestamp(),
  `booking_id` int(10) UNSIGNED DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `currency` char(3) NOT NULL DEFAULT 'PHP',
  `status` varchar(20) NOT NULL DEFAULT 'recorded' COMMENT 'recorded | voided (no payment gateway is connected)',
  `note` varchar(255) DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'password_hash() output, never plaintext',
  `role` varchar(20) NOT NULL DEFAULT 'user',
  `full_name` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`, `full_name`, `email`, `phone`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Justine', '$2y$10$wKT6hssapYVhPfcOpM5aqOtVEuitPTzDf0JUMzvG5wnE8pYp3Okfm', 'user', 'Justine Salinas', 'justinesalinas1310@gmail.com', '09515947041', 'approved', '2026-10-10 15:59:53', '2026-10-10 16:33:46'),
(2, 'admin', '$2y$10$xfcSY0hPoqalliJSHCEqL.kGFtA3pY1yXcLirwym5UNk6rKHMQkPG', 'admin', 'Admin', 'lladmin@gmail.com', '09451846415', 'approved', '2026-10-10 16:26:38', '2026-10-14 13:11:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_logs_created` (`created_at`),
  ADD KEY `fk_logs_user` (`user_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD UNIQUE KEY `uq_bookings_reference` (`reference`),
  ADD KEY `idx_bookings_user` (`user_id`,`status`),
  ADD KEY `idx_bookings_item` (`item_id`),
  ADD KEY `idx_bookings_status` (`status`),
  ADD KEY `idx_bookings_travel_date` (`travel_date`);

--
-- Indexes for table `booking_status_history`
--
ALTER TABLE `booking_status_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `idx_history_booking` (`booking_id`),
  ADD KEY `fk_history_user` (`changed_by`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `idx_items_status_cat` (`status`,`category`),
  ADD KEY `idx_items_name` (`name`),
  ADD KEY `idx_items_location` (`location`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD UNIQUE KEY `uq_transactions_booking` (`booking_id`),
  ADD KEY `idx_transactions_item` (`item_id`),
  ADD KEY `idx_transactions_user` (`user_id`),
  ADD KEY `idx_transactions_date` (`transaction_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booking_status_history`
--
ALTER TABLE `booking_status_history`
  MODIFY `history_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `item_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_bookings_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `booking_status_history`
--
ALTER TABLE `booking_status_history`
  ADD CONSTRAINT `fk_history_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_history_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_transactions_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transactions_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

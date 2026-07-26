-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 21, 2026 at 08:36 PM
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
-- Database: `laundry_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `pin` varchar(10) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `google_id` varchar(100) DEFAULT NULL,
  `picture` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`id`, `full_name`, `phone`, `created_at`, `pin`, `email`, `address`, `google_id`, `picture`) VALUES
(5, 'James Macharia', '0716738697', '2026-07-05 19:28:42', '1234', '', NULL, NULL, NULL),
(6, 'Boyd Kariuki', '0722337846', '2026-07-07 11:44:25', '3579', NULL, NULL, NULL, NULL),
(7, 'Josh', '0788769337', '2026-07-07 13:24:24', '2468', NULL, NULL, NULL, NULL),
(8, 'Natasha Wanjiku', '0711298921', '2026-07-07 22:43:46', '1111', NULL, NULL, NULL, NULL),
(9, 'Kenny West', '0706774724', '2026-07-08 16:49:08', '2222', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `phone_number` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `customer_name`, `phone_number`) VALUES
(1, 'josh', '254729202020');

-- --------------------------------------------------------

--
-- Table structure for table `delivery`
--

CREATE TABLE `delivery` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text NOT NULL,
  `delivery_status` enum('pending','out_for_delivery','delivered','cancelled') DEFAULT 'pending',
  `delivery_date` date DEFAULT NULL,
  `delivered_by` varchar(100) DEFAULT NULL,
  `delivery_notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery`
--

INSERT INTO `delivery` (`id`, `order_id`, `customer_id`, `customer_name`, `phone`, `address`, `delivery_status`, `delivery_date`, `delivered_by`, `delivery_notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'John Doe', '0712345678', '123 Kimathi Street, Nairobi', 'out_for_delivery', '2026-07-08', NULL, 'Call before delivery', NULL, '2026-07-07 09:24:55', '2026-07-07 09:25:18'),
(2, 6, NULL, 'Boyd Kariuki', '0722337846', 'cherry valley estate', 'out_for_delivery', '2026-07-09', NULL, '', 2, '2026-07-08 14:00:56', '2026-07-08 14:03:18');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_proof`
--

CREATE TABLE `delivery_proof` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `rider_id` int(11) NOT NULL,
  `proof_type` enum('signature','photo','note') DEFAULT 'note',
  `proof_data` text DEFAULT NULL,
  `delivered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `received_by` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_proof`
--

INSERT INTO `delivery_proof` (`id`, `order_id`, `rider_id`, `proof_type`, `proof_data`, `delivered_at`, `received_by`) VALUES
(1, 9, 7, 'note', 'n/b', '2026-07-13 07:32:55', 'Natasha wanjiku');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `position` varchar(50) DEFAULT 'staff',
  `salary` decimal(10,2) DEFAULT 0.00,
  `hire_date` date DEFAULT NULL,
  `status` enum('active','on_leave','terminated') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `expense_date` date NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT 'supplies',
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(20) DEFAULT 'pcs',
  `min_stock` int(11) DEFAULT 10,
  `cost_per_unit` decimal(10,2) DEFAULT 0.00,
  `supplier` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `max_stock` int(11) DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mpesa_settings`
--

CREATE TABLE `mpesa_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mpesa_settings`
--

INSERT INTO `mpesa_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'mpesa_consumer_key', '', '2026-07-11 07:48:20'),
(2, 'mpesa_consumer_secret', '', '2026-07-11 07:48:20'),
(3, 'mpesa_shortcode', '', '2026-07-11 07:48:20'),
(4, 'mpesa_passkey', '', '2026-07-11 07:48:20'),
(5, 'mpesa_callback_url', '', '2026-07-11 07:48:20'),
(6, 'mpesa_environment', 'sandbox', '2026-07-11 07:48:20');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('order','system','alert') DEFAULT 'system',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 1, 'TAX FILLING', 'Hi everyone, your reminded that the deadline to fill your return is due.', '', 1, '2026-07-09 12:32:07'),
(2, 2, 'TAX FILLING', 'Hi everyone, your reminded that the deadline to fill your return is due.', '', 0, '2026-07-09 12:32:07'),
(3, 3, 'TAX FILLING', 'Hi everyone, your reminded that the deadline to fill your return is due.', '', 1, '2026-07-09 12:32:07'),
(4, 5, 'TAX FILLING', 'Hi everyone, your reminded that the deadline to fill your return is due.', '', 0, '2026-07-09 12:32:07'),
(5, 1, 'Holiday', 'To this coming monday you are advised to stay at home since its Madarak day, have a happy hoilday thankyou!', '', 0, '2026-07-11 08:06:37'),
(7, 1, 'HOLIDAY', 'To this coming monday you are advised to stay at home since its Madarak day, have a happy hoilday thankyou', '', 0, '2026-07-11 13:26:25'),
(8, 2, 'HOLIDAY', 'To this coming monday you are advised to stay at home since its Madarak day, have a happy hoilday thankyou', '', 0, '2026-07-11 13:26:25'),
(9, 1, 'confirmation', 'hi', 'alert', 0, '2026-07-12 06:12:37'),
(11, 1, 'HOLIDAY', 'To this coming monday you are advised to stay at home since its Madarak day, have a happy hoilday thankyou', '', 0, '2026-07-14 08:59:59'),
(13, 6, 'HOLIDAY', 'To this coming monday you are advised to stay at home since its Madarak day, have a happy hoilday thankyou', '', 0, '2026-07-14 08:59:59'),
(14, 1, 'HOLIDAY', 'Madaraka day', '', 0, '2026-07-14 09:11:38'),
(16, 6, 'HOLIDAY', 'Madaraka day', '', 0, '2026-07-14 09:11:38'),
(17, 1, 'HOLIDAY', 'Madaraka day', '', 0, '2026-07-14 09:14:26'),
(19, 6, 'HOLIDAY', 'Madaraka day', '', 0, '2026-07-14 09:14:26'),
(20, 1, 'PROJECT', 'Your project deadline for presenting is nearing', '', 0, '2026-07-14 09:23:02'),
(21, 2, 'PROJECT', 'Your project deadline for presenting is nearing', '', 0, '2026-07-14 09:23:02'),
(22, 6, 'PROJECT', 'Your project deadline for presenting is nearing', '', 0, '2026-07-14 09:23:02'),
(23, 1, 'HI', 'goodmorning', '', 0, '2026-07-14 09:30:06'),
(24, 2, 'HI', 'goodmorning', '', 0, '2026-07-14 09:30:06'),
(25, 6, 'HI', 'goodmorning', '', 0, '2026-07-14 09:30:06');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `status` enum('received','washing','ready','collected') NOT NULL DEFAULT 'received',
  `notes` text DEFAULT NULL,
  `paid` tinyint(1) NOT NULL DEFAULT 0,
  `payment_method` varchar(50) DEFAULT NULL,
  `mpesa_code` varchar(20) DEFAULT NULL,
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `collected_at` timestamp NULL DEFAULT NULL,
  `delivery_mode` varchar(20) DEFAULT 'pickup',
  `delivery_address` text DEFAULT NULL,
  `delivery_status` varchar(30) DEFAULT 'pending',
  `assigned_rider` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `name`, `qty`, `price`) VALUES
(6, '3', 'Duvets', 1, 120.00),
(7, '3', 'Bedsheets', 1, 77.00),
(8, '4', 'Ironing only', 1, 20.00),
(9, '4', 'Trousers', 1, 37.50),
(17, '5', 'Shirts', 1, 30.00),
(18, '5', 'Ironing only', 1, 20.00),
(19, '5', 'Towels', 1, 50.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_sequence`
--

CREATE TABLE `order_sequence` (
  `next_number` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','mpesa','card','bank') DEFAULT 'cash',
  `payment_status` enum('paid','pending','refunded') DEFAULT 'paid',
  `transaction_ref` varchar(100) DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `unit` varchar(30) NOT NULL DEFAULT 'per item',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `unit`, `price`, `created_at`) VALUES
(1, 'Shirts', 'per item', 30.00, '2026-07-02 15:24:39'),
(2, 'Trousers', 'per item', 37.50, '2026-07-02 15:24:39'),
(3, 'Bedsheets', 'per kg', 75.00, '2026-07-02 15:24:39'),
(4, 'Towels', 'per item', 50.00, '2026-07-02 15:24:39'),
(5, 'Jackets', 'per item', 90.00, '2026-07-02 15:24:39'),
(6, 'Duvets', 'per item', 120.00, '2026-07-02 15:24:39'),
(7, 'Mixed load', 'per kg', 90.00, '2026-07-02 15:24:39'),
(8, 'Ironing only', 'per item', 20.00, '2026-07-02 15:24:39'),
(9, 'Suits', 'per item', 100.00, '2026-07-10 22:10:36');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT 'general',
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_group`, `description`) VALUES
(1, 'business_name', 'Muthoni\'s Laundry', 'general', 'Business name displayed on the system'),
(2, 'business_phone', '0735627892', 'general', 'Business contact phone'),
(3, 'business_address', 'Nairobi, Kenya', 'general', 'Business physical address'),
(4, 'currency', 'Ksh', 'general', 'Currency symbol'),
(5, 'tax_rate', '0', 'general', 'Tax rate percentage'),
(6, 'receipt_header', 'Thank you for choosing Muthoni\'s Laundry!', 'receipt', 'Header text on receipts'),
(7, 'receipt_footer', 'Items not collected within 30 days will be donated.', 'receipt', 'Footer text on receipts'),
(8, 'items_per_page', '10', 'system', 'Number of items per page in tables'),
(9, 'theme_color', '#667eea', 'appearance', 'Primary theme color'),
(10, 'enable_barcode', '1', 'system', 'Enable barcode scanning feature'),
(11, 'auto_sms', '0', 'system', 'Send automatic SMS notifications'),
(12, 'business_email', 'info@muthonis-laundry.co.ke', 'business', 'Business email address'),
(14, 'monthly_revenue_target', '0', 'financial', 'Monthly revenue target set by the owner');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('staff','manager','admin','rider') NOT NULL DEFAULT 'staff',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `google_id` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `picture` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `username`, `password`, `role`, `status`, `created_at`, `google_id`, `email`, `picture`) VALUES
(1, 'Muthoni Wanjiku', 'manager', 'manager123', 'manager', 'active', '2026-07-03 10:25:07', NULL, NULL, NULL),
(2, 'John Doe', 'admin', 'admin123', 'admin', 'active', '2026-07-03 10:25:07', NULL, NULL, NULL),
(3, 'Jane Mwangi', 'staff', 'staff123', 'staff', 'inactive', '2026-07-03 10:25:07', NULL, NULL, NULL),
(5, 'Nicholus Kariuki', 'nicho123', 'nich123', 'manager', 'inactive', '2026-07-09 11:17:58', NULL, NULL, NULL),
(6, 'Kanzie', 'Bell', 'ken123', 'manager', 'active', '2026-07-12 10:00:47', NULL, NULL, NULL),
(7, 'Ray Racka', 'rider1', 'rider123', 'rider', 'inactive', '2026-07-12 14:49:36', NULL, NULL, NULL),
(8, 'Raymond Vik', 'Ray', 'Ray123', '', 'inactive', '2026-07-12 15:01:23', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pref_key` varchar(100) NOT NULL,
  `pref_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `google_id` (`google_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `delivery`
--
ALTER TABLE `delivery`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `delivery_proof`
--
ALTER TABLE `delivery_proof`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mpesa_settings`
--
ALTER TABLE `mpesa_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_read` (`is_read`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `google_id` (`google_id`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_pref` (`user_id`,`pref_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `delivery`
--
ALTER TABLE `delivery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `delivery_proof`
--
ALTER TABLE `delivery_proof`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `mpesa_settings`
--
ALTER TABLE `mpesa_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

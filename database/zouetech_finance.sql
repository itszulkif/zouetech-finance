-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 06, 2026 at 03:46 PM
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
-- Database: `zouetech_finance`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password_hash`, `created_at`) VALUES
(1, 'zulkif@gmail.com', '$2y$10$tuzpWLtEmx05EdBbZPDcd.NaWOShY.WNZQltYzfuGg8wmf/SHuWKK', '2026-05-05 13:16:46');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `type` enum('company','partner') NOT NULL,
  `partner_id` int(10) UNSIGNED DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `income`
--

CREATE TABLE `income` (
  `id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `type` enum('distributed','company_only','external_source') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `income`
--

INSERT INTO `income` (`id`, `amount`, `type`, `created_at`) VALUES
(23, 0.12, 'distributed', '2026-05-05 19:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `ledger`
--

CREATE TABLE `ledger` (
  `id` int(10) UNSIGNED NOT NULL,
  `partner_id` int(10) UNSIGNED NOT NULL,
  `credit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `debit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ledger`
--

INSERT INTO `ledger` (`id`, `partner_id`, `credit`, `debit`, `balance`, `created_at`) VALUES
(40, 22, 0.03, 0.00, 0.03, '2026-05-05 19:00:00'),
(41, 21, 0.03, 0.00, 0.03, '2026-05-05 19:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `partners`
--

CREATE TABLE `partners` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `partners`
--

INSERT INTO `partners` (`id`, `name`, `percentage`, `created_at`) VALUES
(21, 'Hamza', 50.00, '2026-05-06 11:49:21'),
(22, 'Haris', 50.00, '2026-05-06 11:49:26');

-- --------------------------------------------------------

--
-- Table structure for table `partner_shares`
--

CREATE TABLE `partner_shares` (
  `id` int(10) UNSIGNED NOT NULL,
  `partner_id` int(10) UNSIGNED NOT NULL,
  `income_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `partner_shares`
--

INSERT INTO `partner_shares` (`id`, `partner_id`, `income_id`, `amount`, `created_at`) VALUES
(35, 22, 23, 0.03, '2026-05-06 13:23:18'),
(36, 21, 23, 0.03, '2026-05-06 13:23:18');

-- --------------------------------------------------------

--
-- Table structure for table `partner_users`
--

CREATE TABLE `partner_users` (
  `id` int(10) UNSIGNED NOT NULL,
  `partner_id` int(10) UNSIGNED NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_expenses_type_created` (`type`,`created_at`),
  ADD KEY `idx_expenses_partner` (`partner_id`);

--
-- Indexes for table `income`
--
ALTER TABLE `income`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_income_type_created` (`type`,`created_at`);

--
-- Indexes for table `ledger`
--
ALTER TABLE `ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ledger_partner_created` (`partner_id`,`created_at`);

--
-- Indexes for table `partners`
--
ALTER TABLE `partners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `partner_shares`
--
ALTER TABLE `partner_shares`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_partner_shares_partner` (`partner_id`),
  ADD KEY `idx_partner_shares_income` (`income_id`);

--
-- Indexes for table `partner_users`
--
ALTER TABLE `partner_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `partner_id` (`partner_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `income`
--
ALTER TABLE `income`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `ledger`
--
ALTER TABLE `ledger`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `partners`
--
ALTER TABLE `partners`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `partner_shares`
--
ALTER TABLE `partner_shares`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `partner_users`
--
ALTER TABLE `partner_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expenses_partner` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ledger`
--
ALTER TABLE `ledger`
  ADD CONSTRAINT `fk_ledger_partner` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `partner_shares`
--
ALTER TABLE `partner_shares`
  ADD CONSTRAINT `fk_partner_shares_income` FOREIGN KEY (`income_id`) REFERENCES `income` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_partner_shares_partner` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `partner_users`
--
ALTER TABLE `partner_users`
  ADD CONSTRAINT `fk_partner_users_partner` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

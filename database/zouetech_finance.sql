-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 07, 2026 at 03:51 PM
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
  `name` varchar(150) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `picture_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `name`, `password_hash`, `picture_path`, `created_at`) VALUES
(1, 'zulkif@gmail.com', 'zulkif', '$2y$10$gw6zOjzeKcGh.NJgTmdg6eBytJJ1wjPYhvjTe61aFwtH/CtJbLqe2', 'public/uploads/profiles/admin_1_1778161039.png', '2026-05-05 13:16:46');

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

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `amount`, `type`, `partner_id`, `description`, `created_at`) VALUES
(42, 1000.00, 'company', NULL, 'tea', '2026-05-05 19:00:00'),
(43, 20000.00, 'partner', 21, 'charis', '2026-05-06 19:00:00'),
(44, 10000.00, 'partner', 21, 'REIMBURSEMENT: company pay', '2026-05-07 19:00:00'),
(45, 10000.00, 'partner', 22, '', '2026-05-06 19:00:00'),
(46, 3000.00, 'partner', 22, 'amount pay', '2026-05-06 19:00:00'),
(47, 10000.00, 'partner', 22, '', '2026-05-06 19:00:00'),
(48, 10000.00, 'partner', 21, 'REIMBURSEMENT: Partner reimbursement paid by company', '2026-05-06 19:00:00'),
(49, 31000.00, 'company', NULL, '', '2026-05-06 19:00:00'),
(50, 1000.00, 'company', NULL, '', '2026-05-06 19:00:00'),
(51, 199.99, 'partner', 21, 'tea', '2026-05-06 19:00:00'),
(52, 300.00, 'partner', 21, 'tea tea', '2026-05-07 19:00:00'),
(53, 200.00, 'partner', 21, 'tea pepsi', '2026-05-07 19:00:00'),
(54, 1000.00, 'company', NULL, 'tea', '2026-05-06 19:00:00');

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
(23, 0.12, 'distributed', '2026-05-05 19:00:00'),
(24, 100000.00, 'distributed', '2026-05-05 19:00:00'),
(25, 2000.00, 'company_only', '2026-05-06 19:00:00'),
(26, 2000.00, 'company_only', '2026-05-06 19:00:00');

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
(41, 21, 0.03, 0.00, 0.03, '2026-05-05 19:00:00'),
(42, 22, 25000.00, 0.00, 25000.03, '2026-05-05 19:00:00'),
(43, 21, 25000.00, 0.00, 25000.03, '2026-05-05 19:00:00');

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
(36, 21, 23, 0.03, '2026-05-06 13:23:18'),
(37, 22, 24, 25000.00, '2026-05-06 15:45:33'),
(38, 21, 24, 25000.00, '2026-05-06 15:45:33');

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `income`
--
ALTER TABLE `income`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `ledger`
--
ALTER TABLE `ledger`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `partners`
--
ALTER TABLE `partners`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `partner_shares`
--
ALTER TABLE `partner_shares`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

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

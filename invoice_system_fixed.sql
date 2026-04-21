-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 27, 2026 at 04:39 PM
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
-- Database: `invoice_system`
--
CREATE DATABASE IF NOT EXISTS `invoice_system`;
USE `invoice_system`;

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_next_quotation_number` ()   BEGIN
  DECLARE next_num INT;
  SELECT COALESCE(MAX(CAST(SUBSTRING(invoice_number,4) AS UNSIGNED)),0)+1
  INTO next_num
  FROM invoices
  WHERE document_type = 'quotation' AND invoice_number LIKE 'QT-%';
  SELECT CONCAT('QT-',LPAD(next_num,4,'0')) AS next_quotation_number;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `gst_number` varchar(15) DEFAULT NULL,
  `client_gst_number` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `state_code` varchar(2) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `pincode` varchar(10) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `company_name`, `contact_person`, `email`, `phone`, `gst_number`, `client_gst_number`, `address`, `state`, `state_code`, `country`, `pincode`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Tech Solutions Inc.', 'John Doe', 'john@techsolutions.com', '+1-555-0123', '29ABCDE1234F1Z5', NULL, '123 Tech Street, Silicon Valley, CA 94025', NULL, NULL, 'India', NULL, NULL, '2025-09-20 05:42:05', '2025-09-20 05:42:05'),
(2, 'Global Traders Ltd.', 'Jane Smith', 'jane@globaltraders.com', '+1-555-0124', '27FGHIJ5678K2Y6', NULL, '456 Trade Avenue, New York, NY 10001', NULL, NULL, 'India', NULL, NULL, '2025-09-20 05:42:05', '2025-09-20 05:42:05'),
(3, 'Creative Designs Co.', 'Mike Scarlet', 'mike@creativedesigns.com', '+1-555-0125', '24AUUPM4970B1ZF', NULL, '789 Design Road, Los Angeles, CA 90001', NULL, NULL, 'India', NULL, '', '2025-09-20 05:42:05', '2025-09-20 05:44:08'),
(4, 'aqkjfkqlasdwdw', 'ljaljads', 'AHSR@gmail.com', '12512940123', '27ABCDE1234F1Z5', NULL, '', NULL, NULL, 'India', NULL, '', '2025-10-05 03:59:01', '2026-01-15 11:06:51'),
(5, 'honda', 'yash', 'jrhiwef@gmail.com', '1234567989', '27ABCDE1234F2Z5', NULL, '', NULL, NULL, 'India', NULL, '', '2025-10-05 22:55:37', '2025-10-05 22:55:37'),
(6, 'Kiran', 'Harsh Suthar', 'harshsuthar17086@gmail.com', '8485868789', '27ABCDE1234F1Z3', NULL, 'A-103, Aaryan Pride, Vandemataram Road, Gota', NULL, NULL, 'India', NULL, '', '2026-01-15 11:07:04', '2026-01-15 11:07:04'),
(23, 'Maharashtra Industries', 'Rajesh Kumar', 'rajesh@maharashtra-ind.com', '9876543210', '27ABCDE5678F1Z9', NULL, 'Plot 45, MIDC Area, Pune, Maharashtra 411019', 'Maharashtra', '27', 'India', NULL, NULL, '2026-01-26 05:59:43', '2026-01-26 05:59:43'),
(24, 'Delhi Steel Works', 'Amit Sharma', 'amit@delhisteel.com', '9876543211', '07FGHIJ9012K3Y4', NULL, '12/B, Industrial Area, Delhi 110001', 'Delhi', '07', 'India', NULL, NULL, '2026-01-26 05:59:43', '2026-01-26 05:59:43'),
(25, 'Local Fabricators Pvt Ltd', 'Suresh Patel', 'suresh@localfab.com', '9876543212', NULL, NULL, 'Shop 23, Naroda GIDC, Ahmedabad, Gujarat 382330', 'Gujarat', '24', 'India', NULL, NULL, '2026-01-26 05:59:43', '2026-01-26 05:59:43'),
(26, 'Tamil Nadu Construction', 'Venkat Raman', 'venkat@tnconst.com', '9876543213', '33LMNOP3456Q7R8', NULL, 'No.78, Anna Nagar, Chennai, Tamil Nadu 600040', 'Tamil Nadu', '33', 'India', NULL, NULL, '2026-01-26 05:59:43', '2026-01-26 05:59:43');

-- --------------------------------------------------------

--
-- Table structure for table `company_profile`
--

CREATE TABLE `company_profile` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `gst_number` varchar(15) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `pan_number` varchar(10) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `state` varchar(100) DEFAULT 'Gujarat',
  `state_code` varchar(2) DEFAULT '24',
  `logo_url` varchar(500) DEFAULT NULL,
  `signature_url` varchar(500) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL,
  `branch_name` varchar(255) DEFAULT NULL,
  `swift_code` varchar(50) DEFAULT NULL,
  `invoice_prefix` varchar(10) DEFAULT NULL,
  `default_due_days` int(11) DEFAULT 30,
  `invoice_terms` text DEFAULT NULL,
  `payment_instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company_profile`
--

INSERT INTO `company_profile` (`id`, `company_name`, `gst_number`, `email`, `phone`, `website`, `pan_number`, `address`, `state`, `state_code`, `logo_url`, `signature_url`, `bank_name`, `account_number`, `ifsc_code`, `account_holder_name`, `branch_name`, `swift_code`, `invoice_prefix`, `default_due_days`, `invoice_terms`, `payment_instructions`, `created_at`, `updated_at`) VALUES
(1, 'GS Metal Concept', '24BIDPS5550H1Z7', 'g.s.mistry@gmail.com', '9426031170', 'https://gsmetal-concept.harshsuthar17086.workers.dev/', '', 'Aaryan Pride,  Gota, Ahmedabad 382481', 'Gujarat', '24', NULL, NULL, '', '', '', '', '', '', '', 30, '', '', '2025-09-20 05:42:05', '2026-01-15 12:11:51');

-- --------------------------------------------------------

--
-- Table structure for table `hsn_codes`
--

CREATE TABLE `hsn_codes` (
  `id` int(11) NOT NULL,
  `hsn_code` varchar(20) NOT NULL,
  `description` text NOT NULL,
  `gst_rate` decimal(5,2) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hsn_codes`
--

INSERT INTO `hsn_codes` (`id`, `hsn_code`, `description`, `gst_rate`, `category`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '7308', 'Steel structures and parts', 18.00, 'Structural Steel', 1, '2026-01-15 14:14:55', '2026-01-15 14:14:55'),
(2, '7326', 'Other articles of iron or steel', 18.00, 'Fabrication', 1, '2026-01-15 14:14:55', '2026-01-15 14:14:55'),
(3, '9987', 'Welding Services', 18.00, 'Services', 1, '2026-01-15 14:14:55', '2026-01-15 14:14:55'),
(4, '9989', 'Metal Fabrication Services', 18.00, 'Services', 1, '2026-01-15 14:14:55', '2026-01-15 14:14:55'),
(5, '7301', 'Sheet piling of iron or steel, welded angles, shapes and sections', 18.00, 'Structural Steel', 1, '2026-01-15 15:15:58', '2026-01-15 15:15:58'),
(6, '7306', 'Other tubes, pipes and hollow profiles (welded, riveted)', 18.00, 'Pipes & Tubes', 1, '2026-01-15 15:15:58', '2026-01-15 15:15:58'),
(7, '7304', 'Tubes, pipes and hollow profiles, seamless, of iron or steel', 18.00, 'Pipes & Tubes', 1, '2026-01-15 15:15:58', '2026-01-15 15:15:58'),
(8, '7307', 'Tube or pipe fittings (couplings, elbows, sleeves)', 18.00, 'Pipe Fittings', 1, '2026-01-15 15:15:58', '2026-01-15 15:15:58'),
(9, '7216', 'Angles, shapes and sections of iron or non-alloy steel', 18.00, 'Steel Sections', 1, '2026-01-15 15:15:58', '2026-01-15 15:15:58'),
(10, '7214', 'Other bars and rods of iron or non-alloy steel', 18.00, 'Bars & Rods', 1, '2026-01-15 15:15:58', '2026-01-15 15:15:58'),
(11, '7213', 'Bars and rods, hot-rolled, in irregularly wound coils', 18.00, 'Bars & Rods', 1, '2026-01-15 15:15:58', '2026-01-15 15:15:58'),
(12, '7208', 'Flat-rolled products, hot-rolled, not clad/plated/coated', 18.00, 'Steel Sheets', 1, '2026-01-15 15:15:58', '2026-01-15 15:15:58'),
(13, '7209', 'Flat-rolled products, cold-rolled, not clad/plated/coated', 18.00, 'Steel Sheets', 1, '2026-01-15 15:15:58', '2026-01-15 15:15:58'),
(14, '7210', 'Flat-rolled products, clad, plated or coated', 18.00, 'Coated Sheets', 1, '2026-01-15 15:15:58', '2026-01-15 15:15:58'),
(15, '7217', 'Wire of iron or non-alloy steel', 18.00, 'Wire Products', 1, '2026-01-15 15:15:58', '2026-01-15 15:15:58'),
(16, '7315', 'Chain and parts thereof, of iron or steel', 18.00, 'Chains & Wire Products', 1, '2026-01-15 15:15:58', '2026-01-15 15:15:58'),
(17, '7313', 'Barbed wire, twisted hoop or single flat wire', 18.00, 'Wire Products', 1, '2026-01-15 15:15:58', '2026-01-15 15:15:58'),
(18, '7318', 'Screws, bolts, nuts, rivets, washers and similar articles', 18.00, 'Fasteners', 1, '2026-01-15 15:15:58', '2026-01-15 15:15:58'),
(19, '9988', 'Metal Cutting and Bending Services', 18.00, 'Services', 1, '2026-01-15 15:15:58', '2026-01-15 15:15:58'),
(20, '9986', 'Installation and Erection Services', 18.00, 'Services', 1, '2026-01-15 15:15:58', '2026-01-15 15:15:58'),
(21, '9985', 'Surface Treatment and Coating Services', 18.00, 'Services', 1, '2026-01-15 15:15:58', '2026-01-15 15:15:58'),
(22, '9984', 'Metal Repair and Maintenance Services', 18.00, 'Services', 1, '2026-01-15 15:15:58', '2026-01-15 15:15:58'),
(23, '7219', 'Flat-rolled products of stainless steel, width 600mm or more', 18.00, 'Stainless Steel', 1, '2026-01-15 15:15:58', '2026-01-15 15:15:58'),
(24, '7220', 'Flat-rolled products of stainless steel, width less than 600mm', 18.00, 'Stainless Steel', 1, '2026-01-15 15:15:58', '2026-01-15 15:15:58'),
(25, '7222', 'Other bars and rods of stainless steel; angles, shapes and sections', 18.00, 'Stainless Steel', 1, '2026-01-15 15:15:58', '2026-01-15 15:15:58');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `document_type` varchar(30) NOT NULL DEFAULT 'invoice' COMMENT 'invoice, quotation, bill-no-gst, challan',
  `client_id` int(11) NOT NULL,
  `source_document_id` int(11) DEFAULT NULL COMMENT 'Optional parent document in the unified document flow',
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cgst_amount` decimal(10,2) DEFAULT 0.00,
  `sgst_amount` decimal(10,2) DEFAULT 0.00,
  `igst_amount` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_type` varchar(10) NOT NULL DEFAULT 'flat' COMMENT 'flat or percent',
  `grand_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(30) NOT NULL DEFAULT 'unpaid',
  `issuer_type` varchar(20) NOT NULL DEFAULT 'company' COMMENT 'company or personal',
  `place_of_supply` varchar(100) DEFAULT NULL,
  `state_code` varchar(2) DEFAULT NULL,
  `is_interstate` tinyint(1) DEFAULT 0,
  `reverse_charge` tinyint(1) DEFAULT 0,
  `amount_received` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `document_image` longtext DEFAULT NULL COMMENT 'Base64 data URL or remote URL for quotation/bill imagery',
  `vehicle_number` varchar(50) DEFAULT NULL,
  `transport_mode` varchar(50) DEFAULT NULL,
  `lr_number` varchar(50) DEFAULT NULL COMMENT 'Lorry Receipt Number',
  `eway_bill_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `hsn_code` varchar(20) DEFAULT NULL,
  `specifications` longtext DEFAULT NULL COMMENT 'JSON metadata for description_extra and sub_items',
  `item_date` date DEFAULT NULL COMMENT 'Per-row date used by challan rows in the unified document flow',
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit` varchar(50) DEFAULT 'Nos',
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `cgst_rate` decimal(5,2) DEFAULT 0.00,
  `cgst_amount` decimal(10,2) DEFAULT 0.00,
  `sgst_rate` decimal(5,2) DEFAULT 0.00,
  `sgst_amount` decimal(10,2) DEFAULT 0.00,
  `igst_rate` decimal(5,2) DEFAULT 0.00,
  `igst_amount` decimal(10,2) DEFAULT 0.00,
  `line_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `image_url` longtext DEFAULT NULL COMMENT 'Supports pasted base64 data URLs as well as remote URLs',
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `item_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `username`, `password`, `created_at`, `last_login`, `is_active`) VALUES
(1, 'Harsh', 'Suthar', 'harshsuthar17086@gmail.com', 'HarshSuthar1', '$2y$10$qwW/NZyArUoVEkETl3SwfOE4Xwvskb0MgRYJypdyP./qOztkv3crO', '2025-09-27 10:30:38', '2025-09-30 01:15:02', 1);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_active_quotations`
-- (See below for the actual view)
--
CREATE TABLE `v_active_quotations` (
`id` int(11)
,`quotation_number` varchar(50)
,`quotation_date` date
,`valid_until` date
,`status` varchar(30)
,`grand_total` decimal(10,2)
,`client_name` varchar(255)
,`item_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_invoice_document_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_invoice_document_summary` (
`document_type` varchar(30)
,`invoice_count` bigint(21)
,`total_amount` decimal(32,2)
,`received_amount` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_quotation_conversion_stats`
-- (See below for the actual view)
--
CREATE TABLE `v_quotation_conversion_stats` (
`total_quotations` bigint(21)
,`converted_count` decimal(22,0)
,`rejected_count` decimal(22,0)
,`pending_count` decimal(22,0)
,`conversion_rate` decimal(28,2)
,`converted_value` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Structure for view `v_active_quotations`
--
DROP TABLE IF EXISTS `v_active_quotations`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_active_quotations`  AS SELECT `i`.`id` AS `id`, `i`.`invoice_number` AS `quotation_number`, `i`.`invoice_date` AS `quotation_date`, `i`.`due_date` AS `valid_until`, `i`.`status` AS `status`, `i`.`grand_total` AS `grand_total`, `c`.`company_name` AS `client_name`, count(`ii`.`id`) AS `item_count` FROM ((`invoices` `i` join `clients` `c` on(`i`.`client_id` = `c`.`id`)) left join `invoice_items` `ii` on(`i`.`id` = `ii`.`invoice_id`)) WHERE `i`.`document_type` = 'quotation' AND lower(coalesce(`i`.`status`,'')) <> 'cancelled' GROUP BY `i`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `v_invoice_document_summary`
--
DROP TABLE IF EXISTS `v_invoice_document_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_invoice_document_summary`  AS SELECT `invoices`.`document_type` AS `document_type`, count(0) AS `invoice_count`, sum(`invoices`.`grand_total`) AS `total_amount`, sum(`invoices`.`amount_received`) AS `received_amount` FROM `invoices` GROUP BY `invoices`.`document_type` ;

-- --------------------------------------------------------

--
-- Structure for view `v_quotation_conversion_stats`
--
DROP TABLE IF EXISTS `v_quotation_conversion_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_quotation_conversion_stats`  AS SELECT count(distinct `q`.`id`) AS `total_quotations`, count(distinct case when `child`.`id` is not null then `q`.`id` else null end) AS `converted_count`, sum(case when lower(coalesce(`q`.`status`,'')) = 'cancelled' then 1 else 0 end) AS `rejected_count`, sum(case when lower(coalesce(`q`.`status`,'')) <> 'cancelled' and `child`.`id` is null then 1 else 0 end) AS `pending_count`, CASE WHEN count(distinct `q`.`id`) = 0 THEN 0 ELSE round(count(distinct case when `child`.`id` is not null then `q`.`id` else null end) * 100.0 / count(distinct `q`.`id`),2) END AS `conversion_rate`, sum(case when `child`.`id` is not null then `q`.`grand_total` else 0 end) AS `converted_value` FROM (`invoices` `q` left join `invoices` `child` on(`child`.`source_document_id` = `q`.`id` and `child`.`document_type` = 'invoice')) WHERE `q`.`document_type` = 'quotation' ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_client_gst` (`client_gst_number`),
  ADD KEY `idx_state_code` (`state_code`);

--
-- Indexes for table `company_profile`
--
ALTER TABLE `company_profile`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hsn_codes`
--
ALTER TABLE `hsn_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hsn_code` (`hsn_code`),
  ADD KEY `idx_hsn_code` (`hsn_code`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `idx_invoice_doc_type` (`document_type`),
  ADD KEY `idx_invoice_source_document` (`source_document_id`),
  ADD KEY `idx_invoice_state` (`state_code`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `idx_invoice_hsn` (`hsn_code`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `company_profile`
--
ALTER TABLE `company_profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hsn_codes`
--
ALTER TABLE `hsn_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_invoices_source_document` FOREIGN KEY (`source_document_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`);

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `fk_invoice_items_hsn` FOREIGN KEY (`hsn_code`) REFERENCES `hsn_codes` (`hsn_code`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

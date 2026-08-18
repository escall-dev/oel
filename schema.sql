-- QPTEO Electronic Logbook System Database Schema
CREATE DATABASE IF NOT EXISTS `qpteo_logbook_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `qpteo_logbook_db`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'encoder', 'viewer') NOT NULL DEFAULT 'viewer',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_name` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Document Types Table
CREATE TABLE IF NOT EXISTS `document_types` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `type_name` VARCHAR(100) NOT NULL,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Attachment Items Table
CREATE TABLE IF NOT EXISTS `attachment_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `document_type_id` INT NOT NULL,
  `item_name` VARCHAR(150) NOT NULL,
  FOREIGN KEY (`document_type_id`) REFERENCES `document_types`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Documents Table
CREATE TABLE IF NOT EXISTS `documents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reference_number` VARCHAR(100) NULL DEFAULT NULL,
  `direction` ENUM('Incoming', 'Outgoing') NOT NULL,
  `document_title` VARCHAR(255) NOT NULL,
  `category_id` INT NOT NULL,
  `document_type_id` INT NULL,
  `origin_source` VARCHAR(255) NULL,
  `recipient_office` VARCHAR(255) NULL,
  `document_date` DATE NOT NULL,
  `time_log` TIME NULL DEFAULT NULL,
  `remarks` TEXT NULL,
  `encoded_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`),
  FOREIGN KEY (`document_type_id`) REFERENCES `document_types`(`id`),
  FOREIGN KEY (`encoded_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Document Attachments Table
CREATE TABLE IF NOT EXISTS `document_attachments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `document_id` INT NOT NULL,
  `attachment_item_id` INT NULL,
  `custom_item_name` VARCHAR(150) NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`document_id`) REFERENCES `documents`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`attachment_item_id`) REFERENCES `attachment_items`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SEED DATA

-- Seed Categories
INSERT IGNORE INTO `categories` (`id`, `category_name`) VALUES
(1, 'Finance and Administration Office'),
(2, 'Bids and Awards Committee'),
(3, 'Human Resource'),
(4, 'With Codes'),
(5, 'Other Legal Documents'),
(6, 'Technical'),
(7, 'Others');

-- Seed Document Types (23 types)
INSERT IGNORE INTO `document_types` (`id`, `category_id`, `type_name`) VALUES
-- Finance and Administration Office (1-4)
(1, 1, 'Payment'),
(2, 1, 'Reimbursement'),
(3, 1, 'Cash Advance'),
(4, 1, 'Liquidation'),
-- Bids and Awards Committee (5)
(5, 2, 'Procurement'),
-- Human Resource (6-9)
(6, 3, 'HR Forms'),
(7, 3, 'Service Agreement'),
(8, 3, 'Appointment'),
(9, 3, 'Salary'),
-- With Codes (10-14)
(10, 4, 'Letter'),
(11, 4, 'Memorandum'),
(12, 4, 'Notice of Meeting'),
(13, 4, 'Special Order'),
(14, 4, 'Office Order'),
-- Other Legal Documents (15-17)
(15, 5, 'Resolution'),
(16, 5, 'Circular'),
(17, 5, 'MOA/MOU'),
-- Technical (18-22)
(18, 6, 'Report'),
(19, 6, 'Request'),
(20, 6, 'Proposal'),
(21, 6, 'Concept Note'),
(22, 6, 'Briefer'),
-- Others (23)
(23, 7, 'Certification');

-- Seed Attachment Items
INSERT IGNORE INTO `attachment_items` (`id`, `document_type_id`, `item_name`) VALUES
-- Payment (Type 1)
(1, 1, 'ORS'),
(2, 1, 'DV'),
(3, 1, 'Itinerary of Travel'),
(4, 1, 'Certificate of Completion of Travel'),
(5, 1, 'Summary of Expenses'),
(6, 1, 'Petty Cash Voucher'),
(7, 1, 'CSW'),

-- Reimbursement (Type 2)
(8, 2, 'ORS'),
(9, 2, 'DV'),
(10, 2, 'Summary of Expenses'),
(11, 2, 'CSW'),

-- Cash Advance (Type 3)
(12, 3, 'ORS'),
(13, 3, 'DV'),
(14, 3, 'Itinerary of Travel'),
(15, 3, 'CSW'),

-- Liquidation (Type 4)
(16, 4, 'Summary of Expenses'),
(17, 4, 'Certificate of Completion of Travel'),
(18, 4, 'Petty Cash Voucher'),

-- Procurement (Type 5)
(19, 5, 'Purchase Request'),
(20, 5, 'Purchase Order'),
(21, 5, 'Notice to Award'),
(22, 5, 'Notice to Proceed'),
(23, 5, 'Contract'),
(24, 5, 'Technical Specifications'),
(25, 5, 'PPMP'),
(26, 5, 'APP'),

-- HR Forms (Type 6)
(27, 6, 'Application for Leave'),
(28, 6, 'Clearance Form'),
(29, 6, 'AR'),
(30, 6, 'DTR'),
(31, 6, 'Permit to Study/Part-time Work/Practice of Private Profession'),

-- Service Agreement (Type 7)
(32, 7, 'Application for Leave'),
(33, 7, 'Clearance Form'),
(34, 7, 'AR'),

-- Appointment (Type 8)
(35, 8, 'Clearance Form'),
(36, 8, 'DTR'),

-- Salary (Type 9)
(37, 9, 'DTR'),
(38, 9, 'AR'),

-- Technical Types (Report 18, Request 19, Proposal 20, Concept Note 21, Briefer 22)
(39, 18, 'Travel Authority'),
(40, 19, 'Travel Authority'),
(41, 20, 'Travel Authority'),
(42, 21, 'Travel Authority'),
(43, 22, 'Travel Authority'),

-- Certification (Type 23)
(44, 23, 'Attendance Sheet'),
(45, 23, 'Justification');

-- Default Superadmin User (061920 / escall)
-- Password hash for 'escall'
INSERT IGNORE INTO `users` (`id`, `username`, `password`, `full_name`, `role`) VALUES
(1, '061920', '$2y$10$iM1vNq3h4j8D8Kz9A1L.EeC5aM0zS0R1Q2P3O4N5M6L7K8J9I0H1G', 'Super Admin', 'admin');

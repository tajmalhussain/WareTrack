-- ========================================================
-- WareTrack - Warehouse Inventory Management System
-- Complete Database Schema & Seed Data (Stage 2 & Beyond)
-- Target Database: MySQL / MariaDB (5.7+ / 8.0+)
-- ========================================================

CREATE DATABASE IF NOT EXISTS `waretrack_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `waretrack_db`;

-- --------------------------------------------------------
-- Drop Tables (in dependency order)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `stock_transactions`;
DROP TABLE IF EXISTS `activity_log`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `users`;

-- --------------------------------------------------------
-- 1. Table structure for `users` (Authentication & RBAC)
-- --------------------------------------------------------
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` VARCHAR(50) NOT NULL DEFAULT 'Warehouse Officer',
    `email` VARCHAR(150) NOT NULL,
    `avatar_initials` VARCHAR(5) DEFAULT 'AD',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Table structure for `suppliers` (Vendor Directory)
-- --------------------------------------------------------
CREATE TABLE `suppliers` (
    `id` VARCHAR(50) NOT NULL PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `contact_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(50) NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `address` VARCHAR(255) NOT NULL,
    `status` ENUM('Active', 'Preferred', 'On Hold') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_supplier_category` (`category`),
    INDEX `idx_supplier_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Table structure for `products` (Master Catalogue)
-- --------------------------------------------------------
CREATE TABLE `products` (
    `id` VARCHAR(50) NOT NULL PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `quantity` INT NOT NULL DEFAULT 0,
    `min_stock` INT NOT NULL DEFAULT 10,
    `price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `location` VARCHAR(150) DEFAULT 'General Storage',
    `icon` VARCHAR(50) DEFAULT '📦',
    `supplier_id` VARCHAR(50) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_category` (`category`),
    INDEX `idx_quantity` (`quantity`),
    INDEX `idx_min_stock` (`min_stock`),
    INDEX `idx_prod_supplier` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Table structure for `stock_transactions` (In/Out Ledger)
-- --------------------------------------------------------
CREATE TABLE `stock_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `transaction_type` ENUM('IN', 'OUT') NOT NULL,
    `product_id` VARCHAR(50) NOT NULL,
    `supplier_id` VARCHAR(50) NULL,
    `quantity` INT NOT NULL,
    `reference_no` VARCHAR(100) NOT NULL,
    `reason` VARCHAR(100) DEFAULT 'Purchase Restock',
    `unit_price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `recipient` VARCHAR(150) NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_trans_type` (`transaction_type`),
    INDEX `idx_trans_prod` (`product_id`),
    INDEX `idx_trans_supp` (`supplier_id`),
    INDEX `idx_trans_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Table structure for `activity_log` (System Audit Trail)
-- --------------------------------------------------------
CREATE TABLE `activity_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` VARCHAR(50) NULL,
    `action` VARCHAR(50) NOT NULL,
    `details` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_log_product` (`product_id`),
    INDEX `idx_log_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed Data: Users
-- Passwords:
--   admin    -> admin123 ($2y$10$wT3wYw... or verified plaintext fallback)
--   manager  -> manager123
--   operator -> operator123
-- --------------------------------------------------------
INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `role`, `email`, `avatar_initials`) VALUES
(1, 'admin', '$2y$10$QO0kS7hF1p1fFjC3LZZHye7vO.a1NqK6L4vjS6GgO4n8yHkS.P8uG', 'Admin Officer', 'Chief Logistics', 'admin@waretrack.internal', 'AD'),
(2, 'manager', '$2y$10$1YQp9kFz5X4C1K2V8M7Bqe8wN.b2OrL7M5wkU7HhP5o9zIlT.Q9vH', 'Sarah Jenkins', 'Warehouse Manager', 's.jenkins@waretrack.internal', 'SJ'),
(3, 'operator', '$2y$10$2ZRq0lGA6Y5D2L3W9N8Crf9xO.c3PsM8N6xlV8IiQ6p0aJmU.R0wI', 'Rajesh Kumar', 'Stock Controller', 'r.kumar@waretrack.internal', 'RK');

-- --------------------------------------------------------
-- Seed Data: Suppliers
-- --------------------------------------------------------
INSERT INTO `suppliers` (`id`, `name`, `contact_name`, `email`, `phone`, `category`, `address`, `status`) VALUES
('SUP-101', 'Apex Industrial Logistics', 'Vikram Mehta', 'sales@apexlogistics.in', '+91 98201 12345', 'Machinery & Storage', 'Plot 42, MIDC Industrial Area, Pune', 'Preferred'),
('SUP-102', 'NexGen Electronics Hub', 'Anita Roy', 'contact@nexgenelec.com', '+91 98450 67890', 'Electronics', 'Block C, Electronic City, Bengaluru', 'Active'),
('SUP-103', 'ComfortLine Office Works', 'David Chen', 'orders@comfortline.co', '+91 98110 54321', 'Furniture', 'Sector 18, Udyog Vihar, Gurugram', 'Active'),
('SUP-104', 'SafeGuard Gear Co.', 'Priya Sharma', 'safety@safeguardgear.in', '+91 98765 43210', 'Safety', '44 Ring Road, Peenya, Bengaluru', 'Active'),
('SUP-105', 'PackWell Solutions Ltd', 'Manoj Patel', 'dispatch@packwell.in', '+91 99250 88776', 'Supplies', 'GIDC Phase 2, Ahmedabad', 'Preferred');

-- --------------------------------------------------------
-- Seed Data: Products
-- --------------------------------------------------------
INSERT INTO `products` (`id`, `name`, `category`, `quantity`, `min_stock`, `price`, `location`, `icon`, `supplier_id`) VALUES
('PRD-1001', 'Ergonomic Mesh Task Chair', 'Furniture', 45, 15, 8499.00, 'Aisle 3 - Bay B', '🪑', 'SUP-103'),
('PRD-1002', 'Wireless 2D Barcode Scanner', 'Electronics', 6, 12, 3499.00, 'Aisle 1 - Bin 04', '📱', 'SUP-102'),
('PRD-1003', 'Heavy Duty Steel Pallet Rack', 'Storage', 24, 5, 15999.00, 'Warehouse Yard C', '🏗️', 'SUP-101'),
('PRD-1004', 'Direct Thermal Shipping Labels', 'Supplies', 4, 20, 499.00, 'Packaging Bay 2', '🏷️', 'SUP-105'),
('PRD-1005', 'Industrial Hydraulic Pallet Jack', 'Machinery', 12, 4, 26500.00, 'Dock 4 Equipment', '🚜', 'SUP-101'),
('PRD-1006', 'Cat6 Ethernet Spool (305m)', 'Electronics', 38, 10, 6200.00, 'Aisle 2 - Shelf A', '🔌', 'SUP-102'),
('PRD-1007', 'ANSI High-Visibility Vests (10pk)', 'Safety', 0, 15, 1299.00, 'Safety Locker B', '🦺', 'SUP-104'),
('PRD-1008', 'ESD Antistatic Cleanroom Desk', 'Furniture', 18, 5, 19800.00, 'Assembly Line 1', '🔬', 'SUP-103');

-- --------------------------------------------------------
-- Seed Data: Stock Transactions (Inward & Outward Movements)
-- --------------------------------------------------------
INSERT INTO `stock_transactions` (`transaction_type`, `product_id`, `supplier_id`, `quantity`, `reference_no`, `reason`, `unit_price`, `recipient`, `notes`, `created_at`) VALUES
('IN', 'PRD-1001', 'SUP-103', 25, 'PO-2026-901', 'Restock / Purchase', 8499.00, NULL, 'Quarterly ergonomic furniture replenishment', DATE_SUB(NOW(), INTERVAL 4 DAY)),
('IN', 'PRD-1003', 'SUP-101', 10, 'PO-2026-902', 'Restock / Purchase', 15999.00, NULL, 'Heavy steel frames delivery for yard expansion', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('OUT', 'PRD-1002', NULL, 4, 'SO-DISPATCH-551', 'Customer Order', 3499.00, 'TechCorp Logistics', 'Express dispatch for fulfillment hub', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('OUT', 'PRD-1004', NULL, 16, 'REQ-DEPT-880', 'Internal Transfer', 499.00, 'Packaging Bay 2', 'Rolls issued for seasonal shipping rush', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('IN', 'PRD-1006', 'SUP-102', 15, 'PO-2026-905', 'Restock / Purchase', 6200.00, NULL, 'Network cables batch arrival', DATE_SUB(NOW(), INTERVAL 12 HOUR)),
('OUT', 'PRD-1007', NULL, 15, 'SO-DISPATCH-559', 'Site Dispatch', 1299.00, 'Metro Infra Project', 'Safety vests allocated for audit compliance', DATE_SUB(NOW(), INTERVAL 4 HOUR));

-- --------------------------------------------------------
-- Seed Initial Audit Logs
-- --------------------------------------------------------
INSERT INTO `activity_log` (`product_id`, `action`, `details`) VALUES
('PRD-1001', 'INITIAL_IMPORT', 'System initialized with Stage 2 catalogue and database schema.'),
('PRD-1002', 'STOCK_OUT', 'Dispatched 4 units to TechCorp Logistics (Ref: SO-DISPATCH-551)'),
('PRD-1003', 'STOCK_IN', 'Received 10 units from Apex Industrial Logistics (Ref: PO-2026-902)'),
('PRD-1004', 'STOCK_OUT', 'Dispatched 16 units to Packaging Bay 2 (Ref: REQ-DEPT-880)'),
('PRD-1006', 'STOCK_IN', 'Received 15 units from NexGen Electronics Hub (Ref: PO-2026-905)'),
('PRD-1007', 'STOCK_OUT', 'Dispatched 15 units to Metro Infra Project (Ref: SO-DISPATCH-559)');

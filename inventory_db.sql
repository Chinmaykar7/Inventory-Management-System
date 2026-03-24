-- ============================================================
-- Intelligent Inventory Optimization & Demand Analytics System
-- Database Schema for phpMyAdmin Import
-- ============================================================
-- How to use:
--   1. Open phpMyAdmin (http://localhost/phpmyadmin)
--   2. Click on the "Import" tab at the top
--   3. Choose this file (inventory_db.sql)
--   4. Click "Go" to execute
--   5. The database and all 4 tables will be created
-- ============================================================

-- Create and select the database
CREATE DATABASE IF NOT EXISTS `inventory_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE `inventory_db`;

-- ============================================================
-- TABLE 1: stock_master
-- Tracks every batch of stock that enters the inventory.
-- CSV: "Stock Master.csv"
-- ============================================================
CREATE TABLE IF NOT EXISTS `stock_master` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `stock_code` VARCHAR(20) NOT NULL,
  `product_code` INT NOT NULL,
  `manufacturing_date` DATE DEFAULT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `initial_qty` INT NOT NULL DEFAULT 0,
  `remaining_qty` INT NOT NULL DEFAULT 0,
  `arrival_date` DATE DEFAULT NULL,
  `shelf_life` DECIMAL(10,2) DEFAULT 0.00,
  `lead_time` DECIMAL(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  INDEX `idx_stock_code` (`stock_code`),
  INDEX `idx_expiry_date` (`expiry_date`),
  INDEX `idx_stock_remaining` (`stock_code`, `remaining_qty`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE 2: transaction_master
-- Records every sales transaction with location, weather,
-- financial details, and time-series features.
-- CSV: "Transaction Master.csv"
-- ============================================================
CREATE TABLE IF NOT EXISTS `transaction_master` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `stock_code` VARCHAR(20) NOT NULL,
  `order_id` VARCHAR(20) NOT NULL,
  `category` VARCHAR(50) DEFAULT NULL,
  `sub_category` VARCHAR(50) DEFAULT NULL,
  `city` VARCHAR(50) DEFAULT NULL,
  `region` VARCHAR(20) DEFAULT NULL,
  `sales` DECIMAL(12,2) DEFAULT 0.00,
  `discount` DECIMAL(5,2) DEFAULT 0.00,
  `profit` DECIMAL(12,2) DEFAULT 0.00,
  `day_of_week` INT DEFAULT NULL,
  `is_weekend` TINYINT(1) DEFAULT 0,
  `weather` VARCHAR(20) DEFAULT NULL,
  `unit_cost` DECIMAL(10,2) DEFAULT 0.00,
  `units_sold` INT DEFAULT 0,
  `transaction_date` DATE DEFAULT NULL,
  `week_date` DATE DEFAULT NULL,
  `rolling_avg_4weeks` DECIMAL(10,2) DEFAULT 0.00,
  `product_code` INT DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_stock_code` (`stock_code`),
  INDEX `idx_order_id` (`order_id`),
  INDEX `idx_transaction_date` (`transaction_date`),
  INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE 3: lost_table
-- Logs lost/unmet demand — when customers wanted stock
-- that wasn't available (understock events).
-- CSV: "Lost Table.csv"
-- ============================================================
CREATE TABLE IF NOT EXISTS `lost_table` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `lost_date` DATE DEFAULT NULL,
  `stock_code` VARCHAR(20) NOT NULL,
  `lost_qty` INT NOT NULL DEFAULT 0,
  `lost_profit` DECIMAL(12,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  INDEX `idx_stock_code` (`stock_code`),
  INDEX `idx_lost_date` (`lost_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE 4: waste_table
-- Tracks expired/wasted inventory — when stock passed its
-- expiry date before being sold (overstock events).
-- CSV: "Waste Table.csv"
-- ============================================================
CREATE TABLE IF NOT EXISTS `waste_table` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `waste_date` DATE DEFAULT NULL,
  `stock_code` VARCHAR(20) NOT NULL,
  `product_code` INT DEFAULT NULL,
  `expired_qty` INT NOT NULL DEFAULT 0,
  `waste_lost_profit` DECIMAL(12,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  INDEX `idx_stock_code` (`stock_code`),
  INDEX `idx_waste_date` (`waste_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DONE! Now import the CSV files into each table:
--   1. Click on a table (e.g. stock_master)
--   2. Go to "Import" tab
--   3. Choose the corresponding CSV file
--   4. Format: CSV
--   5. Check "The first line of the file contains the table
--      column names" option
--   6. Click "Go"
--   7. Repeat for all 4 tables
-- ============================================================

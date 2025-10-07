-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 07, 2025 at 01:52 PM
-- Server version: 8.0.42
-- PHP Version: 8.4.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `vxjtgclw_Spirits`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `action` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'Login', 'User logged in successfully', '41.209.14.78', '2025-10-07 08:13:33'),
(2, 1, 'Login', 'User logged in successfully', '41.209.14.78', '2025-10-07 08:18:38'),
(3, 1, 'Login', 'User logged in successfully', '41.209.14.78', '2025-10-07 09:26:20'),
(4, 1, 'Login', 'User logged in successfully', '41.209.14.78', '2025-10-07 09:30:28'),
(5, 1, 'Login', 'User logged in successfully', '41.209.14.78', '2025-10-07 09:40:41'),
(6, 1, 'SALE_COMPLETED', 'Completed sale ZWS-20251007-7F0A8A with total 1800', '41.209.14.78', '2025-10-07 10:33:44'),
(7, 1, 'SALE_COMPLETED', 'Completed sale ZWS-20251007-B0487D with total 380', '41.209.14.78', '2025-10-07 10:34:35'),
(8, 2, 'Login', 'User logged in successfully', '41.209.14.78', '2025-10-07 10:47:46'),
(9, 2, 'SALE_COMPLETED', 'Completed sale ZWS-20251007-51D8F6 with total 2300', '41.209.14.78', '2025-10-07 10:48:05');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Whisky', 'Premium Scotch, Bourbon, and Blended Whiskies', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(2, 'Vodka', 'Premium and Standard Vodkas', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(3, 'Gin', 'London Dry, Premium and Flavored Gins', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(4, 'Rum', 'White, Dark, Spiced and Premium Rums', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(5, 'Cognac & Brandy', 'Fine Cognacs and Brandies', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(6, 'Tequila', 'Blanco, Reposado and Añejo Tequilas', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(7, 'Wine - Red', 'Red Wines from Various Regions', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(8, 'Wine - White', 'White and Rosé Wines', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(9, 'Wine - Sparkling', 'Champagne and Sparkling Wines', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(10, 'Beer', 'Local and International Beers', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(11, 'Liqueurs', 'Sweet and Flavored Liqueurs', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(12, 'Ready-to-Drink (RTD)', 'Pre-mixed Cocktails and Coolers', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(13, 'Non-Alcoholic', 'Mixers, Soft Drinks and Water', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `category` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `description` text,
  `expense_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int NOT NULL,
  `category_id` int DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `barcode` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cost_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `selling_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock_quantity` int NOT NULL DEFAULT '0',
  `reorder_level` int NOT NULL DEFAULT '10',
  `supplier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bottle',
  `size` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sku` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `barcode`, `description`, `cost_price`, `selling_price`, `stock_quantity`, `reorder_level`, `supplier`, `unit`, `size`, `sku`, `location`, `expiry_date`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'Johnnie Walker Black Label 750ml', 'JWB750', '12 Year Old Blended Scotch Whisky', 2800.00, 3500.00, 45, 10, 'Diageo Kenya', 'bottle', NULL, 'WHY-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(2, 1, 'Johnnie Walker Red Label 750ml', 'JWR750', 'Classic Blended Scotch Whisky', 1800.00, 2300.00, 60, 15, 'Diageo Kenya', 'bottle', NULL, 'WHY-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(3, 1, 'Jameson Irish Whiskey 750ml', 'JAM750', 'Triple Distilled Irish Whiskey', 2200.00, 2800.00, 35, 10, 'Pernod Ricard', 'bottle', NULL, 'WHY-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(4, 1, 'Jack Daniels Tennessee Whiskey 750ml', 'JD750', 'Old No.7 Tennessee Whiskey', 3000.00, 3800.00, 30, 8, 'Brown-Forman', 'bottle', NULL, 'WHY-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(5, 1, 'Chivas Regal 12 Year 750ml', 'CHV12', 'Premium Blended Scotch Whisky', 3500.00, 4500.00, 25, 8, 'Pernod Ricard', 'bottle', NULL, 'WHY-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(6, 1, 'Glenfiddich 12 Year 750ml', 'GLE12', 'Single Malt Scotch Whisky', 4000.00, 5200.00, 20, 5, 'William Grant', 'bottle', NULL, 'WHY-006', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(7, 1, 'Teachers Highland Cream 750ml', 'TCH750', 'Blended Scotch Whisky', 1600.00, 2000.00, 50, 15, 'Beam Suntory', 'bottle', NULL, 'WHY-007', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(8, 1, 'Famous Grouse 750ml', 'FGR750', 'Finest Blended Scotch Whisky', 1700.00, 2200.00, 40, 12, 'Edrington Group', 'bottle', NULL, 'WHY-008', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(9, 1, 'Ballantines Finest 750ml', 'BAL750', 'Premium Blended Scotch', 1900.00, 2400.00, 38, 10, 'Pernod Ricard', 'bottle', NULL, 'WHY-009', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(10, 1, 'Grants Triple Wood 750ml', 'GRT750', 'Triple Matured Blended Scotch', 1500.00, 1900.00, 55, 15, 'William Grant', 'bottle', NULL, 'WHY-010', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(11, 2, 'Smirnoff Red Label 750ml', 'SMR750', 'Triple Distilled Vodka', 1400.00, 1800.00, 65, 20, 'Diageo Kenya', 'bottle', NULL, 'VOD-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(12, 2, 'Absolute Vodka 750ml', 'ABS750', 'Swedish Premium Vodka', 1800.00, 2300.00, 44, 15, 'Pernod Ricard', 'bottle', NULL, 'VOD-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 10:48:05'),
(13, 2, 'Ciroc Vodka 750ml', 'CRC750', 'Ultra Premium French Vodka', 3500.00, 4500.00, 20, 8, 'Diageo Kenya', 'bottle', NULL, 'VOD-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(14, 2, 'Skyy Vodka 750ml', 'SKY750', 'American Premium Vodka', 1500.00, 1900.00, 50, 15, 'Campari Group', 'bottle', NULL, 'VOD-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(15, 2, 'Grey Goose 750ml', 'GGO750', 'French Luxury Vodka', 4000.00, 5200.00, 15, 5, 'Bacardi', 'bottle', NULL, 'VOD-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(16, 2, 'Russian Standard 750ml', 'RST750', 'Original Russian Vodka', 1600.00, 2000.00, 40, 12, 'Russian Standard', 'bottle', NULL, 'VOD-006', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(17, 2, 'Belvedere Vodka 750ml', 'BEL750', 'Polish Luxury Vodka', 3800.00, 4800.00, 18, 6, 'LVMH', 'bottle', NULL, 'VOD-007', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(18, 2, 'Ketel One 750ml', 'KET750', 'Dutch Premium Vodka', 2500.00, 3200.00, 28, 10, 'Diageo Kenya', 'bottle', NULL, 'VOD-008', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(19, 2, 'Stolichnaya 750ml', 'STO750', 'Premium Russian Vodka', 1700.00, 2200.00, 35, 12, 'SPI Group', 'bottle', NULL, 'VOD-009', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(20, 2, 'Smirnoff Ice 275ml', 'SMI275', 'Vodka Mixed Drink', 180.00, 250.00, 120, 40, 'Diageo Kenya', 'bottle', NULL, 'VOD-010', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(21, 3, 'Gordons London Dry Gin 750ml', 'GOR750', 'Classic London Dry Gin', 1300.00, 1700.00, 55, 15, 'Diageo Kenya', 'bottle', NULL, 'GIN-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(22, 3, 'Tanqueray London Dry Gin 750ml', 'TAN750', 'Premium London Dry Gin', 2200.00, 2800.00, 35, 10, 'Diageo Kenya', 'bottle', NULL, 'GIN-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(23, 3, 'Bombay Sapphire 750ml', 'BOM750', 'Premium London Dry Gin', 2400.00, 3000.00, 30, 10, 'Bacardi', 'bottle', NULL, 'GIN-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(24, 3, 'Beefeater London Dry Gin 750ml', 'BEE750', 'Classic London Gin', 1800.00, 2300.00, 40, 12, 'Pernod Ricard', 'bottle', NULL, 'GIN-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(25, 3, 'Hendricks Gin 750ml', 'HEN750', 'Premium Scottish Gin', 3200.00, 4000.00, 22, 8, 'William Grant', 'bottle', NULL, 'GIN-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(26, 3, 'Gilbeys Gin 750ml', 'GIL750', 'London Dry Gin', 1100.00, 1400.00, 60, 18, 'Diageo Kenya', 'bottle', NULL, 'GIN-006', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(27, 3, 'Gordons Pink Gin 750ml', 'GRP750', 'Premium Pink Gin', 1500.00, 1900.00, 45, 12, 'Diageo Kenya', 'bottle', NULL, 'GIN-007', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(28, 3, 'Monkey 47 Gin 500ml', 'MON500', 'Ultra Premium German Gin', 4500.00, 5800.00, 12, 5, 'Pernod Ricard', 'bottle', NULL, 'GIN-008', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(29, 4, 'Captain Morgan Spiced Rum 750ml', 'CAP750', 'Original Spiced Rum', 1600.00, 2000.00, 50, 15, 'Diageo Kenya', 'bottle', NULL, 'RUM-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(30, 4, 'Bacardi Superior White 750ml', 'BAC750', 'Premium White Rum', 1700.00, 2200.00, 45, 15, 'Bacardi', 'bottle', NULL, 'RUM-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(31, 4, 'Havana Club 3 Year 750ml', 'HAV3', 'Aged Cuban Rum', 1900.00, 2400.00, 35, 10, 'Pernod Ricard', 'bottle', NULL, 'RUM-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(32, 4, 'Malibu Coconut Rum 750ml', 'MAL750', 'Caribbean Coconut Rum', 1500.00, 1900.00, 55, 15, 'Pernod Ricard', 'bottle', NULL, 'RUM-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(33, 4, 'Appleton Estate 750ml', 'APP750', 'Jamaican Gold Rum', 2000.00, 2600.00, 30, 10, 'Campari Group', 'bottle', NULL, 'RUM-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(34, 4, 'Kraken Black Spiced Rum 750ml', 'KRA750', 'Black Spiced Rum', 2200.00, 2800.00, 28, 8, 'Proximo Spirits', 'bottle', NULL, 'RUM-006', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(35, 4, 'Bacardi Black 750ml', 'BAB750', 'Premium Dark Rum', 1800.00, 2300.00, 40, 12, 'Bacardi', 'bottle', NULL, 'RUM-007', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(36, 4, 'Mount Gay Eclipse 750ml', 'MGE750', 'Barbados Golden Rum', 2100.00, 2700.00, 32, 10, 'Remy Cointreau', 'bottle', NULL, 'RUM-008', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(37, 5, 'Hennessy VS 700ml', 'HEN700', 'Fine Cognac', 4500.00, 5800.00, 25, 8, 'Moet Hennessy', 'bottle', NULL, 'COG-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(38, 5, 'Remy Martin VSOP 700ml', 'REM700', 'Premium Cognac', 5500.00, 7000.00, 18, 6, 'Remy Cointreau', 'bottle', NULL, 'COG-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(39, 5, 'Martell VS 700ml', 'MAR700', 'French Cognac', 4000.00, 5200.00, 22, 8, 'Pernod Ricard', 'bottle', NULL, 'COG-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(40, 5, 'Courvoisier VS 700ml', 'COU700', 'Cognac Fine Champagne', 4200.00, 5500.00, 20, 6, 'Beam Suntory', 'bottle', NULL, 'COG-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(41, 5, 'Klipdrift Brandy 750ml', 'KLP750', 'South African Brandy', 1200.00, 1600.00, 45, 15, 'Distell', 'bottle', NULL, 'COG-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(42, 5, 'Viceroy Brandy 750ml', 'VIC750', 'Premium Brandy', 1400.00, 1800.00, 40, 12, 'Pernod Ricard', 'bottle', NULL, 'COG-006', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(43, 6, 'Jose Cuervo Especial Gold 750ml', 'JCG750', 'Premium Gold Tequila', 2500.00, 3200.00, 30, 10, 'Proximo Spirits', 'bottle', NULL, 'TEQ-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(44, 6, 'Jose Cuervo Especial Silver 750ml', 'JCS750', 'Silver Tequila', 2400.00, 3000.00, 32, 10, 'Proximo Spirits', 'bottle', NULL, 'TEQ-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(45, 6, 'Patron Silver 750ml', 'PAT750', 'Ultra Premium Tequila', 5000.00, 6500.00, 15, 5, 'Bacardi', 'bottle', NULL, 'TEQ-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(46, 6, 'Olmeca Blanco 750ml', 'OLM750', 'Premium Blanco Tequila', 2200.00, 2800.00, 28, 8, 'Pernod Ricard', 'bottle', NULL, 'TEQ-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(47, 6, 'Don Julio Blanco 750ml', 'DON750', 'Premium Luxury Tequila', 4800.00, 6200.00, 18, 6, 'Diageo Kenya', 'bottle', NULL, 'TEQ-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(48, 7, 'Four Cousins Sweet Red 750ml', 'FC4SR', 'Sweet Red Wine', 900.00, 1200.00, 80, 20, 'Distell', 'bottle', NULL, 'WRD-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(49, 7, 'Nederburg Cabernet Sauvignon 750ml', 'NED750', 'South African Red Wine', 1100.00, 1500.00, 60, 18, 'Distell', 'bottle', NULL, 'WRD-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(50, 7, 'Drostdy Hof Merlot 750ml', 'DRO750', 'Premium Merlot', 850.00, 1100.00, 70, 20, 'Distell', 'bottle', NULL, 'WRD-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(51, 7, 'Robertson Winery Sweet Red 750ml', 'ROB750', 'Natural Sweet Red', 750.00, 1000.00, 90, 25, 'Robertson', 'bottle', NULL, 'WRD-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(52, 7, 'Amarula Cream 750ml', 'AMA750', 'Cream Liqueur', 1400.00, 1800.00, 49, 15, 'Distell', 'bottle', NULL, 'WRD-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 10:33:44'),
(53, 7, 'KWV Red Muscadel 750ml', 'KWV750', 'Fortified Red Wine', 800.00, 1050.00, 65, 18, 'KWV', 'bottle', NULL, 'WRD-006', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(54, 7, 'Kumala Shiraz Cabernet 750ml', 'KUM750', 'Red Wine Blend', 950.00, 1250.00, 55, 15, 'Accolade Wines', 'bottle', NULL, 'WRD-007', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(55, 7, 'Casillero del Diablo Merlot 750ml', 'CAS750', 'Chilean Red Wine', 1300.00, 1700.00, 45, 12, 'Concha y Toro', 'bottle', NULL, 'WRD-008', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(56, 7, 'Lindeman\'s Bin 45 Cabernet 750ml', 'LIN750', 'Australian Red Wine', 1200.00, 1600.00, 48, 15, 'Treasury Wine', 'bottle', NULL, 'WRD-009', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(57, 7, 'Jacobs Creek Shiraz 750ml', 'JAC750', 'Classic Shiraz', 1100.00, 1450.00, 52, 15, 'Pernod Ricard', 'bottle', NULL, 'WRD-010', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(58, 8, 'Four Cousins Sweet White 750ml', 'FC4SW', 'Sweet White Wine', 900.00, 1200.00, 75, 20, 'Distell', 'bottle', NULL, 'WWH-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(59, 8, 'Nederburg Sauvignon Blanc 750ml', 'NEDSB', 'Crisp White Wine', 1100.00, 1500.00, 55, 15, 'Distell', 'bottle', NULL, 'WWH-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(60, 8, 'Robertson Winery Sweet White 750ml', 'ROBSW', 'Natural Sweet White', 750.00, 1000.00, 85, 25, 'Robertson', 'bottle', NULL, 'WWH-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(61, 8, 'Drostdy Hof Chardonnay 750ml', 'DROCH', 'Premium Chardonnay', 850.00, 1100.00, 60, 18, 'Distell', 'bottle', NULL, 'WWH-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(62, 8, 'Kumala Chenin Chardonnay 750ml', 'KUMCH', 'White Wine Blend', 950.00, 1250.00, 50, 15, 'Accolade Wines', 'bottle', NULL, 'WWH-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(63, 8, 'Jacobs Creek Chardonnay 750ml', 'JACCH', 'Classic Chardonnay', 1100.00, 1450.00, 48, 15, 'Pernod Ricard', 'bottle', NULL, 'WWH-006', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(64, 8, 'Casillero del Diablo Sauvignon 750ml', 'CASSB', 'Chilean White Wine', 1300.00, 1700.00, 42, 12, 'Concha y Toro', 'bottle', NULL, 'WWH-007', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(65, 8, 'KWV White Muscadel 750ml', 'KWVWM', 'Fortified White Wine', 800.00, 1050.00, 62, 18, 'KWV', 'bottle', NULL, 'WWH-008', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(66, 9, 'J.C. Le Roux Le Domaine 750ml', 'JCLD750', 'South African Sparkling', 1200.00, 1600.00, 40, 12, 'Distell', 'bottle', NULL, 'WSP-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(67, 9, 'Moet & Chandon Brut Imperial 750ml', 'MOET750', 'Premium Champagne', 6000.00, 7800.00, 15, 5, 'Moet Hennessy', 'bottle', NULL, 'WSP-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(68, 9, 'Veuve Clicquot Yellow Label 750ml', 'VEUVE750', 'Luxury Champagne', 7000.00, 9000.00, 12, 4, 'Moet Hennessy', 'bottle', NULL, 'WSP-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(69, 9, 'Pongracz Brut Rose 750ml', 'PONG750', 'Sparkling Rose', 1800.00, 2400.00, 30, 10, 'Distell', 'bottle', NULL, 'WSP-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(70, 9, 'Graham Beck Brut 750ml', 'GRAB750', 'Premium MCC', 1600.00, 2100.00, 35, 10, 'Graham Beck', 'bottle', NULL, 'WSP-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(71, 10, 'Tusker Lager 500ml', 'TUS500', 'Kenyan Premium Lager', 120.00, 180.00, 200, 50, 'EABL', 'bottle', NULL, 'BER-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(72, 10, 'Tusker Malt 500ml', 'TUSM500', 'Kenyan Premium Malt', 130.00, 200.00, 180, 50, 'EABL', 'bottle', NULL, 'BER-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(73, 10, 'White Cap Lager 500ml', 'WHC500', 'Kenyan Lager', 110.00, 170.00, 220, 60, 'EABL', 'bottle', NULL, 'BER-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(74, 10, 'Pilsner Lager 500ml', 'PIL500', 'Ice Cold Lager', 100.00, 150.00, 250, 70, 'EABL', 'bottle', NULL, 'BER-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(75, 10, 'Guinness Original 500ml', 'GUI500', 'Irish Stout', 150.00, 220.00, 150, 40, 'EABL', 'bottle', NULL, 'BER-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(76, 10, 'Heineken Lager 330ml', 'HEI330', 'Premium Lager', 140.00, 200.00, 180, 50, 'Heineken Kenya', 'bottle', NULL, 'BER-006', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(77, 10, 'Corona Extra 355ml', 'COR355', 'Mexican Lager', 160.00, 240.00, 120, 35, 'ABInBev', 'bottle', NULL, 'BER-007', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(78, 10, 'Budweiser 330ml', 'BUD330', 'American Lager', 150.00, 220.00, 140, 40, 'ABInBev', 'bottle', NULL, 'BER-008', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(79, 10, 'Amstel Lager 330ml', 'AMS330', 'Premium Lager', 130.00, 190.00, 158, 45, 'Heineken Kenya', 'bottle', NULL, 'BER-009', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 10:34:35'),
(80, 10, 'Balozi Lager 500ml', 'BAL500', 'Kenyan Lager', 90.00, 140.00, 280, 80, 'EABL', 'bottle', NULL, 'BER-010', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(81, 10, 'Chrome Vodka Ice 275ml', 'CHR275', 'Vodka Premix', 110.00, 160.00, 200, 50, 'EABL', 'bottle', NULL, 'BER-011', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(82, 10, 'Hunters Dry 330ml', 'HUN330', 'Premium Cider', 140.00, 200.00, 150, 40, 'Distell', 'bottle', NULL, 'BER-012', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(83, 11, 'Baileys Original Irish Cream 750ml', 'BAI750', 'Irish Cream Liqueur', 2200.00, 2800.00, 40, 12, 'Diageo Kenya', 'bottle', NULL, 'LIQ-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(84, 11, 'Jagermeister 700ml', 'JAG700', 'Herbal Liqueur', 2400.00, 3000.00, 35, 10, 'Mast-Jaegermeister', 'bottle', NULL, 'LIQ-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(85, 11, 'Cointreau 700ml', 'COI700', 'Orange Liqueur', 2800.00, 3600.00, 25, 8, 'Remy Cointreau', 'bottle', NULL, 'LIQ-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(86, 11, 'Kahlua Coffee Liqueur 750ml', 'KAH750', 'Coffee Liqueur', 2000.00, 2600.00, 30, 10, 'Pernod Ricard', 'bottle', NULL, 'LIQ-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(87, 11, 'Southern Comfort 750ml', 'SOU750', 'Whiskey Liqueur', 2200.00, 2800.00, 32, 10, 'Sazerac', 'bottle', NULL, 'LIQ-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(88, 11, 'Tia Maria 700ml', 'TIA700', 'Coffee Liqueur', 1900.00, 2400.00, 28, 8, 'Illva Saronno', 'bottle', NULL, 'LIQ-006', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(89, 11, 'Campari 700ml', 'CAM700', 'Italian Aperitif', 2100.00, 2700.00, 26, 8, 'Campari Group', 'bottle', NULL, 'LIQ-007', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(90, 11, 'Aperol 700ml', 'APE700', 'Italian Aperitif', 1800.00, 2300.00, 30, 10, 'Campari Group', 'bottle', NULL, 'LIQ-008', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(91, 12, 'Smirnoff Ice Double Black 300ml', 'SIDB300', 'Vodka Premix', 180.00, 250.00, 150, 40, 'Diageo Kenya', 'bottle', NULL, 'RTD-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(92, 12, 'Brutal Fruit Ruby 275ml', 'BRU275', 'Sparkling Fruit Drink', 160.00, 220.00, 180, 50, 'Heineken Kenya', 'bottle', NULL, 'RTD-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(93, 12, 'Flying Fish 330ml', 'FLY330', 'Flavored Beer', 150.00, 210.00, 160, 45, 'Distell', 'bottle', NULL, 'RTD-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(94, 12, 'Bacardi Breezer 275ml', 'BAB275', 'Rum Cooler', 170.00, 240.00, 140, 40, 'Bacardi', 'bottle', NULL, 'RTD-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(95, 12, 'Ciroc Spritz 275ml', 'CRS275', 'Vodka Cocktail', 200.00, 280.00, 100, 30, 'Diageo Kenya', 'bottle', NULL, 'RTD-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(96, 13, 'Coca Cola 500ml', 'COK500', 'Carbonated Soft Drink', 35.00, 60.00, 300, 100, 'Coca Cola', 'bottle', NULL, 'NON-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(97, 13, 'Sprite 500ml', 'SPR500', 'Lemon Lime Soda', 35.00, 60.00, 280, 100, 'Coca Cola', 'bottle', NULL, 'NON-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(98, 13, 'Schweppes Tonic Water 300ml', 'SCH300', 'Premium Tonic Water', 60.00, 100.00, 200, 60, 'Coca Cola', 'bottle', NULL, 'NON-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(99, 13, 'Keringet Sparkling Water 500ml', 'KER500', 'Natural Mineral Water', 50.00, 80.00, 250, 80, 'Keringet', 'bottle', NULL, 'NON-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(100, 13, 'Red Bull Energy 250ml', 'RDB250', 'Energy Drink', 150.00, 220.00, 180, 50, 'Red Bull', 'can', NULL, 'NON-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int NOT NULL,
  `sale_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_method` enum('cash','mpesa','mpesa_till','card') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `mpesa_reference` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `change_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `sale_date` datetime NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `sale_number`, `user_id`, `subtotal`, `tax_amount`, `discount_amount`, `total_amount`, `payment_method`, `mpesa_reference`, `amount_paid`, `change_amount`, `sale_date`, `notes`, `created_at`) VALUES
(1, 'ZWS-20251007-7F0A8A', 1, 1800.00, 0.00, 0.00, 1800.00, 'cash', NULL, 1800.00, 0.00, '2025-10-07 13:33:43', NULL, '2025-10-07 10:33:43'),
(2, 'ZWS-20251007-B0487D', 1, 380.00, 0.00, 0.00, 380.00, 'cash', NULL, 380.00, 0.00, '2025-10-07 13:34:35', NULL, '2025-10-07 10:34:35'),
(3, 'ZWS-20251007-51D8F6', 2, 2300.00, 0.00, 0.00, 2300.00, 'cash', NULL, 2300.00, 0.00, '2025-10-07 13:48:05', NULL, '2025-10-07 10:48:05');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int NOT NULL,
  `sale_id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`, `created_at`) VALUES
(1, 1, 52, 'Amarula Cream 750ml', 1, 1800.00, 1800.00, '2025-10-07 10:33:44'),
(2, 2, 79, 'Amstel Lager 330ml', 2, 190.00, 380.00, '2025-10-07 10:34:35'),
(3, 3, 12, 'Absolute Vodka 750ml', 1, 2300.00, 2300.00, '2025-10-07 10:48:05');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `session_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `login_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `logout_time` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `session_token`, `ip_address`, `user_agent`, `login_time`, `last_activity`, `logout_time`) VALUES
(1, 1, '2a5eb5d986be18c549eb1ac772104cb4c975f23aadf9025c3b900327f729006f', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-07 08:13:33', '2025-10-07 08:13:33', NULL),
(2, 1, '411b40a164876b00a24627620bb767998d762f8aadd1d85f75a22231218de0b2', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-07 08:18:38', '2025-10-07 08:18:38', NULL),
(3, 1, '2c3005dd117ae2b02a48e53d76a2da63213f537a81ee2df5ed1d79b84032a6aa', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-07 09:26:20', '2025-10-07 09:26:20', NULL),
(4, 1, '11f0812ea8686b810b86567b5d8febcb937f9e1ce357e7d32b6f263b068c5d6f', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-07 09:30:28', '2025-10-07 09:30:28', NULL),
(5, 1, '420ea5ec81aa816d11016bc09804fee2555e0a2dc4af92bda319a4bbcff4aafb', '41.209.14.78', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', '2025-10-07 09:40:41', '2025-10-07 09:40:41', NULL),
(6, 2, 'c9a35961c781054f54aaf3fb0425e7f142c1e2da550815557485061d14965dc4', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-07 10:47:46', '2025-10-07 10:47:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `company_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Zuri Wines & Spirits',
  `logo_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '/logo.jpg',
  `primary_color` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#ea580c',
  `secondary_color` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#ffffff',
  `currency` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'KSh',
  `currency_symbol` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'KSh',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `receipt_footer` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `barcode_scanner_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `low_stock_alert_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `company_name`, `logo_path`, `primary_color`, `secondary_color`, `currency`, `currency_symbol`, `tax_rate`, `receipt_footer`, `barcode_scanner_enabled`, `low_stock_alert_enabled`, `created_at`, `updated_at`) VALUES
(1, 'Zuri Wines & Spirits', '/logo.jpg', '#ea580c', '#ffffff', 'KSh', 'KSh', 0.00, 'Thank you for your business!\nVisit us again!', 1, 1, '2025-10-07 07:04:06', '2025-10-07 07:04:06');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `user_id` int NOT NULL,
  `movement_type` enum('in','out','adjustment','sale','return') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `reference_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` int DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `product_id`, `user_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_at`) VALUES
(1, 52, 1, 'sale', 1, 'sale', 1, 'Sale: ZWS-20251007-7F0A8A', '2025-10-07 10:33:44'),
(2, 79, 1, 'sale', 2, 'sale', 2, 'Sale: ZWS-20251007-B0487D', '2025-10-07 10:34:35'),
(3, 12, 2, 'sale', 1, 'sale', 3, 'Sale: ZWS-20251007-51D8F6', '2025-10-07 10:48:05');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pin_code` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('owner','seller') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'seller',
  `permissions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `pin_code`, `role`, `permissions`, `status`, `created_at`, `updated_at`) VALUES
(1, 'System Owner', '1234', 'owner', '[\"all\"]', 'active', '2025-10-07 07:04:05', '2025-10-07 07:04:05'),
(2, 'Seller Demo', '5678', 'seller', '[\"pos\", \"view_products\", \"add_products\", \"view_own_sales\"]', 'active', '2025-10-07 07:04:05', '2025-10-07 07:04:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `sku` (`sku`),
  ADD KEY `status` (`status`),
  ADD KEY `stock_quantity` (`stock_quantity`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_products_category_status` (`category_id`,`status`),
  ADD KEY `idx_products_stock_status` (`stock_quantity`,`status`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sale_number` (`sale_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `sale_date` (`sale_date`),
  ADD KEY `payment_method` (`payment_method`),
  ADD KEY `idx_sales_date_user` (`sale_date`,`user_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `last_activity` (`last_activity`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `movement_type` (`movement_type`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `idx_stock_movements_product_type` (`product_id`,`movement_type`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pin_code` (`pin_code`),
  ADD KEY `status` (`status`),
  ADD KEY `role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

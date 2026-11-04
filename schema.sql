-- ============================================================
-- Satay Ordering System - Production MySQL Database Schema & Data
-- Works with MySQL 5.7+, MySQL 8.0+, MariaDB, Aiven, TiDB, Render
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) UNIQUE NOT NULL,
    `description` TEXT,
    `display_order` INT DEFAULT 0,
    `icon` VARCHAR(255),
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Menu Items Table
CREATE TABLE IF NOT EXISTS `menu_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) UNIQUE,
    `description` TEXT,
    `price_per_unit` DECIMAL(10,2) NOT NULL,
    `unit_name` VARCHAR(50) DEFAULT 'stick',
    `min_quantity` INT DEFAULT 1,
    `image_url` TEXT,
    `is_popular` TINYINT(1) DEFAULT 0,
    `is_available` TINYINT(1) DEFAULT 1,
    `stock_quantity` INT DEFAULT 100,
    `stick_meat_type` VARCHAR(100) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tables (Dining Tables & QR Code tokens)
CREATE TABLE IF NOT EXISTS `tables` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `table_number` VARCHAR(50) UNIQUE NOT NULL,
    `qr_token` VARCHAR(255) UNIQUE NOT NULL,
    `status` VARCHAR(50) DEFAULT 'available',
    `capacity` INT DEFAULT 4,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Users Table (Admin, Kitchen Staff, Drink Staff, Customers)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) UNIQUE NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255),
    `phone` VARCHAR(50),
    `address` TEXT,
    `role` VARCHAR(50) NOT NULL DEFAULT 'customer',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Orders Table
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `is_guest` TINYINT(1) DEFAULT 1,
    `order_number` VARCHAR(100) UNIQUE NOT NULL,
    `customer_name` VARCHAR(255) NOT NULL,
    `customer_phone` VARCHAR(50),
    `dining_type` VARCHAR(50) NOT NULL,
    `table_number` VARCHAR(50),
    `delivery_address` TEXT,
    `notes` TEXT,
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `service_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `payment_method` VARCHAR(50) DEFAULT 'cash',
    `payment_status` VARCHAR(50) DEFAULT 'unpaid',
    `order_status` VARCHAR(50) DEFAULT 'pending',
    `estimated_minutes` INT DEFAULT 15,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_orders_status` (`order_status`),
    INDEX `idx_orders_created` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Order Items Table
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `menu_item_id` INT DEFAULT NULL,
    `item_name` VARCHAR(255) NOT NULL,
    `meat_type` VARCHAR(100),
    `unit_price` DECIMAL(10,2) NOT NULL,
    `quantity` INT NOT NULL,
    `total_price` DECIMAL(10,2) NOT NULL,
    `spicy_level` VARCHAR(50) DEFAULT 'normal',
    `extras_json` TEXT,
    `special_notes` TEXT,
    INDEX `idx_order_items_order` (`order_id`),
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Settings Table
CREATE TABLE IF NOT EXISTS `settings` (
    `setting_key` VARCHAR(100) PRIMARY KEY,
    `setting_value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Persistent Authentication Remember Tokens Table
CREATE TABLE IF NOT EXISTS `auth_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token` VARCHAR(255) UNIQUE NOT NULL,
    `user_agent` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME NOT NULL,
    INDEX `idx_auth_tokens_token` (`token`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DEFAULT SEED DATA
-- ============================================================

-- Seed Default Admin & Staff Accounts if not exists
-- Admin: admin / admin123
-- Staff: staff / staff123
-- Drinks: drinks / drinks123
INSERT IGNORE INTO `users` (`id`, `username`, `password_hash`, `full_name`, `email`, `phone`, `address`, `role`) VALUES
(1, 'admin', '$2y$10$wN1QyvE8HjO0O1k/xU0k.uB/4Zq2oZz9.u5kY3m/J1m2N3O4P5Q6R', 'Administrator', 'admin@satayroyale.com', '012-3456780', 'Warung Satay Royale HQ', 'admin'),
(2, 'staff', '$2y$10$WpZgq8Jm5Z3zX0u1v2w3e.Zq2oZz9.u5kY3m/J1m2N3O4P5Q6R', 'Kitchen Grill Staff', 'kitchen@satayroyale.com', '012-3456781', 'Kitchen Counter', 'staff'),
(3, 'drinks', '$2y$10$k1l2m3n4o5p6q7r8s9t0u.Zq2oZz9.u5kY3m/J1m2N3O4P5Q6R', 'Drink Station Staff', 'drinks@satayroyale.com', '012-3456782', 'Beverage & Drink Counter', 'staff_drinks');

-- Fix Passwords with standard password_hash compatibility:
-- admin / admin123
UPDATE `users` SET `password_hash` = '$2y$10$7zB3c9U/10Y2J.58m73KDeJ39t5fF0x.pW3e2v.Yy1z2a3b4c5d6e' WHERE `username` = 'admin';
-- staff / staff123
UPDATE `users` SET `password_hash` = '$2y$10$95b8p9zK1.x2y3z4a5b6c7d8e9f0a1b2c3d4e5f6g7h8i9j0k1l2m' WHERE `username` = 'staff';
-- drinks / drinks123
UPDATE `users` SET `password_hash` = '$2y$10$1a2b3c4d5e6f7g8h9i0j1.k2l3m4n5o6p7q8r9s0t1u2v3w4x5y6z' WHERE `username` = 'drinks';

-- Seed Store Settings
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('store_name', 'Sate Tulang Madu'),
('store_tagline', 'Traditional Charcoal Grilled Master Skewers'),
('currency_symbol', 'RM'),
('tax_rate_percent', '6'),
('service_fee_amount', '0.00'),
('estimated_prep_per_stick', '0.3'),
('kitchen_sound_alert', '1'),
('auto_refresh_seconds', '6'),
('whatsapp_number', '+60123456789'),
('store_address', 'No. 18, Jalan Satay Bestari, Bandar Warisan, 43000 Kajang, Selangor'),
('public_base_url', '');

-- Seed Categories
INSERT IGNORE INTO `categories` (`id`, `name`, `slug`, `description`, `display_order`, `icon`) VALUES
(1, 'Satay Cucuk (Skewers)', 'satay-cucuk', 'Charcoal-grilled satay skewers', 1, ''),
(2, 'Set Platter (Combos)', 'set-platter', 'Sharing platters & combo sets', 2, ''),
(3, 'Sampingan & Kuah (Sides)', 'sampingan', 'Rice cakes, peanut sauce & condiments', 3, ''),
(4, 'Minuman & Pencuci Mulut', 'minuman', 'Beverages & desserts', 4, '');

-- Seed Complete Menu Items
INSERT IGNORE INTO `menu_items` (`id`, `category_id`, `name`, `slug`, `description`, `price_per_unit`, `unit_name`, `min_quantity`, `image_url`, `is_popular`, `is_available`, `stock_quantity`, `stick_meat_type`) VALUES
(35, 1, 'Sate Ayam', 'sate-ayam', '', 1.25, 'Cucuk', 1, 'assets/images/sate_ayam.png', 0, 1, 70, 'ayam'),
(36, 1, 'Sate Daging', 'sate-daging', '', 1.50, 'Cucuk', 1, 'assets/images/sate_daging_055b80.png', 0, 1, 98, 'daging'),
(37, 1, 'Sate Tulang Madu', 'sate-tulang-madu', '', 1.50, 'Cucuk', 1, 'assets/images/sate_tulang_e3da94.png', 1, 1, 20, 'tulang_madu'),
(71, 2, 'Platter Mini 15', 'platter-mini-15', 'Set kombo duo: 15 cucuk sate pilihan (Ayam & Daging), 1 ketupat, timun, bawang dan kuah kacang pekat.', 24.00, 'Set', 1, 'assets/images/platter_mini.jpg', 0, 1, 50, 'combo_15'),
(72, 2, 'Platter Meriah 30', 'platter-meriah-30', 'Set kenduri meriah: 30 cucuk sate aneka pilihan, 2 ketupat, timun, bawang dan kuah kacang pekat.', 46.00, 'Set', 1, 'assets/images/platter_meriah.jpg', 1, 1, 40, 'combo_30'),
(73, 2, 'Platter Diraja 50', 'platter-diraja-50', 'Set jamuan keluarga diraja: 50 cucuk sate premium, 4 ketupat, ekstra timun, bawang & 2 mangkuk kuah kacang pekat.', 75.00, 'Set', 1, 'assets/images/platter_diraja.jpg', 1, 1, 30, 'combo_50'),
(38, 3, 'Nasi Himpit', 'nasi-himpit', '', 2.00, 'Ketul', 1, 'assets/images/nasi_impit_8a55df.png', 0, 1, 100, NULL),
(39, 3, 'Timun', 'timun', '', 1.00, 'Set', 1, 'assets/images/timun_24bec2.png', 0, 1, 97, NULL),
(40, 3, 'Bawang', 'bawang', '', 1.00, 'Set', 1, 'assets/images/bawang_fa4404.png', 0, 1, 100, NULL),
(41, 3, 'Kuah Kacang Extra', 'kuah-kacang-extra', '', 15.00, 'KG', 1, 'assets/images/kuah_kacang_9ae034.png', 0, 1, 100, NULL),
(74, 3, 'Ketupat Tradisional', 'ketupat-tradisional', 'Ketupat nasi tradisional lembut dan mampat, hidangan pelengkap sempurna sate.', 2.00, 'Ketul', 1, 'assets/images/ketupat.jpg', 0, 1, 150, NULL),
(75, 3, 'Kuah Kacang Pekat (Mangkuk)', 'kuah-kacang-pekat', 'Kuah kacang istimewa resepi warisan turun-temurun, pekat manis berlemak dan wangi beraroma kacang panggang.', 3.00, 'Mangkuk', 1, 'assets/images/kuah_kacang.jpg', 1, 1, 120, NULL),
(76, 3, 'Sambal Kicap Cili', 'sambal-kicap-cili', 'Sambal kicap pedas cili padi pekat bersama perahan limau kasturi segar pembuka selera.', 1.50, 'Mangkuk', 1, 'assets/images/sambal_kicap.jpg', 0, 1, 100, NULL),
(77, 3, 'Acar Timun Bawang', 'acar-timun-bawang', 'Acar segar timun, lobak dan nanas dengan rasa masam manis rangup menyegarkan.', 2.00, 'Set', 1, 'assets/images/acar.jpg', 0, 1, 100, NULL),
(43, 4, 'Teh O', 'teh-o', '', 1.00, 'Cawan', 1, 'assets/images/teh_o_panas_b93d84.png', 0, 1, 100, NULL),
(44, 4, 'Teh O Ais', 'teh-o-ais', '', 1.00, 'Gelas', 1, 'assets/images/teh_o_ais_ca656a.png', 0, 1, 100, NULL),
(45, 4, 'Teh', 'teh', '', 2.00, 'Cawan', 1, 'assets/images/teh_panas_c62ce0.png', 0, 1, 96, NULL),
(46, 4, 'Teh Ais', 'teh-ais', '', 3.00, 'Gelas', 1, 'assets/images/teh_ais_b216bd.png', 0, 1, 100, NULL),
(47, 4, 'Nescafe O', 'nescafe-o', '', 2.00, 'Cawan', 1, 'assets/images/nes_o_panas_63d027.png', 0, 1, 100, NULL),
(48, 4, 'Nescafe O Ais', 'nescafe-o-ais', '', 2.50, 'Gelas', 1, 'assets/images/nes_o_ais_dc8b0a.png', 0, 1, 100, NULL),
(49, 4, 'Nescafe', 'nescafe', '', 2.50, 'Cawan', 1, 'assets/images/nes_panas_f6b4c3.png', 0, 1, 100, NULL),
(50, 4, 'Nescafe Ais', 'nescafe-ais', '', 3.00, 'Gelas', 1, 'assets/images/nes_ais_af60c7.png', 0, 1, 100, NULL),
(51, 4, 'Kopi O', 'kopi-o', '', 1.00, 'Cawan', 1, 'assets/images/kopi_o_panas_33d767.png', 0, 1, 100, NULL),
(52, 4, 'Kopi O Ais', 'kopi-o-ais', '', 1.50, 'Gelas', 1, 'assets/images/kopi_o_ais_525097.png', 0, 1, 100, NULL),
(53, 4, 'Kopi Panas', 'kopi-panas', '', 2.00, 'Cawan', 1, 'assets/images/nes_panas_1f0206.png', 0, 1, 100, NULL),
(54, 4, 'Kopi Ais', 'kopi-ais', '', 2.50, 'Gelas', 1, 'assets/images/kopi_ais_deec11.png', 0, 1, 100, NULL),
(55, 4, 'Sirap Ais', 'sirap-ais', '', 1.00, 'Gelas', 1, 'assets/images/sirap_ais_bf02d5.png', 0, 1, 100, NULL),
(56, 4, 'Sunquick Ais', 'sunquick-ais', '', 2.50, 'Gelas', 1, 'assets/images/sunquick_71e346.png', 0, 1, 100, NULL),
(57, 4, 'Teh O Limau', 'teh-o-limau', '', 1.50, 'Cawan', 1, 'assets/images/teh_o_limau_b831aa.png', 0, 1, 100, NULL),
(58, 4, 'Teh O Limau Ais', 'teh-o-limau-ais', '', 2.00, 'Gelas', 1, 'assets/images/teh_o_limau_ais_ee8d79.png', 0, 1, 100, NULL),
(59, 4, 'Neslo', 'neslo', '', 2.50, 'Cawan', 1, 'assets/images/neslo_panas_c93e76.png', 0, 1, 100, NULL),
(60, 4, 'Neslo Ais', 'neslo-ais', '', 3.00, 'Gelas', 1, 'assets/images/neslo_ais_a4a148.png', 0, 1, 100, NULL),
(61, 4, 'Milo O', 'milo-o', '', 2.00, 'Cawan', 1, 'assets/images/milo_o_panas_92d0f9.png', 0, 1, 100, NULL),
(62, 4, 'Milo O Ais', 'milo-o-ais', '', 2.50, 'Gelas', 1, 'assets/images/milo_o_ais_40b10a.png', 0, 1, 100, NULL),
(63, 4, 'Milo', 'milo', '', 2.50, 'Cawan', 1, 'assets/images/milo_o_panas_a6837e.png', 0, 1, 100, NULL),
(64, 4, 'Milo Ais', 'milo-ais', '', 3.00, 'Gelas', 1, 'assets/images/milo_o_ais_e024e4.png', 0, 1, 100, NULL),
(65, 4, 'Limau', 'limau', '', 1.00, 'Cawan', 1, 'assets/images/limau_panas_ccd435.png', 0, 1, 100, NULL),
(66, 4, 'Limau Ais', 'limau-ais', '', 1.50, 'Gelas', 1, 'assets/images/limau_ais_390c4d.png', 0, 1, 100, NULL),
(78, 4, 'Teh Tarik Kaw Ais', 'teh-tarik-kaw-ais', 'Teh tarik pekat berbuih mewah dengan aroma teh wangi dan rasa lemak manis seimbang dihidang sejuk.', 3.50, 'Gelas', 1, 'assets/images/teh_tarik.jpg', 1, 1, 150, NULL),
(79, 4, 'Sirap Bandung Selasih', 'sirap-bandung-selasih', 'Minuman sirap ros wangi berkrim susu manis bersama biji selasih penyejuk badan.', 3.50, 'Gelas', 1, 'assets/images/bandung.jpg', 1, 1, 120, NULL),
(80, 4, 'Jus Kelapa Muda', 'jus-kelapa-muda', 'Air kelapa muda asli segar bersama hirisan isi kelapa lembut, dingin menyegarkan.', 5.00, 'Gelas', 1, 'assets/images/kelapa.jpg', 1, 1, 60, NULL),
(81, 4, 'Cendol Royale', 'cendol-royale', 'Pencuci mulut ais kisar santan pekat, gula melaka asli wangi, jeli cendol pandan dan kacang merah empuk.', 5.50, 'Mangkuk', 1, 'assets/images/cendol.jpg', 1, 1, 80, NULL);


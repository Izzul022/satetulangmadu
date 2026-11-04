<?php
/**
 * Satay Ordering System - Schema Initialization
 * Clean production schema without hardcoded demo menu items or mock orders.
 */

function init_database_schema(PDO $pdo) {
    $driver = strtolower($pdo->getAttribute(PDO::ATTR_DRIVER_NAME));

    if ($driver === 'mysql') {
        // MySQL DDL Schema
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) UNIQUE NOT NULL,
                description TEXT,
                display_order INT DEFAULT 0,
                icon VARCHAR(255),
                is_active TINYINT(1) DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS menu_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                category_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) UNIQUE,
                description TEXT,
                price_per_unit DECIMAL(10,2) NOT NULL,
                unit_name VARCHAR(50) DEFAULT 'stick',
                min_quantity INT DEFAULT 1,
                image_url TEXT,
                is_popular TINYINT(1) DEFAULT 0,
                is_available TINYINT(1) DEFAULT 1,
                stock_quantity INT DEFAULT 100,
                stick_meat_type VARCHAR(100) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS tables (
                id INT AUTO_INCREMENT PRIMARY KEY,
                table_number VARCHAR(50) UNIQUE NOT NULL,
                qr_token VARCHAR(255) UNIQUE NOT NULL,
                status VARCHAR(50) DEFAULT 'available',
                capacity INT DEFAULT 4,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                full_name VARCHAR(255) NOT NULL,
                email VARCHAR(255),
                phone VARCHAR(50),
                address TEXT,
                role VARCHAR(50) NOT NULL DEFAULT 'customer',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT DEFAULT NULL,
                is_guest TINYINT(1) DEFAULT 1,
                order_number VARCHAR(100) UNIQUE NOT NULL,
                customer_name VARCHAR(255) NOT NULL,
                customer_phone VARCHAR(50),
                dining_type VARCHAR(50) NOT NULL,
                table_number VARCHAR(50),
                delivery_address TEXT,
                notes TEXT,
                subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                service_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                payment_method VARCHAR(50) DEFAULT 'cash',
                payment_status VARCHAR(50) DEFAULT 'unpaid',
                order_status VARCHAR(50) DEFAULT 'pending',
                estimated_minutes INT DEFAULT 15,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_orders_status (order_status),
                INDEX idx_orders_created (created_at),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                menu_item_id INT DEFAULT NULL,
                item_name VARCHAR(255) NOT NULL,
                meat_type VARCHAR(100),
                unit_price DECIMAL(10,2) NOT NULL,
                quantity INT NOT NULL,
                total_price DECIMAL(10,2) NOT NULL,
                spicy_level VARCHAR(50) DEFAULT 'normal',
                extras_json TEXT,
                special_notes TEXT,
                INDEX idx_order_items_order (order_id),
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS settings (
                setting_key VARCHAR(100) PRIMARY KEY,
                setting_value TEXT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS auth_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(255) UNIQUE NOT NULL,
                user_agent TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NOT NULL,
                INDEX idx_auth_tokens_token (token),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } else {
        // SQLite DDL Schema
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT UNIQUE NOT NULL,
                description TEXT,
                display_order INTEGER DEFAULT 0,
                icon TEXT,
                is_active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS menu_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                slug TEXT UNIQUE,
                description TEXT,
                price_per_unit REAL NOT NULL,
                unit_name TEXT DEFAULT 'stick',
                min_quantity INTEGER DEFAULT 1,
                image_url TEXT,
                is_popular INTEGER DEFAULT 0,
                is_available INTEGER DEFAULT 1,
                stock_quantity INTEGER DEFAULT 100,
                stick_meat_type TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS tables (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                table_number TEXT UNIQUE NOT NULL,
                qr_token TEXT UNIQUE NOT NULL,
                status TEXT DEFAULT 'available',
                capacity INTEGER DEFAULT 4,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER DEFAULT NULL,
                is_guest INTEGER DEFAULT 1,
                order_number TEXT UNIQUE NOT NULL,
                customer_name TEXT NOT NULL,
                customer_phone TEXT,
                dining_type TEXT NOT NULL,
                table_number TEXT,
                delivery_address TEXT,
                notes TEXT,
                subtotal REAL NOT NULL DEFAULT 0.00,
                tax_amount REAL NOT NULL DEFAULT 0.00,
                service_fee REAL NOT NULL DEFAULT 0.00,
                discount_amount REAL NOT NULL DEFAULT 0.00,
                total_amount REAL NOT NULL DEFAULT 0.00,
                payment_method TEXT DEFAULT 'cash',
                payment_status TEXT DEFAULT 'unpaid',
                order_status TEXT DEFAULT 'pending',
                estimated_minutes INTEGER DEFAULT 15,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS order_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                menu_item_id INTEGER,
                item_name TEXT NOT NULL,
                meat_type TEXT,
                unit_price REAL NOT NULL,
                quantity INTEGER NOT NULL,
                total_price REAL NOT NULL,
                spicy_level TEXT DEFAULT 'normal',
                extras_json TEXT,
                special_notes TEXT,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE SET NULL
            );

            CREATE TABLE IF NOT EXISTS settings (
                setting_key TEXT PRIMARY KEY,
                setting_value TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                full_name TEXT NOT NULL,
                email TEXT,
                phone TEXT,
                address TEXT,
                role TEXT NOT NULL DEFAULT 'customer',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS auth_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                token TEXT UNIQUE NOT NULL,
                user_agent TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(order_status);
            CREATE INDEX IF NOT EXISTS idx_orders_created ON orders(created_at);
            CREATE INDEX IF NOT EXISTS idx_order_items_order ON order_items(order_id);
            CREATE INDEX IF NOT EXISTS idx_auth_tokens_token ON auth_tokens(token);
        ");
    }

    // Run safe migrations for existing databases
    migrate_database_schema($pdo);

    // 2. Seed Default Administrator & Staff Accounts (if none exist)
    $admin_count = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if ($admin_count === 0) {
        $user_stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $user_stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'Administrator', 'admin@satayroyale.com', '012-3456780', 'Warung Satay Royale HQ', 'admin']);
    }

    $staff_count = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'staff'")->fetchColumn();
    if ($staff_count === 0) {
        $user_stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $user_stmt->execute(['staff', password_hash('staff123', PASSWORD_DEFAULT), 'Kitchen Grill Staff', 'kitchen@satayroyale.com', '012-3456781', 'Kitchen Counter', 'staff']);
    }

    $drinks_count = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE username = 'drinks' OR role = 'staff_drinks'")->fetchColumn();
    if ($drinks_count === 0) {
        $user_stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $user_stmt->execute(['drinks', password_hash('drinks123', PASSWORD_DEFAULT), 'Drink Station Staff', 'drinks@satayroyale.com', '012-3456782', 'Beverage & Drink Counter', 'staff_drinks']);
    }

    // 3. Seed Default Store Settings (if empty)
    $settings_count = (int)$pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();
    if ($settings_count === 0) {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $settings = [
            ['store_name', 'Sate Tulang Madu'],
            ['store_tagline', 'Traditional Charcoal Grilled Master Skewers'],
            ['currency_symbol', 'RM'],
            ['tax_rate_percent', '6'],
            ['service_fee_amount', '0.00'],
            ['estimated_prep_per_stick', '0.3'],
            ['kitchen_sound_alert', '1'],
            ['auto_refresh_seconds', '6'],
            ['whatsapp_number', '+60123456789'],
            ['store_address', 'No. 18, Jalan Satay Bestari, Bandar Warisan, 43000 Kajang, Selangor'],
            ['public_base_url', '']
        ];
        foreach ($settings as $setting) {
            $stmt->execute($setting);
        }
    }

    // 4. Seed Standard Menu Categories (if empty)
    $cat_count = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ($cat_count === 0) {
        $pdo->exec("
            INSERT INTO categories (id, name, slug, description, display_order, icon) VALUES
            (1, 'Satay Cucuk (Skewers)', 'satay-cucuk', 'Charcoal-grilled satay skewers', 1, ''),
            (2, 'Set Platter (Combos)', 'set-platter', 'Sharing platters & combo sets', 2, ''),
            (3, 'Sampingan & Kuah (Sides)', 'sampingan', 'Rice cakes, peanut sauce & condiments', 3, ''),
            (4, 'Minuman & Pencuci Mulut', 'minuman', 'Beverages & desserts', 4, '');
        ");
    }

    // 5. Seed Standard Menu Catalog
    try {
        sync_default_menu_catalog($pdo);
    } catch (Exception $e) {}
}

function migrate_database_schema(PDO $pdo) {
    $driver = strtolower($pdo->getAttribute(PDO::ATTR_DRIVER_NAME));

    // 1. Check users table columns
    $user_cols = [];
    try {
        if ($driver === 'mysql') {
            $cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll();
            $user_cols = array_column($cols, 'Field');
        } else {
            $cols = $pdo->query("PRAGMA table_info(users)")->fetchAll();
            $user_cols = array_column($cols, 'name');
        }
    } catch (Exception $e) {}

    if (!empty($user_cols)) {
        if (!in_array('email', $user_cols)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN email TEXT;");
        }
        if (!in_array('phone', $user_cols)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN phone TEXT;");
        }
        if (!in_array('address', $user_cols)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN address TEXT;");
        }
    }

    // 2. Check orders table columns
    $order_cols = [];
    try {
        if ($driver === 'mysql') {
            $cols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll();
            $order_cols = array_column($cols, 'Field');
        } else {
            $cols = $pdo->query("PRAGMA table_info(orders)")->fetchAll();
            $order_cols = array_column($cols, 'name');
        }
    } catch (Exception $e) {}

    if (!empty($order_cols)) {
        if (!in_array('user_id', $order_cols)) {
            $type = ($driver === 'mysql') ? "INT DEFAULT NULL" : "INTEGER DEFAULT NULL";
            $pdo->exec("ALTER TABLE orders ADD COLUMN user_id {$type};");
        }
        if (!in_array('is_guest', $order_cols)) {
            $type = ($driver === 'mysql') ? "TINYINT(1) DEFAULT 1" : "INTEGER DEFAULT 1";
            $pdo->exec("ALTER TABLE orders ADD COLUMN is_guest {$type};");
        }
    }

    // 3. Check menu_items table columns
    $menu_cols = [];
    try {
        if ($driver === 'mysql') {
            $cols = $pdo->query("SHOW COLUMNS FROM menu_items")->fetchAll();
            $menu_cols = array_column($cols, 'Field');
        } else {
            $cols = $pdo->query("PRAGMA table_info(menu_items)")->fetchAll();
            $menu_cols = array_column($cols, 'name');
        }
    } catch (Exception $e) {}

    if (!empty($menu_cols)) {
        if (!in_array('stock_quantity', $menu_cols)) {
            $type = ($driver === 'mysql') ? "INT DEFAULT 100" : "INTEGER DEFAULT 100";
            $pdo->exec("ALTER TABLE menu_items ADD COLUMN stock_quantity {$type};");
        }
    }

    // 4. Ensure default staff users exist for existing databases
    try {
        $staff_count = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'staff'")->fetchColumn();
        if ($staff_count === 0) {
            $user_stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $user_stmt->execute(['staff', password_hash('staff123', PASSWORD_DEFAULT), 'Kitchen Grill Staff', 'kitchen@satayroyale.com', '012-3456781', 'Kitchen Counter', 'staff']);
        }

        $drinks_count = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE username = 'drinks' OR role = 'staff_drinks'")->fetchColumn();
        if ($drinks_count === 0) {
            $user_stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $user_stmt->execute(['drinks', password_hash('drinks123', PASSWORD_DEFAULT), 'Drink Station Staff', 'drinks@satayroyale.com', '012-3456782', 'Beverage & Drink Counter', 'staff_drinks']);
        }
    } catch (Exception $e) {}


    // 6. Ensure auth_tokens table exists for persistent forever login sessions
    try {
        if ($driver === 'mysql') {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS auth_tokens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token VARCHAR(255) UNIQUE NOT NULL,
                    user_agent TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    expires_at DATETIME NOT NULL,
                    INDEX idx_auth_tokens_token (token),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } else {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS auth_tokens (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    token TEXT UNIQUE NOT NULL,
                    user_agent TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    expires_at DATETIME NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                );
                CREATE INDEX IF NOT EXISTS idx_auth_tokens_token ON auth_tokens(token);
            ");
        }
    } catch (Exception $e) {}

    // 7. Auto-synchronize full menu items catalog if missing in online persistent databases (e.g. Render)
    try {
        sync_default_menu_catalog($pdo);
    } catch (Exception $e) {}
}

function sync_default_menu_catalog(PDO $pdo, bool $force = false) {
    if (!$force) {
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'menu_catalog_synced_v2'");
            $stmt->execute();
            if ($stmt->fetchColumn() === '1') {
                return;
            }
        } catch (Exception $e) {}
    }

    $catalog = [
        ['category_id' => 1, 'name' => 'Sate Ayam', 'slug' => 'sate-ayam', 'description' => 'Sate ayam perap rempah madu dibakar arang.', 'price_per_unit' => 1.25, 'unit_name' => 'Cucuk', 'min_quantity' => 1, 'image_url' => 'assets/images/sate_ayam.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => 'ayam'],
        ['category_id' => 1, 'name' => 'Sate Daging', 'slug' => 'sate-daging', 'description' => 'Sate daging lembu empuk beraroma.', 'price_per_unit' => 1.50, 'unit_name' => 'Cucuk', 'min_quantity' => 1, 'image_url' => 'assets/images/sate_daging_055b80.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => 'daging'],
        ['category_id' => 1, 'name' => 'Sate Tulang Madu', 'slug' => 'sate-tulang-madu', 'description' => 'Sate tulang madu istimewa kegemaran ramai.', 'price_per_unit' => 1.50, 'unit_name' => 'Cucuk', 'min_quantity' => 1, 'image_url' => 'assets/images/sate_tulang_e3da94.png', 'is_popular' => 1, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => 'tulang_madu'],
        ['category_id' => 2, 'name' => 'Platter Mini 15', 'slug' => 'platter-mini-15', 'description' => 'Set kombo duo: 15 cucuk sate pilihan (Ayam & Daging), 1 ketupat, timun, bawang dan kuah kacang pekat.', 'price_per_unit' => 24.00, 'unit_name' => 'Set', 'min_quantity' => 1, 'image_url' => 'assets/images/platter_mini.jpg', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 50, 'stick_meat_type' => 'combo_15'],
        ['category_id' => 2, 'name' => 'Platter Meriah 30', 'slug' => 'platter-meriah-30', 'description' => 'Set kenduri meriah: 30 cucuk sate aneka pilihan, 2 ketupat, timun, bawang dan kuah kacang pekat.', 'price_per_unit' => 46.00, 'unit_name' => 'Set', 'min_quantity' => 1, 'image_url' => 'assets/images/platter_meriah.jpg', 'is_popular' => 1, 'is_available' => 1, 'stock_quantity' => 40, 'stick_meat_type' => 'combo_30'],
        ['category_id' => 2, 'name' => 'Platter Diraja 50', 'slug' => 'platter-diraja-50', 'description' => 'Set jamuan keluarga diraja: 50 cucuk sate premium, 4 ketupat, ekstra timun, bawang & 2 mangkuk kuah kacang pekat.', 'price_per_unit' => 75.00, 'unit_name' => 'Set', 'min_quantity' => 1, 'image_url' => 'assets/images/platter_diraja.jpg', 'is_popular' => 1, 'is_available' => 1, 'stock_quantity' => 30, 'stick_meat_type' => 'combo_50'],
        ['category_id' => 3, 'name' => 'Nasi Himpit', 'slug' => 'nasi-himpit', 'description' => 'Nasi himpit lembut dan kenyal.', 'price_per_unit' => 2.00, 'unit_name' => 'Ketul', 'min_quantity' => 1, 'image_url' => 'assets/images/nasi_impit_8a55df.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 3, 'name' => 'Timun', 'slug' => 'timun', 'description' => 'Hirisan timun segar pelengkap sate.', 'price_per_unit' => 1.00, 'unit_name' => 'Set', 'min_quantity' => 1, 'image_url' => 'assets/images/timun_24bec2.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 3, 'name' => 'Bawang', 'slug' => 'bawang', 'description' => 'Bawang merah manis segar.', 'price_per_unit' => 1.00, 'unit_name' => 'Set', 'min_quantity' => 1, 'image_url' => 'assets/images/bawang_fa4404.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 3, 'name' => 'Kuah Kacang Extra', 'slug' => 'kuah-kacang-extra', 'description' => 'Kuah kacang pekat bersaiz 1KG untuk jamuan.', 'price_per_unit' => 15.00, 'unit_name' => 'KG', 'min_quantity' => 1, 'image_url' => 'assets/images/kuah_kacang_9ae034.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 3, 'name' => 'Ketupat Tradisional', 'slug' => 'ketupat-tradisional', 'description' => 'Ketupat nasi tradisional lembut dan mampat, hidangan pelengkap sempurna sate.', 'price_per_unit' => 2.00, 'unit_name' => 'Ketul', 'min_quantity' => 1, 'image_url' => 'assets/images/ketupat.jpg', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 150, 'stick_meat_type' => null],
        ['category_id' => 3, 'name' => 'Kuah Kacang Pekat (Mangkuk)', 'slug' => 'kuah-kacang-pekat', 'description' => 'Kuah kacang istimewa resepi warisan turun-temurun, pekat manis berlemak dan wangi beraroma kacang panggang.', 'price_per_unit' => 3.00, 'unit_name' => 'Mangkuk', 'min_quantity' => 1, 'image_url' => 'assets/images/kuah_kacang.jpg', 'is_popular' => 1, 'is_available' => 1, 'stock_quantity' => 120, 'stick_meat_type' => null],
        ['category_id' => 3, 'name' => 'Sambal Kicap Cili', 'slug' => 'sambal-kicap-cili', 'description' => 'Sambal kicap pedas cili padi pekat bersama perahan limau kasturi segar pembuka selera.', 'price_per_unit' => 1.50, 'unit_name' => 'Mangkuk', 'min_quantity' => 1, 'image_url' => 'assets/images/sambal_kicap.jpg', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 3, 'name' => 'Acar Timun Bawang', 'slug' => 'acar-timun-bawang', 'description' => 'Acar segar timun, lobak dan nanas dengan rasa masam manis rangup menyegarkan.', 'price_per_unit' => 2.00, 'unit_name' => 'Set', 'min_quantity' => 1, 'image_url' => 'assets/images/acar.jpg', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Teh O', 'slug' => 'teh-o', 'description' => '', 'price_per_unit' => 1.00, 'unit_name' => 'Cawan', 'min_quantity' => 1, 'image_url' => 'assets/images/teh_o_panas_b93d84.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Teh O Ais', 'slug' => 'teh-o-ais', 'description' => '', 'price_per_unit' => 1.00, 'unit_name' => 'Gelas', 'min_quantity' => 1, 'image_url' => 'assets/images/teh_o_ais_ca656a.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Teh', 'slug' => 'teh', 'description' => '', 'price_per_unit' => 2.00, 'unit_name' => 'Cawan', 'min_quantity' => 1, 'image_url' => 'assets/images/teh_panas_c62ce0.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Teh Ais', 'slug' => 'teh-ais', 'description' => '', 'price_per_unit' => 3.00, 'unit_name' => 'Gelas', 'min_quantity' => 1, 'image_url' => 'assets/images/teh_ais_b216bd.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Nescafe O', 'slug' => 'nescafe-o', 'description' => '', 'price_per_unit' => 2.00, 'unit_name' => 'Cawan', 'min_quantity' => 1, 'image_url' => 'assets/images/nes_o_panas_63d027.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Nescafe O Ais', 'slug' => 'nescafe-o-ais', 'description' => '', 'price_per_unit' => 2.50, 'unit_name' => 'Gelas', 'min_quantity' => 1, 'image_url' => 'assets/images/nes_o_ais_dc8b0a.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Nescafe', 'slug' => 'nescafe', 'description' => '', 'price_per_unit' => 2.50, 'unit_name' => 'Cawan', 'min_quantity' => 1, 'image_url' => 'assets/images/nes_panas_f6b4c3.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Nescafe Ais', 'slug' => 'nescafe-ais', 'description' => '', 'price_per_unit' => 3.00, 'unit_name' => 'Gelas', 'min_quantity' => 1, 'image_url' => 'assets/images/nes_ais_af60c7.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Kopi O', 'slug' => 'kopi-o', 'description' => '', 'price_per_unit' => 1.00, 'unit_name' => 'Cawan', 'min_quantity' => 1, 'image_url' => 'assets/images/kopi_o_panas_33d767.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Kopi O Ais', 'slug' => 'kopi-o-ais', 'description' => '', 'price_per_unit' => 1.50, 'unit_name' => 'Gelas', 'min_quantity' => 1, 'image_url' => 'assets/images/kopi_o_ais_525097.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Kopi Panas', 'slug' => 'kopi-panas', 'description' => '', 'price_per_unit' => 2.00, 'unit_name' => 'Cawan', 'min_quantity' => 1, 'image_url' => 'assets/images/nes_panas_1f0206.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Kopi Ais', 'slug' => 'kopi-ais', 'description' => '', 'price_per_unit' => 2.50, 'unit_name' => 'Gelas', 'min_quantity' => 1, 'image_url' => 'assets/images/kopi_ais_deec11.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Sirap Ais', 'slug' => 'sirap-ais', 'description' => '', 'price_per_unit' => 1.00, 'unit_name' => 'Gelas', 'min_quantity' => 1, 'image_url' => 'assets/images/sirap_ais_bf02d5.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Sunquick Ais', 'slug' => 'sunquick-ais', 'description' => '', 'price_per_unit' => 2.50, 'unit_name' => 'Gelas', 'min_quantity' => 1, 'image_url' => 'assets/images/sunquick_71e346.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Teh O Limau', 'slug' => 'teh-o-limau', 'description' => '', 'price_per_unit' => 1.50, 'unit_name' => 'Cawan', 'min_quantity' => 1, 'image_url' => 'assets/images/teh_o_limau_b831aa.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Teh O Limau Ais', 'slug' => 'teh-o-limau-ais', 'description' => '', 'price_per_unit' => 2.00, 'unit_name' => 'Gelas', 'min_quantity' => 1, 'image_url' => 'assets/images/teh_o_limau_ais_ee8d79.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Neslo', 'slug' => 'neslo', 'description' => '', 'price_per_unit' => 2.50, 'unit_name' => 'Cawan', 'min_quantity' => 1, 'image_url' => 'assets/images/neslo_panas_c93e76.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Neslo Ais', 'slug' => 'neslo-ais', 'description' => '', 'price_per_unit' => 3.00, 'unit_name' => 'Gelas', 'min_quantity' => 1, 'image_url' => 'assets/images/neslo_ais_a4a148.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Milo O', 'slug' => 'milo-o', 'description' => '', 'price_per_unit' => 2.00, 'unit_name' => 'Cawan', 'min_quantity' => 1, 'image_url' => 'assets/images/milo_o_panas_92d0f9.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Milo O Ais', 'slug' => 'milo-o-ais', 'description' => '', 'price_per_unit' => 2.50, 'unit_name' => 'Gelas', 'min_quantity' => 1, 'image_url' => 'assets/images/milo_o_ais_40b10a.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Milo', 'slug' => 'milo', 'description' => '', 'price_per_unit' => 2.50, 'unit_name' => 'Cawan', 'min_quantity' => 1, 'image_url' => 'assets/images/milo_o_panas_a6837e.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Milo Ais', 'slug' => 'milo-ais', 'description' => '', 'price_per_unit' => 3.00, 'unit_name' => 'Gelas', 'min_quantity' => 1, 'image_url' => 'assets/images/milo_o_ais_e024e4.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Limau', 'slug' => 'limau', 'description' => '', 'price_per_unit' => 1.00, 'unit_name' => 'Cawan', 'min_quantity' => 1, 'image_url' => 'assets/images/limau_panas_ccd435.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Limau Ais', 'slug' => 'limau-ais', 'description' => '', 'price_per_unit' => 1.50, 'unit_name' => 'Gelas', 'min_quantity' => 1, 'image_url' => 'assets/images/limau_ais_390c4d.png', 'is_popular' => 0, 'is_available' => 1, 'stock_quantity' => 100, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Teh Tarik Kaw Ais', 'slug' => 'teh-tarik-kaw-ais', 'description' => 'Teh tarik pekat berbuih mewah dengan aroma teh wangi dan rasa lemak manis seimbang dihidang sejuk.', 'price_per_unit' => 3.50, 'unit_name' => 'Gelas', 'min_quantity' => 1, 'image_url' => 'assets/images/teh_tarik.jpg', 'is_popular' => 1, 'is_available' => 1, 'stock_quantity' => 150, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Sirap Bandung Selasih', 'slug' => 'sirap-bandung-selasih', 'description' => 'Minuman sirap ros wangi berkrim susu manis bersama biji selasih penyejuk badan.', 'price_per_unit' => 3.50, 'unit_name' => 'Gelas', 'min_quantity' => 1, 'image_url' => 'assets/images/bandung.jpg', 'is_popular' => 1, 'is_available' => 1, 'stock_quantity' => 120, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Jus Kelapa Muda', 'slug' => 'jus-kelapa-muda', 'description' => 'Air kelapa muda asli segar bersama hirisan isi kelapa lembut, dingin menyegarkan.', 'price_per_unit' => 5.00, 'unit_name' => 'Gelas', 'min_quantity' => 1, 'image_url' => 'assets/images/kelapa.jpg', 'is_popular' => 1, 'is_available' => 1, 'stock_quantity' => 60, 'stick_meat_type' => null],
        ['category_id' => 4, 'name' => 'Cendol Royale', 'slug' => 'cendol-royale', 'description' => 'Pencuci mulut ais kisar santan pekat, gula melaka asli wangi, jeli cendol pandan dan kacang merah empuk.', 'price_per_unit' => 5.50, 'unit_name' => 'Mangkuk', 'min_quantity' => 1, 'image_url' => 'assets/images/cendol.jpg', 'is_popular' => 1, 'is_available' => 1, 'stock_quantity' => 80, 'stick_meat_type' => null]
    ];

    $check_stmt = $pdo->prepare("SELECT id FROM menu_items WHERE slug = ? OR name = ? LIMIT 1");
    $insert_stmt = $pdo->prepare("
        INSERT INTO menu_items (category_id, name, slug, description, price_per_unit, unit_name, min_quantity, image_url, is_popular, is_available, stock_quantity, stick_meat_type)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($catalog as $item) {
        $check_stmt->execute([$item['slug'], $item['name']]);
        if (!$check_stmt->fetch()) {
            $insert_stmt->execute([
                $item['category_id'],
                $item['name'],
                $item['slug'],
                $item['description'],
                $item['price_per_unit'],
                $item['unit_name'],
                $item['min_quantity'],
                $item['image_url'],
                $item['is_popular'],
                $item['is_available'],
                $item['stock_quantity'],
                $item['stick_meat_type']
            ]);
        }
    }

    try {
        $set_stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('menu_catalog_synced_v2', '1')");
        $set_stmt->execute();
    } catch (Exception $e) {}
}


<?php
/**
 * Satay Ordering System - Orders API (Placement, KDS Live Feed, Status Updates)
 */

require_once __DIR__ . '/config.php';

$pdo = get_db_connection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handle_get_orders($pdo);
        break;
    case 'POST':
        handle_create_order($pdo);
        break;
    case 'PATCH':
    case 'PUT':
        handle_update_order_status($pdo);
        break;
    default:
        json_error('Method not allowed', 405);
}

function handle_get_orders(PDO $pdo) {
    // 0. Customer My Orders history endpoint
    if (isset($_GET['my_orders']) && $_GET['my_orders'] == '1') {
        $user = get_current_auth_user();
        if (!$user) {
            json_error('Please log in to view your order history.', 401);
        }

        $stmt = $pdo->prepare("
            SELECT * FROM orders 
            WHERE user_id = ? OR (customer_phone IS NOT NULL AND customer_phone != '' AND customer_phone = ?)
            ORDER BY created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$user['id'], $user['phone'] ?? '']);
        $orders = $stmt->fetchAll();

        // Attach items to each order
        $order_ids = array_column($orders, 'id');
        $items_by_order = [];
        if (!empty($order_ids)) {
            $in_clause = implode(',', array_fill(0, count($order_ids), '?'));
            $item_stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id IN ($in_clause)");
            $item_stmt->execute($order_ids);
            $all_items = $item_stmt->fetchAll();

            foreach ($all_items as $item) {
                $items_by_order[$item['order_id']][] = $item;
            }
        }

        foreach ($orders as &$ord) {
            $ord['items'] = $items_by_order[$ord['id']] ?? [];
            $progress = 0;
            switch ($ord['order_status']) {
                case 'pending': $progress = 0; break;
                case 'confirmed': $progress = 25; break;
                case 'grilling': $progress = 50; break;
                case 'ready': $progress = 75; break;
                case 'completed': $progress = 100; break;
                case 'cancelled': $progress = 0; break;
            }
            $ord['progress_percent'] = $progress;
        }
        unset($ord);

        json_response([
            'success' => true,
            'count' => count($orders),
            'orders' => $orders
        ]);
    }

    // 0b. Guest multiple orders lookup by comma-separated numbers (e.g. STY-20260815-001,STY-20260815-002)
    if (isset($_GET['order_numbers'])) {
        $raw_numbers = explode(',', trim($_GET['order_numbers']));
        $numbers = array_values(array_filter(array_map('trim', $raw_numbers)));

        if (empty($numbers)) {
            json_response(['success' => true, 'orders' => []]);
        }

        $in_clause = implode(',', array_fill(0, count($numbers), '?'));
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number IN ($in_clause) ORDER BY created_at DESC");
        $stmt->execute($numbers);
        $orders = $stmt->fetchAll();

        // Attach items
        $order_ids = array_column($orders, 'id');
        $items_by_order = [];
        if (!empty($order_ids)) {
            $in_items = implode(',', array_fill(0, count($order_ids), '?'));
            $item_stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id IN ($in_items)");
            $item_stmt->execute($order_ids);
            $all_items = $item_stmt->fetchAll();
            foreach ($all_items as $item) {
                $items_by_order[$item['order_id']][] = $item;
            }
        }

        foreach ($orders as &$ord) {
            $ord['items'] = $items_by_order[$ord['id']] ?? [];
            $progress = 0;
            switch ($ord['order_status']) {
                case 'pending': $progress = 0; break;
                case 'confirmed': $progress = 25; break;
                case 'grilling': $progress = 50; break;
                case 'ready': $progress = 75; break;
                case 'completed': $progress = 100; break;
                case 'cancelled': $progress = 0; break;
            }
            $ord['progress_percent'] = $progress;
        }
        unset($ord);

        json_response(['success' => true, 'orders' => $orders]);
    }

    // 1. Fetch single order by ID or Order Number
    if (isset($_GET['id']) || isset($_GET['order_number'])) {
        $order = null;
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([(int)$_GET['id']]);
            $order = $stmt->fetch();
        } else {
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
            $stmt->execute([trim($_GET['order_number'])]);
            $order = $stmt->fetch();
        }

        if (!$order) {
            json_error('Order not found', 404);
        }

        // Fetch items
        $item_stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $item_stmt->execute([$order['id']]);
        $order['items'] = $item_stmt->fetchAll();

        // Calculate progress percentage for customer tracker (5 stages: 0%, 25%, 50%, 75%, 100%)
        $progress = 0;
        switch ($order['order_status']) {
            case 'pending': $progress = 0; break;
            case 'confirmed': $progress = 25; break;
            case 'grilling': $progress = 50; break;
            case 'ready': $progress = 75; break;
            case 'completed': $progress = 100; break;
            case 'cancelled': $progress = 0; break;
        }
        $order['progress_percent'] = $progress;

        json_response(['success' => true, 'order' => $order]);
    }

    // 2. Kitchen Live Feed (KDS)
    if (isset($_GET['live']) && $_GET['live'] == '1') {
        // Fetch all active orders
        $stmt = $pdo->query("
            SELECT * FROM orders 
            WHERE order_status IN ('pending', 'confirmed', 'grilling', 'ready')
            ORDER BY 
                CASE order_status
                    WHEN 'grilling' THEN 1
                    WHEN 'confirmed' THEN 2
                    WHEN 'pending' THEN 3
                    WHEN 'ready' THEN 4
                    ELSE 5
                END,
                created_at ASC
        ");
        $orders = $stmt->fetchAll();

        // Attach items to each order
        $order_ids = array_column($orders, 'id');
        $items_by_order = [];
        $grill_summary = [
            'total_sticks' => 0,
            'tulang_madu' => 0,
            'ayam' => 0,
            'daging' => 0,
            'other' => 0
        ];
        $drinks_summary = [
            'total_drinks' => 0,
            'breakdown' => []
        ];

        if (!empty($order_ids)) {
            $in_clause = implode(',', array_fill(0, count($order_ids), '?'));
            $item_stmt = $pdo->prepare("
                SELECT oi.*, m.category_id, c.slug as category_slug, c.name as category_name, m.unit_name
                FROM order_items oi
                LEFT JOIN menu_items m ON oi.menu_item_id = m.id
                LEFT JOIN categories c ON m.category_id = c.id
                WHERE oi.order_id IN ($in_clause)
            ");
            $item_stmt->execute($order_ids);
            $all_items = $item_stmt->fetchAll();

            foreach ($all_items as $item) {
                // Classify if item is beverage/drink
                $cat_slug = strtolower($item['category_slug'] ?? '');
                $name = strtolower($item['item_name'] ?? '');
                $unit = strtolower($item['unit_name'] ?? '');
                
                $is_drink = (
                    $cat_slug === 'minuman' || 
                    (int)($item['category_id'] ?? 0) === 4 ||
                    in_array($unit, ['cawan', 'gelas', 'cup', 'bottle', 'botol', 'can', 'tin']) ||
                    preg_match('/(teh|kopi|milo|sirap|jus|air|nescafe|horlicks|bandung|cincau|limau|ribena|extra joss|mineral|beverage|drink)/i', $name)
                );
                
                $item['is_drink'] = $is_drink;
                $items_by_order[$item['order_id']][] = $item;
            }
        }

        foreach ($orders as &$ord) {
            $ord_items = $items_by_order[$ord['id']] ?? [];
            $ord['items'] = $ord_items;
            
            $drink_items = [];
            $grill_items = [];
            $drink_text_parts = [];

            foreach ($ord_items as $it) {
                $qty = (int)$it['quantity'];
                $meat = strtolower($it['meat_type'] ?? '');
                $name = strtolower($it['item_name'] ?? '');
                $raw_name = $it['item_name'] ?? 'Item';

                if (!empty($it['is_drink'])) {
                    $drink_items[] = $it;
                    $drink_text_parts[] = "{$qty}x {$raw_name}";

                    if (in_array($ord['order_status'], ['pending', 'confirmed', 'grilling', 'preparing'])) {
                        $drinks_summary['total_drinks'] += $qty;
                        $drinks_summary['breakdown'][$raw_name] = ($drinks_summary['breakdown'][$raw_name] ?? 0) + $qty;
                    }
                } else {
                    $grill_items[] = $it;

                    // Aggregate sticks on the grill (for pending, confirmed, grilling orders)
                    if (in_array($ord['order_status'], ['pending', 'confirmed', 'grilling'])) {
                        if ($meat === 'tulang_madu' || strpos($name, 'tulang') !== false) {
                            $grill_summary['tulang_madu'] += $qty;
                            $grill_summary['total_sticks'] += $qty;
                        } elseif ($meat === 'ayam' || strpos($name, 'ayam') !== false) {
                            $grill_summary['ayam'] += $qty;
                            $grill_summary['total_sticks'] += $qty;
                        } elseif ($meat === 'daging' || strpos($name, 'daging') !== false || strpos($name, 'beef') !== false) {
                            $grill_summary['daging'] += $qty;
                            $grill_summary['total_sticks'] += $qty;
                        } elseif (!empty($meat) || strpos($name, 'satay') !== false || strpos($name, 'sate') !== false) {
                            $grill_summary['other'] += $qty;
                            $grill_summary['total_sticks'] += $qty;
                        }
                    }
                }
            }

            $ord['has_drinks'] = !empty($drink_items);
            $ord['has_grill'] = !empty($grill_items);
            $ord['drink_items'] = $drink_items;
            $ord['grill_items'] = $grill_items;
            $ord['drink_summary_text'] = implode(', ', $drink_text_parts);
        }
        unset($ord);

        json_response([
            'success' => true,
            'active_orders_count' => count($orders),
            'grill_summary' => $grill_summary,
            'drinks_summary' => $drinks_summary,
            'orders' => $orders,
            'server_time' => date('Y-m-d H:i:s')
        ]);
    }

    // 3. Admin / History / Filtered list
    $status = $_GET['status'] ?? 'all';
    $dining_type = $_GET['dining_type'] ?? 'all';
    $search = trim($_GET['search'] ?? '');
    $date_filter = trim($_GET['date'] ?? '');
    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 50;
    $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;

    $sql = "SELECT * FROM orders WHERE 1=1";
    $params = [];

    if (!empty($date_filter)) {
        $sql .= " AND DATE(created_at) = ?";
        $params[] = $date_filter;
    }

    if ($status !== 'all') {
        if ($status === 'active') {
            $sql .= " AND order_status IN ('pending', 'confirmed', 'grilling', 'ready')";
        } else {
            $sql .= " AND order_status = ?";
            $params[] = $status;
        }
    }

    if ($dining_type !== 'all') {
        $sql .= " AND dining_type = ?";
        $params[] = $dining_type;
    }

    if (!empty($search)) {
        $sql .= " AND (order_number LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ? OR table_number LIKE ?)";
        $term = "%$search%";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    // Fetch items for all retrieved orders
    $order_ids = array_column($orders, 'id');
    $items_by_order = [];
    if (!empty($order_ids)) {
        $in_clause = implode(',', array_fill(0, count($order_ids), '?'));
        $item_stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id IN ($in_clause)");
        $item_stmt->execute($order_ids);
        $all_items = $item_stmt->fetchAll();

        foreach ($all_items as $item) {
            $items_by_order[$item['order_id']][] = $item;
        }
    }

    foreach ($orders as &$ord) {
        $ord['items'] = $items_by_order[$ord['id']] ?? [];
    }

    json_response([
        'success' => true,
        'count' => count($orders),
        'orders' => $orders
    ]);
}

function handle_create_order(PDO $pdo) {
    $data = get_json_input();

    $customer_name = trim($data['customer_name'] ?? '');
    $customer_phone = trim($data['customer_phone'] ?? '');
    $dining_type = trim($data['dining_type'] ?? 'dine_in'); // dine_in, takeaway
    if (!in_array($dining_type, ['dine_in', 'takeaway'])) {
        $dining_type = 'dine_in';
    }
    $table_number = trim($data['table_number'] ?? '');
    $delivery_address = trim($data['delivery_address'] ?? '');
    $notes = trim($data['notes'] ?? '');
    $payment_method = trim($data['payment_method'] ?? 'cash'); // cash, qr_pay, card, online
    $items = $data['items'] ?? [];

    if (empty($customer_name)) {
        $customer_name = ($dining_type === 'dine_in' && !empty($table_number)) ? "Guest Table $table_number" : "Walk-in Guest";
    }

    if ($dining_type === 'dine_in' && empty($table_number)) {
        json_error('Please select or specify your Table Number for Dine-In.');
    }

    if (empty($items) || !is_array($items)) {
        json_error('Your order must contain at least one item.');
    }

    // Fetch tax settings
    $tax_percent = 6.0; // default 6%
    $tax_row = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'tax_rate_percent'")->fetch();
    if ($tax_row) {
        $tax_percent = (float)$tax_row['setting_value'];
    }

    // Start Transaction
    $pdo->beginTransaction();

    try {
        $subtotal = 0.0;
        $total_sticks = 0;
        $validated_items = [];

        // Validate and price each item against DB
        $item_lookup_stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");

        foreach ($items as $it) {
            $menu_id = isset($it['menu_item_id']) ? (int)$it['menu_item_id'] : null;
            $quantity = isset($it['quantity']) ? max(1, (int)$it['quantity']) : 1;
            $spicy_level = $it['spicy_level'] ?? 'normal';
            $special_notes = $it['special_notes'] ?? '';
            $extras_json = isset($it['extras_json']) ? (is_string($it['extras_json']) ? $it['extras_json'] : json_encode($it['extras_json'])) : null;

            if ($menu_id) {
                $item_lookup_stmt->execute([$menu_id]);
                $db_item = $item_lookup_stmt->fetch();

                if (!$db_item) {
                    throw new Exception("Menu item #{$menu_id} is not recognized.");
                }
                if (!$db_item['is_available'] || ($db_item['stock_quantity'] !== null && (int)$db_item['stock_quantity'] <= 0)) {
                    throw new Exception("Sorry, {$db_item['name']} is currently Sold Out.");
                }
                if ($db_item['stock_quantity'] !== null && (int)$db_item['stock_quantity'] < $quantity) {
                    $left = (int)$db_item['stock_quantity'];
                    throw new Exception("Sorry, {$db_item['name']} only has {$left} left in stock.");
                }

                // Minimum quantity check for skewers
                if ($db_item['min_quantity'] > 1 && $quantity < $db_item['min_quantity']) {
                    throw new Exception("{$db_item['name']} requires a minimum of {$db_item['min_quantity']} sticks.");
                }

                $item_name = $db_item['name'];
                $unit_price = (float)$db_item['price_per_unit'];
                $meat_type = $db_item['stick_meat_type'];
            } else {
                // Fallback for custom POS quick items
                $item_name = trim($it['item_name'] ?? 'Satay Item');
                $unit_price = max(0.1, (float)($it['unit_price'] ?? 1.50));
                $meat_type = $it['meat_type'] ?? null;
            }

            $line_total = $unit_price * $quantity;
            $subtotal += $line_total;

            if (!empty($meat_type)) {
                if (strpos($meat_type, 'combo_30') !== false) {
                    $total_sticks += 30 * $quantity;
                } elseif (strpos($meat_type, 'combo_50') !== false) {
                    $total_sticks += 50 * $quantity;
                } elseif (strpos($meat_type, 'combo_15') !== false) {
                    $total_sticks += 15 * $quantity;
                } else {
                    $total_sticks += $quantity;
                }
            }

            $validated_items[] = [
                'menu_item_id' => $menu_id,
                'item_name' => $item_name,
                'meat_type' => $meat_type,
                'unit_price' => $unit_price,
                'quantity' => $quantity,
                'total_price' => $line_total,
                'spicy_level' => $spicy_level,
                'extras_json' => $extras_json,
                'special_notes' => $special_notes
            ];
        }

        $tax_amount = round(($subtotal * $tax_percent) / 100, 2);
        $service_fee = 0.00;
        $discount_amount = 0.00;
        $total_amount = round($subtotal + $tax_amount + $service_fee - $discount_amount, 2);

        // Calculate dynamic ETA (e.g. base 10 mins + 0.25 min per stick)
        $estimated_minutes = max(12, min(45, (int)(10 + ($total_sticks * 0.25))));

        // Generate unique order number (e.g., STY-20260812-004)
        $today_prefix = 'STY-' . date('Ymd') . '-';
        $seq_stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE order_number LIKE ?");
        $seq_stmt->execute([$today_prefix . '%']);
        $seq_count = (int)$seq_stmt->fetchColumn() + 1;
        $order_number = $today_prefix . sprintf('%03d', $seq_count);

        // Determine user identity
        $auth_user = get_current_auth_user();
        $user_id = $auth_user ? (int)$auth_user['id'] : null;
        $is_guest = $auth_user ? 0 : 1;
        $new_registered_user = null;

        // Support optional inline registration during checkout
        if (!$auth_user && !empty($data['create_account']) && !empty($data['reg_password'])) {
            $reg_pass = trim($data['reg_password']);
            if (strlen($reg_pass) >= 4) {
                $reg_user = trim($data['reg_username'] ?? '');
                if (empty($reg_user)) {
                    $sanitized_name = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $customer_name));
                    $reg_user = !empty($sanitized_name) ? $sanitized_name . rand(100, 999) : 'user_' . substr(md5(uniqid()), 0, 6);
                }

                // Check if username already exists
                $chk_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $chk_stmt->execute([$reg_user]);
                if ($chk_stmt->fetch()) {
                    $reg_user = $reg_user . '_' . rand(10, 99);
                }

                $pass_hash = password_hash($reg_pass, PASSWORD_DEFAULT);
                $ins_user = $pdo->prepare("
                    INSERT INTO users (username, password_hash, full_name, phone, address, role)
                    VALUES (?, ?, ?, ?, ?, 'customer')
                ");
                $ins_user->execute([$reg_user, $pass_hash, $customer_name, $customer_phone, $delivery_address]);
                $user_id = (int)$pdo->lastInsertId();
                $is_guest = 0;

                // Log in new user with persistent session
                unset($_SESSION['is_guest'], $_SESSION['guest_name'], $_SESSION['guest_phone']);
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $reg_user;
                $_SESSION['full_name'] = $customer_name;
                $_SESSION['phone'] = $customer_phone;
                $_SESSION['address'] = $delivery_address;
                $_SESSION['role'] = 'customer';

                create_user_remember_token($pdo, $user_id);

                $new_registered_user = [
                    'id' => $user_id,
                    'username' => $reg_user,
                    'full_name' => $customer_name,
                    'phone' => $customer_phone,
                    'role' => 'customer'
                ];
            }
        }

        // Auto-mark as paid for QR pay/cash POS if passed
        $payment_status = (isset($data['payment_status']) && $data['payment_status'] === 'paid') ? 'paid' : ($payment_method === 'cash' ? 'unpaid' : 'paid');

        // Insert Order with exact local timestamp
        $now = date('Y-m-d H:i:s');
        $order_stmt = $pdo->prepare("
            INSERT INTO orders 
            (order_number, user_id, is_guest, customer_name, customer_phone, dining_type, table_number, delivery_address, notes, subtotal, tax_amount, service_fee, discount_amount, total_amount, payment_method, payment_status, order_status, estimated_minutes, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)
        ");

        $order_stmt->execute([
            $order_number,
            $user_id,
            $is_guest,
            $customer_name,
            $customer_phone,
            $dining_type,
            $table_number,
            $delivery_address,
            $notes,
            $subtotal,
            $tax_amount,
            $service_fee,
            $discount_amount,
            $total_amount,
            $payment_method,
            $payment_status,
            $estimated_minutes,
            $now,
            $now
        ]);

        $order_id = $pdo->lastInsertId();

        // Insert Order Items and Deduct Inventory Stock
        $item_insert_stmt = $pdo->prepare("
            INSERT INTO order_items 
            (order_id, menu_item_id, item_name, meat_type, unit_price, quantity, total_price, spicy_level, extras_json, special_notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $driver = strtolower($pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
        $stock_calc = ($driver === 'mysql') ? "GREATEST(0, stock_quantity - ?)" : "MAX(0, stock_quantity - ?)";

        $deduct_stock_stmt = $pdo->prepare("
            UPDATE menu_items 
            SET stock_quantity = CASE WHEN stock_quantity IS NOT NULL THEN {$stock_calc} ELSE NULL END,
                is_available = CASE WHEN stock_quantity IS NOT NULL AND (stock_quantity - ?) <= 0 THEN 0 ELSE is_available END
            WHERE id = ?
        ");

        foreach ($validated_items as $v_item) {
            $item_insert_stmt->execute([
                $order_id,
                $v_item['menu_item_id'],
                $v_item['item_name'],
                $v_item['meat_type'],
                $v_item['unit_price'],
                $v_item['quantity'],
                $v_item['total_price'],
                $v_item['spicy_level'],
                $v_item['extras_json'],
                $v_item['special_notes']
            ]);

            if ($v_item['menu_item_id']) {
                $deduct_stock_stmt->execute([
                    $v_item['quantity'],
                    $v_item['quantity'],
                    $v_item['menu_item_id']
                ]);
            }
        }

        $pdo->commit();

        $response_data = [
            'success' => true,
            'message' => 'Order successfully placed!',
            'order_id' => $order_id,
            'order_number' => $order_number,
            'total_amount' => $total_amount,
            'estimated_minutes' => $estimated_minutes,
            'dining_type' => $dining_type,
            'table_number' => $table_number
        ];
        if ($new_registered_user) {
            $response_data['user'] = $new_registered_user;
        }

        json_response($response_data, 201);

    } catch (Exception $e) {
        $pdo->rollBack();
        json_error($e->getMessage(), 400);
    }
}

function handle_update_order_status(PDO $pdo) {
    $data = get_json_input();

    $id = isset($data['id']) ? (int)$data['id'] : null;
    $order_number = trim($data['order_number'] ?? '');
    $new_status = trim($data['order_status'] ?? '');
    $payment_status = trim($data['payment_status'] ?? '');

    if (!$id && empty($order_number)) {
        json_error('Order ID or Order Number is required.');
    }

    $valid_statuses = ['pending', 'confirmed', 'grilling', 'ready', 'completed', 'cancelled'];
    if (!empty($new_status) && !in_array($new_status, $valid_statuses)) {
        json_error("Invalid order status: $new_status");
    }

    // Build update query
    $updates = [];
    $params = [];

    if (!empty($new_status)) {
        $updates[] = "order_status = ?";
        $params[] = $new_status;
    }

    if (!empty($payment_status)) {
        $updates[] = "payment_status = ?";
        $params[] = $payment_status;
    }

    if (empty($updates)) {
        json_error('No status changes provided.');
    }

    $updates[] = "updated_at = CURRENT_TIMESTAMP";

    $sql = "UPDATE orders SET " . implode(', ', $updates) . " WHERE " . ($id ? "id = ?" : "order_number = ?");
    $params[] = $id ? $id : $order_number;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    json_response([
        'success' => true,
        'message' => 'Order updated successfully',
        'order_status' => $new_status,
        'payment_status' => $payment_status
    ]);
}

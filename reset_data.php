<?php
/**
 * Sate Tulang Madu - One-Time Sales Data Reset
 * Clears all orders, order_items, and order_notifications only.
 * Menu, categories, settings, users and tables are preserved.
 *
 * SECURITY: Protected by a secret token. Delete this file after use.
 */

// ---- CHANGE THIS SECRET before uploading ----
define('RESET_SECRET', 'tulang-madu-reset-2024');

require_once __DIR__ . '/api/config.php';

header('Content-Type: text/html; charset=utf-8');

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

// Show form if no token
if (empty($token)) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sales Data Reset</title>
        <style>
            body { font-family: sans-serif; background: #faf8f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
            .box { background: #fff; border: 1px solid #e0d6cc; border-radius: 12px; padding: 2.5rem; max-width: 420px; width: 100%; box-shadow: 0 4px 16px rgba(0,0,0,0.07); }
            h2 { color: #8B4513; margin: 0 0 0.5rem; }
            p { color: #7a6b60; font-size: 0.88rem; margin: 0 0 1.5rem; }
            input { width: 100%; padding: 0.7rem 0.9rem; border: 1px solid #e0d6cc; border-radius: 8px; font-size: 1rem; box-sizing: border-box; margin-bottom: 1rem; }
            button { width: 100%; padding: 0.8rem; background: #b53d2e; color: #fff; border: none; border-radius: 8px; font-size: 1rem; font-weight: 700; cursor: pointer; }
            button:hover { background: #943225; }
        </style>
    </head>
    <body>
        <div class="box">
            <h2>⚠️ Sales Data Reset</h2>
            <p>This will permanently delete all <strong>orders, order items, and notifications</strong> from the database. Menu, users, tables and settings will <strong>not</strong> be affected.</p>
            <form method="post">
                <input type="password" name="token" placeholder="Enter reset secret token" required>
                <button type="submit">🗑️ Clear All Sales Data</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Wrong token
if ($token !== RESET_SECRET) {
    http_response_code(403);
    echo '<h2 style="color:red;font-family:sans-serif;padding:2rem;">❌ Invalid secret token. Access denied.</h2>';
    exit;
}

// Correct token — do the reset
try {
    $pdo = get_db_connection();
    $driver = strtolower($pdo->getAttribute(PDO::ATTR_DRIVER_NAME));

    $pdo->beginTransaction();

    $items    = $pdo->exec("DELETE FROM order_items");
    $notifs   = $pdo->exec("DELETE FROM order_notifications");
    $orders   = $pdo->exec("DELETE FROM orders");

    // Reset auto-increment sequences
    if ($driver === 'mysql') {
        $pdo->exec("ALTER TABLE orders AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE order_items AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE order_notifications AUTO_INCREMENT = 1");
    } else {
        // SQLite
        $pdo->exec("DELETE FROM sqlite_sequence WHERE name IN ('orders','order_items','order_notifications')");
    }

    // Reset table statuses to available
    $pdo->exec("UPDATE tables SET status = 'available'");

    // Restore menu item stock & availability
    $pdo->exec("UPDATE menu_items SET is_available = 1 WHERE is_available = 0");
    // Reset any stock that was depleted (below 30) back to 100
    $pdo->exec("UPDATE menu_items SET stock_quantity = 100 WHERE stock_quantity < 30");

    $pdo->commit();

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Reset Complete</title>
        <style>
            body { font-family: sans-serif; background: #faf8f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
            .box { background: #fff; border: 1px solid #e0d6cc; border-radius: 12px; padding: 2.5rem; max-width: 480px; width: 100%; box-shadow: 0 4px 16px rgba(0,0,0,0.07); }
            h2 { color: #2d7a4f; margin: 0 0 1rem; }
            ul { color: #2D1810; font-size: 0.92rem; line-height: 1.8; }
            a { display: inline-block; margin-top: 1.5rem; padding: 0.7rem 1.5rem; background: #8B4513; color: #fff; border-radius: 8px; text-decoration: none; font-weight: 700; }
            .warn { color: #b53d2e; font-size: 0.82rem; margin-top: 1rem; }
        </style>
    </head>
    <body>
        <div class="box">
            <h2>✅ Sales Data Cleared Successfully</h2>
            <ul>
                <li>🗑️ <strong><?= $orders ?></strong> orders deleted</li>
                <li>🗑️ <strong><?= $items ?></strong> order items deleted</li>
                <li>🗑️ <strong><?= $notifs ?></strong> notifications deleted</li>
                <li>✅ All tables reset to <em>available</em></li>
                <li>✅ Auto-increment sequences reset</li>
                <li>✅ Menu items availability restored</li>
            </ul>
            <p class="warn">⚠️ <strong>Please delete this file</strong> (<code>reset_data.php</code>) from your server now to prevent unauthorised access.</p>
            <a href="admin.php">→ Go to Admin Dashboard</a>
        </div>
    </body>
    </html>
    <?php

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo '<div style="font-family:sans-serif;padding:2rem;color:red;"><h2>❌ Reset failed</h2><p>' . htmlspecialchars($e->getMessage()) . '</p></div>';
}

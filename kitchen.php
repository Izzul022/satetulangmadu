<?php
require_once __DIR__ . '/api/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Strictly require staff, drink staff or admin authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'staff' && $_SESSION['role'] !== 'staff_drinks' && $_SESSION['role'] !== 'admin')) {
    header('Location: login.php?redirect=kitchen.php&error=auth_required');
    exit;
}

$currentUser = get_current_auth_user();
$isDrinksStaff = ($currentUser['role'] === 'staff_drinks' || strpos(strtolower($currentUser['username'] ?? ''), 'drink') !== false);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo $isDrinksStaff ? '🥤 Drink Station KDS' : 'Kitchen Display System & POS'; ?> - Sate Tulang Madu</title>
    <meta name="description" content="Dedicated Kitchen Display System (KDS), Drink Barista Station, and Walk-In Cashier POS for Sate Tulang Madu.">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22 fill=%22%238B4513%22>S</text></svg>">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#8B4513">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Satay KDS">
</head>
<body class="store-layout-active">

    <!-- ========== EXISTING MOBILE HAMBURGER DRAWER (kept for mobile/tablet) ========== -->
    <div class="app-sidenav-backdrop" id="app-sidenav-backdrop" onclick="SatayApp.closeSideNav()"></div>
    <aside class="app-sidenav" id="app-side-nav">
        <div class="sidenav-header">
            <div class="sidenav-brand">
                <div class="nav-logo-icon" style="width:34px; height:34px; font-size:0.9rem; background:<?php echo $isDrinksStaff ? 'var(--info)' : 'var(--gold)'; ?>;">
                    <?php echo $isDrinksStaff ? '🥤' : 'K'; ?>
                </div>
                <div>
                    <div class="sidenav-brand-title"><?php echo $isDrinksStaff ? 'DRINK STATION' : 'KITCHEN & POS'; ?></div>
                    <div class="sidenav-brand-sub">Sate Tulang Madu Ops</div>
                </div>
            </div>
            <button type="button" class="sidenav-close-btn" onclick="SatayApp.closeSideNav()" title="Close menu">&times;</button>
        </div>

        <div class="sidenav-user-card">
            <div class="sidenav-avatar" style="background:<?php echo $isDrinksStaff ? 'var(--info)' : 'var(--gold)'; ?>;">
                <?php echo $isDrinksStaff ? '🥤' : strtoupper(substr($currentUser['full_name'] ?? 'Staff', 0, 1)); ?>
            </div>
            <div class="sidenav-user-info">
                <div class="sidenav-user-name"><?php echo htmlspecialchars($currentUser['full_name'] ?? 'Kitchen Staff'); ?></div>
                <div class="sidenav-user-role"><?php echo $isDrinksStaff ? '🥤 Drink Section Barista' : ucfirst($currentUser['role'] ?? 'Staff') . ' Operations'; ?></div>
            </div>
        </div>

        <nav class="sidenav-body">
            <div class="sidenav-section-title">Station Views</div>
            <button type="button" class="sidenav-link client-tab-btn active" data-tab="kds" onclick="ClientApp.switchTab('kds'); SatayApp.closeSideNav()">
                <span class="sidenav-link-icon">🍳</span>
                <span>Live Orders Display (KDS)</span>
            </button>
            <button type="button" class="sidenav-link client-tab-btn" data-tab="pos" onclick="ClientApp.switchTab('pos'); SatayApp.closeSideNav()">
                <span class="sidenav-link-icon">💵</span>
                <span>Walk-In Cashier POS</span>
            </button>
            <button type="button" class="sidenav-link client-tab-btn" data-tab="stock" onclick="ClientApp.switchTab('stock'); SatayApp.closeSideNav()">
                <span class="sidenav-link-icon">📦</span>
                <span>Stock & Availability</span>
            </button>

            <div class="sidenav-section-title">Quick Actions</div>
            <button type="button" class="sidenav-link" onclick="ClientApp.enableAndTestNotification(); SatayApp.closeSideNav()">
                <span class="sidenav-link-icon">🔔</span>
                <span>Test Phone Ring & Vibration</span>
            </button>

            <?php if ($currentUser['role'] === 'admin'): ?>
                <div class="sidenav-section-title">Quick Portals</div>
                <a href="admin.php" class="sidenav-link">
                    <span class="sidenav-link-icon">👑</span>
                    <span>Admin Control Center</span>
                </a>
            <?php endif; ?>
        </nav>

        <div class="sidenav-footer">
            <a href="logout.php?redirect=login.php" class="btn btn-secondary btn-sm" style="color:var(--danger); border-color:rgba(181, 61, 46, 0.3); width:100%; justify-content:center;">
                Logout
            </a>
        </div>
    </aside>

    <!-- ========== DESKTOP PERSISTENT SIDEBAR (Microsoft Store Layout) ========== -->
    <aside class="store-sidebar" id="store-sidebar">
        <!-- Sidebar Brand Header & Toggle Button -->
        <div class="store-sb-header">
            <div class="store-sb-brand">
                <span><?php echo $isDrinksStaff ? '🥤' : '🍳'; ?></span> <?php echo $isDrinksStaff ? 'DRINK BAR STATION' : 'KITCHEN OPS & POS'; ?>
            </div>
            <button type="button" class="nav-opener-btn" onclick="SatayApp.toggleSideNav()" title="Close Navigation Menu" aria-label="Toggle Side Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>

        <!-- Staff User Card -->
        <div class="store-sb-user">
            <div class="store-sb-avatar" style="background:<?php echo $isDrinksStaff ? 'var(--info)' : 'var(--gold)'; ?>;">
                <?php echo $isDrinksStaff ? '🥤' : strtoupper(substr($currentUser['full_name'] ?? 'Staff', 0, 1)); ?>
            </div>
            <div class="store-sb-user-info">
                <div class="store-sb-user-name"><?php echo htmlspecialchars($currentUser['full_name'] ?? 'Kitchen Staff'); ?></div>
                <div class="store-sb-user-role"><?php echo $isDrinksStaff ? '🥤 Drink Section Barista' : ucfirst($currentUser['role'] ?? 'Staff') . ' Ops'; ?></div>
            </div>
        </div>

        <!-- Sidebar Navigation -->
        <nav class="store-sb-nav" id="store-sb-nav">
            <div class="store-sb-section">Station Views</div>
            <button type="button" class="store-sb-link client-tab-btn active" data-tab="kds" onclick="ClientApp.switchTab('kds')">
                <span class="store-sb-link-icon">🍳</span>
                <span>Live Orders Queue (KDS)</span>
            </button>
            <button type="button" class="store-sb-link client-tab-btn" data-tab="pos" onclick="ClientApp.switchTab('pos')">
                <span class="store-sb-link-icon">💵</span>
                <span>Walk-In Cashier POS</span>
            </button>
            <button type="button" class="store-sb-link client-tab-btn" data-tab="stock" onclick="ClientApp.switchTab('stock')">
                <span class="store-sb-link-icon">📦</span>
                <span>Stock & Availability</span>
            </button>

            <div class="store-sb-section">Quick Actions</div>
            <button type="button" class="store-sb-link" onclick="ClientApp.enableAndTestNotification()">
                <span class="store-sb-link-icon">🔔</span>
                <span>Test Ring & Vibration</span>
            </button>

            <?php if ($currentUser['role'] === 'admin'): ?>
                <div class="store-sb-section">Quick Portals</div>
                <a href="admin.php" class="store-sb-link">
                    <span class="store-sb-link-icon">👑</span>
                    <span>Admin Control Center</span>
                </a>
            <?php endif; ?>
        </nav>

        <!-- Sidebar Footer -->
        <div class="store-sb-footer">
            <a href="logout.php?redirect=login.php" class="btn btn-secondary btn-sm" style="color:var(--danger); border-color:rgba(181, 61, 46, 0.3); width:100%; justify-content:center; font-size:0.78rem;">
                Sign Out / Logout
            </a>
        </div>
    </aside>

    <!-- ========== MAIN CANVAS (Content Area) ========== -->
    <div class="store-canvas" id="store-canvas">

        <!-- Floating Navigation Toggle Button (visible on mobile / when sidebar is closed) -->
        <button type="button" class="floating-nav-btn" onclick="SatayApp.toggleSideNav()" title="Toggle Navigation Menu" aria-label="Toggle Side Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <!-- Main Content Area -->
        <main class="store-content container-fluid">
        <!-- 1. KITCHEN DISPLAY SYSTEM (KDS) VIEW -->
        <section id="view-kds">
            <!-- Active Orders Grid -->
            <div class="kds-grid" id="kds-grid">
                <!-- Dynamically updated via client.js -->
            </div>
        </section>

        <!-- 2. CASHIER WALK-IN POS VIEW -->
        <section id="view-pos" style="display:none;">
            <div class="pos-layout-container">
                <!-- POS Menu Grid -->
                <div>
                    <div class="pos-header-text" style="margin-bottom:1rem; display:flex; flex-direction:column; gap:0.2rem;">
                        <h3 style="font-size:1.15rem;">Quick Order Selection</h3>
                        <p style="font-size:0.8rem; color:var(--text-muted);">Click items to add to POS ticket</p>
                    </div>

                    <div class="pos-menu-grid" id="pos-menu-grid">
                        <!-- Populated via client.js -->
                    </div>
                </div>

                <!-- POS Ticket & Payment Panel -->
                <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:1.5rem; display:flex; flex-direction:column; height:fit-content; position:sticky; top:80px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; padding-bottom:0.75rem; border-bottom:1px solid var(--border-color);">
                        <h3 style="font-size:1.1rem;">Walk-In POS Bill</h3>
                        <button class="btn btn-sm btn-secondary" onclick="ClientApp.clearPOSCart()">Clear</button>
                    </div>

                    <div class="form-group">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem;">
                            <div>
                                <label class="form-label" for="pos-dining-type">Dining Mode:</label>
                                <select class="form-select" id="pos-dining-type" style="padding:0.4rem 0.6rem;">
                                    <option value="dine_in">Dine-In</option>
                                    <option value="takeaway" selected>Takeaway</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label" for="pos-table-number" id="pos-table-label">Table Selection:</label>
                                <select class="form-select" id="pos-table-number" style="padding:0.4rem 0.6rem;" disabled>
                                    <option value="">-- No Table (Takeaway) --</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="pos-customer-name" id="pos-name-label">Customer Name:</label>
                        <input type="text" class="form-input" id="pos-customer-name" value="Takeaway Customer" placeholder="Takeaway Customer" style="padding:0.4rem 0.6rem;" disabled>
                    </div>

                    <!-- Cart Item List -->
                    <div style="max-height:220px; overflow-y:auto; margin-bottom:1rem; border-top:1px solid var(--border-color); border-bottom:1px solid var(--border-color);" id="pos-cart-items">
                        <div style="text-align:center; padding:1.5rem; color:var(--text-muted); font-size:0.85rem;">
                            No items added yet. Click dishes on the left.
                        </div>
                    </div>

                    <!-- Financial Breakdown -->
                    <div style="font-size:0.86rem; margin-bottom:1rem;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:3px;">
                            <span style="color:var(--text-muted);">Subtotal:</span>
                            <span id="pos-subtotal">RM 0.00</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:3px;">
                            <span style="color:var(--text-muted);">SST (6%):</span>
                            <span id="pos-tax">RM 0.00</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:1.2rem; font-weight:800; color:var(--primary); border-top:1px solid var(--border-color); padding-top:6px; margin-top:4px;">
                            <span>Total Due:</span>
                            <span id="pos-total">RM 0.00</span>
                        </div>
                    </div>

                    <!-- Cash Calculator -->
                    <div style="background:var(--bg-card); padding:0.75rem; border-radius:var(--radius-md); border:1px solid var(--border-color); margin-bottom:1rem;">
                        <div class="form-group" style="margin-bottom:0.5rem;">
                            <label class="form-label" for="pos-payment-method">Payment Type:</label>
                            <select class="form-select" id="pos-payment-method" style="padding:0.4rem 0.6rem;">
                                <option value="cash">Cash Payment</option>
                                <option value="qr_pay">DuitNow QR Pay</option>
                            </select>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem;">
                            <div>
                                <label class="form-label" for="pos-cash-received">Cash Received:</label>
                                <input type="number" step="0.5" class="form-input" id="pos-cash-received" placeholder="0.00" oninput="ClientApp.calculatePOSChange()" style="padding:0.4rem 0.6rem;">
                            </div>
                            <div>
                                <label class="form-label">Change to Return:</label>
                                <div id="pos-change-amount" style="font-weight:800; font-size:1.1rem; padding:0.35rem 0; color:var(--text-muted);">
                                    RM 0.00
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary" style="width:100%; font-size:0.95rem; padding:0.8rem;" onclick="ClientApp.submitPOSOrder()">
                        Charge & Print Bill
                    </button>
                </div>
            </div>
        </section>

        <!-- 3. STOCK AVAILABILITY & QUANTITY MANAGER -->
        <section id="view-stock" style="display:none;">
            <div class="table-card">
                <div class="table-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                    <div>
                        <h3>Live Menu Stock & Quantity Left</h3>
                        <p style="font-size:0.85rem; color:var(--text-muted);">Adjust remaining stock counts in real-time. When items hit 0, they automatically become Sold Out.</p>
                    </div>
                    <button class="btn btn-secondary btn-sm" onclick="ClientApp.renderStockManager()">
                        🔄 Refresh Stock
                    </button>
                </div>

                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Item Details</th>
                                <th>Category</th>
                                <th>Unit Price</th>
                                <th>Quantity Left</th>
                                <th>Status / Toggle</th>
                            </tr>
                        </thead>
                        <tbody id="stock-manager-table-body">
                            <!-- Populated via client.js -->
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div><!-- /.store-canvas -->

    <!-- Core Scripts -->
    <script>
        window.currentUser = <?php echo json_encode($currentUser); ?>;
    </script>
    <script src="assets/js/app.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/client.js?v=<?php echo time(); ?>"></script>
</body>
</html>

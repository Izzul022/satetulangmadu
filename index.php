<?php
require_once __DIR__ . '/api/config.php';
$currentUser = get_current_auth_user();
$guestInfo = get_guest_info();

// If admin or staff accesses the customer order page, redirect to their dedicated dashboard
if ($currentUser) {
    if ($currentUser['role'] === 'admin') {
        header('Location: admin.php');
        exit;
    } elseif ($currentUser['role'] === 'staff') {
        header('Location: kitchen.php');
        exit;
    }
}

// Auto-initialize guest session if customer is not logged in
$tableParam = trim($_GET['table'] ?? '');
if (!$currentUser) {
    if (!$guestInfo) {
        $_SESSION['is_guest'] = true;
        $_SESSION['guest_name'] = !empty($tableParam) ? "Guest Table " . htmlspecialchars($tableParam) : "Guest Customer";
        $guestInfo = get_guest_info();
    } elseif (!empty($tableParam) && strpos($guestInfo['guest_name'] ?? '', 'Guest') === 0) {
        $_SESSION['guest_name'] = "Guest Table " . htmlspecialchars($tableParam);
        $guestInfo['guest_name'] = $_SESSION['guest_name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Sate Tulang Madu - Charcoal Grilled Skewers & Platters</title>
    <meta name="description"
        content="Order authentic charcoal-grilled Sate Tulang Madu, Sate Ayam, Sate Daging, and Sharing Platters with rich peanut sauce for Dine-In and Takeaway.">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22 fill=%22%238B4513%22>S</text></svg>">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#8B4513">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Sate Tulang">
</head>

<body class="store-layout-active">

    <!-- ========== EXISTING MOBILE HAMBURGER DRAWER (kept for mobile/tablet) ========== -->
    <div class="app-sidenav-backdrop" id="app-sidenav-backdrop" onclick="SatayApp.closeSideNav()"></div>
    <aside class="app-sidenav" id="app-side-nav">
        <div class="sidenav-header">
            <div class="sidenav-brand">
                <div class="nav-logo-icon" style="width:34px; height:34px; font-size:0.9rem;">S</div>
                <div>
                    <div class="sidenav-brand-title">SATE TULANG MADU</div>
                    <div class="sidenav-brand-sub">Authentic Charcoal Skewers</div>
                </div>
            </div>
            <button type="button" class="sidenav-close-btn" onclick="SatayApp.closeSideNav()"
                title="Close menu">&times;</button>
        </div>

        <?php if ($currentUser): ?>
            <div class="sidenav-user-card">
                <div class="sidenav-avatar"><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></div>
                <div class="sidenav-user-info">
                    <div class="sidenav-user-name"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                    <div class="sidenav-user-role"><?php echo ucfirst($currentUser['role']); ?> Account</div>
                </div>
            </div>
        <?php else: ?>
            <div class="sidenav-user-card" style="justify-content:space-between; align-items:center;">
                <div style="display:flex; align-items:center; gap:0.75rem; min-width:0;">
                    <div class="sidenav-avatar" style="background:var(--gold);">G</div>
                    <div class="sidenav-user-info">
                        <div class="sidenav-user-name"><?php echo htmlspecialchars($guestInfo['guest_name'] ?? 'Guest Customer'); ?></div>
                        <div class="sidenav-user-role">Guest Mode</div>
                    </div>
                </div>
                <button type="button" class="btn btn-primary btn-xs"
                    onclick="SatayApp.closeSideNav(); CustomerApp.openAuthModal('register')"
                    style="font-size:0.75rem; padding:0.35rem 0.7rem; border-radius:var(--radius-full); white-space:nowrap; flex-shrink:0;">
                    Sign In
                </button>
            </div>
        <?php endif; ?>

        <nav class="sidenav-body">
            <div class="sidenav-section-title">Menu & Ordering</div>
            <button type="button" class="sidenav-link active" onclick="SatayApp.closeSideNav()">
                <span class="sidenav-link-icon">🍗</span>
                <span>Satay Menu Catalog</span>
            </button>
            <button type="button" class="sidenav-link"
                onclick="SatayApp.closeSideNav(); CustomerApp.showOrderHistoryModal()">
                <span class="sidenav-link-icon">📋</span>
                <span>My Orders & History</span>
            </button>

            <div class="sidenav-section-title" id="sidenav-dining-title">Dining Modes</div>
            <button type="button" class="sidenav-link mode-btn" data-mode="dine_in" id="sidenav-dinein-opt"
                onclick="SatayApp.closeSideNav(); CustomerApp.setDiningMode('dine_in')">
                <span class="sidenav-link-icon">🍽️</span>
                <span>Dine-In (Table Service)</span>
            </button>
            <button type="button" class="sidenav-link mode-btn" data-mode="takeaway" id="sidenav-takeaway-opt"
                onclick="SatayApp.closeSideNav(); CustomerApp.setDiningMode('takeaway')">
                <span class="sidenav-link-icon">🥡</span>
                <span>Takeaway (Bungkus)</span>
            </button>

            <div class="sidenav-section-title">Account & Access</div>
            <?php if ($currentUser && $currentUser['role'] === 'customer'): ?>
                <button type="button" class="sidenav-link"
                    onclick="SatayApp.closeSideNav(); CustomerApp.openProfileModal()">
                    <span class="sidenav-link-icon">👤</span>
                    <span>My Saved Profile</span>
                </button>
                <a href="logout.php?redirect=login.php" class="sidenav-link" style="color:var(--danger);">
                    <span class="sidenav-link-icon">🚪</span>
                    <span>Sign Out</span>
                </a>
            <?php else: ?>
                <button type="button" class="sidenav-link"
                    onclick="SatayApp.closeSideNav(); CustomerApp.openAuthModal('register')"
                    style="color:var(--gold); font-weight:700; background:rgba(212, 175, 55, 0.1); border:1px solid rgba(212, 175, 55, 0.28);">
                    <span class="sidenav-link-icon">✨</span>
                    <span>Register / Sign In</span>
                </button>
                <a href="login.php" class="sidenav-link" onclick="SatayApp.closeSideNav()">
                    <span class="sidenav-link-icon">🔐</span>
                    <span>Staff & Portal Login</span>
                </a>
            <?php endif; ?>

            <?php if ($currentUser && in_array($currentUser['role'], ['admin', 'staff'])): ?>
                <div class="sidenav-section-title">Staff Portals</div>
                <?php if ($currentUser['role'] === 'admin'): ?>
                    <a href="admin.php" class="sidenav-link">
                        <span class="sidenav-link-icon">👑</span>
                        <span>Admin Control Center</span>
                    </a>
                <?php endif; ?>
                <a href="kitchen.php" class="sidenav-link">
                    <span class="sidenav-link-icon">🍳</span>
                    <span>Kitchen Display (KDS)</span>
                </a>
            <?php endif; ?>
        </nav>

        <div class="sidenav-footer">
            <?php if ($currentUser): ?>
                <a href="logout.php?redirect=login.php" class="btn btn-secondary btn-sm"
                    style="color:var(--danger); border-color:rgba(181, 61, 46, 0.3); width:100%; justify-content:center;">
                    Sign Out
                </a>
            <?php else: ?>
                <button type="button" class="btn btn-primary btn-sm"
                    onclick="SatayApp.closeSideNav(); CustomerApp.openAuthModal('register')"
                    style="width:100%; justify-content:center; font-weight:700;">
                    ✨ Register / Sign In
                </button>
            <?php endif; ?>
        </div>
    </aside>

    <!-- ========== DESKTOP PERSISTENT SIDEBAR (Microsoft Store Layout) ========== -->
    <aside class="store-sidebar" id="store-sidebar">
        <!-- Sidebar Brand Header & Toggle Button -->
        <div class="store-sb-header">
            <div class="store-sb-brand">
                <span>🍖</span> SATE TULANG MADU
            </div>
            <button type="button" class="nav-opener-btn" onclick="SatayApp.toggleSideNav()"
                title="Close Navigation Menu" aria-label="Toggle Side Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>

        <!-- User Card -->
        <?php if ($currentUser): ?>
            <div class="store-sb-user">
                <div class="store-sb-avatar"><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></div>
                <div class="store-sb-user-info">
                    <div class="store-sb-user-name"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                    <div class="store-sb-user-role"><?php echo ucfirst($currentUser['role']); ?> Account</div>
                </div>
            </div>
        <?php elseif ($guestInfo): ?>
            <div class="store-sb-user">
                <div class="store-sb-avatar" style="background:var(--gold);">G</div>
                <div class="store-sb-user-info">
                    <div class="store-sb-user-name"><?php echo htmlspecialchars($guestInfo['guest_name']); ?></div>
                    <div class="store-sb-user-role">Guest Mode</div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Sidebar Navigation -->
        <nav class="store-sb-nav" id="store-sb-nav">
            <!-- Dining Mode Segment -->
            <div class="store-sb-section" id="store-sb-dining-title">Dining Mode</div>
            <div class="store-sb-dining-segment" id="store-sb-dining-segment">
                <button type="button" class="store-sb-dining-btn" data-mode="dine_in" id="sb-dinein-btn"
                    onclick="CustomerApp.setDiningMode('dine_in')">🍽️ Dine-In</button>
                <button type="button" class="store-sb-dining-btn active" data-mode="takeaway" id="sb-takeaway-btn"
                    onclick="CustomerApp.setDiningMode('takeaway')">🥡 Takeaway</button>
            </div>

            <!-- Table Status Chip (shown if table assigned) -->
            <div class="store-sb-table-chip" id="store-sb-table-chip" style="display:none;">
                📍 <span id="store-sb-table-label">Table 01</span>
            </div>

            <!-- Menu Categories -->
            <div class="store-sb-section">Storefront</div>
            <div id="store-sb-categories">
                <!-- Dynamically populated by customer.js -->
            </div>

            <!-- Quick Links -->
            <div class="store-sb-section">Quick Actions</div>
            <button type="button" class="store-sb-link" onclick="CustomerApp.showOrderHistoryModal()">
                <span class="store-sb-link-icon">📋</span>
                <span>My Orders & History</span>
            </button>
            <?php if ($currentUser && $currentUser['role'] === 'customer'): ?>
                <button type="button" class="store-sb-link" onclick="CustomerApp.openProfileModal()">
                    <span class="store-sb-link-icon">👤</span>
                    <span>My Saved Profile</span>
                </button>
            <?php endif; ?>
        </nav>

        <!-- Sidebar Footer -->
        <div class="store-sb-footer">
            <?php if ($currentUser): ?>
                <a href="logout.php?redirect=login.php" class="btn btn-secondary btn-sm"
                    style="color:var(--danger); border-color:rgba(181, 61, 46, 0.3); width:100%; justify-content:center; font-size:0.78rem;">
                    Sign Out
                </a>
            <?php else: ?>
                <button type="button" class="btn btn-primary btn-sm" onclick="CustomerApp.openAuthModal('register')"
                    style="width:100%; justify-content:center; font-size:0.78rem;">
                    ✨ Register / Sign In
                </button>
            <?php endif; ?>
        </div>
    </aside>

    <!-- ========== MAIN CANVAS (Content Area) ========== -->
    <div class="store-canvas" id="store-canvas">

        <!-- Floating Navigation Toggle Button (visible on mobile / when sidebar is closed) -->
        <button type="button" class="floating-nav-btn" onclick="SatayApp.toggleSideNav()" title="Toggle Navigation Menu"
            aria-label="Toggle Side Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <!-- Store Main Content -->
        <main class="store-content">
            <!-- Spotlight Hero Banner -->
            <section class="store-hero">
                <div class="store-hero-content">
                    <div class="store-hero-badge">⭐ Signature Charcoal Grilled</div>
                    <h2>Aromatic, Juicy & Smokey <span>Sate Tulang Madu</span></h2>
                    <p>Handcrafted skewers marinated in 12 traditional aromatic spices with pure honey glaze,
                        flame-grilled over red-hot charcoal and served with thick velvety peanut sauce.</p>
                    <div class="store-hero-actions">
                        <button type="button" class="btn-hero-primary"
                            onclick="CustomerApp.sidebarFilterCategory('all')">🔥 Explore Full Menu</button>
                        <button type="button" class="btn-hero-secondary" onclick="CustomerApp.filterPopular()">⭐ Popular
                            & Hits</button>
                    </div>
                </div>
                <div class="store-hero-right">
                    <img src="assets/images/hero_satay.png" alt="Sate Tulang Madu Grill" class="store-hero-img">
                </div>
            </section>

            <!-- Category Ribbon (Horizontal Pills) -->
            <nav class="store-category-ribbon" id="store-category-ribbon">
                <!-- Dynamically populated by customer.js -->
            </nav>

            <!-- Menu Cards Grid (unchanged ID — customer.js targets this) -->
            <section class="menu-grid" id="menu-grid">
                <!-- Dynamically loaded via customer.js -->
            </section>
        </main>
    </div>


    <!-- Floating Cart Trigger Button -->
    <button class="cart-floating-btn" id="floating-cart-btn" aria-label="View Order Cart">
        <span>View Cart</span>
        <span class="cart-counter-bubble" id="cart-bubble-count">0</span>
        <span id="floating-cart-total" style="margin-left:auto; font-weight:800;">RM 0.00</span>
    </button>

    <!-- Slide-over Cart Drawer -->
    <div class="drawer-backdrop" id="cart-backdrop"></div>
    <aside class="drawer" id="cart-drawer">
        <div class="drawer-header">
            <h3 style="font-size:1.1rem;">Your Order Cart</h3>
            <button class="btn btn-secondary btn-icon" id="close-cart-btn" aria-label="Close Cart">&#10005;</button>
        </div>

        <div class="drawer-body" id="cart-items-list">
            <!-- Populated via customer.js -->
        </div>

        <div class="drawer-footer">
            <div style="margin-bottom:1rem; font-size:0.86rem;">
                <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                    <span style="color:var(--text-muted);">Total Skewers:</span>
                    <strong id="cart-stick-count" style="color:var(--primary);">0 Sticks</strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                    <span style="color:var(--text-muted);">Subtotal:</span>
                    <span id="cart-subtotal">RM 0.00</span>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                    <span style="color:var(--text-muted);" id="cart-tax-label">SST (6%):</span>
                    <span id="cart-tax">RM 0.00</span>
                </div>
                <div
                    style="display:flex; justify-content:space-between; font-size:1.15rem; font-weight:800; padding-top:6px; border-top:1px solid var(--border-color);">
                    <span>Total:</span>
                    <span id="cart-total" style="color:var(--primary);">RM 0.00</span>
                </div>
            </div>

            <button type="button" class="btn btn-primary" style="width:100%; font-size:0.95rem; padding:0.8rem;"
                onclick="CustomerApp.proceedToCheckout()">
                Proceed to Checkout
            </button>
        </div>
    </aside>

    <!-- Skewer Customizer Modal -->
    <div class="modal-overlay" id="customizer-modal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Customize Your Order</h3>
                <button class="btn btn-secondary btn-icon"
                    onclick="SatayApp.closeModal('customizer-modal')">&#10005;</button>
            </div>
            <div class="modal-body" id="customizer-modal-content">
                <!-- Populated dynamically -->
            </div>
        </div>
    </div>

    <!-- Customer Auth Modal (Register / Sign In / Guest Mode) -->
    <div class="modal-overlay" id="customer-auth-modal">
        <div class="modal-box" style="width: 500px;">
            <div class="modal-header">
                <h3 id="auth-modal-title">Customer Registration & Sign In</h3>
                <button class="btn btn-secondary btn-icon"
                    onclick="SatayApp.closeModal('customer-auth-modal')">&#10005;</button>
            </div>
            <div class="modal-body">
                <!-- Auth Tabs Navigation -->
                <div class="auth-tabs-nav">
                    <button type="button" class="auth-tab-btn active" data-tab="auth-register"
                        onclick="CustomerApp.switchAuthTab('auth-register')">
                        ✨ Register
                    </button>
                    <button type="button" class="auth-tab-btn" data-tab="auth-login"
                        onclick="CustomerApp.switchAuthTab('auth-login')">
                        🔑 Sign In
                    </button>
                    <button type="button" class="auth-tab-btn" data-tab="auth-guest"
                        onclick="CustomerApp.switchAuthTab('auth-guest')">
                        ⚡ Guest Mode
                    </button>
                </div>

                <!-- 1. Register Form -->
                <div class="auth-tab-pane active" id="auth-register">
                    <div
                        style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0.85rem; margin-bottom:1rem; font-size:0.82rem; color:var(--text-muted); line-height:1.4;">
                        Create a free customer account to save your past orders and re-order in 1 tap! Or continue
                        ordering as a guest.
                    </div>
                    <form id="customer-register-form">
                        <div class="form-group">
                            <label class="form-label" for="reg-fullname">Full Name: <span
                                    style="color:var(--danger);">*</span></label>
                            <input type="text" class="form-input" id="reg-fullname" placeholder="e.g. Ahmad Firdaus"
                                required>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                            <div class="form-group">
                                <label class="form-label" for="reg-username">Username: <span
                                        style="color:var(--danger);">*</span></label>
                                <input type="text" class="form-input" id="reg-username" placeholder="e.g. ahmad123"
                                    required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="reg-phone">Phone Number:</label>
                                <input type="tel" class="form-input" id="reg-phone" placeholder="012-3456789">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="reg-email">Email Address (Optional):</label>
                            <input type="email" class="form-input" id="reg-email" placeholder="ahmad@example.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="reg-password">Create Password: <span
                                    style="color:var(--danger);">*</span></label>
                            <div class="password-input-wrap">
                                <input type="password" class="form-input" id="reg-password"
                                    placeholder="Min. 4 characters" required minlength="4">
                                <button type="button" class="password-toggle-btn"
                                    onclick="CustomerApp.toggleInputPassword('reg-password', this)"
                                    title="Show/Hide Password">👁️</button>
                            </div>
                        </div>
                        <div id="cust-reg-error"
                            style="display:none; color:var(--danger); font-size:0.85rem; margin-bottom:0.75rem; text-align:center;">
                        </div>
                        <button type="submit" class="btn btn-primary" id="btn-cust-register"
                            style="width:100%; padding:0.8rem; font-weight:600;">
                            Create Account & Start Ordering
                        </button>
                    </form>
                    <div style="margin-top:0.75rem; text-align:center; font-size:0.8rem; color:var(--text-muted);">
                        Already have an account? <a href="javascript:void(0)"
                            onclick="CustomerApp.switchAuthTab('auth-login')"
                            style="color:var(--primary); font-weight:600;">Sign In here</a>
                    </div>
                </div>

                <!-- 2. Sign In Form -->
                <div class="auth-tab-pane" id="auth-login">
                    <form id="customer-login-form">
                        <div class="form-group">
                            <label class="form-label" for="cust-login-user">Username, Email, or Phone:</label>
                            <input type="text" class="form-input" id="cust-login-user"
                                placeholder="e.g. username or 012-9876543" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="cust-login-pass">Password:</label>
                            <div class="password-input-wrap">
                                <input type="password" class="form-input" id="cust-login-pass" placeholder="••••••••"
                                    required>
                                <button type="button" class="password-toggle-btn"
                                    onclick="CustomerApp.toggleInputPassword('cust-login-pass', this)"
                                    title="Show/Hide Password">👁️</button>
                            </div>
                        </div>
                        <div id="cust-login-error"
                            style="display:none; color:var(--danger); font-size:0.85rem; margin-bottom:0.75rem; text-align:center;">
                        </div>
                        <button type="submit" class="btn btn-primary" id="btn-cust-login"
                            style="width:100%; padding:0.8rem; font-weight:600;">
                            Sign In to Account
                        </button>
                    </form>
                    <div style="margin-top:0.75rem; text-align:center; font-size:0.8rem; color:var(--text-muted);">
                        Need an account? <a href="javascript:void(0)"
                            onclick="CustomerApp.switchAuthTab('auth-register')"
                            style="color:var(--primary); font-weight:600;">Register now</a>
                    </div>
                </div>

                <!-- 3. Guest Mode Form -->
                <div class="auth-tab-pane" id="auth-guest">
                    <div
                        style="background:var(--gold-light); border:1px solid rgba(155, 106, 47, 0.25); border-radius:var(--radius-md); padding:1rem; margin-bottom:1.25rem;">
                        <h4
                            style="color:var(--gold); font-size:0.92rem; margin-bottom:0.35rem; font-family:var(--font-body); font-weight:700;">
                            Fast Guest Ordering</h4>
                        <p style="font-size:0.82rem; color:var(--text-muted); line-height:1.4;">
                            No password or registration required! Set your nickname for your order receipts.
                        </p>
                    </div>

                    <form id="customer-guest-form">
                        <div class="form-group">
                            <label class="form-label" for="guest-nickname">Your Nickname / Name:</label>
                            <input type="text" class="form-input" id="guest-nickname" placeholder="e.g. Bob or Sarah"
                                required
                                value="<?php echo htmlspecialchars($guestInfo['guest_name'] ?? 'Guest Customer'); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="guest-phone">Phone Number (For WhatsApp / SMS
                                Alerts):</label>
                            <input type="tel" class="form-input" id="guest-phone" placeholder="e.g. 012-3456789"
                                value="<?php echo htmlspecialchars($guestInfo['guest_phone'] ?? ''); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary"
                            style="width:100%; padding:0.8rem; font-weight:600;">
                            Continue as Guest
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Profile Modal -->
    <div class="modal-overlay" id="profile-modal">
        <div class="modal-box" style="width: 480px;">
            <div class="modal-header">
                <h3>My Customer Profile</h3>
                <button class="btn btn-secondary btn-icon"
                    onclick="SatayApp.closeModal('profile-modal')">&#10005;</button>
            </div>
            <form id="customer-profile-form">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="prof-fullname">Full Name:</label>
                        <input type="text" class="form-input" id="prof-fullname" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="prof-phone">Phone Number:</label>
                        <input type="tel" class="form-input" id="prof-phone">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="prof-email">Email Address:</label>
                        <input type="email" class="form-input" id="prof-email">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        onclick="SatayApp.closeModal('profile-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Profile</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Checkout Modal -->
    <div class="modal-overlay" id="checkout-modal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Confirm & Checkout</h3>
                <button class="btn btn-secondary btn-icon"
                    onclick="SatayApp.closeModal('checkout-modal')">&#10005;</button>
            </div>
            <form id="checkout-form">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="checkout-name">Your Name / Nickname: <span
                                style="color:var(--danger);">*</span></label>
                        <input type="text" class="form-input" id="checkout-name" placeholder="e.g. Encik Farhan"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="checkout-phone">Phone Number (For WhatsApp / SMS status):</label>
                        <input type="tel" class="form-input" id="checkout-phone" placeholder="e.g. 012-3456789">
                    </div>

                    <!-- Dine-in specific -->
                    <div class="form-group" id="dine-in-fields">
                        <label class="form-label" for="checkout-table">Choose Dining Table: <span
                                style="color:var(--danger);">*</span></label>
                        <select class="form-select" id="checkout-table">
                            <option value="">-- Select Table --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="checkout-payment">Payment Method:</label>
                        <select class="form-select" id="checkout-payment">
                            <option value="cash">Cash at Counter / Pay at Counter</option>
                            <option value="qr_pay">DuitNow QR / Touch 'n Go eWallet</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="checkout-order-notes">Special Cooking Request:</label>
                        <input type="text" class="form-input" id="checkout-order-notes"
                            placeholder="e.g. Bakar garing, kuah jangan pedas sangat...">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        onclick="SatayApp.closeModal('checkout-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-order">Confirm & Place Order</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Live Order Tracker Modal -->
    <div class="modal-overlay" id="tracker-modal">
        <div class="modal-box" style="width: 620px;">
            <div class="modal-header">
                <h3>Live Kitchen Tracking</h3>
                <button class="btn btn-secondary btn-icon" onclick="CustomerApp.stopLiveTracking()">&#10005;</button>
            </div>
            <div class="modal-body" id="tracker-details-container">
                <!-- Real-time progress updates -->
            </div>
        </div>
    </div>

    <!-- Order History Modal -->
    <div class="modal-overlay" id="history-modal">
        <div class="modal-box" style="width: 580px;">
            <div class="modal-header">
                <h3>My Orders & History</h3>
                <button class="btn btn-secondary btn-icon"
                    onclick="SatayApp.closeModal('history-modal')">&#10005;</button>
            </div>
            <div class="modal-body" id="history-modal-content">
                <!-- Populated via customer.js -->
            </div>
        </div>
    </div>

    <!-- Core Scripts -->
    <script src="assets/js/app.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/customer.js?v=<?php echo time(); ?>"></script>
</body>

</html>
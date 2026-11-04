<?php
require_once __DIR__ . '/api/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php?redirect=admin.php&error=auth_required');
    exit;
}

$currentUser = get_current_auth_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Admin Dashboard & Management - Sate Tulang Madu</title>
    <meta name="description" content="Executive analytics, menu catalog CRUD, order master view, and QR code manager for Sate Tulang Madu.">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22 fill=%22%238B4513%22>S</text></svg>">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#8B4513">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Satay Admin">
</head>
<body class="store-layout-active">

    <!-- ========== EXISTING MOBILE HAMBURGER DRAWER (kept for mobile/tablet) ========== -->
    <div class="app-sidenav-backdrop" id="app-sidenav-backdrop" onclick="SatayApp.closeSideNav()"></div>
    <aside class="app-sidenav" id="app-side-nav">
        <div class="sidenav-header">
            <div class="sidenav-brand">
                <div class="nav-logo-icon" style="width:34px; height:34px; font-size:0.9rem; background:var(--info);">A</div>
                <div>
                    <div class="sidenav-brand-title">ADMIN CONTROL</div>
                    <div class="sidenav-brand-sub">Sate Tulang Madu</div>
                </div>
            </div>
            <button type="button" class="sidenav-close-btn" onclick="SatayApp.closeSideNav()" title="Close menu">&times;</button>
        </div>

        <div class="sidenav-user-card">
            <div class="sidenav-avatar" style="background:var(--info);"><?php echo strtoupper(substr($currentUser['full_name'] ?? 'Admin', 0, 1)); ?></div>
            <div class="sidenav-user-info">
                <div class="sidenav-user-name"><?php echo htmlspecialchars($currentUser['full_name'] ?? 'Administrator'); ?></div>
                <div class="sidenav-user-role">Master Administrator</div>
            </div>
        </div>

        <nav class="sidenav-body">
            <div class="sidenav-section-title">Administration</div>
            <button type="button" class="sidenav-link admin-nav-item active" data-tab="dashboard" onclick="AdminApp.switchTab('dashboard'); SatayApp.closeSideNav()">
                <span class="sidenav-link-icon">📊</span>
                <span>Executive Dashboard</span>
            </button>
            <button type="button" class="sidenav-link admin-nav-item" data-tab="menu" onclick="AdminApp.switchTab('menu'); SatayApp.closeSideNav()">
                <span class="sidenav-link-icon">🍗</span>
                <span>Menu Catalog</span>
            </button>
            <button type="button" class="sidenav-link admin-nav-item" data-tab="orders" onclick="AdminApp.switchTab('orders'); SatayApp.closeSideNav()">
                <span class="sidenav-link-icon">📦</span>
                <span>Live Orders</span>
            </button>
            <button type="button" class="sidenav-link admin-nav-item" data-tab="tables" onclick="AdminApp.switchTab('tables'); SatayApp.closeSideNav()">
                <span class="sidenav-link-icon">📱</span>
                <span>Table QRs & Stands</span>
            </button>
            <button type="button" class="sidenav-link admin-nav-item" data-tab="users" onclick="AdminApp.switchTab('users'); SatayApp.closeSideNav()">
                <span class="sidenav-link-icon">👥</span>
                <span>Staff & Customer Users</span>
            </button>
            <button type="button" class="sidenav-link admin-nav-item" data-tab="settings" onclick="AdminApp.switchTab('settings'); SatayApp.closeSideNav()">
                <span class="sidenav-link-icon">⚙️</span>
                <span>Store & Domain Settings</span>
            </button>
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
                <span>👑</span> ADMIN CONTROL CENTER
            </div>
            <button type="button" class="nav-opener-btn" onclick="SatayApp.toggleSideNav()" title="Close Navigation Menu" aria-label="Toggle Side Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>

        <!-- Admin User Card -->
        <div class="store-sb-user">
            <div class="store-sb-avatar" style="background:var(--info);"><?php echo strtoupper(substr($currentUser['full_name'] ?? 'Admin', 0, 1)); ?></div>
            <div class="store-sb-user-info">
                <div class="store-sb-user-name"><?php echo htmlspecialchars($currentUser['full_name'] ?? 'Administrator'); ?></div>
                <div class="store-sb-user-role">Master Administrator</div>
            </div>
        </div>

        <!-- Sidebar Navigation -->
        <nav class="store-sb-nav" id="store-sb-nav">
            <div class="store-sb-section">Administration</div>
            <button type="button" class="store-sb-link admin-nav-item active" data-tab="dashboard" onclick="AdminApp.switchTab('dashboard')">
                <span class="store-sb-link-icon">📊</span>
                <span>Executive Dashboard</span>
            </button>
            <button type="button" class="store-sb-link admin-nav-item" data-tab="menu" onclick="AdminApp.switchTab('menu')">
                <span class="store-sb-link-icon">🍗</span>
                <span>Menu Catalog</span>
            </button>
            <button type="button" class="store-sb-link admin-nav-item" data-tab="orders" onclick="AdminApp.switchTab('orders')">
                <span class="store-sb-link-icon">📦</span>
                <span>Live Orders</span>
            </button>
            <button type="button" class="store-sb-link admin-nav-item" data-tab="tables" onclick="AdminApp.switchTab('tables')">
                <span class="store-sb-link-icon">📱</span>
                <span>Table QRs & Stands</span>
            </button>
            <button type="button" class="store-sb-link admin-nav-item" data-tab="users" onclick="AdminApp.switchTab('users')">
                <span class="store-sb-link-icon">👥</span>
                <span>Staff & Customers</span>
            </button>
            <button type="button" class="store-sb-link admin-nav-item" data-tab="settings" onclick="AdminApp.switchTab('settings')">
                <span class="store-sb-link-icon">⚙️</span>
                <span>Store Settings</span>
            </button>
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
        <!-- 1. DASHBOARD & ANALYTICS TAB -->
        <section id="tab-dashboard">
            <div style="margin-bottom:1.5rem; display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-end; gap:1rem;">
                <div>
                    <h2>Executive Business Overview</h2>
                    <p style="color:var(--text-muted); font-size:0.9rem;">Real-time sales figures, meat stick volume, and daily performance metrics separated by date.</p>
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center;">
                    <!-- Date Presets -->
                    <div class="date-filter-group" style="display:inline-flex; background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:3px; gap:2px;">
                        <button type="button" class="btn btn-sm overview-date-preset active" data-range="today" onclick="AdminApp.setDateFilter('today')">Today</button>
                        <button type="button" class="btn btn-sm overview-date-preset btn-ghost" data-range="yesterday" onclick="AdminApp.setDateFilter('yesterday')">Yesterday</button>
                        <button type="button" class="btn btn-sm overview-date-preset btn-ghost" data-range="this_week" onclick="AdminApp.setDateFilter('this_week')">This Week</button>
                        <button type="button" class="btn btn-sm overview-date-preset btn-ghost" data-range="this_month" onclick="AdminApp.setDateFilter('this_month')">This Month</button>
                        <button type="button" class="btn btn-sm overview-date-preset btn-ghost" data-range="all" onclick="AdminApp.setDateFilter('all')">All Time</button>
                        <button type="button" class="btn btn-sm overview-date-preset btn-ghost" data-range="custom" onclick="AdminApp.toggleCustomDateInputs()">Custom Date</button>
                    </div>

                    <!-- Custom Date Range Form (collapsible) -->
                    <div id="overview-custom-date-container" style="display:none; align-items:center; gap:0.4rem; background:var(--bg-surface); padding:4px 8px; border-radius:var(--radius-md); border:1px solid var(--border-color);">
                        <input type="date" id="overview-start-date" class="form-input" style="padding:0.3rem 0.5rem; font-size:0.82rem; width:135px;">
                        <span style="color:var(--text-muted); font-size:0.8rem;">to</span>
                        <input type="date" id="overview-end-date" class="form-input" style="padding:0.3rem 0.5rem; font-size:0.82rem; width:135px;">
                        <button type="button" class="btn btn-primary btn-sm" onclick="AdminApp.applyCustomDateFilter()">Apply</button>
                    </div>

                    <button class="btn btn-secondary btn-sm" onclick="AdminApp.fetchDashboardStats()" title="Refresh Data">
                        ↺ Refresh
                    </button>
                </div>
            </div>

            <!-- Active Date Filter Pill Banner -->
            <div style="margin-bottom:1.25rem; display:flex; align-items:center; justify-content:space-between; background:rgba(212, 175, 55, 0.08); border:1px solid rgba(212, 175, 55, 0.25); border-radius:var(--radius-md); padding:0.6rem 1rem;">
                <div style="font-size:0.88rem; display:flex; align-items:center; gap:0.5rem;">
                    <span style="color:var(--gold); font-weight:700;">📅 Active Period:</span>
                    <span id="overview-active-date-label" style="font-weight:600; color:var(--text-main);">Today</span>
                </div>
                <div style="font-size:0.8rem; color:var(--text-muted);">
                    All overview metrics, stick counts & charts reflect this timeframe.
                </div>
            </div>

            <!-- KPI Cards Grid -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-icon" style="background:var(--gold-light); color:var(--gold);">RM</div>
                    <div class="kpi-info">
                        <h3 id="kpi-today-revenue">RM 0.00</h3>
                        <p id="kpi-revenue-label">PERIOD REVENUE</p>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon" style="background:var(--primary-light); color:var(--primary);">Qty</div>
                    <div class="kpi-info">
                        <h3 id="kpi-today-sticks">0 Sticks</h3>
                        <p id="kpi-sticks-label">STICKS SOLD</p>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon" style="background:var(--info-light); color:var(--info);">#</div>
                    <div class="kpi-info">
                        <h3 id="kpi-today-orders">0 Orders</h3>
                        <p id="kpi-orders-label">ORDERS PLACED</p>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon" style="background:var(--success-light); color:var(--success);">Q</div>
                    <div class="kpi-info">
                        <h3 id="kpi-active-orders">0 Active</h3>
                        <p>KITCHEN QUEUE</p>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon" style="background:rgba(139, 69, 19, 0.08); color:var(--primary);">Avg</div>
                    <div class="kpi-info">
                        <h3 id="kpi-aov">RM 0.00</h3>
                        <p>AVG TICKET SIZE</p>
                    </div>
                </div>
            </div>

            <!-- Daily Performance Breakdown by Date Table -->
            <div class="table-card" style="margin-bottom:2rem;">
                <div class="table-header">
                    <div>
                        <h3 style="margin:0 0 0.2rem;">Daily Business Breakdown by Date</h3>
                        <p style="font-size:0.8rem; color:var(--text-muted); margin:0;">Separated performance log grouped by calendar day (Revenue, Orders, Completed, Status).</p>
                    </div>
                    <button class="btn btn-secondary btn-sm" onclick="AdminApp.exportCSV()">Export Orders CSV</button>
                </div>
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Total Orders</th>
                                <th>Completed</th>
                                <th>Cancelled</th>
                                <th>Daily Revenue</th>
                                <th>Avg Order Value</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="daily-breakdown-tbody">
                            <!-- Populated via AdminApp.renderDailyBreakdownTable -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Analytics Distribution Section -->
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem; margin-bottom:2rem;">
                <!-- Stick Breakdown Card -->
                <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:1.5rem;">
                    <h3 style="font-size:1.15rem; margin-bottom:0.25rem;">Meat Skewers Volume Distribution</h3>
                    <p style="font-size:0.82rem; color:var(--text-muted); margin-bottom:1.25rem;">Breakdown of total sticks ordered across all meats & platters for selected period</p>
                    <div id="stick-breakdown-container">
                        <!-- Populated via admin.js -->
                    </div>
                </div>

                <!-- Top Best Selling Items -->
                <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:1.5rem;">
                    <h3 style="font-size:1.15rem; margin-bottom:0.25rem;">Top Best Selling Menu Items</h3>
                    <p style="font-size:0.82rem; color:var(--text-muted); margin-bottom:1.25rem;">Ranked by units sold & total revenue contribution for selected period</p>
                    <div id="top-items-list">
                        <!-- Populated via admin.js -->
                    </div>
                </div>
            </div>

            <!-- Recent Orders Quick Table -->
            <div class="table-card">
                <div class="table-header">
                    <h3>Recent Transactions</h3>
                    <button class="btn btn-secondary btn-sm" onclick="AdminApp.switchTab('orders')">View All Orders →</button>
                </div>
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Dining Mode</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="recent-orders-tbody">
                            <!-- Populated via admin.js -->
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- 2. MENU MANAGEMENT CRUD TAB -->
        <section id="tab-menu" style="display:none;">
            <div style="margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h2>Menu & Item Catalog</h2>
                    <p style="color:var(--text-muted); font-size:0.9rem;">Manage skewers, platters, sides, prices, minimum stick quantities, and descriptions.</p>
                </div>
                <button class="btn btn-primary" onclick="AdminApp.openAddMenuModal()">
                    + Add New Menu Item
                </button>
            </div>

            <div class="table-card">
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Item Details</th>
                                <th>Category</th>
                                <th>Price / Unit</th>
                                <th>Min Qty</th>
                                <th>Quantity Left / Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="admin-menu-tbody">
                            <!-- Populated via admin.js -->
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- 3. ORDERS MASTER LIST TAB -->
        <section id="tab-orders" style="display:none;">
            <div style="margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                <div>
                    <h2>Orders Directory</h2>
                    <p style="color:var(--text-muted); font-size:0.9rem;">Track, inspect, update status, or export all customer orders.</p>
                </div>
                <div style="display:flex; gap:0.75rem;">
                    <button class="btn btn-success" onclick="AdminApp.exportCSV()">
                        Export to CSV / Excel
                    </button>
                </div>
            </div>

            <!-- Filters Bar -->
            <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:1rem; margin-bottom:1.25rem; display:flex; gap:1rem; flex-wrap:wrap; align-items:center;">
                <div style="flex:1; min-width:200px;">
                    <input type="text" id="filter-order-search" class="form-input" placeholder="Search by Order #, Name, Phone, Table...">
                </div>
                <div>
                    <input type="date" id="filter-order-date" class="form-input" title="Filter orders by specific date">
                </div>
                <div>
                    <select id="filter-order-status" class="form-select">
                        <option value="all">All Statuses</option>
                        <option value="active">Active (Pending to Ready)</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="grilling">Grilling</option>
                        <option value="ready">Ready</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div>
                    <select id="filter-order-dining" class="form-select">
                        <option value="all">All Dining Types</option>
                        <option value="dine_in">Dine-In</option>
                        <option value="takeaway">Takeaway</option>
                    </select>
                </div>
            </div>

            <div class="table-card">
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer Info</th>
                                <th>Dining Mode</th>
                                <th>Order Status</th>
                                <th>Payment</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="admin-orders-tbody">
                            <!-- Populated via admin.js -->
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- 4. TABLES & QR CODE TAB -->
        <section id="tab-tables" style="display:none;">
            <div style="margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h2>Tables & QR Code Ordering</h2>
                    <p style="color:var(--text-muted); font-size:0.9rem;">Generate and print contactless QR code stands for each dining table.</p>
                </div>
                <button class="btn btn-primary" onclick="AdminApp.openAddTableModal()">
                    + Add New Table
                </button>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:1.25rem;" id="tables-grid">
                <!-- Populated via admin.js -->
            </div>
        </section>

        <!-- 5. USERS & CUSTOMERS DIRECTORY TAB -->
        <section id="tab-users" style="display:none;">
            <div style="margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                <div>
                    <h2>User Accounts & Customer Directory</h2>
                    <p style="color:var(--text-muted); font-size:0.9rem;">Manage store staff, administrators, and registered customer accounts.</p>
                </div>
                <div style="display:flex; gap:0.75rem;">
                    <button class="btn btn-primary" onclick="AdminApp.openAddUserModal()">
                        + Add Staff / User
                    </button>
                </div>
            </div>

            <!-- Role Filter Pills -->
            <div style="margin-bottom:1.25rem; display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
                <button class="btn btn-sm btn-secondary user-filter-pill active" data-role="all" onclick="AdminApp.filterUsersByRole('all')">All Accounts</button>
                <button class="btn btn-sm btn-secondary user-filter-pill" data-role="staff_drinks" onclick="AdminApp.filterUsersByRole('staff_drinks')">🥤 Drink Staff</button>
                <button class="btn btn-sm btn-secondary user-filter-pill" data-role="staff" onclick="AdminApp.filterUsersByRole('staff')">🍢 Grill & POS Staff</button>
                <button class="btn btn-sm btn-secondary user-filter-pill" data-role="customer" onclick="AdminApp.filterUsersByRole('customer')">Registered Customers</button>
                <button class="btn btn-sm btn-secondary user-filter-pill" data-role="admin" onclick="AdminApp.filterUsersByRole('admin')">Master Admins</button>
            </div>

            <div class="table-card">
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>User Profile</th>
                                <th>Username</th>
                                <th>Contact (Phone / Email)</th>
                                <th>Role</th>
                                <th>Orders / Activity</th>
                                <th>Registered Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="admin-users-tbody">
                            <!-- Populated via admin.js -->
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- 6. STORE SETTINGS TAB -->
        <section id="tab-settings" style="display:none;">
            <div style="margin-bottom:1.5rem;">
                <h2>System & Store Configuration</h2>
                <p style="color:var(--text-muted); font-size:0.9rem;">Configure business details, tax calculations, and kitchen preferences.</p>
            </div>

            <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:2rem; max-width:760px;">
                <form id="settings-form">
                    <div style="margin-bottom:1.5rem; padding-bottom:1.25rem; border-bottom:1px solid var(--border-color);">
                        <h3 style="font-size:1.1rem; margin-bottom:0.35rem; color:var(--primary);">🌐 Public Online Domain & QR Redirection</h3>
                        <p style="font-size:0.83rem; color:var(--text-muted); margin-bottom:1rem;">
                            This URL is encoded into customer Table QR stands. When customers scan the QR stand with their smartphone camera, they are instantly redirected to this web address.
                        </p>

                        <div class="form-group" style="margin-bottom:0.75rem;">
                            <label class="form-label" for="setting-public-base-url">Public Server / Domain URL:</label>
                            <div style="display:flex; gap:8px;">
                                <input type="url" class="form-input" id="setting-public-base-url" placeholder="https://your-satay-app.onrender.com/" style="flex:1;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="AdminApp.autoDetectBaseUrl()" style="white-space:nowrap;">
                                    🔍 Auto-Detect
                                </button>
                            </div>
                            <small style="color:var(--text-muted); font-size:0.78rem; display:block; margin-top:4px;">
                            Leave blank to automatically use the current server's origin. For Render, enter your Render URL (e.g. <code>https://satetulangmadu.onrender.com/</code>).
                            </small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="setting-store-name">Store / Brand Name:</label>
                        <input type="text" class="form-input" id="setting-store-name" placeholder="Sate Tulang Madu">
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label" for="setting-tax-rate">SST / Tax Rate (%):</label>
                            <input type="number" step="0.1" class="form-input" id="setting-tax-rate" placeholder="6">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="setting-currency">Currency Symbol:</label>
                            <input type="text" class="form-input" id="setting-currency" placeholder="RM">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label" for="setting-phone">Store WhatsApp / Phone:</label>
                            <input type="text" class="form-input" id="setting-phone" placeholder="+60123456789">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="setting-auto-refresh">KDS Auto-Refresh Interval (Seconds):</label>
                            <input type="number" class="form-input" id="setting-auto-refresh" min="3" max="60" placeholder="6">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="setting-address">Store Physical Address:</label>
                        <textarea class="form-textarea" id="setting-address" rows="2" placeholder="Full restaurant address"></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom:1.5rem;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.9rem;">
                            <input type="checkbox" id="setting-kitchen-audio" checked style="accent-color:var(--primary); width:18px; height:18px;">
                            <span>Enable Loud Kitchen Order Audio Chime</span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary" id="btn-save-settings">💾 Save System Settings</button>
                </form>
            </div>
        </section>
    </main>
</div><!-- /.store-canvas -->

    <!-- Add / Edit Menu Item Modal -->
    <div class="modal-overlay" id="menu-modal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 id="menu-modal-title">Add / Edit Menu Item</h3>
                <button class="btn btn-secondary btn-icon" onclick="SatayApp.closeModal('menu-modal')">&times;</button>
            </div>
            <form id="menu-item-form">
                <input type="hidden" id="menu-item-id">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="menu-item-name">Item Name:</label>
                        <input type="text" class="form-input" id="menu-item-name" required placeholder="e.g. Satay Ayam Royale">
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label" for="menu-item-category">Category:</label>
                            <select class="form-select" id="menu-item-category" required>
                                <!-- Populated dynamically -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="menu-item-price">Price per Unit (RM):</label>
                            <input type="number" step="0.05" class="form-input" id="menu-item-price" required placeholder="1.40">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:0.75rem;">
                        <div class="form-group">
                            <label class="form-label" for="menu-item-unit">Unit Name:</label>
                            <input type="text" class="form-input" id="menu-item-unit" value="cucuk" placeholder="cucuk, set, bowl">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="menu-item-stock">Quantity Left:</label>
                            <input type="number" class="form-input" id="menu-item-stock" min="0" value="100" placeholder="100">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="menu-item-min-qty">Min Order:</label>
                            <input type="number" class="form-input" id="menu-item-min-qty" value="5">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="menu-item-meat-type">Grill Type:</label>
                            <select class="form-select" id="menu-item-meat-type">
                                <option value="">None (Sides/Drinks/Extras)</option>
                                <option value="tulang_madu">Sate Tulang Madu</option>
                                <option value="ayam">Ayam (Chicken)</option>
                                <option value="daging">Daging (Beef)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="menu-item-desc">Description:</label>
                        <textarea class="form-textarea" id="menu-item-desc" rows="2" placeholder="Description of the dish, spices, and sides included..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="menu-item-img">Dish Image:</label>
                        <div style="display:flex; gap:1rem; align-items:center; margin-bottom:0.6rem; background:var(--bg-card); padding:0.6rem; border-radius:var(--radius-md); border:1px solid var(--border-color);">
                            <img id="menu-item-img-preview" src="assets/images/sate_ayam.png" alt="Preview" style="width:72px; height:64px; border-radius:var(--radius-sm); object-fit:cover; border:1px solid var(--border-color); background:#1a100b;" onerror="this.src='assets/images/sate_ayam.png'">
                            <div style="flex:1;">
                                <input type="file" id="menu-item-file-input" accept="image/png, image/jpeg, image/webp, image/gif" class="form-input" style="padding:0.3rem 0.5rem; font-size:0.8rem;" onchange="AdminApp.handleImageUpload(event)">
                                <p style="font-size:0.72rem; color:var(--text-muted); margin-top:3px;">Upload image file from your computer (PNG, JPG, WEBP)</p>
                            </div>
                        </div>
                        <input type="text" class="form-input" id="menu-item-img" value="assets/images/sate_ayam.png" placeholder="e.g. assets/images/sate_ayam.png" oninput="AdminApp.updateImagePreview(this.value)">
                    </div>

                    <div style="display:flex; gap:1.5rem; margin-top:0.5rem;">
                        <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                            <input type="checkbox" id="menu-item-popular" style="accent-color:var(--primary);">
                            <span>Mark as Popular</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                            <input type="checkbox" id="menu-item-available" checked style="accent-color:var(--success);">
                            <span>Available in Stock</span>
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="SatayApp.closeModal('menu-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Item</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Order Detail Modal -->
    <div class="modal-overlay" id="order-detail-modal">
        <div class="modal-box" style="width: 580px;">
            <div class="modal-header">
                <h3>Order Inspection</h3>
                <button class="btn btn-secondary btn-icon" onclick="SatayApp.closeModal('order-detail-modal')">&times;</button>
            </div>
            <div class="modal-body" id="order-detail-content">
                <!-- Populated dynamically via admin.js -->
            </div>
        </div>
    </div>

    <!-- Add / Edit User Modal -->
    <div class="modal-overlay" id="user-modal">
        <div class="modal-box" style="width: 480px;">
            <div class="modal-header">
                <h3 id="user-modal-title">Add / Edit User Account</h3>
                <button class="btn btn-secondary btn-icon" onclick="SatayApp.closeModal('user-modal')">&times;</button>
            </div>
            <form id="user-form">
                <input type="hidden" id="user-id">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="user-fullname">Full Name:</label>
                        <input type="text" class="form-input" id="user-fullname" required placeholder="e.g. Pak Samad">
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                        <div class="form-group">
                            <label class="form-label" for="user-username">Username:</label>
                            <input type="text" class="form-input" id="user-username" required placeholder="e.g. staff2">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="user-role">Role:</label>
                            <select class="form-select" id="user-role" required>
                                <option value="staff_drinks">🥤 Kitchen Drink Staff</option>
                                <option value="staff">🍢 Kitchen Grill & POS Staff</option>
                                <option value="customer">Registered Customer</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                        <div class="form-group">
                            <label class="form-label" for="user-phone">Phone Number:</label>
                            <input type="tel" class="form-input" id="user-phone" placeholder="012-3456789">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="user-email">Email:</label>
                            <input type="email" class="form-input" id="user-email" placeholder="staff@satayroyale.com">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="user-address">Address (For customers):</label>
                        <textarea class="form-textarea" id="user-address" rows="2" placeholder="Full address"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="user-password">Password <small style="color:var(--text-muted);">(Leave blank to keep current)</small>:</label>
                        <input type="password" class="form-input" id="user-password" placeholder="••••••••">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="SatayApp.closeModal('user-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Table Modal -->
    <div class="modal-overlay" id="table-modal">
        <div class="modal-box" style="width: 440px;">
            <div class="modal-header">
                <h3 id="table-modal-title">Add Dining Table</h3>
                <button class="btn btn-secondary btn-icon" onclick="SatayApp.closeModal('table-modal')">&times;</button>
            </div>
            <form id="table-form" onsubmit="AdminApp.saveTable(event)">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="table-num-input">Table Number / Label: <span style="color:var(--danger);">*</span></label>
                        <input type="text" class="form-input" id="table-num-input" required placeholder="e.g. T01, Table 1, VIP 1, Outdoor 3" autofocus>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="table-cap-input">Seating Capacity (Pax):</label>
                        <input type="number" class="form-input" id="table-cap-input" min="1" max="50" value="4" required placeholder="4">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="SatayApp.closeModal('table-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-table">Create Table</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Core Scripts -->
    <script src="assets/js/app.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/admin.js?v=<?php echo time(); ?>"></script>
</body>
</html>

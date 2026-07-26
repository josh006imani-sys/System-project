<?php
// header.php — This is included at the top of every staff page
// I made this so I don't have to write the same HTML and CSS on every page
// It has the top bar, sidebar menu, and basic styling

// Set page title if not already set
if (!isset($pageTitle)) $pageTitle = 'Dashboard';
if (!isset($activePage)) $activePage = 'dashboard';

// Get user info from session
$fullName = $_SESSION['full_name'] ?? 'User';
$role = $_SESSION['role'] ?? 'staff';

// Make initials for the avatar circle
$parts = explode(' ', $fullName);
$initials = strtoupper(substr($parts[0], 0, 1));
if (isset($parts[1])) {
    $initials .= strtoupper(substr($parts[1], 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($pageTitle); ?> — Muthoni's Laundry</title>
<!-- I use Google Fonts because it looks better than default Arial -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* My basic CSS variables — I learned this from a YouTube tutorial */
:root {
    --accent-1: #667eea;
    --accent-2: #764ba2;
    --sidebar-bg: #1a202c;
    --sidebar-hover: #252e3d;
    --page-bg: #f0f2f5;
    --card-bg: #ffffff;
    --border-light: #e2e8f0;
    --text-dark: #1a202c;
    --text-mid: #4a5568;
    --text-muted: #718096;
    --danger: #e53e3e;
    --success: #2f855a;
    --warning: #b7791f;
}

/* Reset everything — I copy this from every tutorial */
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Inter', sans-serif;
    background: var(--page-bg);
    color: var(--text-dark);
    min-height: 100vh;
}

a { text-decoration: none; color: inherit; }
button { font-family: inherit; }

/* ===== TOP BAR ===== */
.topbar {
    background: #fff;
    padding: 14px 28px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border-light);
    position: sticky;
    top: 0;
    z-index: 50;
}

.menu-toggle {
    display: none; /* hidden on desktop, shown on mobile */
    background: none;
    border: 1px solid var(--border-light);
    border-radius: 6px;
    padding: 6px 10px;
    font-size: 14px;
    cursor: pointer;
}

.page-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--accent-1);
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

.topbar-user {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

/* The circle with user initials */
.avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 13px;
}

.user-info { line-height: 1.25; }
.user-name { font-weight: 600; font-size: 13.5px; color: var(--text-dark); }
.user-role { font-size: 11.5px; color: var(--text-muted); }

.user-wrap { position: relative; }

/* Dropdown menu for user */
.user-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    background: #fff;
    border: 1px solid var(--border-light);
    border-radius: 8px;
    width: 190px;
    display: none;
    z-index: 60;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
}

.user-menu a {
    display: block;
    width: 100%;
    text-align: left;
    padding: 10px 14px;
    font-size: 13.5px;
    color: var(--text-mid);
    background: none;
    border: none;
    cursor: pointer;
}
.user-menu a:hover { background: #f7fafc; }
.user-menu a.danger { color: var(--danger); }

.logout-btn {
    border: 1.5px solid var(--danger);
    color: var(--danger);
    background: none;
    padding: 7px 16px;
    border-radius: 7px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
}
.logout-btn:hover { background: #fff5f5; }

/* ===== SIDEBAR ===== */
.sidebar {
    position: fixed;
    left: 0;
    top: 65px;
    bottom: 0;
    width: 220px;
    background: var(--sidebar-bg);
    overflow-y: auto;
    padding: 20px 14px;
    z-index: 40;
}

.sidebar-menu {
    list-style: none;
}

.sidebar-menu .menu-label {
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    color: #6b7280;
    letter-spacing: 0.6px;
    padding: 14px 10px 6px;
}

.sidebar-menu .menu-label:first-child { padding-top: 4px; }

.sidebar-menu li a {
    display: block;
    padding: 10px 12px;
    border-radius: 7px;
    color: #c2c8d2;
    font-weight: 500;
    font-size: 13.5px;
    margin-bottom: 2px;
}

.sidebar-menu li a:hover {
    background: var(--sidebar-hover);
    color: #fff;
}

.sidebar-menu li a.active {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

/* ===== MAIN CONTENT ===== */
.main-content {
    margin-left: 220px; /* space for sidebar */
    padding: 26px 30px;
}

.page-header { margin-bottom: 22px; }
.page-header h1 { font-size: 23px; font-weight: 800; }
.page-header p { color: var(--text-muted); margin-top: 3px; font-size: 13.5px; }

/* ===== STATS CARDS ===== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: var(--card-bg);
    border: 1px solid var(--border-light);
    border-radius: 10px;
    padding: 16px 18px;
    cursor: pointer;
    border-left: 4px solid var(--accent-1);
}

.stat-card.s-processing { border-left-color: var(--warning); }
.stat-card.s-ready { border-left-color: var(--success); }
.stat-card.s-uncollected { border-left-color: var(--accent-2); }

.stat-label {
    font-size: 12px;
    color: var(--text-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.stat-number {
    font-size: 26px;
    font-weight: 800;
    margin-top: 4px;
}

/* ===== TABLES ===== */
.table-container {
    background: var(--card-bg);
    border: 1px solid var(--border-light);
    border-radius: 10px;
    padding: 18px;
    overflow-x: auto;
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
    flex-wrap: wrap;
    gap: 10px;
}

.table-header h3 { font-size: 15.5px; font-weight: 700; }

.btn {
    padding: 9px 16px;
    border: none;
    border-radius: 7px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; }
.btn-success { background: var(--success); color: #fff; }
.btn-outline {
    background: transparent;
    color: var(--text-mid);
    border: 1.5px solid var(--border-light);
}

table { width: 100%; border-collapse: collapse; }

table thead th {
    padding: 10px 12px;
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: var(--text-mid);
    border-bottom: 2px solid var(--border-light);
}

table tbody tr { border-bottom: 1px solid #f0f2f5; }
table tbody tr:hover { background: #fafbfc; }
table tbody td { padding: 11px 12px; font-size: 13px; vertical-align: middle; }

/* Status pills — I made these small colored badges */
.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 14px;
    font-size: 11.5px;
    font-weight: 600;
}

.status-pill.received { background: #e2e8f0; color: #4a5568; }
.status-pill.washing { background: #fffbeb; color: var(--warning); }
.status-pill.ready { background: #f0fff4; color: var(--success); }
.status-pill.collected { background: #faf5ff; color: #6b46c1; }

/* Messages */
.inline-message {
    display: none;
    padding: 11px 16px;
    border-radius: 8px;
    font-size: 13.5px;
    font-weight: 500;
    margin-bottom: 16px;
    border: 1px solid transparent;
}
.inline-message.show { display: block; }
.inline-message.success { background: #f0fff4; color: var(--success); border-color: #c6f6d5; }
.inline-message.error { background: #fff5f5; color: var(--danger); border-color: #fed7d7; }

/* Forms */
.form-group { margin-bottom: 14px; }
.form-group label {
    display: block;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--text-mid);
    margin-bottom: 5px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 9px 12px;
    border: 1.5px solid var(--border-light);
    border-radius: 7px;
    font-size: 13.5px;
    font-family: 'Inter', sans-serif;
    outline: none;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus { border-color: var(--accent-1); }

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

/* Row action links */
.row-actions { display: flex; gap: 10px; }
.row-actions a {
    font-size: 12.5px;
    font-weight: 600;
    text-decoration: underline;
}
.row-actions .ready-link { color: var(--success); }
.row-actions .edit-link { color: #2b6cb0; }
.row-actions .delete-link { color: var(--danger); }

/* ===== MOBILE RESPONSIVE ===== */
@media (max-width: 768px) {
    .menu-toggle { display: inline-block; }
    .sidebar { display: none; } /* hide sidebar on mobile */
    .sidebar.open { display: block; position: fixed; left: 0; top: 65px; bottom: 0; width: 220px; }
    .main-content { margin-left: 0; padding: 18px 14px; }
    .user-info { display: none; } /* hide name on small screens */
    .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .form-row { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<!-- ===== TOP BAR ===== -->
<header class="topbar">
    <div style="display:flex; align-items:center; gap:14px;">
        <button class="menu-toggle" id="menuToggle">Menu</button>
        <div class="page-title"><?php echo htmlspecialchars($pageTitle); ?></div>
    </div>
    <div class="topbar-right">
        <div class="user-wrap">
            <div class="topbar-user" id="userMenuTrigger">
                <div class="avatar"><?php echo htmlspecialchars($initials); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($fullName); ?></div>
                    <div class="user-role"><?php echo ucfirst(htmlspecialchars($role)); ?></div>
                </div>
            </div>
            <div class="user-menu" id="userMenu">
                <a href="profile.php">Profile information</a>
                <a href="logout.php" class="danger">Logout</a>
            </div>
        </div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</header>

<!-- ===== SIDEBAR ===== -->
<nav class="sidebar" id="sidebar">
    <ul class="sidebar-menu">

    <?php if ($role === 'admin'): ?>
        <!-- ADMIN MENU — I gave admin everything because they are the boss -->
        <li class="menu-label">Main menu</li>
        <li><a href="dashboard_admin.php" class="nav-link <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">Dashboard</a></li>
        <li><a href="orders.php" class="nav-link <?php echo $activePage === 'orders' ? 'active' : ''; ?>">Orders</a></li>
        <li><a href="customers.php" class="nav-link <?php echo $activePage === 'customers' ? 'active' : ''; ?>">Customers</a></li>
        <li><a href="scan_barcode.php" class="nav-link <?php echo $activePage === 'scan_barcode' ? 'active' : ''; ?>">Scan Barcode</a></li>
        <li><a href="ready_pickup.php" class="nav-link <?php echo $activePage === 'ready_pickup' ? 'active' : ''; ?>">Ready for Pickup</a></li>

        <li class="menu-label">Management</li>
        <li><a href="services.php" class="nav-link <?php echo $activePage === 'services' ? 'active' : ''; ?>">Pricing</a></li>
      
        <li><a href="inventory.php" class="nav-link <?php echo $activePage === 'inventory' ? 'active' : ''; ?>">Inventory</a></li>
        <li><a href="payments.php" class="nav-link <?php echo $activePage === 'payments' ? 'active' : ''; ?>">Payments</a></li>
        <li><a href="delivery.php" class="nav-link <?php echo $activePage === 'delivery' ? 'active' : ''; ?>">Delivery</a></li>
        <li><a href="reports.php" class="nav-link <?php echo $activePage === 'reports' ? 'active' : ''; ?>">Reports</a></li>
        <li><a href="expenses.php" class="nav-link <?php echo $activePage === 'expenses' ? 'active' : ''; ?>">Expenses</a></li>
        
        <li><a href="users.php" class="nav-link <?php echo $activePage === 'users' ? 'active' : ''; ?>">Users</a></li>
     <li><a href="settings.php" class="nav-link <?php echo $activePage === 'users' ? 'active' : ''; ?>">settings</a></li>
     

        <li class="menu-label">Account</li>
       
        <li><a href="profile.php" class="nav-link <?php echo $activePage === 'profile' ? 'active' : ''; ?>">Profile</a></li>
        <li><a href="logout.php" class="nav-link">Logout</a></li>

    <?php elseif ($role === 'manager'): ?>
        <!-- MANAGER MENU — Manager can see most things but not everything -->
        <li class="menu-label">Main menu</li>
        <li><a href="dashboard_manager.php" class="nav-link <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">Dashboard</a></li>
        <li><a href="orders.php" class="nav-link <?php echo $activePage === 'orders' ? 'active' : ''; ?>">All Orders</a></li>
        <li><a href="customers.php" class="nav-link <?php echo $activePage === 'customers' ? 'active' : ''; ?>">Customers</a></li>

        <li class="menu-label">Management</li>
        <li><a href="services.php" class="nav-link <?php echo $activePage === 'services' ? 'active' : ''; ?>">Pricing</a></li>
        <li><a href="inventory.php" class="nav-link <?php echo $activePage === 'inventory' ? 'active' : ''; ?>">Inventory</a></li>
        <li><a href="payments.php" class="nav-link <?php echo $activePage === 'payments' ? 'active' : ''; ?>">Payments</a></li>
        <li><a href="reports.php" class="nav-link <?php echo $activePage === 'reports' ? 'active' : ''; ?>">Reports</a></li>
        <li><a href="expenses.php" class="nav-link <?php echo $activePage === 'expenses' ? 'active' : ''; ?>">Expenses</a></li>

        <li class="menu-label">Account</li>
         <li><a href="settings.php" class="nav-link <?php echo $activePage === 'users' ? 'active' : ''; ?>">settings</a></li>
        <li><a href="profile.php" class="nav-link <?php echo $activePage === 'profile' ? 'active' : ''; ?>">Profile</a></li>
        <li><a href="logout.php" class="nav-link">Logout</a></li>

    <?php elseif ($role === 'staff'): ?>
        <!-- STAFF MENU — Staff can only see basic stuff -->
        <li class="menu-label">Main menu</li>
        <li><a href="dashboard_staff.php" class="nav-link <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">Dashboard</a></li>
        <li><a href="orders.php" class="nav-link <?php echo $activePage === 'orders' ? 'active' : ''; ?>">Orders</a></li>
        <li><a href="customers.php" class="nav-link <?php echo $activePage === 'customers' ? 'active' : ''; ?>">Customers</a></li>
        <li><a href="scan_barcode.php" class="nav-link <?php echo $activePage === 'scan_barcode' ? 'active' : ''; ?>">Scan Barcode</a></li>
        <li><a href="ready_pickup.php" class="nav-link <?php echo $activePage === 'ready_pickup' ? 'active' : ''; ?>">Ready for Pickup</a></li>

 <li class="menu-label">Operations</li>
        <li><a href="delivery.php" class="nav-link <?php echo $activePage === 'delivery' ? 'active' : ''; ?>">Delivery</a></li>
        <li><a href="payments.php" class="nav-link <?php echo $activePage === 'payments' ? 'active' : ''; ?>">Payments</a></li>
        <li><a href="inventory.php" class="nav-link <?php echo $activePage === 'inventory' ? 'active' : ''; ?>">Inventory</a></li>
        <li class="menu-label">Account</li>
        
        <li><a href="profile.php" class="nav-link <?php echo $activePage === 'profile' ? 'active' : ''; ?>">Profile</a></li>
        <li><a href="logout.php" class="nav-link">Logout</a></li>

        <!-- I added this so staff can see what customers see -->
        <li><a href="customer_dashboard.php" target="_blank" class="nav-link"><i class="fas fa-external-link-alt"></i> Customer Portal</a></li>

    <?php elseif ($role === 'rider'): ?>
        <!-- RIDER MENU -->
        <li class="menu-label">Main menu</li>
        <li><a href="dashboard_rider.php" class="nav-link <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">Dashboard</a></li>
        

        <li class="menu-label">Operations</li>
        <li><a href="rider_status.php" class="nav-link <?php echo $activePage === 'rider_status' ? 'active' : ''; ?>">Update Status</a></li>
        <li><a href="location_pin.php" class="nav-link <?php echo $activePage === 'location_pin' ? 'active' : ''; ?>">Location Pin</a></li>
       

        <li class="menu-label">Account</li>
        <li><a href="profile.php" class="nav-link <?php echo $activePage === 'profile' ? 'active' : ''; ?>">Profile</a></li>
        <li><a href="notifications.php" class="nav-link <?php echo $activePage === 'notifications' ? 'active' : ''; ?>">Notifications</a></li>
        <li><a href="logout.php" class="nav-link">Logout</a></li>
    <?php endif; ?>

    </ul>
</nav>

<!-- ===== MAIN CONTENT STARTS HERE ===== -->
<main class="main-content">
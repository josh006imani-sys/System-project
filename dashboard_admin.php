<?php
// dashboard_admin.php — Admin Dashboard
// Full system overview: orders, revenue, users, services.

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

$pageTitle = 'Admin Dashboard';
$activePage = 'dashboard';

// Stats
$totalOrders = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$totalRevenue = $conn->query("SELECT SUM(total_amount) FROM orders")->fetch_row()[0] ?? 0;
$totalUsers = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$totalServices = $conn->query("SELECT COUNT(*) FROM services")->fetch_row()[0];

// Recent orders
$recentOrders = [];
$res = $conn->query("SELECT * FROM orders ORDER BY order_date DESC LIMIT 5");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $recentOrders[] = $row;
    }
}

function formatMoney($amount) {
    return 'Ksh ' . number_format($amount, 2);
}
function formatDate($date) {
    return date('Y-m-d H:i', strtotime($date));
}

include 'header.php';
?>

<div style="margin-bottom: 20px;">
    <h1 style="font-size: 24px; margin-bottom: 5px;">Admin Dashboard</h1>
    <p style="color: #666; font-size: 14px;">Full system overview and control</p>
</div>

<!-- Stats -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
    <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea;">
        <p style="margin: 0; font-size: 12px; color: #666;">Total Orders</p>
        <p style="margin: 5px 0 0 0; font-size: 28px; font-weight: bold;"><?php echo $totalOrders; ?></p>
    </div>
    <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #34a853;">
        <p style="margin: 0; font-size: 12px; color: #666;">Total Revenue</p>
        <p style="margin: 5px 0 0 0; font-size: 28px; font-weight: bold;"><?php echo formatMoney($totalRevenue); ?></p>
    </div>
    <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #f9ab00;">
        <p style="margin: 0; font-size: 12px; color: #666;">System Users</p>
        <p style="margin: 5px 0 0 0; font-size: 28px; font-weight: bold;"><?php echo $totalUsers; ?></p>
    </div>
    <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #ea4335;">
        <p style="margin: 0; font-size: 12px; color: #666;">Services</p>
        <p style="margin: 5px 0 0 0; font-size: 28px; font-weight: bold;"><?php echo $totalServices; ?></p>
    </div>
</div>

<!-- Recent Orders -->
<div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h3 style="margin: 0; font-size: 16px;">Recent Orders</h3>
        <a href="orders.php" style="background: #667eea; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 13px;">View All</a>
    </div>
    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
        <thead>
            <tr style="background: #f5f5f5;">
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Order ID</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Customer</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Status</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Total</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentOrders as $o): ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px;"><strong>#<?php echo $o['id']; ?></strong></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($o['customer_name']); ?></td>
                <td style="padding: 10px;">
                    <?php 
                    $color = '';
                    if ($o['status'] === 'received') $color = '#888';
                    elseif ($o['status'] === 'washing') $color = '#f9ab00';
                    elseif ($o['status'] === 'ready') $color = '#34a853';
                    else $color = '#667eea';
                    ?>
                    <span style="background: <?php echo $color; ?>20; color: <?php echo $color; ?>; padding: 3px 10px; border-radius: 10px; font-size: 11px; font-weight: bold;">
                        <?php echo ucfirst($o['status']); ?>
                    </span>
                </td>
                <td style="padding: 10px;"><?php echo formatMoney($o['total_amount']); ?></td>
                <td style="padding: 10px; color: #666;"><?php echo formatDate($o['order_date']); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentOrders)): ?>
            <tr><td colspan="5" style="padding: 30px; text-align: center; color: #999;">No orders yet</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>
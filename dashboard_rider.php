<?php
// dashboard_rider.php — Rider / Delivery Dashboard
// Focused on delivery execution: assigned deliveries, status updates, location

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'rider') {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

$pageTitle = 'Dashboard';
$activePage = 'dashboard';

$riderId = (int)$_SESSION['user_id'];

// ============================================
// "Out for Delivery" Stat
// ============================================
$outForDelivery = 0;
try {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM orders 
        WHERE delivery_mode = 'delivery' 
        AND delivery_status = 'out_for_delivery'
        AND (assigned_rider = ? OR assigned_rider IS NULL)
    ");
    $stmt->bind_param("i", $riderId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $outForDelivery = (int)$row['total'];
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Rider Dashboard - Out for Delivery error: " . $e->getMessage());
    $outForDelivery = 0;
}

// ============================================
// FIX 3: "Delivered Today" Stat
// ============================================
$deliveredToday = 0;
try {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM orders 
        WHERE delivery_mode = 'delivery' 
        AND delivery_status = 'delivered' 
        AND DATE(updated_at) = CURDATE()
        AND (assigned_rider = ? OR assigned_rider IS NULL)
    ");
    $stmt->bind_param("i", $riderId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $deliveredToday = (int)$row['total'];
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Rider Dashboard - Delivered Today error: " . $e->getMessage());
    $deliveredToday = 0;
}

// ============================================
// Delivery Orders List
// ============================================
$deliveryOrders = [];
try {
    $stmt = $conn->prepare("
        SELECT * FROM orders 
        WHERE delivery_mode = 'delivery' 
        AND delivery_status = 'out_for_delivery'
        AND (assigned_rider = ? OR assigned_rider IS NULL)
        ORDER BY order_date ASC 
        LIMIT 5
    ");
    $stmt->bind_param("i", $riderId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $deliveryOrders[] = $row;
        }
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Rider Dashboard - Delivery Orders error: " . $e->getMessage());
    $deliveryOrders = [];
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
    <h1 style="font-size: 24px; margin-bottom: 5px;">Rider Dashboard</h1>
    <p style="color: #666; font-size: 14px;">Delivery execution overview</p>
</div>

<!-- Stats Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 25px;">
    <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #f9ab00;">
        <p style="margin: 0; font-size: 12px; color: #666;">Out for Delivery</p>
        <p style="margin: 5px 0 0 0; font-size: 28px; font-weight: bold;"><?php echo $outForDelivery; ?></p>
    </div>
    <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #34a853;">
        <p style="margin: 0; font-size: 12px; color: #666;">Delivered Today</p>
        <p style="margin: 5px 0 0 0; font-size: 28px; font-weight: bold;"><?php echo $deliveredToday; ?></p>
    </div>
</div>

<!-- Quick Actions -->
<div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ddd;">
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="rider_status.php" style="background: #f9ab00; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-size: 14px;"><i class="fas fa-sync-alt"></i> Update Status</a>
        <a href="location_pin.php" style="background: #764ba2; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-size: 14px;"><i class="fas fa-map-marker-alt"></i> Location Pin</a>
    </div>
</div>

<!-- Delivery Orders -->
<div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h3 style="margin: 0; font-size: 16px;">Delivery Orders</h3>
        <a href="rider_status.php" style="background: #667eea; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 13px;">View All</a>
    </div>
    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
        <thead>
            <tr style="background: #f5f5f5;">
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Order #</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Customer</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Address</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Status</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Total</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Order Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($deliveryOrders as $o): ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px;"><strong>#<?php echo $o['id']; ?></strong></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($o['customer_name']); ?></td>
                <td style="padding: 10px; font-size: 12px;"><?php echo nl2br(htmlspecialchars($o['delivery_address'] ?: '-')); ?></td>
                <td style="padding: 10px;">
                    <?php 
                    $dStatus = $o['delivery_status'] ?? 'pending';
                    $color = $dStatus === 'out_for_delivery' ? '#2b6cb0' : ($dStatus === 'delivered' ? '#34a853' : '#b7791f');
                    ?>
                    <span style="background: <?php echo $color; ?>20; color: <?php echo $color; ?>; padding: 3px 10px; border-radius: 10px; font-size: 11px; font-weight: bold;">
                        <?php echo ucfirst(str_replace('_', ' ', $dStatus)); ?>
                    </span>
                </td>
                <td style="padding: 10px;"><?php echo formatMoney($o['total_amount']); ?></td>
                <td style="padding: 10px; color: #666; font-size: 12px;"><?php echo formatDate($o['order_date']); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($deliveryOrders)): ?>
            <tr><td colspan="6" style="padding: 30px; text-align: center; color: #999;">No delivery orders assigned to you</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>
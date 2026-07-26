<?php
// delivery.php — I made this so manager/admin can manage deliveries
// See which orders are for pickup vs delivery and update their status

session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'manager' && $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

$pageTitle = 'Delivery';
$activePage = 'delivery';

$message = '';
$messageType = '';

// Mark as out for delivery
if (isset($_GET['out']) && is_numeric($_GET['out'])) {
    $id = (int)$_GET['out'];
    $conn->query("UPDATE orders SET delivery_status = 'out_for_delivery', updated_at = NOW() WHERE id = $id");
    $message = 'Order marked as Out for Delivery.';
    $messageType = 'success';
}

// Mark as delivered
if (isset($_GET['delivered']) && is_numeric($_GET['delivered'])) {
    $id = (int)$_GET['delivered'];
    $conn->query("UPDATE orders SET delivery_status = 'delivered', status = 'collected', updated_at = NOW() WHERE id = $id");
    $message = 'Order marked as Delivered.';
    $messageType = 'success';
}
// Delete delivery order
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM order_items WHERE order_id = $id");
    $conn->query("DELETE FROM orders WHERE id = $id");
    $message = 'Order deleted.';
    $messageType = 'success';
}

// Get delivery orders
$deliveryOrders = [];
$res = $conn->query("SELECT * FROM orders WHERE delivery_mode = 'delivery' ORDER BY 
    CASE status WHEN 'ready' THEN 1 WHEN 'washing' THEN 2 WHEN 'received' THEN 3 ELSE 4 END,
    CASE delivery_status WHEN 'pending' THEN 1 WHEN 'out_for_delivery' THEN 2 WHEN 'delivered' THEN 3 ELSE 4 END,
    order_date DESC");
while ($row = $res->fetch_assoc()) $deliveryOrders[] = $row;

// Get pickup orders
$pickupOrders = [];
$res = $conn->query("SELECT * FROM orders WHERE delivery_mode = 'pickup' OR delivery_mode IS NULL ORDER BY 
    CASE status WHEN 'ready' THEN 1 WHEN 'washing' THEN 2 WHEN 'received' THEN 3 ELSE 4 END, order_date DESC");
while ($row = $res->fetch_assoc()) $pickupOrders[] = $row;

function formatMoney($amount) {
    return 'Ksh ' . number_format($amount, 2);
}
function formatDate($date) {
    return date('M d, Y H:i', strtotime($date));
}

include 'header.php';
?>

<div class="page-header">
    <h1>Delivery & Pickup</h1>
    <p>Manage deliveries and customer pickups.</p>
</div>

<?php if ($message): ?>
<div class="inline-message show <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<!-- Delivery Orders -->
<section class="table-container" style="margin-bottom:24px;">
    <div class="table-header">
        <h3>Delivery Orders</h3>
        <span style="font-size:12px;color:#718096;"><?php echo count($deliveryOrders); ?> total</span>
    </div>
    <table>
        <thead>
            <tr><th>Order #</th><th>Customer</th><th>Phone</th><th>Address</th><th>Total</th><th>Status</th><th>Delivery</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($deliveryOrders as $o): 
                $dStatus = $o['delivery_status'] ?? 'pending';
                if ($dStatus === 'pending') $badge = '<span style="background:#fffbeb;color:#b7791f;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:700;">Pending</span>';
                elseif ($dStatus === 'out_for_delivery') $badge = '<span style="background:#ebf4ff;color:#2b6cb0;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:700;">Out</span>';
                elseif ($dStatus === 'delivered') $badge = '<span style="background:#f0fff4;color:#2f855a;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:700;">Done</span>';
                else $badge = '<span style="background:#e2e8f0;color:#718096;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:700;">-</span>';
            ?>
            <tr>
                <td><strong>#<?php echo $o['id']; ?></strong></td>
                <td><?php echo htmlspecialchars($o['customer_name']); ?></td>
                <td><?php echo htmlspecialchars($o['phone']); ?></td>
                <td style="font-size:12px;max-width:200px;"><?php echo nl2br(htmlspecialchars($o['delivery_address'] ?: '-')); ?></td>
                <td><?php echo formatMoney($o['total_amount']); ?></td>
                <td><span class="status-pill <?php echo $o['status']; ?>"><?php echo ucfirst($o['status']); ?></span></td>
                <td><?php echo $badge; ?></td>
                <td>
                   <div class="row-actions">
    <?php if ($o['status'] === 'ready' && $dStatus === 'pending'): ?>
    <a href="delivery.php?out=<?php echo $o['id']; ?>" class="edit-link" onclick="return confirm('Mark as Out for Delivery?')">Send Out</a>
    <?php elseif ($dStatus === 'out_for_delivery'): ?>
    <a href="delivery.php?delivered=<?php echo $o['id']; ?>" class="ready-link" onclick="return confirm('Confirm delivery?')">Mark Delivered</a>
    <?php elseif ($dStatus === 'delivered'): ?>
    <span style="color:#48bb78;font-size:12px;font-weight:600;">Complete</span>
    <?php else: ?>
    <span style="color:#a0aec0;font-size:12px;">Waiting...</span>
    <?php endif; ?>
    <a href="order_view.php?id=<?php echo $o['id']; ?>" style="color:#718096;font-size:12px;">View</a>
    <a href="delivery.php?delete=<?php echo $o['id']; ?>" style="color:#ea4335;font-size:12px;text-decoration:none;" onclick="return confirm('Delete this order?')">Delete</a>
</div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($deliveryOrders)): ?>
            <tr><td colspan="8" style="text-align:center;padding:40px;color:#a0aec0;">No delivery orders yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<!-- Pickup Orders -->
<section class="table-container">
    <div class="table-header">
        <h3>Customer Pickups</h3>
        <span style="font-size:12px;color:#718096;"><?php echo count($pickupOrders); ?> total</span>
    </div>
    <table>
        <thead>
            <tr><th>Order #</th><th>Customer</th><th>Phone</th><th>Total</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($pickupOrders as $o): ?>
            <tr>
                <td><strong>#<?php echo $o['id']; ?></strong></td>
                <td><?php echo htmlspecialchars($o['customer_name']); ?></td>
                <td><?php echo htmlspecialchars($o['phone']); ?></td>
                <td><?php echo formatMoney($o['total_amount']); ?></td>
                <td><span class="status-pill <?php echo $o['status']; ?>"><?php echo ucfirst($o['status']); ?></span></td>
                <td><a href="order_view.php?id=<?php echo $o['id']; ?>" style="color:#2b6cb0;font-size:12px;font-weight:600;">View</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($pickupOrders)): ?>
            <tr><td colspan="6" style="text-align:center;padding:40px;color:#a0aec0;">No pickup orders yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<?php include 'footer.php'; ?>
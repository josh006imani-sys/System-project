<?php
// orders.php — Order Management Page
// I made this page so staff can see all orders and manage them
// You can search by customer name or order ID, and filter by status

session_start();

// Only staff/manager/admin manage the full order list.
// Riders have their own scoped view (rider_status.php) and should not
// land here — they don't need order editing/deleting.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['role'] === 'rider') {
    header('Location: dashboard_rider.php');
    exit;
}

require_once 'db_config.php';
// Delete order
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM order_items WHERE order_id = $id");
    $conn->query("DELETE FROM orders WHERE id = $id");
    $message = 'Order deleted.';
    $messageType = 'success';
}

$pageTitle = 'Orders';
$activePage = 'orders';

// Get filter values from URL
$statusFilter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build the SQL query
$sql = "SELECT * FROM orders WHERE 1=1";

// Add status filter if not 'all'
if ($statusFilter !== 'all') {
    $sql .= " AND status = '" . $conn->real_escape_string($statusFilter) . "'";
}

// Add search filter
if (!empty($search)) {
    $searchTerm = $conn->real_escape_string("%$search%");
    $sql .= " AND (customer_name LIKE '$searchTerm' OR id LIKE '$searchTerm')";
}

// Order by date, newest first
$sql .= " ORDER BY order_date DESC";

// Run query
$result = $conn->query($sql);
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

// Helper function to format money
function formatMoney($amount) {
    return 'Ksh ' . number_format($amount, 2);
}

// Turns a raw DB id (e.g. 6) into the display code ORD-1006.
// Kept identical to the version in barcode_scan.php so codes match everywhere.
function formatOrderId($id) {
    return 'ORD-' . (1000 + (int)$id);
}

// Helper function to format date
function formatDate($date) {
    return date('Y-m-d H:i', strtotime($date));
}

include 'header.php';
?>

<div class="page-header">
    <h1>Orders</h1>
    <p>Manage all laundry orders</p>
</div>

<div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ddd;">
    <form method="GET" action="" style="display: flex; gap: 10px; flex-wrap: wrap;">
        <input type="text" name="search" placeholder="Search orders..." value="<?php echo htmlspecialchars($search); ?>" 
               style="flex: 1; min-width: 200px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">

        <select name="status" onchange="this.form.submit()" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
            <option value="received" <?php echo $statusFilter === 'received' ? 'selected' : ''; ?>>Received</option>
            <option value="washing" <?php echo $statusFilter === 'washing' ? 'selected' : ''; ?>>Washing</option>
            <option value="ready" <?php echo $statusFilter === 'ready' ? 'selected' : ''; ?>>Ready</option>
            <option value="collected" <?php echo $statusFilter === 'collected' ? 'selected' : ''; ?>>Collected</option>
        </select>

        <button type="submit" style="background: #667eea; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">Search</button>
    </form>
</div>

<div style="margin-bottom: 15px;">
    <a href="order_form.php" style="background: #34a853; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-size: 14px; display: inline-block;">
        <i class="fas fa-plus"></i> New Order
    </a>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd; overflow-x: auto;">
    <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 16px;">All Orders</h3>

    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
        <thead>
            <tr style="background: #f5f5f5;">
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Order #</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Customer</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Phone</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Total</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Status</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Payment</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Date</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $o): ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px;"><strong><?php echo htmlspecialchars(formatOrderId($o['id'])); ?></strong></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($o['customer_name']); ?></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($o['phone']); ?></td>
                <td style="padding: 10px;"><?php echo formatMoney($o['total_amount']); ?></td>
                <td style="padding: 10px;">
                    <?php 
                    // I set colors based on status
                    $statusColor = '';
                    if ($o['status'] === 'received') $statusColor = '#888';
                    elseif ($o['status'] === 'washing') $statusColor = '#f9ab00';
                    elseif ($o['status'] === 'ready') $statusColor = '#34a853';
                    else $statusColor = '#667eea';
                    ?>
                    <span style="background: <?php echo $statusColor; ?>20; color: <?php echo $statusColor; ?>; padding: 3px 10px; border-radius: 10px; font-size: 11px; font-weight: bold;">
                        <?php echo ucfirst($o['status']); ?>
                    </span>
                </td>
                <td style="padding: 10px;">
                    <?php if ($o['paid']): ?>
                        <span style="font-size:11px;color:#2f855a;font-weight:600;"><?php echo ucfirst($o['payment_method'] ?? 'Cash'); ?></span>
                    <?php else: ?>
                        <span style="font-size:11px;color:#b7791f;">Unpaid</span>
                    <?php endif; ?>
                </td>
                <td style="padding: 10px; color: #666;"><?php echo formatDate($o['order_date']); ?></td>
               <td style="padding: 10px;">
    <a href="order_view.php?id=<?php echo $o['id']; ?>" style="color: #667eea; text-decoration: none; font-size: 12px; margin-right: 10px;">View</a>
    <a href="order_form.php?edit=<?php echo $o['id']; ?>" style="color: #667eea; text-decoration: none; font-size: 12px; margin-right: 10px;">Edit</a>
    <a href="barcode_scan.php?order=<?php echo urlencode(formatOrderId($o['id'])); ?>" style="color: #667eea; text-decoration: none; font-size: 12px; margin-right: 10px;">Barcode</a>
    <a href="orders.php?delete=<?php echo $o['id']; ?>" style="color: #ea4335; text-decoration: none; font-size: 12px;" onclick="return confirm('Delete this order? This cannot be undone.')">Delete</a>
</td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?>
            <tr>
                <td colspan="8" style="padding: 30px; text-align: center; color: #999;">No orders found</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>
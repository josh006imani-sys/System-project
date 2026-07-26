<?php
// ready_pickup.php — I made this so staff can quickly see ready orders
// And mark them as collected when the customer comes

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

$pageTitle = 'Ready for Pickup';
$activePage = 'ready_pickup';

$message = '';
$messageType = '';

// Mark as collected with payment method
if (isset($_GET['collect']) && is_numeric($_GET['collect'])) {
    $id = (int)$_GET['collect'];
    $payMethod = $_GET['method'] ?? 'cash';
    $payMethod = in_array($payMethod, ['cash', 'mpesa']) ? $payMethod : 'cash';

    // Update order status
    $conn->query("UPDATE orders SET status = 'collected', paid = 1, collected_at = NOW() WHERE id = $id");

    // Update order payment method
    $conn->query("UPDATE orders SET payment_method = '$payMethod' WHERE id = $id");

    // === RECORD REAL REVENUE: If there was a pending payment, mark it as paid ===
    $conn->query("UPDATE payments SET payment_status = 'paid', notes = CONCAT(notes, ' | Collected and paid on ', NOW()) WHERE order_id = $id AND payment_status = 'pending' AND payment_method = '$payMethod'");

    // Record payment based on method selected
    $checkPay = $conn->query("SELECT id FROM payments WHERE order_id = $id AND payment_status = 'paid' LIMIT 1");
    if ($checkPay->num_rows == 0) {
        $methodLabel = $payMethod === 'mpesa' ? 'M-Pesa' : 'Cash';
        $orderRes = $conn->query("SELECT customer_name, total_amount FROM orders WHERE id = $id");
        if ($orderRes && $orderRes->num_rows > 0) {
            $o = $orderRes->fetch_assoc();
            $cName = $conn->real_escape_string($o['customer_name'] ?? 'Walk-in');
            $amount = (float)$o['total_amount'];
            $staffId = (int)$_SESSION['user_id'];
            
            // Using prepared statement for security as suggested in your prompt rules
            $stmt = $conn->prepare("INSERT INTO payments 
                (order_id, customer_name, amount, payment_method, payment_status, transaction_ref, processed_by, notes, created_at) 
                VALUES (?, ?, ?, '$payMethod', 'paid', ?, ?, 'Paid on collection ($methodLabel)', NOW())");
            
            $ref = strtoupper($payMethod) . '-PICKUP-' . $id;
            $stmt->bind_param("isdsi", $id, $cName, $amount, $ref, $staffId);
            $stmt->execute();
            $stmt->close();
        }
    }

    $message = 'Order marked as collected. Payment recorded.';
    $messageType = 'success';
}

// Get ready orders
$result = $conn->query("SELECT * FROM orders WHERE status = 'ready' ORDER BY order_date DESC");
$orders = [];
while ($row = $result->fetch_assoc()) $orders[] = $row;

function formatMoney($amount) {
    return 'Ksh ' . number_format($amount, 2);
}
function formatDate($date) {
    return date('Y-m-d H:i', strtotime($date));
}

include 'header.php';
?>

<div class="page-header">
    <h1>Ready for Pickup</h1>
    <p>Orders that are ready to be collected by customers.</p>
</div>

<?php if ($message): ?>
<div class="inline-message show <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<section class="table-container">
    <div class="table-header">
        <h3>Ready Orders</h3>
        <span style="font-size:12px;color:#718096;"><?php echo count($orders); ?> orders waiting</span>
    </div>
    <table>
        <thead><tr><th>Order ID</th><th>Customer</th><th>Phone</th><th>Total</th><th>Ready Since</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td><strong>#<?php echo $o['id']; ?></strong></td>
                <td><?php echo htmlspecialchars($o['customer_name']); ?></td>
                <td><?php echo htmlspecialchars($o['phone']); ?></td>
                <td><?php echo formatMoney($o['total_amount']); ?></td>
                <td><?php echo formatDate($o['updated_at']); ?></td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <a href="ready_pickup.php?collect=<?php echo $o['id']; ?>&method=cash" class="btn btn-success" style="font-size:11px;padding:5px 10px;text-decoration:none;" onclick="return confirm('Collect and record CASH payment?')"><i class="fas fa-money-bill-wave"></i> Cash</a>
                        <a href="ready_pickup.php?collect=<?php echo $o['id']; ?>&method=mpesa" class="btn btn-primary" style="font-size:11px;padding:5px 10px;text-decoration:none;" onclick="return confirm('Collect and record M-PESA payment?')"><i class="fas fa-mobile-alt"></i> M-Pesa</a>
                        <a href="order_view.php?id=<?php echo $o['id']; ?>" class="edit-link" style="font-size:11px;">View</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?>
            <tr><td colspan="6" style="text-align:center;padding:40px;color:#a0aec0;">No orders ready for pickup.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<?php include 'footer.php'; ?>
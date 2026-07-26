<?php
// rider_status.php — Rider Status Update Page
// Riders can: Mark Delivered → Confirm Payment
// Sending an order "out for delivery" is a staff action (see delivery.php).
// Riders only work orders that staff has already sent out, and only
// delivery-mode orders — shop self-pickup belongs to ready_pickup.php.

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'rider') {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

$pageTitle = 'Status Update';
$activePage = 'rider_status';

$message = '';
$messageType = '';

$riderId = (int)$_SESSION['user_id'];



// ============================================
// ACTION 2: Mark as Delivered (Handed to Customer)
// ============================================
if (isset($_POST['mark_delivered']) && is_numeric($_POST['order_id'])) {
    $id = (int)$_POST['order_id'];
    $receivedBy = $conn->real_escape_string(trim($_POST['received_by'] ?? 'Customer'));
    $notes = $conn->real_escape_string(trim($_POST['delivery_notes'] ?? ''));
    
    // Update order status. Orders sent out by staff are unassigned by default
    // (pool model), so any rider working the pool can complete one — this
    // also credits the completing rider by setting assigned_rider now.
    $stmt = $conn->prepare("UPDATE orders SET 
        delivery_status = 'delivered', 
        status = 'collected', 
        assigned_rider = ?,
        updated_at = NOW() 
        WHERE id = ? AND (assigned_rider = ? OR assigned_rider IS NULL) AND delivery_status = 'out_for_delivery'");
    $stmt->bind_param("iii", $riderId, $id, $riderId);
    $stmt->execute();
    $stmt->close();
    
    // Record delivery proof
    $stmt = $conn->prepare("INSERT INTO delivery_proof 
        (order_id, rider_id, proof_type, proof_data, received_by) 
        VALUES (?, ?, 'note', ?, ?)");
    $stmt->bind_param("iiss", $id, $riderId, $notes, $receivedBy);
    $stmt->execute();
    $stmt->close();
    
    $message = 'Order #' . $id . ' marked as Delivered to ' . htmlspecialchars($receivedBy) . '.';
    $messageType = 'success';
}

// ============================================
// ACTION 3: Confirm Payment - Cash or M-Pesa only
// ============================================
if (isset($_POST['confirm_payment']) && is_numeric($_POST['order_id'])) {
    $id = (int)$_POST['order_id'];
    $paymentMethod = $conn->real_escape_string($_POST['payment_method'] ?? 'cash');
    $amount = (float)($_POST['payment_amount'] ?? 0);
    $mpesaCode = strtoupper($conn->real_escape_string(trim($_POST['mpesa_code'] ?? '')));
    $notes = $conn->real_escape_string(trim($_POST['payment_notes'] ?? ''));
    
    // Validate M-Pesa code if M-Pesa selected
    if ($paymentMethod === 'mpesa' && empty($mpesaCode)) {
        $message = 'Please enter the M-Pesa confirmation code.';
        $messageType = 'error';
    } else {
        // Get order details
        $stmt = $conn->prepare("SELECT customer_name, total_amount, paid FROM orders WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $orderRes = $stmt->get_result();
        $orderData = $orderRes->fetch_assoc();
        $stmt->close();
        
        if ($orderData && !$orderData['paid']) {
            $cName = $conn->real_escape_string($orderData['customer_name'] ?? 'Walk-in');
            $actualAmount = $amount > 0 ? $amount : (float)$orderData['total_amount'];
            $transRef = ($paymentMethod === 'mpesa' && $mpesaCode) ? $mpesaCode : 'CASH-' . $id . '-' . time();
            
            // Record payment
            $stmt = $conn->prepare("INSERT INTO payments 
                (order_id, customer_name, amount, payment_method, payment_status, transaction_ref, processed_by, notes, created_at) 
                VALUES (?, ?, ?, ?, 'paid', ?, ?, ?, NOW())");
            $stmt->bind_param("isdssis", $id, $cName, $actualAmount, $paymentMethod, $transRef, $riderId, $notes);
            $stmt->execute();
            $stmt->close();
            
            // Update order with payment details
            $mpesaCodeEsc = $conn->real_escape_string($mpesaCode);
            $conn->query("UPDATE orders SET paid = 1, payment_method = '$paymentMethod', mpesa_code = " . ($mpesaCode ? "'$mpesaCodeEsc'" : "NULL") . " WHERE id = $id");
            
            $message = 'Payment confirmed: ' . ucfirst($paymentMethod) . ' Ksh ' . number_format($actualAmount, 2);
            $messageType = 'success';
        } else {
            $message = 'Order is already paid or not found.';
            $messageType = 'error';
        }
    }
}

// ============================================
// FETCH ORDERS (with rider filtering)
// ============================================

// Out for delivery orders (need payment confirmation)
$outForDeliveryOrders = [];
$stmt = $conn->prepare("SELECT o.*, 
    (SELECT COUNT(*) FROM payments p WHERE p.order_id = o.id AND p.payment_status = 'paid') as has_paid_payment
    FROM orders o 
    WHERE o.delivery_mode = 'delivery' 
    AND o.delivery_status = 'out_for_delivery'
    AND (o.assigned_rider = ? OR o.assigned_rider IS NULL)
    ORDER BY o.order_date DESC");
$stmt->bind_param("i", $riderId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $outForDeliveryOrders[] = $row;
$stmt->close();

function formatMoney($amount) {
    return 'Ksh ' . number_format($amount, 2);
}
function formatDate($date) {
    return date('M d, Y H:i', strtotime($date));
}

include 'header.php';
?>

<style>
.delivery-form {
    background: #f7fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px;
    margin-top: 8px;
}
.delivery-form input, .delivery-form select, .delivery-form textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    margin-bottom: 8px;
    font-family: inherit;
}
.delivery-form button {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
}
.btn-deliver { background: #48bb78; color: white; }
.btn-pay { background: #667eea; color: white; }
.btn-cancel { background: #e2e8f0; color: #4a5568; }

.payment-form {
    background: #ebf4ff;
    border: 1px solid #bee3f8;
    border-radius: 8px;
    padding: 12px;
    margin-top: 8px;
}
.payment-form select, .payment-form input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    margin-bottom: 8px;
}

.row-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
</style>

<div class="page-header">
    <h1>Status Update</h1>
    <p>Update delivery status, confirm handoff, and record payments.</p>
</div>

<?php if ($message): ?>
<div class="inline-message show <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<section class="table-container" style="margin-bottom:24px;">
    <div class="table-header">
        <h3><i class="fas fa-shipping-fast" style="color:#f9ab00;"></i> Out for Delivery</h3>
        <span style="font-size:12px;color:#718096;"><?php echo count($outForDeliveryOrders); ?> orders</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($outForDeliveryOrders as $o): 
                $isPaid = !empty($o['paid']) || $o['has_paid_payment'] > 0;
            ?>
            <tr>
                <td><strong>#<?php echo $o['id']; ?></strong></td>
                <td><?php echo htmlspecialchars($o['customer_name']); ?></td>
                <td><?php echo htmlspecialchars($o['phone']); ?></td>
                <td style="font-size:12px;max-width:180px;"><?php echo nl2br(htmlspecialchars($o['delivery_address'] ?: '-')); ?></td>
                <td><?php echo formatMoney($o['total_amount']); ?></td>
                <td>
                    <span style="padding:3px 10px;border-radius:10px;font-size:11px;font-weight:bold;<?php echo $isPaid ? 'background:#f0fff4;color:#2f855a;' : 'background:#fffbeb;color:#b7791f;' ?>">
                        <?php echo $isPaid ? 'Paid' : 'Pending'; ?>
                    </span>
                </td>
                <td>
                    <div class="row-actions">
                        <button onclick="document.getElementById('deliverForm<?php echo $o['id']; ?>').style.display='block'" 
                                class="btn btn-success" style="font-size:12px;padding:6px 12px;">
                            <i class="fas fa-handshake"></i> Mark Delivered
                        </button>
                        
                        <?php if (!$isPaid): ?>
                        <button onclick="document.getElementById('payForm<?php echo $o['id']; ?>').style.display='block'" 
                                class="btn btn-primary" style="font-size:12px;padding:6px 12px;">
                            <i class="fas fa-money-bill-wave"></i> Record Payment
                        </button>
                        <?php endif; ?>
                        
                        <a href="order_view.php?id=<?php echo $o['id']; ?>" style="color:#718096;font-size:12px;">View</a>
                    </div>
                    
                    <div id="deliverForm<?php echo $o['id']; ?>" class="delivery-form" style="display:none;">
                        <form method="POST" action="">
                            <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                            <label style="font-size:12px;font-weight:600;">Who received the order?</label>
                            <input type="text" name="received_by" placeholder="Customer name or 'Security'" required>
                            <label style="font-size:12px;font-weight:600;">Delivery Notes (optional)</label>
                            <textarea name="delivery_notes" rows="2" placeholder="e.g. Left with neighbor, Gate A"></textarea>
                            <div style="display:flex;gap:8px;">
                                <button type="submit" name="mark_delivered" class="btn-deliver">
                                    <i class="fas fa-check"></i> Confirm Delivered
                                </button>
                                <button type="button" onclick="document.getElementById('deliverForm<?php echo $o['id']; ?>').style.display='none'" class="btn-cancel">Cancel</button>
                            </div>
                        </form>
                    </div>
                    
                    <?php if (!$isPaid): ?>
                    <div id="payForm<?php echo $o['id']; ?>" class="payment-form" style="display:none;">
                        <form method="POST" action="">
                            <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                            
                            <label style="font-size:12px;font-weight:600;">How did customer pay?</label>
                            <select name="payment_method" onchange="toggleMpesa(this, <?php echo $o['id']; ?>)" required style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;margin-bottom:10px;">
                                <option value="cash">Cash</option>
                                <option value="mpesa">M-Pesa</option>
                            </select>
                            
                            <label style="font-size:12px;font-weight:600;">Amount Received (Ksh)</label>
                            <input type="number" name="payment_amount" step="0.01" value="<?php echo $o['total_amount']; ?>" required style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;margin-bottom:10px;">
                            
                            <div id="mpesaField<?php echo $o['id']; ?>" style="display:none;">
                                <label style="font-size:12px;font-weight:600;">M-Pesa Confirmation Code *</label>
                                <input type="text" name="mpesa_code" placeholder="e.g. QKJ7H8JKL9" style="text-transform:uppercase;width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;margin-bottom:10px;" maxlength="20">
                            </div>
                            
                            <label style="font-size:12px;font-weight:600;">Notes (optional)</label>
                            <input type="text" name="payment_notes" placeholder="Any notes" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;margin-bottom:10px;">
                            
                            <div style="display:flex;gap:8px;">
                                <button type="submit" name="confirm_payment" class="btn-pay" style="background:#667eea;color:white;border:none;padding:8px 16px;border-radius:4px;font-weight:600;cursor:pointer;">
                                    <i class="fas fa-check"></i> Confirm Payment
                                </button>
                                <button type="button" onclick="document.getElementById('payForm<?php echo $o['id']; ?>').style.display='none'" class="btn-cancel" style="background:#e2e8f0;color:#4a5568;border:none;padding:8px 16px;border-radius:4px;cursor:pointer;">Cancel</button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($outForDeliveryOrders)): ?>
            <tr><td colspan="7" style="text-align:center;padding:40px;color:#a0aec0;">No orders out for delivery.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<script>
function toggleMpesa(select, orderId) {
    const mpesaField = document.getElementById('mpesaField' + orderId);
    mpesaField.style.display = select.value === 'mpesa' ? 'block' : 'none';
}
</script>

<?php include 'footer.php'; ?>
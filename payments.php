<?php
// payments.php — Payment Management (Manual M-Pesa Verification)
// Managers and admins can see pending payments, verify M-Pesa transactions,
// and manually mark orders as paid. No API integration needed.

session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'manager' && $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

$pageTitle = 'Payments';
$activePage = 'payments';

$message = '';
$messageType = '';

// Mark pending M-Pesa payment as paid (staff verified)
if (isset($_GET['verify']) && is_numeric($_GET['verify'])) {
    $paymentId = (int)$_GET['verify'];
    
    // Get payment details
    $payRes = $conn->query("SELECT * FROM payments WHERE id = $paymentId");
    if ($payRes && $payRes->num_rows > 0) {
        $payment = $payRes->fetch_assoc();
        $orderId = $payment['order_id'];
        
        // Update payment status
        $conn->query("UPDATE payments SET payment_status = 'paid', processed_by = {$_SESSION['user_id']}, notes = CONCAT(notes, ' | Verified by staff on ', NOW()) WHERE id = $paymentId");
        
        // Mark order as paid
        $conn->query("UPDATE orders SET paid = 1, payment_method = 'mpesa' WHERE id = $orderId");
        
        $message = 'M-Pesa payment verified and order marked as paid.';
        $messageType = 'success';
    }
}

// Mark pending payment as failed/rejected. The reason is always the same
// fixed message — staff verify against their own M-Pesa app and reject if
// it doesn't match; they don't type free text here.
if (isset($_GET['reject']) && is_numeric($_GET['reject'])) {
    $paymentId = (int)$_GET['reject'];
    $conn->query("UPDATE payments SET payment_status = 'failed', notes = CONCAT(notes, ' | Rejected: Invalid Payment Code') WHERE id = $paymentId");
    $message = 'Payment rejected. The customer will see this the next time they open their payment page.';
    $messageType = 'success';
}
// Delete payment record
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM payments WHERE id = $id");
    $message = 'Payment record deleted.';
    $messageType = 'success';
}

// Record manual payment (cash, card, bank)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $method = $conn->real_escape_string($_POST['payment_method'] ?? 'cash');
    $status = $conn->real_escape_string($_POST['payment_status'] ?? 'paid');
    $ref = $conn->real_escape_string($_POST['transaction_ref'] ?? '');
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');
    
    $orderRes = $conn->query("SELECT customer_name, total_amount FROM orders WHERE id = $orderId");
    $orderData = $orderRes->num_rows ? $orderRes->fetch_assoc() : null;
    $customerName = $orderData ? $orderData['customer_name'] : 'Unknown';
    
    $paidRes =$conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE order_id = $orderId AND payment_status = 'paid'");
    $alreadyPaid = (float)$paidRes->fetch_assoc()['total'];
    
    if ($status === 'paid' && ($alreadyPaid + $amount) > ($orderData['total_amount'] * 1.01)) {
        $message = 'Warning: Payment exceeds order total.';$messageType = 'error';
    }
    
    $conn->query("INSERT INTO payments (order_id, customer_name, amount, payment_method, payment_status, transaction_ref, processed_by, notes) 
                  VALUES ($orderId, '$customerName',$amount, '$method', '$status', '$ref', {$_SESSION['user_id']}, '$notes')");
    
    if ($status === 'paid') {$newTotal = $alreadyPaid +$amount;
        if ($newTotal >= $orderData['total_amount'])$conn->query("UPDATE orders SET paid = 1 WHERE id = $orderId");
    }
    
    if (empty($message)) { $message = 'Payment recorded.';$messageType = 'success'; }
}

// Fetch payments
$result =$conn->query("SELECT p.*, u.full_name as processed_by_name 
                        FROM payments p 
                        LEFT JOIN users u ON p.processed_by = u.id 
                        ORDER BY p.created_at DESC");
$payments = [];
$totalReceived = 0; $totalPending = 0; $totalRefunded = 0; $totalMpesa = 0;
while ($row =$result->fetch_assoc()) {
    $payments[] =$row;
    if ($row['payment_status'] === 'paid') {
        $totalReceived +=$row['amount'];
        if ($row['payment_method'] === 'mpesa') $totalMpesa +=$row['amount'];
    } elseif ($row['payment_status'] === 'pending') {
        $totalPending +=$row['amount'];
    } elseif ($row['payment_status'] === 'refunded') {
        $totalRefunded +=$row['amount'];
    }
}

// Pending M-Pesa payments that need verification
$pendingMpesa =$conn->query("SELECT p.*, o.customer_name, o.total_amount, o.phone 
                               FROM payments p 
                               JOIN orders o ON p.order_id = o.id 
                               WHERE p.payment_status = 'pending' AND p.payment_method = 'mpesa' 
                               ORDER BY p.created_at DESC");

// Orders for dropdown
$ordersRes =$conn->query("SELECT o.id, o.customer_name, o.total_amount, o.paid, 
    COALESCE((SELECT SUM(amount) FROM payments WHERE order_id = o.id AND payment_status = 'paid'), 0) as amount_paid 
    FROM orders o ORDER BY o.id DESC LIMIT 50");
$allOrders = [];
while ($row = $ordersRes->fetch_assoc()) {$row['balance'] = $row['total_amount'] -$row['amount_paid'];
    $allOrders[] =$row;
}

function formatMoney($amount) {
    return 'Ksh ' . number_format($amount, 2);
}

include 'header.php';
?>

<div style="margin-bottom: 20px;">
    <h1 style="font-size: 24px; margin-bottom: 5px;">Payments</h1>
    <p style="color: #666; font-size: 14px;">Record and verify customer payments</p>
</div>

<?php if ($message): ?>
<div style="padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 13px; <?php echo $messageType === 'success' ? 'background: #e8f5e9; color: #2e7d32;' : 'background: #ffebee; color: #c62828;'; ?>">
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 25px;">
    <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #48bb78;">
        <p style="margin: 0; font-size: 12px; color: #666;">Total Received</p>
        <p style="margin: 5px 0 0 0; font-size: 28px; font-weight: bold;"><?php echo formatMoney($totalReceived); ?></p>
    </div>
    <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #00a650;">
        <p style="margin: 0; font-size: 12px; color: #666;">M-Pesa Payments</p>
        <p style="margin: 5px 0 0 0; font-size: 28px; font-weight: bold;"><?php echo formatMoney($totalMpesa); ?></p>
    </div>
    <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #f9ab00;">
        <p style="margin: 0; font-size: 12px; color: #666;">Pending Verification</p>
        <p style="margin: 5px 0 0 0; font-size: 28px; font-weight: bold;"><?php echo formatMoney($totalPending); ?></p>
    </div>
    <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #ea4335;">
        <p style="margin: 0; font-size: 12px; color: #666;">Refunded</p>
        <p style="margin: 5px 0 0 0; font-size: 28px; font-weight: bold;"><?php echo formatMoney($totalRefunded); ?></p>
    </div>
    <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea;">
        <p style="margin: 0; font-size: 12px; color: #666;">Transactions</p>
        <p style="margin: 5px 0 0 0; font-size: 28px; font-weight: bold;"><?php echo count($payments); ?></p>
    </div>
</div>

<?php if ($pendingMpesa &&$pendingMpesa->num_rows > 0): ?>
<div style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 1px solid #fbd38d; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
    <h3 style="font-size: 16px; font-weight: 700; color: #744210; margin-bottom: 14px;">
        <i class="fas fa-bell" style="color: #b7791f;"></i> Pending M-Pesa Verifications
    </h3>
    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
        <thead>
            <tr style="border-bottom: 2px solid #fbd38d;">
                <th style="padding: 10px; text-align: left;">Date</th>
                <th style="padding: 10px; text-align: left;">Order</th>
                <th style="padding: 10px; text-align: left;">Customer</th>
                <th style="padding: 10px; text-align: left;">Amount</th>
                <th style="padding: 10px; text-align: left;">M-Pesa Code</th>
                <th style="padding: 10px; text-align: left;">Phone</th>
                <th style="padding: 10px; text-align: left;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($p =$pendingMpesa->fetch_assoc()): ?>
            <tr style="border-bottom: 1px solid #fef3c7;">
                <td style="padding: 10px; font-size: 12px; color: #666;"><?php echo date('M d, H:i', strtotime($p['created_at'])); ?></td>
                <td style="padding: 10px;"><a href="order_view.php?id=<?php echo $p['order_id']; ?>" style="color: #667eea; font-weight: 700;">#<?php echo $p['order_id']; ?></a></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($p['customer_name']); ?></td>
                <td style="padding: 10px; font-weight: 600;"><?php echo formatMoney($p['amount']); ?></td>
                <td style="padding: 10px; font-family: monospace; font-weight: 700; color: #00a650;"><?php echo htmlspecialchars($p['transaction_ref']); ?></td>
                <td style="padding: 10px; font-size: 12px;"><?php echo htmlspecialchars($p['phone'] ?? '-'); ?></td>
                <td style="padding: 10px;">
                    <a href="payments.php?verify=<?php echo $p['id']; ?>" style="background: #48bb78; color: white; padding: 5px 12px; border-radius: 4px; font-size: 12px; text-decoration: none; font-weight: 600; margin-right: 6px;" onclick="return confirm('Verify this M-Pesa payment? Check your M-Pesa app first.')">
                        <i class="fas fa-check"></i> Verify
                    </a>
                    <a href="#" onclick="return rejectPayment(<?php echo $p['id']; ?>)" style="background: #ea4335; color: white; padding: 5px 12px; border-radius: 4px; font-size: 12px; text-decoration: none; font-weight: 600;">
                        <i class="fas fa-times"></i> Reject
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start;">
    
    <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px;">
        <h3 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 700;">Payments Ledger</h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="border-bottom: 2px solid #edf2f7; color: #4a5568;">
                    <th style="padding: 10px; text-align: left;">ID</th>
                    <th style="padding: 10px; text-align: left;">Order</th>
                    <th style="padding: 10px; text-align: left;">Customer</th>
                    <th style="padding: 10px; text-align: left;">Method</th>
                    <th style="padding: 10px; text-align: left;">Amount</th>
                    <th style="padding: 10px; text-align: left;">Ref/Code</th>
                    <th style="padding: 10px; text-align: left;">Status</th>
                    <th style="padding: 10px; text-align: left;">Processed By</th>
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as$pay): ?>
                <tr style="border-bottom: 1px solid #edf2f7; color: #2d3748;">
                    <td style="padding: 10px; color: #718096;">#<?php echo $pay['id']; ?></td>
                    <td style="padding: 10px;"><a href="order_view.php?id=<?php echo $pay['order_id']; ?>" style="color: #667eea; font-weight: 700;">#<?php echo $pay['order_id']; ?></a></td>
                    <td style="padding: 10px;"><?php echo htmlspecialchars($pay['customer_name']); ?></td>
                    <td style="padding: 10px;"><span style="text-transform: uppercase; font-size: 11px; font-weight: 700; background: #edf2f7; padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($pay['payment_method']); ?></span></td>
                    <td style="padding: 10px; font-weight: 600;"><?php echo formatMoney($pay['amount']); ?></td>
                    <td style="padding: 10px; font-family: monospace; color: #4a5568;"><?php echo htmlspecialchars($pay['transaction_ref'] ?: '-'); ?></td>
                    <td style="padding: 10px;">
                        <?php 
                        $statusColors = [
                            'paid' => ['bg' => '#f0fff4', 'text' => '#2f855a'],
                            'pending' => ['bg' => '#fffbeb', 'text' => '#b7791f'],
                            'failed' => ['bg' => '#fff5f5', 'text' => '#c53030'],
                            'refunded' => ['bg' => '#ebf8ff', 'text' => '#2b6cb0']
                        ];
                        $c = $statusColors[$pay['payment_status']] ?? ['bg' => '#f7fafc', 'text' => '#4a5568'];
                        ?>
                        <span style="background: <?php echo $c['bg']; ?>; color: <?php echo$c['text']; ?>; padding: 3px 8px; border-radius: 10px; font-size: 11px; font-weight: bold;">
                            <?php echo ucfirst($pay['payment_status']); ?>
                        </span>
                    </td>
                    <td style="padding: 10px; font-size: 12px; color: #718096;"><?php echo htmlspecialchars($pay['processed_by_name'] ?? 'System'); ?></td>
                    <td style="padding: 10px;">
    <a href="payments.php?delete=<?php echo $pay['id']; ?>" style="color: #ea4335; font-size: 12px; text-decoration: none;" onclick="return confirm('Delete this payment record?')">Delete</a>
</td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($payments)): ?>
                <tr><td colspan="8" style="padding: 30px; text-align: center; color: #999;">No payments recorded yet</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div style="display: flex; justify-content: flex-end; margin-top: 16px; padding-top: 14px; border-top: 1px solid #e2e8f0; gap: 10px; align-items: center;">
            <span style="font-size: 12.5px; font-weight: 600; color: #4a5568;">Total Received:</span>
            <span style="font-size: 17px; font-weight: 800; color: #764ba2;"><?php echo formatMoney($totalReceived); ?></span>
        </div>
    </div>

    <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px;">
        <h3 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 700;">Record Payment</h3>
        <form method="POST" action="">
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #4a5568; margin-bottom: 4px;">Select Order</label>
                <select name="order_id" required style="width: 100%; padding: 8px; border: 1px solid #cbd5e0; border-radius: 4px;" onchange="fillAmount(this)">
                    <option value="">-- Choose Order --</option>
                    <?php foreach ($allOrders as$o): ?>
                    <option value="<?php echo $o['id']; ?>" data-balance="<?php echo $o['balance']; ?>" data-total="<?php echo $o['total_amount']; ?>" data-paid="<?php echo $o['amount_paid']; ?>">
                        #<?php echo $o['id']; ?> - <?php echo htmlspecialchars($o['customer_name']); ?> (Ksh <?php echo number_format($o['total_amount'], 2); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="balanceInfo" style="display: none; background: #ebf8ff; border: 1px solid #bee3f8; padding: 10px; border-radius: 4px; font-size: 11.5px; color: #2b6cb0; margin-bottom: 12px;"></div>

            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #4a5568; margin-bottom: 4px;">Payment Method</label>
                <select name="payment_method" style="width: 100%; padding: 8px; border: 1px solid #cbd5e0; border-radius: 4px;">
                    <option value="cash">Cash</option>
                    <option value="mpesa">M-Pesa</option>
                    <option value="card">Card</option>
                    <option value="bank">Bank Transfer</option>
                </select>
            </div>

            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #4a5568; margin-bottom: 4px;">Amount (Ksh)</label>
                <input type="number" name="amount" id="payAmount" step="0.01" required style="width: 100%; padding: 8px; border: 1px solid #cbd5e0; border-radius: 4px;">
            </div>

            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #4a5568; margin-bottom: 4px;">Transaction Ref/Code</label>
                <input type="text" name="transaction_ref" placeholder="e.g. QX89HDJK" style="width: 100%; padding: 8px; border: 1px solid #cbd5e0; border-radius: 4px;">
            </div>

            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #4a5568; margin-bottom: 4px;">Status</label>
                <select name="payment_status" style="width: 100%; padding: 8px; border: 1px solid #cbd5e0; border-radius: 4px;">
                    <option value="paid">Paid (Fully Cleared)</option>
                    <option value="pending">Pending Verification</option>
                </select>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #4a5568; margin-bottom: 4px;">Notes</label>
                <textarea name="notes" placeholder="Any internal notes..." style="width: 100%; padding: 8px; border: 1px solid #cbd5e0; border-radius: 4px; height: 60px; font-family: inherit; resize: none;"></textarea>
            </div>

            <button type="submit" name="add_payment" style="width: 100%; background: #667eea; color: white; padding: 10px; border-radius: 4px; border: none; font-weight: 700; cursor: pointer; transition: background 0.2s;">
                Record Payment
            </button>
        </form>
    </div>
</div>

<script>
function rejectPayment(id) {
    if (!confirm('Reject this payment as an Invalid Payment Code? Check your M-Pesa app first — this tells the customer no payment was received.')) {
        return false;
    }
    window.location.href = 'payments.php?reject=' + id;
    return false;
}

function fillAmount(select) {
    const option = select.options[select.selectedIndex];
    if (option.value) {
        const balance = parseFloat(option.getAttribute('data-balance')) || 0;
        const total = parseFloat(option.getAttribute('data-total')) || 0;
        const paid = parseFloat(option.getAttribute('data-paid')) || 0;
        document.getElementById('payAmount').value = balance > 0 ? balance.toFixed(2) : '';
        const info = document.getElementById('balanceInfo');
        info.style.display = 'block';
        info.innerHTML = '<strong>Order Total:</strong> Ksh ' + total.toFixed(2) + ' | <strong>Paid:</strong> Ksh ' + paid.toFixed(2) + ' | <strong>Remaining Balance:</strong> Ksh ' + balance.toFixed(2);
    } else {
        document.getElementById('balanceInfo').style.display = 'none';
        document.getElementById('payAmount').value = '';
    }
}
</script>

<?php include 'footer.php'; ?>
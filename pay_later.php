<?php
// pay_later.php — Simple M-Pesa Manual Payment Page
// Customers see till/paybill number and confirm payment manually
// Staff verify in their M-Pesa app and mark as paid

session_start();
require_once 'db_config.php';

// Get order ID
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId <= 0) {
    header('Location: index.php');
    exit;
}

// Fetch order
$stmt = $conn->prepare("SELECT o.*, c.full_name as customer_full_name, c.phone as customer_phone 
                        FROM orders o 
                        LEFT JOIN customer c ON o.customer_id = c.id 
                        WHERE o.id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header('Location: index.php');
    exit;
}

// If already paid, redirect to confirmation
if ($order['paid']) {
    header('Location: order_confirmation.php?id=' . $orderId);
    exit;
}

$message = '';
$messageType = '';

// Handle M-Pesa payment confirmation from customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    $mpesaCodeRaw = strtoupper(trim($_POST['mpesa_code'] ?? ''));
    $phoneUsedRaw = trim($_POST['phone_used'] ?? '');

    // Real Safaricom confirmation codes are a fixed-format alphanumeric
    // string (commonly 10 chars, always a mix of letters and digits, no
    // spaces or punctuation). A plain length check lets someone type a
    // sentence like "I paid yesterday please check" straight through as
    // long as it's 6+ characters — that's not remotely a code. Enforce
    // the actual shape instead: letters+digits only, 8-12 chars, and it
    // must contain at least one letter AND one digit (a message like
    // "IPAIDYESTERDAY" is all letters and would still fail this).
    $looksLikeCode = (bool)preg_match('/^[A-Z0-9]{8,12}$/', $mpesaCodeRaw)
        && preg_match('/[A-Z]/', $mpesaCodeRaw)
        && preg_match('/[0-9]/', $mpesaCodeRaw);

    // Phone is optional, but if they typed something, it should look like
    // a Kenyan phone number, not more free text.
    $phoneLooksValid = $phoneUsedRaw === '' || preg_match('/^(?:\+?254|0)[17]\d{8}$/', preg_replace('/\s+/', '', $phoneUsedRaw));

    if (empty($mpesaCodeRaw)) {
        $message = 'Please enter your M-Pesa confirmation code.';
        $messageType = 'error';
    } elseif (!$looksLikeCode) {
        $message = 'That doesn\'t look like a valid M-Pesa code. It should be the short letters+numbers code from your M-Pesa SMS (e.g. QGH7XXXXR1) — not a message.';
        $messageType = 'error';
    } elseif (!$phoneLooksValid) {
        $message = 'That phone number doesn\'t look right. Please enter the number you paid from, e.g. 0712345678.';
        $messageType = 'error';
    } else {
        $mpesaCode = $conn->real_escape_string($mpesaCodeRaw);
        $phoneUsed = $conn->real_escape_string($phoneUsedRaw);

        // Save code to the order for quick reference, without also
        // stuffing it into notes — appending to notes on every submit
        // (including resubmits after a rejection) let that field grow
        // without bound and duplicated what the payments table already
        // tracks properly.
        $conn->query("UPDATE orders SET mpesa_code = '$mpesaCode', payment_method = 'mpesa' WHERE id = $orderId");

        // Record in payments table for staff to verify
        $customerName = $conn->real_escape_string($order['customer_name'] ?? 'Guest');
        $amount = (float)$order['total_amount'];

        $conn->query("INSERT INTO payments (order_id, customer_name, amount, payment_method, payment_status, transaction_ref, notes, created_at) 
                      VALUES ($orderId, '$customerName', $amount, 'mpesa', 'pending', '$mpesaCode', 'M-Pesa payment pending verification. Phone: $phoneUsed', NOW())");

        $message = 'M-Pesa code submitted! Our staff will verify and confirm your payment.';
        $messageType = 'success';
    }
}

// Handle cash on pickup confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cash_on_pickup'])) {
    $message = 'Your order is confirmed. Please pay Ksh ' . number_format($order['total_amount'], 2) . ' when you pick up your laundry.';
    $messageType = 'info';
}

// Check payment status
$paymentStatus = '';
$payRes = $conn->query("SELECT payment_status, transaction_ref FROM payments WHERE order_id = $orderId ORDER BY id DESC LIMIT 1");
if ($payRes && $payRes->num_rows > 0) {
    $payRow = $payRes->fetch_assoc();
    $paymentStatus = $payRow['payment_status'];
}

// Your M-Pesa Till/Paybill details — CHANGE THESE
$TILL_NUMBER = '123456';        // Your Buy Goods Till Number
$PAYBILL_NUMBER = '522522';     // Your Paybill Number
$BUSINESS_NAME = "Muthoni's Laundry";

function formatMoney($amount) {
    return 'Ksh ' . number_format($amount, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay for Order #<?php echo $orderId; ?> - <?php echo $BUSINESS_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #667eea;
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .pay-container {
            max-width: 520px;
            width: 100%;
        }
        .pay-card {
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 25px 80px rgba(0,0,0,0.3);
        }
        .brand {
            text-align: center;
            margin-bottom: 25px;
        }
        .brand h2 {
            font-size: 22px;
            color: #1a202c;
            margin-bottom: 4px;
        }
        .brand p {
            color: #718096;
            font-size: 14px;
        }
        
        .order-summary {
            background: #f7fafc;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 14px;
        }
        .summary-row span:first-child { color: #718096; }
        .summary-row span:last-child { font-weight: 600; color: #1a202c; }
        .summary-row.total {
            border-top: 2px solid #e2e8f0;
            margin-top: 8px;
            padding-top: 12px;
            font-size: 18px;
        }
        .summary-row.total span:last-child {
            color: #667eea;
            font-size: 22px;
            font-weight: 800;
        }
        
        .message {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 500;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .message.success { background: #f0fff4; color: #2f855a; border: 1px solid #c6f6d5; }
        .message.error { background: #fff5f5; color: #c53030; border: 1px solid #fed7d7; }
        .message.info { background: #ebf4ff; color: #2b6cb0; border: 1px solid #bee3f8; }
        
        /* M-Pesa Payment Box */
        .mpesa-box {
            background: linear-gradient(135deg, #00a650 0%, #008f45 100%);
            border-radius: 16px;
            padding: 24px;
            color: white;
            margin-bottom: 16px;
            text-align: center;
        }
        .mpesa-box h3 {
            font-size: 18px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .till-number {
            background: rgba(255,255,255,0.2);
            border: 2px dashed rgba(255,255,255,0.5);
            border-radius: 12px;
            padding: 20px;
            margin: 12px 0;
        }
        .till-number .label {
            font-size: 12px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }
        .till-number .number {
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 4px;
            font-family: 'Courier New', monospace;
        }
        .till-number .name {
            font-size: 13px;
            margin-top: 6px;
            opacity: 0.9;
        }
        .mpesa-steps {
            text-align: left;
            font-size: 13px;
            margin-top: 16px;
            background: rgba(0,0,0,0.15);
            border-radius: 10px;
            padding: 14px;
        }
        .mpesa-steps ol {
            padding-left: 18px;
            line-height: 2;
        }
        .mpesa-steps li { opacity: 0.95; }
        
        .payment-options {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .pay-option {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
        }
        .pay-option:hover {
            border-color: #667eea;
            background: #fafbfc;
        }
        .pay-option.active {
            border-color: #667eea;
            background: #f0f2ff;
        }
        .pay-option-header {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .pay-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .pay-icon.mpesa { background: #00a650; color: white; }
        .pay-icon.cash { background: #f9ab00; color: white; }
        .pay-option-title {
            font-weight: 700;
            font-size: 15px;
            color: #1a202c;
        }
        .pay-option-desc {
            font-size: 12.5px;
            color: #718096;
            margin-top: 2px;
        }
        
        .pay-form {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            display: none;
        }
        .pay-form.show { display: block; }
        
        .form-group {
            margin-bottom: 14px;
        }
        .form-group label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 6px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            outline: none;
        }
        .form-group input:focus {
            border-color: #667eea;
        }
        .form-group .hint {
            font-size: 11px;
            color: #a0aec0;
            margin-top: 4px;
        }
        
        .btn-pay {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-pay.mpesa-btn { background: linear-gradient(135deg, #00a650, #008f45); }
        .btn-pay.mpesa-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,166,80,0.3); }
        .btn-pay.cash-btn { background: linear-gradient(135deg, #f9ab00, #d97706); }
        .btn-pay.cash-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(249,171,0,0.3); }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 600;
        }
        .back-link a:hover { text-decoration: underline; }
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
            color: #a0aec0;
            font-size: 13px;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }
        .divider::before { margin-right: 12px; }
        .divider::after { margin-left: 12px; }
        
        .pending-badge {
            background: #fffbeb;
            border: 1px solid #fbd38d;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 16px;
            text-align: center;
        }
        .pending-badge i { color: #b7791f; font-size: 24px; margin-bottom: 6px; }
        .pending-badge p { color: #975a16; font-weight: 600; font-size: 14px; margin: 0; }
        .pending-badge small { color: #718096; font-size: 12px; }

        .rejected-badge {
            background: #fff5f5;
            border: 1px solid #feb2b2;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 16px;
            text-align: center;
        }
        .rejected-badge i { color: #c53030; font-size: 24px; margin-bottom: 6px; }
        .rejected-badge p { color: #c53030; font-weight: 700; font-size: 14px; margin: 0 0 4px 0; }
        .rejected-badge small { color: #742a2a; font-size: 12.5px; display: block; }
        
        @media (max-width: 480px) {
            .pay-card { padding: 24px; }
            .till-number .number { font-size: 28px; }
        }
    </style>
</head>
<body>

<div class="pay-container">
    <div class="pay-card">
        <div class="brand">
            <h2><i class="fas fa-credit-card"></i> Complete Your Payment</h2>
            <p>Order #<?php echo $orderId; ?></p>
        </div>
        
        <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : ($messageType === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'); ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($paymentStatus === 'pending'): ?>
        <div class="pending-badge">
            <i class="fas fa-clock"></i>
            <p>Payment Pending Verification</p>
            <small>Our staff is verifying your M-Pesa transaction. You'll receive an update shortly.</small>
        </div>
        <?php elseif ($paymentStatus === 'failed'): ?>
        <div class="rejected-badge">
            <i class="fas fa-exclamation-triangle"></i>
            <p>Invalid Payment Code — no payment was recorded.</p>
            <small>Please double-check your M-Pesa SMS and submit the correct code below.</small>
        </div>
        <?php endif; ?>
        
        <div class="order-summary">
            <div class="summary-row">
                <span>Customer</span>
                <span><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></span>
            </div>
            <div class="summary-row">
                <span>Phone</span>
                <span><?php echo htmlspecialchars($order['phone'] ?? '-'); ?></span>
            </div>
            <div class="summary-row">
                <span>Status</span>
                <span><?php echo ucfirst($order['status']); ?></span>
            </div>
            <div class="summary-row total">
                <span>Amount Due</span>
                <span><?php echo formatMoney($order['total_amount']); ?></span>
            </div>
        </div>
        
        <div class="mpesa-box">
            <h3><i class="fas fa-mobile-alt"></i> Pay with M-Pesa</h3>
            
            <div class="till-number">
                <div class="label">Buy Goods Till Number</div>
                <div class="number"><?php echo $TILL_NUMBER; ?></div>
                <div class="name"><?php echo $BUSINESS_NAME; ?></div>
            </div>
            
            <div class="mpesa-steps">
                <strong style="display:block;margin-bottom:8px;font-size:14px;"><i class="fas fa-list-ol"></i> How to Pay:</strong>
                <ol>
                    <li>Open M-Pesa on your phone</li>
                    <li>Select <strong>Lipa na M-Pesa</strong></li>
                    <li>Select <strong>Buy Goods and Services</strong></li>
                    <li>Enter Till Number: <strong><?php echo $TILL_NUMBER; ?></strong></li>
                    <li>Enter Amount: <strong><?php echo formatMoney($order['total_amount']); ?></strong></li>
                    <li>Enter your M-Pesa PIN and confirm</li>
                    <li>Enter the confirmation code below</li>
                </ol>
            </div>
        </div>
        
        <div class="payment-options">
    <div class="pay-option active" id="mpesaOption">
        <div class="pay-option-header">
            <div class="pay-icon mpesa"><i class="fas fa-mobile-alt"></i></div>
            <div>
                <div class="pay-option-title">Pay with M-Pesa</div>
                <div class="pay-option-desc">Enter your M-Pesa confirmation code after paying</div>
            </div>
        </div>
        <div class="pay-form show" id="mpesaForm">
            <form method="POST" action="">
                <div class="form-group">
                    <label><i class="fas fa-receipt"></i> M-Pesa Confirmation Code</label>
                    <input type="text" name="mpesa_code"
                           placeholder="e.g. QKJ7H8JKL9"
                           maxlength="20"
                           style="text-transform:uppercase;"
                           required>
                    <div class="hint">Found in your M-Pesa SMS after payment</div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Phone Number Used</label>
                    <input type="tel" name="phone_used"
                           placeholder="e.g. 0712345678"
                           value="<?php echo htmlspecialchars($order['phone'] ?? ''); ?>">
                </div>
                <button type="submit" name="confirm_payment" class="btn-pay mpesa-btn">
                    <i class="fas fa-check"></i> I've Paid — Submit Code
                </button>
            </form>
        </div>
    </div>

    <div class="divider">OR</div>

    <div class="pay-option" id="cashOption">
        <div class="pay-option-header">
            <div class="pay-icon cash"><i class="fas fa-hand-holding-usd"></i></div>
            <div>
                <div class="pay-option-title">Pay Cash on Pickup</div>
                <div class="pay-option-desc">Pay when you collect your laundry</div>
            </div>
        </div>
        <div class="pay-form" id="cashForm">
            <p style="font-size:13px;color:#718096;margin-bottom:14px;">
                Your order is confirmed. Please pay <strong><?php echo formatMoney($order['total_amount']); ?></strong>
                when you pick up your laundry.
            </p>
            <a href="customer_dashboard.php" class="btn-pay cash-btn" style="text-decoration:none;text-align:center;">
                <i class="fas fa-check"></i> OK, I'll Pay on Pickup
            </a>
        </div>
    </div>
</div>

<div class="back-link">
            <a href="track_guest.php?id=<?php echo $orderId; ?>"><i class="fas fa-arrow-left"></i> Back to Order Tracking</a>
            <?php if (isset($_SESSION['customer_id'])): ?> | 
            <a href="customer_dashboard.php"><i class="fas fa-home"></i> My Dashboard</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleForm(type) {
    document.querySelectorAll('.pay-form').forEach(f => f.classList.remove('show'));
    document.querySelectorAll('.pay-option').forEach(o => o.classList.remove('active'));
    
    document.getElementById(type + 'Form').classList.add('show');
    document.getElementById(type + 'Option').classList.add('active');
}

document.getElementById('mpesaOption').addEventListener('click', function() { toggleForm('mpesa'); });
document.getElementById('cashOption').addEventListener('click', function() { toggleForm('cash'); });
</script>

</body>
</html>
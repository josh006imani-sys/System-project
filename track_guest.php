<?php
// track_guest.php — Guest Order Tracker (No Login Required)
// Customers without accounts can track their order using Order ID + phone verification.
// We verify with the last 4 digits of their phone number for privacy.

require_once 'db_config.php';

$order = null;
$error = '';
$step = 'verify'; // always start at verify

// Process the tracking form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['track'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $phoneVerify = trim($_POST['phone_verify'] ?? '');
    
    if ($orderId <= 0 || empty($phoneVerify)) {
        $error = 'Please enter both order number and verification code.';
    } else {
        // Look up the order
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        
        if (!$order) {
            $error = 'Order not found. Please check your order number.';
        } else {
            // Verify phone — accept last 4 digits OR full phone
            $phone = $order['phone'] ?? '';
            $digitsOnly = preg_replace('/[^0-9]/', '', $phone);
            $last4 = substr($digitsOnly, -4);
            $inputClean = preg_replace('/[^0-9]/', '', $phoneVerify);
            
            if ($inputClean === $last4 || $inputClean === $digitsOnly) {
                $step = 'result'; // success — show order
            } else {
                $error = 'Verification code does not match. Use the last 4 digits of your phone number.';
                $order = null;
            }
        }
    }
}

// Latest payment attempt for the verified order — so a guest can see
// whether their submitted M-Pesa code was approved or rejected, not just
// whether the order itself is paid.
$paymentStatus = '';
if ($order) {
    $payStmt = $conn->prepare("SELECT payment_status FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1");
    $payStmt->bind_param("i", $order['id']);
    $payStmt->execute();
    $payRow = $payStmt->get_result()->fetch_assoc();
    $payStmt->close();
    if ($payRow) $paymentStatus = $payRow['payment_status'];
}

// Prefill order ID if passed in URL
$prefillId = isset($_GET['id']) ? (int)$_GET['id'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order - Muthoni's Laundry</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #667eea;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .box {
            background: white;
            padding: 35px;
            border-radius: 10px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        .brand { text-align: center; margin-bottom: 25px; }
        .brand h2 { color: #333; margin-bottom: 5px; }
        .brand p { color: #888; font-size: 14px; }
        
        .info {
            background: #ebf4ff;
            border: 1px solid #bee3f8;
            border-radius: 5px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #2b6cb0;
        }
        
        .form-group { margin-bottom: 15px; }
        .form-group label {
            display: block;
            font-size: 13px;
            color: #555;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-group input:focus {
            border-color: #667eea;
            outline: none;
        }
        
        .error {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 5px;
            font-size: 13px;
            margin-bottom: 15px;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn:hover { background: #5568d3; }
        
        .links {
            text-align: center;
            margin-top: 15px;
            font-size: 13px;
        }
        .links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
        }
        .links a:hover { text-decoration: underline; }
        
        /* Result styles */
        .result {
            margin-top: 20px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .result h3 { font-size: 20px; color: #333; }
        
        .status-badge {
            display: inline-block;
            padding: 5px 14px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-received { background: #e2e8f0; color: #4a5568; }
        .badge-washing { background: #fffbeb; color: #b7791f; }
        .badge-ready { background: #f0fff4; color: #2f855a; }
        .badge-collected { background: #faf5ff; color: #6b46c1; }
        
        .meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .meta div { color: #555; }
        .meta strong { color: #333; }
        
        /* Status tracker */
        .tracker {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            position: relative;
        }
        .tracker::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 12%;
            right: 12%;
            height: 3px;
            background: #ddd;
            z-index: 0;
        }
        .step {
            text-align: center;
            position: relative;
            z-index: 1;
            flex: 1;
        }
        .step-dot {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #ddd;
            color: #888;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 5px;
            font-size: 14px;
        }
        .step.active .step-dot {
            background: #667eea;
            color: white;
        }
        .step.done .step-dot {
            background: #48bb78;
            color: white;
        }
        .step-label {
            font-size: 11px;
            color: #999;
        }
        .step.active .step-label {
            color: #667eea;
            font-weight: bold;
        }
        .step.done .step-label {
            color: #48bb78;
        }
        
        .alert {
            background: #f0fff4;
            border: 1px solid #9ae6b4;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            text-align: center;
        }
        .alert i {
            color: #48bb78;
            font-size: 24px;
            margin-bottom: 8px;
        }
        .alert p {
            color: #2f855a;
            font-weight: bold;
            margin: 0;
            font-size: 14px;
        }
        
        .pay-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background: #34a853;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 15px;
            font-weight: bold;
            text-decoration: none;
            text-align: center;
            margin-top: 15px;
        }
        .pay-btn:hover { background: #2d8a47; }

        .pay-status {
            margin-top: 15px;
            border-radius: 8px;
            padding: 14px;
            text-align: center;
        }
        .pay-status.verified {
            background: #f0fff4; border: 1px solid #9ae6b4;
        }
        .pay-status.verified p { color: #2f855a; font-weight: bold; margin: 0; font-size: 14px; }
        .pay-status.verified i { color: #48bb78; font-size: 22px; margin-bottom: 6px; display: block; }

        .pay-status.pending {
            background: #fffbeb; border: 1px solid #fbd38d;
        }
        .pay-status.pending p { color: #975a16; font-weight: 600; margin: 0; font-size: 14px; }
        .pay-status.pending i { color: #b7791f; font-size: 22px; margin-bottom: 6px; display: block; }

        .pay-status.rejected {
            background: #fff5f5; border: 1px solid #feb2b2;
        }
        .pay-status.rejected p { color: #c53030; font-weight: 700; margin: 0 0 4px 0; font-size: 14px; }
        .pay-status.rejected small { color: #742a2a; font-size: 12.5px; }
        .pay-status.rejected i { color: #c53030; font-size: 22px; margin-bottom: 6px; display: block; }
    </style>
</head>
<body>
    <div class="box">
        <div class="brand">
            <h2><i class="fas fa-search-location"></i> Track Your Order</h2>
            <p>Enter your details below</p>
        </div>

        <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($step === 'verify'): ?>
        <!-- Verification Form -->
        <div class="info">
            <i class="fas fa-shield-alt"></i> 
            <strong>Secure Tracking:</strong> Enter your order number and the <strong>last 4 digits</strong> of your phone number.
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label><i class="fas fa-hashtag" style="color:#667eea;"></i> Order Number</label>
                <input type="number" name="order_id" placeholder="e.g. 123" required autofocus 
                       value="<?php echo $prefillId ? htmlspecialchars($prefillId) : ''; ?>">
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock" style="color:#667eea;"></i> Phone Verification (Last 4 Digits)</label>
                <input type="text" name="phone_verify" placeholder="e.g. 5678" required maxlength="4">
            </div>
            <button type="submit" name="track" class="btn">
                <i class="fas fa-search"></i> Track Order
            </button>
        </form>

        <?php elseif ($step === 'result' && $order): 
            $statuses = ['received', 'washing', 'ready', 'collected'];
            $current = strtolower($order['status'] ?? 'received');
            $currentIndex = array_search($current, $statuses);
            if ($currentIndex === false) $currentIndex = 0;
        ?>
        <!-- Order Result -->
        <div class="result">
            <div class="result-header">
                <h3>Order #<?php echo $order['id']; ?></h3>
                <span class="status-badge badge-<?php echo $current; ?>">
                    <?php echo ucfirst($current); ?>
                </span>
            </div>
            
            <div class="meta">
                <div><strong>Customer:</strong><br><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></div>
                <div><strong>Phone:</strong><br>****<?php echo htmlspecialchars(substr($order['phone'] ?? '****', -4)); ?></div>
                <div><strong>Date:</strong><br><?php echo date('M d, Y', strtotime($order['order_date'])); ?></div>
                <div><strong>Total:</strong><br>Ksh <?php echo number_format($order['total_amount'] ?? 0, 2); ?></div>
            </div>
            
            <!-- Status Tracker -->
            <div class="tracker">
                <?php 
                $icons = ['received' => 'fa-inbox', 'washing' => 'fa-soap', 'ready' => 'fa-check', 'collected' => 'fa-hand-holding'];
                foreach ($statuses as $i => $s): 
                ?>
                <div class="step <?php echo $currentIndex >= $i ? 'done' : ''; ?> <?php echo $currentIndex == $i ? 'active' : ''; ?>">
                    <div class="step-dot"><i class="fas <?php echo $icons[$s]; ?>"></i></div>
                    <div class="step-label"><?php echo ucfirst($s); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($current === 'ready'): ?>
            <div class="alert">
                <i class="fas fa-bell"></i>
                <p>Your laundry is ready for pickup!</p>
                <small style="color:#718096;">Please bring your order number: <strong>#<?php echo $order['id']; ?></strong></small>
            </div>
            <?php elseif ($current === 'collected'): ?>
            <div style="text-align:center; color:#48bb78; margin-top:15px; font-size:14px;">
                <i class="fas fa-check-circle" style="font-size:20px;"></i><br>
                This order has been collected. Thank you!
            </div>
            <?php endif; ?>
            
            <?php if (!empty($order['paid'])): ?>
            <div class="pay-status verified">
                <i class="fas fa-check-circle"></i>
                <p>Payment Verified</p>
            </div>
            <?php elseif ($paymentStatus === 'pending'): ?>
            <div class="pay-status pending">
                <i class="fas fa-clock"></i>
                <p>Payment Pending Verification</p>
            </div>
            <?php elseif ($paymentStatus === 'failed'): ?>
            <div class="pay-status rejected">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Invalid Payment Code — no payment was recorded.</p>
                <small>Please double-check your M-Pesa SMS and submit the correct code.</small>
            </div>
            <?php endif; ?>

            <?php if (empty($order['paid']) && $paymentStatus !== 'pending' && ($order['payment_timing'] ?? 'pay_later') === 'pay_later'): ?>
            <a href="pay_later.php?id=<?php echo $order['id']; ?>" class="pay-btn">
                <i class="fas fa-credit-card"></i> Pay Now — Ksh <?php echo number_format($order['total_amount'], 2); ?>
            </a>
            <?php endif; ?>
        </div>
        
        <div class="links" style="margin-top:20px;">
            <a href="track_guest.php"><i class="fas fa-arrow-left"></i> Track Another Order</a>
        </div>
        <?php endif; ?>

        <div class="links">
            <a href="customer_login.php">Customer Login</a> | 
            <a href="index.php">Back to Home</a>
        </div>
    </div>
</body>
</html>
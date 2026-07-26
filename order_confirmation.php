<?php
// order_confirmation.php — Shows order details after placement
// Clean version without Daraja/M-Pesa API references

require_once 'db_config.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$orderId = (int)$_GET['id'];

// Get the order
$res = $conn->query("SELECT o.*, c.full_name as customer_full_name FROM orders o LEFT JOIN customer c ON o.customer_id = c.id WHERE o.id = $orderId");
if (!$res || $res->num_rows === 0) {
    header('Location: index.php');
    exit;
}
$order = $res->fetch_assoc();

// Get the items
$items = [];
$ires = $conn->query("SELECT name, qty, price FROM order_items WHERE order_id = $orderId");
while ($row = $ires->fetch_assoc()) $items[] = $row;

// Make a nice display ID
$displayId = 'ORD-' . date('Y', strtotime($order['order_date'])) . '-' . str_pad($order['id'], 4, '0', STR_PAD_LEFT);

// Your Till Number — CHANGE THIS
$TILL_NUMBER = '123456';

function formatMoney($amount) {
    return 'Ksh ' . number_format($amount, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed — Muthoni's Laundry</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #667eea;
            color: #1a202c;
            padding: 40px 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            max-width: 600px;
            width: 100%;
        }
        .success-icon {
            text-align: center;
            color: #2f855a;
            font-size: 48px;
            margin-bottom: 16px;
        }
        h1 {
            text-align: center;
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 8px;
        }
        .subtitle {
            text-align: center;
            color: #718096;
            font-size: 14px;
            margin-bottom: 30px;
        }
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .card-title {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 14px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
            color: #4a5568;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 13.5px;
            margin-bottom: 8px;
        }
        .info-row span:first-child { color: #718096; }
        .info-row span:last-child { font-weight: 600; }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .items-table th {
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            color: #718096;
            padding-bottom: 8px;
            border-bottom: 2px solid #e2e8f0;
        }
        .items-table td {
            padding: 10px 0;
            font-size: 13.5px;
            border-bottom: 1px solid #edf2f7;
        }
        
        .mpesa-box {
            background: #f0fff4;
            border: 1px dashed #38a169;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .mpesa-box h3 {
            font-size: 14px;
            color: #276749;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .track-info {
            background: #ebf8ff;
            border: 1px solid #bee3f8;
            color: #2b6cb0;
            border-radius: 10px;
            padding: 14px;
            font-size: 13px;
            margin-bottom: 24px;
            line-height: 1.5;
        }
        
        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn {
            flex: 1;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            border: none;
        }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .btn-success { background: #00a650; color: white; }
        .btn-outline { background: transparent; border: 1.5px solid #e2e8f0; color: #4a5568; }
        
        @media (max-width: 480px) {
            .actions { flex-direction: column; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="success-icon">
        <i class="fas fa-check-circle"></i>
    </div>
    <h1>Thank You for Your Order!</h1>
    <p class="subtitle">Your laundry order has been received and is processing.</p>
    
    <div class="card">
        <div class="card-title">Order Details</div>
        <div class="info-row">
            <span>Order Reference</span>
            <span><?php echo $displayId; ?></span>
        </div>
        <div class="info-row">
            <span>Customer Name</span>
            <span><?php echo htmlspecialchars($order['customer_name'] ?: ($order['customer_full_name'] ?: 'Guest')); ?></span>
        </div>
        <div class="info-row">
            <span>Phone Number</span>
            <span><?php echo htmlspecialchars($order['phone']); ?></span>
        </div>
        <div class="info-row">
            <span>Payment Method</span>
            <span><?php echo strtoupper($order['payment_method']); ?></span>
        </div>
        <div class="info-row">
            <span>Payment Status</span>
            <span style="color: <?php echo $order['paid'] ? '#2f855a' : '#b7791f'; ?>">
                <?php echo $order['paid'] ? 'Paid' : 'Unpaid (Pending)'; ?>
            </span>
        </div>
    </div>

    <div class="card">
        <div class="card-title">Items Ordered</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Service / Item</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td style="text-align: center;"><?php echo $item['qty']; ?></td>
                    <td style="text-align: right; font-weight: 600;"><?php echo formatMoney($item['qty'] * $item['price']); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="2" style="text-align: right; font-weight: 700; padding-top: 15px; border: none;">Grand Total:</td>
                    <td style="text-align: right; font-weight: 800; font-size: 16px; color: #667eea; padding-top: 15px; border: none;">
                        <?php echo formatMoney($order['total_amount']); ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <?php if (!$order['paid'] && $order['payment_method'] === 'mpesa'): ?>
    <div class="mpesa-box">
        <h3><i class="fas fa-mobile-alt"></i> Manual M-Pesa Step Required</h3>
        <p style="font-size: 13px; color: #276749; line-height: 1.5;">
            Please click the <strong>Pay Now</strong> button below to see our Buy Goods Till Number <strong><?php echo $TILL_NUMBER; ?></strong> and submit your confirmation code.
        </p>
        <div class="mpesa-steps" style="margin-top: 10px; font-size: 12px; color: #333;">
            <ol style="padding-left: 15px;">
                <li>Open M-Pesa on your phone</li>
                <li>Select <strong>Lipa na M-Pesa</strong> → <strong>Buy Goods and Services</strong></li>
                <li>Enter Till Number: <strong><?php echo $TILL_NUMBER; ?></strong></li>
                <li>Enter Amount: <strong><?php echo formatMoney($order['total_amount']); ?></strong></li>
                <li>Enter your M-Pesa PIN and confirm</li>
            </ol>
        </div>
    </div>
    <?php endif; ?>

    <div class="track-info">
        <strong><i class=\"fas fa-info-circle\"></i> Track Your Order</strong><br>
        Use your order number <strong><?php echo $order['id']; ?></strong> and the last 4 digits of your phone to track status.
    </div>

    <div class="actions">
        <?php if (!$order['paid']): ?>
        <a href="pay_later.php?id=<?php echo $order['id']; ?>" class="btn btn-success">
            <i class="fas fa-credit-card"></i> Pay Now
        </a>
        <?php endif; ?>
        <a href="track_guest.php?id=<?php echo $order['id']; ?>" class="btn btn-primary">
            <i class="fas fa-search"></i> Track Order
        </a>
        <a href="index.php" class="btn btn-outline">
            <i class="fas fa-home"></i> Back to Home
        </a>
    </div>
</div>

</body>
</html>
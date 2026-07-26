<?php
// customer_dashboard.php — Customer Order Dashboard
// Shows all orders for the logged-in customer with status tracking and item details.

session_start();
require_once 'db_config.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: customer_login.php');
    exit;
}

$customerId = $_SESSION['customer_id'];

// Get customer
$res = $conn->query("SELECT * FROM customer WHERE id = $customerId");
$customer = $res->fetch_assoc();
if (!$customer) {
    unset($_SESSION['customer_id'], $_SESSION['customer_name']);
    header('Location: customer_login.php');
    exit;
}

// Get orders by customer_id OR phone, with item counts
$phone = $conn->real_escape_string($customer['phone']);
$res = $conn->query("
    SELECT o.*, 
           COALESCE(SUM(oi.qty), 0) as item_count,
           GROUP_CONCAT(DISTINCT CONCAT(oi.name, ' x', oi.qty) SEPARATOR ', ') as item_summary
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.customer_id = $customerId OR o.phone = '$phone'
    GROUP BY o.id
    ORDER BY o.order_date DESC
");
$orders = [];
while ($row = $res->fetch_assoc()) {
    $orders[] = $row;
}

// Any unpaid orders whose most recent payment attempt was rejected? Surface
// this right at the top of the page — not just buried inside each order
// card — so it can't be missed.
$rejectedOrders = [];
foreach ($orders as $o) {
    if ($o['paid']) continue;
    $pStmt = $conn->prepare("SELECT payment_status FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1");
    $pStmt->bind_param("i", $o['id']);
    $pStmt->execute();
    $pRow = $pStmt->get_result()->fetch_assoc();
    $pStmt->close();
    if ($pRow && $pRow['payment_status'] === 'failed') {
        $rejectedOrders[] = $o['id'];
    }
}

$deleteError = '';

// Delete an old order. Restricted to:
//  - this customer's own orders (never someone else's, checked by customer_id)
//  - only orders staff either hasn't started on yet, or has fully finished:
//      * status = 'collected' (done, safe to clear from history)
//      * status = 'received' AND unpaid (not started, so removing it
//        doesn't pull a record out from under staff mid-wash or mid-delivery)
//    'washing', 'ready', and anything out for delivery stay protected —
//    staff/riders are actively relying on that record existing.
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmt = $conn->prepare("SELECT id, status, paid FROM orders WHERE id = ? AND customer_id = ?");
    $stmt->bind_param("ii", $delId, $customerId);
    $stmt->execute();
    $delOrder = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $canDelete = $delOrder && (
        $delOrder['status'] === 'collected' ||
        ($delOrder['status'] === 'received' && !$delOrder['paid'])
    );

    if (!$delOrder) {
        $deleteError = 'Order not found.';
    } elseif (!$canDelete) {
        $deleteError = 'This order is currently being worked on and can\'t be deleted yet — it can be removed once it\'s completed.';
    } else {
        $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->bind_param("i", $delId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM orders WHERE id = ? AND customer_id = ?");
        $stmt->bind_param("ii", $delId, $customerId);
        $stmt->execute();
        $stmt->close();

        header('Location: customer_dashboard.php?deleted=1');
        exit;
    }
}

// Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['customer_id'], $_SESSION['customer_name']);
    header('Location: customer_login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Muthoni's Laundry</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; color: #333; }
        
        .navbar {
            background: #667eea; color: white;
            padding: 15px 30px; display: flex;
            justify-content: space-between; align-items: center;
        }
        .navbar h1 { font-size: 20px; }
        .navbar a { color: white; text-decoration: none; margin-left: 15px; font-size: 14px; }
        
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        
        .welcome {
            background: white; padding: 20px; border-radius: 8px;
            margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .welcome h2 { font-size: 18px; margin-bottom: 5px; }
        .welcome p { color: #666; font-size: 14px; }
        
        .new-order {
            text-align: center; margin-bottom: 20px;
        }
        .new-order a {
            display: inline-block; padding: 12px 25px;
            background: #667eea; color: white;
            text-decoration: none; border-radius: 5px; font-weight: bold;
        }
        
        /* Order card */
        .order-card {
            background: white; padding: 20px;
            border-radius: 8px; margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .order-header {
            display: flex; justify-content: space-between;
            align-items: flex-start; margin-bottom: 15px;
            padding-bottom: 10px; border-bottom: 1px solid #eee;
        }
        .order-id { font-size: 20px; font-weight: bold; color: #667eea; }
        .order-date { color: #999; font-size: 13px; }
        
        .status-badge {
            display: inline-block; padding: 4px 12px;
            border-radius: 12px; font-size: 12px; font-weight: bold;
        }
        .status-received { background: #e2e8f0; color: #4a5568; }
        .status-washing { background: #fffbeb; color: #b7791f; }
        .status-ready { background: #f0fff4; color: #2f855a; }
        .status-collected { background: #faf5ff; color: #6b46c1; }
        
        .payment-badge {
            display: inline-block; padding: 3px 10px;
            border-radius: 10px; font-size: 11px; font-weight: bold;
            margin-left: 5px;
        }
        .paid { background: #f0fff4; color: #2f855a; }
        .unpaid { background: #fffbeb; color: #b7791f; }
        
        /* Status tracker */
        .tracker {
            display: flex; justify-content: space-between;
            margin: 15px 0; position: relative;
        }
        .tracker::before {
            content: ''; position: absolute; top: 15px;
            left: 10%; right: 10%; height: 3px; background: #ddd;
        }
        .step {
            text-align: center; position: relative; z-index: 1; flex: 1;
        }
        .step-dot {
            width: 34px; height: 34px; border-radius: 50%;
            background: #ddd; color: #888;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 5px; font-size: 14px;
        }
        .step.active .step-dot { background: #667eea; color: white; }
        .step.done .step-dot { background: #48bb78; color: white; }
        .step-label { font-size: 11px; color: #999; }
        .step.active .step-label { color: #667eea; font-weight: bold; }
        .step.done .step-label { color: #48bb78; }
        
        /* Items section */
        .items-section {
            background: #f9f9f9; padding: 12px; border-radius: 6px;
            margin: 12px 0; font-size: 13px;
        }
        .items-section .label {
            font-size: 10px; color: #999; text-transform: uppercase;
            letter-spacing: 0.5px; margin-bottom: 6px;
        }
        .items-section .item-list {
            color: #4a5568; line-height: 1.6;
        }
        .items-section .item-list .item-tag {
            display: inline-block; background: #e2e8f0;
            padding: 2px 8px; border-radius: 4px; margin: 2px;
            font-size: 12px;
        }
        
        .details {
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 10px; margin: 15px 0; font-size: 13px;
        }
        .detail-box { background: #f9f9f9; padding: 10px; border-radius: 5px; text-align: center; }
        .detail-box .label { color: #999; font-size: 11px; text-transform: uppercase; }
        .detail-box .value { font-weight: bold; color: #333; margin-top: 3px; }
        
        .total {
            display: flex; justify-content: space-between;
            padding-top: 10px; border-top: 2px solid #eee;
            font-size: 18px; font-weight: bold; color: #667eea;
        }

        .delivery-box {
            background: #f0f2ff; border: 1px solid #dde1ff;
            border-radius: 8px; padding: 12px; margin: 12px 0;
            font-size: 13px; display: flex; gap: 10px; align-items: flex-start;
        }
        .delivery-box i { color: #667eea; font-size: 16px; margin-top: 2px; }
        .delivery-box .dtitle { font-weight: bold; color: #4a5568; margin-bottom: 2px; }
        .delivery-box .daddr { color: #666; }
        .delivery-box a.map-link { color: #667eea; font-weight: 600; font-size: 12px; }
        
        .alert {
            background: #f0fff4; border: 1px solid #9ae6b4;
            border-radius: 8px; padding: 15px; margin-top: 15px; text-align: center;
        }
        .alert i { color: #48bb78; font-size: 20px; margin-bottom: 5px; }
        .alert p { color: #2f855a; font-weight: bold; margin: 0; }
        
        .empty {
            text-align: center; padding: 50px; color: #999;
        }
        .empty i { font-size: 40px; color: #ddd; margin-bottom: 10px; }
        
        .footer {
            text-align: center; padding: 20px; color: #999; font-size: 13px;
        }
        
        @media (max-width: 600px) {
            .details { grid-template-columns: 1fr 1fr; }
            .navbar { flex-direction: column; gap: 10px; text-align: center; }
            .order-header { flex-direction: column; gap: 8px; }
        }
    </style>
</head>
<body>

    <div class="navbar">
        <h1><i class="fas fa-tshirt"></i> Muthoni's Laundry</h1>
        <div>
            <a href="place_order.php"><i class="fas fa-plus"></i> New Order</a>
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="welcome">
            <h2>Welcome, <?php echo htmlspecialchars($customer['full_name']); ?>!</h2>
            <p>Phone: <?php echo htmlspecialchars($customer['phone']); ?></p>
        </div>

        <?php if (isset($_GET['deleted'])): ?>
        <div style="background:#f0fff4;border:1px solid #c6f6d5;color:#2f855a;padding:12px 16px;border-radius:8px;margin-bottom:15px;font-size:13.5px;">
            <i class="fas fa-check-circle"></i> Order deleted.
        </div>
        <?php elseif ($deleteError): ?>
        <div style="background:#fff5f5;border:1px solid #fed7d7;color:#c53030;padding:12px 16px;border-radius:8px;margin-bottom:15px;font-size:13.5px;">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($deleteError); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($rejectedOrders)): ?>
        <div style="background:#fff5f5;border:1px solid #feb2b2;color:#c53030;padding:14px 16px;border-radius:10px;margin-bottom:15px;">
            <div style="font-weight:700;font-size:14px;margin-bottom:4px;"><i class="fas fa-exclamation-triangle"></i> Invalid Payment Code</div>
            <div style="font-size:13px;">
                <?php if (count($rejectedOrders) === 1): ?>
                The payment code you submitted for order #<?php echo $rejectedOrders[0]; ?> wasn't verified — no payment was recorded.
                <?php else: ?>
                The payment codes you submitted for orders <?php echo implode(', ', array_map(fn($id) => '#' . $id, $rejectedOrders)); ?> weren't verified — no payments were recorded.
                <?php endif; ?>
                Please check your M-Pesa SMS and resubmit below.
            </div>
        </div>
        <?php endif; ?>

        <div class="new-order">
            <a href="place_order.php"><i class="fas fa-shopping-basket"></i> Place New Order</a>
        </div>

        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): 
                $statuses = ['received', 'washing', 'ready', 'collected'];
                $current = strtolower($order['status'] ?? 'received');
                $currentIndex = array_search($current, $statuses);
                if ($currentIndex === false) $currentIndex = 0;
                $isPaid = !empty($order['paid']);
                
                // Fetch individual items for this order
                $oid = (int)$order['id'];
                $itemsRes = $conn->query("SELECT name, qty, price FROM order_items WHERE order_id = $oid");
                $orderItems = [];
                while ($it = $itemsRes->fetch_assoc()) {
                    $orderItems[] = $it;
                }

                // Parse delivery info (a map pin may be embedded in the address as "[Map pin: lat,lng]")
                $deliveryMode = $order['delivery_mode'] ?? 'pickup';
                $rawAddress = $order['delivery_address'] ?? '';
                $mapLink = '';
                $cleanAddress = $rawAddress;
                if (preg_match('/\[Map pin:\s*([\-0-9.]+),\s*([\-0-9.]+)\]/', $rawAddress, $m)) {
                    $mapLink = "https://www.google.com/maps?q={$m[1]},{$m[2]}";
                    $cleanAddress = trim(str_replace($m[0], '', $rawAddress));
                }

                // Was the most recent payment attempt on this order rejected?
                // Surface that here too, not just on the pay page, so it's
                // not missed if they don't happen to click back into it.
                $lastPaymentRejected = false;
                if (!$isPaid) {
                    $pStmt = $conn->prepare("SELECT payment_status FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1");
                    $pStmt->bind_param("i", $oid);
                    $pStmt->execute();
                    $pRow = $pStmt->get_result()->fetch_assoc();
                    $pStmt->close();
                    $lastPaymentRejected = $pRow && $pRow['payment_status'] === 'failed';
                }
            ?>
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <div class="order-id">Order #<?php echo $order['id']; ?></div>
                        <div class="order-date">
                            <?php echo !empty($order['order_date']) ? date('F d, Y', strtotime($order['order_date'])) : 'N/A'; ?>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <span class="status-badge status-<?php echo $current; ?>"><?php echo ucfirst($current); ?></span>
                        <span class="payment-badge <?php echo $isPaid ? 'paid' : 'unpaid'; ?>">
    <?php echo $isPaid ? ('Paid (' . ucfirst($order['payment_method'] ?? 'Cash') . ')') : 'Pay on Pickup'; ?>
</span>
                    </div>
                </div>

                <!-- Status Tracker -->
                <div class="tracker">
                    <?php foreach ($statuses as $i => $s): 
                        $icons = ['received' => 'fa-inbox', 'washing' => 'fa-soap', 'ready' => 'fa-check', 'collected' => 'fa-hand-holding'];
                    ?>
                    <div class="step <?php echo $currentIndex >= $i ? 'done' : ''; ?> <?php echo $currentIndex == $i ? 'active' : ''; ?>">
                        <div class="step-dot"><i class="fas <?php echo $icons[$s]; ?>"></i></div>
                        <div class="step-label"><?php echo ucfirst($s); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pickup / Delivery Info -->
                <div class="delivery-box">
                    <?php if ($deliveryMode === 'delivery'): ?>
                        <i class="fas fa-motorcycle"></i>
                        <div>
                            <div class="dtitle">Home pickup &amp; delivery</div>
                            <div class="daddr"><?php echo !empty($cleanAddress) ? htmlspecialchars($cleanAddress) : 'No address on file'; ?></div>
                            <?php if ($mapLink): ?>
                            <a class="map-link" href="<?php echo htmlspecialchars($mapLink); ?>" target="_blank" rel="noopener">
                                <i class="fas fa-map-location-dot"></i> View pinned location
                            </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <i class="fas fa-store"></i>
                        <div>
                            <div class="dtitle">Drop off &amp; collect in person</div>
                            <div class="daddr">No pickup/delivery requested for this order</div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Order Items -->
                <?php if (!empty($orderItems)): ?>
                <div class="items-section">
                    <div class="label"><i class="fas fa-list"></i> Items Ordered</div>
                    <div class="item-list">
                        <?php foreach ($orderItems as $it): ?>
                        <span class="item-tag"><?php echo htmlspecialchars($it['name']); ?> x<?php echo $it['qty']; ?> (Ksh <?php echo number_format($it['qty'] * $it['price'], 0); ?>)</span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="details">
                    <div class="detail-box">
                        <div class="label">Service</div>
                        <div class="value"><?php echo !empty($orderItems) ? htmlspecialchars($orderItems[0]['name']) : 'Standard'; ?></div>
                    </div>
                    <div class="detail-box">
                        <div class="label">Items</div>
                        <div class="value"><?php echo $order['item_count'] ?? '—'; ?></div>
                    </div>
                   <div class="detail-box">
    <div class="label">Payment</div>
    <div class="value"><?php echo $isPaid ? ucfirst($order['payment_method'] ?? 'Cash') : 'Pay on Pickup'; ?></div>
</div>
                    <div class="detail-box">
                        <div class="label">Updated</div>
                        <div class="value"><?php echo !empty($order['updated_at']) ? date('M d', strtotime($order['updated_at'])) : '—'; ?></div>
                    </div>
                </div>

                <div class="total">
                    <span>Total</span>
                    <span>Ksh <?php echo number_format($order['total_amount'] ?? 0, 2); ?></span>
                </div>

                <?php if ($lastPaymentRejected): ?>
                <div style="background:#fff5f5;border:1px solid #feb2b2;color:#c53030;border-radius:8px;padding:10px 12px;margin:10px 0;font-size:12.5px;">
                    <i class="fas fa-exclamation-triangle"></i> Invalid Payment Code — no payment was recorded.
                    <a href="pay_later.php?id=<?php echo $order['id']; ?>" style="color:#c53030;font-weight:700;text-decoration:underline;">Resubmit payment</a>
                </div>
                <?php endif; ?>

                <?php if ($current === 'ready'): ?>
                <div class="alert">
                    <i class="fas fa-bell"></i>
                    <p>Your laundry is ready for pickup!</p>
                    <small style="color:#718096;">Bring order number: <strong>#<?php echo $order['id']; ?></strong></small>
                </div>
                <?php elseif ($current === 'collected'): ?>
                <div style="text-align:center;color:#48bb78;margin-top:10px;">
                    <i class="fas fa-check-circle"></i> Order collected. Thank you!
                </div>
                <?php endif; ?>

                <?php if ($current === 'collected' || ($current === 'received' && !$isPaid)): ?>
                <div style="text-align:center;margin-top:10px;">
                    <a href="customer_dashboard.php?delete=<?php echo $order['id']; ?>"
                       onclick="return confirm('Delete order #<?php echo $order['id']; ?> from your history? This cannot be undone.')"
                       style="color:#a0aec0;font-size:12px;text-decoration:none;">
                        <i class="fas fa-trash"></i> Delete this order
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty">
                <i class="fas fa-box-open"></i>
                <h3>No orders yet</h3>
                <p>Place your first order to see it here.</p>
                <a href="place_order.php" style="display:inline-block;padding:10px 20px;background:#667eea;color:white;border-radius:5px;text-decoration:none;margin-top:10px;">Place Order</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="footer">
        <p>Need help? Call <strong>0700-000-000</strong></p>
        <p>&copy; 2026 Muthoni's Laundry</p>
    </div>

</body>
</html>
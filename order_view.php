<?php
// order_view.php — View Order Details
// Staff can see full order info, print a laundry tag with barcode,
// and quickly update the order status.

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

$pageTitle = 'View Order';
$activePage = 'orders';

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$orderId = (int)$_GET['id'];

// Get order
$stmt = $conn->prepare("SELECT o.*, u.full_name as created_by_name FROM orders o LEFT JOIN users u ON o.created_by = u.id WHERE o.id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Get items
$stmt = $conn->prepare("SELECT name, qty, price FROM order_items WHERE order_id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$itemCount = array_sum(array_column($items, 'qty'));

// Status for tracker
$statuses = ['received', 'washing', 'ready', 'collected'];
$currentIndex = array_search($order['status'], $statuses);
if ($currentIndex === false) $currentIndex = 0;

function formatMoney($amount) {
    return 'Ksh ' . number_format($amount, 2);
}

// Same format used in barcode_scan.php / scan_barcode.php / orders.php —
// kept identical everywhere so a code always means the same order.
function formatOrderId($id) {
    return 'ORD-' . (1000 + (int)$id);
}

include 'header.php';
?>

<style>
@media print {
    .no-print { display: none !important; }
    .sidebar, .topbar { display: none !important; }
    .main-content { margin-left: 0 !important; padding: 20px !important; }
    body { background: white !important; }
    .print-tag { margin: 0 auto !important; border: 2px solid #333 !important; box-shadow: none !important; }
}
</style>

<div class="no-print" style="margin-bottom: 20px;">
    <h1 style="font-size: 24px; margin-bottom: 5px;">Order <?php echo htmlspecialchars(formatOrderId($orderId)); ?></h1>
    <div style="display: flex; gap: 10px;">
        <button onclick="window.print()" style="background: #667eea; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">Print Tag</button>
        <a href="order_form.php?edit=<?php echo $orderId; ?>" style="background: #888; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-size: 14px;">Edit</a>
        <a href="orders.php" style="background: #ddd; color: #333; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-size: 14px;">Back</a>
    </div>
</div>

<!-- Status Tracker -->
<div class="no-print" style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ddd;">
    <div style="display: flex; justify-content: space-between; position: relative; margin: 20px 0;">
        <div style="position: absolute; top: 15px; left: 10%; right: 10%; height: 3px; background: #ddd; z-index: 0;"></div>
        <?php 
        $icons = ['received' => 'fa-inbox', 'washing' => 'fa-soap', 'ready' => 'fa-check', 'collected' => 'fa-hand-holding'];
        foreach ($statuses as $i => $s): 
            $active = $i === $currentIndex;
            $done = $i < $currentIndex;
        ?>
        <div style="text-align: center; position: relative; z-index: 1; flex: 1;">
            <div style="width: 34px; height: 34px; border-radius: 50%; margin: 0 auto 5px; display: flex; align-items: center; justify-content: center; font-size: 14px; <?php echo $active ? 'background: #667eea; color: white;' : ($done ? 'background: #48bb78; color: white;' : 'background: #ddd; color: #888;'); ?>">
                <i class="fas <?php echo $icons[$s]; ?>"></i>
            </div>
            <div style="font-size: 11px; <?php echo $active ? 'color: #667eea; font-weight: bold;' : ($done ? 'color: #48bb78;' : 'color: #999;'); ?>"><?php echo ucfirst($s); ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Order Info -->
<div class="no-print" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
    <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
        <h3 style="margin: 0 0 15px 0; font-size: 16px;">Customer</h3>
        <p style="margin: 5px 0;"><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name'] ?? 'Walk-in'); ?></p>
        <p style="margin: 5px 0;"><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone'] ?? '-'); ?></p>
        <p style="margin: 5px 0;"><strong>Order Date:</strong> <?php echo date('M d, Y', strtotime($order['order_date'])); ?></p>
    </div>
    <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
        <h3 style="margin: 0 0 15px 0; font-size: 16px;">Order Details</h3>
        <p style="margin: 5px 0;"><strong>Status:</strong> 
            <span style="padding: 3px 10px; border-radius: 10px; font-size: 11px; font-weight: bold; <?php 
                echo $order['status']==='received' ? 'background: #e2e8f0; color: #4a5568;' : 
                     ($order['status']==='washing' ? 'background: #fffbeb; color: #b7791f;' : 
                     ($order['status']==='ready' ? 'background: #f0fff4; color: #2f855a;' : 'background: #faf5ff; color: #6b46c1;')); 
            ?>"><?php echo ucfirst($order['status']); ?></span>
        </p>
        <p style="margin: 5px 0;"><strong>Payment:</strong> 
    <?php if ($order['paid']): ?>
        <span style="color: #2f855a; font-weight: 600;">
            <i class="fas fa-check-circle"></i> Paid (<?php echo ucfirst($order['payment_method'] ?? 'Cash'); ?>)
            <?php if (($order['payment_method'] ?? '') === 'mpesa' && !empty($order['mpesa_code'])): ?>
                <br><small style="color: #718096;">Code: <?php echo htmlspecialchars($order['mpesa_code']); ?></small>
            <?php endif; ?>
        </span>
    <?php else: ?>
        <span style="color: #b7791f; font-weight: 600;"><i class="fas fa-clock"></i> Pending — Pay on Pickup</span>
    <?php endif; ?>
</p>
        <p style="margin: 5px 0;"><strong>Created By:</strong> <?php echo htmlspecialchars($order['created_by_name'] ?? 'System'); ?></p>
        <?php if (!empty($order['notes'])): ?>
        <p style="margin: 5px 0;"><strong>Notes:</strong> <?php echo htmlspecialchars($order['notes']); ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Items -->
<div class="no-print" style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 20px;">
    <h3 style="margin: 0 0 15px 0; font-size: 16px;">Items (<?php echo $itemCount; ?> total)</h3>
    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
        <thead>
            <tr style="background: #f5f5f5;">
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Item</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Qty</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Price</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px;"><?php echo htmlspecialchars($item['name']); ?></td>
                <td style="padding: 10px;"><?php echo $item['qty']; ?></td>
                <td style="padding: 10px;"><?php echo formatMoney($item['price']); ?></td>
                <td style="padding: 10px;"><?php echo formatMoney($item['qty'] * $item['price']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="padding: 10px; text-align: right; font-weight: bold;">Grand Total:</td>
                <td style="padding: 10px; font-weight: bold; color: #667eea; font-size: 16px;"><?php echo formatMoney($order['total_amount']); ?></td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Print Tag -->
<div class="print-tag" style="background: white; padding: 30px; border-radius: 8px; border: 2px solid #333; max-width: 300px; margin: 0 auto; text-align: center;">
    <h2 style="font-size: 18px; margin-bottom: 5px; letter-spacing: 2px;">MUTHONI'S LAUNDRY</h2>
    <p style="font-size: 10px; color: #666; margin-bottom: 15px;">Quality You Can Trust</p>
    
    <canvas id="barcode" width="200" height="60"></canvas>
    <p style="font-family: monospace; font-size: 14px; letter-spacing: 3px; margin: 10px 0;"><?php echo htmlspecialchars(formatOrderId($orderId)); ?></p>
    
    <hr style="border: none; border-top: 1px dashed #ccc; margin: 15px 0;">
    
    <p style="font-size: 16px; font-weight: bold; margin: 5px 0;"><?php echo htmlspecialchars($order['customer_name'] ?? 'Walk-in'); ?></p>
    <p style="font-size: 12px; color: #666; margin: 5px 0;"><?php echo htmlspecialchars($order['phone'] ?? ''); ?></p>
    
    <div style="display: flex; justify-content: space-between; font-size: 12px; margin: 10px 0;">
        <span><?php echo $itemCount; ?> items</span>
        <span><?php echo formatMoney($order['total_amount']); ?></span>
    </div>
    
    <p style="font-size: 20px; font-weight: bold; color: #667eea; margin: 10px 0;"><?php echo formatMoney($order['total_amount']); ?></p>
    <p style="font-size: 11px; color: #999;"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></p>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
JsBarcode("#barcode", "<?php echo formatOrderId($orderId); ?>", {
    format: "CODE128",
    lineColor: "#000",
    width: 2,
    height: 40,
    displayValue: false,
    margin: 0
});
</script>

<?php include 'footer.php'; ?>
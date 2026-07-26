<?php
// dashboard_staff.php — Staff Dashboard
// Staff see their daily summary: total orders, counts by status,
// recent orders, and a quick search bar to find orders fast.

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

$pageTitle = 'Dashboard';
$activePage = 'dashboard';

// Stats
$totalOrders = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$received = $conn->query("SELECT COUNT(*) FROM orders WHERE status='received'")->fetch_row()[0];
$washing = $conn->query("SELECT COUNT(*) FROM orders WHERE status='washing'")->fetch_row()[0];
$ready = $conn->query("SELECT COUNT(*) FROM orders WHERE status='ready'")->fetch_row()[0];

// Payment & Inventory Stats
$paidToday = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE DATE(created_at) = CURDATE() AND payment_status = 'paid'")->fetch_row()[0] ?? 0;
$lowStock = $conn->query("SELECT COUNT(*) FROM inventory WHERE quantity <= min_stock")->fetch_row()[0] ?? 0;
$pendingPayments = $conn->query("SELECT COUNT(*) FROM payments WHERE payment_status = 'pending'")->fetch_row()[0] ?? 0;

// Recent orders
$result = $conn->query("
    SELECT o.*, COUNT(oi.id) as item_count, SUM(oi.qty) as total_items 
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    GROUP BY o.id 
    ORDER BY o.order_date DESC 
    LIMIT 5
");
$recentOrders = [];
while ($row = $result->fetch_assoc()) {
    $recentOrders[] = $row;
}

// Today's payments
$todayPayments = $conn->query("SELECT p.*, o.customer_name FROM payments p JOIN orders o ON p.order_id = o.id WHERE DATE(p.created_at) = CURDATE() AND p.payment_status = 'paid' ORDER BY p.created_at DESC LIMIT 5");

// Low stock items
$lowStockItems = $conn->query("SELECT item_name, quantity, min_stock, unit FROM inventory WHERE quantity <= min_stock ORDER BY quantity ASC LIMIT 5");

function formatMoney($amount) {
    return 'Ksh ' . number_format($amount, 2);
}
function formatDate($date) {
    return date('Y-m-d H:i', strtotime($date));
}

include 'header.php';
?>

<div style="margin-bottom: 20px;">
    <h1 style="font-size: 24px; margin-bottom: 5px;">Dashboard</h1>
    <p style="color: #666; font-size: 14px;">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 25px;">
    <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea;">
        <p style="margin: 0; font-size: 12px; color: #666;">Total Orders</p>
        <p style="margin: 5px 0 0 0; font-size: 28px; font-weight: bold;"><?php echo $totalOrders; ?></p>
    </div>
    <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #f9ab00;">
        <p style="margin: 0; font-size: 12px; color: #666;">Received</p>
        <p style="margin: 5px 0 0 0; font-size: 28px; font-weight: bold;"><?php echo $received; ?></p>
    </div>
    <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #f9ab00;">
        <p style="margin: 0; font-size: 12px; color: #666;">Washing</p>
        <p style="margin: 5px 0 0 0; font-size: 28px; font-weight: bold;"><?php echo $washing; ?></p>
    </div>
    <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #34a853;">
        <p style="margin: 0; font-size: 12px; color: #666;">Ready</p>
        <p style="margin: 5px 0 0 0; font-size: 28px; font-weight: bold;"><?php echo $ready; ?></p>
    </div>
    <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #48bb78;">
        <p style="margin: 0; font-size: 12px; color: #666;">Paid Today</p>
        <p style="margin: 5px 0 0 0; font-size: 28px; font-weight: bold;"><?php echo 'Ksh ' . number_format($paidToday, 0); ?></p>
    </div>
    <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #ed8936;">
        <p style="margin: 0; font-size: 12px; color: #666;">Low Stock</p>
        <p style="margin: 5px 0 0 0; font-size: 28px; font-weight: bold;"><?php echo $lowStock; ?></p>
    </div>
</div>

<div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ddd;">
    <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
        <input type="text" id="searchInput" placeholder="Search order number..." 
               style="flex: 1; min-width: 200px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        <button onclick="findOrder()" style="background: #667eea; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">Find</button>
        <a href="order_form.php" style="background: #34a853; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-size: 14px;">New Order</a>
        <a href="scan_barcode.php" style="background: #888; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-size: 14px;">Scan Barcode</a>
        <a href="payments.php" style="background: #48bb78; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-size: 14px;"><i class="fas fa-money-bill-wave"></i> Payments</a>
        <a href="inventory.php" style="background: #ed8936; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-size: 14px;"><i class="fas fa-boxes"></i> Inventory</a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px;">
    <!-- Recent Orders -->
    <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0; font-size: 16px;">Recent Orders</h3>
            <a href="orders.php" style="background: #667eea; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 13px;">View All</a>
        </div>
        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="background: #f5f5f5;">
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Order #</th>
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Customer</th>
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Items</th>
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Total</th>
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Status</th>
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Payment</th>
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentOrders as $o): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;"><strong>#<?php echo $o['id']; ?></strong></td>
                    <td style="padding: 10px;"><?php echo htmlspecialchars($o['customer_name']); ?></td>
                    <td style="padding: 10px;"><?php echo ($o['total_items'] ?? 0); ?> items</td>
                    <td style="padding: 10px;"><?php echo formatMoney($o['total_amount']); ?></td>
                    <td style="padding: 10px;">
                        <?php 
                        $color = '';
                        if ($o['status'] === 'received') $color = '#888';
                        elseif ($o['status'] === 'washing') $color = '#f9ab00';
                        elseif ($o['status'] === 'ready') $color = '#34a853';
                        else $color = '#667eea';
                        ?>
                        <span style="background: <?php echo $color; ?>20; color: <?php echo $color; ?>; padding: 3px 10px; border-radius: 10px; font-size: 11px; font-weight: bold;">
                            <?php echo ucfirst($o['status']); ?>
                        </span>
                    </td>
                    <td style="padding: 10px;">
                        <?php if ($o['paid']): ?>
                            <span style="font-size:11px;color:#2f855a;font-weight:600;">
                                <i class="fas fa-check"></i> <?php echo ucfirst($o['payment_method'] ?? 'Cash'); ?>
                            </span>
                        <?php else: ?>
                            <span style="font-size:11px;color:#b7791f;">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 10px; color: #666;"><?php echo formatDate($o['order_date']); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recentOrders)): ?>
                <tr><td colspan="7" style="padding: 30px; text-align: center; color: #999;">No orders yet</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Right Column: Payments & Inventory -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <!-- Today's Payments -->
        <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; font-size: 16px;"><i class="fas fa-money-bill-wave" style="color:#48bb78;"></i> Today's Payments</h3>
                <a href="payments.php" style="background: #48bb78; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 13px;">View All</a>
            </div>
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="background: #f5f5f5;">
                        <th style="padding: 8px; text-align: left; border-bottom: 2px solid #ddd;">Order</th>
                        <th style="padding: 8px; text-align: left; border-bottom: 2px solid #ddd;">Amount</th>
                        <th style="padding: 8px; text-align: left; border-bottom: 2px solid #ddd;">Method</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($p = $todayPayments->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 8px;"><strong>#<?php echo $p['order_id']; ?></strong></td>
                        <td style="padding: 8px; font-weight: 600;"><?php echo formatMoney($p['amount']); ?></td>
                        <td style="padding: 8px;"><?php echo ucfirst($p['payment_method']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if ($todayPayments->num_rows === 0): ?>
                    <tr><td colspan="3" style="padding: 20px; text-align: center; color: #999;">No payments today</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php if ($pendingPayments > 0): ?>
            <div style="margin-top: 12px; padding: 10px; background: #fffbeb; border-radius: 6px; border: 1px solid #fbd38d;">
                <span style="font-size: 12px; color: #975a16;"><i class="fas fa-clock"></i> <?php echo $pendingPayments; ?> payment(s) pending verification</span>
                <a href="payments.php" style="float: right; font-size: 12px; color: #667eea; font-weight: 600;">Verify &rarr;</a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Low Stock Alert -->
        <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; font-size: 16px;"><i class="fas fa-boxes" style="color:#ed8936;"></i> Low Stock Alert</h3>
                <a href="inventory.php" style="background: #ed8936; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 13px;">Manage</a>
            </div>
            <?php if ($lowStock > 0): ?>
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="background: #f5f5f5;">
                        <th style="padding: 8px; text-align: left; border-bottom: 2px solid #ddd;">Item</th>
                        <th style="padding: 8px; text-align: center; border-bottom: 2px solid #ddd;">Stock</th>
                        <th style="padding: 8px; text-align: center; border-bottom: 2px solid #ddd;">Min</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $lowStockItems->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 8px;"><?php echo htmlspecialchars($item['item_name']); ?></td>
                        <td style="padding: 8px; text-align: center; color: #c53030; font-weight: 600;"><?php echo $item['quantity'] . ' ' . $item['unit']; ?></td>
                        <td style="padding: 8px; text-align: center; color: #718096;"><?php echo $item['min_stock']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div style="text-align: center; padding: 20px; color: #48bb78;">
                <i class="fas fa-check-circle" style="font-size: 24px; margin-bottom: 8px;"></i>
                <p style="font-size: 13px; margin: 0;">All stock levels are good!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function findOrder() {
    const val = document.getElementById('searchInput').value.trim();
    if (val) window.location = 'order_view.php?id=' + val;
}
document.getElementById('searchInput').addEventListener('keypress', e => {
    if (e.key === 'Enter') { e.preventDefault(); findOrder(); }
});
</script>

<?php include 'footer.php'; ?>
<?php
// reports.php — Business Reports & Financial Analytics (merged)
// Everything here is REAL data pulled from the system:
//   Revenue   = SUM(payments.amount) WHERE payment_status = 'paid'
//   Expenses  = SUM(expenses.amount)
//   Profit    = Revenue - Expenses
// Nothing on this page is estimated or fake, except the "target" figure,
// which is a goal the owner sets manually (stored in the settings table).

session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'manager' && $_SESSION['role'] !== 'admin')) {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

$pageTitle = 'Reports';
$activePage = 'reports';
$role = $_SESSION['role'];

$message = '';
$messageType = '';

// ============================================
// OWNER'S REVENUE TARGET (stored in settings table)
// ============================================
// Make sure the setting exists so this page never breaks on a fresh install
$targetRow = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'monthly_revenue_target'");
if ($targetRow && $targetRow->num_rows === 0) {
    $conn->query("INSERT INTO settings (setting_key, setting_value, setting_group, description)
                  VALUES ('monthly_revenue_target', '0', 'financial', 'Monthly revenue target set by the owner')");
    $targetRow = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'monthly_revenue_target'");
}
$monthlyTarget = (float)($targetRow->fetch_assoc()['setting_value'] ?? 0);

// Only admin can update the target (same rule as settings.php)
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_target'])) {
    $newTarget = (float)($_POST['monthly_revenue_target'] ?? 0);
    if ($newTarget >= 0) {
        $conn->query("UPDATE settings SET setting_value = '$newTarget' WHERE setting_key = 'monthly_revenue_target'");
        $monthlyTarget = $newTarget;
        $message = 'Revenue target updated.';
        $messageType = 'success';
    }
}

// ============================================
// ORDERS BY STATUS (pie chart data)
// ============================================
$statusResult = $conn->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status ORDER BY count DESC");
$statusLabels = [];
$statusData = [];
$statusColors = [];
$statusColorMap = [
    'received'  => '#718096',
    'washing'   => '#ed8936',
    'ready'     => '#48bb78',
    'collected' => '#9f7aea',
    'cancelled' => '#e53e3e'
];
$totalOrdersAll = 0;
while ($row = $statusResult->fetch_assoc()) {
    $statusLabels[] = ucfirst($row['status']);
    $statusData[] = (int)$row['count'];
    $totalOrdersAll += (int)$row['count'];
    $statusColors[] = $statusColorMap[$row['status']] ?? '#667eea';
}

// ============================================
// REAL REVENUE / EXPENSES / PROFIT — last 12 months
// ============================================
$monthLabels = [];
$revenueData = [];
$expenseData = [];
$profitData = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthLabels[] = date('M Y', strtotime("-$i months"));

    $res = $conn->query("SELECT COALESCE(SUM(amount), 0) as total
                          FROM payments
                          WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'
                          AND payment_status = 'paid'");
    $rev = (float)($res->fetch_assoc()['total'] ?? 0);

    $expRes = $conn->query("SELECT COALESCE(SUM(amount), 0) as total
                             FROM expenses
                             WHERE DATE_FORMAT(expense_date, '%Y-%m') = '$month'");
    $exp = (float)($expRes->fetch_assoc()['total'] ?? 0);

    $revenueData[] = $rev;
    $expenseData[] = $exp;
    $profitData[]  = round($rev - $exp, 2);
}

// ============================================
// ALL-TIME REAL TOTALS
// ============================================
$totalRevenue = (float)($conn->query("SELECT COALESCE(SUM(amount), 0) as total
                                        FROM payments
                                        WHERE payment_status = 'paid'")->fetch_assoc()['total'] ?? 0);

$totalExpenses = (float)($conn->query("SELECT COALESCE(SUM(amount), 0) as total
                                        FROM expenses")->fetch_assoc()['total'] ?? 0);

$netProfit = $totalRevenue - $totalExpenses;
$profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

$totalPaidOrders = (int)($conn->query("SELECT COUNT(*) as c FROM orders WHERE paid = 1 AND status != 'cancelled'")->fetch_assoc()['c'] ?? 0);
$totalOrders = (int)($conn->query("SELECT COUNT(*) as c FROM orders WHERE status != 'cancelled'")->fetch_assoc()['c'] ?? 0);
$avgOrder = $totalPaidOrders > 0 ? $totalRevenue / $totalPaidOrders : 0;
$collectionRate = $totalOrders > 0 ? ($totalPaidOrders / $totalOrders) * 100 : 0;

$unpaidOrdersTotal = (float)($conn->query("SELECT COALESCE(SUM(total_amount), 0) as total
                                             FROM orders
                                             WHERE paid = 0 AND status != 'cancelled'")->fetch_assoc()['total'] ?? 0);

$readyUnpaid = (int)($conn->query("SELECT COUNT(*) as c FROM orders WHERE status = 'ready' AND paid = 0")->fetch_assoc()['c'] ?? 0);
$readyUnpaidValue = (float)($conn->query("SELECT COALESCE(SUM(total_amount), 0) as c FROM orders WHERE status = 'ready' AND paid = 0")->fetch_assoc()['c'] ?? 0);

$pendingPayments = (float)($conn->query("SELECT COALESCE(SUM(amount), 0) as total
                                          FROM payments
                                          WHERE payment_status = 'pending'")->fetch_assoc()['total'] ?? 0);

// This month's revenue vs target
$thisMonth = date('Y-m');
$lastMonth = date('Y-m', strtotime('-1 month'));
$thisMonthRevenue = (float)($conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments
                                           WHERE DATE_FORMAT(created_at, '%Y-%m') = '$thisMonth' AND payment_status = 'paid'")->fetch_assoc()['total'] ?? 0);
$lastMonthRevenue = (float)($conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments
                                           WHERE DATE_FORMAT(created_at, '%Y-%m') = '$lastMonth' AND payment_status = 'paid'")->fetch_assoc()['total'] ?? 0);
$monthGrowth = $lastMonthRevenue > 0 ? (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;
$targetProgress = $monthlyTarget > 0 ? min(($thisMonthRevenue / $monthlyTarget) * 100, 100) : 0;
$targetHit = $monthlyTarget > 0 && $thisMonthRevenue >= $monthlyTarget;

// ============================================
// REVENUE BY PAYMENT METHOD
// ============================================
$methodLabels = [];
$methodData = [];
$res = $conn->query("SELECT payment_method, SUM(amount) as total
                      FROM payments
                      WHERE payment_status = 'paid'
                      GROUP BY payment_method
                      ORDER BY total DESC");
while ($row = $res->fetch_assoc()) {
    $methodLabels[] = ucfirst($row['payment_method']);
    $methodData[] = (float)$row['total'];
}

// ============================================
// REVENUE BY SERVICE (paid orders only)
// ============================================
$serviceLabels = [];
$serviceData = [];
$res = $conn->query("SELECT oi.name as service_name, COALESCE(SUM(oi.qty * oi.price), 0) as total
                     FROM order_items oi
                     JOIN orders o ON oi.order_id = o.id
                     WHERE o.paid = 1 AND o.status != 'cancelled' AND oi.name IS NOT NULL AND oi.name != ''
                     GROUP BY oi.name
                     ORDER BY total DESC
                     LIMIT 8");
while ($row = $res->fetch_assoc()) {
    $serviceLabels[] = $row['service_name'];
    $serviceData[] = (float)$row['total'];
}

// ============================================
// TOP EXPENSE CATEGORIES
// ============================================
$expenseCategories = [];
$expenseCatRes = $conn->query("SELECT category, COALESCE(SUM(amount), 0) as total FROM expenses GROUP BY category ORDER BY total DESC LIMIT 6");
while ($row = $expenseCatRes->fetch_assoc()) {
    $expenseCategories[] = $row;
}

// ============================================
// RECENT REVENUE TRANSACTIONS
// ============================================
$recentPayments = $conn->query("SELECT p.*, o.customer_name
                                FROM payments p
                                LEFT JOIN orders o ON p.order_id = o.id
                                WHERE p.payment_status = 'paid'
                                ORDER BY p.created_at DESC
                                LIMIT 15");

$hasRevenue = $totalRevenue > 0;
$hasExpenses = $totalExpenses > 0;

function formatMoney($amount) {
    return 'Ksh ' . number_format($amount, 2);
}

include 'header.php';
?>

<div class="page-header">
    <h1>Reports</h1>
    <p>Real revenue, real expenses, real profit — pulled straight from your orders, payments and expenses.</p>
</div>

<?php if ($message): ?>
<div class="inline-message show <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<!-- ============================================
     MONTHLY REVENUE TARGET
     ============================================ -->
<section class="table-container" style="margin-bottom:20px;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;">
        <div style="flex:1;min-width:240px;">
            <h3 style="font-size:15px;font-weight:700;margin-bottom:4px;">Monthly Revenue Target — <?php echo date('F Y'); ?></h3>
            <?php if ($monthlyTarget > 0): ?>
                <p style="font-size:13px;color:#718096;margin-bottom:12px;">
                    <?php echo formatMoney($thisMonthRevenue); ?> collected of <?php echo formatMoney($monthlyTarget); ?> target
                    (<?php echo number_format($targetProgress, 1); ?>%)
                </p>
                <div style="width:100%;height:14px;background:#e2e8f0;border-radius:7px;overflow:hidden;">
                    <div style="width:<?php echo $targetProgress; ?>%;height:100%;background:<?php echo $targetHit ? '#48bb78' : '#667eea'; ?>;border-radius:7px;transition:width .3s;"></div>
                </div>
                <?php if ($targetHit): ?>
                <p style="font-size:12.5px;color:#2f855a;font-weight:600;margin-top:8px;">Target reached! 🎉</p>
                <?php else: ?>
                <p style="font-size:12.5px;color:#718096;margin-top:8px;">Ksh <?php echo number_format(max($monthlyTarget - $thisMonthRevenue, 0), 2); ?> remaining to hit target</p>
                <?php endif; ?>
            <?php else: ?>
                <p style="font-size:13px;color:#a0aec0;">No target set yet. <?php echo $role === 'admin' ? 'Set one on the right.' : 'Ask an admin to set one.'; ?></p>
            <?php endif; ?>
        </div>
        <?php if ($role === 'admin'): ?>
        <form method="POST" style="min-width:220px;">
            <label style="display:block;font-size:12px;font-weight:600;color:#4a5568;margin-bottom:6px;">Set target (Ksh)</label>
            <div style="display:flex;gap:8px;">
                <input type="number" name="monthly_revenue_target" min="0" step="100" value="<?php echo $monthlyTarget; ?>"
                       style="width:140px;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:13.5px;">
                <button type="submit" name="update_target" class="btn btn-primary" style="background:#667eea;color:#fff;border:none;padding:8px 16px;border-radius:7px;font-weight:600;cursor:pointer;">Save</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</section>

<?php if ($readyUnpaid > 0): ?>
<div style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);border:1px solid #fbd38d;border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:14px;">
    <i class="fas fa-hand-holding-usd" style="font-size:26px;color:#b7791f;"></i>
    <div>
        <div style="font-weight:700;font-size:14.5px;color:#744210;"><?php echo $readyUnpaid; ?> order(s) ready but NOT paid</div>
        <div style="font-size:13px;color:#975a16;">Collect <strong><?php echo formatMoney($readyUnpaidValue); ?></strong> when customers pick up. Not counted as revenue yet.</div>
    </div>
</div>
<?php endif; ?>

<!-- ============================================
     KEY FINANCIAL STATS
     ============================================ -->
<section class="stats-grid" style="margin-bottom:24px;">
    <div class="stat-card" style="border-left-color:#667eea;">
        <div class="stat-label">Real Revenue (Paid)</div>
        <div class="stat-number"><?php echo formatMoney($totalRevenue); ?></div>
        <div style="font-size:11px;color:#718096;margin-top:4px;"><?php echo $totalPaidOrders; ?> paid orders</div>
    </div>
    <div class="stat-card" style="border-left-color:#e53e3e;">
        <div class="stat-label">Real Expenses</div>
        <div class="stat-number"><?php echo formatMoney($totalExpenses); ?></div>
        <div style="font-size:11px;color:#718096;margin-top:4px;">Actual costs recorded</div>
    </div>
    <div class="stat-card" style="border-left-color:<?php echo $netProfit >= 0 ? '#48bb78' : '#e53e3e'; ?>;">
        <div class="stat-label">Net Profit / Loss</div>
        <div class="stat-number" style="color:<?php echo $netProfit >= 0 ? '#2f855a' : '#c53030'; ?>;">
            <?php echo $netProfit >= 0 ? '+' : ''; ?><?php echo formatMoney($netProfit); ?>
        </div>
        <div style="font-size:11px;color:#718096;"><?php echo number_format($profitMargin, 1); ?>% margin</div>
    </div>
    <div class="stat-card" style="border-left-color:#ed8936;">
        <div class="stat-label">Collection Rate</div>
        <div class="stat-number" style="color:<?php echo $collectionRate >= 80 ? '#2f855a' : ($collectionRate >= 50 ? '#b7791f' : '#c53030'); ?>">
            <?php echo number_format($collectionRate, 1); ?>%
        </div>
        <div style="font-size:11px;color:#718096;"><?php echo $totalPaidOrders; ?>/<?php echo $totalOrders; ?> orders paid</div>
    </div>
    <div class="stat-card" style="border-left-color:#9f7aea;">
        <div class="stat-label">Avg Paid Order</div>
        <div class="stat-number"><?php echo formatMoney($avgOrder); ?></div>
        <div style="font-size:11px;color:#718096;">Revenue per paid order</div>
    </div>
    <div class="stat-card" style="border-left-color:#4299e1;">
        <div class="stat-label">Unpaid Orders</div>
        <div class="stat-number" style="color:#c53030;"><?php echo formatMoney($unpaidOrdersTotal); ?></div>
        <div style="font-size:11px;color:#718096;">Money not yet received</div>
    </div>
    <div class="stat-card" style="border-left-color:#764ba2;">
        <div class="stat-label">Pending Payments</div>
        <div class="stat-number"><?php echo formatMoney($pendingPayments); ?></div>
        <div style="font-size:11px;color:#718096;">Awaiting confirmation</div>
    </div>
    <div class="stat-card" style="border-left-color:<?php echo $monthGrowth >= 0 ? '#48bb78' : '#e53e3e'; ?>;">
        <div class="stat-label">Revenue vs Last Month</div>
        <div class="stat-number" style="color:<?php echo $monthGrowth >= 0 ? '#2f855a' : '#c53030'; ?>">
            <?php echo $monthGrowth >= 0 ? '+' : ''; ?><?php echo number_format($monthGrowth, 1); ?>%
        </div>
        <div style="font-size:11px;color:#718096;"><?php echo formatMoney($thisMonthRevenue); ?> this month</div>
    </div>
</section>

<!-- ============================================
     ORDERS BY STATUS  +  REVENUE/EXPENSES/PROFIT TREND
     ============================================ -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">

    <section class="table-container">
        <div class="table-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h3 style="font-size:15px;font-weight:700;">Orders by Status</h3>
            <span style="font-size:12px;color:#718096;"><?php echo $totalOrdersAll; ?> total</span>
        </div>
        <?php if (!empty($statusData) && array_sum($statusData) > 0): ?>
        <div style="max-width:280px;margin:0 auto;">
            <canvas id="statusPieChart"></canvas>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center;margin-top:16px;">
            <?php foreach ($statusLabels as $i => $label):
                $color = $statusColors[$i] ?? '#667eea';
                $count = $statusData[$i] ?? 0;
                $pct = $totalOrdersAll > 0 ? round(($count / $totalOrdersAll) * 100, 1) : 0;
            ?>
            <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:#4a5568;">
                <span style="width:10px;height:10px;border-radius:50%;background:<?php echo $color; ?>"></span>
                <?php echo $label; ?> (<?php echo $count; ?>, <?php echo $pct; ?>%)
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:50px;color:#a0aec0;">
            <i class="fas fa-chart-pie" style="font-size:40px;margin-bottom:12px;display:block;"></i>
            <p>No order data available yet</p>
        </div>
        <?php endif; ?>
    </section>

    <section class="table-container">
        <div class="table-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h3 style="font-size:15px;font-weight:700;">Revenue vs Expenses (12 months)</h3>
            <span style="font-size:12px;color:#718096;">Real figures only</span>
        </div>
        <?php if ($hasRevenue || $hasExpenses): ?>
        <div style="height:260px;">
            <canvas id="revenueExpenseChart"></canvas>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:50px;color:#a0aec0;">
            <i class="fas fa-chart-area" style="font-size:40px;margin-bottom:12px;display:block;"></i>
            <p>No financial data yet. Record payments and expenses to see this chart.</p>
        </div>
        <?php endif; ?>
    </section>
</div>

<!-- ============================================
     PROFIT/LOSS TREND (FULL WIDTH)
     ============================================ -->
<section class="table-container" style="margin-bottom:20px;">
    <div class="table-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h3 style="font-size:15px;font-weight:700;">Monthly Profit / Loss</h3>
        <span style="font-size:12px;color:#718096;">Revenue minus expenses, 12-month view</span>
    </div>
    <?php if ($hasRevenue || $hasExpenses): ?>
    <div style="height:220px;">
        <canvas id="profitChart"></canvas>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:40px;color:#a0aec0;">
        <i class="fas fa-balance-scale" style="font-size:32px;margin-bottom:10px;display:block;"></i>
        <p>No data to display</p>
    </div>
    <?php endif; ?>
</section>

<!-- ============================================
     REVENUE BY SERVICE  +  REVENUE BY PAYMENT METHOD
     ============================================ -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
    <section class="table-container">
        <div class="table-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h3 style="font-size:15px;font-weight:700;">Revenue by Service</h3>
            <span style="font-size:12px;color:#718096;">Paid orders only</span>
        </div>
        <?php if (!empty($serviceData) && array_sum($serviceData) > 0): ?>
        <div style="height:250px;">
            <canvas id="serviceChart"></canvas>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:40px;color:#a0aec0;">
            <i class="fas fa-tshirt" style="font-size:32px;margin-bottom:10px;display:block;"></i>
            <p>No paid service data yet</p>
        </div>
        <?php endif; ?>
    </section>

    <section class="table-container">
        <div class="table-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h3 style="font-size:15px;font-weight:700;">Revenue by Payment Method</h3>
            <span style="font-size:12px;color:#718096;">Cash / M-Pesa / Card</span>
        </div>
        <?php if (!empty($methodData) && array_sum($methodData) > 0): ?>
        <div style="height:250px;">
            <canvas id="methodChart"></canvas>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:40px;color:#a0aec0;">
            <i class="fas fa-money-bill-wave" style="font-size:32px;margin-bottom:10px;display:block;"></i>
            <p>No payment data yet</p>
        </div>
        <?php endif; ?>
    </section>
</div>

<!-- ============================================
     TOP EXPENSE CATEGORIES
     ============================================ -->
<?php if (!empty($expenseCategories)): ?>
<section class="table-container" style="margin-bottom:20px;">
    <div class="table-header" style="margin-bottom:16px;">
        <h3 style="font-size:15px;font-weight:700;">Top Expense Categories</h3>
        <span style="font-size:12px;color:#718096;">Where money goes out</span>
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="background:#f5f5f5;">
                <th style="padding:10px;text-align:left;border-bottom:2px solid #ddd;">Category</th>
                <th style="padding:10px;text-align:right;border-bottom:2px solid #ddd;">Amount</th>
                <th style="padding:10px;text-align:right;border-bottom:2px solid #ddd;">Share</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($expenseCategories as $ec):
                $pct = $totalExpenses > 0 ? round(($ec['total'] / $totalExpenses) * 100, 1) : 0;
            ?>
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:10px;font-weight:600;"><?php echo htmlspecialchars($ec['category']); ?></td>
                <td style="padding:10px;text-align:right;font-weight:700;"><?php echo formatMoney($ec['total']); ?></td>
                <td style="padding:10px;text-align:right;">
                    <div style="display:flex;align-items:center;gap:8px;justify-content:flex-end;">
                        <div style="width:60px;height:6px;background:#e2e8f0;border-radius:3px;overflow:hidden;">
                            <div style="width:<?php echo min($pct, 100); ?>%;height:100%;background:#e53e3e;border-radius:3px;"></div>
                        </div>
                        <span style="font-size:12px;font-weight:600;color:#718096;min-width:36px;"><?php echo $pct; ?>%</span>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php endif; ?>

<!-- ============================================
     RECENT REVENUE TRANSACTIONS
     ============================================ -->
<section class="table-container" style="margin-bottom:20px;">
    <div class="table-header" style="margin-bottom:16px;">
        <h3 style="font-size:15px;font-weight:700;">Recent Revenue Transactions</h3>
        <span style="font-size:12px;color:#718096;">Last 15 payments received</span>
    </div>
    <?php if ($recentPayments && $recentPayments->num_rows > 0): ?>
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="background:#f5f5f5;">
                <th style="padding:10px;text-align:left;border-bottom:2px solid #ddd;">Date</th>
                <th style="padding:10px;text-align:left;border-bottom:2px solid #ddd;">Order</th>
                <th style="padding:10px;text-align:left;border-bottom:2px solid #ddd;">Customer</th>
                <th style="padding:10px;text-align:left;border-bottom:2px solid #ddd;">Method</th>
                <th style="padding:10px;text-align:right;border-bottom:2px solid #ddd;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($rp = $recentPayments->fetch_assoc()): ?>
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:10px;font-size:12px;color:#718096;"><?php echo date('M d, Y', strtotime($rp['created_at'])); ?></td>
                <td style="padding:10px;"><a href="order_view.php?id=<?php echo $rp['order_id']; ?>" style="color:#667eea;font-weight:600;">#<?php echo $rp['order_id']; ?></a></td>
                <td style="padding:10px;"><?php echo htmlspecialchars($rp['customer_name'] ?? '-'); ?></td>
                <td style="padding:10px;"><span style="font-size:11px;background:#667eea20;color:#667eea;padding:2px 8px;border-radius:10px;font-weight:600;"><?php echo ucfirst($rp['payment_method']); ?></span></td>
                <td style="padding:10px;text-align:right;font-weight:700;"><?php echo formatMoney($rp['amount']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div style="text-align:center;padding:30px;color:#a0aec0;">
        <p>No revenue transactions yet. Record payments in the Payments page.</p>
    </div>
    <?php endif; ?>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = '#718096';

const monthLabels = <?php echo json_encode($monthLabels); ?>;
const revenueData = <?php echo json_encode($revenueData); ?>;
const expenseData = <?php echo json_encode($expenseData); ?>;
const profitData = <?php echo json_encode($profitData); ?>;

<?php if (!empty($statusData) && array_sum($statusData) > 0): ?>
new Chart(document.getElementById('statusPieChart'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($statusLabels); ?>,
        datasets: [{
            data: <?php echo json_encode($statusData); ?>,
            backgroundColor: <?php echo json_encode($statusColors); ?>,
            borderWidth: 2,
            borderColor: '#ffffff',
            hoverOffset: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        cutout: '55%',
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1a202c',
                padding: 12,
                cornerRadius: 8,
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const pct = total > 0 ? ((context.raw / total) * 100).toFixed(1) : 0;
                        return ` ${context.label}: ${context.raw} orders (${pct}%)`;
                    }
                }
            }
        }
    }
});
<?php endif; ?>

<?php if ($hasRevenue || $hasExpenses): ?>
new Chart(document.getElementById('revenueExpenseChart'), {
    type: 'line',
    data: {
        labels: monthLabels,
        datasets: [
            {
                label: 'Revenue (Paid)',
                data: revenueData,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.08)',
                borderWidth: 2.5,
                fill: true,
                tension: 0.4,
                pointRadius: 3,
                pointBackgroundColor: '#667eea',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            },
            {
                label: 'Expenses',
                data: expenseData,
                borderColor: '#e53e3e',
                backgroundColor: 'rgba(229, 62, 62, 0.06)',
                borderWidth: 2.5,
                fill: true,
                tension: 0.4,
                pointRadius: 3,
                pointBackgroundColor: '#e53e3e',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { position: 'top', labels: { usePointStyle: true, padding: 16, font: { size: 11.5 } } },
            tooltip: {
                backgroundColor: '#1a202c', padding: 12, cornerRadius: 8,
                callbacks: { label: (ctx) => ` ${ctx.dataset.label}: Ksh ${ctx.raw.toLocaleString('en-KE', {minimumFractionDigits: 2})}` }
            }
        },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f0f2f5' }, ticks: { callback: (v) => v >= 1000 ? 'Ksh ' + (v/1000).toFixed(0) + 'k' : 'Ksh ' + v, font: { size: 11 } } },
            x: { grid: { display: false }, ticks: { font: { size: 10 } } }
        }
    }
});

new Chart(document.getElementById('profitChart'), {
    type: 'bar',
    data: {
        labels: monthLabels,
        datasets: [{
            label: 'Profit / Loss',
            data: profitData,
            backgroundColor: profitData.map(v => v >= 0 ? 'rgba(72, 187, 120, 0.75)' : 'rgba(229, 62, 62, 0.75)'),
            borderColor: profitData.map(v => v >= 0 ? '#48bb78' : '#e53e3e'),
            borderWidth: 1,
            borderRadius: 6,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1a202c', padding: 12, cornerRadius: 8,
                callbacks: { label: (ctx) => ` ${ctx.raw >= 0 ? 'Profit' : 'Loss'}: Ksh ${Math.abs(ctx.raw).toLocaleString('en-KE', {minimumFractionDigits: 2})}` }
            }
        },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f0f2f5' }, ticks: { callback: (v) => v >= 1000 ? 'Ksh ' + (v/1000).toFixed(0) + 'k' : 'Ksh ' + v, font: { size: 11 } } },
            x: { grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 45 } }
        }
    }
});
<?php endif; ?>

<?php if (!empty($serviceData) && array_sum($serviceData) > 0): ?>
new Chart(document.getElementById('serviceChart'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($serviceLabels); ?>,
        datasets: [{
            data: <?php echo json_encode($serviceData); ?>,
            backgroundColor: ['#667eea', '#764ba2', '#48bb78', '#ed8936', '#fc8181', '#63b3ed', '#9f7aea', '#38b2ac'],
            borderWidth: 2, borderColor: '#ffffff', hoverOffset: 6
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false, cutout: '55%',
        plugins: {
            legend: { position: 'bottom', labels: { usePointStyle: true, padding: 12, font: { size: 11 } } },
            tooltip: {
                backgroundColor: '#1a202c', padding: 10, cornerRadius: 6,
                callbacks: {
                    label: function(ctx) {
                        const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                        const pct = total > 0 ? ((ctx.raw / total) * 100).toFixed(1) : 0;
                        return ` ${ctx.label}: Ksh ${ctx.raw.toLocaleString()} (${pct}%)`;
                    }
                }
            }
        }
    }
});
<?php endif; ?>

<?php if (!empty($methodData) && array_sum($methodData) > 0): ?>
new Chart(document.getElementById('methodChart'), {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($methodLabels); ?>,
        datasets: [{
            data: <?php echo json_encode($methodData); ?>,
            backgroundColor: ['#48bb78', '#4299e1', '#ed8936', '#9f7aea', '#fc8181', '#667eea'],
            borderWidth: 2, borderColor: '#ffffff', hoverOffset: 6
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { usePointStyle: true, padding: 12, font: { size: 11 } } },
            tooltip: {
                backgroundColor: '#1a202c', padding: 10, cornerRadius: 6,
                callbacks: {
                    label: function(ctx) {
                        const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                        const pct = total > 0 ? ((ctx.raw / total) * 100).toFixed(1) : 0;
                        return ` ${ctx.label}: Ksh ${ctx.raw.toLocaleString()} (${pct}%)`;
                    }
                }
            }
        }
    }
});
<?php endif; ?>
</script>

<?php include 'footer.php'; ?>
<?php
// location_pin.php — Rider Customer Location & Address Viewer
// Shows delivery addresses for orders currently out for delivery.
// Scope: delivery-mode orders only. Shop self-pickup is handled by
// staff in ready_pickup.php, not by riders.

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'rider') {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

$pageTitle = 'Customer Location';
$activePage = 'location_pin';

$message = '';
$messageType = '';

// Get all active delivery orders with addresses
$deliveryOrders = [];
$res = $conn->query("SELECT * FROM orders WHERE delivery_mode = 'delivery' AND delivery_status = 'out_for_delivery' ORDER BY order_date DESC");
while ($row = $res->fetch_assoc()) $deliveryOrders[] = $row;

function formatMoney($amount) {
    return 'Ksh ' . number_format($amount, 2);
}
function formatDate($date) {
    return date('M d, Y H:i', strtotime($date));
}

include 'header.php';
?>

<style>
.location-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    transition: box-shadow 0.2s;
}
.location-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.location-card h4 {
    font-size: 15px;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 4px;
}
.location-card .meta {
    font-size: 12px;
    color: #718096;
    margin-bottom: 12px;
}
.address-box {
    background: #f7fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 14px;
    margin-bottom: 12px;
}
.address-box .label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #a0aec0;
    font-weight: 700;
    margin-bottom: 6px;
}
.address-box .address-text {
    font-size: 14px;
    color: #4a5568;
    line-height: 1.6;
    font-weight: 500;
}
.phone-row {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #4a5568;
    margin-bottom: 12px;
}
.phone-row i {
    color: #667eea;
}
.map-placeholder {
    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
    border-radius: 10px;
    height: 160px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #4c51bf;
    font-size: 13px;
    margin-bottom: 12px;
    position: relative;
    overflow: hidden;
}
.map-placeholder i {
    font-size: 36px;
    margin-bottom: 8px;
}
.map-placeholder .pin {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -100%);
    color: #e53e3e;
    font-size: 28px;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
}
.action-btns {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.action-btns a {
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.btn-map {
    background: #667eea;
    color: white;
}
.btn-call {
    background: #48bb78;
    color: white;
}
.btn-navigate {
    background: #ed8936;
    color: white;
}
.pickup-badge {
    display: inline-block;
    background: #f0fff4;
    color: #2f855a;
    padding: 4px 12px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 700;
    margin-bottom: 10px;
}
.delivery-badge {
    display: inline-block;
    background: #ebf4ff;
    color: #2b6cb0;
    padding: 4px 12px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 700;
    margin-bottom: 10px;
}
</style>

<div class="page-header">
    <h1>Customer Location</h1>
    <p>Delivery addresses and contact details for active orders.</p>
</div>

<?php if ($message): ?>
<div class="inline-message show <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<!-- Delivery Orders with Addresses -->
<div style="margin-bottom: 24px;">
    <div class="table-header" style="margin-bottom: 16px;">
        <h3><i class="fas fa-map-marker-alt" style="color:#e53e3e;"></i> Delivery Addresses</h3>
        <span style="font-size:12px;color:#718096;"><?php echo count($deliveryOrders); ?> orders</span>
    </div>

    <?php if (empty($deliveryOrders)): ?>
    <div class="table-container" style="text-align:center;padding:40px;color:#a0aec0;">
        <i class="fas fa-motorcycle" style="font-size:32px;margin-bottom:10px;display:block;"></i>
        <p>No delivery orders active.</p>
    </div>
    <?php else: ?>
        <?php foreach ($deliveryOrders as $o): 
            $dStatus = $o['delivery_status'] ?? 'pending';
            $address = $o['delivery_address'] ?: 'No address provided';
            $phone = $o['phone'] ?: '';
            // Create a simple map link (Google Maps search)
            $mapQuery = urlencode($address . ' Nairobi Kenya');
            $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . $mapQuery;
            $telUrl = 'tel:' . preg_replace('/[^0-9]/', '', $phone);
        ?>
        <div class="location-card">
            <span class="delivery-badge"><i class="fas fa-motorcycle"></i> Delivery</span>
            <h4>Order #<?php echo $o['id']; ?> — <?php echo htmlspecialchars($o['customer_name']); ?></h4>
            <div class="meta">
                <?php echo formatMoney($o['total_amount']); ?> 
                &middot; <?php echo $dStatus === 'out_for_delivery' ? '<span style="color:#ed8936;font-weight:600;">Out for Delivery</span>' : '<span style="color:#48bb78;font-weight:600;">Ready</span>'; ?>
                &middot; <?php echo formatDate($o['order_date']); ?>
            </div>

            <?php if ($phone): ?>
            <div class="phone-row">
                <i class="fas fa-phone"></i>
                <a href="<?php echo $telUrl; ?>" style="color:#667eea;font-weight:600;"><?php echo htmlspecialchars($phone); ?></a>
            </div>
            <?php endif; ?>

            <div class="address-box">
                <div class="label"><i class="fas fa-home"></i> Delivery Address</div>
                <div class="address-text"><?php echo nl2br(htmlspecialchars($address)); ?></div>
            </div>

            <div class="map-placeholder">
                <i class="fas fa-map-marked-alt"></i>
                <span>Map View</span>
                <div class="pin"><i class="fas fa-map-marker-alt"></i></div>
            </div>

            <div class="action-btns">
                <a href="<?php echo $mapsUrl; ?>" target="_blank" class="btn-map">
                    <i class="fas fa-external-link-alt"></i> Open in Maps
                </a>
                <?php if ($phone): ?>
                <a href="<?php echo $telUrl; ?>" class="btn-call">
                    <i class="fas fa-phone"></i> Call Customer
                </a>
                <?php endif; ?>
                <a href="rider_status.php" class="btn-navigate">
                    <i class="fas fa-check-circle"></i> Update Status
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
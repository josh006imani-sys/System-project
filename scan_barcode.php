<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

$pageTitle = 'Scan Barcode';
$activePage = 'scan_barcode';

$message = '';
$messageType = '';
$order = null;

// Turns a raw DB id (e.g. 6) into the display code ORD-1006.
// Identical to the version in barcode_scan.php / orders.php so a code
// always resolves to the same order no matter which page reads it.
function formatOrderId($id) {
    return 'ORD-' . (1000 + (int)$id);
}

// Reverses formatOrderId(): "ORD-1006" or a raw scanner number both
// resolve back to the real id used to query the database.
function parseOrderCode($code) {
    $code = trim($code);
    if (preg_match('/(\d+)/', $code, $m)) {
        $n = (int)$m[1];
        return $n > 1000 ? $n - 1000 : $n;
    }
    return null;
}

// Handle barcode scan input
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['barcode'])) {
    $barcode = trim($_POST['barcode']);
    $order_id = parseOrderCode($barcode);

    if ($order_id !== null && $order_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$order) {
            $message = 'Order not found. Please check the barcode or order ID.';
            $messageType = 'error';
        }
    } else {
        $message = 'Invalid barcode. Please scan again or enter a valid order ID.';
        $messageType = 'error';
    }
}

// Handle quick status update
if (isset($_POST['quick_update']) && isset($_POST['order_id']) && isset($_POST['new_status'])) {
    $oid = intval($_POST['order_id']);
    $new_status = $_POST['new_status'];
    $valid = ['received', 'washing', 'ready', 'collected'];
    if (in_array($new_status, $valid)) {
        $collected_at = ($new_status === 'collected') ? date('Y-m-d H:i:s') : null;
        if ($collected_at) {
            $stmt = $conn->prepare("UPDATE orders SET status = ?, collected_at = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssi", $new_status, $collected_at, $oid);
        } else {
            $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $new_status, $oid);
        }
        $stmt->execute();
        $stmt->close();
        
        // Refresh order
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->bind_param("i", $oid);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $message = 'Status updated to ' . ucfirst($new_status) . '!';
        $messageType = 'success';
    }
}

include 'header.php';
?>

<style>
.scanner-page { max-width: 700px; margin: 0 auto; }

.scanner-box { 
    border: 3px dashed #3b7cff; 
    border-radius: 20px; 
    padding: 50px 40px; 
    margin: 20px 0;
    background: linear-gradient(135deg, #f0f5ff 0%, #dbeafe 100%);
    text-align: center;
}

.scanner-box h2 { 
    color: #1e293b; 
    margin-bottom: 8px;
    font-size: 24px;
}

.scanner-box p { 
    color: #64748b; 
    margin-bottom: 24px;
    font-size: 14px;
}

.scan-input { 
    width: 100%; 
    max-width: 400px;
    padding: 18px 28px; 
    font-size: 20px; 
    border: 2px solid #3b7cff; 
    border-radius: 14px; 
    text-align: center;
    letter-spacing: 4px;
    font-family: 'Courier New', monospace;
    font-weight: 600;
    outline: none;
    transition: all 0.2s;
}

.scan-input:focus {
    box-shadow: 0 0 0 4px rgba(59, 124, 255, 0.2);
}

.scan-btn {
    margin-top: 16px;
    padding: 14px 48px;
    font-size: 16px;
    font-weight: 600;
}

/* Order result card */
.order-result { 
    background: white; 
    border-radius: 16px; 
    padding: 30px; 
    margin-top: 24px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #e2e8f0;
}

.order-result h2 { 
    font-size: 22px; 
    margin-bottom: 4px;
    color: #1e293b;
}

.order-result .meta {
    color: #64748b;
    font-size: 14px;
    margin-bottom: 20px;
}

.status-display {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 24px;
    border-radius: 20px;
    font-size: 15px;
    font-weight: 700;
    margin: 10px 0;
}

.status-display.received { background: #e2e8f0; color: #4a5568; }
.status-display.washing { background: #fffbeb; color: #b7791f; }
.status-display.ready { background: #f0fff4; color: #2f855a; }
.status-display.collected { background: #eff6ff; color: #2563eb; }

.status-buttons { 
    display: flex; 
    gap: 12px; 
    justify-content: center; 
    margin-top: 24px; 
    flex-wrap: wrap; 
}

.status-buttons .btn {
    padding: 14px 32px;
    font-size: 15px;
    font-weight: 600;
}

/* Barcode display on result */
.barcode-display {
    background: #f8fafc;
    border-radius: 10px;
    padding: 16px;
    margin: 16px 0;
}

.barcode-display canvas {
    display: block;
    margin: 0 auto;
}

.barcode-number {
    font-family: 'Courier New', monospace;
    font-size: 14px;
    font-weight: 700;
    color: #4a5568;
    letter-spacing: 4px;
    margin-top: 6px;
    text-align: center;
}

/* Keyboard shortcut hint */
.keyboard-hint {
    margin-top: 16px;
    font-size: 12px;
    color: #94a3b8;
}

.keyboard-hint kbd {
    background: #e2e8f0;
    padding: 2px 8px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 11px;
}

/* Recent scans list */
.recent-scans {
    margin-top: 30px;
}

.recent-scans h3 {
    font-size: 14px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
}

/* Camera scanner section */
.camera-section {
    display: none;
    margin-top: 16px;
    background: #fff;
    border-radius: 16px;
    padding: 16px;
    border: 2px solid #3b7cff;
    animation: fadeIn 0.25s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}

.camera-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.camera-header span {
    font-weight: 600;
    color: #1e293b;
    font-size: 14px;
}

.camera-reader-wrap {
    width: 100%;
    max-width: 420px;
    margin: 0 auto;
    border-radius: 12px;
    overflow: hidden;
    min-height: 220px;
    background: #000;
}

.camera-hint {
    font-size: 12px;
    color: #64748b;
    margin-top: 10px;
    text-align: center;
}

.camera-error {
    padding: 40px;
    text-align: center;
    color: #c53030;
    font-size: 13px;
}

.camera-error i {
    font-size: 24px;
    margin-bottom: 8px;
    display: block;
}

.divider-text {
    margin: 16px 0;
    color: #94a3b8;
    font-size: 13px;
}
</style>

<div class="page-header no-print">
    <h1>Scan Barcode</h1>
    <p>Quickly find and update orders by scanning their barcode.</p>
</div>

<div class="scanner-page">
    
    <!-- Scanner Input Box -->
    <div class="scanner-box">
        <h2><i class="fas fa-barcode"></i> Scan Order Barcode</h2>
        <p>Use your phone camera to point at the barcode in the tag  or type the order ID manually</p>
        
        <form method="POST" action="" id="scanForm">
            <input 
                type="text" 
                name="barcode" 
                id="barcodeInput"
                class="scan-input" 
                placeholder="ORD-1006" 
                autofocus 
                autocomplete="off"
                maxlength="20"
            >
            <br>
            <button type="submit" class="btn btn-primary scan-btn">
                <i class="fas fa-search"></i> Find Order
            </button>
        </form>
        
        <div class="divider-text">— OR —</div>
        
        <button type="button" onclick="toggleCamera()" class="btn btn-outline" style="font-size:15px; padding:12px 28px;">
            <i class="fas fa-camera"></i> Scan with Phone Camera
        </button>
        
        <div class="keyboard-hint">
            use the <strong>camera button</strong> on your phone, or type the order ID manually
        </div>
    </div>
    
    <!-- Camera Scanner Section -->
    <div id="cameraSection" class="camera-section">
        <div class="camera-header">
            <span><i class="fas fa-video"></i> Camera Scanner</span>
            <button type="button" onclick="stopCamera()" style="background:#ffebee; border:1px solid #ea4335; color:#ea4335; padding:6px 14px; border-radius:6px; cursor:pointer; font-weight:600; font-size:12px;">
                <i class="fas fa-times"></i> Close Camera
            </button>
        </div>
        <div class="camera-reader-wrap">
            <div id="cameraReader" style="width:100%; height:100%;"></div>
        </div>
        <p class="camera-hint">
            <i class="fas fa-info-circle"></i> Point your camera at the barcode. It will scan automatically.
            <br><small style="color:#94a3b8;">Camera requires HTTPS or localhost on most phones.</small>
        </p>
    </div>
    
    <?php if ($message): ?>
    <div class="inline-message show <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if ($order): 
        $display_id = formatOrderId($order['id']);
        $barcode_text = $display_id;
        
        $countStmt = $conn->prepare("SELECT COALESCE(SUM(qty), 0) as total FROM order_items WHERE order_id = ?");
        $countStmt->bind_param("i", $order['id']);
        $countStmt->execute();
        $item_count = $countStmt->get_result()->fetch_assoc()['total'] ?? 0;
        $countStmt->close();
        
        $status_cycle = ['received' => 'washing', 'washing' => 'ready', 'ready' => 'collected'];
        $next_status = $status_cycle[$order['status']] ?? null;
        
        $status_labels = [
            'received' => 'Received',
            'washing' => 'Washing',
            'ready' => 'Ready for Pickup',
            'collected' => 'Collected'
        ];
    ?>
    <div class="order-result">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:10px;">
            <div>
                <h2><?php echo htmlspecialchars($display_id); ?></h2>
                <div class="meta">
                    <?php echo htmlspecialchars($order['customer_name'] ?? 'Walk-in'); ?> 
                    &middot; <?php echo $item_count; ?> items 
                    &middot; <?php echo formatMoney($order['total_amount']); ?>
                </div>
            </div>
            <span class="status-display <?php echo $order['status']; ?>">
                <i class="fas fa-circle" style="font-size:8px;"></i>
                <?php echo $status_labels[$order['status']] ?? ucfirst($order['status']); ?>
            </span>
        </div>
        
        <div class="barcode-display">
            <canvas id="resultBarcode" width="240" height="60"></canvas>
            <div class="barcode-number"><?php echo $barcode_text; ?></div>
        </div>
        
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; font-size:13px; color:#4a5568; margin:12px 0;">
            <div><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone'] ?: '-'); ?></div>
            <div><strong>Payment:</strong> <?php echo $order['paid'] ? '<span style="color:#2f855a;">Paid</span>' : '<span style="color:#b7791f;">Pending</span>'; ?></div>
            <div><strong>Date:</strong> <?php echo date('M d, Y', strtotime($order['order_date'])); ?></div>
            <div><strong>Notes:</strong> <?php echo htmlspecialchars($order['notes'] ?: '-'); ?></div>
        </div>
        
        <?php if ($next_status): ?>
        <div class="status-buttons">
            <form method="POST" action="" style="display:inline;">
                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                <input type="hidden" name="new_status" value="<?php echo $next_status; ?>">
                <button type="submit" name="quick_update" class="btn btn-success">
                    <i class="fas fa-arrow-right"></i> Mark as <?php echo ucfirst($next_status); ?>
                </button>
            </form>
            <a href="order_view.php?id=<?php echo $order['id']; ?>" class="btn btn-outline">
                <i class="fas fa-eye"></i> Full Details
            </a>
            <a href="order_form.php?edit=<?php echo $order['id']; ?>" class="btn btn-outline">
                <i class="fas fa-edit"></i> Edit
            </a>
        </div>
        <?php else: ?>
        <div style="text-align:center; margin-top:20px; padding:16px; background:#f0fff4; border-radius:10px; color:#2f855a;">
            <i class="fas fa-check-circle" style="font-size:24px; margin-bottom:8px;"></i>
            <p style="font-weight:600; margin:0;">Order Complete!</p>
            <p style="font-size:12px; margin:4px 0 0 0;">This order has been collected.</p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
</div>

<!-- html5-qrcode camera library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<!-- Barcode Generator -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
document.getElementById('barcodeInput').focus();

document.getElementById('barcodeInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        if (this.value.trim()) {
            document.getElementById('scanForm').submit();
        }
    }
});

setInterval(function() {
    const cameraOpen = document.getElementById('cameraSection').style.display === 'block';
    if (!cameraOpen && !document.querySelector('.order-result:hover')) {
        document.getElementById('barcodeInput').focus();
    }
}, 3000);

let html5QrCode = null;

function toggleCamera() {
    const section = document.getElementById('cameraSection');
    if (section.style.display === 'none' || section.style.display === '') {
        section.style.display = 'block';
        startCamera();
    } else {
        stopCamera();
    }
}

function startCamera() {
    const readerDiv = document.getElementById('cameraReader');
    readerDiv.innerHTML = '';

    html5QrCode = new Html5Qrcode("cameraReader");

    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 320, height: 120 } },
        onScanSuccess,
        onScanFailure
    ).catch(function(err) {
        readerDiv.innerHTML = 
            '<div class="camera-error">' +
            '<i class="fas fa-exclamation-circle"></i>' +
            'Could not start camera.<br>' +
            '<small style="color:#64748b;">' + (err.message || err) + '<br>Make sure you are on HTTPS or localhost and have granted camera permission.</small>' +
            '</div>';
    });
}

function onScanSuccess(decodedText, decodedResult) {
    stopCamera();
    document.getElementById('barcodeInput').value = decodedText;
    document.getElementById('scanForm').submit();
}

function onScanFailure(errorMessage) {}

function stopCamera() {
    const section = document.getElementById('cameraSection');
    if (html5QrCode) {
        html5QrCode.stop().then(function() {
            html5QrCode.clear();
            html5QrCode = null;
            section.style.display = 'none';
        }).catch(function() {
            html5QrCode = null;
            section.style.display = 'none';
        });
    } else {
        section.style.display = 'none';
    }
}

<?php if ($order): ?>
JsBarcode("#resultBarcode", "<?php echo $barcode_text; ?>", {
    format: "CODE128",
    lineColor: "#1e293b",
    width: 2,
    height: 45,
    displayValue: false,
    margin: 0
});
<?php endif; ?>
</script>

<?php 
function formatMoney($amount) {
    return 'Ksh ' . number_format($amount, 2);
}
include 'footer.php'; 
?>
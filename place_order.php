<?php
// place_order.php — Customer Self-Service Order Placement
// Customers can select services and place orders without staff help

session_start();
require_once 'db_config.php';

// Check if customer is logged in (either regular or Google)
if (!isset($_SESSION['customer_id'])) {
    header('Location: customer_login.php');
    exit;
}

$customerId = $_SESSION['customer_id'];

// Get customer details
$res = $conn->query("SELECT * FROM customer WHERE id = $customerId");
$customer = $res->fetch_assoc();
if (!$customer) {
    unset($_SESSION['customer_id'], $_SESSION['customer_name']);
    header('Location: customer_login.php');
    exit;
}

// Fetch available services
$services = [];
$sres = $conn->query("SELECT id, name, unit, price FROM services ORDER BY name");
while ($s = $sres->fetch_assoc()) {
    $services[] = $s;
}

$error = '';
$success = '';

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $deliveryMode = $conn->real_escape_string($_POST['delivery_mode'] ?? 'pickup');
    $deliveryAddressRaw = trim($_POST['delivery_address'] ?? '');
    $deliveryAddress = $conn->real_escape_string($deliveryAddressRaw);
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');
    
    // Calculate total from items
    $total = 0;
    $validItems = [];
    $itemNames = $_POST['item_name'] ?? [];
    $itemQtys = $_POST['item_qty'] ?? [];
    $itemPrices = $_POST['item_price'] ?? [];
    
    for ($i = 0; $i < count($itemNames); $i++) {
        $name = trim($itemNames[$i] ?? '');
        $qty = (int)($itemQtys[$i] ?? 0);
        $price = (float)($itemPrices[$i] ?? 0);
        if (!empty($name) && $qty > 0) {
            $total += $qty * $price;
            $validItems[] = ['name' => $name, 'qty' => $qty, 'price' => $price];
        }
    }
    
    if (empty($validItems)) {
        $error = 'Please add at least one service.';
    } elseif ($deliveryMode === 'delivery' && trim($deliveryAddressRaw) === '') {
        // JS marks this required, but that's client-side only — a customer
        // with JS disabled (or a bypassed form) could otherwise submit a
        // delivery order with no address at all. Enforce it here too.
        $error = 'Please enter your address for home pickup & delivery.';
    } else {
        $customerName = $conn->real_escape_string($customer['full_name']);
        $phone = $conn->real_escape_string($customer['phone']);
        $paid = 0; // Default to unpaid for manual confirmation flow
        
        // Insert order
        $conn->query("INSERT INTO orders 
            (customer_id, customer_name, phone, status, notes, total_amount, paid, created_by, delivery_mode, delivery_address, order_date) 
            VALUES ($customerId, '$customerName', '$phone', 'received', '$notes', $total, $paid, 0, '$deliveryMode', '$deliveryAddress', NOW())");
        $orderId = $conn->insert_id;
        
        // Insert order items
        foreach ($validItems as $it) {
            $name = $conn->real_escape_string($it['name']);
            $conn->query("INSERT INTO order_items (order_id, name, qty, price) 
                          VALUES ($orderId, '$name', {$it['qty']}, {$it['price']})");
        }
        
        header('Location: order_confirmation.php?id=' . $orderId);
        exit;
    }
}

function formatMoney($amount) {
    return 'Ksh ' . number_format($amount, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order - Muthoni's Laundry</title>
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
        
        .container { max-width: 700px; margin: 0 auto; padding: 20px; }
        
        .card {
            background: white; padding: 25px; border-radius: 10px;
            margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .card h2 { font-size: 18px; margin-bottom: 15px; color: #667eea; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label {
            display: block; font-size: 13px; font-weight: 600;
            color: #555; margin-bottom: 6px;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 10px; border: 1px solid #ccc;
            border-radius: 6px; font-size: 14px; font-family: Arial, sans-serif;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #667eea; outline: none;
        }
        
        .item-row {
            display: grid;
            grid-template-columns: 2fr 80px 100px 40px;
            gap: 8px; margin-bottom: 10px; align-items: center;
        }
        .item-row select, .item-row input {
            padding: 8px; border: 1px solid #ccc; border-radius: 5px;
            font-size: 13px;
        }
        .remove-btn {
            background: #ffebee; border: 1px solid #ea4335;
            color: #ea4335; border-radius: 5px; cursor: pointer;
            padding: 8px; font-size: 12px;
        }
        
        .add-item-btn {
            width: 100%; padding: 10px; background: #f0f2ff;
            border: 2px dashed #667eea; color: #667eea;
            border-radius: 8px; font-weight: bold; cursor: pointer;
            margin-top: 5px; font-size: 14px;
        }
        .add-item-btn:hover { background: #e8ebff; }
        
        .total-box {
            background: #f7fafc; padding: 15px; border-radius: 8px;
            display: flex; justify-content: space-between; align-items: center;
            margin: 15px 0; border: 1px solid #e2e8f0;
        }
        .total-box span:first-child { font-weight: 600; font-size: 14px; }
        .total-box span:last-child { font-size: 22px; font-weight: 800; color: #667eea; }
        
        .submit-btn {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white; border: none; border-radius: 10px;
            font-size: 16px; font-weight: bold; cursor: pointer;
        }
        .submit-btn:hover { opacity: 0.9; }
        
        .error {
            background: #fff5f5; color: #c53030;
            padding: 12px; border-radius: 8px; margin-bottom: 15px;
            border: 1px solid #fed7d7; font-size: 13px;
        }
        
        .radio-group {
            display: flex; gap: 15px; flex-wrap: wrap;
        }
        .radio-option {
            flex: 1; min-width: 120px;
            padding: 12px; border: 2px solid #e2e8f0;
            border-radius: 8px; cursor: pointer;
            text-align: center; transition: all 0.2s;
        }
        .radio-option:hover { border-color: #667eea; }
        .radio-option input { display: none; }
        .radio-option.selected {
            border-color: #667eea; background: #f0f2ff;
        }
        .radio-option span { font-size: 13px; font-weight: 600; }
        
        .help-text {
            font-size: 12px; color: #888; margin: -8px 0 12px;
        }

        @media (max-width: 600px) {
            .item-row { grid-template-columns: 1fr 60px 80px 35px; }
            .navbar { padding: 15px; }
            .container { padding: 15px; }
        }

        .modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(26,32,44,0.55);
            z-index: 1000;
            align-items: center; justify-content: center;
            padding: 20px;
        }
        .modal-overlay.show { display: flex; }
        .modal-box {
            background: white; border-radius: 14px;
            max-width: 480px; width: 100%;
            max-height: 85vh; overflow-y: auto;
            padding: 26px;
        }
        .modal-box h3 { font-size: 18px; margin-bottom: 4px; color: #1a202c; }
        .modal-box .modal-sub { font-size: 13px; color: #718096; margin-bottom: 18px; }
        .review-section { margin-bottom: 16px; }
        .review-section .rlabel {
            font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;
            color: #a0aec0; font-weight: 700; margin-bottom: 6px;
        }
        .review-item {
            display: flex; justify-content: space-between;
            font-size: 13.5px; padding: 6px 0; border-bottom: 1px solid #f1f1f1;
        }
        .review-total {
            display: flex; justify-content: space-between;
            font-size: 17px; font-weight: 800; color: #667eea;
            padding-top: 10px; margin-top: 6px; border-top: 2px solid #e2e8f0;
        }
        .review-address { font-size: 13.5px; color: #4a5568; line-height: 1.5; }
        .modal-actions { display: flex; gap: 10px; margin-top: 22px; }
        .modal-actions button {
            flex: 1; padding: 12px; border-radius: 8px; font-size: 14px;
            font-weight: 700; cursor: pointer; border: none;
        }
        .btn-edit { background: #edf2f7; color: #4a5568; }
        .btn-confirm { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
    </style>
</head>
<body>

    <div class="navbar">
        <h1><i class="fas fa-tshirt"></i> Muthoni's Laundry</h1>
        <div>
            <a href="customer_dashboard.php"><i class="fas fa-arrow-left"></i> My Orders</a>
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <h2><i class="fas fa-shopping-basket"></i> Place New Order</h2>
            <p style="color:#718096;font-size:13px;margin-bottom:15px;">
                Welcome, <strong><?php echo htmlspecialchars($customer['full_name']); ?></strong> 
                (<?php echo htmlspecialchars($customer['phone']); ?>)
            </p>
            
            <?php if ($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="orderForm">
                <input type="hidden" name="place_order" value="1">
                
                <div class="form-group">
                    <label>Select Services *</label>
                    <div id="itemsList">
                        <div class="item-row">
                            <select name="item_name[]" class="service-select" onchange="autofillPrice(this)">
                                <option value="">-- Choose Service --</option>
                                <?php foreach ($services as $s): ?>
                                <option value="<?php echo htmlspecialchars($s['name']); ?>" data-price="<?php echo $s['price']; ?>">
                                    <?php echo htmlspecialchars($s['name']); ?> — Ksh <?php echo number_format($s['price'], 0); ?>/<?php echo htmlspecialchars($s['unit']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" name="item_qty[]" value="1" min="1" placeholder="Qty" onchange="calculateTotal()" required>
                            <input type="number" name="item_price[]" value="0" min="0" step="0.01" placeholder="Price" onchange="calculateTotal()" readonly style="background:#f5f5f5;">
                            <button type="button" class="remove-btn" onclick="removeItem(this)" title="Remove">×</button>
                        </div>
                    </div>
                    <button type="button" class="add-item-btn" onclick="addItem()">+ Add Another Service</button>
                </div>
                
                <div class="form-group">
                    <label>How do you want your laundry handled?</label>
                    <div class="radio-group">
                        <label class="radio-option selected" onclick="selectRadio(this, 'pickup')">
                            <input type="radio" name="delivery_mode" value="pickup" checked onchange="toggleAddress()">
                            <span>I'll drop off &amp; collect</span>
                        </label>
                        <label class="radio-option" onclick="selectRadio(this, 'delivery')">
                            <input type="radio" name="delivery_mode" value="delivery" onchange="toggleAddress()">
                            <span>Pick up &amp; deliver to my home</span>
                        </label>
                    </div>
                    <p class="help-text" id="modeHelp">You'll bring your laundry to us and collect it once it's ready.</p>
                </div>
                
                <div class="form-group" id="addressField" style="display:none;">
                    <label>Your Address *</label>
                    <p class="help-text" style="margin-top:-2px;">We'll come to this address to collect your laundry, and bring it back here once it's clean.</p>
                    <textarea name="delivery_address" id="addressText" rows="2" placeholder="e.g. Kamulu, next to Co-op Bank, house B4"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Payment</label>
                    <div class="radio-group">
                        <label class="radio-option selected">
                            <span>Pay on Pickup (Cash/M-Pesa)</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Special Instructions (optional)</label>
                    <textarea name="notes" rows="2" placeholder="Any special requests..."></textarea>
                </div>
                
                <div class="total-box">
                    <span>Grand Total</span>
                    <span id="grandTotal">Ksh 0.00</span>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> Place Order
                </button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="reviewModal">
        <div class="modal-box">
            <h3><i class="fas fa-clipboard-check"></i> Review Your Order</h3>
            <p class="modal-sub">Please check everything is correct — once placed, this order goes straight to our staff and can't be edited yourself.</p>

            <div class="review-section">
                <div class="rlabel">Items</div>
                <div id="reviewItems"></div>
            </div>

            <div class="review-section">
                <div class="rlabel">Handling</div>
                <div class="review-address" id="reviewMode"></div>
            </div>

            <div class="review-total">
                <span>Grand Total</span>
                <span id="reviewTotal">Ksh 0.00</span>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-edit" id="reviewEditBtn"><i class="fas fa-pen"></i> Edit</button>
                <button type="button" class="btn-confirm" id="reviewConfirmBtn"><i class="fas fa-check"></i> Confirm &amp; Place Order</button>
            </div>
        </div>
    </div>

<script>
const servicePrices = {};
<?php foreach ($services as $s): ?>
servicePrices[<?php echo json_encode($s['name']); ?>] = <?php echo $s['price']; ?>;
<?php endforeach; ?>

function autofillPrice(select) {
    const name = select.value;
    const row = select.closest('.item-row');
    if (name && servicePrices[name] !== undefined) {
        row.querySelector('input[name="item_price[]"]').value = servicePrices[name];
    }
    calculateTotal();
}

function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('input[name="item_qty[]"]').value) || 0;
        const price = parseFloat(row.querySelector('input[name="item_price[]"]').value) || 0;
        total += qty * price;
    });
    document.getElementById('grandTotal').textContent = 'Ksh ' + total.toFixed(2);
}

function addItem() {
    const container = document.getElementById('itemsList');
    const div = document.createElement('div');
    div.className = 'item-row';
    div.innerHTML = `
        <select name="item_name[]" class="service-select" onchange="autofillPrice(this)">
            <option value="">-- Choose Service --</option>
            <?php foreach ($services as $s): ?>
            <option value="<?php echo htmlspecialchars($s['name']); ?>" data-price="<?php echo $s['price']; ?>">
                <?php echo htmlspecialchars($s['name']); ?> — Ksh <?php echo number_format($s['price'], 0); ?>/<?php echo htmlspecialchars($s['unit']); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="item_qty[]" value="1" min="1" placeholder="Qty" onchange="calculateTotal()" required>
        <input type="number" name="item_price[]" value="0" min="0" step="0.01" placeholder="Price" onchange="calculateTotal()" readonly style="background:#f5f5f5;">
        <button type="button" class="remove-btn" onclick="removeItem(this)" title="Remove">×</button>
    `;
    container.appendChild(div);
}

function removeItem(btn) {
    const rows = document.querySelectorAll('.item-row');
    if (rows.length > 1) {
        btn.closest('.item-row').remove();
        calculateTotal();
    } else {
        alert('You need at least one service.');
    }
}

function selectRadio(label, value) {
    const group = label.closest('.radio-group');
    group.querySelectorAll('.radio-option').forEach(opt => opt.classList.remove('selected'));
    label.classList.add('selected');
    label.querySelector('input').checked = true;
    // Setting .checked directly does NOT fire a native 'change' event, so
    // the input's onchange="toggleAddress()" never ran — the address field
    // just silently stayed hidden. Call it directly instead of relying on
    // an event that browsers won't dispatch for a JS-driven check.
    if (value === 'delivery' || value === 'pickup') {
        toggleAddress();
    }
}

function toggleAddress() {
    const isDelivery = document.querySelector('input[name="delivery_mode"]:checked').value === 'delivery';
    document.getElementById('addressField').style.display = isDelivery ? 'block' : 'none';
    document.getElementById('addressText').required = isDelivery;

    document.getElementById('modeHelp').textContent = isDelivery
        ? "We'll come collect your laundry from you, and drop it back off once it's clean."
        : "You'll bring your laundry to us and collect it once it's ready.";
}

let orderConfirmed = false;

const orderFormEl = document.getElementById('orderForm');
if (orderFormEl) {
    orderFormEl.addEventListener('submit', function(e) {
        if (orderConfirmed) return; // user already reviewed & confirmed — let it through
        e.preventDefault();

        let hasValid = false;
        document.querySelectorAll('.item-row').forEach(row => {
            const name = row.querySelector('select[name="item_name[]"]').value;
            const qty = parseInt(row.querySelector('input[name="item_qty[]"]').value) || 0;
            if (name && qty > 0) hasValid = true;
        });
        if (!hasValid) {
            alert('Please select at least one service.');
            return;
        }

        const isDelivery = document.querySelector('input[name="delivery_mode"]:checked').value === 'delivery';
        const address = document.getElementById('addressText').value.trim();
        if (isDelivery && !address) {
            alert('Please enter your address for home pickup & delivery.');
            return;
        }

        openReviewModal(isDelivery, address);
    });
} else {
    console.error('place_order.php: #orderForm not found — order form submission is broken.');
}

function openReviewModal(isDelivery, address) {
    const itemsWrap = document.getElementById('reviewItems');
    itemsWrap.innerHTML = '';
    let total = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const name = row.querySelector('select[name="item_name[]"]').value;
        const qty = parseFloat(row.querySelector('input[name="item_qty[]"]').value) || 0;
        const price = parseFloat(row.querySelector('input[name="item_price[]"]').value) || 0;
        if (!name || qty <= 0) return;
        total += qty * price;
        const line = document.createElement('div');
        line.className = 'review-item';
        line.innerHTML = `<span>${name} × ${qty}</span><span>Ksh ${(qty * price).toFixed(2)}</span>`;
        itemsWrap.appendChild(line);
    });

    const modeDiv = document.getElementById('reviewMode');
    if (isDelivery) {
        modeDiv.innerHTML = 'Home pickup &amp; delivery<br><span style="color:#718096;">' +
            address.replace(/</g, '&lt;') + '</span>';
    } else {
        modeDiv.innerHTML = 'I\'ll drop off &amp; collect in person';
    }

    document.getElementById('reviewTotal').textContent = 'Ksh ' + total.toFixed(2);
    document.getElementById('reviewModal').classList.add('show');
}

function closeReviewModal() {
    document.getElementById('reviewModal').classList.remove('show');
}

function confirmOrder() {
    orderConfirmed = true;
    closeReviewModal();
    document.getElementById('orderForm').submit();
}

const reviewEditBtn = document.getElementById('reviewEditBtn');
const reviewConfirmBtn = document.getElementById('reviewConfirmBtn');
if (reviewEditBtn) reviewEditBtn.addEventListener('click', closeReviewModal);
if (reviewConfirmBtn) reviewConfirmBtn.addEventListener('click', confirmOrder);

// Close on backdrop click too, so a mis-tap doesn't trap someone in the modal
document.getElementById('reviewModal').addEventListener('click', function(e) {
    if (e.target === this) closeReviewModal();
});

calculateTotal();
toggleAddress();
</script>

</body>
</html>
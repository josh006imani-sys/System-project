<?php
// order_form.php — Create or Edit Order
// Staff create new orders here. Pick customer, add items, auto-calculate total.

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

$pageTitle = 'New Order';
$activePage = 'orders';

$editMode = false;
$order = ['customer_name'=>'','phone'=>'','status'=>'received','notes'=>'','total_amount'=>0, 'paid'=>0, 'delivery_mode'=>'pickup', 'delivery_address'=>''];
$items = [['name'=>'','qty'=>1,'price'=>0]];

// Fetch services for dropdown
$services = [];
$res = $conn->query("SELECT id, name, unit, price FROM services ORDER BY name");
while ($s = $res->fetch_assoc()) $services[] = $s;

// Fetch customers for dropdown
$customers = [];
$cres = $conn->query("SELECT id, full_name, phone FROM customer ORDER BY full_name");
while ($c = $cres->fetch_assoc()) $customers[] = $c;

// Edit mode — load existing order
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM orders WHERE id = $id");
    if ($res->num_rows) {
        $order = $res->fetch_assoc();
        $editMode = true;
        $pageTitle = 'Edit Order #' . $id;
        $ires = $conn->query("SELECT name, qty, price FROM order_items WHERE order_id = $id");
        $items = [];
        while ($it = $ires->fetch_assoc()) $items[] = $it;
        if (empty($items)) $items = [['name'=>'','qty'=>1,'price'=>0]];
    }
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer = $conn->real_escape_string($_POST['customer_name'] ?? '');
    $phone = $conn->real_escape_string($_POST['phone'] ?? '');
    $status = $conn->real_escape_string($_POST['status'] ?? 'received');
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');
    $paymentMethod = $conn->real_escape_string($_POST['payment_method'] ?? 'unpaid');
    $paid = ($paymentMethod !== 'unpaid') ? 1 : 0;
    $deliveryMode = $conn->real_escape_string($_POST['delivery_mode'] ?? 'pickup');
    $deliveryAddress = $conn->real_escape_string($_POST['delivery_address'] ?? '');

    // Find or create customer
    $customerId = null;
    if (!empty($phone)) {
        $custRes = $conn->query("SELECT id FROM customer WHERE phone = '$phone' LIMIT 1");
        if ($custRes && $custRes->num_rows > 0) {
            $customerId = $custRes->fetch_assoc()['id'];
            if (!empty($customer)) {
                $conn->query("UPDATE customer SET full_name = '$customer' WHERE id = $customerId");
            }
        } else if (!empty($customer)) {
            $conn->query("INSERT INTO customer (full_name, phone) VALUES ('$customer', '$phone')");
            $customerId = $conn->insert_id;
        }
    }

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
        $error = 'Please add at least one item.';
    } else {
        if ($editMode) {
            $id = (int)$_GET['edit'];
            // Also updated the UPDATE statement to store the new payment_method value
            $conn->query("UPDATE orders SET customer_id=" . ($customerId ?: 'NULL') . ", customer_name='$customer', phone='$phone', status='$status', notes='$notes', total_amount=$total, paid=$paid, payment_method='$paymentMethod', delivery_mode='$deliveryMode', delivery_address='$deliveryAddress' WHERE id=$id");
            $conn->query("DELETE FROM order_items WHERE order_id=$id");
            $orderId = $id;
        } else {
            // Updated INSERT statement with payment_method and '$paymentMethod'
            $conn->query("INSERT INTO orders (customer_id, customer_name, phone, status, notes, total_amount, paid, payment_method, created_by, delivery_mode, delivery_address) VALUES (" . ($customerId ?: 'NULL') . ", '$customer', '$phone', '$status', '$notes', $total, $paid, '$paymentMethod', {$_SESSION['user_id']}, '$deliveryMode', '$deliveryAddress')");
            $orderId = $conn->insert_id;
        }

        foreach ($validItems as $it) {
            $name = $conn->real_escape_string($it['name']);
            $conn->query("INSERT INTO order_items (order_id, name, qty, price) VALUES ($orderId, '$name', {$it['qty']}, {$it['price']})");
        }

        header('Location: orders.php');
        exit;
    }
}

include 'header.php';
?>

<div style="margin-bottom: 20px;">
    <h1 style="font-size: 24px; margin-bottom: 5px;"><?php echo $editMode ? 'Edit Order #' . (int)$_GET['edit'] : 'New Order'; ?></h1>
    <p style="color: #666; font-size: 14px;"><?php echo $editMode ? 'Update order details' : 'Create a new laundry order'; ?></p>
</div>

<?php if (isset($error)): ?>
<div style="padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 13px; background: #ffebee; color: #c62828;">
    <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd; max-width: 700px;">
    <form method="POST" id="orderForm">
        
        <!-- Customer -->
        <div style="margin-bottom: 15px;">
            <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px; font-weight: 600;">Select Customer (optional)</label>
            <select id="customerSelect" onchange="fillCustomer(this)" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 10px;">
                <option value="">-- New Customer --</option>
                <?php foreach ($customers as $c): ?>
                <option value="<?php echo $c['id']; ?>" data-name="<?php echo htmlspecialchars($c['full_name']); ?>" data-phone="<?php echo htmlspecialchars($c['phone']); ?>" <?php echo ($editMode && $order['customer_id'] == $c['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($c['full_name'] . ' - ' . $c['phone']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px; font-weight: 600;">Customer Name *</label>
                <input type="text" name="customer_name" id="customerName" value="<?php echo htmlspecialchars($order['customer_name']); ?>" required 
                       style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px; font-weight: 600;">Phone *</label>
                <input type="tel" name="phone" id="customerPhone" value="<?php echo htmlspecialchars($order['phone']); ?>" required 
                       style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 15px;">
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px; font-weight: 600;">Status</label>
                <select name="status" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="received" <?php echo $order['status']=='received'?'selected':''; ?>>Received</option>
                    <option value="washing" <?php echo $order['status']=='washing'?'selected':''; ?>>Washing</option>
                    <option value="ready" <?php echo $order['status']=='ready'?'selected':''; ?>>Ready</option>
                    <option value="collected" <?php echo $order['status']=='collected'?'selected':''; ?>>Collected</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px; font-weight: 600;">Collection</label>
                <select name="delivery_mode" id="deliveryMode" onchange="toggleAddress(this)" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="pickup" <?php echo ($order['delivery_mode'] ?? 'pickup')=='pickup'?'selected':''; ?>>Pickup</option>
                    <option value="delivery" <?php echo ($order['delivery_mode'] ?? '')=='delivery'?'selected':''; ?>>Delivery</option>
                </select>
            </div>
          <!-- Payment method selection - Cash or M-Pesa only -->
<div>
    <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px; font-weight: 600;">Payment Method</label>
    <select name="payment_method" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        <option value="unpaid" <?php echo empty($order['paid']) ? 'selected' : ''; ?>>Unpaid (Pay on Pickup)</option>
        <option value="cash" <?php echo ($order['payment_method'] ?? '') === 'cash' ? 'selected' : ''; ?>>Cash</option>
        <option value="mpesa" <?php echo ($order['payment_method'] ?? '') === 'mpesa' ? 'selected' : ''; ?>>M-Pesa</option>
    </select>
</div>
        </div>

        <!-- Delivery Address -->
        <div id="addressField" style="display: <?php echo ($order['delivery_mode'] ?? 'pickup')=='delivery'?'block':'none'; ?>; margin-bottom: 15px;">
            <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px; font-weight: 600;">Delivery Address *</label>
            <textarea name="delivery_address" rows="2" placeholder="Enter full address" 
                      style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"><?php echo htmlspecialchars($order['delivery_address'] ?? ''); ?></textarea>
        </div>

        <!-- Items -->
        <div style="margin-bottom: 15px;">
            <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px; font-weight: 600;">Order Items *</label>
            <div id="itemsList">
                <?php foreach ($items as $idx => $it): ?>
                <div class="item-row" style="display: grid; grid-template-columns: 2fr 70px 100px 40px; gap: 8px; margin-bottom: 8px;">
                    <select name="item_name[]" class="service-select" onchange="autofillPrice(this)" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        <option value="">-- Select Service --</option>
                        <?php foreach ($services as $s): ?>
                        <option value="<?php echo htmlspecialchars($s['name']); ?>" data-price="<?php echo $s['price']; ?>" <?php echo ($it['name'] === $s['name']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['name'] . ' (Ksh ' . number_format($s['price'], 2) . '/' . $s['unit'] . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="item_qty[]" value="<?php echo $it['qty']; ?>" min="1" placeholder="Qty" onchange="calculateTotal()" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <input type="number" name="item_price[]" value="<?php echo $it['price']; ?>" min="0" step="0.01" placeholder="Price" onchange="calculateTotal()" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <button type="button" onclick="removeItem(this)" style="background: #ffebee; border: 1px solid #ea4335; color: #ea4335; border-radius: 4px; cursor: pointer;">X</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" onclick="addItem()" style="width: 100%; padding: 8px; background: #f5f5f5; border: 1px dashed #ccc; color: #667eea; border-radius: 4px; font-weight: bold; cursor: pointer; margin-top: 5px;">+ Add Item</button>
        </div>

        <!-- Total -->
        <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-weight: bold;">Grand Total:</span>
            <span id="grandTotal" style="font-size: 20px; font-weight: bold; color: #667eea;">Ksh 0.00</span>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px; font-weight: 600;">Notes (optional)</label>
            <textarea name="notes" rows="2" placeholder="Special instructions..." 
                      style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"><?php echo htmlspecialchars($order['notes']); ?></textarea>
        </div>

        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <a href="orders.php" style="padding: 10px 20px; background: #f5f5f5; color: #555; text-decoration: none; border-radius: 4px; font-weight: bold;">Cancel</a>
            <button type="submit" style="padding: 10px 25px; background: #667eea; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;"><?php echo $editMode ? 'Save Changes' : 'Create Order'; ?></button>
        </div>
    </form>
</div>

<script>
const servicePrices = {};
<?php foreach ($services as $s): ?>
servicePrices[<?php echo json_encode($s['name']); ?>] = <?php echo $s['price']; ?>;
<?php endforeach; ?>

function fillCustomer(select) {
    const option = select.options[select.selectedIndex];
    if (option.value) {
        document.getElementById('customerName').value = option.getAttribute('data-name') || '';
        document.getElementById('customerPhone').value = option.getAttribute('data-phone') || '';
    } else {
        document.getElementById('customerName').value = '';
        document.getElementById('customerPhone').value = '';
    }
}

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
    div.style.cssText = 'display: grid; grid-template-columns: 2fr 70px 100px 40px; gap: 8px; margin-bottom: 8px;';
    div.innerHTML = `
        <select name="item_name[]" class="service-select" onchange="autofillPrice(this)" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            <option value="">-- Select Service --</option>
            <?php foreach ($services as $s): ?>
            <option value="<?php echo htmlspecialchars($s['name']); ?>" data-price="<?php echo $s['price']; ?>"><?php echo htmlspecialchars($s['name'] . ' (Ksh ' . number_format($s['price'], 2) . '/' . $s['unit'] . ')'); ?></option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="item_qty[]" value="1" min="1" placeholder="Qty" onchange="calculateTotal()" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        <input type="number" name="item_price[]" value="0" min="0" step="0.01" placeholder="Price" onchange="calculateTotal()" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        <button type="button" onclick="removeItem(this)" style="background: #ffebee; border: 1px solid #ea4335; color: #ea4335; border-radius: 4px; cursor: pointer;">X</button>
    `;
    container.appendChild(div);
}

function removeItem(btn) {
    const rows = document.querySelectorAll('.item-row');
    if (rows.length > 1) {
        btn.closest('.item-row').remove();
        calculateTotal();
    } else {
        alert('You must have at least one item.');
    }
}

function toggleAddress(select) {
    document.getElementById('addressField').style.display = select.value === 'delivery' ? 'block' : 'none';
}

document.getElementById('orderForm').addEventListener('submit', function(e) {
    let hasValid = false;
    document.querySelectorAll('.item-row').forEach(row => {
        const name = row.querySelector('select[name="item_name[]"]').value;
        const qty = parseInt(row.querySelector('input[name="item_qty[]"]').value) || 0;
        if (name && qty > 0) hasValid = true;
    });
    if (!hasValid) {
        e.preventDefault();
        alert('Please add at least one valid item.');
    }
});

calculateTotal();
</script>

<?php include 'footer.php'; ?>
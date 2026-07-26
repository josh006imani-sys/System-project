<?php
// inventory.php — I made this to track laundry supplies
// Managers and admins can add items, update stock, edit items, and see battery-style stock indicators

session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'manager' && $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

$pageTitle = 'Inventory';
$activePage = 'inventory';

$message = '';
$messageType = '';

// Create table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    category VARCHAR(100) DEFAULT 'supplies',
    quantity INT DEFAULT 0,
    unit VARCHAR(50) DEFAULT 'pcs',
    min_stock INT DEFAULT 10,
    max_stock INT DEFAULT 100,
    cost_per_unit DECIMAL(10,2) DEFAULT 0,
    supplier VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Add max_stock column if not exists (for existing databases)
$conn->query("ALTER TABLE inventory ADD COLUMN IF NOT EXISTS max_stock INT DEFAULT 100");

// Add item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $name = $conn->real_escape_string($_POST['item_name'] ?? '');
    $category = $conn->real_escape_string($_POST['category'] ?? 'supplies');
    $qty = (int)($_POST['quantity'] ?? 0);
    $unit = $conn->real_escape_string($_POST['unit'] ?? 'pcs');
    $minStock = (int)($_POST['min_stock'] ?? 10);
    $maxStock = (int)($_POST['max_stock'] ?? 100);
    $cost = (float)($_POST['cost_per_unit'] ?? 0);
    $supplier = $conn->real_escape_string($_POST['supplier'] ?? '');
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');

    $conn->query("INSERT INTO inventory (item_name, category, quantity, unit, min_stock, max_stock, cost_per_unit, supplier, notes) 
                  VALUES ('$name', '$category', $qty, '$unit', $minStock, $maxStock, $cost, '$supplier', '$notes')");
    $message = 'Item added.';
    $messageType = 'success';
}

// Edit item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_item'])) {
    $id = (int)($_POST['edit_id'] ?? 0);
    $name = $conn->real_escape_string($_POST['edit_item_name'] ?? '');
    $category = $conn->real_escape_string($_POST['edit_category'] ?? 'supplies');
    $qty = (int)($_POST['edit_quantity'] ?? 0);
    $unit = $conn->real_escape_string($_POST['edit_unit'] ?? 'pcs');
    $minStock = (int)($_POST['edit_min_stock'] ?? 10);
    $maxStock = (int)($_POST['edit_max_stock'] ?? 100);
    $cost = (float)($_POST['edit_cost_per_unit'] ?? 0);
    $supplier = $conn->real_escape_string($_POST['edit_supplier'] ?? '');
    $notes = $conn->real_escape_string($_POST['edit_notes'] ?? '');

    if ($id > 0 && !empty($name)) {
        $conn->query("UPDATE inventory SET 
            item_name = '$name', 
            category = '$category', 
            quantity = $qty, 
            unit = '$unit', 
            min_stock = $minStock, 
            max_stock = $maxStock, 
            cost_per_unit = $cost, 
            supplier = '$supplier', 
            notes = '$notes' 
            WHERE id = $id");
        $message = 'Item updated.';
        $messageType = 'success';
    } else {
        $message = 'Invalid item data.';
        $messageType = 'error';
    }
}

// Update quantity (quick add)
if (isset($_GET['add_stock']) && isset($_GET['qty'])) {
    $id = (int)$_GET['add_stock'];
    $qty = (int)$_GET['qty'];
    $conn->query("UPDATE inventory SET quantity = quantity + $qty WHERE id = $id");
    $message = 'Stock updated.';
    $messageType = 'success';
}

// Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM inventory WHERE id = $id");
    $message = 'Item deleted.';
    $messageType = 'success';
}

// Fetch inventory
$result = $conn->query("SELECT * FROM inventory ORDER BY item_name");
$inventory = [];
while ($row = $result->fetch_assoc()) $inventory[] = $row;

function formatMoney($amount) {
    return 'Ksh ' . number_format($amount, 2);
}

// Calculate stock percentage and battery color
function getStockInfo($qty, $minStock, $maxStock) {
    if ($maxStock <= 0) $maxStock = max($minStock * 2, 100);
    $pct = min(100, max(0, round(($qty / $maxStock) * 100)));

    if ($qty <= 0) {
        $color = '#e53e3e'; // Red - empty
        $status = 'Empty';
        $statusClass = 'empty';
    } elseif ($qty <= $minStock) {
        $color = '#e53e3e'; // Red - low
        $status = 'Low';
        $statusClass = 'low';
    } elseif ($pct <= 50) {
        $color = '#ecc94b'; // Yellow - medium
        $status = 'Medium';
        $statusClass = 'medium';
    } else {
        $color = '#48bb78'; // Green - good
        $status = 'Good';
        $statusClass = 'good';
    }

    return ['pct' => $pct, 'color' => $color, 'status' => $status, 'class' => $statusClass];
}

include 'header.php';
?>

<style>
/* ===== COMPACT BATTERY-STYLE STOCK INDICATOR ===== */
.stock-battery {
    display: flex;
    align-items: center;
    gap: 6px;
}

.battery-body {
    position: relative;
    width: 36px;
    height: 16px;
    border: 2px solid #cbd5e0;
    border-radius: 3px;
    background: #f7fafc;
    overflow: hidden;
}

.battery-body::after {
    content: '';
    position: absolute;
    right: -4px;
    top: 50%;
    transform: translateY(-50%);
    width: 2px;
    height: 6px;
    background: #cbd5e0;
    border-radius: 0 1px 1px 0;
}

.battery-fill {
    height: 100%;
    border-radius: 1px;
    transition: width 0.5s ease, background-color 0.3s ease;
}

/* Pulsing animation for low/empty stock */
@keyframes battery-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.battery-fill.low, .battery-fill.empty {
    animation: battery-pulse 1.5s infinite;
}

/* Status badge - matches existing layout */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: bold;
}

.status-badge.good { background: #f0fff4; color: #2f855a; }
.status-badge.medium { background: #fffbeb; color: #b7791f; }
.status-badge.low { background: #fff5f5; color: #c53030; }
.status-badge.empty { background: #fed7d7; color: #9b2c2c; }

/* Edit form - matches existing page style */
#editForm {
    display: none;
    background: white;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 1px solid #ddd;
}

#editForm.show { display: block; }

/* Action links - matches existing row-actions style */
.row-actions { display: flex; gap: 10px; }
.row-actions a {
    font-size: 12.5px;
    font-weight: 600;
    text-decoration: underline;
}
.row-actions .edit-link { color: #2b6cb0; }
.row-actions .delete-link { color: #ea4335; }
</style>

<div style="margin-bottom: 20px;">
    <h1 style="font-size: 24px; margin-bottom: 5px;">Inventory</h1>
    <p style="color: #666; font-size: 14px;">Track laundry supplies and stock levels</p>
</div>

<?php if ($message): ?>
<div style="padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 13px; <?php echo $messageType === 'success' ? 'background: #e8f5e9; color: #2e7d32;' : 'background: #ffebee; color: #c62828;'; ?>">
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<div id="editForm">
    <h3 style="margin: 0 0 15px 0; font-size: 16px;">Edit Item</h3>
    <form method="POST" action="">
        <input type="hidden" name="edit_id" id="edit_id">
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap: 10px; align-items: end;">
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Item Name</label>
                <input type="text" name="edit_item_name" id="edit_item_name" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Category</label>
                <select name="edit_category" id="edit_category" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="supplies">Supplies</option>
                    <option value="equipment">Equipment</option>
                    <option value="packaging">Packaging</option>
                    <option value="cleaning">Cleaning</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Quantity</label>
                <input type="number" name="edit_quantity" id="edit_quantity" min="0" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Unit</label>
                <input type="text" name="edit_unit" id="edit_unit" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div style="display: flex; gap: 5px;">
                <button type="submit" name="edit_item" style="background: #667eea; color: white; border: none; padding: 8px 16px; border-radius: 4px; font-weight: bold; cursor: pointer;">Update</button>
                <button type="button" onclick="document.getElementById('editForm').style.display='none'" style="background: #888; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer;">Cancel</button>
            </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 10px; margin-top: 10px;">
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Min Stock</label>
                <input type="number" name="edit_min_stock" id="edit_min_stock" min="0" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Max Stock</label>
                <input type="number" name="edit_max_stock" id="edit_max_stock" min="1" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Cost/Unit (Ksh)</label>
                <input type="number" name="edit_cost_per_unit" id="edit_cost_per_unit" min="0" step="0.01" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Supplier</label>
                <input type="text" name="edit_supplier" id="edit_supplier" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
        </div>
        <div style="margin-top: 10px;">
            <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Notes</label>
            <input type="text" name="edit_notes" id="edit_notes" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>
    </form>
</div>

<div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
    <h3 style="margin: 0 0 15px 0; font-size: 16px;">Add New Inventory Item</h3>
    <form method="POST" action="">
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap: 10px; align-items: end;">
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Item Name</label>
                <input type="text" name="item_name" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Category</label>
                <select name="category" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="supplies">Supplies</option>
                    <option value="equipment">Equipment</option>
                    <option value="packaging">Packaging</option>
                    <option value="cleaning">Cleaning</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Quantity</label>
                <input type="number" name="quantity" min="0" value="0" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Unit</label>
                <input type="text" name="unit" value="pcs" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div>
                <button type="submit" name="add_item" style="background: #48bb78; color: white; border: none; padding: 8px 16px; border-radius: 4px; font-weight: bold; cursor: pointer; height: 36px;">Add Item</button>
            </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 10px; margin-top: 10px;">
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Min Stock (Alert level)</label>
                <input type="number" name="min_stock" min="0" value="10" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Max Stock (Target cap)</label>
                <input type="number" name="max_stock" min="1" value="100" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Cost per Unit (Ksh)</label>
                <input type="number" name="cost_per_unit" min="0" step="0.01" value="0.00" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Supplier</label>
                <input type="text" name="supplier" placeholder="Optional" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
        </div>
        <div style="margin-top: 10px;">
            <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Notes</label>
            <input type="text" name="notes" placeholder="Optional comments..." style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>
    </form>
</div>

<div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
        <thead>
            <tr style="border-bottom: 2px solid #edf2f7; color: #4a5568;">
                <th style="padding: 10px; text-align: left;">Item Name</th>
                <th style="padding: 10px; text-align: left;">Category</th>
                <th style="padding: 10px; text-align: left;">Stock Level</th>
                <th style="padding: 10px; text-align: left;">Quantity</th>
                <th style="padding: 10px; text-align: left;">Cost/Unit</th>
                <th style="padding: 10px; text-align: left;">Total Value</th>
                <th style="padding: 10px; text-align: left;">Supplier</th>
                <th style="padding: 10px; text-align: left;">Notes</th>
                <th style="padding: 10px; text-align: left;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($inventory as $item): 
                $stock = getStockInfo($item['quantity'], $item['min_stock'], $item['max_stock']);
                $totalValue = $item['quantity'] * $item['cost_per_unit'];
            ?>
            <tr style="border-bottom: 1px solid #edf2f7; color: #2d3748;">
                <td style="padding: 10px; font-weight: 600;"><?php echo htmlspecialchars($item['item_name']); ?></td>
                <td style="padding: 10px;"><span style="text-transform: capitalize; font-size: 11px; background: #edf2f7; padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($item['category']); ?></span></td>
                
                <td style="padding: 10px;">
                    <div style="display: flex; flex-direction: column; gap: 4px;">
                        <div class="stock-battery">
                            <div class="battery-body">
                                <div class="battery-fill <?php echo $stock['class']; ?>" style="width: <?php echo $stock['pct']; ?>%; background-color: <?php echo $stock['color']; ?>;"></div>
                            </div>
                            <span style="font-size: 11px; color: #718096; font-weight: bold;"><?php echo $stock['pct']; ?>%</span>
                        </div>
                        <div>
                            <span class="status-badge <?php echo $stock['class']; ?>"><?php echo $stock['status']; ?></span>
                        </div>
                    </div>
                </td>
                
                <td style="padding: 10px; font-weight: bold; font-size: 14px;"><?php echo $item['quantity']; ?> <span style="font-size: 11px; color: #718096; font-weight: normal;"><?php echo htmlspecialchars($item['unit']); ?></span></td>
                <td style="padding: 10px; color: #4a5568;"><?php echo formatMoney($item['cost_per_unit']); ?></td>
                <td style="padding: 10px; font-weight: 600; color: #2d3748;"><?php echo formatMoney($totalValue); ?></td>
                <td style="padding: 10px; color: #718096;"><?php echo htmlspecialchars($item['supplier'] ?: '-'); ?></td>
                <td style="padding: 10px; color: #718096; max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($item['notes']); ?>">
                    <?php echo htmlspecialchars($item['notes'] ?: '-'); ?>
                </td>
                <td style="padding: 10px;">
                    <div class="row-actions">
                        <a href="javascript:void(0)" class="edit-link" onclick="openEditForm(
                            '<?php echo $item['id']; ?>',
                            '<?php echo addslashes($item['item_name']); ?>',
                            '<?php echo addslashes($item['category']); ?>',
                            '<?php echo $item['quantity']; ?>',
                            '<?php echo addslashes($item['unit']); ?>',
                            '<?php echo $item['min_stock']; ?>',
                            '<?php echo $item['max_stock']; ?>',
                            '<?php echo $item['cost_per_unit']; ?>',
                            '<?php echo addslashes($item['supplier']); ?>',
                            '<?php echo addslashes($item['notes']); ?>'
                        )">Edit</a>
                        
                        <a href="inventory.php?add_stock=<?php echo $item['id']; ?>&qty=10" style="color: #48bb78; font-size: 12px; font-weight: 600; text-decoration: underline;">+10</a>
                        <a href="inventory.php?add_stock=<?php echo $item['id']; ?>&qty=50" style="color: #3182ce; font-size: 12px; font-weight: 600; text-decoration: underline;">+50</a>
                        
                        <a href="inventory.php?delete=<?php echo $item['id']; ?>" class="delete-link" onclick="return confirm('Delete this item?')">Delete</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($inventory)): ?>
            <tr><td colspan="9" style="padding: 30px; text-align: center; color: #999;">No inventory items yet</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function openEditForm(id, name, category, qty, unit, minStock, maxStock, cost, supplier, notes) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_item_name').value = name;
    document.getElementById('edit_category').value = category;
    document.getElementById('edit_quantity').value = qty;
    document.getElementById('edit_unit').value = unit;
    document.getElementById('edit_min_stock').value = minStock;
    document.getElementById('edit_max_stock').value = maxStock;
    document.getElementById('edit_cost_per_unit').value = cost;
    document.getElementById('edit_supplier').value = supplier;
    document.getElementById('edit_notes').value = notes;

    var form = document.getElementById('editForm');
    form.style.display = 'block';
    form.scrollIntoView({ behavior: 'smooth' });
}
</script>

<?php include 'footer.php'; ?>
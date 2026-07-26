<?php
// services.php — Service Pricing
// Add, edit, and delete laundry service prices.

session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'manager' && $_SESSION['role'] !== 'admin')) {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

$pageTitle = 'Prices';
$activePage = 'services';

$message = '';
$messageType = '';

// Add service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $name = $conn->real_escape_string($_POST['name'] ?? '');
    $unit = $conn->real_escape_string($_POST['unit'] ?? 'per item');
    $price = (float)($_POST['price'] ?? 0);

    if ($name && $price >= 0) {
        $conn->query("INSERT INTO services (name, unit, price) VALUES ('$name', '$unit', $price)");
        $message = 'Service added.';
        $messageType = 'success';
    } else {
        $message = 'Please fill in all fields.';
        $messageType = 'error';
    }
}

// Edit service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_service'])) {
    $id = (int)$_POST['id'];
    $name = $conn->real_escape_string($_POST['name'] ?? '');
    $unit = $conn->real_escape_string($_POST['unit'] ?? 'per item');
    $price = (float)($_POST['price'] ?? 0);

    $conn->query("UPDATE services SET name='$name', unit='$unit', price=$price WHERE id=$id");
    $message = 'Service updated.';
    $messageType = 'success';
}

// Delete service
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM services WHERE id = $id");
    $message = 'Service deleted.';
    $messageType = 'success';
}

// Fetch services
$result = $conn->query("SELECT * FROM services ORDER BY name");
$services = [];
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}

function formatMoney($amount) {
    return 'Ksh ' . number_format($amount, 2);
}

include 'header.php';
?>

<div style="margin-bottom: 20px;">
    <h1 style="font-size: 24px; margin-bottom: 5px;">Prices</h1>
    <p style="color: #666; font-size: 14px;">Standard rates for each service</p>
</div>

<?php if ($message): ?>
<div style="padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 13px; <?php echo $messageType === 'success' ? 'background: #e8f5e9; color: #2e7d32;' : 'background: #ffebee; color: #c62828;'; ?>">
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<!-- Add Service Form -->
<div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ddd;">
    <h3 style="margin: 0 0 15px 0; font-size: 16px;">Add Service</h3>
    <form method="POST">
        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 10px; align-items: end;">
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Service Name</label>
                <input type="text" name="name" placeholder="e.g. Shirts" required 
                       style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Unit</label>
                <select name="unit" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="per item">Per item</option>
                    <option value="per kg">Per kg</option>
                    <option value="per load">Per load</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Price (Ksh)</label>
                <input type="number" name="price" min="0" step="0.01" placeholder="0.00" required 
                       style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <button type="submit" name="add_service" style="background: #34a853; color: white; border: none; padding: 8px 20px; border-radius: 4px; font-weight: bold; cursor: pointer;">Add</button>
        </div>
    </form>
</div>

<!-- Services Table -->
<div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
    <h3 style="margin: 0 0 15px 0; font-size: 16px;">Service Prices</h3>
    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
        <thead>
            <tr style="background: #f5f5f5;">
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Service</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Unit</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Price</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($services as $s): ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px; font-weight: 600;"><?php echo htmlspecialchars($s['name']); ?></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($s['unit']); ?></td>
                <td style="padding: 10px;"><?php echo formatMoney($s['price']); ?></td>
                <td style="padding: 10px;">
                    <button onclick="editService(<?php echo $s['id']; ?>, '<?php echo htmlspecialchars($s['name'], ENT_QUOTES); ?>', '<?php echo $s['unit']; ?>', <?php echo $s['price']; ?>)" 
                            style="background: none; border: none; color: #667eea; cursor: pointer; font-size: 12px; margin-right: 10px;">Edit</button>
                    <a href="services.php?delete=<?php echo $s['id']; ?>" 
                       onclick="return confirm('Delete this service?')" 
                       style="color: #ea4335; font-size: 12px; text-decoration: none;">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($services)): ?>
            <tr><td colspan="4" style="padding: 30px; text-align: center; color: #999;">No services yet</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Edit Form (hidden by default) -->
<div id="editForm" style="display:none; background: white; padding: 15px; border-radius: 8px; margin-top: 20px; border: 1px solid #667eea;">
    <h3 style="margin: 0 0 15px 0; font-size: 16px;">Edit Service</h3>
    <form method="POST">
        <input type="hidden" name="id" id="edit_id">
        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 10px; align-items: end;">
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Service Name</label>
                <input type="text" name="name" id="edit_name" required 
                       style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Unit</label>
                <select name="unit" id="edit_unit" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="per item">Per item</option>
                    <option value="per kg">Per kg</option>
                    <option value="per load">Per load</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Price (Ksh)</label>
                <input type="number" name="price" id="edit_price" min="0" step="0.01" required 
                       style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div>
                <button type="submit" name="edit_service" style="background: #667eea; color: white; border: none; padding: 8px 20px; border-radius: 4px; font-weight: bold; cursor: pointer;">Update</button>
                <button type="button" onclick="document.getElementById('editForm').style.display='none'" style="background: #888; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; margin-left: 5px;">Cancel</button>
            </div>
        </div>
    </form>
</div>

<script>
function editService(id, name, unit, price) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_unit').value = unit;
    document.getElementById('edit_price').value = price;
    document.getElementById('editForm').style.display = 'block';
    document.getElementById('editForm').scrollIntoView({behavior: 'smooth'});
}
</script>

<?php include 'footer.php'; ?>
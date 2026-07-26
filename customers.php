<?php
// customers.php — Customer Management
// View all customers, add new ones, and delete if needed.

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

$pageTitle = 'Customers';
$activePage = 'customers';

$message = '';
$messageType = '';

// Add customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $name = $conn->real_escape_string($_POST['full_name'] ?? '');
    $phone = $conn->real_escape_string($_POST['phone'] ?? '');

    if ($name && $phone) {
        $conn->query("INSERT INTO customer (full_name, phone) VALUES ('$name', '$phone')");
        $message = 'Customer added.';
        $messageType = 'success';
    } else {
        $message = 'Please fill in all fields.';
        $messageType = 'error';
    }
}

// Delete customer
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM customer WHERE id = $id");
    $message = 'Customer deleted.';
    $messageType = 'success';
}

// Fetch customers
$result = $conn->query("SELECT * FROM customer ORDER BY created_at DESC");
$customers = [];
while ($row = $result->fetch_assoc()) {
    $customers[] = $row;
}

include 'header.php';
?>

<div style="margin-bottom: 20px;">
    <h1 style="font-size: 24px; margin-bottom: 5px;">Customers</h1>
    <p style="color: #666; font-size: 14px;">Everyone who has placed an order</p>
</div>

<?php if ($message): ?>
<div style="padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 13px; <?php echo $messageType === 'success' ? 'background: #e8f5e9; color: #2e7d32;' : 'background: #ffebee; color: #c62828;'; ?>">
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<!-- Add Customer Form -->
<div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ddd;">
    <h3 style="margin: 0 0 15px 0; font-size: 16px;">Add Customer</h3>
    <form method="POST">
        <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 10px; align-items: end;">
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Full Name</label>
                <input type="text" name="full_name" placeholder="e.g. Maria Santos" required 
                       style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Phone</label>
                <input type="tel" name="phone" placeholder="0712-345-678" required 
                       style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <button type="submit" name="add_customer" style="background: #34a853; color: white; border: none; padding: 8px 20px; border-radius: 4px; font-weight: bold; cursor: pointer;">Add</button>
        </div>
    </form>
</div>

<!-- Customers Table -->
<div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
    <h3 style="margin: 0 0 15px 0; font-size: 16px;">Customer List (<?php echo count($customers); ?>)</h3>
    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
        <thead>
            <tr style="background: #f5f5f5;">
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Name</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Phone</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Created</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customers as $c): ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px; font-weight: 600;"><?php echo htmlspecialchars($c['full_name']); ?></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($c['phone']); ?></td>
                <td style="padding: 10px; color: #666;"><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
                <td style="padding: 10px;">
                    <a href="customers.php?delete=<?php echo $c['id']; ?>" 
                       onclick="return confirm('Delete this customer?')" 
                       style="color: #ea4335; font-size: 12px; text-decoration: none;">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($customers)): ?>
            <tr><td colspan="4" style="padding: 30px; text-align: center; color: #999;">No customers yet</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>
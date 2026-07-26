<?php
// expenses.php — I made this to track business costs
// Managers and admins can add expenses by category and see totals

session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'manager' && $_SESSION['role'] !== 'admin')) {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

$pageTitle = 'Expenses';
$activePage = 'expenses';

$message = '';
$messageType = '';

// Create table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    expense_date DATE NOT NULL,
    description TEXT,
    created_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Add expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    $category = $conn->real_escape_string($_POST['category'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $date = $conn->real_escape_string($_POST['expense_date'] ?? date('Y-m-d'));
    $description = $conn->real_escape_string($_POST['description'] ?? '');

    if ($category && $amount > 0) {
        $conn->query("INSERT INTO expenses (category, amount, expense_date, description, created_by) 
                      VALUES ('$category', $amount, '$date', '$description', {$_SESSION['user_id']})");
        $message = 'Expense recorded.';
        $messageType = 'success';
    } else {
        $message = 'Please enter category and amount.';
        $messageType = 'error';
    }
}

// Edit expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_expense'])) {
    $editId = (int)($_POST['edit_id'] ?? 0);
    $category = $conn->real_escape_string($_POST['edit_category'] ?? '');
    $amount = (float)($_POST['edit_amount'] ?? 0);
    $date = $conn->real_escape_string($_POST['edit_expense_date'] ?? date('Y-m-d'));
    $description = $conn->real_escape_string($_POST['edit_description'] ?? '');

    if ($editId > 0 && $category && $amount > 0) {
        $conn->query("UPDATE expenses SET category='$category', amount=$amount, expense_date='$date', description='$description' WHERE id = $editId");
        $message = 'Expense updated.';
        $messageType = 'success';
    } else {
        $message = 'Please fill all fields correctly.';
        $messageType = 'error';
    }
}

// Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM expenses WHERE id = $id");
    $message = 'Expense deleted.';
    $messageType = 'success';
}

// Fetch expenses
$result = $conn->query("SELECT e.*, u.full_name as recorded_by_name FROM expenses e LEFT JOIN users u ON e.created_by = u.id ORDER BY e.expense_date DESC");
$expenses = [];
$totalExpenses = 0;
while ($row = $result->fetch_assoc()) {
    $expenses[] = $row;
    $totalExpenses += $row['amount'];
}

// Category totals for chart
$catRes = $conn->query("SELECT category, SUM(amount) as total FROM expenses GROUP BY category ORDER BY total DESC");
$categories = [];
$catTotals = [];
while ($row = $catRes->fetch_assoc()) {
    $categories[] = $row['category'];
    $catTotals[] = (float)$row['total'];
}

function formatMoney($amount) {
    return 'Ksh ' . number_format($amount, 2);
}

include 'header.php';
?>

<div style="margin-bottom: 20px;">
    <h1 style="font-size: 24px; margin-bottom: 5px;">Expenses</h1>
    <p style="color: #666; font-size: 14px;">Track business costs and operational expenses</p>
</div>

<?php if ($message): ?>
<div style="padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 13px; <?php echo $messageType === 'success' ? 'background: #e8f5e9; color: #2e7d32;' : 'background: #ffebee; color: #c62828;'; ?>">
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<!-- Stats Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 25px;">
    <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #ea4335;">
        <p style="margin: 0; font-size: 12px; color: #666;">Total Expenses</p>
        <p style="margin: 5px 0 0 0; font-size: 28px; font-weight: bold;"><?php echo formatMoney($totalExpenses); ?></p>
    </div>
    <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea;">
        <p style="margin: 0; font-size: 12px; color: #666;">Records</p>
        <p style="margin: 5px 0 0 0; font-size: 28px; font-weight: bold;"><?php echo count($expenses); ?></p>
    </div>
</div>

<!-- Add Expense Form -->
<div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ddd;">
    <h3 style="margin: 0 0 15px 0; font-size: 16px;">Record Expense</h3>
    <form method="POST">
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 10px; align-items: end;">
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Category</label>
                <select name="category" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="">Select</option>
                    <option>Detergent & Supplies</option>
                    <option>Equipment Repair</option>
                    <option>Electricity</option>
                    <option>Water</option>
                    <option>Rent</option>
                    <option>Staff Salaries</option>
                    <option>Fuel/Transport</option>
                    <option>Other</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Amount (Ksh)</label>
                <input type="number" name="amount" min="0" step="0.01" required placeholder="0.00" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Date</label>
                <input type="date" name="expense_date" required value="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <button type="submit" name="add_expense" style="background: #34a853; color: white; border: none; padding: 8px 20px; border-radius: 4px; font-weight: bold; cursor: pointer;">Record</button>
        </div>
        <div style="margin-top: 10px;">
            <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Description</label>
            <input type="text" name="description" placeholder="Optional details..." style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>
    </form>
</div>

<!-- Edit Expense Form (hidden by default) -->
<div id="editForm" style="display:none; background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #667eea;">
    <h3 style="margin: 0 0 15px 0; font-size: 16px; color: #667eea;"><i class="fas fa-edit"></i> Edit Expense</h3>
    <form method="POST">
        <input type="hidden" name="edit_id" id="edit_id">
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 10px; align-items: end;">
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Category</label>
                <select name="edit_category" id="edit_category" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="">Select</option>
                    <option>Detergent & Supplies</option>
                    <option>Equipment Repair</option>
                    <option>Electricity</option>
                    <option>Water</option>
                    <option>Rent</option>
                    <option>Staff Salaries</option>
                    <option>Fuel/Transport</option>
                    <option>Other</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Amount (Ksh)</label>
                <input type="number" name="edit_amount" id="edit_amount" min="0" step="0.01" required placeholder="0.00" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Date</label>
                <input type="date" name="edit_expense_date" id="edit_date" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="submit" name="edit_expense" style="background: #667eea; color: white; border: none; padding: 8px 20px; border-radius: 4px; font-weight: bold; cursor: pointer;">Update</button>
                <button type="button" onclick="cancelEdit()" style="background: #888; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Cancel</button>
            </div>
        </div>
        <div style="margin-top: 10px;">
            <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Description</label>
            <input type="text" name="edit_description" id="edit_description" placeholder="Optional details..." style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>
    </form>
</div>

<!-- Expenses Table -->
<div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
    <h3 style="margin: 0 0 15px 0; font-size: 16px;">Expense Records</h3>
    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
        <thead>
            <tr style="background: #f5f5f5;">
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Date</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Category</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Description</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Amount</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Recorded By</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($expenses as $e): ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px; font-size: 12px; color: #666;"><?php echo date('M d, Y', strtotime($e['expense_date'])); ?></td>
                <td style="padding: 10px; font-weight: 600;"><?php echo htmlspecialchars($e['category']); ?></td>
                <td style="padding: 10px; font-size: 13px; color: #666;"><?php echo htmlspecialchars($e['description'] ?: '-'); ?></td>
                <td style="padding: 10px; font-weight: 600; color: #ea4335;"><?php echo formatMoney($e['amount']); ?></td>
                <td style="padding: 10px; font-size: 12px; color: #666;"><?php echo htmlspecialchars($e['recorded_by_name'] ?? 'System'); ?></td>
                <td style="padding: 10px;">
                    <a href="#" class="edit-expense-link" 
   data-id="<?php echo $e['id']; ?>"
   data-category="<?php echo htmlspecialchars($e['category'], ENT_QUOTES, 'UTF-8'); ?>"
   data-amount="<?php echo $e['amount']; ?>"
   data-date="<?php echo $e['expense_date']; ?>"
   data-description="<?php echo htmlspecialchars($e['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
   style="color: #667eea; font-size: 12px; font-weight: 600; text-decoration: none; margin-right: 10px;">Edit</a>
                    <a href="expenses.php?delete=<?php echo $e['id']; ?>" style="color: #ea4335; font-size: 12px; text-decoration: none;" onclick="return confirm('Delete this expense?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($expenses)): ?>
            <tr><td colspan="6" style="padding: 30px; text-align: center; color: #999;">No expenses recorded yet</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div style="display: flex; justify-content: flex-end; margin-top: 16px; padding-top: 14px; border-top: 1px solid #e2e8f0; gap: 10px; align-items: center;">
        <span style="font-size: 12.5px; font-weight: 600; color: #4a5568;">Total:</span>
        <span style="font-size: 17px; font-weight: 800; color: #ea4335;"><?php echo formatMoney($totalExpenses); ?></span>
    </div>
</div>

<?php if (!empty($categories)): ?>
<!-- Category Chart -->
<div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd; margin-top: 20px;">
    <h3 style="margin: 0 0 15px 0; font-size: 16px;">Expenses by Category</h3>
    <div style="max-width: 500px; margin: 0 auto;">
        <canvas id="expenseChart" height="250"></canvas>
    </div>
</div>
<script>
// Use event delegation for edit links - more reliable than inline onclick
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.edit-expense-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var id = this.getAttribute('data-id');
            var category = this.getAttribute('data-category');
            var amount = this.getAttribute('data-amount');
            var date = this.getAttribute('data-date');
            var description = this.getAttribute('data-description');

            document.getElementById('edit_id').value = id;
            document.getElementById('edit_category').value = category;
            document.getElementById('edit_amount').value = amount;
            document.getElementById('edit_date').value = date;
            document.getElementById('edit_description').value = description || '';

            var form = document.getElementById('editForm');
            form.style.display = 'block';
            form.scrollIntoView({behavior: 'smooth', block: 'center'});
        });
    });
});

function cancelEdit() {
    document.getElementById('editForm').style.display = 'none';
}
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('expenseChart'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($categories); ?>,
        datasets: [{ data: <?php echo json_encode($catTotals); ?>, backgroundColor: ['#ea4335','#f9ab00','#ecc94b','#48bb78','#4299e1','#667eea','#9f7aea','#ed64a6'] }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});
</script>
<?php endif; ?>

<?php include 'footer.php'; ?>
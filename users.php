<?php
// users.php — User Management
// Admin can add new users and activate/deactivate them.

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

$pageTitle = 'Users';
$activePage = 'users';

$message = '';
$messageType = '';

// Add user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $fullName = $conn->real_escape_string($_POST['full_name'] ?? '');
    $username = $conn->real_escape_string($_POST['username'] ?? '');
    $password = $conn->real_escape_string($_POST['password'] ?? '');
    $role = $conn->real_escape_string($_POST['role'] ?? 'staff');

    if ($fullName && $username && $password) {
        $conn->query("INSERT INTO users (full_name, username, password, role) VALUES ('$fullName', '$username', '$password', '$role')");
        $message = 'User added.';
        $messageType = 'success';
    } else {
        $message = 'Please fill in all fields.';
        $messageType = 'error';
    }
}

// Toggle status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $res = $conn->query("SELECT status FROM users WHERE id = $id");
    if ($res->num_rows) {
        $current = $res->fetch_assoc()['status'];
        $newStatus = $current === 'active' ? 'inactive' : 'active';
        $conn->query("UPDATE users SET status = '$newStatus' WHERE id = $id");
        $message = 'User status updated.';
        $messageType = 'success';
    }
}

// Fetch users
$result = $conn->query("SELECT * FROM users ORDER BY id DESC");
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

include 'header.php';
?>

<div style="margin-bottom: 20px;">
    <h1 style="font-size: 24px; margin-bottom: 5px;">Users</h1>
    <p style="color: #666; font-size: 14px;">Manage system users and their roles</p>
</div>

<?php if ($message): ?>
<div style="padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 13px; <?php echo $messageType === 'success' ? 'background: #e8f5e9; color: #2e7d32;' : 'background: #ffebee; color: #c62828;'; ?>">
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<!-- Add User Form -->
<div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ddd;">
    <h3 style="margin: 0 0 15px 0; font-size: 16px;">Add User</h3>
    <form method="POST">
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap: 10px; align-items: end;">
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Full Name</label>
                <input type="text" name="full_name" placeholder="John Doe" required 
                       style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Username</label>
                <input type="text" name="username" placeholder="johndoe" required 
                       style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Password</label>
                <input type="text" name="password" placeholder="Enter password" required 
                       style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
                       <div>
                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px;">Role</label>
                <select name="role" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="staff">Staff</option>
                    <option value="manager">Manager</option>
                    <option value="admin">Admin</option>
                    <option value="rider">Rider</option>
                </select>
            </div>
            <button type="submit" name="add_user" style="background: #34a853; color: white; border: none; padding: 8px 20px; border-radius: 4px; font-weight: bold; cursor: pointer;">Add</button>
        </div>
    </form>
</div>

<!-- Users Table -->
<div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
    <h3 style="margin: 0 0 15px 0; font-size: 16px;">System Users</h3>
    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
        <thead>
            <tr style="background: #f5f5f5;">
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Name</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Username</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Role</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Status</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Created</th>
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px; font-weight: 600;"><?php echo htmlspecialchars($u['full_name']); ?></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($u['username']); ?></td>
                <td style="padding: 10px;"><?php echo ucfirst($u['role']); ?></td>
                <td style="padding: 10px;">
                    <span style="padding: 3px 10px; border-radius: 10px; font-size: 11px; font-weight: bold; <?php echo $u['status']==='active' ? 'background: #f0fff4; color: #2f855a;' : 'background: #fff5f5; color: #ea4335;'; ?>">
                        <?php echo ucfirst($u['status']); ?>
                    </span>
                </td>
                <td style="padding: 10px; color: #666;"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                <td style="padding: 10px;">
                    <a href="users.php?toggle=<?php echo $u['id']; ?>" 
                       style="color: #667eea; font-size: 12px; text-decoration: none;">
                        <?php echo $u['status']==='active' ? 'Deactivate' : 'Activate'; ?>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
            <tr><td colspan="6" style="padding: 30px; text-align: center; color: #999;">No users found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>
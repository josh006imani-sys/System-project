<?php
// profile.php — User Profile
// Staff can update their name and username here.

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

$pageTitle = 'Profile';
$activePage = 'profile';

$message = '';
$messageType = '';

// Get current user
$id = $_SESSION['user_id'];
$res = $conn->query("SELECT * FROM users WHERE id = $id");
$user = $res->fetch_assoc();

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = $conn->real_escape_string($_POST['full_name'] ?? '');
    $username = $conn->real_escape_string($_POST['username'] ?? '');

    $conn->query("UPDATE users SET full_name='$fullName', username='$username' WHERE id=$id");
    
    $_SESSION['full_name'] = $_POST['full_name'];
    $_SESSION['username'] = $_POST['username'];
    
    $message = 'Profile updated.';
    $messageType = 'success';
    
    $res = $conn->query("SELECT * FROM users WHERE id = $id");
    $user = $res->fetch_assoc();
}

include 'header.php';
?>

<div style="margin-bottom: 20px;">
    <h1 style="font-size: 24px; margin-bottom: 5px;">Profile</h1>
    <p style="color: #666; font-size: 14px;">Your account details</p>
</div>

<?php if ($message): ?>
<div style="padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 13px; <?php echo $messageType === 'success' ? 'background: #e8f5e9; color: #2e7d32;' : 'background: #ffebee; color: #c62828;'; ?>">
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd; max-width: 500px;">
    <form method="POST">
        <div style="margin-bottom: 15px;">
            <label style="display: block; font-size: 13px; color: #555; margin-bottom: 5px; font-weight: 600;">Full Name</label>
            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required 
                   style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label style="display: block; font-size: 13px; color: #555; margin-bottom: 5px; font-weight: 600;">Username</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required 
                   style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label style="display: block; font-size: 13px; color: #555; margin-bottom: 5px; font-weight: 600;">Role</label>
            <input type="text" value="<?php echo ucfirst($user['role']); ?>" disabled 
                   style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; background: #f5f5f5;">
        </div>
        <div style="margin-bottom: 15px;">
            <label style="display: block; font-size: 13px; color: #555; margin-bottom: 5px; font-weight: 600;">Member Since</label>
            <input type="text" value="<?php echo $user['created_at']; ?>" disabled 
                   style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; background: #f5f5f5;">
        </div>
        <button type="submit" style="background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 4px; font-weight: bold; cursor: pointer;">Save Changes</button>
    </form>
</div>

<?php include 'footer.php'; ?>
<?php
// settings.php — System Settings
// I made this so admin can change business details like name, phone, address
// It reads from a settings table in the database

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

$pageTitle = 'Settings';
$activePage = 'settings';

$message = '';
$messageType = '';

// Handle saving settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    foreach ($_POST['settings'] as $key => $value) {
        $key = $conn->real_escape_string($key);
        $value = $conn->real_escape_string($value);
        $conn->query("UPDATE settings SET setting_value = '$value' WHERE setting_key = '$key'");
    }
    $message = 'Settings saved successfully.';
    $messageType = 'success';
}

// Fetch settings grouped by category
$result = $conn->query("SELECT * FROM settings ORDER BY setting_group, setting_key");
$settings = [];
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_group']][] = $row;
}

include 'header.php';
?>

<div class="page-header">
    <h1>Settings</h1>
    <p>Configure system preferences and business details.</p>
</div>

<?php if ($message): ?>
<div class="inline-message show <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<section class="table-container" style="max-width:700px;">
    <form method="POST" action="">
        <?php foreach ($settings as $group => $items): ?>
        <div style="margin-bottom:24px;">
            <!-- I group settings by category so it's easier to find -->
            <h3 style="font-size:14px;font-weight:700;color:#667eea;text-transform:uppercase;margin-bottom:12px;"><?php echo ucfirst($group); ?></h3>
            <?php foreach ($items as $s): ?>
            <div class="form-group" style="margin-bottom:14px;">
                <label style="display:block;font-size:12.5px;font-weight:600;color:#4a5568;margin-bottom:5px;">
                    <?php echo ucwords(str_replace('_', ' ', $s['setting_key'])); ?>
                </label>
                <input type="text" name="settings[<?php echo $s['setting_key']; ?>]" value="<?php echo htmlspecialchars($s['setting_value']); ?>" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:13.5px;">
                <div style="font-size:11px;color:#718096;margin-top:3px;"><?php echo htmlspecialchars($s['description']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>

        <div style="display:flex;justify-content:flex-end;margin-top:16px;padding-top:14px;border-top:1px solid #e2e8f0;">
            <button type="submit" name="save_settings" class="btn btn-primary">Save Settings</button>
        </div>
    </form>
</section>

<?php include 'footer.php'; ?>
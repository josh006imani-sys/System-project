<?php
// customer_login.php — Customer Login / Register / Reset PIN
// Customers use phone + PIN (not email/password) because that's what
// works best for our market in Kenya.

session_start();
require_once 'db_config.php';

$error = '';
$resetMsg = '';
$mode = $_GET['mode'] ?? 'login';

// LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $phone = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
    $pin = $conn->real_escape_string(trim($_POST['pin'] ?? ''));
    
    $res = $conn->query("SELECT * FROM customer WHERE phone = '$phone' AND pin = '$pin'");
    if ($res && $res->num_rows === 1) {
        $customer = $res->fetch_assoc();
        $_SESSION['customer_id'] = $customer['id'];
        $_SESSION['customer_name'] = $customer['full_name'];
        header('Location: customer_dashboard.php');
        exit;
    } else {
        $error = 'Invalid phone number or PIN.';
    }
}

// REGISTER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $fullName = $conn->real_escape_string(trim($_POST['full_name'] ?? ''));
    $phone = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
    $pin = $conn->real_escape_string(trim($_POST['pin'] ?? ''));
    $confirmPin = $conn->real_escape_string(trim($_POST['confirm_pin'] ?? ''));
    
    if (empty($fullName) || empty($phone) || empty($pin)) {
        $error = 'All fields are required.';
    } elseif (strlen($pin) < 4) {
        $error = 'PIN must be at least 4 digits.';
    } elseif ($pin !== $confirmPin) {
        $error = 'PINs do not match.';
    } else {
        $check = $conn->query("SELECT id FROM customer WHERE phone = '$phone'");
        if ($check && $check->num_rows > 0) {
            $error = 'Phone already registered. Please login.';
        } else {
            $conn->query("INSERT INTO customer (full_name, phone, pin, created_at) VALUES ('$fullName', '$phone', '$pin', NOW())");
            $_SESSION['customer_id'] = $conn->insert_id;
            $_SESSION['customer_name'] = $fullName;
            header('Location: customer_dashboard.php');
            exit;
        }
    }
}

// RESET PIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_pin'])) {
    $resetPhone = $conn->real_escape_string(trim($_POST['reset_phone'] ?? ''));
    $newPin = $conn->real_escape_string(trim($_POST['new_pin'] ?? ''));
    $confirmNewPin = $conn->real_escape_string(trim($_POST['confirm_new_pin'] ?? ''));
    
    if (empty($resetPhone) || empty($newPin)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($newPin) < 4) {
        $error = 'PIN must be at least 4 digits.';
    } elseif ($newPin !== $confirmNewPin) {
        $error = 'PINs do not match.';
    } else {
        $check = $conn->query("SELECT id FROM customer WHERE phone = '$resetPhone'");
        if ($check && $check->num_rows > 0) {
            $conn->query("UPDATE customer SET pin = '$newPin' WHERE phone = '$resetPhone'");
            $resetMsg = 'PIN reset successful! You can now login.';
            $mode = 'login';
        } else {
            $error = 'Phone number not found.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Portal - Muthoni's Laundry</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #667eea;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .box {
            background: white;
            padding: 35px;
            border-radius: 10px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        .brand { text-align: center; margin-bottom: 25px; }
        .brand h2 { color: #333; margin-bottom: 5px; }
        .brand p { color: #888; font-size: 14px; }
        
        .tabs {
            display: flex;
            background: #f5f5f5;
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 4px;
        }
        .tabs a {
            flex: 1;
            padding: 10px;
            text-align: center;
            border-radius: 6px;
            text-decoration: none;
            color: #666;
            font-size: 14px;
            font-weight: 600;
        }
        .tabs a.active {
            background: white;
            color: #667eea;
        }
        
        .form-group { margin-bottom: 15px; }
        .form-group label {
            display: block;
            font-size: 13px;
            color: #555;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-group input:focus {
            border-color: #667eea;
            outline: none;
        }
        
        .error {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 5px;
            font-size: 13px;
            margin-bottom: 15px;
        }
        .success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 5px;
            font-size: 13px;
            margin-bottom: 15px;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn:hover { background: #5568d3; }
        
        .links {
            text-align: center;
            margin-top: 15px;
            font-size: 13px;
        }
        .links a { color: #667eea; text-decoration: none; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="box">
        <div class="brand">
            <h2><i class="fas fa-tshirt"></i> Muthoni's Laundry</h2>
            <p>Customer Portal</p>
        </div>

        <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($resetMsg): ?>
        <div class="success"><?php echo htmlspecialchars($resetMsg); ?></div>
        <?php endif; ?>

        <?php if ($mode === 'login'): ?>
        <div class="tabs">
            <a href="?mode=login" class="active">Sign In</a>
            <a href="?mode=register">Create Account</a>
        </div>
        <form method="POST">
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" placeholder="e.g. 0712345678" required autofocus>
            </div>
            <div class="form-group">
                <label>PIN</label>
                <input type="password" name="pin" placeholder="Enter your PIN" required>
            </div>
            <button type="submit" name="login" class="btn">Sign In</button>
        </form>
        
        <div class="links">
            <a href="?mode=reset">Forgot PIN?</a><br><br>
            <a href="track_guest.php">Track Order as Guest</a> | 
            <a href="index.php">Back to Home</a>
        </div>

        <?php elseif ($mode === 'register'): ?>
        <div class="tabs">
            <a href="?mode=login">Sign In</a>
            <a href="?mode=register" class="active">Create Account</a>
        </div>
        <form method="POST" action="?mode=register">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" placeholder="Your full name" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" placeholder="e.g. 0712345678" required>
            </div>
            <div class="form-group">
                <label>Create PIN (min 4 digits)</label>
                <input type="password" name="pin" placeholder="Create a PIN" required minlength="4">
            </div>
            <div class="form-group">
                <label>Confirm PIN</label>
                <input type="password" name="confirm_pin" placeholder="Repeat PIN" required minlength="4">
            </div>
            <button type="submit" name="register" class="btn">Create Account</button>
        </form>

        <?php elseif ($mode === 'reset'): ?>
        <div class="tabs">
            <a href="?mode=login">Sign In</a>
            <a href="?mode=register">Create Account</a>
        </div>
        <h3 style="text-align:center;margin-bottom:20px;font-size:16px;">Reset Your PIN</h3>
        <form method="POST" action="?mode=reset">
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="reset_phone" placeholder="e.g. 0712345678" required autofocus>
            </div>
            <div class="form-group">
                <label>New PIN (min 4 digits)</label>
                <input type="password" name="new_pin" placeholder="New PIN" required minlength="4">
            </div>
            <div class="form-group">
                <label>Confirm New PIN</label>
                <input type="password" name="confirm_new_pin" placeholder="Repeat PIN" required minlength="4">
            </div>
            <button type="submit" name="reset_pin" class="btn">Reset PIN</button>
        </form>
        <div class="links">
            <a href="?mode=login"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
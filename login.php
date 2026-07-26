<?php
// login.php - Staff login page
// I made this so admin, manager and staff can login to the system
// Each role sees different things after login

session_start();
require_once 'db_config.php';

$error = '';
$success = '';

// When form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $conn->real_escape_string(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    
    // Check if both fields are filled
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Look for user in database
        $result = $conn->query("SELECT * FROM users WHERE username = '$username'");
        
        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Check password (I use plain text for now, should hash later)
            if ($password === $user['password']) {
                // Save user info in session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'] ?? $user['username'];
                $_SESSION['role'] = $user['role'] ?? 'staff';
                
                // Send to different dashboard based on role
                if ($user['role'] === 'admin') {
                    header('Location: dashboard_admin.php');
                } elseif ($user['role'] === 'manager') {
                    header('Location: dashboard_manager.php');
                } elseif ($user['role'] === 'rider') {
                    header('Location: dashboard_rider.php');
                } else {
                    header('Location: dashboard_staff.php');
                }
                exit;
            } else {
                $error = 'Wrong password. Please try again.';
            }
        } else {
            $error = 'Username not found.';
        }
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $resetUsername = $conn->real_escape_string(trim($_POST['reset_username'] ?? ''));
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_new_password'] ?? '';
    
    if (empty($resetUsername) || empty($newPassword)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        // Check if user exists
        $check = $conn->query("SELECT id FROM users WHERE username = '$resetUsername'");
        if ($check && $check->num_rows > 0) {
            $newPass = $conn->real_escape_string($newPassword);
            $conn->query("UPDATE users SET password = '$newPass' WHERE username = '$resetUsername'");
            $success = 'Password reset successful! You can now login.';
        } else {
            $error = 'Username not found.';
        }
    }
}

$showReset = isset($_GET['reset']) || !empty($success);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - Muthoni's Laundry</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Simple centered login form */
        body {
            font-family: Arial, sans-serif;
            background: #667eea;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-box {
            background: white;
            padding: 35px;
            border-radius: 10px;
            width: 100%;
            max-width: 380px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        .login-box h2 {
            text-align: center;
            margin-bottom: 5px;
            color: #333;
        }
        .login-box p {
            text-align: center;
            color: #888;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
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
        .btn-submit {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-submit:hover {
            background: #5568d3;
        }
        .links {
            text-align: center;
            margin-top: 15px;
            font-size: 13px;
        }
        .links a {
            color: #667eea;
            text-decoration: none;
        }
        .links a:hover {
            text-decoration: underline;
        }
        .bottom-links {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #718096;
        }
        .bottom-links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .bottom-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2><i class="fas fa-tshirt"></i> Muthoni's Laundry</h2>
        <p>Staff Management Portal</p>
        
        <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (!$showReset): ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Enter your username" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" name="login" class="btn-submit">Sign In</button>
        </form>
        
        <div class="links">
            <a href="?reset=1"><i class="fas fa-key" style="font-size:11px;"></i> Forgot Password?</a>
        </div>
        
     <div class="bottom-links">
    <a href="index.php"><i class="fas fa-home"></i> Back to Home</a>
</div>
        
        <?php else: ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="reset_username" placeholder="Enter username" required autofocus>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" placeholder="Min 6 characters" required minlength="6">
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_new_password" placeholder="Repeat password" required>
            </div>
            <button type="submit" name="reset_password" class="btn-submit">Reset Password</button>
        </form>
        <div class="links">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
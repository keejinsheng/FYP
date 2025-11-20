<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// 调试信息
error_log("Reset password accessed with token: " . ($_GET['token'] ?? 'none'));
require_once '../../config/database.php';

$page_title = "Reset Password";

// 验证令牌
if (!isset($_GET['token'])) {
    $_SESSION['error'] = "Invalid reset link.";
    header("Location: forgot_password.php");
    exit;
}

$token = $_GET['token'];

try {
    $pdo = getDBConnection();
    
    // 检查令牌是否有效且未过期
    $stmt = $pdo->prepare("SELECT user_id FROM user WHERE reset_token = ? AND token_expiry > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['error'] = "Invalid or expired reset link.";
        header("Location: forgot_password.php");
        exit;
    }
    
    $user_id = $user['user_id'];
    
} catch (PDOException $e) {
    $_SESSION['error'] = "System error. Please try again.";
    header("Location: forgot_password.php");
    exit;
}

// 处理密码重置
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($new_password) || empty($confirm_password)) {
        $error_message = "Please fill in all fields.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error_message = "Password must be at least 6 characters.";
    } else {
        try {
            // 更新密码并清除重置令牌
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE user SET password_hash = ?, reset_token = NULL, token_expiry = NULL WHERE user_id = ?");
            
            if ($update_stmt->execute([$hashed_password, $user_id])) {
                $_SESSION['status'] = "Password reset successfully! You can now login with your new password.";
                header("Location: login.php");
                exit;
            } else {
                $error_message = "Error resetting password. Please try again.";
            }
        } catch (PDOException $e) {
            $error_message = "System error. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Spice Fusion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../includes/styles.css">
    <style>
        .auth-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .auth-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .submit-btn {
            width: 100%;
            background-color: #28a745;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 1rem;
        }
        .submit-btn:hover {
            background-color: #218838;
        }
        .message {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .message.error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="auth-container">
        <div class="auth-header">
            <h1>Reset Your Password</h1>
            <p>Enter your new password below.</p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required minlength="6">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
            </div>
            <button type="submit" class="submit-btn">Reset Password</button>
        </form>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
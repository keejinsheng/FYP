<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $email = $_POST['email'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // 验证输入
    if (empty($new_password) || empty($confirm_password)) {
        $_SESSION['error'] = "请填写所有字段！";
        header("Location: reset_password.php");
        exit;
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = "两次输入的密码不一致！";
        header("Location: reset_password.php");
        exit;
    } elseif (strlen($new_password) < 6) {
        $_SESSION['error'] = "密码长度至少6位！";
        header("Location: reset_password.php");
        exit;
    } else {
        try {
            $pdo = getDBConnection();
            
            // 检查邮箱是否存在
            $stmt = $pdo->prepare("SELECT * FROM user WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                // 加密密码
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // 更新密码
                $update_stmt = $pdo->prepare("UPDATE user SET password = ? WHERE email = ?");
                $update_stmt->execute([$hashed_password, $email]);
                
                $_SESSION['status'] = "密码重置成功！请使用新密码登录。";
                unset($_SESSION['reset_email']); // 清除重置会话
                header("Location: login.php"); // 重定向到登录页面
                exit;
            } else {
                $_SESSION['error'] = "邮箱未找到！";
                header("Location: reset_password.php");
                exit;
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "系统错误，请稍后重试";
            header("Location: reset_password.php");
            exit;
        }
    }
} else {
    // 如果不是POST请求，重定向到忘记密码页面
    header("Location: forgot_password.php");
    exit;
}
?>
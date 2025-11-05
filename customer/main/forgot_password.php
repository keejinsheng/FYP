<?php
session_start();

// 包含数据库配置
require_once '../../config/database.php';

$page_title = "重置密码";
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        button:hover {
            background-color: #0056b3;
        }
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2 style="text-align: center; margin-bottom: 30px; color: #333;">重置密码</h2>
        
        <?php
        // 显示状态消息
        if(isset($_SESSION['status'])) {
            echo '<div class="alert alert-success">';
            echo '<h5>' . $_SESSION['status'] . '</h5>';
            echo '</div>';
            unset($_SESSION['status']);
        }
        
        if(isset($_SESSION['reset_email'])) {
            $email = $_SESSION['reset_email'];
        } else {
            // 如果没有设置重置邮箱，重定向到忘记密码页面
            header("Location: forgot_password.php");
            exit;
        }
        ?>

        <form action="password_reset_process.php" method="POST">
            <div class="form-group">
                <label for="email">邮箱地址：</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly class="form-control">
            </div>
            <div class="form-group">
                <label for="new_password">新密码：</label>
                <input type="password" id="new_password" name="new_password" required class="form-control" placeholder="请输入新密码">
            </div>
            <div class="form-group">
                <label for="confirm_password">确认新密码：</label>
                <input type="password" id="confirm_password" name="confirm_password" required class="form-control" placeholder="请再次输入新密码">
            </div>
            <div class="form-group">
                <button type="submit" name="reset_password">重置密码</button>
            </div>
        </form>
        
        <?php if(isset($msg)): ?>
            <div class="alert alert-error"><?php echo $msg; ?></div>
        <?php endif; ?>
    </div>
</body>
</html>
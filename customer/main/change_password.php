<?php
require_once '../../config/database.php';
if (!isLoggedIn()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $old = $_POST['old_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    $user_id = $_SESSION['user_id'];

    if ($new != $confirm) {
        $msg = "New passwords do not match!";
    } else {
        try {
            $pdo = getDBConnection();
            // 检查 password_hash 字段，如果没有则检查 password 字段（向后兼容）
            $stmt = $pdo->prepare("SELECT password_hash, password FROM user WHERE user_id=?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            
            // 优先使用 password_hash，如果没有则使用 password
            $stored_password = $result['password_hash'] ?? $result['password'] ?? null;

            if ($stored_password && password_verify($old, $stored_password)) {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                // 优先更新 password_hash，如果没有则更新 password
                if (isset($result['password_hash'])) {
                    $update = $pdo->prepare("UPDATE user SET password_hash=? WHERE user_id=?");
                } else {
                    $update = $pdo->prepare("UPDATE user SET password=? WHERE user_id=?");
                }
                $update->execute([$hashed, $user_id]);
                $msg = "Password updated successfully!";
            } else {
                $msg = "Old password incorrect!";
            }
        } catch (Exception $e) {
            $msg = "Error updating password. Please try again.";
        }
    }
}
?>

<style>
.password-match-message {
    margin-top: 0.5rem;
    font-size: 0.875rem;
    display: none;
}
.password-match-success {
    color: #28a745;
    display: block;
}
.password-match-error {
    color: #dc3545;
    display: block;
}
.form-group {
    margin-bottom: 1rem;
}
.form-group label {
    display: block;
    margin-bottom: 0.5rem;
}
.form-group input {
    width: 100%;
    padding: 0.5rem;
    box-sizing: border-box;
}
</style>

<form method="POST">
  <div class="form-group">
    <label>Current Password</label>
    <input type="password" id="old_password" name="old_password" required>
    <div id="passwordMatchMessage" class="password-match-message"></div>
  </div>

  <div class="form-group">
    <label>New Password</label>
    <input type="password" name="new_password" required>
  </div>

  <div class="form-group">
    <label>Confirm New Password</label>
    <input type="password" name="confirm_password" required>
  </div>

  <button type="submit">Change Password</button>

  <p><?php echo $msg ?? ''; ?></p>
</form>

<script>
let checkPasswordTimeout;

document.getElementById('old_password').addEventListener('input', function() {
    const password = this.value;
    const messageDiv = document.getElementById('passwordMatchMessage');
    const input = this;
    
    // 清除之前的定时器
    clearTimeout(checkPasswordTimeout);
    
    // 如果密码为空，隐藏消息
    if (password.length === 0) {
        messageDiv.style.display = 'none';
        messageDiv.className = 'password-match-message';
        input.style.borderColor = '';
        return;
    }
    
    // 延迟检查，避免频繁请求
    checkPasswordTimeout = setTimeout(function() {
        // 发送 AJAX 请求验证密码
        fetch('verify_current_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'password=' + encodeURIComponent(password)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                messageDiv.style.display = 'block';
                if (data.match) {
                    messageDiv.textContent = '✓ Password match';
                    messageDiv.className = 'password-match-message password-match-success';
                    input.style.borderColor = '#28a745';
                } else {
                    messageDiv.textContent = '✗ Password not match';
                    messageDiv.className = 'password-match-message password-match-error';
                    input.style.borderColor = '#dc3545';
                }
            } else {
                messageDiv.style.display = 'none';
                messageDiv.className = 'password-match-message';
                input.style.borderColor = '';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            messageDiv.style.display = 'none';
            messageDiv.className = 'password-match-message';
            input.style.borderColor = '';
        });
    }, 500); // 延迟 500ms 后检查
});
</script>

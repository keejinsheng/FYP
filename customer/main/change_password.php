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

    // 验证1: 新密码不能与当前密码相同
    if ($old === $new) {
        $msg = "New password cannot be the same as current password!";
    }
    // 验证2: 确认密码是否匹配
    elseif ($new != $confirm) {
        $msg = "New passwords do not match!";
    }
    // 验证3: 新密码至少6位
    elseif (strlen($new) < 6) {
        $msg = "New password must be at least 6 characters long!";
    }
    // 验证4: 新密码复杂度要求（可选）
    elseif (!preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) {
        $msg = "Password must contain at least one letter and one number!";
    }
    else {
 $query = $conn->prepare("SELECT password_hash FROM user WHERE user_id=?");
        $query->bind_param("i", $user_id);
        $query->execute();
        $result = $query->get_result()->fetch_assoc();

        if (password_verify($old, $result['password_hash'])) {
            // 再次验证新密码是否与旧密码相同（通过哈希验证）
            if (password_verify($new, $result['password_hash'])) {
                $msg = "New password cannot be the same as current password!";
            } else {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE user SET password_hash=? WHERE user_id=?");
                $update->bind_param("si", $hashed, $user_id);
                if ($update->execute()) {
                    $msg = "Password updated successfully!";
                } else {
                    $msg = "Error updating password!";
                }
            }
        } else {
            $msg = "Current password incorrect!";
        }
    }
}
?>
<form method="POST" id="passwordForm">
  <label>Current Password</label>
  <input type="password" name="old_password" id="old_password" required>

  <label>New Password</label>
  <input type="password" name="new_password" id="new_password" required>
  <small style="display: block; color: #666; margin-bottom: 10px;">
    • At least 6 characters<br>
    • Contains at least one letter<br>
    • Contains at least one number<br>
    • Cannot be the same as current password
  </small>

  <label>Confirm New Password</label>
  <input type="password" name="confirm_password" id="confirm_password" required>

  <button type="submit" id="submitBtn">Change Password</button>

  <p style="margin-top: 15px; padding: 10px; background: <?php echo isset($msg) && strpos($msg, 'successfully') !== false ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo isset($msg) && strpos($msg, 'successfully') !== false ? '#155724' : '#721c24'; ?>; border-radius: 4px;">
    <?php echo $msg ?? ''; ?>
  </p>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const oldPassword = document.getElementById('old_password');
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('passwordForm');
    let errorMessage = document.getElementById('errorMessage');
    
    // 创建错误信息显示元素
    if (!errorMessage) {
        errorMessage = document.createElement('div');
        errorMessage.id = 'errorMessage';
        errorMessage.style.cssText = 'color: #721c24; background-color: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 4px; display: none;';
        form.insertBefore(errorMessage, submitBtn);
    }
    
    function showError(message) {
        errorMessage.textContent = message;
        errorMessage.style.display = 'block';
        submitBtn.disabled = true;
    }
    
    function hideError() {
        errorMessage.style.display = 'none';
        submitBtn.disabled = false;
    }
    
    function validatePassword() {
        const old = oldPassword.value.trim();
        const newPw = newPassword.value.trim();
        const confirm = confirmPassword.value.trim();
        
        // 清空之前的错误
        hideError();
        
        // 验证新密码是否与当前密码相同
        if (old && newPw && old === newPw) {
            showError('New password cannot be the same as current password!');
            return false;
        }
        
        // 验证密码长度
        if (newPw && newPw.length < 6) {
            showError('Password must be at least 6 characters long!');
            return false;
        }
        
        // 验证密码复杂度
        if (newPw && (!/[A-Za-z]/.test(newPw) || !/\d/.test(newPw))) {
            showError('Password must contain at least one letter and one number!');
            return false;
        }
        
        // 验证确认密码
        if (newPw && confirm && newPw !== confirm) {
            showError('Passwords do not match!');
            return false;
        }
        
        return true;
    }
    
    // 实时验证
    oldPassword.addEventListener('input', validatePassword);
    newPassword.addEventListener('input', validatePassword);
    confirmPassword.addEventListener('input', validatePassword);
    
    // 表单提交前验证
    form.addEventListener('submit', function(e) {
        if (!validatePassword()) {
            e.preventDefault();
        }
    });
});
</script>
<script>
// 实时验证新密码是否与当前密码相同
function checkPasswordMatch() {
    var old = document.getElementById('old_password').value;
    var newPw = document.getElementById('new_password').value;
    
    if (old && newPw && old === newPw) {
        document.getElementById('password_error').style.display = 'block';
        document.getElementById('password_error').innerHTML = 'New password cannot be the same as current password!';
        document.getElementById('submitBtn').disabled = true;
    } else {
        document.getElementById('password_error').style.display = 'none';
        document.getElementById('submitBtn').disabled = false;
    }
}

// 为输入框添加事件监听
document.getElementById('old_password').addEventListener('input', checkPasswordMatch);
document.getElementById('new_password').addEventListener('input', checkPasswordMatch);
</script>
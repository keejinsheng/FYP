<?php
// 显示状态消息
if(isset($_SESSION['status'])) {
    echo '<div class="alert alert-success">';
    echo '<h5>' . $_SESSION['status'] . '</h5>';
    echo '</div>';
    unset($_SESSION['status']);
}

// 添加错误消息显示
if(isset($_SESSION['error'])) {
    echo '<div class="alert alert-error">';
    echo '<h5>' . $_SESSION['error'] . '</h5>';
    echo '</div>';
    unset($_SESSION['error']);
}

if(isset($_SESSION['reset_email'])) {
    $email = $_SESSION['reset_email'];
} else {
    // 如果没有设置重置邮箱，重定向到忘记密码页面
    header("Location: forgot_password.php");
    exit;
}
?>
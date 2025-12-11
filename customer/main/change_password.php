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
        $query = $conn->prepare("SELECT password FROM user WHERE user_id=?");
        $query->bind_param("i", $user_id);
        $query->execute();
        $result = $query->get_result()->fetch_assoc();

        if (password_verify($old, $result['password'])) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE user SET password=? WHERE user_id=?");
            $update->bind_param("si", $hashed, $user_id);
            $update->execute();
            $msg = "Password updated successfully!";
        } else {
            $msg = "Old password incorrect!";
        }
    }
}
?>

<form method="POST">
  <label>Old Password</label>
  <input type="password" name="old_password" required>

  <label>New Password</label>
  <input type="password" name="new_password" required>

  <label>Confirm New Password</label>
  <input type="password" name="confirm_password" required>

  <button type="submit">Change Password</button>

  <p><?php echo $msg ?? ''; ?></p>
</form>

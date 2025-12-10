<?php
require_once '../../config/database.php';

// Check if admin is logged in
if (!isAdmin()) {
    redirect('../auth/login.php');
}

// Superadmin cannot access profile page
if (isSuperAdmin()) {
    redirect('../dashboard/dashboard.php');
}

$pdo = getDBConnection();
$admin_id = getCurrentAdminId();
$success_message = '';
$error_message = '';

// Fetch current admin data
$stmt = $pdo->prepare("SELECT admin_id, username, email, first_name, last_name, role, is_active, created_at FROM admin_user WHERE admin_id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();

if (!$admin) {
    redirect('../auth/login.php');
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $email = sanitize($_POST['email'] ?? '');
        $first_name = sanitize($_POST['first_name'] ?? '');
        $last_name = sanitize($_POST['last_name'] ?? '');
        
        if (empty($email) || empty($first_name)) {
            $error_message = 'Please fill in all required fields';
        } else {
            // Check if email is already taken by another admin
            $stmt = $pdo->prepare("SELECT admin_id FROM admin_user WHERE email = ? AND admin_id != ?");
            $stmt->execute([$email, $admin_id]);
            if ($stmt->fetch()) {
                $error_message = 'Email already exists. Please use a different email.';
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE admin_user SET email = ?, first_name = ?, last_name = ? WHERE admin_id = ?");
                    $stmt->execute([$email, $first_name, $last_name, $admin_id]);
                    
                    // Update session
                    $_SESSION['admin_email'] = $email;
                    $_SESSION['admin_first_name'] = $first_name;
                    $_SESSION['admin_last_name'] = $last_name;
                    
                    // Refresh admin data
                    $stmt = $pdo->prepare("SELECT admin_id, username, email, first_name, last_name, role, is_active, created_at FROM admin_user WHERE admin_id = ?");
                    $stmt->execute([$admin_id]);
                    $admin = $stmt->fetch();
                    
                    $success_message = 'Profile updated successfully!';
                } catch (Exception $e) {
                    $error_message = 'Failed to update profile. Please try again.';
                }
            }
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = 'Please fill in all password fields';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'New passwords do not match';
        } elseif (strlen($new_password) < 6) {
            $error_message = 'Password must be at least 6 characters long';
        } else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password_hash FROM admin_user WHERE admin_id = ?");
            $stmt->execute([$admin_id]);
            $result = $stmt->fetch();
            
            if ($result && password_verify($current_password, $result['password_hash'])) {
                try {
                    $hash = password_hash($new_password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE admin_user SET password_hash = ? WHERE admin_id = ?");
                    $stmt->execute([$hash, $admin_id]);
                    $success_message = 'Password changed successfully!';
                } catch (Exception $e) {
                    $error_message = 'Failed to change password. Please try again.';
                }
            } else {
                $error_message = 'Current password is incorrect';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Spice Fusion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        :root {
            --primary-color: #FF4B2B;
            --secondary-color: #FF416C;
            --background-dark: #1a1a1a;
            --text-light: #ffffff;
            --text-gray: #a0a0a0;
            --card-bg: #2a2a2a;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --gradient-primary: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            --shadow-soft: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-strong: 0 8px 16px rgba(0, 0, 0, 0.2);
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-dark);
            color: var(--text-light);
        }

        .admin-header {
            background: var(--card-bg);
            padding: 1rem 2rem;
            box-shadow: var(--shadow-soft);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo h1 {
            color: var(--primary-color);
            margin: 0;
            font-size: 1.5rem;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .nav-links a {
            color: var(--text-light);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: var(--transition);
        }

        .nav-links a:hover {
            background: var(--primary-color);
        }

        .logout-btn {
            background: var(--danger-color);
            color: var(--text-light);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
        }

        .logout-btn:hover {
            background: #c82333;
        }

        .container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            color: var(--primary-color);
            margin: 0;
        }

        .back-btn {
            background: var(--gradient-primary);
            color: var(--text-light);
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-soft);
        }

        .profile-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow-soft);
            margin-bottom: 2rem;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--primary-color);
        }

        .card-header h2 {
            color: var(--primary-color);
            margin: 0;
            font-size: 1.5rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid var(--success-color);
            color: var(--success-color);
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid var(--danger-color);
            color: var(--danger-color);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-light);
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--text-gray);
            border-radius: 6px;
            background: var(--background-dark);
            color: var(--text-light);
            font-family: 'Inter', sans-serif;
            box-sizing: border-box;
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 75, 43, 0.15);
        }

        .form-group input[readonly] {
            background: rgba(255, 255, 255, 0.05);
            cursor: not-allowed;
            opacity: 0.7;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--text-gray);
            font-weight: 500;
        }

        .info-value {
            color: var(--text-light);
            text-align: right;
        }

        .badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
        }

        .badge.active {
            background: var(--success-color);
            color: #fff;
        }

        .badge.inactive {
            background: var(--danger-color);
            color: #fff;
        }

        .badge.superadmin {
            background: #9c27b0;
            color: #fff;
        }

        .badge.manager {
            background: var(--info-color);
            color: #fff;
        }

        .badge.staff {
            background: var(--warning-color);
            color: #000;
        }

        .submit-btn {
            background: var(--gradient-primary);
            color: var(--text-light);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            font-size: 1rem;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-soft);
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle input {
            padding-right: 2.5rem;
        }

        .password-toggle i {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-gray);
            transition: var(--transition);
        }

        .password-toggle i:hover {
            color: var(--text-light);
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="header-content">
            <div class="logo">
                <h1>Spice Fusion Admin</h1>
            </div>
            <div class="nav-links">
                <a href="../dashboard/dashboard.php">Dashboard</a>
                <a href="../orders/order.php">Orders</a>
                <a href="../products/product.php">Products</a>
                <a href="../members/member.php">Customers</a>
                <a href="profile.php" style="background: var(--primary-color);">Profile</a>
                <a href="../auth/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">My Profile</h1>
            <a href="../dashboard/dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Account Information (Read-only) -->
        <div class="profile-card">
            <div class="card-header">
                <h2><i class="fas fa-info-circle"></i> Account Information</h2>
            </div>
            <div class="info-row">
                <span class="info-label">Admin ID:</span>
                <span class="info-value"><?php echo htmlspecialchars($admin['admin_id']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Username:</span>
                <span class="info-value"><?php echo htmlspecialchars($admin['username']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Role:</span>
                <span class="info-value">
                    <span class="badge <?php echo strtolower(str_replace(' ', '', $admin['role'])); ?>">
                        <?php echo htmlspecialchars($admin['role']); ?>
                    </span>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Account Status:</span>
                <span class="info-value">
                    <span class="badge <?php echo $admin['is_active'] ? 'active' : 'inactive'; ?>">
                        <?php echo $admin['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Member Since:</span>
                <span class="info-value"><?php echo date('F j, Y', strtotime($admin['created_at'])); ?></span>
            </div>
        </div>

        <!-- Profile Update Form -->
        <div class="profile-card">
            <div class="card-header">
                <h2><i class="fas fa-user-edit"></i> Update Profile</h2>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($admin['first_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($admin['last_name']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>
        </div>

        <!-- Change Password Form -->
        <div class="profile-card">
            <div class="card-header">
                <h2><i class="fas fa-lock"></i> Change Password</h2>
            </div>
            <form method="POST" action="" id="passwordForm">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label for="current_password">Current Password *</label>
                    <div class="password-toggle">
                        <input type="password" id="current_password" name="current_password" required>
                        <i class="fas fa-eye" onclick="togglePassword('current_password', this)"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password *</label>
                    <div class="password-toggle">
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                        <i class="fas fa-eye" onclick="togglePassword('new_password', this)"></i>
                    </div>
                    <small style="color: var(--text-gray); font-size: 0.85rem;">Password must be at least 6 characters long</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password *</label>
                    <div class="password-toggle">
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                        <i class="fas fa-eye" onclick="togglePassword('confirm_password', this)"></i>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </form>
        </div>
    </div>

    <script>
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Validate password match on form submit
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
                return false;
            }
        });

        // Auto-hide success/error messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>


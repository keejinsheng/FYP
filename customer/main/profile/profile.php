<?php
require_once '../../../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../login/login.php');
    exit();
}

$pdo = getDBConnection();
$user_id = getCurrentUserId();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $first_name = sanitize($_POST['first_name']);
                $last_name = sanitize($_POST['last_name']);
                $email = sanitize($_POST['email']);
                $phone = sanitize($_POST['phone']);
                
                $stmt = $pdo->prepare("UPDATE user SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE user_id = ?");
                if ($stmt->execute([$first_name, $last_name, $email, $phone, $user_id])) {
                    $success_message = "Profile updated successfully!";
                } else {
                    $error_message = "Error updating profile.";
                }
                break;
                
            case 'add_address':
                $address_line1 = sanitize($_POST['address_line1']);
                $address_line2 = sanitize($_POST['address_line2'] ?? '');
                $city = sanitize($_POST['city']);
                $state = sanitize($_POST['state']);
                $postal_code = sanitize($_POST['postal_code']);
                $country = sanitize($_POST['country'] ?? 'Malaysia');
                $is_default = isset($_POST['is_default']) ? 1 : 0;
                
                // If this is set as default, unset other defaults
                if ($is_default) {
                    $stmt = $pdo->prepare("UPDATE delivery_address SET is_default = 0 WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                }
                
                $stmt = $pdo->prepare("INSERT INTO delivery_address (user_id, address_line1, address_line2, city, state, postal_code, country, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$user_id, $address_line1, $address_line2, $city, $state, $postal_code, $country, $is_default])) {
                    $success_message = "Address added successfully!";
                } else {
                    $error_message = "Error adding address.";
                }
                break;
                
            case 'delete_address':
                $address_id = (int)$_POST['address_id'];
                $stmt = $pdo->prepare("DELETE FROM delivery_address WHERE address_id = ? AND user_id = ?");
                if ($stmt->execute([$address_id, $user_id])) {
                    $success_message = "Address deleted successfully!";
                } else {
                    $error_message = "Error deleting address.";
                }
                break;
            
            case 'update_address':
                $address_id = (int)$_POST['address_id'];
                $address_line1 = sanitize($_POST['address_line1']);
                $address_line2 = sanitize($_POST['address_line2'] ?? '');
                $city = sanitize($_POST['city']);
                $state = sanitize($_POST['state']);
                $postal_code = sanitize($_POST['postal_code']);
                $country = sanitize($_POST['country'] ?? 'Malaysia');
                $is_default = isset($_POST['is_default']) ? 1 : 0;

                if ($is_default) {
                    $stmt = $pdo->prepare("UPDATE delivery_address SET is_default = 0 WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                }

                $stmt = $pdo->prepare("UPDATE delivery_address SET address_line1 = ?, address_line2 = ?, city = ?, state = ?, postal_code = ?, country = ?, is_default = ? WHERE address_id = ? AND user_id = ?");
                if ($stmt->execute([$address_line1, $address_line2, $city, $state, $postal_code, $country, $is_default, $address_id, $user_id])) {
                    $success_message = "Address updated successfully!";
                } else {
                    $error_message = "Error updating address.";
                }
                break;

case 'change_password':
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // 验证输入
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "Please fill in all password fields!";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New password and confirmation password do not match!";
    } elseif (strlen($new_password) < 6) {
        $error_message = "New password must be at least 6 characters long!";
    } else {
        try {
            // 先检查用户是否存在
            $stmt = $pdo->prepare("SELECT * FROM user WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user_data = $stmt->fetch();
            
            if (!$user_data) {
                $error_message = "User does not exist! User ID: " . $user_id;
            } else {
                // 检查password_hash字段是否存在
                if (!isset($user_data['password_hash'])) {
                    $error_message = "Database error! The password_hash field does not exist.";
                } else {
                    // 验证当前密码 - 使用 password_hash 字段
                    if (password_verify($current_password, $user_data['password_hash'])) {
                        // 当前密码正确，更新密码 - 更新 password_hash 字段
                        $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $update_stmt = $pdo->prepare("UPDATE user SET password_hash = ? WHERE user_id = ?");
                        
                        if ($update_stmt->execute([$hashed_new_password, $user_id])) {
                            $success_message = "Password changed successfully!";
                        } else {
                            $error_message = "Password change failed. Please try again later.";
                        }
                    } else {
                        $error_message = "Current password is incorrect!";
                    }
                }
            }
        } catch (PDOException $e) {
            // 显示详细的错误信息用于调试
            $error_message = "Database error: " . $e->getMessage();
        } catch (Exception $e) {
            $error_message = "System error: " . $e->getMessage();
        }
    }
    break;

        }
    }
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM user WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get user's delivery addresses
$stmt = $pdo->prepare("SELECT * FROM delivery_address WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll();

// Get user's recent orders
$stmt = $pdo->prepare("
    SELECT o.*, COUNT(oi.item_id) as item_count 
    FROM `order` o 
    LEFT JOIN order_item oi ON o.order_id = oi.order_id 
    WHERE o.user_id = ? 
    GROUP BY o.order_id 
    ORDER BY o.created_at DESC 
    LIMIT 10
");
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Spice Fusion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../includes/styles.css">
    <link rel="stylesheet" href="profile.css">
</head>
<body>
    <?php include_once __DIR__ . '/../../includes/header.php'; ?>

    <div class="profile-container">
        <div class="profile-header">
            <h1>My Profile</h1>
            <p>Manage your account information and preferences</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="profile-content">
            <!-- Personal Information -->
            <div class="profile-section">
                <h2><i class="fas fa-user"></i> Personal Information</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>
                    <button type="submit" class="submit-btn">Update Profile</button>
                </form>
                   <!-- 添加修改密码部分 -->
<div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #eee;">
    <h3><i class="fas fa-lock"></i> Change Password</h3>
    <form method="POST" id="changePasswordForm">
        <input type="hidden" name="action" value="change_password">
        <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" required>
        </div>
        <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" required minlength="6">
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
        </div>
        <button type="submit" class="submit-btn" style="background-color: #28a745;">Change Password</button>
    </form>
</div>
            </div>

            <!-- Delivery Addresses -->
            <div class="profile-section">
                <h2><i class="fas fa-map-marker-alt"></i> Delivery Addresses</h2>
                <?php if (empty($addresses)): ?>
                    <p style="color: var(--text-gray);">No delivery addresses saved yet.</p>
                <?php else: ?>
                    <?php foreach ($addresses as $address): ?>
                        <div class="address-item">
                            <?php if ($address['is_default']): ?>
                                <span class="default-badge">Default Address</span>
                            <?php endif; ?>
                            <p><?php echo htmlspecialchars($address['address_line1']); ?></p>
                            <?php if ($address['address_line2']): ?>
                                <p><?php echo htmlspecialchars($address['address_line2']); ?></p>
                            <?php endif; ?>
                            <p><?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' ' . $address['postal_code']); ?></p>
                            <p><?php echo htmlspecialchars($address['country']); ?></p>
                            <div class="address-actions">
                                <button class="btn-small btn-edit" onclick='openEditAddressForm(
                                <?php echo json_encode
                                ([
                                    "address_id" => (int)$address["address_id"],
                                    "address_line1" => $address["address_line1"],
                                    "address_line2" => $address["address_line2"],
                                    "city" => $address["city"],
                                    "state" => $address["state"],
                                    "postal_code" => $address["postal_code"],
                                    "country" => $address["country"],
                                    "is_default" => (int)$address["is_default"],
                                ]); ?>)'>Edit</button>
                                <button class="btn-small btn-delete" onclick="deleteAddress(<?php echo $address['address_id']; ?>)">Delete</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <button class="submit-btn" onclick="showAddAddressForm()">Add New Address</button>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="profile-section" style="margin-top: 2rem;">
            <h2><i class="fas fa-shopping-bag"></i> Recent Orders</h2>
            <?php if (empty($recent_orders)): ?>
                <p style="color: var(--text-gray);">No orders yet. <a href="../index/index.php" style="color: var(--primary-color);">Start shopping!</a></p>
            <?php else: ?>
                <?php foreach ($recent_orders as $order): ?>
                    <div class="order-item">
                        <div class="order-header">
                            <span class="order-number">Order #<?php echo htmlspecialchars($order['order_number']); ?></span>
                            <span class="order-status status-<?php echo strtolower($order['order_status']); ?>">
                                <?php echo htmlspecialchars($order['order_status']); ?>
                            </span>
                        </div>
                        <div class="order-details">
                            <p>Date: <?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                            <p>Items: <?php echo $order['item_count']; ?></p>
                            <p>Type: <?php echo htmlspecialchars($order['order_type']); ?></p>
                        </div>
                        <div class="order-total">
                            Total: RM <?php echo number_format($order['total_amount'], 2); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Address Modal (standardized and compact) -->
    <div id="addAddressModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h3 style="margin-bottom: 1rem;">Add New Address</h3>
            <form id="addAddressForm" method="POST">
                <input type="hidden" name="action" value="add_address">
                <div class="form-group">
                    <label for="address_line1">Address Line 1</label>
                    <input type="text" id="address_line1" name="address_line1" required>
                </div>
                <div class="form-group">
                    <label for="address_line2">Address Line 2 (Optional)</label>
                    <input type="text" id="address_line2" name="address_line2">
                </div>
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" required>
                </div>
                <div class="form-group">
                    <label for="state">State</label>
                    <input type="text" id="state" name="state" required>
                </div>
                <div class="form-group">
                    <label for="postal_code">Postal Code</label>
                    <input type="text" id="postal_code" name="postal_code" required>
                </div>
                <div class="form-group">
                    <label for="country">Country</label>
                    <input type="text" id="country" name="country" value="Malaysia" required>
                </div>
                <div class="form-group default-checkbox">
                    <input type="checkbox" name="is_default" id="is_default">
                    <label for="is_default">Set as default address</label>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="submit-btn">Add Address</button>
                    <button type="button" class="btn-cancel" onclick="hideAddAddressForm()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Address Modal -->
    <div id="editAddressModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h3 style="margin-bottom: 1rem;">Edit Address</h3>
            <form id="editAddressForm" method="POST">
                <input type="hidden" name="action" value="update_address">
                <input type="hidden" id="edit_address_id" name="address_id">
                <div class="form-group">
                    <label for="edit_address_line1">Address Line 1</label>
                    <input type="text" id="edit_address_line1" name="address_line1" required>
                </div>
                <div class="form-group">
                    <label for="edit_address_line2">Address Line 2 (Optional)</label>
                    <input type="text" id="edit_address_line2" name="address_line2">
                </div>
                <div class="form-group">
                    <label for="edit_city">City</label>
                    <input type="text" id="edit_city" name="city" required>
                </div>
                <div class="form-group">
                    <label for="edit_state">State</label>
                    <input type="text" id="edit_state" name="state" required>
                </div>
                <div class="form-group">
                    <label for="edit_postal_code">Postal Code</label>
                    <input type="text" id="edit_postal_code" name="postal_code" required>
                </div>
                <div class="form-group">
                    <label for="edit_country">Country</label>
                    <input type="text" id="edit_country" name="country" required>
                </div>
                <div class="form-group default-checkbox">
                    <input type="checkbox" id="edit_is_default" name="is_default">
                    <label for="edit_is_default">Set as default address</label>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="submit-btn">Save Changes</button>
                    <button type="button" class="btn-cancel" onclick="hideEditAddressForm()">Cancel</button>
                </div>
            </form>

        </div>
    </div>

    <?php include_once __DIR__ . '/../../includes/footer.php'; ?>

    <script>
        function openEditAddressForm(address) {
            document.getElementById('edit_address_id').value = address.address_id;
            document.getElementById('edit_address_line1').value = address.address_line1 || '';
            document.getElementById('edit_address_line2').value = address.address_line2 || '';
            document.getElementById('edit_city').value = address.city || '';
            document.getElementById('edit_state').value = address.state || '';
            document.getElementById('edit_postal_code').value = address.postal_code || '';
            document.getElementById('edit_country').value = address.country || 'Malaysia';
            document.getElementById('edit_is_default').checked = !!Number(address.is_default);
            document.getElementById('editAddressModal').style.display = 'flex';
            document.body.classList.add('modal-open');

            // sync button color based on default toggle
            if (typeof syncDefaultButtons === 'function') { syncDefaultButtons(); }
        }

        function deleteAddress(addressId) {
            if (confirm('Are you sure you want to delete this address?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_address">
                    <input type="hidden" name="address_id" value="${addressId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function showAddAddressForm() {
            document.getElementById('addAddressModal').style.display = 'flex';
            document.body.classList.add('modal-open');
        }

        function hideAddAddressForm() {
            document.getElementById('addAddressModal').style.display = 'none';
            document.body.classList.remove('modal-open');
        }

        function hideEditAddressForm() {
            document.getElementById('editAddressModal').style.display = 'none';
            document.body.classList.remove('modal-open');
        }

        // Close modal when clicking outside
        document.getElementById('addAddressModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideAddAddressForm();
            }
        });

        document.getElementById('editAddressModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideEditAddressForm();
            }
        });

        // Turn primary button green when default is checked
        function bindDefaultToggle(formId, checkboxId) {
            const form = document.getElementById(formId);
            const checkbox = document.getElementById(checkboxId);
            if (!form || !checkbox) return;
            const primaryBtn = form.querySelector('.submit-btn');
            const sync = () => {
                if (checkbox.checked) {
                    primaryBtn.classList.add('btn-success');
                } else {
                    primaryBtn.classList.remove('btn-success');
                }
            };
            checkbox.removeEventListener('change', sync);
            checkbox.addEventListener('change', sync);
            sync();
        }

        function syncDefaultButtons() {
            bindDefaultToggle('addAddressForm', 'is_default');
            bindDefaultToggle('editAddressForm', 'edit_is_default');
        }

        // Initial bind
        syncDefaultButtons();

// 密码表单验证
document.getElementById('changePasswordForm')?.addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('New password and confirmation password do not match!');
        return false;
    }
    
    if (newPassword.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        return false;
    }
});
</script>
</body>
</html>

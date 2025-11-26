<?php
require_once '../../../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../login/login.php');
    exit();
}

$pdo = getDBConnection();
$user_id = getCurrentUserId();

// Function to handle avatar upload
function handleAvatarUpload($fileInput, $oldImage = null) {
    if (isset($_FILES[$fileInput]) && $_FILES[$fileInput]['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES[$fileInput]['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed)) {
            return ['error' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.'];
        }
        
        // Check file size (max 5MB)
        if ($_FILES[$fileInput]['size'] > 5 * 1024 * 1024) {
            return ['error' => 'File size exceeds 5MB limit.'];
        }
        
        // Create upload directory if it doesn't exist
        $uploadDir = __DIR__ . '/../../uploads/avatars/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $newName = 'avatar_' . uniqid() . '_' . time() . '.' . $ext;
        $dest = $uploadDir . $newName;
        
        if (move_uploaded_file($_FILES[$fileInput]['tmp_name'], $dest)) {
            // Delete old avatar if it's not the default one
            if ($oldImage && $oldImage !== 'user.jpg' && file_exists($uploadDir . $oldImage)) {
                @unlink($uploadDir . $oldImage);
            }
            return ['success' => $newName];
        } else {
            return ['error' => 'Failed to upload file.'];
        }
    }
    return ['success' => $oldImage];
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $username = sanitize($_POST['username'] ?? '');
                $full_name = sanitize($_POST['full_name'] ?? '');
                $email = sanitize($_POST['email']);
                $phone = sanitize($_POST['phone']);
                $date_of_birth = $_POST['date_of_birth'] ?? null;
                
                // Split full_name into first_name and last_name
                $name_parts = explode(' ', trim($full_name), 2);
                $first_name = $name_parts[0] ?? '';
                $last_name = $name_parts[1] ?? '';
                
                // Get current user data
                $stmt = $pdo->prepare("SELECT username, profile_image FROM user WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $current_user = $stmt->fetch();
                $current_username = $current_user['username'] ?? '';
                $current_avatar = $current_user['profile_image'] ?? 'user.jpg';
                
                // Validate username
                if (empty($username)) {
                    $error_message = "Username cannot be empty!";
                    break;
                }
                
                // Check if username is being changed and if it already exists
                if ($username !== $current_username) {
                    $stmt = $pdo->prepare("SELECT user_id FROM user WHERE username = ? AND user_id != ?");
                    $stmt->execute([$username, $user_id]);
                    if ($stmt->fetch()) {
                        $error_message = "Username already exists. Please choose a different username.";
                        break;
                    }
                }
                
                // Handle avatar upload
                $avatar_result = handleAvatarUpload('avatar', $current_avatar);
                if (isset($avatar_result['error'])) {
                    $error_message = $avatar_result['error'];
                    break;
                }
                $profile_image = $avatar_result['success'];
                
                // Update profile
                $stmt = $pdo->prepare("UPDATE user SET username = ?, first_name = ?, last_name = ?, email = ?, phone = ?, date_of_birth = ?, profile_image = ? WHERE user_id = ?");
                if ($stmt->execute([$username, $first_name, $last_name, $email, $phone, $date_of_birth ?: null, $profile_image, $user_id])) {
                    $success_message = "Profile updated successfully!";
                    // Update session username if changed
                    if ($username !== $current_username) {
                        $_SESSION['username'] = $username;
                    }
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
        $error_message = "Password must be at least 6 characters long";
    } elseif (!preg_match('/[a-zA-Z]/', $new_password)) {
        $error_message = "Password must contain at least one letter";
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $error_message = "Password must contain at least one number";
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
                
                <!-- Avatar Display and Upload -->
                <div class="avatar-section">
                    <?php 
                    $avatar_path = '';
                    $has_avatar = false;
                    if (!empty($user['profile_image']) && $user['profile_image'] !== 'user.jpg') {
                        $avatar_path = '../../uploads/avatars/' . $user['profile_image'];
                        if (file_exists(__DIR__ . '/../../uploads/avatars/' . $user['profile_image'])) {
                            $has_avatar = true;
                        }
                    }
                    ?>
                    <div class="avatar-wrapper">
                        <div class="avatar-preview">
                            <?php if ($has_avatar): ?>
                                <img src="<?php echo $avatar_path; ?>" alt="Profile Avatar" id="avatarPreview" class="avatar-image">
                            <?php else: ?>
                                <div class="avatar-placeholder" id="avatarPreview">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="submit-btn avatar-change-button" onclick="document.getElementById('avatar').click()" style="width: 100%; max-width: 280px; font-weight: 700;">
                            <i class="fas fa-camera"></i> Change Photo
                        </button>
                    </div>
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_profile">
                    <input type="file" id="avatar" name="avatar" accept="image/*" style="display: none;" onchange="previewAvatar(this)">
                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">
                    </div>
                    <button type="submit" class="submit-btn">Update Profile</button>
                </form>
                   <!-- 添加修改密码部分 -->
<div class="change-password-section" style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #eee;">
    <h3 class="change-password-heading"><i class="fas fa-lock"></i> Change Password</h3>
    <form method="POST" id="changePasswordForm">
        <input type="hidden" name="action" value="change_password">
        <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" required>
            <div id="currentPasswordMatchMessage" class="password-match-message" style="display: none; margin-top: 0.5rem; font-size: 0.9rem;"></div>
        </div>
        <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" required minlength="6">
            <div class="password-strength-container">
                <div class="password-strength-bar">
                    <div class="password-strength-segment" id="strength-seg-1"></div>
                    <div class="password-strength-segment" id="strength-seg-2"></div>
                    <div class="password-strength-segment" id="strength-seg-3"></div>
                    <div class="password-strength-segment" id="strength-seg-4"></div>
                </div>
                <div class="password-strength-text empty" id="strength-text"></div>
                <div class="password-requirements">
                    <div class="requirement invalid" id="req-length">
                        <span>✓</span>
                        <span>At least 6 characters</span>
                    </div>
                    <div class="requirement invalid" id="req-letter">
                        <span>✓</span>
                        <span>Contains at least one letter</span>
                    </div>
                    <div class="requirement invalid" id="req-number">
                        <span>✓</span>
                        <span>Contains at least one number</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
            <div id="passwordMatchMessage" class="password-match-message" style="display: none; margin-top: 0.5rem; font-size: 0.9rem;"></div>
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

// ====== Password Strength Indicator ======
(function() {
    const passwordInput = document.getElementById('new_password');
    if (!passwordInput) return;
    
    const strengthSegments = [
        document.getElementById('strength-seg-1'),
        document.getElementById('strength-seg-2'),
        document.getElementById('strength-seg-3'),
        document.getElementById('strength-seg-4')
    ];
    const strengthText = document.getElementById('strength-text');
    const reqLength = document.getElementById('req-length');
    const reqLetter = document.getElementById('req-letter');
    const reqNumber = document.getElementById('req-number');

    function checkPasswordStrength(password) {
        let strength = 0;
        let strengthLevel = 'empty';
        let strengthLabel = '';

        // 检查各项要求
        const hasLength = password.length >= 6;
        const hasLetter = /[a-zA-Z]/.test(password);
        const hasNumber = /[0-9]/.test(password);

        // 更新要求指示器
        if (hasLength) {
            reqLength.classList.remove('invalid');
            reqLength.classList.add('valid');
        } else {
            reqLength.classList.remove('valid');
            reqLength.classList.add('invalid');
        }

        if (hasLetter) {
            reqLetter.classList.remove('invalid');
            reqLetter.classList.add('valid');
        } else {
            reqLetter.classList.remove('valid');
            reqLetter.classList.add('invalid');
        }

        if (hasNumber) {
            reqNumber.classList.remove('invalid');
            reqNumber.classList.add('valid');
        } else {
            reqNumber.classList.remove('valid');
            reqNumber.classList.add('invalid');
        }

        if (password.length === 0) {
            strengthLevel = 'empty';
            strengthLabel = '';
        } else {
            // 长度检查
            if (password.length >= 8) {
                strength += 1;
            } else if (password.length >= 6) {
                strength += 0.5;
            }

            // 包含小写字母
            if (/[a-z]/.test(password)) {
                strength += 1;
            }

            // 包含大写字母
            if (/[A-Z]/.test(password)) {
                strength += 1;
            }

            // 包含数字
            if (/[0-9]/.test(password)) {
                strength += 1;
            }

            // 包含特殊字符
            if (/[^a-zA-Z0-9]/.test(password)) {
                strength += 1;
            }

            // 确定强度等级
            if (strength <= 2) {
                strengthLevel = 'weak';
                strengthLabel = 'weak';
            } else if (strength <= 3.5) {
                strengthLevel = 'medium';
                strengthLabel = 'medium';
            } else {
                strengthLevel = 'strong';
                strengthLabel = 'strong';
            }
        }

        // 更新强度条
        strengthSegments.forEach((seg, index) => {
            seg.classList.remove('weak', 'medium', 'strong');
            if (strengthLevel === 'empty') {
                // 不显示任何颜色
            } else if (strengthLevel === 'weak') {
                if (index === 0) {
                    seg.classList.add('weak');
                }
            } else if (strengthLevel === 'medium') {
                if (index <= 1) {
                    seg.classList.add('medium');
                }
            } else if (strengthLevel === 'strong') {
                seg.classList.add('strong');
            }
        });

        // 更新文字
        strengthText.textContent = strengthLabel;
        strengthText.className = 'password-strength-text ' + strengthLevel;
    }

    passwordInput.addEventListener('input', function() {
        checkPasswordStrength(this.value);
    });

    // 初始化
    checkPasswordStrength(passwordInput.value);
})();

// 实时密码匹配验证
function checkPasswordMatch() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const messageDiv = document.getElementById('passwordMatchMessage');
    const confirmInput = document.getElementById('confirm_password');
    
    if (confirmPassword.length === 0) {
        messageDiv.style.display = 'none';
        confirmInput.style.borderColor = '#ccc';
        return;
    }
    
    if (newPassword.length === 0) {
        messageDiv.style.display = 'none';
        confirmInput.style.borderColor = '#ccc';
        return;
    }
    
    messageDiv.style.display = 'block';
    
    if (newPassword === confirmPassword) {
        messageDiv.textContent = '✓ Passwords match';
        messageDiv.className = 'password-match-message password-match-success';
        confirmInput.style.borderColor = '#28a745';
    } else {
        messageDiv.textContent = '✗ Passwords do not match';
        messageDiv.className = 'password-match-message password-match-error';
        confirmInput.style.borderColor = '#dc3545';
    }
}

// 监听新密码和确认密码输入
document.getElementById('new_password')?.addEventListener('input', checkPasswordMatch);
document.getElementById('confirm_password')?.addEventListener('input', checkPasswordMatch);

// 实时验证当前密码
let checkCurrentPasswordTimeout;
document.getElementById('current_password')?.addEventListener('input', function() {
    const password = this.value;
    const messageDiv = document.getElementById('currentPasswordMatchMessage');
    const input = this;
    
    // 清除之前的定时器
    clearTimeout(checkCurrentPasswordTimeout);
    
    // 如果密码为空，隐藏消息
    if (password.length === 0) {
        messageDiv.style.display = 'none';
        messageDiv.className = 'password-match-message';
        input.style.borderColor = '';
        return;
    }
    
    // 延迟检查，避免频繁请求
    checkCurrentPasswordTimeout = setTimeout(function() {
        // 发送 AJAX 请求验证密码
        fetch('../verify_current_password.php', {
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
                    input.style.borderColor = '#4CAF50';
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

// Avatar preview function
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('avatarPreview');
            // Check if it's an image or placeholder div
            if (preview.tagName === 'IMG') {
                preview.src = e.target.result;
            } else {
                // Replace placeholder with image
                const img = document.createElement('img');
                img.id = 'avatarPreview';
                img.className = 'avatar-image';
                img.src = e.target.result;
                img.alt = 'Profile Avatar';
                preview.parentNode.replaceChild(img, preview);
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>

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
                                <button class="btn-small btn-edit" onclick="editAddress(<?php echo $address['address_id']; ?>)">Edit</button>
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

    <!-- Add Address Modal -->
    <div id="addAddressModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: var(--card-bg); padding: 2rem; border-radius: var(--border-radius); width: 90%; max-width: 500px;">
            <h3 style="margin-bottom: 1rem;">Add New Address</h3>
            <form method="POST">
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
                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <button type="submit" class="submit-btn">Add Address</button>
                    <button type="button" class="submit-btn" onclick="hideAddAddressForm()" style="background: var(--text-gray);">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <?php include_once __DIR__ . '/../../includes/footer.php'; ?>

    <script>
        function editAddress(addressId) {
            // Redirect to checkout page with address editing
            window.location.href = '../checkout/checkout.php?edit_address=' + addressId;
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
            document.getElementById('addAddressModal').style.display = 'block';
        }

        function hideAddAddressForm() {
            document.getElementById('addAddressModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('addAddressModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideAddAddressForm();
            }
        });
    </script>
</body>
</html>

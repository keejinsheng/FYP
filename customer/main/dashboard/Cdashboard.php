<?php
require_once '../../../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../login/login.php');
}

$pdo = getDBConnection();
$user_id = getCurrentUserId();

// Handle address actions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_address') {
        $address_line1 = sanitize($_POST['address_line1'] ?? '');
        $address_line2 = sanitize($_POST['address_line2'] ?? '');
        $city = sanitize($_POST['city'] ?? '');
        $state = sanitize($_POST['state'] ?? '');
        $postal_code = sanitize($_POST['postal_code'] ?? '');
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        
        if (empty($address_line1) || empty($city) || empty($state) || empty($postal_code)) {
            $error_message = 'Please fill in all required fields';
        } else {
            try {
                $pdo->beginTransaction();
                
                // If this is set as default, remove default from other addresses
                if ($is_default) {
                    $stmt = $pdo->prepare("UPDATE delivery_address SET is_default = 0 WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                }
                
                // Add new address
                $stmt = $pdo->prepare("
                    INSERT INTO delivery_address (user_id, address_line1, address_line2, city, state, postal_code, is_default)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$user_id, $address_line1, $address_line2, $city, $state, $postal_code, $is_default]);
                
                $pdo->commit();
                $success_message = 'Address added successfully';
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_message = 'Error adding address';
            }
        }
    }
    
    elseif ($action === 'update_address') {
        $address_id = intval($_POST['address_id'] ?? 0);
        $address_line1 = sanitize($_POST['address_line1'] ?? '');
        $address_line2 = sanitize($_POST['address_line2'] ?? '');
        $city = sanitize($_POST['city'] ?? '');
        $state = sanitize($_POST['state'] ?? '');
        $postal_code = sanitize($_POST['postal_code'] ?? '');
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        
        if ($address_id <= 0 || empty($address_line1) || empty($city) || empty($state) || empty($postal_code)) {
            $error_message = 'Please fill in all required fields';
        } else {
            try {
                $pdo->beginTransaction();
                
                // If this is set as default, remove default from other addresses
                if ($is_default) {
                    $stmt = $pdo->prepare("UPDATE delivery_address SET is_default = 0 WHERE user_id = ? AND address_id != ?");
                    $stmt->execute([$user_id, $address_id]);
                }
                
                // Update address
                $stmt = $pdo->prepare("
                    UPDATE delivery_address 
                    SET address_line1 = ?, address_line2 = ?, city = ?, state = ?, postal_code = ?, is_default = ?
                    WHERE address_id = ? AND user_id = ?
                ");
                $stmt->execute([$address_line1, $address_line2, $city, $state, $postal_code, $is_default, $address_id, $user_id]);
                
                $pdo->commit();
                $success_message = 'Address updated successfully';
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_message = 'Error updating address';
            }
        }
    }
    
    elseif ($action === 'delete_address') {
        $address_id = intval($_POST['address_id'] ?? 0);
        
        if ($address_id <= 0) {
            $error_message = 'Invalid address';
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM delivery_address WHERE address_id = ? AND user_id = ?");
                $stmt->execute([$address_id, $user_id]);
                $success_message = 'Address deleted successfully';
            } catch (Exception $e) {
                $error_message = 'Error deleting address';
            }
        }
    }
    
    elseif ($action === 'set_default') {
        $address_id = intval($_POST['address_id'] ?? 0);
        
        if ($address_id <= 0) {
            $error_message = 'Invalid address';
        } else {
            try {
                $pdo->beginTransaction();
                
                // Remove default from all addresses
                $stmt = $pdo->prepare("UPDATE delivery_address SET is_default = 0 WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Set this address as default
                $stmt = $pdo->prepare("UPDATE delivery_address SET is_default = 1 WHERE address_id = ? AND user_id = ?");
                $stmt->execute([$address_id, $user_id]);
                
                $pdo->commit();
                $success_message = 'Default address updated successfully';
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_message = 'Error updating default address';
            }
        }
    }
}

// Fetch user information
$stmt = $pdo->prepare("SELECT * FROM user WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch user's orders
$stmt = $pdo->prepare("
    SELECT o.*, da.address_line1, da.city, da.state, da.postal_code,
           p.payment_method, p.payment_status,
           (SELECT COUNT(*) FROM order_item WHERE order_id = o.order_id) as item_count
    FROM `order` o
    LEFT JOIN delivery_address da ON o.address_id = da.address_id
    LEFT JOIN payment p ON o.order_id = p.order_id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

// Fetch user's delivery addresses
$stmt = $pdo->prepare("SELECT * FROM delivery_address WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll();

// Handle specific order view
$selected_order = null;
if (isset($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
    $stmt = $pdo->prepare("
        SELECT o.*, da.address_line1, da.address_line2, da.city, da.state, da.postal_code,
               p.payment_method, p.payment_status, p.amount as payment_amount
        FROM `order` o
        LEFT JOIN delivery_address da ON o.address_id = da.address_id
        LEFT JOIN payment p ON o.order_id = p.order_id
        WHERE o.order_id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $selected_order = $stmt->fetch();
    
    if ($selected_order) {
        // Fetch order items
        $stmt = $pdo->prepare("
            SELECT oi.*, p.product_name, p.image
            FROM order_item oi
            JOIN product p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Spice Fusion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #FF4B2B;
            --secondary-color: #FF416C;
            --background-dark: #1a1a1a;
            --text-light: #ffffff;
            --text-gray: #a0a0a0;
            --card-bg: #2a2a2a;
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

        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            margin-top: 4rem;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .dashboard-header h1 {
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
        }

        .sidebar {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            height: fit-content;
        }

        .user-info {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--text-gray);
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--text-light);
        }

        .user-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .user-email {
            color: var(--text-gray);
            font-size: 0.9rem;
        }

        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-menu li {
            margin-bottom: 0.5rem;
        }

        .nav-menu a {
            display: block;
            padding: 0.8rem 1rem;
            color: var(--text-light);
            text-decoration: none;
            border-radius: 6px;
            transition: var(--transition);
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            background: var(--primary-color);
        }

        .main-content {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 2rem;
        }

        .section-header {
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--text-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header h2 {
            color: var(--primary-color);
            margin: 0;
        }

        .add-address-btn {
            background: var(--gradient-primary);
            color: var(--text-light);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .add-address-btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-soft);
        }

        .order-card {
            background: var(--background-dark);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: var(--transition);
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-soft);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .order-number {
            font-weight: 600;
            color: var(--primary-color);
        }

        .order-status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending { background: #ff9800; color: #000; }
        .status-confirmed { background: #2196f3; color: #fff; }
        .status-preparing { background: #9c27b0; color: #fff; }
        .status-ready { background: #4caf50; color: #fff; }
        .status-delivered { background: #4caf50; color: #fff; }
        .status-cancelled { background: #f44336; color: #fff; }

        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .order-detail {
            text-align: center;
        }

        .order-detail-label {
            color: var(--text-gray);
            font-size: 0.8rem;
            margin-bottom: 0.3rem;
        }

        .order-detail-value {
            font-weight: 600;
        }

        .order-actions {
            text-align: right;
        }

        .view-order-btn {
            background: var(--gradient-primary);
            color: var(--text-light);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .view-order-btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-soft);
        }

        .order-items {
            margin-top: 1rem;
        }

        .order-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 50px;
            height: 50px;
            border-radius: 6px;
            object-fit: cover;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 500;
            margin-bottom: 0.2rem;
        }

        .item-quantity {
            color: var(--text-gray);
            font-size: 0.8rem;
        }

        .item-price {
            color: var(--primary-color);
            font-weight: 600;
        }

        .address-card {
            background: var(--background-dark);
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .address-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .address-actions {
            display: flex;
            gap: 0.5rem;
        }

        .address-btn {
            background: var(--primary-color);
            color: var(--text-light);
            border: none;
            padding: 0.3rem 0.6rem;
            border-radius: 15px;
            cursor: pointer;
            font-size: 0.7rem;
            transition: var(--transition);
        }

        .address-btn:hover {
            transform: translateY(-1px);
        }

        .address-btn.delete {
            background: #f44336;
        }

        .default-badge {
            background: var(--primary-color);
            color: var(--text-light);
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            font-size: 0.7rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            border: 1px solid #4caf50;
            color: #4caf50;
        }

        .alert-error {
            background: rgba(255, 75, 43, 0.1);
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 10000;
        }

        .modal-content {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 2rem;
            max-width: 500px;
            margin: 2rem auto;
            position: relative;
            top: 50%;
            transform: translateY(-50%);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h3 {
            color: var(--primary-color);
            margin: 0;
        }

        .close {
            background: none;
            border: none;
            color: var(--text-gray);
            font-size: 1.5rem;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-light);
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--text-gray);
            border-radius: 6px;
            background: var(--background-dark);
            color: var(--text-light);
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: var(--text-light);
        }

        .btn-secondary {
            background: #666;
            color: var(--text-light);
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-soft);
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .order-details {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                margin: 1rem;
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../../includes/header.php'; ?>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>My Dashboard</h1>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <div class="sidebar">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                    </div>
                    <div class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>

                <ul class="nav-menu">
                    <li><a href="#orders" class="active" onclick="showSection('orders')">My Orders</a></li>
                    <li><a href="#addresses" onclick="showSection('addresses')">Delivery Addresses</a></li>
                    <li><a href="../profile/profile.php">Edit Profile</a></li>
                    <li><a href="../index/index.php">Continue Shopping</a></li>
                </ul>
            </div>

            <div class="main-content">
                <?php if ($selected_order): ?>
                    <!-- Order Detail View -->
                    <div class="section-header">
                        <h2>Order #<?php echo htmlspecialchars($selected_order['order_number']); ?></h2>
                    </div>

                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-number">Order #<?php echo htmlspecialchars($selected_order['order_number']); ?></div>
                            <div class="order-status status-<?php echo strtolower($selected_order['order_status']); ?>">
                                <?php echo htmlspecialchars($selected_order['order_status']); ?>
                            </div>
                        </div>

                        <div class="order-details">
                            <div class="order-detail">
                                <div class="order-detail-label">Order Date</div>
                                <div class="order-detail-value"><?php echo date('M d, Y', strtotime($selected_order['created_at'])); ?></div>
                            </div>
                            <div class="order-detail">
                                <div class="order-detail-label">Total Amount</div>
                                <div class="order-detail-value">RM <?php echo number_format($selected_order['total_amount'], 2); ?></div>
                            </div>
                            <div class="order-detail">
                                <div class="order-detail-label">Payment Method</div>
                                <div class="order-detail-value"><?php echo htmlspecialchars($selected_order['payment_method'] ?? 'N/A'); ?></div>
                            </div>
                        </div>

                        <div class="order-items">
                            <h4>Order Items</h4>
                            <?php foreach ($order_items as $item): ?>
                                <div class="order-item">
                                    <img src="../../../food_images/<?php echo htmlspecialchars($item['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="item-image">
                                    <div class="item-details">
                                        <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                        <div class="item-quantity">Qty: <?php echo $item['quantity']; ?></div>
                                    </div>
                                    <div class="item-price">RM <?php echo number_format($item['total_price'], 2); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($selected_order['special_instructions']): ?>
                            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--text-gray);">
                                <strong>Special Instructions:</strong><br>
                                <?php echo htmlspecialchars($selected_order['special_instructions']); ?>
                            </div>
                        <?php endif; ?>

                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--text-gray);">
                            <strong>Delivery Address:</strong><br>
                            <?php echo htmlspecialchars($selected_order['address_line1']); ?><br>
                            <?php if ($selected_order['address_line2']): ?>
                                <?php echo htmlspecialchars($selected_order['address_line2']); ?><br>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($selected_order['city'] . ', ' . $selected_order['state'] . ' ' . $selected_order['postal_code']); ?>
                        </div>

                        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--text-gray); display: flex; gap: 1rem;">
                            <a href="../../api/download_receipt.php?order_id=<?php echo $selected_order['order_id']; ?>" class="view-order-btn" target="_blank" style="background: var(--gradient-primary); color: white; text-decoration: none; padding: 0.75rem 1.5rem; border-radius: 8px; display: inline-flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-download"></i> Download Receipt (PDF)
                            </a>
                        </div>
                    </div>

                    <a href="Cdashboard.php" class="view-order-btn">Back to Orders</a>

                <?php else: ?>
                    <!-- Orders List -->
                    <div id="orders-section">
                        <div class="section-header">
                            <h2>My Orders</h2>
                        </div>

                        <?php if (empty($orders)): ?>
                            <div style="text-align: center; padding: 3rem;">
                                <h3>No orders yet</h3>
                                <p>Start ordering delicious food from Spice Fusion!</p>
                                <a href="../index/index.php" class="view-order-btn">Browse Menu</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <div class="order-card">
                                    <div class="order-header">
                                        <div class="order-number">Order #<?php echo htmlspecialchars($order['order_number']); ?></div>
                                        <div class="order-status status-<?php echo strtolower($order['order_status']); ?>">
                                            <?php echo htmlspecialchars($order['order_status']); ?>
                                        </div>
                                    </div>

                                    <div class="order-details">
                                        <div class="order-detail">
                                            <div class="order-detail-label">Order Date</div>
                                            <div class="order-detail-value"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></div>
                                        </div>
                                        <div class="order-detail">
                                            <div class="order-detail-label">Items</div>
                                            <div class="order-detail-value"><?php echo $order['item_count']; ?> items</div>
                                        </div>
                                        <div class="order-detail">
                                            <div class="order-detail-label">Payment</div>
                                            <div class="order-detail-value"><?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?></div>
                                        </div>
                                        <div class="order-detail">
                                            <div class="order-detail-label">Total</div>
                                            <div class="order-detail-value">RM <?php echo number_format($order['total_amount'], 2); ?></div>
                                        </div>
                                    </div>

                                    <div class="order-actions">
                                        <a href="?order_id=<?php echo $order['order_id']; ?>" class="view-order-btn">View Details</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Delivery Addresses -->
                    <div id="addresses-section" style="display: none;">
                        <div class="section-header">
                            <h2>Delivery Addresses</h2>
                            <button class="add-address-btn" onclick="showAddAddressModal()">
                                <i class="fas fa-plus"></i> Add Address
                            </button>
                        </div>

                        <?php if (empty($addresses)): ?>
                            <div style="text-align: center; padding: 3rem;">
                                <h3>No delivery addresses saved yet</h3>
                                <p>Add your first delivery address to make ordering easier!</p>
                                <button class="add-address-btn" onclick="showAddAddressModal()">
                                    <i class="fas fa-plus"></i> Add Address
                                </button>
                            </div>
                        <?php else: ?>
                            <?php foreach ($addresses as $address): ?>
                                <div class="address-card">
                                    <div class="address-header">
                                        <div>
                                            <?php if ($address['is_default']): ?>
                                                <span class="default-badge">Default</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="address-actions">
                                            <?php if (!$address['is_default']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="set_default">
                                                    <input type="hidden" name="address_id" value="<?php echo $address['address_id']; ?>">
                                                    <button type="submit" class="address-btn">Set Default</button>
                                                </form>
                                            <?php endif; ?>
                                            <button class="address-btn" onclick="showEditAddressModal(<?php echo htmlspecialchars(json_encode($address)); ?>)">Edit</button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this address?')">
                                                <input type="hidden" name="action" value="delete_address">
                                                <input type="hidden" name="address_id" value="<?php echo $address['address_id']; ?>">
                                                <button type="submit" class="address-btn delete">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                    <div>
                                        <?php echo htmlspecialchars($address['address_line1']); ?><br>
                                        <?php if ($address['address_line2']): ?>
                                            <?php echo htmlspecialchars($address['address_line2']); ?><br>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' ' . $address['postal_code']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Address Modal -->
    <div id="addAddressModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Address</h3>
                <button class="close" onclick="closeModal('addAddressModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_address">
                <div class="form-group">
                    <label for="address_line1">Address Line 1 *</label>
                    <input type="text" id="address_line1" name="address_line1" required>
                </div>
                <div class="form-group">
                    <label for="address_line2">Address Line 2</label>
                    <input type="text" id="address_line2" name="address_line2">
                </div>
                <div class="form-group">
                    <label for="city">City *</label>
                    <input type="text" id="city" name="city" required>
                </div>
                <div class="form-group">
                    <label for="state">State *</label>
                    <input type="text" id="state" name="state" required>
                </div>
                <div class="form-group">
                    <label for="postal_code">Postal Code *</label>
                    <input type="text" id="postal_code" name="postal_code" required>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="is_default" name="is_default">
                    <label for="is_default">Set as default address</label>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addAddressModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Address</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Address Modal -->
    <div id="editAddressModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Address</h3>
                <button class="close" onclick="closeModal('editAddressModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_address">
                <input type="hidden" id="edit_address_id" name="address_id">
                <div class="form-group">
                    <label for="edit_address_line1">Address Line 1 *</label>
                    <input type="text" id="edit_address_line1" name="address_line1" required>
                </div>
                <div class="form-group">
                    <label for="edit_address_line2">Address Line 2</label>
                    <input type="text" id="edit_address_line2" name="address_line2">
                </div>
                <div class="form-group">
                    <label for="edit_city">City *</label>
                    <input type="text" id="edit_city" name="city" required>
                </div>
                <div class="form-group">
                    <label for="edit_state">State *</label>
                    <input type="text" id="edit_state" name="state" required>
                </div>
                <div class="form-group">
                    <label for="edit_postal_code">Postal Code *</label>
                    <input type="text" id="edit_postal_code" name="postal_code" required>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="edit_is_default" name="is_default">
                    <label for="edit_is_default">Set as default address</label>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editAddressModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Address</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showSection(sectionName) {
            // Hide all sections
            document.getElementById('orders-section').style.display = 'none';
            document.getElementById('addresses-section').style.display = 'none';
            
            // Show selected section
            document.getElementById(sectionName + '-section').style.display = 'block';
            
            // Update navigation active state
            document.querySelectorAll('.nav-menu a').forEach(link => {
                link.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        function showAddAddressModal() {
            document.getElementById('addAddressModal').style.display = 'block';
        }

        function showEditAddressModal(address) {
            document.getElementById('edit_address_id').value = address.address_id;
            document.getElementById('edit_address_line1').value = address.address_line1;
            document.getElementById('edit_address_line2').value = address.address_line2 || '';
            document.getElementById('edit_city').value = address.city;
            document.getElementById('edit_state').value = address.state;
            document.getElementById('edit_postal_code').value = address.postal_code;
            document.getElementById('edit_is_default').checked = address.is_default == 1;
            document.getElementById('editAddressModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>

    <?php include_once __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html> 
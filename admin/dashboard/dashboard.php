<?php
require_once '../../config/database.php';

// Check if admin is logged in
if (!isAdmin()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();

// Determine superadmin from DB (robust)
$isSuperAdmin = false;
$roleSource = 'db';
$rawRoleFromDb = null;
try {
    $stmtRole = $pdo->prepare('SELECT role FROM admin_user WHERE admin_id = ? AND is_active = 1');
    $stmtRole->execute([$_SESSION['admin_id'] ?? 0]);
    $roleRow = $stmtRole->fetchColumn();
    $rawRoleFromDb = $roleRow;
    if ($roleRow !== false && trim((string)$roleRow) !== '') {
        $_SESSION['admin_role'] = $roleRow; // keep session in sync
        $norm = normalizeRole($roleRow);
        $isSuperAdmin = ($norm === 'superadmin' || strpos($norm, 'super') !== false);
    } else {
        // Fallback to session role
        $roleSource = 'session';
        $sessRole = $_SESSION['admin_role'] ?? '';
        $norm = normalizeRole($sessRole);
        $isSuperAdmin = ($norm === 'superadmin' || strpos($norm, 'super') !== false);
    }
} catch (Exception $e) {
    // If DB fails, use session
    $roleSource = 'session';
    $sessRole = $_SESSION['admin_role'] ?? '';
    $norm = normalizeRole($sessRole);
    $isSuperAdmin = ($norm === 'superadmin' || strpos($norm, 'super') !== false);
}

// Get statistics
$stats = [];

// Total orders
$stmt = $pdo->prepare("SELECT COUNT(*) FROM `order`");
$stmt->execute();
$stats['total_orders'] = $stmt->fetchColumn();

// Pending orders
$stmt = $pdo->prepare("SELECT COUNT(*) FROM `order` WHERE order_status = 'Pending'");
$stmt->execute();
$stats['pending_orders'] = $stmt->fetchColumn();

// Total revenue
$stmt = $pdo->prepare("SELECT SUM(total_amount) FROM `order` WHERE order_status != 'Cancelled'");
$stmt->execute();
$stats['total_revenue'] = $stmt->fetchColumn() ?: 0;

// Total customers
$stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE is_active = 1");
$stmt->execute();
$stats['total_customers'] = $stmt->fetchColumn();

// Total products
$stmt = $pdo->prepare("SELECT COUNT(*) FROM product WHERE is_available = 1");
$stmt->execute();
$stats['total_products'] = $stmt->fetchColumn();

// Recent orders
$stmt = $pdo->prepare("
    SELECT o.*, u.first_name, u.last_name, u.email
    FROM `order` o
    JOIN user u ON o.user_id = u.user_id
    ORDER BY o.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recent_orders = $stmt->fetchAll();

// Today's orders
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM `order` 
    WHERE DATE(created_at) = CURDATE()
");
$stmt->execute();
$today_orders = $stmt->fetchColumn();

// Today's revenue
$stmt = $pdo->prepare("
    SELECT SUM(total_amount) FROM `order` 
    WHERE DATE(created_at) = CURDATE() AND order_status != 'Cancelled'
");
$stmt->execute();
$today_revenue = $stmt->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Spice Fusion</title>
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

        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo h1 {
            color: var(--primary-color);
            margin: 0;
            font-size: 1.5rem;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-name {
            color: var(--text-light);
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

        .dashboard-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-soft);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-strong);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--text-light);
        }

        .stat-icon.orders { background: var(--primary-color); }
        .stat-icon.revenue { background: var(--success-color); }
        .stat-icon.customers { background: var(--info-color); }
        .stat-icon.products { background: var(--warning-color); }

        .stat-title {
            color: var(--text-gray);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-light);
        }

        .stat-change {
            font-size: 0.8rem;
            color: var(--success-color);
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .recent-orders {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--text-gray);
        }

        .section-header h2 {
            color: var(--primary-color);
            margin: 0;
        }

        .view-all-btn {
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

        .view-all-btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-soft);
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-info h4 {
            margin: 0 0 0.5rem;
            color: var(--text-light);
        }

        .order-info p {
            margin: 0;
            color: var(--text-gray);
            font-size: 0.9rem;
        }

        .order-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .view-details-btn {
            background: var(--gradient-primary);
            color: var(--text-light);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .view-details-btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-soft);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: var(--card-bg);
            margin: auto;
            padding: 0;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-strong);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: var(--card-bg);
            z-index: 10;
        }

        .modal-header h2 {
            margin: 0;
            color: var(--primary-color);
        }

        .close-btn {
            color: var(--text-gray);
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: var(--transition);
        }

        .close-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .order-detail-section {
            margin-bottom: 2rem;
        }

        .order-detail-section:last-child {
            margin-bottom: 0;
        }

        .order-detail-section h3 {
            color: var(--primary-color);
            margin: 0 0 1rem 0;
            font-size: 1.1rem;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: var(--text-gray);
            font-weight: 500;
        }

        .detail-value {
            color: var(--text-light);
            text-align: right;
        }

        .items-list {
            margin-top: 1rem;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            margin-bottom: 0.75rem;
        }

        .item-row:last-child {
            margin-bottom: 0;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            color: var(--text-light);
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .item-details {
            color: var(--text-gray);
            font-size: 0.9rem;
        }

        .item-price {
            color: var(--text-light);
            font-weight: 600;
            text-align: right;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: var(--text-gray);
        }

        .error-message {
            text-align: center;
            padding: 2rem;
            color: var(--danger-color);
        }

        .order-status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending { background: var(--warning-color); color: #000; }
        .status-preparing { background: #9c27b0; color: #fff; }
        .status-delivered { background: var(--success-color); color: #fff; }

        .quick-actions {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
        }

        .action-btn {
            display: block;
            width: 90%;
            padding: 1rem;
            margin-bottom: 1rem;
            background: var(--background-dark);
            color: var(--text-light);
            border: 1px solid var(--text-gray);
            border-radius: var(--border-radius);
            text-decoration: none;
            text-align: center;
            transition: var(--transition);
        }

        .action-btn:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .action-btn i {
            margin-right: 0.5rem;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
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
            <div class="admin-info">
                <span class="admin-name">Welcome, <?php echo htmlspecialchars($_SESSION['admin_first_name'] . ' ' . $_SESSION['admin_last_name']); ?></span>
                <?php if (!$isSuperAdmin): ?>
                <a href="../profile/profile.php" class="logout-btn" style="background:#17a2b8;">Profile</a>
                <?php endif; ?>
                <?php if ($isSuperAdmin): ?>
                <a href="../staff/staff.php" class="logout-btn" style="background:#6a1b9a;">Manage Admins</a>
                <?php endif; ?>
                <a href="../auth/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
        <?php if (!empty($_GET['debug'])): ?>
        <div style="max-width:1400px;margin:0.3rem auto 0;color:#bbb;font-size:0.85rem;padding:0 2rem;">
            <?php
            $rawRole = ($roleSource === 'db' && $rawRoleFromDb !== null) ? $rawRoleFromDb : ($_SESSION['admin_role'] ?? '(none)');
            $normRole = strtolower(preg_replace('/[^a-z]/', '', (string)$rawRole));
            echo 'DEBUG • source=' . $roleSource . ' • rawRole=' . htmlspecialchars((string)$rawRole) . ' • norm=' . htmlspecialchars($normRole) . ' • isSuperAdmin=' . ($isSuperAdmin ? 'true' : 'false') . ' • admin_id=' . (int)($_SESSION['admin_id'] ?? 0);
            ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="dashboard-container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Total Orders</div>
                        <div class="stat-value"><?php echo number_format($stats['total_orders']); ?></div>
                        <div class="stat-change">+<?php echo $today_orders; ?> today</div>
                    </div>
                    <div class="stat-icon orders">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Total Revenue</div>
                        <div class="stat-value">RM <?php echo number_format($stats['total_revenue'], 2); ?></div>
                        <div class="stat-change">+RM <?php echo number_format($today_revenue, 2); ?> today</div>
                    </div>
                    <div class="stat-icon revenue">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Total Customers</div>
                        <div class="stat-value"><?php echo number_format($stats['total_customers']); ?></div>
                    </div>
                    <div class="stat-icon customers">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Total Products</div>
                        <div class="stat-value"><?php echo number_format($stats['total_products']); ?></div>
                    </div>
                    <div class="stat-icon products">
                        <i class="fas fa-utensils"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-grid">
            <div class="recent-orders">
                <div class="section-header">
                    <h2>Recent Orders</h2>
                    <a href="../orders/order.php" class="view-all-btn">View All Orders</a>
                </div>

                <?php if (empty($recent_orders)): ?>
                    <p>No orders found.</p>
                <?php else: ?>
                    <?php foreach ($recent_orders as $order): ?>
                        <div class="order-item">
                            <div class="order-info">
                                <h4>Order #<?php echo htmlspecialchars($order['order_number']); ?></h4>
                                <p><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?> • RM <?php echo number_format($order['total_amount'], 2); ?></p>
                            </div>
                            <div class="order-actions">
                                <div class="order-status status-<?php echo strtolower($order['order_status']); ?>">
                                    <?php echo htmlspecialchars($order['order_status']); ?>
                                </div>
                                <button class="view-details-btn" onclick="openOrderModal(<?php echo $order['order_id']; ?>)">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="quick-actions">
                <div class="section-header">
                    <h2>Quick Actions</h2>
                </div>

                <a href="../orders/order.php" class="action-btn">
                    <i class="fas fa-shopping-cart"></i>
                    View Orders
                </a>

                <a href="../products/product.php" class="action-btn">
                    <i class="fas fa-utensils"></i>
                    Manage Products
                </a>

                <a href="../categories/categories.php" class="action-btn">
                    <i class="fas fa-tags"></i>
                    Manage Categories
                </a>

                <a href="../members/member.php" class="action-btn">
                    <i class="fas fa-users"></i>
                    View Customers
                </a>

                <a href="../reviews/reviews.php" class="action-btn">
                    <i class="fas fa-star"></i>
                    View Reviews
                </a>

                <a href="../reports/report.php" class="action-btn">
                    <i class="fas fa-chart-bar"></i>
                    View Reports
                </a>
                <?php if ($isSuperAdmin): ?>
                <a href="../staff/staff.php" class="action-btn">
                    <i class="fas fa-user-shield"></i>
                    Manage Admins
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Order Details</h2>
                <button class="close-btn" onclick="closeOrderModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading order details...
                </div>
            </div>
        </div>
    </div>

    <script>
        function openOrderModal(orderId) {
            const modal = document.getElementById('orderModal');
            const modalBody = document.getElementById('modalBody');
            
            // Show modal with loading state
            modal.classList.add('show');
            modalBody.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading order details...</div>';
            
            // Fetch order details
            fetch(`../orders/get_order_details.php?order_id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        modalBody.innerHTML = `<div class="error-message">${data.error}</div>`;
                        return;
                    }
                    
                    // Populate modal with order details
                    modalBody.innerHTML = buildOrderDetailsHTML(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = '<div class="error-message">Failed to load order details. Please try again.</div>';
                });
        }

        function closeOrderModal() {
            const modal = document.getElementById('orderModal');
            modal.classList.remove('show');
        }

        function buildOrderDetailsHTML(data) {
            const order = data.order;
            const items = data.items || [];
            
            // Format date
            const orderDate = new Date(order.created_at).toLocaleString();
            
            // Calculate item count
            const totalItems = items.reduce((sum, item) => sum + parseInt(item.quantity), 0);
            
            let html = `
                <!-- Order Information -->
                <div class="order-detail-section">
                    <h3><i class="fas fa-info-circle"></i> Order Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Order Number:</span>
                        <span class="detail-value">${escapeHtml(order.order_number)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Order Date:</span>
                        <span class="detail-value">${escapeHtml(orderDate)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Order Status:</span>
                        <span class="detail-value">
                            <span class="order-status status-${order.order_status.toLowerCase()}">
                                ${escapeHtml(order.order_status)}
                            </span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Order Type:</span>
                        <span class="detail-value">${escapeHtml(order.order_type)}</span>
                    </div>
                </div>

                <!-- Customer Information -->
                <div class="order-detail-section">
                    <h3><i class="fas fa-user"></i> Customer Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Name:</span>
                        <span class="detail-value">${escapeHtml(order.first_name + ' ' + order.last_name)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value">${escapeHtml(order.email || 'N/A')}</span>
                    </div>
                    ${order.phone ? `
                    <div class="detail-row">
                        <span class="detail-label">Phone:</span>
                        <span class="detail-value">${escapeHtml(order.phone)}</span>
                    </div>
                    ` : ''}
                </div>

                <!-- Ordered Items -->
                <div class="order-detail-section">
                    <h3><i class="fas fa-shopping-bag"></i> Ordered Items (${totalItems} ${totalItems === 1 ? 'item' : 'items'})</h3>
                    <div class="items-list">
            `;
            
            if (items.length === 0) {
                html += '<p style="color: var(--text-gray); text-align: center; padding: 1rem;">No items found in this order.</p>';
            } else {
                items.forEach(item => {
                    html += `
                        <div class="item-row">
                            <div class="item-info">
                                <div class="item-name">${escapeHtml(item.product_name)}</div>
                                <div class="item-details">
                                    Quantity: ${item.quantity} × RM ${parseFloat(item.unit_price).toFixed(2)}
                                </div>
                            </div>
                            <div class="item-price">
                                RM ${parseFloat(item.total_price).toFixed(2)}
                            </div>
                        </div>
                    `;
                });
            }
            
            html += `
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="order-detail-section">
                    <h3><i class="fas fa-credit-card"></i> Payment Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Payment Method:</span>
                        <span class="detail-value">${escapeHtml(order.payment_method || 'N/A')}</span>
                    </div>
                    ${order.payment_status ? `
                    <div class="detail-row">
                        <span class="detail-label">Payment Status:</span>
                        <span class="detail-value">${escapeHtml(order.payment_status)}</span>
                    </div>
                    ` : ''}
                </div>

                <!-- Order Summary -->
                <div class="order-detail-section">
                    <h3><i class="fas fa-calculator"></i> Order Summary</h3>
                    <div class="detail-row">
                        <span class="detail-label">Subtotal:</span>
                        <span class="detail-value">RM ${parseFloat(order.subtotal).toFixed(2)}</span>
                    </div>
                    ${parseFloat(order.tax_amount) > 0 ? `
                    <div class="detail-row">
                        <span class="detail-label">Tax:</span>
                        <span class="detail-value">RM ${parseFloat(order.tax_amount).toFixed(2)}</span>
                    </div>
                    ` : ''}
                    ${parseFloat(order.delivery_fee) > 0 ? `
                    <div class="detail-row">
                        <span class="detail-label">Delivery Fee:</span>
                        <span class="detail-value">RM ${parseFloat(order.delivery_fee).toFixed(2)}</span>
                    </div>
                    ` : ''}
                    <div class="detail-row" style="border-top: 2px solid var(--primary-color); padding-top: 1rem; margin-top: 0.5rem;">
                        <span class="detail-label" style="font-weight: 700; font-size: 1.1rem;">Total Amount:</span>
                        <span class="detail-value" style="font-weight: 700; font-size: 1.1rem; color: var(--primary-color);">RM ${parseFloat(order.total_amount).toFixed(2)}</span>
                    </div>
                </div>
            `;
            
            if (order.special_instructions) {
                html += `
                <div class="order-detail-section">
                    <h3><i class="fas fa-sticky-note"></i> Special Instructions</h3>
                    <p style="color: var(--text-gray); padding: 0.5rem 0;">${escapeHtml(order.special_instructions)}</p>
                </div>
                `;
            }
            
            return html;
        }

        function escapeHtml(text) {
            if (text == null) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('orderModal');
            if (event.target === modal) {
                closeOrderModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeOrderModal();
            }
        });
    </script>
</body>
</html> 
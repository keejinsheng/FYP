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
        $norm = strtolower(preg_replace('/[^a-z]/', '', (string)$roleRow));
        $isSuperAdmin = ($norm === 'superadmin' || strpos($norm, 'super') !== false);
    } else {
        // Fallback to session role
        $roleSource = 'session';
        $sessRole = $_SESSION['admin_role'] ?? '';
        $norm = strtolower(preg_replace('/[^a-z]/', '', (string)$sessRole));
        $isSuperAdmin = ($norm === 'superadmin' || strpos($norm, 'super') !== false);
    }
} catch (Exception $e) {
    // If DB fails, use session
    $roleSource = 'session';
    $sessRole = $_SESSION['admin_role'] ?? '';
    $norm = strtolower(preg_replace('/[^a-z]/', '', (string)$sessRole));
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

        .order-status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending { background: var(--warning-color); color: #000; }
        .status-confirmed { background: var(--info-color); color: #fff; }
        .status-preparing { background: #9c27b0; color: #fff; }
        .status-ready { background: var(--success-color); color: #fff; }
        .status-delivered { background: var(--success-color); color: #fff; }
        .status-cancelled { background: var(--danger-color); color: #fff; }

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
                            <div class="order-status status-<?php echo strtolower($order['order_status']); ?>">
                                <?php echo htmlspecialchars($order['order_status']); ?>
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
                    Manage Orders
                </a>

                <a href="../products/product.php" class="action-btn">
                    <i class="fas fa-utensils"></i>
                    Manage Products
                </a>

                <a href="../categories/categories.php" class="action-btn">
                    <i class="fas fa-tags"></i>
                    View Categories
                </a>

                <a href="../members/member.php" class="action-btn">
                    <i class="fas fa-users"></i>
                    View Customers
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
</body>
</html> 
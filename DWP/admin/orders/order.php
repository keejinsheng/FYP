<?php
require_once '../../config/database.php';

// 检查是否已登录为管理员
if (!isAdmin()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();

// 查询最近20个订单
$stmt = $pdo->prepare('
    SELECT o.*, u.first_name, u.last_name, u.email
    FROM `order` o
    JOIN user u ON o.user_id = u.user_id
    ORDER BY o.created_at DESC
    LIMIT 20
');
$stmt->execute();
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Spice Fusion Admin</title>
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
        .container {
            max-width: 1200px;
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
        }
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-soft);
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-soft);
        }
        .orders-table th, .orders-table td {
            padding: 1rem;
            text-align: left;
        }
        .orders-table th {
            background: var(--background-dark);
            color: var(--primary-color);
        }
        .orders-table tr:not(:last-child) {
            border-bottom: 1px solid var(--text-gray);
        }
        .order-status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-block;
        }
        .status-pending { background: var(--warning-color); color: #000; }
        .status-confirmed { background: var(--info-color); color: #fff; }
        .status-preparing { background: #9c27b0; color: #fff; }
        .status-ready { background: var(--success-color); color: #fff; }
        .status-delivered { background: var(--success-color); color: #fff; }
        .status-cancelled { background: var(--danger-color); color: #fff; }
        @media (max-width: 768px) {
            .container { padding: 0 0.5rem; }
            .orders-table th, .orders-table td { padding: 0.5rem; font-size: 0.95rem; }
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
                <a href="../products/product.php">Products</a>
                <a href="order.php" style="background: var(--primary-color);">Orders</a>
                <a href="../members/member.php">Customers</a>
                <a href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Order Management</h1>
            <a href="../dashboard/dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        <?php if (empty($orders)): ?>
            <p style="color: var(--text-gray);">暂无订单。</p>
        <?php else: ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Total (RM)</th>
                        <th>Order Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                            <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?><br><span style="color: var(--text-gray); font-size: 0.9em;">(<?php echo htmlspecialchars($order['email']); ?>)</span></td>
                            <td><span class="order-status status-<?php echo strtolower($order['order_status']); ?>"><?php echo htmlspecialchars($order['order_status']); ?></span></td>
                            <td><?php echo number_format($order['total_amount'], 2); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html> 
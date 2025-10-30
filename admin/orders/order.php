<?php
require_once '../../config/database.php';

// 检查是否已登录为管理员
if (!isAdmin()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = trim($_POST['order_status'] ?? '');
    // Allowed statuses (refined)
    $allowedStatuses = ['Pending', 'Confirmed', 'Delivered', 'Preparing', 'Cancelled'];
    if ($orderId > 0 && in_array($newStatus, $allowedStatuses, true)) {
        $stmt = $pdo->prepare('UPDATE `order` SET order_status = ?, updated_at = NOW() WHERE order_id = ?');
        $stmt->execute([$newStatus, $orderId]);
        // PRG pattern to avoid resubmission with success indicator
        redirect('order.php?updated=1');
    }
}

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
        /* Toast */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid #28a745;
            color: #28a745;
            padding: 0.85rem 1rem;
            border-radius: 10px;
            box-shadow: var(--shadow-strong);
            z-index: 2000;
            backdrop-filter: blur(6px);
            animation: slidein .25s ease-out;
        }
        @keyframes slidein { from { transform: translateY(-10px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
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
        /* Pretty status select */
        .status-select {
            appearance: none;
            -webkit-appearance: none;
            background: #1f1f1f url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 20 20" fill="%23a0a0a0"><path d="M5.516 7.548l4.484 4.487 4.484-4.487 1.516 1.516-6 6-6-6z"/></svg>') no-repeat right .55rem center/16px;
            color: #fff;
            border: 1px solid #555;
            border-radius: 8px;
            padding: .45rem 2rem .45rem .6rem;
            cursor: pointer;
            transition: border .2s, box-shadow .2s;
            min-width: 150px;
        }
        .status-select:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(255,75,43,.15); }
        .status-select.pending { border-color: var(--warning-color); box-shadow: inset 0 0 0 1px var(--warning-color); }
        .status-select.confirmed { border-color: var(--info-color); box-shadow: inset 0 0 0 1px var(--info-color); }
        .status-select.preparing { border-color: #9c27b0; box-shadow: inset 0 0 0 1px #9c27b0; }
        .status-select.delivered { border-color: var(--success-color); box-shadow: inset 0 0 0 1px var(--success-color); }
        .status-select.cancelled { border-color: var(--danger-color); box-shadow: inset 0 0 0 1px var(--danger-color); }
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
        <?php if (!empty($_GET['updated'])): ?>
            <div class="toast" id="statusToast">Order status updated successfully.</div>
            <script>
                setTimeout(function(){
                    var t = document.getElementById('statusToast');
                    if (t) { t.style.transition = 'opacity .25s ease'; t.style.opacity = '0'; setTimeout(function(){ t.remove(); }, 300); }
                }, 2200);
                // Colorize status selects
                function paintSelect(el){
                    var val = (el.value || '').toLowerCase();
                    el.classList.remove('pending','confirmed','preparing','delivered','cancelled');
                    if (val) el.classList.add(val);
                }
                document.querySelectorAll('[data-status-select]').forEach(function(el){
                    paintSelect(el);
                    el.addEventListener('change', function(){ paintSelect(el); });
                });
            </script>
        <?php endif; ?>
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
                        <td>
                            <form method="POST" action="" style="display:flex; align-items:center; gap:.5rem;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="order_id" value="<?php echo (int)$order['order_id']; ?>">
                                <select name="order_status" class="status-select" data-status-select>
                                    <?php
                                        $statuses = ['Pending','Confirmed','Delivered','Preparing','Cancelled'];
                                        foreach ($statuses as $status) {
                                            $selected = $status === $order['order_status'] ? 'selected' : '';
                                            echo '<option value="' . htmlspecialchars($status) . '" ' . $selected . '>' . htmlspecialchars($status) . '</option>';
                                        }
                                    ?>
                                </select>
                                <button type="submit" class="update-btn" style="background: var(--gradient-primary); color:#fff; border:none; border-radius:6px; padding:.4rem .7rem; cursor:pointer;">
                                    Update
                                </button>
                            </form>
                        </td>
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
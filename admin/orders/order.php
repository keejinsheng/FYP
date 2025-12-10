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
        /* View Details Button */
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
            white-space: nowrap;
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
        /* Search Box */
        .search-container {
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            align-items: center;
        }
        .search-box {
            position: relative;
            max-width: 350px;
            width: 100%;
        }
        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            background: var(--card-bg);
            border: 1px solid var(--text-gray);
            border-radius: var(--border-radius);
            color: var(--text-light);
            font-size: 0.95rem;
            transition: var(--transition);
        }
        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 75, 43, 0.15);
        }
        .search-box i {
            position: absolute;
            left: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-gray);
        }
        .search-box input::placeholder {
            color: var(--text-gray);
        }
        .no-results {
            text-align: center;
            padding: 2rem;
            color: var(--text-gray);
            display: none;
        }
        .no-results.show {
            display: block;
        }
        @media (max-width: 768px) {
            .container { padding: 0 0.5rem; }
            .orders-table th, .orders-table td { padding: 0.5rem; font-size: 0.95rem; }
            .search-container {
                justify-content: center;
            }
            .search-box {
                max-width: 100%;
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
            <p style="color: var(--text-gray);">No orders at present.</p>
        <?php else: ?>
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="orderSearch" placeholder="Search by ID or Name..." onkeyup="filterOrders()">
                </div>
            </div>
            <div class="no-results" id="noResults">No orders found matching your search.</div>
            <table class="orders-table" id="ordersTable">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Total (RM)</th>
                        <th>Order Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr data-order-id="<?php echo (int)$order['order_id']; ?>" 
                        data-order-number="<?php echo htmlspecialchars(strtolower($order['order_number'])); ?>" 
<<<<<<< HEAD
                        data-customer-name="<?php echo htmlspecialchars(strtolower($order['first_name'] . ' ' . $order['last_name'])); ?>"
                        data-order-email="<?php echo htmlspecialchars(strtolower($order['email'])); ?>">
=======
                        data-customer-name="<?php echo htmlspecialchars(strtolower($order['first_name'] . ' ' . $order['last_name'])); ?>">
>>>>>>> 24875fb43610183a3f4ce4d3603736e9d0186736
                            <td><?php echo (int)$order['order_id']; ?></td>
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
                            <td>
                                <button class="view-details-btn" onclick="openOrderModal(<?php echo $order['order_id']; ?>)">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
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
            fetch(`get_order_details.php?order_id=${orderId}`)
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

        // Search functionality
        function filterOrders() {
            const input = document.getElementById('orderSearch');
<<<<<<< HEAD
            const filter = input.value.toLowerCase().trim();
=======
            const filter = input.value.toLowerCase();
>>>>>>> 24875fb43610183a3f4ce4d3603736e9d0186736
            const table = document.getElementById('ordersTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            const noResults = document.getElementById('noResults');
            let found = false;

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
<<<<<<< HEAD
                const orderId = (row.getAttribute('data-order-id') || '').toLowerCase();
                const orderNumber = row.getAttribute('data-order-number') || '';
                const customerName = row.getAttribute('data-customer-name') || '';
                const orderEmail = row.getAttribute('data-order-email') || '';
                const orderIdText = `order${orderId}`;
                const hashIdText = `#${orderId}`;
                const orderIdWithSpace = `order ${orderId}`;
                const orderIdLabel = `order id ${orderId}`;
                const plainIdLabel = `id ${orderId}`;
                const idCompact = `id${orderId}`;
                
                const searchText = [
                    orderId,
                    orderNumber,
                    customerName,
                    orderEmail,
                    orderIdText,
                    hashIdText,
                    orderIdWithSpace,
                    orderIdLabel,
                    plainIdLabel,
                    idCompact
                ].join(' ');
=======
                const orderId = row.getAttribute('data-order-id') || '';
                const orderNumber = row.getAttribute('data-order-number') || '';
                const customerName = row.getAttribute('data-customer-name') || '';
                
                const searchText = orderId + ' ' + orderNumber + ' ' + customerName;
>>>>>>> 24875fb43610183a3f4ce4d3603736e9d0186736
                
                if (searchText.includes(filter)) {
                    row.style.display = '';
                    found = true;
                } else {
                    row.style.display = 'none';
                }
            }

            // Show/hide no results message
            if (found || filter === '') {
                noResults.classList.remove('show');
            } else {
                noResults.classList.add('show');
            }
        }
    </script>
</body>
</html> 
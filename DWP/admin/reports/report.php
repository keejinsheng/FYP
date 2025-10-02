<?php
require_once '../../config/database.php';

// 检查是否已登录为管理员
if (!isAdmin()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();

// 统计数据
// 总订单数
$stmt = $pdo->prepare("SELECT COUNT(*) FROM `order`");
$stmt->execute();
$total_orders = $stmt->fetchColumn();
// 总收入
$stmt = $pdo->prepare("SELECT SUM(total_amount) FROM `order` WHERE order_status != 'Cancelled'");
$stmt->execute();
$total_revenue = $stmt->fetchColumn() ?: 0;
// 今日订单数
$stmt = $pdo->prepare("SELECT COUNT(*) FROM `order` WHERE DATE(created_at) = CURDATE()");
$stmt->execute();
$today_orders = $stmt->fetchColumn();
// 今日收入
$stmt = $pdo->prepare("SELECT SUM(total_amount) FROM `order` WHERE DATE(created_at) = CURDATE() AND order_status != 'Cancelled'");
$stmt->execute();
$today_revenue = $stmt->fetchColumn() ?: 0;

// 查询所有分类
$stmt = $pdo->prepare("SELECT * FROM category WHERE is_active = 1 ORDER BY category_name");
$stmt->execute();
$categories = $stmt->fetchAll();

// 查询每个分类下产品的今日销量、销售额、库存
$category_products = [];
foreach ($categories as $cat) {
    $stmt = $pdo->prepare("
        SELECT p.product_id, p.product_name, p.stock_quantity,
            IFNULL(SUM(oi.quantity), 0) as sold_qty,
            IFNULL(SUM(oi.quantity * oi.unit_price), 0) as sold_amount
        FROM product p
        LEFT JOIN order_item oi ON p.product_id = oi.product_id
        LEFT JOIN `order` o ON oi.order_id = o.order_id AND DATE(o.created_at) = CURDATE() AND o.order_status != 'Cancelled'
        WHERE p.category_id = ?
        GROUP BY p.product_id
        ORDER BY p.product_name
    ");
    $stmt->execute([$cat['category_id']]);
    $category_products[$cat['category_id']] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Spice Fusion Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            max-width: 1100px;
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
        }
        .stat-card {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-soft);
            text-align: center;
        }
        .stat-title {
            color: var(--text-gray);
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-light);
        }
        .stat-desc {
            color: var(--primary-color);
            font-size: 0.95rem;
            margin-top: 0.5rem;
        }
        .category-section { margin-top: 3rem; }
        .category-title { color: var(--primary-color); font-size: 1.3rem; margin-bottom: 1rem; }
        .products-table { width: 100%; border-collapse: collapse; background: var(--card-bg); border-radius: var(--border-radius); overflow: hidden; box-shadow: var(--shadow-soft); margin-bottom: 2rem; }
        .products-table th, .products-table td { padding: 0.8rem; text-align: left; }
        .products-table th { background: var(--background-dark); color: var(--primary-color); }
        .products-table tr:not(:last-child) { border-bottom: 1px solid var(--text-gray); }
        .chart-container { background: var(--card-bg); border-radius: var(--border-radius); padding: 1.5rem; margin-bottom: 2rem; box-shadow: var(--shadow-soft); }
        @media (max-width: 768px) {
            .container { padding: 0 0.5rem; }
            .stats-grid { grid-template-columns: 1fr; }
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
                <a href="../orders/order.php">Orders</a>
                <a href="../members/member.php">Customers</a>
                <a href="report.php" style="background: var(--primary-color);">Reports</a>
                <a href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Reports</h1>
            <a href="../dashboard/dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Total Orders</div>
                <div class="stat-value"><?php echo number_format($total_orders); ?></div>
                <div class="stat-desc">All time</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Total Revenue</div>
                <div class="stat-value">RM <?php echo number_format($total_revenue, 2); ?></div>
                <div class="stat-desc">All time</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Today's Orders</div>
                <div class="stat-value"><?php echo number_format($today_orders); ?></div>
                <div class="stat-desc">Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Today's Revenue</div>
                <div class="stat-value">RM <?php echo number_format($today_revenue, 2); ?></div>
                <div class="stat-desc">Today</div>
            </div>
        </div>
        <?php foreach ($categories as $cat): ?>
            <div class="category-section">
                <div class="category-title"><?php echo htmlspecialchars($cat['category_name']); ?> - Today's Product Sales</div>
                <div class="chart-container">
                    <canvas id="chart_<?php echo $cat['category_id']; ?>" height="80"></canvas>
                </div>
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Sold Qty</th>
                            <th>Sales (RM)</th>
                            <th>Stock Left</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($category_products[$cat['category_id']] as $prod): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($prod['product_name']); ?></td>
                                <td><?php echo $prod['sold_qty']; ?></td>
                                <td>RM <?php echo number_format($prod['sold_amount'], 2); ?></td>
                                <td><?php echo $prod['stock_quantity']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
    <script>
    <?php foreach ($categories as $cat): 
        $labels = [];
        $sales = [];
        $qtys = [];
        foreach ($category_products[$cat['category_id']] as $prod) {
            $labels[] = addslashes($prod['product_name']);
            $sales[] = (float)$prod['sold_amount'];
            $qtys[] = (int)$prod['sold_qty'];
        }
    ?>
    new Chart(document.getElementById('chart_<?php echo $cat['category_id']; ?>').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [
                {
                    label: 'Sales (RM)',
                    data: <?php echo json_encode($sales); ?>,
                    backgroundColor: function(context) {
                        const ctx = context.chart.ctx;
                        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                        gradient.addColorStop(0, 'rgba(255,75,43,0.9)');
                        gradient.addColorStop(1, 'rgba(255,65,108,0.5)');
                        return gradient;
                    },
                    borderRadius: 10,
                    borderSkipped: false,
                    barPercentage: 0.6,
                    categoryPercentage: 0.7,
                    borderWidth: 2,
                    borderColor: 'rgba(255,75,43,1)',
                    hoverBackgroundColor: 'rgba(255,75,43,1)',
                    shadowOffsetX: 2,
                    shadowOffsetY: 2,
                    shadowBlur: 8,
                    shadowColor: 'rgba(255,75,43,0.2)'
                },
                {
                    label: 'Sold Qty',
                    data: <?php echo json_encode($qtys); ?>,
                    backgroundColor: function(context) {
                        const ctx = context.chart.ctx;
                        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                        gradient.addColorStop(0, 'rgba(23,162,184,0.9)');
                        gradient.addColorStop(1, 'rgba(23,162,184,0.3)');
                        return gradient;
                    },
                    borderRadius: 10,
                    borderSkipped: false,
                    barPercentage: 0.6,
                    categoryPercentage: 0.7,
                    borderWidth: 2,
                    borderColor: 'rgba(23,162,184,1)',
                    hoverBackgroundColor: 'rgba(23,162,184,1)',
                    shadowOffsetX: 2,
                    shadowOffsetY: 2,
                    shadowBlur: 8,
                    shadowColor: 'rgba(23,162,184,0.2)'
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: '#fff',
                        font: { weight: 'bold', size: 15 }
                    }
                },
                title: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(30,30,30,0.95)',
                    titleColor: '#FF4B2B',
                    bodyColor: '#fff',
                    borderColor: '#FF4B2B',
                    borderWidth: 1,
                    padding: 12,
                    caretSize: 8,
                    cornerRadius: 8,
                    titleFont: { weight: 'bold', size: 15 },
                    bodyFont: { size: 14 }
                }
            },
            scales: {
                x: {
                    ticks: {
                        color: '#fff',
                        font: { weight: 'bold', size: 13 }
                    },
                    grid: { color: 'rgba(255,255,255,0.08)' }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#fff',
                        font: { weight: 'bold', size: 13 }
                    },
                    grid: { color: 'rgba(255,255,255,0.12)' }
                }
            },
            animation: {
                duration: 1200,
                easing: 'easeOutQuart'
            }
        }
    });
    <?php endforeach; ?>
    </script>
</body>
</html> 
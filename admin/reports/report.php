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
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
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
            <div style="display:flex;gap:0.5rem;align-items:center;">
                <button id="btnExportPdf" class="back-btn" style="background:#2e7d32;"><i class="fas fa-file-pdf"></i> Export Charts PDF</button>
                <button id="btnExportTablesPdf" class="back-btn" style="background:#6a1b9a;"><i class="fas fa-table"></i> Export Tables PDF</button>
                <button id="btnExportExcel" class="back-btn" style="background:#1e88e5;"><i class="fas fa-file-excel"></i> Export Tables Excel</button>
                <a href="../dashboard/dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
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
    document.addEventListener('DOMContentLoaded', function () {
        const btnPdf = document.getElementById('btnExportPdf');
        const btnTablesPdf = document.getElementById('btnExportTablesPdf');
        const btnExcel = document.getElementById('btnExportExcel');
        if (btnPdf) {
            btnPdf.addEventListener('click', async function() {
                const { jsPDF } = window.jspdf || {};
                if (!jsPDF) return alert('PDF library failed to load.');
                const pdf = new jsPDF({ orientation: 'p', unit: 'pt', format: 'a4' });
                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();
                const margin = 36; // 0.5in margins
                const headerH = 60;
                const footerH = 28;
                let first = true;
                const dateStr = new Date().toLocaleString();
                const charts = Array.from(document.querySelectorAll('canvas[id^="chart_"]'));
                if (charts.length === 0) return alert('No charts to export.');

                function drawHeader(sectionTitle) {
                    // full-page dark background
                    pdf.setFillColor(34, 34, 34); // dark gray background
                    pdf.rect(0, 0, pageWidth, pageHeight, 'F');
                    // banner background on top
                    pdf.setFillColor(255, 75, 43); // primary color
                    pdf.rect(0, 0, pageWidth, headerH, 'F');
                    // title (light text on dark bg)
                    pdf.setTextColor(255, 255, 255);
                    pdf.setFontSize(16);
                    pdf.text('Spice Fusion — Reports', margin, 26);
                    pdf.setFontSize(10);
                    pdf.text(dateStr, pageWidth - margin - pdf.getTextWidth(dateStr), 26);
                    // section subtitle
                    if (sectionTitle) {
                        pdf.setFontSize(12);
                        pdf.text(String(sectionTitle), margin, 44);
                    }
                    // leave text color as white by default
                }

                function drawFooter(pageNum, pageCount) {
                    pdf.setFontSize(9);
                    pdf.setTextColor(220); // light gray on dark background
                    const footerText = 'Page ' + pageNum + ' of ' + pageCount;
                    pdf.text(footerText, pageWidth - margin - pdf.getTextWidth(footerText), pageHeight - 10);
                    pdf.text('Generated: ' + dateStr, margin, pageHeight - 10);
                    // do not reset color; subsequent code sets its own colors
                }

                // Add each chart as a page with header/section
                const availableW = pageWidth - margin * 2;
                const availableH = pageHeight - headerH - footerH - margin;
                const chartsPerPage = 2; // two charts vertically per page
                const paddingY = 18;
                const slotH = (availableH - paddingY) / chartsPerPage;
                let idxOnPage = 0;
                let pageIndex = 0;
                for (let i = 0; i < charts.length; i++) {
                    const canvas = charts[i];
                    try {
                        if (idxOnPage === 0) {
                            if (!first) pdf.addPage();
                            first = false;
                            pageIndex++;
                            drawHeader('Category Charts');
                        }
                        const section = canvas.closest('.category-section');
                        const sectionTitle = section ? (section.querySelector('.category-title')?.textContent || '') : '';
                        const imgData = canvas.toDataURL('image/png', 1.0);
                        const ratio = canvas.height / canvas.width;
                        let imgW = availableW;
                        let imgH = imgW * ratio;
                        if (imgH > slotH - 22) { imgH = slotH - 22; imgW = imgH / ratio; }
                        const imgX = margin + (availableW - imgW) / 2;
                        const imgY = headerH + (idxOnPage * slotH) + 16; // start inside this slot
                        // subtitle above chart (light text)
                        pdf.setFontSize(12);
                        pdf.setTextColor(255, 255, 255);
                        pdf.text(String(sectionTitle || ('Chart ' + (i + 1))), margin, imgY - 6);
                        // image will overlay on dark background
                        // image
                        pdf.addImage(imgData, 'PNG', imgX, imgY, imgW, imgH);
                        idxOnPage++;
                        if (idxOnPage >= chartsPerPage && i < charts.length - 1) {
                            idxOnPage = 0;
                        }
                    } catch (e) {
                        // skip this canvas on error
                    }
                }

                // Footer with page numbers
                const totalPages = pdf.getNumberOfPages();
                for (let i = 1; i <= totalPages; i++) {
                    pdf.setPage(i);
                    drawFooter(i, totalPages);
                }

                pdf.save('spice_fusion_charts_' + new Date().toISOString().slice(0,10) + '.pdf');
            });
        }

        // Export all tables to a single PDF with dark theme
        if (btnTablesPdf) {
            btnTablesPdf.addEventListener('click', function() {
                const { jsPDF } = window.jspdf || {};
                if (!jsPDF || !('autoTable' in (jsPDF.prototype || {}))) {
                    // attempt anyway; if plugin missing, notify
                }
                const pdf = new jsPDF({ orientation: 'p', unit: 'pt', format: 'a4' });
                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();
                const margin = 36;
                const headerH = 60;
                const footerH = 28;
                const dateStr = new Date().toLocaleString();

                function drawHeaderGeneric() {
                    pdf.setFillColor(255, 75, 43);
                    pdf.rect(0, 0, pageWidth, headerH, 'F');
                    pdf.setTextColor(255, 255, 255);
                    pdf.setFontSize(16);
                    pdf.text('Spice Fusion — Report Tables', margin, 26);
                    pdf.setFontSize(10);
                    pdf.text(dateStr, pageWidth - margin - pdf.getTextWidth(dateStr), 26);
                    pdf.setTextColor(0, 0, 0);
                }

                function drawFooter(pageNum, pageCount) {
                    pdf.setFontSize(9);
                    pdf.setTextColor(120);
                    const footerText = 'Page ' + pageNum + ' of ' + pageCount;
                    pdf.text(footerText, pageWidth - margin - pdf.getTextWidth(footerText), pageHeight - 10);
                    pdf.text('Generated: ' + dateStr, margin, pageHeight - 10);
                    pdf.setTextColor(0, 0, 0);
                }

                let currentY = headerH + margin;
                drawHeaderGeneric();

                const sections = Array.from(document.querySelectorAll('.category-section'));
                if (sections.length === 0) return alert('No tables to export.');

                // Hook header/footer for each new page
                const autoTableCommon = {
                    margin: { top: headerH + 10, left: margin, right: margin, bottom: footerH + 10 },
                    styles: { fillColor: [22,22,22], textColor: 255, lineColor: [68,68,68], lineWidth: 0.5, fontSize: 10 },
                    headStyles: { fillColor: [255,75,43], textColor: 255, lineColor: [68,68,68], lineWidth: 0.5, fontStyle: 'bold' },
                    alternateRowStyles: { fillColor: [34,34,34] },
                    didDrawPage: function(data) {
                        // Header and footer on every page
                        drawHeaderGeneric();
                        const pageNum = pdf.internal.getNumberOfPages();
                        drawFooter(pageNum, pdf.internal.getNumberOfPages());
                    }
                };

                sections.forEach((section, idx) => {
                    const title = (section.querySelector('.category-title')?.textContent || '').trim();
                    const tableEl = section.querySelector('table');
                    if (!tableEl) return;
                    // Section title
                    pdf.setFontSize(12);
                    pdf.setTextColor(255, 75, 43);
                    pdf.text(title, margin, currentY);
                    pdf.setTextColor(0, 0, 0);
                    currentY += 12;

                    // Render table from HTML
                    pdf.autoTable({
                        html: tableEl,
                        startY: currentY,
                        ...autoTableCommon
                    });
                    currentY = pdf.lastAutoTable.finalY + 18;
                    if (currentY > pageHeight - footerH - margin) {
                        pdf.addPage();
                        drawHeaderGeneric();
                        currentY = headerH + margin;
                    }
                });

                // Footer fix for last page numbering
                const totalPages = pdf.getNumberOfPages();
                for (let i = 1; i <= totalPages; i++) {
                    pdf.setPage(i);
                    drawFooter(i, totalPages);
                }

                pdf.save('spice_fusion_tables_' + new Date().toISOString().slice(0,10) + '.pdf');
            });
        }

        // Export all tables to an Excel workbook (one sheet per category)
        if (btnExcel) {
            btnExcel.addEventListener('click', function() {
                if (!window.XLSX) return alert('Excel library failed to load.');
                const wb = XLSX.utils.book_new();
                const sections = Array.from(document.querySelectorAll('.category-section'));
                if (sections.length === 0) return alert('No tables to export.');
                sections.forEach((section, idx) => {
                    const titleEl = section.querySelector('.category-title');
                    const tableEl = section.querySelector('table');
                    if (!tableEl) return;
                    const ws = XLSX.utils.table_to_sheet(tableEl, { raw: true });
                    let name = (titleEl ? titleEl.textContent.trim() : 'Sheet ' + (idx+1));
                    name = name.replace(/[\[\]:\*\?\/\\]/g, ' ');
                    if (name.length > 31) name = name.slice(0, 31);
                    XLSX.utils.book_append_sheet(wb, ws, name || ('Sheet' + (idx+1)));
                });
                XLSX.writeFile(wb, 'tables_report.xlsx');
            });
        }
    });
    </script>
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
    // Dark theme defaults for Chart.js labels
    if (typeof Chart !== 'undefined') {
        Chart.defaults.color = '#e0e0e0';
        Chart.defaults.borderColor = 'rgba(255,255,255,0.08)';
    }
    new Chart(document.getElementById('chart_<?php echo $cat['category_id']; ?>').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [
                {
                    label: 'Sales (RM)',
                    data: <?php echo json_encode($sales); ?>,
                    backgroundColor: 'rgba(198,40,40,0.8)', // dark red
                    borderRadius: 10,
                    borderSkipped: false,
                    barPercentage: 0.6,
                    categoryPercentage: 0.7,
                    borderWidth: 2,
                    borderColor: 'rgba(183,28,28,1)',
                    hoverBackgroundColor: 'rgba(183,28,28,1)',
                    shadowOffsetX: 2,
                    shadowOffsetY: 2,
                    shadowBlur: 8,
                    shadowColor: 'rgba(255,75,43,0.2)'
                },
                {
                    label: 'Sold Qty',
                    data: <?php echo json_encode($qtys); ?>,
                    backgroundColor: 'rgba(21,101,192,0.8)', // dark blue
                    borderRadius: 10,
                    borderSkipped: false,
                    barPercentage: 0.6,
                    categoryPercentage: 0.7,
                    borderWidth: 2,
                    borderColor: 'rgba(13,71,161,1)',
                    hoverBackgroundColor: 'rgba(13,71,161,1)',
                    shadowOffsetX: 2,
                    shadowOffsetY: 2,
                    shadowBlur: 8,
                    shadowColor: 'rgba(21,101,192,0.2)'
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: '#e0e0e0',
                        font: { weight: 'bold', size: 15 }
                    }
                },
                title: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(18,18,18,0.95)',
                    titleColor: '#FF4B2B',
                    bodyColor: '#e0e0e0',
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
                        color: '#e0e0e0',
                        font: { weight: 'bold', size: 13 }
                    },
                    grid: { color: 'rgba(255,255,255,0.06)' }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#e0e0e0',
                        font: { weight: 'bold', size: 13 }
                    },
                    grid: { color: 'rgba(255,255,255,0.10)' }
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
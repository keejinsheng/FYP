<?php
/**
 * PDF Receipt Download Function
 * Generates and downloads a PDF receipt for completed orders
 */

require_once '../../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('HTTP/1.1 403 Forbidden');
    die('Access denied. Please login first.');
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;
$user_id = getCurrentUserId();
$pdo = getDBConnection();

// If order_id is provided, fetch from database; otherwise use session
if ($order_id) {
    // Fetch order details from database
    $stmt = $pdo->prepare("
        SELECT o.*, u.first_name, u.last_name, u.email, u.phone,
               da.address_line1, da.address_line2, da.city, da.state, da.postal_code, da.country,
               p.payment_method, p.payment_status, p.payment_date
        FROM `order` o
        JOIN user u ON o.user_id = u.user_id
        LEFT JOIN delivery_address da ON o.address_id = da.address_id
        LEFT JOIN payment p ON o.order_id = p.order_id
        WHERE o.order_id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        header('HTTP/1.1 404 Not Found');
        die('Order not found.');
    }
    
    // Fetch order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.product_name
        FROM order_item oi
        JOIN product p ON oi.product_id = p.product_id
        WHERE oi.order_id = ?
        ORDER BY oi.item_id
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();
    
    $order_data = [
        'order_id' => $order['order_id'],
        'order_number' => $order['order_number'],
        'total_amount' => $order['total_amount'],
        'subtotal' => $order['subtotal'],
        'tax_amount' => $order['tax_amount'],
        'delivery_fee' => $order['delivery_fee'],
        'payment_method' => $order['payment_method'],
        'created_at' => $order['created_at'],
        'items' => [],
        'address' => [
            'line1' => $order['address_line1'],
            'line2' => $order['address_line2'] ?? '',
            'city' => $order['city'],
            'state' => $order['state'],
            'postal_code' => $order['postal_code'],
            'country' => $order['country'] ?? 'Malaysia'
        ],
        'user' => [
            'first_name' => $order['first_name'],
            'last_name' => $order['last_name'],
            'email' => $order['email'],
            'phone' => $order['phone']
        ]
    ];
    
    foreach ($order_items as $item) {
        $order_data['items'][] = [
            'product_name' => $item['product_name'],
            'quantity' => $item['quantity'],
            'price' => $item['unit_price'],
            'total_price' => $item['total_price']
        ];
    }
} else {
    // Use session data if available
    if (!isset($_SESSION['last_order'])) {
        header('HTTP/1.1 400 Bad Request');
        die('No order data found.');
    }
    
    $order_data = $_SESSION['last_order'];
    
    // Get user information
    $stmt = $pdo->prepare("SELECT first_name, last_name, email, phone FROM user WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    $order_data['user'] = [
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email'],
        'phone' => $user['phone']
    ];
    
    $order_data['created_at'] = date('Y-m-d H:i:s');
}

// Include TCPDF library (adjust path as needed)
// Download TCPDF from: https://github.com/tecnickcom/TCPDF
// Place it in a 'libraries' or 'vendor' folder
$tcpdf_path = dirname(__DIR__) . '/../../vendor/tcpdf/tcpdf.php';

// If TCPDF is not available, use a simple HTML-based solution
if (file_exists($tcpdf_path)) {
    require_once $tcpdf_path;
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Spice Fusion');
    $pdf->SetAuthor('Spice Fusion');
    $pdf->SetTitle('Order Receipt - ' . $order_data['order_number']);
    $pdf->SetSubject('Order Receipt');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', 'B', 20);
    
    // Title
    $pdf->Cell(0, 15, 'SPICE FUSION', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 5, 'Order Receipt', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Order Information
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Order Details', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    
    $order_date = date('F d, Y \a\t g:i A', strtotime($order_data['created_at']));
    $pdf->Cell(0, 6, 'Order Number: ' . $order_data['order_number'], 0, 1);
    $pdf->Cell(0, 6, 'Order Date: ' . $order_date, 0, 1);
    $pdf->Ln(5);
    
    // Customer Information
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Customer Information', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, $order_data['user']['first_name'] . ' ' . $order_data['user']['last_name'], 0, 1);
    $pdf->Cell(0, 6, $order_data['user']['email'], 0, 1);
    if ($order_data['user']['phone']) {
        $pdf->Cell(0, 6, 'Phone: ' . $order_data['user']['phone'], 0, 1);
    }
    $pdf->Ln(5);
    
    // Delivery Address
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Delivery Address', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $address_text = $order_data['address']['line1'];
    if ($order_data['address']['line2']) {
        $address_text .= "\n" . $order_data['address']['line2'];
    }
    $address_text .= "\n" . $order_data['address']['city'] . ', ' . $order_data['address']['state'] . ' ' . $order_data['address']['postal_code'];
    $address_text .= "\n" . $order_data['address']['country'];
    $pdf->MultiCell(0, 6, $address_text, 0, 'L');
    $pdf->Ln(5);
    
    // Order Items
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Order Items', 0, 1);
    $pdf->Ln(2);
    
    // Table header
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(100, 8, 'Item', 'B', 0, 'L');
    $pdf->Cell(25, 8, 'Qty', 'B', 0, 'C');
    $pdf->Cell(30, 8, 'Price', 'B', 0, 'R');
    $pdf->Cell(35, 8, 'Total', 'B', 1, 'R');
    
    // Table rows
    $pdf->SetFont('helvetica', '', 10);
    foreach ($order_data['items'] as $item) {
        $pdf->Cell(100, 8, $item['product_name'], 0, 0, 'L');
        $pdf->Cell(25, 8, $item['quantity'], 0, 0, 'C');
        $pdf->Cell(30, 8, 'RM ' . number_format($item['price'], 2), 0, 0, 'R');
        $pdf->Cell(35, 8, 'RM ' . number_format($item['total_price'], 2), 0, 1, 'R');
    }
    
    $pdf->Ln(5);
    
    // Summary
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(100, 6, '', 0, 0);
    $pdf->Cell(55, 6, 'Subtotal:', 0, 0, 'R');
    $pdf->Cell(35, 6, 'RM ' . number_format($order_data['subtotal'], 2), 0, 1, 'R');
    
    $pdf->Cell(100, 6, '', 0, 0);
    $pdf->Cell(55, 6, 'Tax (6%):', 0, 0, 'R');
    $pdf->Cell(35, 6, 'RM ' . number_format($order_data['tax_amount'], 2), 0, 1, 'R');
    
    $pdf->Cell(100, 6, '', 0, 0);
    $pdf->Cell(55, 6, 'Delivery Fee:', 0, 0, 'R');
    $pdf->Cell(35, 6, 'RM ' . number_format($order_data['delivery_fee'], 2), 0, 1, 'R');
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(100, 8, '', 0, 0);
    $pdf->Cell(55, 8, 'Total:', 'T', 0, 'R');
    $pdf->Cell(35, 8, 'RM ' . number_format($order_data['total_amount'], 2), 'T', 1, 'R');
    
    $pdf->Ln(5);
    
    // Payment Information
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Payment Information', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Payment Method: ' . $order_data['payment_method'], 0, 1);
    $pdf->Cell(0, 6, 'Payment Status: Completed', 0, 1);
    
    $pdf->Ln(10);
    
    // Footer
    $pdf->SetFont('helvetica', 'I', 9);
    $pdf->Cell(0, 6, 'Thank you for your order!', 0, 1, 'C');
    $pdf->Cell(0, 6, 'If you have any questions, please contact us.', 0, 1, 'C');
    
    // Output PDF
    $filename = 'Receipt_' . $order_data['order_number'] . '.pdf';
    $pdf->Output($filename, 'D'); // 'D' = force download
    
} else {
    // Fallback: HTML-based receipt that can be printed as PDF
    // This works without TCPDF by using browser's print-to-PDF functionality
    header('Content-Type: text/html; charset=UTF-8');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Receipt - <?php echo htmlspecialchars($order_data['order_number']); ?></title>
        <style>
            @media print {
                body { margin: 0; padding: 20px; }
                .no-print { display: none; }
                @page { margin: 1cm; }
            }
            body {
                font-family: Arial, sans-serif;
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
                background: #f5f5f5;
            }
            .receipt-container {
                background: white;
                padding: 30px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .header {
                text-align: center;
                border-bottom: 2px solid #333;
                padding-bottom: 20px;
                margin-bottom: 30px;
            }
            .header h1 {
                margin: 0;
                color: #FF4B2B;
                font-size: 28px;
            }
            .section {
                margin-bottom: 25px;
            }
            .section h2 {
                color: #333;
                border-bottom: 1px solid #ddd;
                padding-bottom: 5px;
                font-size: 18px;
            }
            .info-row {
                margin: 8px 0;
                font-size: 14px;
            }
            .info-label {
                font-weight: bold;
                display: inline-block;
                width: 150px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }
            th, td {
                padding: 10px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }
            th {
                background-color: #f8f8f8;
                font-weight: bold;
            }
            .text-right {
                text-align: right;
            }
            .text-center {
                text-align: center;
            }
            .total-row {
                font-weight: bold;
                font-size: 16px;
                border-top: 2px solid #333;
                padding-top: 10px;
            }
            .summary {
                margin-top: 20px;
            }
            .summary-row {
                display: flex;
                justify-content: space-between;
                padding: 5px 0;
            }
            .summary-total {
                font-weight: bold;
                font-size: 18px;
                border-top: 2px solid #333;
                padding-top: 10px;
                margin-top: 10px;
            }
            .btn {
                background: #FF4B2B;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-size: 16px;
                margin: 10px 5px;
            }
            .btn:hover {
                background: #FF416C;
            }
        </style>
    </head>
    <body>
        <div class="no-print" style="text-align: center; margin-bottom: 20px;">
            <button class="btn" onclick="window.print()">Print / Save as PDF</button>
            <button class="btn" onclick="window.close()">Close</button>
        </div>
        
        <div class="receipt-container">
            <div class="header">
                <h1>SPICE FUSION</h1>
                <p>Order Receipt</p>
            </div>
            
            <div class="section">
                <h2>Order Details</h2>
                <div class="info-row">
                    <span class="info-label">Order Number:</span>
                    <?php echo htmlspecialchars($order_data['order_number']); ?>
                </div>
                <div class="info-row">
                    <span class="info-label">Order Date:</span>
                    <?php echo date('F d, Y \a\t g:i A', strtotime($order_data['created_at'])); ?>
                </div>
            </div>
            
            <div class="section">
                <h2>Customer Information</h2>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <?php echo htmlspecialchars($order_data['user']['first_name'] . ' ' . $order_data['user']['last_name']); ?>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <?php echo htmlspecialchars($order_data['user']['email']); ?>
                </div>
                <?php if ($order_data['user']['phone']): ?>
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    <?php echo htmlspecialchars($order_data['user']['phone']); ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="section">
                <h2>Delivery Address</h2>
                <div class="info-row">
                    <?php echo htmlspecialchars($order_data['address']['line1']); ?><br>
                    <?php if ($order_data['address']['line2']): ?>
                        <?php echo htmlspecialchars($order_data['address']['line2']); ?><br>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($order_data['address']['city'] . ', ' . $order_data['address']['state'] . ' ' . $order_data['address']['postal_code']); ?><br>
                    <?php echo htmlspecialchars($order_data['address']['country']); ?>
                </div>
            </div>
            
            <div class="section">
                <h2>Order Items</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-center">Quantity</th>
                            <th class="text-right">Unit Price</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_data['items'] as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td class="text-center"><?php echo $item['quantity']; ?></td>
                            <td class="text-right">RM <?php echo number_format($item['price'], 2); ?></td>
                            <td class="text-right">RM <?php echo number_format($item['total_price'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="section">
                <div class="summary">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>RM <?php echo number_format($order_data['subtotal'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Tax (6%):</span>
                        <span>RM <?php echo number_format($order_data['tax_amount'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Delivery Fee:</span>
                        <span>RM <?php echo number_format($order_data['delivery_fee'], 2); ?></span>
                    </div>
                    <div class="summary-total summary-row">
                        <span>Total:</span>
                        <span>RM <?php echo number_format($order_data['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <h2>Payment Information</h2>
                <div class="info-row">
                    <span class="info-label">Payment Method:</span>
                    <?php echo htmlspecialchars($order_data['payment_method']); ?>
                </div>
                <div class="info-row">
                    <span class="info-label">Payment Status:</span>
                    <span style="color: #4caf50; font-weight: bold;">Completed</span>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px;">
                <p>Thank you for your order!</p>
                <p>If you have any questions, please contact us.</p>
            </div>
        </div>
        
        <script>
            // Auto-trigger print dialog on page load (optional)
            // window.onload = function() {
            //     setTimeout(function() {
            //         window.print();
            //     }, 500);
            // };
        </script>
    </body>
    </html>
    <?php
}
?>


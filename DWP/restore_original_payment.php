<?php
require_once 'config/database.php';

echo "<h2>RESTORE ORIGINAL PAYMENT METHOD</h2>";

$pdo = getDBConnection();

// Check current ENUM
echo "<h3>1. Current ENUM:</h3>";
$stmt = $pdo->query("SHOW COLUMNS FROM payment LIKE 'payment_method'");
$column_info = $stmt->fetch();
echo "<p><strong>Current:</strong> " . $column_info['Type'] . "</p>";

// Restore original ENUM with Credit Card
echo "<h3>2. Restore Original ENUM:</h3>";
if (isset($_POST['restore'])) {
    try {
        $stmt = $pdo->prepare("
            ALTER TABLE payment MODIFY COLUMN payment_method 
            ENUM('Cash', 'Credit Card', 'Debit Card', 'PayPal', 'Online Banking') NOT NULL
        ");
        $stmt->execute();
        echo "<p style='color: green;'>✓ ENUM restored successfully!</p>";
        
        // Show updated ENUM
        $stmt = $pdo->query("SHOW COLUMNS FROM payment LIKE 'payment_method'");
        $column_info = $stmt->fetch();
        echo "<p><strong>Updated:</strong> " . $column_info['Type'] . "</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Failed: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<form method='POST'>";
    echo "<input type='submit' name='restore' value='Restore Original ENUM' style='padding: 10px 20px; background: #FF4B2B; color: white; border: none; border-radius: 5px; cursor: pointer;'>";
    echo "</form>";
}

// Show working order
echo "<h3>3. Working Order Check:</h3>";
$stmt = $pdo->prepare("
    SELECT o.order_number, p.payment_method, p.payment_status
    FROM `order` o
    LEFT JOIN payment p ON o.order_id = p.order_id
    WHERE o.order_number = ?
");
$stmt->execute(['SF202506227769']);
$working_order = $stmt->fetch();

if ($working_order) {
    echo "<div style='border: 2px solid green; padding: 15px; background: #f0f8f0;'>";
    echo "<h4>✓ Working Order Found!</h4>";
    echo "<p><strong>Order:</strong> " . $working_order['order_number'] . "</p>";
    echo "<p><strong>Payment Method:</strong> <span style='color: green; font-weight: bold;'>" . $working_order['payment_method'] . "</span></p>";
    echo "<p><strong>Status:</strong> " . $working_order['payment_status'] . "</p>";
    echo "</div>";
} else {
    echo "<p style='color: red;'>Working order not found!</p>";
}

echo "<h3>4. Summary:</h3>";
echo "<p>• Changed checkout form to use 'Credit Card' instead of 'Card'</p>";
echo "<p>• Restored database ENUM to include 'Credit Card'</p>";
echo "<p>• This should match the working order SF202506227769</p>";
echo "<p>• Now test creating a new order with Credit/Debit Card</p>";
?> 
<?php
require_once '../../config/database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');

if (!isLoggedIn()) {
    die("Please log in first.");
}

$pdo = getDBConnection();

echo "<h2>Bank Verification Test</h2>";

// Show all records in dummy_bank table
echo "<h3>All Records in dummy_bank table:</h3>";
try {
    $stmt = $pdo->query("SELECT * FROM dummy_bank ORDER BY bank_id");
    $all_banks = $stmt->fetchAll();
    
    if (empty($all_banks)) {
        echo "<p style='color: red;'>No records found in dummy_bank table!</p>";
        echo "<p>Please run the SQL script: create_dummy_bank_table.sql</p>";
    } else {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Bank Name</th><th>Cardholder</th><th>Card Number</th><th>Expiry</th><th>CVV</th><th>Active</th></tr>";
        foreach ($all_banks as $bank) {
            $active = $bank['is_active'] ? 'Yes' : 'No';
            echo "<tr>";
            echo "<td>{$bank['bank_id']}</td>";
            echo "<td>{$bank['bank_name']}</td>";
            echo "<td>{$bank['cardholder_name']}</td>";
            echo "<td>{$bank['card_number']}</td>";
            echo "<td>{$bank['expiry_date']}</td>";
            echo "<td>{$bank['cvv']}</td>";
            echo "<td>$active</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Test verification function
echo "<h3>Test Verification:</h3>";
echo "<form method='POST'>";
echo "<table>";
echo "<tr><td>Bank Name:</td><td><input type='text' name='test_bank_name' value='Maybank'></td></tr>";
echo "<tr><td>Cardholder Name:</td><td><input type='text' name='test_cardholder' value='John Doe'></td></tr>";
echo "<tr><td>Card Number:</td><td><input type='text' name='test_card' value='1234567890123456'></td></tr>";
echo "<tr><td>Expiry Date:</td><td><input type='text' name='test_expiry' value='12/25'></td></tr>";
echo "<tr><td>CVV:</td><td><input type='text' name='test_cvv' value='123'></td></tr>";
echo "<tr><td colspan='2'><input type='submit' name='test' value='Test Verification'></td></tr>";
echo "</table>";
echo "</form>";

if (isset($_POST['test'])) {
    $test_bank = trim($_POST['test_bank_name'] ?? '');
    $test_cardholder = trim($_POST['test_cardholder'] ?? '');
    $test_card = trim($_POST['test_card'] ?? '');
    $test_expiry = trim($_POST['test_expiry'] ?? '');
    $test_cvv = trim($_POST['test_cvv'] ?? '');
    
    echo "<h4>Test Results:</h4>";
    echo "<p><strong>Input:</strong></p>";
    echo "<ul>";
    echo "<li>Bank: '$test_bank'</li>";
    echo "<li>Cardholder: '$test_cardholder'</li>";
    echo "<li>Card: '$test_card'</li>";
    echo "<li>Expiry: '$test_expiry'</li>";
    echo "<li>CVV: '$test_cvv'</li>";
    echo "</ul>";
    
    // Clean inputs the same way as the function does
    $test_card_clean = preg_replace('/\s+/', '', trim($test_card));
    
    echo "<p><strong>Normalized Input:</strong></p>";
    echo "<ul>";
    echo "<li>Bank: '$test_bank' (length: " . strlen($test_bank) . ")</li>";
    echo "<li>Cardholder: '$test_cardholder' (length: " . strlen($test_cardholder) . ")</li>";
    echo "<li>Card: '$test_card_clean' (length: " . strlen($test_card_clean) . ")</li>";
    echo "<li>Expiry: '$test_expiry' (length: " . strlen($test_expiry) . ")</li>";
    echo "<li>CVV: '$test_cvv' (length: " . strlen($test_cvv) . ")</li>";
    echo "</ul>";
    
    // Manual comparison with database
    echo "<p><strong>Manual Comparison with Database:</strong></p>";
    try {
        $compare_stmt = $pdo->query("SELECT * FROM dummy_bank WHERE is_active = 1");
        $all_records = $compare_stmt->fetchAll();
        
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Field</th><th>Your Input</th><th>Database Value</th><th>Match?</th></tr>";
        
        $found_match = false;
        foreach ($all_records as $record) {
            $db_bank = trim($record['bank_name']);
            $db_cardholder = trim($record['cardholder_name']);
            $db_card = preg_replace('/\s+/', '', trim($record['card_number']));
            $db_expiry = trim($record['expiry_date']);
            $db_cvv = trim($record['cvv']);
            
            $bank_match = (strtolower($db_bank) === strtolower($test_bank)) || empty($test_bank);
            $cardholder_match = (strtolower($db_cardholder) === strtolower($test_cardholder));
            $card_match = ($db_card === $test_card_clean);
            $expiry_match = ($db_expiry === $test_expiry);
            $cvv_match = ($db_cvv === $test_cvv);
            
            $all_match = $bank_match && $cardholder_match && $card_match && $expiry_match && $cvv_match;
            
            if ($all_match) {
                $found_match = true;
                echo "<tr style='background-color: #d4edda;'><td colspan='4'><strong>✓ MATCH FOUND - Record ID: {$record['bank_id']}</strong></td></tr>";
            }
            
            echo "<tr>";
            echo "<td>Bank Name</td>";
            echo "<td>'$test_bank'</td>";
            echo "<td>'$db_bank'</td>";
            echo "<td>" . ($bank_match ? '✓' : '✗') . "</td>";
            echo "</tr>";
            
            echo "<tr>";
            echo "<td>Cardholder</td>";
            echo "<td>'$test_cardholder'</td>";
            echo "<td>'$db_cardholder'</td>";
            echo "<td>" . ($cardholder_match ? '✓' : '✗') . "</td>";
            echo "</tr>";
            
            echo "<tr>";
            echo "<td>Card Number</td>";
            echo "<td>'$test_card_clean'</td>";
            echo "<td>'$db_card'</td>";
            echo "<td>" . ($card_match ? '✓' : '✗') . "</td>";
            echo "</tr>";
            
            echo "<tr>";
            echo "<td>Expiry Date</td>";
            echo "<td>'$test_expiry'</td>";
            echo "<td>'$db_expiry'</td>";
            echo "<td>" . ($expiry_match ? '✓' : '✗') . "</td>";
            echo "</tr>";
            
            echo "<tr>";
            echo "<td>CVV</td>";
            echo "<td>'$test_cvv'</td>";
            echo "<td>'$db_cvv'</td>";
            echo "<td>" . ($cvv_match ? '✓' : '✗') . "</td>";
            echo "</tr>";
            
            echo "<tr><td colspan='4'><hr></td></tr>";
        }
        echo "</table>";
        
        if (!$found_match) {
            echo "<p style='color: orange;'>⚠ No exact match found in database. Check which fields don't match above.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error comparing: " . $e->getMessage() . "</p>";
    }
    
    $result = verifyBankDetails($test_bank, $test_cardholder, $test_card, $test_expiry, $test_cvv);
    
    if ($result) {
        echo "<p style='color: green; font-weight: bold; font-size: 18px;'>✓ Verification SUCCESS!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold; font-size: 18px;'>✗ Verification FAILED!</p>";
        echo "<p>Check the PHP error log for detailed comparison information.</p>";
        echo "<p><strong>Common issues:</strong></p>";
        echo "<ul>";
        echo "<li>Card number must match exactly (no spaces)</li>";
        echo "<li>Expiry date must be exactly MM/YY format (e.g., '12/25' not '12/2025')</li>";
        echo "<li>CVV must match exactly (e.g., '123' not '0123')</li>";
        echo "<li>Bank name is case-insensitive but must match</li>";
        echo "<li>Record must have is_active = 1</li>";
        echo "</ul>";
    }
}

echo "<hr>";
echo "<p><small>Check PHP error log at: " . ini_get('error_log') . "</small></p>";
?>


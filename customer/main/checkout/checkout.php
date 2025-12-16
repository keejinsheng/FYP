<?php
require_once '../../../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../login/login.php');
}

$pdo = getDBConnection();
$user_id = getCurrentUserId();

$error_message = '';
$success_message = '';

// Fetch cart items
$stmt = $pdo->prepare("
    SELECT sc.cart_id, sc.quantity, sc.special_instructions,
           p.product_id, p.product_name, p.price, p.image, p.description
    FROM shopping_cart sc
    JOIN product p ON sc.product_id = p.product_id
    WHERE sc.user_id = ? AND p.is_available = 1
    ORDER BY sc.created_at DESC
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

// Redirect if cart is empty
if (empty($cart_items)) {
    redirect('../cart/cart.php');
}

// Calculate totals
$subtotal = 0;
$tax_rate = 0.06;
$delivery_fee = 5.00;

foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$tax_amount = $subtotal * $tax_rate;
$total = $subtotal + $tax_amount + $delivery_fee;

// Fetch user's delivery addresses
$stmt = $pdo->prepare("SELECT * FROM delivery_address WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->execute([$user_id]);
$delivery_addresses = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_address_id = sanitize($_POST['selected_address_id'] ?? '');
    $address_line1 = sanitize($_POST['address_line1'] ?? '');
    $address_line2 = sanitize($_POST['address_line2'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $state = sanitize($_POST['state'] ?? '');
    $postal_code = sanitize($_POST['postal_code'] ?? '');
    $payment_method = sanitize($_POST['payment_method'] ?? '');
    $special_instructions = sanitize($_POST['special_instructions'] ?? '');
    $use_new_address = isset($_POST['use_new_address']);
    
    // Card information fields
    $cardholder_name = sanitize($_POST['cardholder_name'] ?? '');
    $card_number = sanitize($_POST['card_number'] ?? '');
    $expiry_date = sanitize($_POST['expiry_date'] ?? '');
    $cvv = sanitize($_POST['cvv'] ?? '');
    $bank_name = sanitize($_POST['bank_name'] ?? '');
    
    if (empty($payment_method)) {
        $error_message = 'Please select a payment method';
    } elseif (!isValidPaymentMethod($payment_method)) {
        $error_message = 'Invalid payment method selected';
    } elseif ($use_new_address && (empty($address_line1) || empty($city) || empty($state) || empty($postal_code))) {
        $error_message = 'Please fill in all required address fields';
    } elseif (!$use_new_address && empty($selected_address_id)) {
        $error_message = 'Please select a delivery address';
    } elseif ($payment_method === 'Credit Card' && (empty($cardholder_name) || empty($card_number) || empty($expiry_date) || empty($cvv))) {
        $error_message = 'Please fill in all required card information';
    } elseif ($payment_method === 'Online Banking' && (empty($cardholder_name) || empty($card_number) || empty($expiry_date) || empty($cvv) || empty($bank_name))) {
        $error_message = 'Please fill in all required payment information';
    } elseif ($payment_method === 'Credit Card' || $payment_method === 'Online Banking') {
        // Validate cardholder name (alphabets and spaces only)
        if (!empty($cardholder_name) && !preg_match('/^[a-zA-Z\s\'-]+$/', $cardholder_name)) {
            $error_message = 'Cardholder name must contain only letters and spaces';
        }
        // Validate card number (numbers only)
        if (empty($error_message) && !empty($card_number)) {
            $card_number_clean = preg_replace('/\s+/', '', $card_number);
            if (!preg_match('/^\d+$/', $card_number_clean)) {
                $error_message = 'Card number must contain only numbers';
            } elseif (strlen($card_number_clean) < 13 || strlen($card_number_clean) > 19) {
                $error_message = 'Card number must be between 13 and 19 digits';
            }
        }
        // Validate expiry date (MM/YY format, numbers only)
        if (empty($error_message) && !empty($expiry_date)) {
            if (!preg_match('/^\d{2}\/\d{2}$/', $expiry_date)) {
                $error_message = 'Expiry date must be in MM/YY format (numbers only)';
            } else {
                // Validate expiry date year cannot be before current year
                list($month, $year) = explode('/', $expiry_date);
                $month_num = (int)$month;
                $year_num = (int)$year;
                $current_year = (int)date('y'); // Get 2-digit current year
                $current_month = (int)date('m'); // Get current month
                
                // Validate month (1-12)
                if ($month_num < 1 || $month_num > 12) {
                    $error_message = 'Expiry month must be between 01 and 12';
                } 
                // Validate year is not before current year
                elseif ($year_num < $current_year) {
                    $error_message = 'Expiry date year cannot be before the current year';
                }
                // If same year, validate month is not before current month
                elseif ($year_num == $current_year && $month_num < $current_month) {
                    $error_message = 'Expiry date cannot be in the past';
                }
            }
        }
        // Validate CVV (numbers only, 3-4 digits)
        if (empty($error_message) && !empty($cvv)) {
            if (!preg_match('/^\d+$/', $cvv)) {
                $error_message = 'CVV must contain only numbers';
            } elseif (strlen($cvv) < 3 || strlen($cvv) > 4) {
                $error_message = 'CVV must be 3 or 4 digits';
            }
        }
        // Validate bank name for Online Banking (alphabets and spaces only)
        if (empty($error_message) && $payment_method === 'Online Banking' && !empty($bank_name) && !preg_match('/^[a-zA-Z\s\'-]+$/', $bank_name)) {
            $error_message = 'Bank name must contain only letters and spaces';
        }
        
        // Verify bank information against dummy_bank table for Online Banking
        if (empty($error_message) && $payment_method === 'Online Banking') {
            $card_number_clean = preg_replace('/\s+/', '', $card_number);
            if (!verifyBankInfo($bank_name, $cardholder_name, $card_number_clean, $expiry_date, $cvv)) {
                $error_message = 'Invalid bank information. Please check your bank details and try again.';
            }
        }
    }
    
    if (empty($error_message)) {
        // Prepare transaction ID early (before transaction starts) for faster processing
        $transaction_id = null;
        if ($payment_method === 'Credit Card' || $payment_method === 'Online Banking') {
            $card_number_clean = preg_replace('/\s+/', '', $card_number);
            $last_four = substr($card_number_clean, -4);
            if ($payment_method === 'Online Banking' && !empty($bank_name)) {
                $transaction_id = "Card ending in {$last_four} | Exp: {$expiry_date} | Bank: {$bank_name}";
            } else {
                $transaction_id = "Card ending in {$last_four} | Exp: {$expiry_date}";
            }
        }
        
        try {
            $pdo->beginTransaction();
            
            // Use existing address or save new one
            if ($use_new_address) {
                // Save new delivery address
                $stmt = $pdo->prepare("
                    INSERT INTO delivery_address (user_id, address_line1, address_line2, city, state, postal_code, is_default)
                    VALUES (?, ?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([$user_id, $address_line1, $address_line2, $city, $state, $postal_code]);
                $address_id = $pdo->lastInsertId();
                
                // Get address details for session (already have the data, no need to query)
                $address_details = [
                    'line1' => $address_line1,
                    'line2' => $address_line2,
                    'city' => $city,
                    'state' => $state,
                    'postal_code' => $postal_code
                ];
            } else {
                // Use existing address - only fetch needed fields for faster query
                $address_id = $selected_address_id;
                
                // Optimized: Only fetch required fields instead of SELECT *
                $stmt = $pdo->prepare("
                    SELECT address_line1, address_line2, city, state, postal_code 
                    FROM delivery_address 
                    WHERE address_id = ? AND user_id = ?
                ");
                $stmt->execute([$address_id, $user_id]);
                $selected_address = $stmt->fetch();
                
                if (!$selected_address) {
                    throw new Exception('Selected address not found');
                }
                
                $address_details = [
                    'line1' => $selected_address['address_line1'],
                    'line2' => $selected_address['address_line2'],
                    'city' => $selected_address['city'],
                    'state' => $selected_address['state'],
                    'postal_code' => $selected_address['postal_code']
                ];
            }
            
            // Create order
            $order_number = 'SF' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("
                INSERT INTO `order` (user_id, address_id, order_number, order_status, order_type, subtotal, tax_amount, delivery_fee, total_amount, special_instructions, estimated_delivery_time)
                VALUES (?, ?, ?, 'Pending', 'Delivery', ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 45 MINUTE))
            ");
            $stmt->execute([$user_id, $address_id, $order_number, $subtotal, $tax_amount, $delivery_fee, $total, $special_instructions]);
            $order_id = $pdo->lastInsertId();
            
            // Create order items with stock deduction (transactional)
            $selectStockStmt = $pdo->prepare("SELECT stock_quantity FROM product WHERE product_id = ? FOR UPDATE");
            $updateStockStmt = $pdo->prepare("UPDATE product SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
            $insertItemStmt = $pdo->prepare("
                INSERT INTO order_item (order_id, product_id, quantity, unit_price, total_price, special_instructions)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($cart_items as $item) {
                // Lock the product row and validate stock
                $selectStockStmt->execute([$item['product_id']]);
                $currentStock = (int)$selectStockStmt->fetchColumn();
                if ($currentStock < (int)$item['quantity']) {
                    throw new Exception('Insufficient stock for ' . $item['product_name']);
                }

                // Deduct stock
                $updateStockStmt->execute([$item['quantity'], $item['product_id']]);

                // Insert order item
                $item_total = $item['price'] * $item['quantity'];
                $insertItemStmt->execute([
                    $order_id, $item['product_id'], $item['quantity'],
                    $item['price'], $item_total, $item['special_instructions']
                ]);
            }
            
            // Create payment record (transaction_id already prepared above for faster processing)
            $stmt = $pdo->prepare("
                INSERT INTO payment (order_id, payment_method, payment_status, amount, transaction_id)
                VALUES (?, ?, 'Pending', ?, ?)
            ");
            $stmt->execute([$order_id, $payment_method, $total, $transaction_id]);
            
            // Clear cart
            $stmt = $pdo->prepare("DELETE FROM shopping_cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            $pdo->commit();
            
            // Store order details in session for receipt display
            $_SESSION['last_order'] = [
                'order_id' => $order_id,
                'order_number' => $order_number,
                'total_amount' => $total,
                'payment_method' => $payment_method,
                'items' => $cart_items,
                'address' => $address_details,
                'special_instructions' => $special_instructions,
                'subtotal' => $subtotal,
                'tax_amount' => $tax_amount,
                'delivery_fee' => $delivery_fee
            ];
            
            // Redirect to order confirmation page
            redirect("../checkout/order_confirmation.php");
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = 'Order processing failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Spice Fusion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #FF4B2B;
            --secondary-color: #FF416C;
            --background-dark: #1a1a1a;
            --text-light: #ffffff;
            --text-gray: #a0a0a0;
            --card-bg: #2a2a2a;
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

        .checkout-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .checkout-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .checkout-header h1 {
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }

        .checkout-form {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 2rem;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--text-gray);
            padding-bottom: 0.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-light);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--text-gray);
            border-radius: 6px;
            background: var(--background-dark);
            color: var(--text-light);
            font-family: 'Inter', sans-serif;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .address-option {
            background: var(--background-dark);
            border: 2px solid var(--text-gray);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .address-option:hover {
            border-color: var(--primary-color);
        }

        .address-option.selected {
            border-color: var(--primary-color);
            background: rgba(255, 75, 43, 0.1);
        }

        .address-option input[type="radio"] {
            margin-right: 0.5rem;
        }

        .address-option .default-badge {
            background: var(--primary-color);
            color: var(--text-light);
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            font-size: 0.7rem;
            margin-left: 0.5rem;
        }

        .new-address-toggle {
            background: var(--gradient-primary);
            color: var(--text-light);
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;
            margin-bottom: 1rem;
            transition: var(--transition);
        }

        .new-address-toggle:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-soft);
        }

        .new-address-form {
            display: none;
            background: var(--background-dark);
            border: 1px solid var(--text-gray);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .new-address-form.show {
            display: block;
        }

        .save-address-btn {
            background: var(--primary-color);
            color: var(--text-light);
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;
            margin-top: 1rem;
            transition: var(--transition);
            width: 100%;
        }

        .save-address-btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-soft);
            opacity: 0.9;
        }

        .save-address-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .card-info-section {
            background: var(--background-dark);
            border: 1px solid var(--text-gray);
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1rem;
        }

        .card-info-section h4 {
            margin-top: 0;
        }

        #card_number, #expiry_date, #cvv {
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
        }

        .form-group input.error {
            border-color: var(--primary-color);
            border-width: 2px;
        }

        .error-message {
            color: var(--primary-color);
            font-size: 0.85rem;
            margin-top: 0.3rem;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .order-summary {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            height: fit-content;
        }

        .order-items {
            margin-bottom: 1.5rem;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .item-image {
            width: 50px;
            height: 50px;
            border-radius: 6px;
            object-fit: cover;
        }

        .item-details h4 {
            margin: 0;
            font-size: 0.9rem;
        }

        .item-details p {
            margin: 0;
            color: var(--text-gray);
            font-size: 0.8rem;
        }

        .item-price {
            color: var(--primary-color);
            font-weight: 600;
        }

        .summary-breakdown {
            border-top: 1px solid var(--text-gray);
            padding-top: 1rem;
            margin-top: 1rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            color: var(--text-gray);
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--text-gray);
            font-weight: 600;
            color: var(--text-light);
            font-size: 1.2rem;
        }

        .place-order-btn {
            width: 100%;
            padding: 1rem;
            background: var(--gradient-primary);
            color: var(--text-light);
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1.5rem;
            transition: var(--transition);
        }

        .place-order-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-strong);
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .alert-error {
            background: rgba(255, 75, 43, 0.1);
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <div class="checkout-header">
            <h1>Checkout</h1>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="checkout-grid">
                <div class="checkout-form">
                    <div class="form-section">
                        <h3>Delivery Address</h3>
                        
                        <?php if (!empty($delivery_addresses)): ?>
                            <p style="color: var(--text-gray); margin-bottom: 1rem;">Select a saved address or add a new one:</p>
                            
                            <?php foreach ($delivery_addresses as $address): ?>
                                <div class="address-option" onclick="selectAddress(<?php echo $address['address_id']; ?>)">
                                    <input type="radio" name="selected_address_id" value="<?php echo $address['address_id']; ?>" 
                                           id="address_<?php echo $address['address_id']; ?>">
                                    <label for="address_<?php echo $address['address_id']; ?>">
                                        <?php echo htmlspecialchars($address['address_line1']); ?><br>
                                        <?php if ($address['address_line2']): ?>
                                            <?php echo htmlspecialchars($address['address_line2']); ?><br>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' ' . $address['postal_code']); ?>
                                        <?php if ($address['is_default']): ?>
                                            <span class="default-badge">Default</span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                            
                            <button type="button" class="new-address-toggle" onclick="toggleNewAddress()">
                                <i class="fas fa-plus"></i> Add New Address
                            </button>
                        <?php endif; ?>
                        
                        <div class="new-address-form <?php echo empty($delivery_addresses) ? 'show' : ''; ?>" id="newAddressForm">
                            <h4 style="margin-bottom: 1rem; color: var(--primary-color);">
                                <?php echo empty($delivery_addresses) ? 'Add Your First Address' : 'New Address'; ?>
                            </h4>
                            <input type="hidden" name="use_new_address" value="1">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="address_line1">Address Line 1 *</label>
                                    <input type="text" id="address_line1" name="address_line1" <?php echo empty($delivery_addresses) ? 'required' : ''; ?>>
                                </div>
                                <div class="form-group">
                                    <label for="address_line2">Address Line 2</label>
                                    <input type="text" id="address_line2" name="address_line2">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="city">City *</label>
                                    <input type="text" id="city" name="city" <?php echo empty($delivery_addresses) ? 'required' : ''; ?>>
                                </div>
                                <div class="form-group">
                                    <label for="state">State *</label>
                                    <input type="text" id="state" name="state" <?php echo empty($delivery_addresses) ? 'required' : ''; ?>>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="postal_code">Postal Code *</label>
                                <input type="text" id="postal_code" name="postal_code" <?php echo empty($delivery_addresses) ? 'required' : ''; ?>>
                            </div>
                            <button type="button" class="save-address-btn" id="saveAddressBtn" onclick="saveAddress()">
                                Save Address
                            </button>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Payment Method</h3>
                        <div class="form-group">
                            <select id="payment_method" name="payment_method" required onchange="toggleCardInfo()">
                                <option value="">Select Payment Method</option>
                                <option value="Cash">Cash on Delivery</option>
                                <option value="Credit Card">Credit/Debit Card</option>
                                <option value="Online Banking">Online Banking</option>
                            </select>
                        </div>
                        
                        <div id="cardInfoSection" class="card-info-section" style="display: none;">
                            <h4 style="margin-top: 1.5rem; margin-bottom: 1rem; color: var(--primary-color);">Card Information</h4>
                            
                            <div class="form-group">
                                <label for="cardholder_name">Cardholder Name *</label>
                                <input type="text" id="cardholder_name" name="cardholder_name" placeholder="Name on card" maxlength="100" oninput="validateCardholderName(this)" onblur="validateCardholderName(this)">
                                <div class="error-message" id="cardholder_name_error">Cardholder name must contain only letters and spaces</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="card_number">Card Number *</label>
                                <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19" oninput="formatCardNumber(this)" onblur="validateCardNumber(this)">
                                <div class="error-message" id="card_number_error">Card number must contain only numbers</div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="expiry_date">Expiry Date *</label>
                                    <input type="text" id="expiry_date" name="expiry_date" placeholder="MM/YY" maxlength="5" oninput="formatExpiryDate(this)" onblur="validateExpiryDate(this)">
                                    <div class="error-message" id="expiry_date_error">Expiry date must contain only numbers (MM/YY format)</div>
                                </div>
                                <div class="form-group">
                                    <label for="cvv">CVV *</label>
                                    <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="4" oninput="formatCVV(this)" onblur="validateCVV(this)">
                                    <div class="error-message" id="cvv_error">CVV must contain only numbers</div>
                                </div>
                            </div>
                            
                            <div class="form-group" id="bankNameSection" style="display: none;">
                                <label for="bank_name">Bank Name *</label>
                                <input type="text" id="bank_name" name="bank_name" placeholder="Enter your bank name (e.g., Maybank, CIMB)" maxlength="100" oninput="validateBankName(this)" onblur="validateBankName(this)">
                                <div class="error-message" id="bank_name_error">Bank name must contain only letters and spaces</div>
                            </div>
                            
                            <button type="button" class="save-address-btn" id="savePaymentBtn" onclick="verifyBankInfo()" style="margin-top: 1rem;">
                                Submit
                            </button>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Special Instructions</h3>
                        <div class="form-group">
                            <textarea id="special_instructions" name="special_instructions" placeholder="Any special instructions for your order..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="order-summary">
                    <h3>Order Summary</h3>
                    <div class="order-items">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="order-item">
                                <div class="item-info">
                                    <img src="../../../food_images/<?php echo htmlspecialchars($item['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="item-image">
                                    <div class="item-details">
                                        <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                        <p>Qty: <?php echo $item['quantity']; ?></p>
                                    </div>
                                </div>
                                <div class="item-price">RM <?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-breakdown">
                        <div class="summary-row">
                            <span>Subtotal (<?php echo count($cart_items); ?> items)</span>
                            <span>RM <?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Tax (6%)</span>
                            <span>RM <?php echo number_format($tax_amount, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Delivery Fee</span>
                            <span>RM <?php echo number_format($delivery_fee, 2); ?></span>
                        </div>
                        <div class="summary-total">
                            <span>Total</span>
                            <span>RM <?php echo number_format($total, 2); ?></span>
                        </div>
                    </div>

                    <button type="submit" class="place-order-btn">Place Order</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function selectAddress(addressId) {
            // Remove selected class from all address options
            document.querySelectorAll('.address-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            event.currentTarget.classList.add('selected');
            
            // Check the radio button
            document.getElementById('address_' + addressId).checked = true;
            
            // Hide new address form and remove required attributes
            const newAddressForm = document.getElementById('newAddressForm');
            newAddressForm.classList.remove('show');
            
            // Remove required attributes from new address fields
            document.getElementById('address_line1').removeAttribute('required');
            document.getElementById('city').removeAttribute('required');
            document.getElementById('state').removeAttribute('required');
            document.getElementById('postal_code').removeAttribute('required');
            
            // Remove the use_new_address hidden input
            const useNewAddressInput = document.querySelector('input[name="use_new_address"]');
            if (useNewAddressInput) {
                useNewAddressInput.remove();
            }
        }
        
        function toggleNewAddress() {
            const newAddressForm = document.getElementById('newAddressForm');
            const isVisible = newAddressForm.classList.contains('show');
            
            if (isVisible) {
                newAddressForm.classList.remove('show');
                // Uncheck all address radio buttons
                document.querySelectorAll('input[name="selected_address_id"]').forEach(radio => {
                    radio.checked = false;
                });
                // Remove selected class from all address options
                document.querySelectorAll('.address-option').forEach(option => {
                    option.classList.remove('selected');
                });
                // Remove required attributes from new address fields
                document.getElementById('address_line1').removeAttribute('required');
                document.getElementById('city').removeAttribute('required');
                document.getElementById('state').removeAttribute('required');
                document.getElementById('postal_code').removeAttribute('required');
                // Remove the use_new_address hidden input
                const useNewAddressInput = document.querySelector('input[name="use_new_address"]');
                if (useNewAddressInput) {
                    useNewAddressInput.remove();
                }
            } else {
                newAddressForm.classList.add('show');
                // Uncheck all address radio buttons
                document.querySelectorAll('input[name="selected_address_id"]').forEach(radio => {
                    radio.checked = false;
                });
                // Remove selected class from all address options
                document.querySelectorAll('.address-option').forEach(option => {
                    option.classList.remove('selected');
                });
                // Add required attributes to new address fields
                document.getElementById('address_line1').setAttribute('required', 'required');
                document.getElementById('city').setAttribute('required', 'required');
                document.getElementById('state').setAttribute('required', 'required');
                document.getElementById('postal_code').setAttribute('required', 'required');
                // Add the use_new_address hidden input if it doesn't exist
                if (!document.querySelector('input[name="use_new_address"]')) {
                    const useNewAddressInput = document.createElement('input');
                    useNewAddressInput.type = 'hidden';
                    useNewAddressInput.name = 'use_new_address';
                    useNewAddressInput.value = '1';
                    newAddressForm.appendChild(useNewAddressInput);
                }
            }
        }
        
        // Save address function
        function saveAddress() {
            const addressLine1 = document.getElementById('address_line1').value.trim();
            const addressLine2 = document.getElementById('address_line2').value.trim();
            const city = document.getElementById('city').value.trim();
            const state = document.getElementById('state').value.trim();
            const postalCode = document.getElementById('postal_code').value.trim();
            
            // Validation
            if (!addressLine1 || !city || !state || !postalCode) {
                alert('Please fill in all required address fields');
                return;
            }
            
            // Disable button during save
            const saveBtn = document.getElementById('saveAddressBtn');
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';
            
            // Create form data
            const formData = new FormData();
            formData.append('address_line1', addressLine1);
            formData.append('address_line2', addressLine2);
            formData.append('city', city);
            formData.append('state', state);
            formData.append('postal_code', postalCode);
            formData.append('is_default', '0');
            
            // Send AJAX request
            fetch('../../api/save_address.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showMessage('Address saved successfully!', 'success');
                    
                    // Clear the form
                    document.getElementById('address_line1').value = '';
                    document.getElementById('address_line2').value = '';
                    document.getElementById('city').value = '';
                    document.getElementById('state').value = '';
                    document.getElementById('postal_code').value = '';
                    
                    // Reload the page to show the new address in the list
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert(data.message || 'Error saving address. Please try again.');
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save Address';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving address. Please try again.');
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Address';
            });
        }
        
        // Show message function
        function showMessage(message, type) {
            // Remove existing alerts
            const existingAlert = document.querySelector('.alert-save');
            if (existingAlert) {
                existingAlert.remove();
            }
            
            // Create new alert
            const alert = document.createElement('div');
            alert.className = 'alert alert-save ' + (type === 'success' ? 'alert-success' : 'alert-error');
            alert.textContent = message;
            alert.style.position = 'fixed';
            alert.style.top = '20px';
            alert.style.right = '20px';
            alert.style.zIndex = '9999';
            alert.style.minWidth = '300px';
            alert.style.padding = '1rem';
            alert.style.borderRadius = '6px';
            alert.style.animation = 'slideIn 0.3s ease';
            
            if (type === 'success') {
                alert.style.background = 'rgba(76, 175, 80, 0.9)';
                alert.style.border = '1px solid #4CAF50';
                alert.style.color = '#ffffff';
            } else {
                alert.style.background = 'rgba(255, 75, 43, 0.1)';
                alert.style.border = '1px solid var(--primary-color)';
                alert.style.color = 'var(--primary-color)';
            }
            
            document.body.appendChild(alert);
            
            // Remove after 3 seconds
            setTimeout(() => {
                alert.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => alert.remove(), 300);
            }, 3000);
        }
        
        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
        
        // Toggle card information section
        function toggleCardInfo() {
            const paymentMethod = document.getElementById('payment_method').value;
            const cardInfoSection = document.getElementById('cardInfoSection');
            const bankNameSection = document.getElementById('bankNameSection');
            
            if (paymentMethod === 'Credit Card' || paymentMethod === 'Online Banking') {
                cardInfoSection.style.display = 'block';
                
                // Show bank name field only for Online Banking
                if (paymentMethod === 'Online Banking') {
                    bankNameSection.style.display = 'block';
                    document.getElementById('bank_name').setAttribute('required', 'required');
                } else {
                    bankNameSection.style.display = 'none';
                    document.getElementById('bank_name').removeAttribute('required');
                }
                
                // Set required attributes for card fields
                document.getElementById('cardholder_name').setAttribute('required', 'required');
                document.getElementById('card_number').setAttribute('required', 'required');
                document.getElementById('expiry_date').setAttribute('required', 'required');
                document.getElementById('cvv').setAttribute('required', 'required');
            } else {
                cardInfoSection.style.display = 'none';
                
                // Remove required attributes
                document.getElementById('cardholder_name').removeAttribute('required');
                document.getElementById('card_number').removeAttribute('required');
                document.getElementById('expiry_date').removeAttribute('required');
                document.getElementById('cvv').removeAttribute('required');
                document.getElementById('bank_name').removeAttribute('required');
            }
        }
        
        // Show error message
        function showError(inputId, errorId, message) {
            const input = document.getElementById(inputId);
            const errorElement = document.getElementById(errorId);
            input.classList.add('error');
            errorElement.textContent = message;
            errorElement.classList.add('show');
        }
        
        // Hide error message
        function hideError(inputId, errorId) {
            const input = document.getElementById(inputId);
            const errorElement = document.getElementById(errorId);
            input.classList.remove('error');
            errorElement.classList.remove('show');
        }
        
        // Validate cardholder name (alphabets and spaces only)
        function validateCardholderName(input) {
            const originalValue = input.value;
            // Remove all non-alphabetic characters (keep letters, spaces, hyphens, apostrophes)
            const value = originalValue.replace(/[^a-zA-Z\s'-]/g, '');
            const errorId = 'cardholder_name_error';
            
            // If user typed invalid characters, remove them and show error
            if (originalValue !== value && originalValue.length > 0) {
                input.value = value;
                showError('cardholder_name', errorId, 'Cardholder name must contain only letters and spaces');
                return false;
            }
            
            if (value.trim() === '') {
                hideError('cardholder_name', errorId);
                return true;
            }
            
            // Allow only letters, spaces, hyphens, and apostrophes
            if (!/^[a-zA-Z\s'-]+$/.test(value)) {
                showError('cardholder_name', errorId, 'Cardholder name must contain only letters and spaces');
                return false;
            }
            
            hideError('cardholder_name', errorId);
            return true;
        }
        
        // Format and validate card number (numbers only)
        function formatCardNumber(input) {
            const originalValue = input.value;
            // Remove all non-numeric characters
            let value = originalValue.replace(/\D/g, '');
            
            // If user typed non-numeric characters, show error
            if (originalValue !== value && originalValue.length > 0) {
                showError('card_number', 'card_number_error', 'Card number must contain only numbers');
            } else {
                hideError('card_number', 'card_number_error');
            }
            
            // Format with spaces
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            input.value = formattedValue;
        }
        
        // Validate card number
        function validateCardNumber(input) {
            const value = input.value.replace(/\s+/g, '');
            const errorId = 'card_number_error';
            
            if (value === '') {
                hideError('card_number', errorId);
                return true;
            }
            
            if (!/^\d+$/.test(value)) {
                showError('card_number', errorId, 'Card number must contain only numbers');
                return false;
            }
            
            if (value.length < 13 || value.length > 19) {
                showError('card_number', errorId, 'Card number must be between 13 and 19 digits');
                return false;
            }
            
            hideError('card_number', errorId);
            return true;
        }
        
        // Format and validate expiry date (numbers only)
        function formatExpiryDate(input) {
            const originalValue = input.value;
            // Remove all non-numeric characters
            let value = originalValue.replace(/\D/g, '');
            
            // If user typed non-numeric characters, show error
            if (originalValue !== value && originalValue.length > 0 && !originalValue.includes('/')) {
                showError('expiry_date', 'expiry_date_error', 'Expiry date must contain only numbers');
            } else {
                hideError('expiry_date', 'expiry_date_error');
            }
            
            // Format as MM/YY
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            input.value = value;
        }
        
        // Validate expiry date
        function validateExpiryDate(input) {
            const value = input.value.trim();
            const errorId = 'expiry_date_error';
            
            if (value === '') {
                hideError('expiry_date', errorId);
                return true;
            }
            
            if (!/^\d{2}\/\d{2}$/.test(value)) {
                showError('expiry_date', errorId, 'Expiry date must be in MM/YY format (numbers only)');
                return false;
            }
            
            const [month, year] = value.split('/');
            const monthNum = parseInt(month, 10);
            const yearNum = parseInt(year, 10);
            const currentYear = parseInt(new Date().getFullYear().toString().slice(-2), 10); // Get 2-digit current year
            const currentMonth = new Date().getMonth() + 1; // Get current month (1-12)
            
            if (monthNum < 1 || monthNum > 12) {
                showError('expiry_date', errorId, 'Month must be between 01 and 12');
                return false;
            }
            
            // Validate year is not before current year
            if (yearNum < currentYear) {
                showError('expiry_date', errorId, 'Expiry date year cannot be before the current year');
                return false;
            }
            
            // If same year, validate month is not before current month
            if (yearNum === currentYear && monthNum < currentMonth) {
                showError('expiry_date', errorId, 'Expiry date cannot be in the past');
                return false;
            }
            
            hideError('expiry_date', errorId);
            return true;
        }
        
        // Format and validate CVV (numbers only)
        function formatCVV(input) {
            const originalValue = input.value;
            // Remove all non-numeric characters
            let value = originalValue.replace(/\D/g, '');
            
            // If user typed non-numeric characters, show error
            if (originalValue !== value && originalValue.length > 0) {
                showError('cvv', 'cvv_error', 'CVV must contain only numbers');
            } else {
                hideError('cvv', 'cvv_error');
            }
            
            input.value = value;
        }
        
        // Validate CVV
        function validateCVV(input) {
            const value = input.value.trim();
            const errorId = 'cvv_error';
            
            if (value === '') {
                hideError('cvv', errorId);
                return true;
            }
            
            if (!/^\d+$/.test(value)) {
                showError('cvv', errorId, 'CVV must contain only numbers');
                return false;
            }
            
            if (value.length < 3 || value.length > 4) {
                showError('cvv', errorId, 'CVV must be 3 or 4 digits');
                return false;
            }
            
            hideError('cvv', errorId);
            return true;
        }
        
        // Validate bank name (alphabets and spaces only)
        function validateBankName(input) {
            const originalValue = input.value;
            // Remove all non-alphabetic characters (keep letters, spaces, hyphens, apostrophes)
            const value = originalValue.replace(/[^a-zA-Z\s'-]/g, '');
            const errorId = 'bank_name_error';
            
            // If user typed invalid characters, remove them and show error
            if (originalValue !== value && originalValue.length > 0) {
                input.value = value;
                showError('bank_name', errorId, 'Bank name must contain only letters and spaces');
                return false;
            }
            
            if (value.trim() === '') {
                hideError('bank_name', errorId);
                return true;
            }
            
            // Allow only letters, spaces, hyphens, and apostrophes
            if (!/^[a-zA-Z\s'-]+$/.test(value)) {
                showError('bank_name', errorId, 'Bank name must contain only letters and spaces');
                return false;
            }
            
            hideError('bank_name', errorId);
            return true;
        }
        
        // Verify bank information function (for Online Banking only)
        function verifyBankInfo() {
            const paymentMethod = document.getElementById('payment_method').value;
            const cardholderName = document.getElementById('cardholder_name').value.trim();
            const cardNumber = document.getElementById('card_number').value.replace(/\s+/g, '');
            const expiryDate = document.getElementById('expiry_date').value.trim();
            const cvv = document.getElementById('cvv').value.trim();
            const bankName = document.getElementById('bank_name').value.trim();
            
            // Only verify for Online Banking
            if (paymentMethod !== 'Online Banking') {
                showMessage('Please select Online Banking payment method to verify', 'error');
                return;
            }
            
            // Validation
            if (!cardholderName || !cardNumber || !expiryDate || !cvv || !bankName) {
                alert('Please fill in all required bank information');
                return;
            }
            
            // Validate card number
            if (cardNumber.length < 13 || cardNumber.length > 19) {
                alert('Please enter a valid card number');
                return;
            }
            
            // Validate expiry date
            if (!/^\d{2}\/\d{2}$/.test(expiryDate)) {
                alert('Please enter a valid expiry date (MM/YY)');
                return;
            }
            
            // Validate CVV
            if (cvv.length < 3 || cvv.length > 4) {
                alert('Please enter a valid CVV');
                return;
            }
            
            // Disable button during verification
            const verifyBtn = document.getElementById('savePaymentBtn');
            verifyBtn.disabled = true;
            verifyBtn.textContent = 'Verifying...';
            
            // Create form data
            const formData = new FormData();
            formData.append('bank_name', bankName);
            formData.append('cardholder_name', cardholderName);
            formData.append('card_number', cardNumber);
            formData.append('expiry_date', expiryDate);
            formData.append('cvv', cvv);
            
            // Send AJAX request to verify bank info
            fetch('../../api/verify_bank_info.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.verified) {
                    // Show success message
                    showMessage('Bank information verified successfully!', 'success');
                    verifyBtn.textContent = 'Verified ✓';
                    verifyBtn.style.background = '#4CAF50';
                    setTimeout(() => {
                        verifyBtn.textContent = 'Submit';
                        verifyBtn.style.background = '';
                    }, 2000);
                } else {
                    alert(data.message || 'Invalid bank information. Please check your bank details and try again.');
                    verifyBtn.disabled = false;
                    verifyBtn.textContent = 'Submit';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error verifying bank information. Please try again.');
                verifyBtn.disabled = false;
                verifyBtn.textContent = 'Submit';
            });
        }
        
        // Form validation before submit
        document.querySelector('form').addEventListener('submit', function(e) {
            const selectedAddress = document.querySelector('input[name="selected_address_id"]:checked');
            const newAddressForm = document.getElementById('newAddressForm');
            const isNewAddressVisible = newAddressForm.classList.contains('show');
            const paymentMethod = document.getElementById('payment_method').value;
            const cardInfoSection = document.getElementById('cardInfoSection');
            const isCardInfoVisible = cardInfoSection.style.display === 'block';
            
            let isValid = true;
            let errorMessage = '';
            
            // Check payment method
            if (!paymentMethod) {
                errorMessage = 'Please select a payment method';
                isValid = false;
            }
            // Check card information if required
            else if (isCardInfoVisible) {
                const cardholderNameInput = document.getElementById('cardholder_name');
                const cardNumberInput = document.getElementById('card_number');
                const expiryDateInput = document.getElementById('expiry_date');
                const cvvInput = document.getElementById('cvv');
                const bankNameInput = document.getElementById('bank_name');
                
                const cardholderName = cardholderNameInput.value.trim();
                const cardNumber = cardNumberInput.value.replace(/\s+/g, '');
                const expiryDate = expiryDateInput.value.trim();
                const cvv = cvvInput.value.trim();
                const bankName = bankNameInput ? bankNameInput.value.trim() : '';
                
                // Validate all fields using validation functions
                if (!cardholderName || !cardNumber || !expiryDate || !cvv) {
                    errorMessage = 'Please fill in all required card information';
                    isValid = false;
                } else if (!validateCardholderName(cardholderNameInput)) {
                    errorMessage = 'Cardholder name must contain only letters and spaces';
                    isValid = false;
                } else if (!validateCardNumber(cardNumberInput)) {
                    isValid = false;
                } else if (!validateExpiryDate(expiryDateInput)) {
                    isValid = false;
                } else if (!validateCVV(cvvInput)) {
                    isValid = false;
                } else if (paymentMethod === 'Online Banking') {
                    if (!bankName) {
                        errorMessage = 'Please enter your bank name';
                        isValid = false;
                    } else if (!validateBankName(bankNameInput)) {
                        isValid = false;
                    }
                }
            }
            // Check address selection
            else if (!isNewAddressVisible && !selectedAddress) {
                errorMessage = 'Please select a delivery address';
                isValid = false;
            }
            // Check new address fields if visible
            else if (isNewAddressVisible) {
                const addressLine1 = document.getElementById('address_line1').value.trim();
                const city = document.getElementById('city').value.trim();
                const state = document.getElementById('state').value.trim();
                const postalCode = document.getElementById('postal_code').value.trim();
                
                if (!addressLine1 || !city || !state || !postalCode) {
                    errorMessage = 'Please fill in all required address fields';
                    isValid = false;
                }
            }
            
            if (!isValid) {
                e.preventDefault();
                alert(errorMessage);
                return false;
            }
        });
    </script>
</body>
</html> 
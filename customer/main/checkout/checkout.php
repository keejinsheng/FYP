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
    $special_instructions = sanitize($_POST['special_instructions'] ?? '');
    $use_new_address = isset($_POST['use_new_address']);
    $payment_method = sanitize($_POST['payment_method'] ?? '');
    
    // Bank information field (only for Online Banking)
    $bank_name = sanitize($_POST['bank_name'] ?? '');
    
    if ($use_new_address && (empty($address_line1) || empty($city) || empty($state) || empty($postal_code))) {
        $error_message = 'Please fill in all required address fields';
    } elseif (!$use_new_address && empty($selected_address_id)) {
        $error_message = 'Please select a delivery address';
    } elseif (empty($payment_method)) {
        $error_message = 'Please select a payment method';
    } elseif ($payment_method !== 'Cash on Delivery' && $payment_method !== 'Online Banking' && $payment_method !== 'Credit Card') {
        $error_message = 'Invalid payment method selected';
    } elseif ($payment_method === 'Online Banking' && empty($bank_name)) {
        $error_message = 'Please select a bank';
    }
    
    if (empty($error_message)) {
        
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
            
            // Clear cart
            $stmt = $pdo->prepare("DELETE FROM shopping_cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            // Map payment method to database enum values
            $db_payment_method = '';
            $payment_status = 'Pending';
            
            if ($payment_method === 'Cash on Delivery') {
                $db_payment_method = 'Cash';
                $payment_status = 'Pending'; // Will be completed on delivery
            } elseif ($payment_method === 'Online Banking') {
                $db_payment_method = 'Online Banking';
                $payment_status = 'Completed'; // Payment already processed
            } elseif ($payment_method === 'Credit Card') {
                $db_payment_method = 'Credit Card';
                $payment_status = 'Completed'; // Payment already processed
            }
            
            // Create payment record
            $stmt = $pdo->prepare("
                INSERT INTO payment (order_id, payment_method, payment_status, amount)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$order_id, $db_payment_method, $payment_status, $total]);
            
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
                            <select id="payment_method" name="payment_method" required onchange="toggleBankInfo()">
                                <option value="">Select Payment Method</option>
                                <option value="Cash on Delivery">Cash on Delivery</option>
                                <option value="Online Banking">Online Banking</option>
                                <option value="Credit Card">Credit Card</option>
                            </select>
                        </div>
                        </div>
                        
                    <div class="form-section" id="bankInfoSection" style="display: none;">
                        <h3>Bank Selection</h3>
                        <p style="color: var(--text-gray); margin-bottom: 1rem; font-size: 0.9rem;">Please select your bank to proceed with the order.</p>
                        
                        <div class="form-group" id="bankNameGroup">
                            <label for="bank_name">Select Bank <span id="bankNameRequired" style="color: var(--primary-color);">*</span></label>
                            <select id="bank_name" name="bank_name" style="display: none;">
                                <option value="">-- Select Bank --</option>
                                <option value="Maybank">Maybank</option>
                                <option value="CIMB">CIMB Bank</option>
                                <option value="Public Bank">Public Bank</option>
                                <option value="Hong Leong Bank">Hong Leong Bank</option>
                                <option value="RHB Bank">RHB Bank</option>
                                <option value="AmBank">AmBank</option>
                                <option value="Bank Islam">Bank Islam</option>
                                <option value="Alliance Bank">Alliance Bank</option>
                                <option value="OCBC Bank">OCBC Bank</option>
                                <option value="Standard Chartered">Standard Chartered</option>
                                <option value="UOB">United Overseas Bank (UOB)</option>
                            </select>
                            <small id="bankNameNote" style="color: var(--text-gray); font-size: 0.85rem; display: none;">Required for Online Banking</small>
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
        
        // Toggle bank information section based on payment method
        function toggleBankInfo() {
            const paymentMethod = document.getElementById('payment_method').value;
            const bankInfoSection = document.getElementById('bankInfoSection');
            const bankNameSelect = document.getElementById('bank_name');
            
            if (paymentMethod === 'Online Banking') {
                bankInfoSection.style.display = 'block';
                bankNameSelect.style.display = 'block';
                bankNameSelect.setAttribute('required', 'required');
                document.getElementById('bankNameRequired').style.display = 'inline';
                document.getElementById('bankNameNote').style.display = 'block';
            } else {
                bankInfoSection.style.display = 'none';
                bankNameSelect.removeAttribute('required');
                bankNameSelect.value = '';
            }
        }
        
        // Validate bank information before submit (only for Online Banking)
        function validateBankInfo() {
            const paymentMethod = document.getElementById('payment_method').value;
            
            // Skip validation if Cash on Delivery or Credit Card
            if (paymentMethod === 'Cash on Delivery' || paymentMethod === 'Credit Card') {
                return { valid: true };
            }
            
            // Validate bank selection for Online Banking
            if (paymentMethod === 'Online Banking') {
                const bankName = document.getElementById('bank_name').value.trim();
                if (!bankName) {
                    return { valid: false, message: 'Please select a bank' };
                }
            }
            
            return { valid: true };
        }
        
        // Form validation before submit
        document.querySelector('form').addEventListener('submit', function(e) {
            const selectedAddress = document.querySelector('input[name="selected_address_id"]:checked');
            const newAddressForm = document.getElementById('newAddressForm');
            const isNewAddressVisible = newAddressForm.classList.contains('show');
            const paymentMethod = document.getElementById('payment_method').value;
            
            let isValid = true;
            let errorMessage = '';
            
            // Check payment method
            if (!paymentMethod) {
                errorMessage = 'Please select a payment method';
                isValid = false;
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
            
            // Validate bank information (only for Online Banking)
            if (isValid && paymentMethod === 'Online Banking') {
                const bankValidation = validateBankInfo();
                if (!bankValidation.valid) {
                    errorMessage = bankValidation.message;
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
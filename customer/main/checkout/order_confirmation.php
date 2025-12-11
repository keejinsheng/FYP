<?php
require_once '../../../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../login/login.php');
}

// Handle session clearing when user clicks to view order history
if (isset($_GET['clear_session'])) {
    unset($_SESSION['last_order']);
    redirect('../dashboard/Cdashboard.php');
}

// Check if there's a last order in session
if (!isset($_SESSION['last_order'])) {
    redirect('../cart/cart.php');
}

$order = $_SESSION['last_order'];
$pdo = getDBConnection();
$user_id = getCurrentUserId();

// Update payment status to completed
try {
    $stmt = $pdo->prepare("UPDATE payment SET payment_status = 'Completed' WHERE order_id = ?");
    $stmt->execute([$order['order_id']]);
} catch (Exception $e) {
    // Log error but continue
}

// Fetch existing reviews for products in this order
$existing_reviews = [];
try {
    $stmt = $pdo->prepare("
        SELECT product_id, rating, comment, review_id 
        FROM review 
        WHERE order_id = ? AND user_id = ?
    ");
    $stmt->execute([$order['order_id'], $user_id]);
    $reviews = $stmt->fetchAll();
    foreach ($reviews as $review) {
        $existing_reviews[$review['product_id']] = $review;
    }
} catch (Exception $e) {
    // Log error but continue
}

// Clear the session order data after displaying
$order_data = $order;
// Don't clear session immediately - let user see the order first
// unset($_SESSION['last_order']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Spice Fusion</title>
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

        .confirmation-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .confirmation-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            color: var(--text-light);
        }

        .confirmation-header h1 {
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .confirmation-header p {
            color: var(--text-gray);
            font-size: 1.1rem;
        }

        .status-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .payment-status {
            display: inline-block;
            background: #4caf50;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .order-number {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .receipt-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--text-gray);
        }

        .receipt-header h2 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .receipt-items {
            margin-bottom: 2rem;
        }

        .receipt-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .receipt-item:last-child {
            border-bottom: none;
        }

        .item-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .item-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
        }

        .item-details h4 {
            margin: 0;
            font-size: 1rem;
        }

        .item-details p {
            margin: 0;
            color: var(--text-gray);
            font-size: 0.9rem;
        }

        .item-price {
            color: var(--primary-color);
            font-weight: 600;
        }

        .receipt-summary {
            border-top: 2px solid var(--text-gray);
            padding-top: 1.5rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8rem;
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
            font-size: 1.3rem;
        }

        .delivery-info {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .delivery-info h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .delivery-address {
            color: var(--text-gray);
            line-height: 1.6;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: var(--transition);
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: var(--text-light);
        }

        .btn-secondary {
            background: #666;
            color: var(--text-light);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-strong);
        }

        .estimated-delivery {
            background: rgba(76, 175, 80, 0.1);
            border: 1px solid #4caf50;
            color: #4caf50;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 2rem;
        }

        .rating-section {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .rating-section h3 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--text-gray);
            padding-bottom: 0.5rem;
        }

        .rating-item {
            background: var(--background-dark);
            border: 1px solid var(--text-gray);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .rating-item:last-child {
            margin-bottom: 0;
        }

        .rating-item-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .rating-item-image {
            width: 50px;
            height: 50px;
            border-radius: 6px;
            object-fit: cover;
        }

        .rating-item-name {
            font-weight: 600;
            color: var(--text-light);
        }

        .star-rating {
            display: flex;
            gap: 0.3rem;
            margin-bottom: 1rem;
            align-items: center;
        }

        .star {
            font-size: 1.8rem;
            color: #666;
            cursor: pointer;
            transition: var(--transition);
        }

        .star:hover {
            transform: scale(1.1);
        }

        .star.active {
            color: #ffc107;
        }

        .star-rating-label {
            margin-left: 0.5rem;
            color: var(--text-gray);
            font-size: 0.9rem;
        }

        .rating-comment {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--text-gray);
            border-radius: 6px;
            background: var(--card-bg);
            color: var(--text-light);
            font-family: 'Inter', sans-serif;
            resize: vertical;
            min-height: 80px;
            box-sizing: border-box;
        }

        .rating-comment:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .submit-rating-btn {
            background: var(--primary-color);
            color: var(--text-light);
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;
            margin-top: 0.5rem;
            transition: var(--transition);
        }

        .submit-rating-btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-soft);
            opacity: 0.9;
        }

        .submit-rating-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .rating-success {
            color: #4caf50;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            display: none;
        }

        .rating-success.show {
            display: block;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
            
            .receipt-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .rating-item-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="confirmation-header">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h1>Order Confirmed!</h1>
            <p>Thank you for your order. We're preparing your delicious meal.</p>
        </div>

        <div class="status-card">
            <div class="payment-status">
                <i class="fas fa-credit-card"></i> Payment Completed
            </div>
            <div class="order-number">Order #<?php echo htmlspecialchars($order_data['order_number']); ?></div>
            <p>Your order has been successfully placed and payment has been processed.</p>
        </div>

        <div class="estimated-delivery">
            <i class="fas fa-clock"></i>
            <strong>Estimated Delivery Time:</strong> <?php echo date('g:i A', strtotime('+45 minutes')); ?>
        </div>

        <div class="receipt-card">
            <div class="receipt-header">
                <h2>Order Receipt</h2>
                <p>Order Date: <?php echo date('F d, Y \a\t g:i A'); ?></p>
            </div>

            <div class="receipt-items">
                <?php foreach ($order_data['items'] as $item): ?>
                    <div class="receipt-item">
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

            <div class="receipt-summary">
                <div class="summary-row">
                    <span>Subtotal (<?php echo count($order_data['items']); ?> items)</span>
                    <span>RM <?php echo number_format($order_data['subtotal'], 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Tax (6%)</span>
                    <span>RM <?php echo number_format($order_data['tax_amount'], 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Delivery Fee</span>
                    <span>RM <?php echo number_format($order_data['delivery_fee'], 2); ?></span>
                </div>
                <div class="summary-total">
                    <span>Total</span>
                    <span>RM <?php echo number_format($order_data['total_amount'], 2); ?></span>
                </div>
            </div>

            <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--text-gray);">
                <strong>Payment Method:</strong> <?php echo htmlspecialchars($order_data['payment_method']); ?><br>
                <?php if ($order_data['special_instructions']): ?>
                    <strong>Special Instructions:</strong> <?php echo htmlspecialchars($order_data['special_instructions']); ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="delivery-info">
            <h3>Delivery Address</h3>
            <div class="delivery-address">
                <?php echo htmlspecialchars($order_data['address']['line1']); ?><br>
                <?php if ($order_data['address']['line2']): ?>
                    <?php echo htmlspecialchars($order_data['address']['line2']); ?><br>
                <?php endif; ?>
                <?php echo htmlspecialchars($order_data['address']['city'] . ', ' . $order_data['address']['state'] . ' ' . $order_data['address']['postal_code']); ?>
            </div>
        </div>

        <div class="rating-section">
            <h3><i class="fas fa-star"></i> Rate Your Order</h3>
            <p style="color: var(--text-gray); margin-bottom: 1.5rem;">Help us improve by rating the products you ordered:</p>
            
            <?php foreach ($order_data['items'] as $item): 
                $product_id = $item['product_id'];
                $existing_review = $existing_reviews[$product_id] ?? null;
                $current_rating = $existing_review ? $existing_review['rating'] : 0;
                $current_comment = $existing_review ? $existing_review['comment'] : '';
            ?>
                <div class="rating-item" data-product-id="<?php echo $product_id; ?>">
                    <div class="rating-item-header">
                        <img src="../../../food_images/<?php echo htmlspecialchars($item['image']); ?>" 
                             alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                             class="rating-item-image">
                        <div class="rating-item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                    </div>
                    
                    <div class="star-rating" data-product-id="<?php echo $product_id; ?>">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star" 
                                  data-rating="<?php echo $i; ?>" 
                                  data-product-id="<?php echo $product_id; ?>"
                                  onclick="setRating(<?php echo $product_id; ?>, <?php echo $i; ?>)"
                                  onmouseover="hoverRating(<?php echo $product_id; ?>, <?php echo $i; ?>)"
                                  onmouseout="resetRating(<?php echo $product_id; ?>)">
                                <i class="fas fa-star"></i>
                            </span>
                        <?php endfor; ?>
                        <span class="star-rating-label" id="rating-label-<?php echo $product_id; ?>">
                            <?php if ($current_rating > 0): ?>
                                <?php echo $current_rating; ?> star<?php echo $current_rating > 1 ? 's' : ''; ?>
                            <?php else: ?>
                                Click to rate
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <textarea class="rating-comment" 
                              id="comment-<?php echo $product_id; ?>" 
                              placeholder="Share your thoughts about this product (optional)..."><?php echo htmlspecialchars($current_comment); ?></textarea>
                    
                    <button type="button" 
                            class="submit-rating-btn" 
                            onclick="submitRating(<?php echo $product_id; ?>, <?php echo $order_data['order_id']; ?>)"
                            id="submit-btn-<?php echo $product_id; ?>">
                        <?php echo $existing_review ? 'Update Review' : 'Submit Review'; ?>
                    </button>
                    
                    <div class="rating-success" id="success-<?php echo $product_id; ?>">
                        <i class="fas fa-check-circle"></i> Review saved successfully!
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="action-buttons">
            <a href="../../api/download_receipt.php<?php echo isset($order_data['order_id']) ? '?order_id=' . $order_data['order_id'] : ''; ?>" class="btn btn-primary" target="_blank">
                <i class="fas fa-download"></i> Download Receipt (PDF)
            </a>
            <a href="../dashboard/Cdashboard.php?clear_session=1" class="btn btn-primary">
                <i class="fas fa-list"></i> View Order History
            </a>
            <a href="../index/index.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Continue Shopping
            </a>
        </div>
    </div>

    <script>
        // Store current ratings for each product
        const currentRatings = {};
        
        // Initialize ratings from existing reviews
        <?php foreach ($order_data['items'] as $item): 
            $product_id = $item['product_id'];
            $existing_review = $existing_reviews[$product_id] ?? null;
            $current_rating = $existing_review ? $existing_review['rating'] : 0;
        ?>
            currentRatings[<?php echo $product_id; ?>] = <?php echo $current_rating; ?>;
            if (<?php echo $current_rating; ?> > 0) {
                updateStarDisplay(<?php echo $product_id; ?>, <?php echo $current_rating; ?>);
            }
        <?php endforeach; ?>
        
        // Set rating when star is clicked
        function setRating(productId, rating) {
            currentRatings[productId] = rating;
            updateStarDisplay(productId, rating);
            updateRatingLabel(productId, rating);
        }
        
        // Hover effect on stars
        function hoverRating(productId, rating) {
            const stars = document.querySelectorAll(`.star[data-product-id="${productId}"]`);
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }
        
        // Reset to current rating when mouse leaves
        function resetRating(productId) {
            const currentRating = currentRatings[productId] || 0;
            updateStarDisplay(productId, currentRating);
        }
        
        // Update star display
        function updateStarDisplay(productId, rating) {
            const stars = document.querySelectorAll(`.star[data-product-id="${productId}"]`);
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }
        
        // Update rating label
        function updateRatingLabel(productId, rating) {
            const label = document.getElementById(`rating-label-${productId}`);
            if (rating > 0) {
                label.textContent = rating + ' star' + (rating > 1 ? 's' : '');
            } else {
                label.textContent = 'Click to rate';
            }
        }
        
        // Submit rating
        function submitRating(productId, orderId) {
            const rating = currentRatings[productId] || 0;
            const comment = document.getElementById(`comment-${productId}`).value.trim();
            const submitBtn = document.getElementById(`submit-btn-${productId}`);
            const successMsg = document.getElementById(`success-${productId}`);
            
            // Validate rating
            if (rating === 0) {
                alert('Please select a rating (1-5 stars) before submitting.');
                return;
            }
            
            // Disable button during submission
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';
            
            // Create form data
            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('product_id', productId);
            formData.append('rating', rating);
            formData.append('comment', comment);
            
            // Send AJAX request
            fetch('../../api/save_review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    successMsg.classList.add('show');
                    submitBtn.textContent = 'Update Review';
                    
                    // Hide success message after 3 seconds
                    setTimeout(() => {
                        successMsg.classList.remove('show');
                    }, 3000);
                } else {
                    alert(data.message || 'Error saving review. Please try again.');
                    submitBtn.textContent = currentRatings[productId] > 0 ? 'Update Review' : 'Submit Review';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving review. Please try again.');
                submitBtn.textContent = currentRatings[productId] > 0 ? 'Update Review' : 'Submit Review';
            })
            .finally(() => {
                submitBtn.disabled = false;
            });
        }
        
        // Remove auto-redirect to let user read the receipt
        // setTimeout(function() {
        //     window.location.href = '../dashboard/Cdashboard.php';
        // }, 10000);
    </script>
</body>
</html> 
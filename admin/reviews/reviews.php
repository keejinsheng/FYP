<?php
require_once '../../config/database.php';

// Check if admin is logged in
if (!isAdmin()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();
$success_message = '';
$error_message = '';

// Handle review approval/disapproval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_approval') {
    $review_id = (int)($_POST['review_id'] ?? 0);
    $is_approved = (int)($_POST['is_approved'] ?? 0) ? 1 : 0;
    
    if ($review_id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE review SET is_approved = ? WHERE review_id = ?");
            $stmt->execute([$is_approved, $review_id]);
            $success_message = 'Review status updated successfully';
            redirect('reviews.php?updated=1');
        } catch (Exception $e) {
            $error_message = 'Failed to update review status';
        }
    }
}

// Fetch all reviews with customer and product information
$stmt = $pdo->prepare("
    SELECT r.*, 
           u.first_name, u.last_name, u.email,
           p.product_name, p.image as product_image
    FROM review r
    JOIN user u ON r.user_id = u.user_id
    JOIN product p ON r.product_id = p.product_id
    ORDER BY r.created_at DESC
");
$stmt->execute();
$reviews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Reviews - Spice Fusion Admin</title>
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
            max-width: 1400px;
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
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-soft);
        }

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

        @keyframes slidein {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid var(--success-color);
            color: var(--success-color);
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid var(--danger-color);
            color: var(--danger-color);
        }

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

        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
        }

        .review-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-soft);
            transition: var(--transition);
        }

        .review-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-strong);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .review-customer {
            flex: 1;
        }

        .customer-name {
            color: var(--text-light);
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .customer-email {
            color: var(--text-gray);
            font-size: 0.85rem;
        }

        .review-rating {
            display: flex;
            gap: 0.25rem;
            margin-bottom: 0.5rem;
        }

        .star {
            color: var(--warning-color);
            font-size: 1rem;
        }

        .star.empty {
            color: var(--text-gray);
            opacity: 0.3;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
        }

        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
        }

        .product-name {
            color: var(--text-light);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .review-comment {
            color: var(--text-light);
            line-height: 1.6;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            min-height: 60px;
        }

        .review-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .review-date {
            color: var(--text-gray);
            font-size: 0.85rem;
        }

        .review-badges {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-block;
        }

        .badge.verified {
            background: var(--success-color);
            color: #fff;
        }

        .badge.approved {
            background: var(--success-color);
            color: #fff;
        }

        .badge.pending {
            background: var(--warning-color);
            color: #000;
        }

        .toggle-approval-btn {
            background: var(--gradient-primary);
            color: var(--text-light);
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: var(--transition);
        }

        .toggle-approval-btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-soft);
        }

        .toggle-approval-btn.disapprove {
            background: var(--danger-color);
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }

            .reviews-grid {
                grid-template-columns: 1fr;
            }

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
                <a href="../orders/order.php">Orders</a>
                <a href="../members/member.php">Customers</a>
                <a href="reviews.php" style="background: var(--primary-color);">Reviews</a>
                <a href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($_GET['updated'])): ?>
            <div class="toast" id="statusToast">Review status updated successfully.</div>
            <script>
                setTimeout(function(){
                    var t = document.getElementById('statusToast');
                    if (t) { t.style.transition = 'opacity .25s ease'; t.style.opacity = '0'; setTimeout(function(){ t.remove(); }, 300); }
                }, 2200);
            </script>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="page-header">
            <h1 class="page-title">Customer Reviews</h1>
            <a href="../dashboard/dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <?php if (empty($reviews)): ?>
            <p style="color: var(--text-gray); text-align: center; padding: 2rem;">No reviews found.</p>
        <?php else: ?>
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="reviewSearch" placeholder="Search by customer name, email, or product..." onkeyup="filterReviews()">
                </div>
            </div>
            <div class="no-results" id="noResults">No reviews found matching your search.</div>
            <div class="reviews-grid" id="reviewsGrid">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card" 
                         data-review-id="<?php echo (int)$review['review_id']; ?>"
                         data-customer-name="<?php echo htmlspecialchars(strtolower($review['first_name'] . ' ' . $review['last_name'])); ?>"
                         data-customer-email="<?php echo htmlspecialchars(strtolower($review['email'])); ?>"
                         data-product-name="<?php echo htmlspecialchars(strtolower($review['product_name'])); ?>">
                        <div class="review-header">
                            <div class="review-customer">
                                <div class="customer-name">
                                    <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>
                                </div>
                                <div class="customer-email">
                                    <?php echo htmlspecialchars($review['email']); ?>
                                </div>
                            </div>
                        </div>

                        <div class="review-rating">
                            <?php
                            $rating = (int)$review['rating'];
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $rating) {
                                    echo '<i class="fas fa-star star"></i>';
                                } else {
                                    echo '<i class="far fa-star star empty"></i>';
                                }
                            }
                            ?>
                            <span style="margin-left: 0.5rem; color: var(--text-gray); font-size: 0.85rem;"><?php echo $rating; ?>/5</span>
                        </div>

                        <div class="product-info">
                            <img src="../../food_images/<?php echo htmlspecialchars($review['product_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($review['product_name']); ?>" 
                                 class="product-image"
                                 onerror="this.src='../../images/user.jpg'">
                            <div class="product-name"><?php echo htmlspecialchars($review['product_name']); ?></div>
                        </div>

                        <?php if (!empty($review['comment'])): ?>
                            <div class="review-comment">
                                <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                            </div>
                        <?php else: ?>
                            <div class="review-comment" style="color: var(--text-gray); font-style: italic;">
                                No comment provided
                            </div>
                        <?php endif; ?>

                        <div class="review-footer">
                            <div class="review-date">
                                <i class="far fa-clock"></i> <?php echo date('M j, Y g:i A', strtotime($review['created_at'])); ?>
                            </div>
                            <div class="review-badges">
                                <?php if ($review['is_verified_purchase']): ?>
                                    <span class="badge verified">
                                        <i class="fas fa-check-circle"></i> Verified Purchase
                                    </span>
                                <?php endif; ?>
                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to <?php echo $review['is_approved'] ? 'disapprove' : 'approve'; ?> this review?');">
                                    <input type="hidden" name="action" value="toggle_approval">
                                    <input type="hidden" name="review_id" value="<?php echo (int)$review['review_id']; ?>">
                                    <input type="hidden" name="is_approved" value="<?php echo $review['is_approved'] ? '0' : '1'; ?>">
                                    <button type="submit" class="toggle-approval-btn <?php echo $review['is_approved'] ? 'disapprove' : ''; ?>">
                                        <?php if ($review['is_approved']): ?>
                                            <i class="fas fa-eye-slash"></i> Disapprove
                                        <?php else: ?>
                                            <i class="fas fa-eye"></i> Approve
                                        <?php endif; ?>
                                    </button>
                                </form>
                                <span class="badge <?php echo $review['is_approved'] ? 'approved' : 'pending'; ?>">
                                    <?php echo $review['is_approved'] ? 'Approved' : 'Pending'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function filterReviews() {
            const input = document.getElementById('reviewSearch');
            const filter = input.value.toLowerCase().trim();
            const cards = document.querySelectorAll('.review-card');
            const noResults = document.getElementById('noResults');
            let found = false;

            for (let i = 0; i < cards.length; i++) {
                const card = cards[i];
                const customerName = card.getAttribute('data-customer-name') || '';
                const customerEmail = card.getAttribute('data-customer-email') || '';
                const productName = card.getAttribute('data-product-name') || '';
                const reviewId = card.getAttribute('data-review-id') || '';
                
                const searchText = reviewId + ' ' + customerName + ' ' + customerEmail + ' ' + productName;
                
                if (searchText.includes(filter)) {
                    card.style.display = '';
                    found = true;
                } else {
                    card.style.display = 'none';
                }
            }

            if (found || filter === '') {
                noResults.classList.remove('show');
            } else {
                noResults.classList.add('show');
            }
        }
    </script>
</body>
</html>


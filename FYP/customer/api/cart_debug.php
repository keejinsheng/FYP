<?php
require_once '../../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Log function
function logError($message) {
    error_log("Cart API Error: " . $message);
}

// Check if user is logged in
if (!isLoggedIn()) {
    logError("User not logged in");
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

try {
    $pdo = getDBConnection();
    $user_id = getCurrentUserId();
    
    logError("User ID: " . $user_id);
} catch (Exception $e) {
    logError("Database connection failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Handle GET request to get cart items
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'get';
    logError("GET request with action: " . $action);
    
    if ($action === 'get') {
        try {
            $stmt = $pdo->prepare("
                SELECT sc.cart_id, sc.quantity, sc.special_instructions,
                       p.product_id, p.product_name, p.price, p.image
                FROM shopping_cart sc
                JOIN product p ON sc.product_id = p.product_id
                WHERE sc.user_id = ? AND p.is_available = 1
                ORDER BY sc.created_at DESC
            ");
            $stmt->execute([$user_id]);
            $items = $stmt->fetchAll();
            
            // Calculate total
            $total = 0;
            foreach ($items as $item) {
                $total += $item['price'] * $item['quantity'];
            }
            
            echo json_encode([
                'success' => true,
                'items' => $items,
                'total' => $total
            ]);
        } catch (Exception $e) {
            logError("Error loading cart: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error loading cart: ' . $e->getMessage()]);
        }
    }
}

// Handle POST request for cart operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_input = file_get_contents('php://input');
    logError("Raw input: " . $raw_input);
    
    $input = json_decode($raw_input, true);
    $action = $input['action'] ?? '';
    
    logError("POST request with action: " . $action);
    logError("Input data: " . print_r($input, true));
    
    switch ($action) {
        case 'add':
            $product_id = $input['product_id'] ?? 0;
            $quantity = $input['quantity'] ?? 1;
            $special_instructions = $input['special_instructions'] ?? '';
            
            logError("Adding product ID: " . $product_id . ", quantity: " . $quantity);
            
            if ($product_id <= 0) {
                logError("Invalid product ID: " . $product_id);
                echo json_encode(['success' => false, 'message' => 'Invalid product']);
                exit();
            }
            
            try {
                // Check if product exists and is available
                $stmt = $pdo->prepare("SELECT product_id, price FROM product WHERE product_id = ? AND is_available = 1");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();
                
                if (!$product) {
                    logError("Product not found or not available: " . $product_id);
                    echo json_encode(['success' => false, 'message' => 'Product not available']);
                    exit();
                }
                
                logError("Product found: " . print_r($product, true));
                
                // Check if item already in cart
                $stmt = $pdo->prepare("SELECT cart_id, quantity FROM shopping_cart WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$user_id, $product_id]);
                $existing_item = $stmt->fetch();
                
                if ($existing_item) {
                    // Update quantity
                    $new_quantity = $existing_item['quantity'] + $quantity;
                    logError("Updating existing item. Old quantity: " . $existing_item['quantity'] . ", New quantity: " . $new_quantity);
                    
                    $stmt = $pdo->prepare("UPDATE shopping_cart SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE cart_id = ?");
                    $stmt->execute([$new_quantity, $existing_item['cart_id']]);
                } else {
                    // Add new item
                    logError("Adding new item to cart");
                    $stmt = $pdo->prepare("INSERT INTO shopping_cart (user_id, product_id, quantity, special_instructions) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$user_id, $product_id, $quantity, $special_instructions]);
                }
                
                // Get updated cart count
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM shopping_cart WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $cart_count = $stmt->fetchColumn();
                
                logError("Cart count updated: " . $cart_count);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Item added to cart',
                    'cart_count' => $cart_count
                ]);
            } catch (Exception $e) {
                logError("Error adding item to cart: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error adding item to cart: ' . $e->getMessage()]);
            }
            break;
            
        case 'update':
            $cart_id = $input['cart_id'] ?? 0;
            $quantity = $input['quantity'] ?? 1;
            
            if ($cart_id <= 0 || $quantity <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                exit();
            }
            
            try {
                // Verify cart item belongs to user
                $stmt = $pdo->prepare("SELECT cart_id FROM shopping_cart WHERE cart_id = ? AND user_id = ?");
                $stmt->execute([$cart_id, $user_id]);
                
                if (!$stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Cart item not found']);
                    exit();
                }
                
                // Update quantity
                $stmt = $pdo->prepare("UPDATE shopping_cart SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE cart_id = ?");
                $stmt->execute([$quantity, $cart_id]);
                
                // Get updated cart count
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM shopping_cart WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $cart_count = $stmt->fetchColumn();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Cart updated',
                    'cart_count' => $cart_count
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error updating cart']);
            }
            break;
            
        case 'remove':
            $cart_id = $input['cart_id'] ?? 0;
            
            if ($cart_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
                exit();
            }
            
            try {
                // Verify cart item belongs to user
                $stmt = $pdo->prepare("SELECT cart_id FROM shopping_cart WHERE cart_id = ? AND user_id = ?");
                $stmt->execute([$cart_id, $user_id]);
                
                if (!$stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Cart item not found']);
                    exit();
                }
                
                // Remove item
                $stmt = $pdo->prepare("DELETE FROM shopping_cart WHERE cart_id = ?");
                $stmt->execute([$cart_id]);
                
                // Get updated cart count
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM shopping_cart WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $cart_count = $stmt->fetchColumn();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Item removed from cart',
                    'cart_count' => $cart_count
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error removing item from cart']);
            }
            break;
            
        case 'clear':
            try {
                $stmt = $pdo->prepare("DELETE FROM shopping_cart WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Cart cleared',
                    'cart_count' => 0
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error clearing cart']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
}
?> 
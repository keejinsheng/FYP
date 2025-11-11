<?php
// Start session
session_start();

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Simple database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=spicefusion;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare("
            SELECT sc.cart_id, sc.quantity, p.product_id, p.product_name, p.price, p.image
            FROM shopping_cart sc
            JOIN product p ON sc.product_id = p.product_id
            WHERE sc.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $items = $stmt->fetchAll();
        
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
        echo json_encode(['success' => false, 'message' => 'Error loading cart']);
    }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    if ($action === 'add') {
        $product_id = intval($input['product_id'] ?? 0);
        $quantity = intval($input['quantity'] ?? 1);
        
        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
            exit();
        }
        
        try {
            // Check if product exists and is available, and check stock
            $stmt = $pdo->prepare("SELECT product_id, stock_quantity, is_available FROM product WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                exit();
            }
            
            // Check if product is available
            if (!$product['is_available']) {
                echo json_encode(['success' => false, 'message' => 'This product is currently unavailable']);
                exit();
            }
            
            // Check stock quantity (stock <= 1 is considered out of stock)
            $stock = (int)$product['stock_quantity'];
            if ($stock <= 1) {
                echo json_encode(['success' => false, 'message' => 'This product is out of stock']);
                exit();
            }
            
            // Check if already in cart
            $stmt = $pdo->prepare("SELECT cart_id, quantity FROM shopping_cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update quantity - check if new total exceeds stock
                $new_qty = $existing['quantity'] + $quantity;
                if ($new_qty > $stock) {
                    echo json_encode(['success' => false, 'message' => 'Insufficient stock. Only ' . $stock . ' item(s) available.']);
                    exit();
                }
                $stmt = $pdo->prepare("UPDATE shopping_cart SET quantity = ? WHERE cart_id = ?");
                $stmt->execute([$new_qty, $existing['cart_id']]);
            } else {
                // Add new item - check if quantity exceeds stock
                if ($quantity > $stock) {
                    echo json_encode(['success' => false, 'message' => 'Insufficient stock. Only ' . $stock . ' item(s) available.']);
                    exit();
                }
                $stmt = $pdo->prepare("INSERT INTO shopping_cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $product_id, $quantity]);
            }
            
            // Get cart count (total quantity of all items)
            $stmt = $pdo->prepare("SELECT SUM(quantity) FROM shopping_cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $cart_count = $stmt->fetchColumn() ?: 0;
            
            echo json_encode([
                'success' => true,
                'message' => 'Item added to cart',
                'cart_count' => $cart_count
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error adding to cart']);
        }
    }
    
    elseif ($action === 'update') {
        $cart_id = intval($input['cart_id'] ?? 0);
        $quantity = intval($input['quantity'] ?? 1);
        
        if ($cart_id <= 0 || $quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit();
        }
        
        try {
            $stmt = $pdo->prepare("UPDATE shopping_cart SET quantity = ? WHERE cart_id = ? AND user_id = ?");
            $stmt->execute([$quantity, $cart_id, $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Cart updated']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error updating cart']);
        }
    }
    
    elseif ($action === 'remove') {
        $cart_id = intval($input['cart_id'] ?? 0);
        
        if ($cart_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
            exit();
        }
        
        try {
            $stmt = $pdo->prepare("DELETE FROM shopping_cart WHERE cart_id = ? AND user_id = ?");
            $stmt->execute([$cart_id, $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Item removed']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error removing item']);
        }
    }
    
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}
?> 
<?php
// Database configuration (env-aware with sensible defaults)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'spicefusion');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8');

// Create database connection
function getDBConnection() {
	try {
		$dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
		$pdo = new PDO($dsn, DB_USER, DB_PASS);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		// Optimize for faster transactions
		$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
		return $pdo;
	} catch(PDOException $e) {
		die("Connection failed. Please verify DB settings.\n");
	}
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to check if user is admin
function isAdmin() {
    return isset($_SESSION['admin_id']);
}

// Normalize role text (e.g., "Super Admin" -> "superadmin")
function normalizeRole($role) {
    return strtolower(preg_replace('/[^a-z]/', '', (string)$role));
}

// Helper function to check if admin is superadmin
function isSuperAdmin() {
    if (!isset($_SESSION['admin_id'])) return false;
    // Prefer DB check: treat any role containing 'super' (case-insensitive, ignoring spaces/underscores) as superadmin
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT role FROM admin_user WHERE admin_id = ? AND is_active = 1");
        $stmt->execute([$_SESSION['admin_id']]);
        $role = $stmt->fetchColumn();
        if ($role !== false && trim((string)$role) !== '') {
            $_SESSION['admin_role'] = $role; // sync session
            $norm = normalizeRole($role); // letters only, lowercase
            if ($norm === 'superadmin' || strpos($norm, 'super') !== false) {
                return true;
            }
            return false;
        }
    } catch (Exception $e) {
        // ignore and fall back
    }
    // Fallback to session role if DB not available
    $sessRole = $_SESSION['admin_role'] ?? '';
    $norm = normalizeRole($sessRole);
    return ($norm === 'superadmin' || strpos($norm, 'super') !== false);
}

// Guard: require superadmin, otherwise redirect to login
function requireSuperAdmin() {
    if (!isAdmin() || !isSuperAdmin()) {
        redirect('../auth/login.php');
    }
}

// Helper function to get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Helper function to get current admin ID
function getCurrentAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

// Helper function to redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Helper function to sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Helper function to generate random string
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length));
}

// Helper function to verify bank information against dummy_bank table
function verifyBankDetails($bank_name, $cardholder_name, $card_number, $expiry_date, $cvv) {
    try {
        $pdo = getDBConnection();
        
        // Clean and normalize all inputs
        $card_number_clean = preg_replace('/\s+/', '', trim($card_number));
        $bank_name_normalized = trim($bank_name);
        $cardholder_name_normalized = trim($cardholder_name);
        $expiry_date_normalized = trim($expiry_date);
        $cvv_normalized = trim($cvv);

        // First check if table exists
        $table_check = $pdo->query("SHOW TABLES LIKE 'dummy_bank'");
        if ($table_check->rowCount() == 0) {
            error_log("dummy_bank table does not exist");
            return false;
        }

        // Build WHERE clause to match all fields
        // Bank name is optional (for Credit Card), but if provided, it must match
        // Use case-insensitive comparison for text fields, exact match for numbers/dates
        // For card_number, remove spaces from database value for comparison
        $where_conditions = [];
        $params = [];
        
        // Add bank_name condition only if provided (required for Online Banking, optional for Credit Card)
        if (!empty($bank_name_normalized)) {
            $where_conditions[] = "LOWER(TRIM(bank_name)) = LOWER(?)";
            $params[] = $bank_name_normalized;
        }
        
        // Always check these fields
        $where_conditions[] = "LOWER(TRIM(cardholder_name)) = LOWER(?)";
        $where_conditions[] = "REPLACE(REPLACE(TRIM(card_number), ' ', ''), '-', '') = ?";
        $where_conditions[] = "TRIM(expiry_date) = ?";
        $where_conditions[] = "TRIM(cvv) = ?";
        $where_conditions[] = "is_active = 1";
        
        $params[] = $cardholder_name_normalized;
        $params[] = $card_number_clean;
        $params[] = $expiry_date_normalized;
        $params[] = $cvv_normalized;
        
        $sql = "SELECT bank_id, bank_name, cardholder_name, card_number, expiry_date, cvv 
                FROM dummy_bank 
                WHERE " . implode(" AND ", $where_conditions) . "
                LIMIT 1";
        
        // Log the SQL query and parameters for debugging
        $debug_sql = $sql;
        foreach ($params as $idx => $param) {
            $debug_sql = preg_replace('/\?/', "'" . addslashes($param) . "'", $debug_sql, 1);
        }
        error_log("Bank verification SQL: " . $debug_sql);
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $bank_info = $stmt->fetch();
        
        // If no match and bank_name was provided, try again without bank_name (for Credit Card)
        if ($bank_info === false && !empty($bank_name_normalized)) {
            $fallback_conditions = [
                "LOWER(TRIM(cardholder_name)) = LOWER(?)",
                "REPLACE(TRIM(card_number), ' ', '') = ?",
                "TRIM(expiry_date) = ?",
                "TRIM(cvv) = ?",
                "is_active = 1"
            ];
            
            $fallback_params = [
                $cardholder_name_normalized,
                $card_number_clean,
                $expiry_date_normalized,
                $cvv_normalized
            ];
            
            $fallback_sql = "SELECT bank_id, bank_name, cardholder_name, card_number, expiry_date, cvv 
                            FROM dummy_bank 
                            WHERE " . implode(" AND ", $fallback_conditions) . "
                            LIMIT 1";
            
            $fallback_stmt = $pdo->prepare($fallback_sql);
            $fallback_stmt->execute($fallback_params);
            $bank_info = $fallback_stmt->fetch();
            
            if ($bank_info !== false) {
                error_log("Bank verification SUCCESS (without bank_name match) for: $cardholder_name_normalized");
            }
        }
        
        // Debug logging - log the comparison details
        if ($bank_info === false) {
            // Get all records for comparison
            $debug_sql = "SELECT bank_name, cardholder_name, card_number, expiry_date, cvv, is_active FROM dummy_bank";
            $debug_stmt = $pdo->query($debug_sql);
            $all_banks = $debug_stmt->fetchAll();
            
            $debug_info = "=== Bank Verification Failed ===\n";
            $debug_info .= "SQL Query: " . $sql . "\n";
            $debug_info .= "Parameters: " . print_r($params, true) . "\n";
            $debug_info .= "Input values (normalized):\n";
            $debug_info .= "  Bank: '$bank_name_normalized' (length: " . strlen($bank_name_normalized) . ", empty: " . (empty($bank_name_normalized) ? 'YES' : 'NO') . ")\n";
            $debug_info .= "  Cardholder: '$cardholder_name_normalized' (length: " . strlen($cardholder_name_normalized) . ")\n";
            $debug_info .= "  Card: '$card_number_clean' (length: " . strlen($card_number_clean) . ")\n";
            $debug_info .= "  Expiry: '$expiry_date_normalized' (length: " . strlen($expiry_date_normalized) . ")\n";
            $debug_info .= "  CVV: '$cvv_normalized' (length: " . strlen($cvv_normalized) . ")\n\n";
            $debug_info .= "All database records:\n";
            foreach ($all_banks as $idx => $bank) {
                $active_status = $bank['is_active'] ? 'ACTIVE' : 'INACTIVE';
                $debug_info .= "  Record #" . ($idx + 1) . " ($active_status):\n";
                $db_bank = trim($bank['bank_name']);
                $db_cardholder = trim($bank['cardholder_name']);
                $db_card = trim($bank['card_number']);
                $db_expiry = trim($bank['expiry_date']);
                $db_cvv = trim($bank['cvv']);
                
                $debug_info .= "    Bank: '$db_bank' (length: " . strlen($db_bank) . ")\n";
                $debug_info .= "    Cardholder: '$db_cardholder' (length: " . strlen($db_cardholder) . ")\n";
                $debug_info .= "    Card: '$db_card' (length: " . strlen($db_card) . ")\n";
                $debug_info .= "    Expiry: '$db_expiry' (length: " . strlen($db_expiry) . ")\n";
                $debug_info .= "    CVV: '$db_cvv' (length: " . strlen($db_cvv) . ")\n";
                
                // Check each field individually
                $bank_match = empty($bank_name_normalized) ? true : (strtolower($db_bank) === strtolower($bank_name_normalized));
                $cardholder_match = (strtolower($db_cardholder) === strtolower($cardholder_name_normalized));
                $card_match = ($db_card === $card_number_clean);
                $expiry_match = ($db_expiry === $expiry_date_normalized);
                $cvv_match = ($db_cvv === $cvv_normalized);
                
                $debug_info .= "    Field-by-field comparison:\n";
                $debug_info .= "      Bank: " . ($bank_match ? 'MATCH' : "NO MATCH - DB: '$db_bank' vs Input: '$bank_name_normalized'") . "\n";
                $debug_info .= "      Cardholder: " . ($cardholder_match ? 'MATCH' : "NO MATCH - DB: '$db_cardholder' vs Input: '$cardholder_name_normalized'") . "\n";
                $debug_info .= "      Card: " . ($card_match ? 'MATCH' : "NO MATCH - DB: '$db_card' vs Input: '$card_number_clean'") . "\n";
                $debug_info .= "      Expiry: " . ($expiry_match ? 'MATCH' : "NO MATCH - DB: '$db_expiry' vs Input: '$expiry_date_normalized'") . "\n";
                $debug_info .= "      CVV: " . ($cvv_match ? 'MATCH' : "NO MATCH - DB: '$db_cvv' vs Input: '$cvv_normalized'") . "\n";
                $debug_info .= "      Active: " . ($bank['is_active'] ? 'YES' : 'NO') . "\n\n";
            }
            error_log($debug_info);
        } else {
            error_log("Bank verification SUCCESS for: $bank_name_normalized, $cardholder_name_normalized");
        }
        
        return $bank_info !== false;
    } catch (Exception $e) {
        // If table doesn't exist or error occurs, return false for security
        error_log("Bank verification error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return false;
    }
}

?> 
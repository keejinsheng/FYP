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
?> 
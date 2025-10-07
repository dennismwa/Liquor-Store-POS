<?php
// ==================== SECURE CONFIG.PHP ====================
// Updated with security enhancements and environment variables

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Include security functions
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/validation.php';

// Start secure session
secureSessionStart();

// Timezone Configuration
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Africa/Nairobi');

// Error Reporting
if (getenv('APP_DEBUG') === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
}

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Database Configuration using environment variables
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));
define('DB_NAME', getenv('DB_NAME'));
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

// Connect to Database with error handling
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        
        if (getenv('APP_DEBUG') === 'true') {
            die("Database connection failed: " . $conn->connect_error);
        } else {
            die("Database connection failed. Please contact administrator.");
        }
    }
    
    $conn->set_charset(DB_CHARSET);
    
    // Set SQL mode for better data integrity
    $conn->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    
    if (getenv('APP_DEBUG') === 'true') {
        die("Database error: " . $e->getMessage());
    } else {
        die("Database error. Please contact administrator.");
    }
}

// ==================== SECURE HELPER FUNCTIONS ====================

/**
 * Sanitize input (DEPRECATED - Use validation.php instead)
 * Kept for backward compatibility
 */
function sanitize($data) {
    global $conn;
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return $conn->real_escape_string(trim($data));
}

/**
 * Secure database query with prepared statement
 */
function dbQuery($query, $params = [], $types = '') {
    global $conn;
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Query preparation failed: " . $conn->error);
        return false;
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        error_log("Query execution failed: " . $stmt->error);
        return false;
    }
    
    return $stmt;
}

/**
 * Secure SELECT query
 */
function dbSelect($query, $params = [], $types = '') {
    $stmt = dbQuery($query, $params, $types);
    if (!$stmt) return false;
    
    $result = $stmt->get_result();
    $stmt->close();
    
    return $result;
}

/**
 * Secure INSERT query
 */
function dbInsert($query, $params = [], $types = '') {
    global $conn;
    $stmt = dbQuery($query, $params, $types);
    if (!$stmt) return false;
    
    $insertId = $conn->insert_id;
    $stmt->close();
    
    return $insertId;
}

/**
 * Secure UPDATE/DELETE query
 */
function dbExecute($query, $params = [], $types = '') {
    $stmt = dbQuery($query, $params, $types);
    if (!$stmt) return false;
    
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    return $affected;
}

/**
 * JSON response
 */
function respond($success, $message = '', $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => time()
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

/**
 * API response (alias for respond)
 */
function apiRespond($success, $message = '', $data = null, $httpCode = 200) {
    respond($success, $message, $data, $httpCode);
}

/**
 * Require authentication
 */
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        if (isAjaxRequest()) {
            respond(false, 'Unauthorized', null, 401);
        } else {
            header('Location: /index.php');
            exit;
        }
    }
    
    // Check session timeout
    $timeout = getenv('SESSION_LIFETIME') ?: 7200;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        session_unset();
        session_destroy();
        
        if (isAjaxRequest()) {
            respond(false, 'Session expired', null, 401);
        } else {
            header('Location: /index.php?expired=1');
            exit;
        }
    }
    
    $_SESSION['last_activity'] = time();
}

/**
 * Require owner role
 */
function requireOwner() {
    requireAuth();
    
    if ($_SESSION['role'] !== 'owner') {
        if (isAjaxRequest()) {
            respond(false, 'Forbidden', null, 403);
        } else {
            header('Location: /403.php');
            exit;
        }
    }
}

/**
 * Log activity
 */
function logActivity($action, $description = '') {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) return false;
    
    $userId = (int)$_SESSION['user_id'];
    $action = sanitize($action);
    $description = sanitize($description);
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isss", $userId, $action, $description, $ipAddress);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    return false;
}

/**
 * Get settings with caching
 */
function getSettings() {
    global $conn;
    
    // Check cache
    if (isset($_SESSION['settings_cache']) && isset($_SESSION['settings_cache_time'])) {
        $cacheAge = time() - $_SESSION['settings_cache_time'];
        $cacheTTL = getenv('CACHE_TTL') ?: 3600;
        
        if ($cacheAge < $cacheTTL) {
            return $_SESSION['settings_cache'];
        }
    }
    
    $result = $conn->query("SELECT * FROM settings WHERE id = 1");
    if ($result && $result->num_rows > 0) {
        $settings = $result->fetch_assoc();
        $_SESSION['settings_cache'] = $settings;
        $_SESSION['settings_cache_time'] = time();
        return $settings;
    }
    
    // Default settings
    $defaults = [
        'company_name' => getenv('APP_NAME') ?: 'Zuri Wines & Spirits',
        'logo_path' => '/logo.jpg',
        'primary_color' => '#ea580c',
        'secondary_color' => '#ffffff',
        'currency' => 'KSh',
        'currency_symbol' => 'KSh',
        'tax_rate' => getenv('DEFAULT_TAX_RATE') ?: 0,
        'receipt_footer' => '',
        'barcode_scanner_enabled' => 1,
        'low_stock_alert_enabled' => 1
    ];
    
    $_SESSION['settings_cache'] = $defaults;
    $_SESSION['settings_cache_time'] = time();
    
    return $defaults;
}

/**
 * Clear settings cache
 */
function clearSettingsCache() {
    unset($_SESSION['settings_cache']);
    unset($_SESSION['settings_cache_time']);
}

/**
 * Generate sale number
 */
function generateSaleNumber() {
    return 'ZWS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    $settings = getSettings();
    return $settings['currency'] . ' ' . number_format($amount, 2);
}

/**
 * Format date
 */
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime($datetime) {
    return date('M d, Y h:i A', strtotime($datetime));
}

/**
 * Get current datetime
 */
function getCurrentDateTime() {
    return date('Y-m-d H:i:s');
}

/**
 * Get client IP
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Get product (secure)
 */
function getProduct($productId) {
    global $conn;
    $productId = (int)$productId;
    
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    return $product;
}

/**
 * Update product stock (secure)
 */
function updateProductStock($productId, $quantity, $operation = 'subtract') {
    global $conn;
    $productId = (int)$productId;
    $quantity = (int)$quantity;
    
    if ($operation === 'subtract') {
        $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?");
        $stmt->bind_param("iii", $quantity, $productId, $quantity);
    } elseif ($operation === 'add') {
        $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
        $stmt->bind_param("ii", $quantity, $productId);
    } else {
        return false;
    }
    
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

/**
 * Record stock movement (secure)
 */
function recordStockMovement($productId, $userId, $movementType, $quantity, $referenceType = null, $referenceId = null, $notes = '') {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO stock_movements (product_id, user_id, movement_type, quantity, reference_type, reference_id, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("iisisss", $productId, $userId, $movementType, $quantity, $referenceType, $referenceId, $notes);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

/**
 * Get stock status
 */
function getStockStatus($currentStock, $reorderLevel) {
    if ($currentStock <= 0) {
        return [
            'status' => 'out',
            'label' => 'Out of Stock',
            'color' => 'red'
        ];
    } elseif ($currentStock <= $reorderLevel) {
        return [
            'status' => 'low',
            'label' => 'Low Stock',
            'color' => 'orange'
        ];
    } else {
        return [
            'status' => 'good',
            'label' => 'In Stock',
            'color' => 'green'
        ];
    }
}

/**
 * Get user info (secure)
 */
function getUserInfo() {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) return null;
    
    $userId = (int)$_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    return $user;
}

/**
 * Flash messages
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Get low stock count
 */
function getLowStockCount() {
    global $conn;
    $result = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= reorder_level AND stock_quantity > 0 AND status = 'active'");
    return $result ? $result->fetch_assoc()['count'] : 0;
}

/**
 * Get today's sales
 */
function getTodaySales() {
    global $conn;
    $today = date('Y-m-d');
    $result = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM sales WHERE DATE(sale_date) = '$today'");
    return $result ? $result->fetch_assoc() : ['count' => 0, 'total' => 0];
}

/**
 * Feature flags
 */
function featureEnabled($feature) {
    $key = 'FEATURE_' . strtoupper($feature);
    return getenv($key) === 'true';
}

/**
 * Check if in production
 */
function isProduction() {
    return getenv('APP_ENV') === 'production';
}

/**
 * Check if debugging
 */
function isDebug() {
    return getenv('APP_DEBUG') === 'true';
}

/**
 * Get app URL
 */
function appUrl($path = '') {
    $url = getenv('APP_URL') ?: 'http://localhost';
    return rtrim($url, '/') . '/' . ltrim($path, '/');
}

/**
 * Validate sale data
 */
function validateSaleData($items, $totalAmount, $amountPaid) {
    if (empty($items)) {
        return ['valid' => false, 'message' => 'No items in cart'];
    }
    
    if ($totalAmount <= 0) {
        return ['valid' => false, 'message' => 'Invalid total amount'];
    }
    
    if ($amountPaid < $totalAmount) {
        return ['valid' => false, 'message' => 'Insufficient payment amount'];
    }
    
    return ['valid' => true];
}

// Sanitize all POST data on every request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !defined('SKIP_POST_SANITIZATION')) {
    sanitizePostData();
}

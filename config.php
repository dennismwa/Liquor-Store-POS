<?php
// Start session FIRST before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone Configuration
date_default_timezone_set('Africa/Nairobi');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'vxjtgclw_Spirits');
define('DB_PASS', 'SGL~3^5O?]Xie%!6');
define('DB_NAME', 'vxjtgclw_Spirits');

// Connect to Database
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("Database connection failed. Please contact administrator.");
    }
    
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    die("Database error. Please contact administrator.");
}

// Helper Functions
function sanitize($data) {
    global $conn;
    return $conn->real_escape_string(trim($data));
}

function respond($success, $message = '', $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            respond(false, 'Unauthorized');
        } else {
            header('Location: /index.php');
            exit;
        }
    }
}

function requireOwner() {
    requireAuth();
    if ($_SESSION['role'] !== 'owner') {
        header('Location: /403.php');
        exit;
    }
}

function logActivity($action, $description = '') {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) return;
    
    $user_id = (int)$_SESSION['user_id'];
    $action = sanitize($action);
    $description = sanitize($description);
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isss", $user_id, $action, $description, $ip_address);
        $stmt->execute();
        $stmt->close();
    }
}

function getSettings() {
    global $conn;
    
    // Check cache
    if (isset($_SESSION['settings_cache'])) {
        return $_SESSION['settings_cache'];
    }
    
    $result = $conn->query("SELECT * FROM settings WHERE id = 1");
    if ($result && $result->num_rows > 0) {
        $settings = $result->fetch_assoc();
        $_SESSION['settings_cache'] = $settings;
        return $settings;
    }
    
    // Default settings
    $defaults = [
        'company_name' => 'Zuri Wines & Spirits',
        'logo_path' => '/logo.jpg',
        'primary_color' => '#ea580c',
        'secondary_color' => '#ffffff',
        'currency' => 'KSh',
        'currency_symbol' => 'KSh',
        'tax_rate' => 0,
        'receipt_footer' => '',
        'barcode_scanner_enabled' => 1,
        'low_stock_alert_enabled' => 1
    ];
    
    $_SESSION['settings_cache'] = $defaults;
    return $defaults;
}

function generateSaleNumber() {
    return 'ZWS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function formatCurrency($amount) {
    $settings = getSettings();
    return $settings['currency'] . ' ' . number_format($amount, 2);
}

function getCurrentDateTime() {
    return date('Y-m-d H:i:s');
}

function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function getProduct($productId) {
    global $conn;
    $productId = (int)$productId;
    $result = $conn->query("SELECT * FROM products WHERE id = $productId AND status = 'active'");
    return $result ? $result->fetch_assoc() : null;
}

function updateProductStock($productId, $quantity, $operation = 'subtract') {
    global $conn;
    $productId = (int)$productId;
    $quantity = (int)$quantity;
    
    if ($operation === 'subtract') {
        $sql = "UPDATE products SET stock_quantity = stock_quantity - $quantity WHERE id = $productId AND stock_quantity >= $quantity";
    } elseif ($operation === 'add') {
        $sql = "UPDATE products SET stock_quantity = stock_quantity + $quantity WHERE id = $productId";
    } else {
        return false;
    }
    
    return $conn->query($sql);
}

function recordStockMovement($productId, $userId, $movementType, $quantity, $referenceType = null, $referenceId = null, $notes = '') {
    global $conn;
    
    $productId = (int)$productId;
    $userId = (int)$userId;
    $movementType = sanitize($movementType);
    $quantity = (int)$quantity;
    $referenceType = $referenceType ? sanitize($referenceType) : null;
    $referenceId = $referenceId ? (int)$referenceId : null;
    $notes = sanitize($notes);
    
    $stmt = $conn->prepare("INSERT INTO stock_movements (product_id, user_id, movement_type, quantity, reference_type, reference_id, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iisisss", $productId, $userId, $movementType, $quantity, $referenceType, $referenceId, $notes);
        $stmt->execute();
        $stmt->close();
        return true;
    }
    return false;
}

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

function getUserInfo() {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) return null;
    
    $userId = (int)$_SESSION['user_id'];
    $result = $conn->query("SELECT * FROM users WHERE id = $userId AND status = 'active'");
    return $result ? $result->fetch_assoc() : null;
}

function isValidPIN($pin) {
    return preg_match('/^\d{4}$/', $pin);
}

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

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('M d, Y h:i A', strtotime($datetime));
}

// Clear settings cache when needed
function clearSettingsCache() {
    unset($_SESSION['settings_cache']);
}

// Check if user has permission
function hasPermission($permission) {
    if (!isset($_SESSION['user_id'])) return false;
    if ($_SESSION['role'] === 'owner') return true;
    
    $userInfo = getUserInfo();
    if (!$userInfo) return false;
    
    $permissions = json_decode($userInfo['permissions'], true);
    return in_array('all', $permissions) || in_array($permission, $permissions);
}

// API response helper with better error handling
function apiRespond($success, $message = '', $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => time()
    ], JSON_PRETTY_PRINT);
    exit;
}

// Validate sale data
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

// Get low stock products count
function getLowStockCount() {
    global $conn;
    $result = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= reorder_level AND stock_quantity > 0 AND status = 'active'");
    return $result ? $result->fetch_assoc()['count'] : 0;
}

// Get today's sales summary
function getTodaySales() {
    global $conn;
    $today = date('Y-m-d');
    $result = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM sales WHERE DATE(sale_date) = '$today'");
    return $result ? $result->fetch_assoc() : ['count' => 0, 'total' => 0];
}

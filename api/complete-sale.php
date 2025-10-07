<?php
require_once '../config.php';
requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method');
}

// Validate input
if (!isset($_POST['items']) || empty($_POST['items'])) {
    respond(false, 'No items in cart');
}

$items = json_decode($_POST['items'], true);
$paymentMethod = sanitize($_POST['payment_method']);
$mpesaReference = isset($_POST['mpesa_reference']) ? sanitize($_POST['mpesa_reference']) : null;
$subtotal = floatval($_POST['subtotal']);
$taxAmount = floatval($_POST['tax_amount']);
$totalAmount = floatval($_POST['total_amount']);
$amountPaid = floatval($_POST['amount_paid']);
$changeAmount = floatval($_POST['change_amount']);

// Validate payment
if ($amountPaid < $totalAmount) {
    respond(false, 'Insufficient payment amount');
}

// Start transaction
$conn->begin_transaction();

try {
    // Generate sale number
    $saleNumber = 'ZWS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    $userId = $_SESSION['user_id'];
    $saleDate = date('Y-m-d H:i:s');
    
    // Insert sale
    $stmt = $conn->prepare("INSERT INTO sales (sale_number, user_id, subtotal, tax_amount, total_amount, payment_method, mpesa_reference, amount_paid, change_amount, sale_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sidddssdds", $saleNumber, $userId, $subtotal, $taxAmount, $totalAmount, $paymentMethod, $mpesaReference, $amountPaid, $changeAmount, $saleDate);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create sale: ' . $stmt->error);
    }
    
    $saleId = $conn->insert_id;
    $stmt->close();
    
    // Insert sale items and update stock
    $stmtItem = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtStock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?");
    $stmtMovement = $conn->prepare("INSERT INTO stock_movements (product_id, user_id, movement_type, quantity, reference_type, reference_id, notes) VALUES (?, ?, 'sale', ?, 'sale', ?, ?)");
    
    foreach ($items as $item) {
        $productId = intval($item['id']);
        $quantity = intval($item['quantity']);
        $unitPrice = floatval($item['price']);
        $itemSubtotal = $quantity * $unitPrice;
        $productName = sanitize($item['name']);
        
        // Check stock availability
        $checkStock = $conn->query("SELECT stock_quantity FROM products WHERE id = $productId");
        if (!$checkStock || $checkStock->num_rows === 0) {
            throw new Exception("Product not found: $productName");
        }
        
        $currentStock = $checkStock->fetch_assoc()['stock_quantity'];
        if ($currentStock < $quantity) {
            throw new Exception("Insufficient stock for: $productName (Available: $currentStock, Requested: $quantity)");
        }
        
        // Insert sale item
        $stmtItem->bind_param("iisidd", $saleId, $productId, $productName, $quantity, $unitPrice, $itemSubtotal);
        if (!$stmtItem->execute()) {
            throw new Exception('Failed to add sale item: ' . $stmtItem->error);
        }
        
        // Update product stock
        $stmtStock->bind_param("iii", $quantity, $productId, $quantity);
        if (!$stmtStock->execute() || $stmtStock->affected_rows === 0) {
            throw new Exception("Failed to update stock for: $productName");
        }
        
        // Record stock movement
        $notes = "Sale: $saleNumber";
        $stmtMovement->bind_param("iiiis", $productId, $userId, $quantity, $saleId, $notes);
        $stmtMovement->execute();
    }
    
    $stmtItem->close();
    $stmtStock->close();
    $stmtMovement->close();
    
    // Log activity
    $logStmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, 'SALE_COMPLETED', ?, ?)");
    $logDesc = "Completed sale $saleNumber with total $totalAmount";
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $logStmt->bind_param("iss", $userId, $logDesc, $ipAddress);
    $logStmt->execute();
    $logStmt->close();
    
    // Commit transaction
    $conn->commit();
    
    respond(true, 'Sale completed successfully', [
        'sale_id' => $saleId,
        'sale_number' => $saleNumber,
        'total' => $totalAmount,
        'change' => $changeAmount
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    respond(false, $e->getMessage());
}
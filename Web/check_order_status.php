<?php
session_start();
require_once('db-connect.php');

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || !isset($_SESSION['OrderID'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid session']);
    exit();
}

try {
    $pdo = getDatabaseConnection('customer');

    // Check if all orders are served
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as unserved_count 
        FROM OrderItem 
        WHERE OrderID = ? AND Status = 0
    ");
    
    $stmt->execute([$_SESSION['OrderID']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'allServed' => ($result['unserved_count'] == 0)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error checking order status'
    ]);
    
    error_log("Order status check error: " . $e->getMessage());
}
<?php
session_start();
require_once('db-connect.php');

header('Content-Type: application/json');

// Check for customer privileges and required session variables
if (!isset($_SESSION['userType']) || 
    $_SESSION['userType'] !== 'customer' ||
    !isset($_SESSION['MemberID']) || 
    !isset($_SESSION['TableNo']) ||
    !isset($_SESSION['OrderID'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid session. Please log in again.'
    ]);
    exit();
}

try {
    $pdo = getDatabaseConnection('customer');
    
    // Get and validate the order data
    $orderData = json_decode(file_get_contents('php://input'), true);
    
    if (empty($orderData)) {
        throw new Exception('No items in order');
    }

    // Validate maximum order quantity
    $totalItems = array_sum(array_column($orderData, 'quantity'));
    if ($totalItems > 4) {
        throw new Exception('Maximum order quantity exceeded');
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Always use the OrderID from the current session
        $orderID = $_SESSION['OrderID'];

        // Validate menu items exist before inserting
        $stmt = $pdo->prepare("SELECT MenuItemName FROM MenuItem WHERE MenuItemName = ?");
        
        // Insert order items
        $insertStmt = $pdo->prepare("
            INSERT INTO OrderItem (OrderID, MenuItemName, ItemAmount, Status) 
            VALUES (?, ?, ?, 0)
        ");
        
        foreach ($orderData as $item) {
            // Verify menu item exists
            $stmt->execute([$item['name']]);
            if (!$stmt->fetch()) {
                throw new Exception('Invalid menu item: ' . $item['name']);
            }

            // Validate quantity
            if ($item['quantity'] <= 0 || $item['quantity'] > 4) {
                throw new Exception('Invalid quantity for item: ' . $item['name']);
            }

            // Insert the order item
            $insertStmt->execute([
                $orderID,
                $item['name'],
                $item['quantity']
            ]);
        }

        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Order placed successfully',
            'orderID' => $orderID
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error processing order: ' . $e->getMessage()
    ]);

    // Log error with additional context
    error_log(sprintf(
        "Order processing error - User: %s, Table: %s, OrderID: %s, Error: %s",
        $_SESSION['MemberID'] ?? 'unknown',
        $_SESSION['TableNo'] ?? 'unknown',
        $_SESSION['OrderID'] ?? 'unknown',
        $e->getMessage()
    ));
}
?>
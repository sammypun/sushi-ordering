<?php
session_start();
require_once('db-connect.php');

try {
    // Get appropriate connection based on user type
    $userType = isset($_SESSION['isAdmin']) ? 'admin' : 'customer';
    $pdo = getDatabaseConnection($userType);
    
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // Clear table assignment for the customer if they're not an admin
        if (isset($_SESSION['MemberID']) && !isset($_SESSION['isAdmin'])) {
            // Check for any unpaid orders
            $stmt = $pdo->prepare("
                SELECT o.OrderID 
                FROM Order_ o
                LEFT JOIN Payment p ON o.OrderID = p.OrderID
                WHERE o.MemberID = ? 
                AND p.OrderID IS NULL
            ");
            $stmt->execute([$_SESSION['MemberID']]);
            $unpaidOrders = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($unpaidOrders)) {
                // Log unpaid orders for administrative purposes
                error_log("User " . $_SESSION['MemberID'] . " logged out with unpaid orders: " . implode(', ', $unpaidOrders));
                
                // Optional: Mark unpaid order items as cancelled
                $updateOrderItems = $pdo->prepare("
                    UPDATE OrderItem 
                    SET Status = -1 
                    WHERE OrderID = ? 
                    AND Status = 0
                ");
                
                foreach ($unpaidOrders as $orderId) {
                    $updateOrderItems->execute([$orderId]);
                }
            }

            // Clear the table assignment
            $stmt = $pdo->prepare("UPDATE Customer SET TableNO = NULL WHERE MemberID = ?");
            $stmt->execute([$_SESSION['MemberID']]);
        }
        
        $pdo->commit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Logout process error: " . $e->getMessage());
    }
    
} catch(PDOException $e) {
    // Log error instead of displaying it (for security)
    error_log("Database error during logout: " . $e->getMessage());
}

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>
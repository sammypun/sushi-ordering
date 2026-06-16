<?php
session_start();
require_once('db-connect.php');

if (isset($_GET['id'])) {
    try {
        $pdo = getDatabaseConnection('admin');
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            $orderId = intval($_GET['id']);
            
            // First delete related records in OrderItem
            $stmt = $pdo->prepare("DELETE FROM OrderItem WHERE OrderID = ?");
            $stmt->execute([$orderId]);
            
            // Then delete the order
            $stmt = $pdo->prepare("DELETE FROM Order_ WHERE OrderID = ?");
            $stmt->execute([$orderId]);
            
            $pdo->commit();
            
            header("Location: admin-order.php?message=Order successfully deleted");
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            header("Location: admin-order.php?error=" . urlencode("Error deleting order: " . $e->getMessage()));
            exit();
        }
    } catch (PDOException $e) {
        header("Location: admin-order.php?error=" . urlencode("Database connection failed: " . $e->getMessage()));
        exit();
    }
}

header("Location: admin-order.php?error=No order ID provided");
exit();
?>
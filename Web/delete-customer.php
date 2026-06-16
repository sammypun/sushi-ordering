<?php
session_start();
require_once('db-connect.php');

try {
    $pdo = getDatabaseConnection('admin');

    if (isset($_GET['id'])) {
        $memberID = $_GET['id'];
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // First, delete related records in Payment table
            $stmt = $pdo->prepare("DELETE FROM Payment WHERE MemberID = ?");
            $stmt->execute([$memberID]);
            
            // Delete related records in OrderItem through Order_
            $stmt = $pdo->prepare("
                DELETE oi FROM OrderItem oi
                INNER JOIN Order_ o ON oi.OrderID = o.OrderID
                WHERE o.MemberID = ?
            ");
            $stmt->execute([$memberID]);
            
            // Delete records in Order_ table
            $stmt = $pdo->prepare("DELETE FROM Order_ WHERE MemberID = ?");
            $stmt->execute([$memberID]);
            
            // Finally, delete the customer
            $stmt = $pdo->prepare("DELETE FROM Customer WHERE MemberID = ?");
            $stmt->execute([$memberID]);
            
            // Commit transaction
            $pdo->commit();
            
            header("Location: admin-customer.php?message=Customer successfully deleted");
            exit();
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            header("Location: admin-customer.php?error=" . urlencode("Error deleting customer: " . $e->getMessage()));
            exit();
        }
    } else {
        header("Location: admin-customer.php?error=No customer ID provided");
        exit();
    }
    
} catch(PDOException $e) {
    header("Location: admin-customer.php?error=" . urlencode("Database connection failed: " . $e->getMessage()));
    exit();
}
?>
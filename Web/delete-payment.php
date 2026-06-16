<?php
session_start();
require_once('db-connect.php');

if (isset($_GET['id'])) {
    try {
        $pdo = getDatabaseConnection('admin');
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            $paymentNo = intval($_GET['id']);
            
            $stmt = $pdo->prepare("DELETE FROM Payment WHERE `PaymentNo.` = ?");
            $stmt->execute([$paymentNo]);
            
            $pdo->commit();
            
            header("Location: admin-payment.php?message=Payment successfully deleted");
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            header("Location: admin-payment.php?error=" . urlencode("Error deleting payment: " . $e->getMessage()));
            exit();
        }
    } catch (PDOException $e) {
        header("Location: admin-payment.php?error=" . urlencode("Database connection failed: " . $e->getMessage()));
        exit();
    }
}

header("Location: admin-payment.php?error=No payment ID provided");
exit();
?>
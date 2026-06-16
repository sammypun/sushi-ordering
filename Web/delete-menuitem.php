<?php
session_start();
require_once('db-connect.php');

try {
    $pdo = getDatabaseConnection('admin');

    if (isset($_GET['name'])) {
        $menuItemName = $_GET['name'];
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // First, delete related records in OrderItem
            $stmt = $pdo->prepare("DELETE FROM OrderItem WHERE MenuItemName = ?");
            $stmt->execute([$menuItemName]);
            
            // Then delete the menu item
            $stmt = $pdo->prepare("DELETE FROM MenuItem WHERE MenuItemName = ?");
            $stmt->execute([$menuItemName]);
            
            // Commit transaction
            $pdo->commit();
            
            header("Location: admin-menuitem.php?message=Menu item successfully deleted");
            exit();
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            header("Location: admin-menuitem.php?error=" . urlencode("Error deleting menu item: " . $e->getMessage()));
            exit();
        }
    } else {
        header("Location: admin-menuitem.php?error=No menu item name provided");
        exit();
    }
    
} catch(PDOException $e) {
    header("Location: admin-menuitem.php?error=" . urlencode("Database connection failed: " . $e->getMessage()));
    exit();
}
?>
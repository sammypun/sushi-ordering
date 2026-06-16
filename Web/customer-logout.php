<?php
session_start();
require_once('db-connect.php');

try {
    // Use the customer connection since we're just updating customer table
    $pdo = getDatabaseConnection('customer');

    // Clear the table number in the database
    if (isset($_SESSION['MemberID'])) {
        $stmt = $pdo->prepare("UPDATE Customer SET TableNO = NULL WHERE MemberID = ?");
        $stmt->execute([$_SESSION['MemberID']]);
    }
} catch(PDOException $e) {
    // Log error if needed
    error_log("Logout error: " . $e->getMessage());
}

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>
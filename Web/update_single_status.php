<?php
session_start();
require_once('db-connect.php');

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['orderItemId']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

try {
    $pdo = getDatabaseConnection('customer');

    $stmt = $pdo->prepare("
        UPDATE OrderItem 
        SET Status = :status 
        WHERE OrderItemID = :orderItemId 
        AND OrderID IN (
            SELECT OrderID 
            FROM Order_ 
            WHERE MemberID = :memberId 
            AND TableNO = :tableNo
        )
    ");

    $stmt->execute([
        ':status' => $data['status'] ? 1 : 0,
        ':orderItemId' => $data['orderItemId'],
        ':memberId' => $_SESSION['MemberID'],
        ':tableNo' => $_SESSION['TableNo']
    ]);
    
    echo json_encode(['success' => true]);

} catch(PDOException $e) {
    error_log("Error updating order status: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
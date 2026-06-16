<?php
session_start();
require_once('db-connect.php');
require_once('encryption_util.php');

date_default_timezone_set('Asia/Bangkok');

if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'customer') {
    header("Location: login.php");
    exit();
}

try {
    $pdo = getDatabaseConnection('customer');

    // Get and decrypt customer information
    $customerStmt = $pdo->prepare("
        SELECT * FROM Customer WHERE MemberID = ?
    ");
    $customerStmt->execute([$_SESSION['MemberID']]);
    $customerData = $customerStmt->fetch(PDO::FETCH_ASSOC);
    
    // Decrypt sensitive information
    $customer = [
        'FirstName' => $customerData['FirstName'],
        'LastName' => $customerData['LastName'],
        'PhoneNumber' => decryptData($customerData['PhoneNumber'], $customerData['EncryptionKey']),
        'TableNO' => $customerData['TableNO'],
        'Points' => $customerData['Points'],
        'EncryptedPhone' => $customerData['PhoneNumber'] // Keep encrypted version for payment
    ];

    // Get unpaid orders
    $orderStmt = $pdo->prepare("
        SELECT DISTINCT o.OrderID 
        FROM Order_ o
        WHERE o.MemberID = ? AND o.TableNO = ?
        AND NOT EXISTS (
            SELECT 1 FROM Payment p 
            WHERE p.OrderID = o.OrderID
        )
    ");
    $orderStmt->execute([$_SESSION['MemberID'], $customer['TableNO']]);
    $orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

    // Initialize arrays
    $orderDetails = [];
    $totalItems = 0;
    $grandTotal = 0;
    $orderIDs = [];

    // Get items for each order
    $itemStmt = $pdo->prepare("
        SELECT 
            oi.OrderID,
            oi.MenuItemName,
            oi.ItemAmount,
            m.PlateColor,
            m.Price
        FROM OrderItem oi
        JOIN MenuItem m ON oi.MenuItemName = m.MenuItemName
        WHERE oi.OrderID = ?
    ");

    foreach ($orders as $order) {
        $orderIDs[] = $order['OrderID'];
        $itemStmt->execute([$order['OrderID']]);
        $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($items as $item) {
            $totalItems += $item['ItemAmount'];
            $subtotal = $item['ItemAmount'] * $item['Price'];
            $grandTotal += $subtotal;
            
            $orderDetails[$item['PlateColor']][] = [
                'amount' => $item['ItemAmount'],
                'price' => $item['Price'],
                'subtotal' => $subtotal
            ];
        }
    }

    $pointsToEarn = floor($grandTotal / 10);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finish'])) {
        $pdo->beginTransaction();
        
        try {
            // Update customer points and clear table
            $updatePointsStmt = $pdo->prepare("
                UPDATE Customer 
                SET Points = Points + ?,
                    TableNO = NULL
                WHERE MemberID = ?
            ");
            $updatePointsStmt->execute([$pointsToEarn, $_SESSION['MemberID']]);

            // Get next payment number
            $nextPaymentStmt = $pdo->query("SELECT COALESCE(MAX(`PaymentNo.`), 0) + 1 as nextPayment FROM Payment");
            $nextPayment = $nextPaymentStmt->fetch(PDO::FETCH_ASSOC)['nextPayment'];

            // Create payment records using encrypted phone number
            $createPaymentStmt = $pdo->prepare("
                INSERT INTO Payment 
                (`PaymentNo.`, MemberID, FirstName, PhoneNumber, TableNO, OrderID, PaymentTotalPrice, DateTime, PointsEarned)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ");

            foreach ($orderIDs as $orderID) {
                $createPaymentStmt->execute([
                    $nextPayment,
                    $_SESSION['MemberID'],
                    $customer['FirstName'],
                    $customer['EncryptedPhone'], // Use encrypted phone number
                    $customer['TableNO'],
                    $orderID,
                    $grandTotal,
                    $pointsToEarn
                ]);

                // Mark orders as completed
                $completeOrderStmt = $pdo->prepare("
                    UPDATE OrderItem 
                    SET Status = 1 
                    WHERE OrderID = ?
                ");
                $completeOrderStmt->execute([$orderID]);

                $nextPayment++;
            }

            $pdo->commit();
            $_SESSION['payment_completed'] = true;
            header("Location: logout.php");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Payment error: " . $e->getMessage());
            $error = "Error processing payment: " . $e->getMessage();
        }
    }

} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred. Please try again later.");
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Check Bill | Conveyor Belt Sushi</title>
        <link rel="stylesheet" href="checkbill-style.css">
        <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    </head>

    <body>
        <div class="wrapper-bill">
            <div class="header">
                <h1>Check Bill</h1>
            </div>

            <div class="bill-content">
                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="bill-info">
                    <p>Table: <?php echo htmlspecialchars($customer['TableNO']); ?></p>
                    <p>Date and Time: <?php echo date('Y-m-d H:i:s'); ?></p>
                    <p>Customer: <?php echo htmlspecialchars($customer['FirstName'] . ' ' . $customer['LastName']); ?></p>
                    <p>Phone: <?php 
                        $phone = $customer['PhoneNumber'];
                        echo substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6);
                    ?></p>
                    <p>Current Points: <?php echo htmlspecialchars($customer['Points']); ?></p>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Plate's Color (Price)</th>
                            <th>Amount</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderDetails as $plateColor => $items): 
                            $colorTotal = array_reduce($items, function($carry, $item) {
                                return $carry + $item['amount'];
                            }, 0);
                            $priceTotal = array_reduce($items, function($carry, $item) {
                                return $carry + $item['subtotal'];
                            }, 0);
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($plateColor) . 
                                    ' (' . htmlspecialchars($items[0]['price']) . '฿)'; ?></td>
                                <td><?php echo htmlspecialchars($colorTotal); ?></td>
                                <td><?php echo htmlspecialchars($priceTotal); ?>฿</td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td>Total :</td>
                            <td><?php echo htmlspecialchars($totalItems); ?></td>
                            <td><?php echo htmlspecialchars($grandTotal); ?>฿</td>
                        </tr>
                    </tbody>
                </table>

                <div style="text-align: center; margin-top: 20px;">
                    <p>Points to be earned: <?php echo htmlspecialchars($pointsToEarn); ?></p>
                    <p>New total points after this purchase: <?php echo htmlspecialchars($customer['Points'] + $pointsToEarn); ?></p>
                </div>

                <form method="post">
                    <button type="submit" name="finish" class="finish-button">Finish</button>
                </form>
            </div>
        </div>

        <script>
            document.querySelector('form').addEventListener('submit', function(e) {
                if (!confirm('Are you sure you want to finish and check out?')) {
                    e.preventDefault();
                }
            });
        </script>
    </body>
</html>
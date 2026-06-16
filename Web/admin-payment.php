<?php
session_start();
require_once('db-connect.php');
require_once('encryption_util.php');

// Check for admin privileges
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'admin') {
    header("Location: login.php");
    exit();
}

try {
    $pdo = getDatabaseConnection('admin');

    // Query to get payment information with customer details including encryption key
    $sql = "SELECT p.`PaymentNo.`, p.TableNO, p.MemberID, 
            c.FirstName, c.LastName, c.PhoneNumber as CustomerPhone, c.EncryptionKey,
            p.PaymentTotalPrice, p.DateTime, p.PointsEarned
            FROM Payment p
            JOIN Customer c ON p.MemberID = c.MemberID
            ORDER BY p.`PaymentNo.`";

    $stmt = $pdo->query($sql);
    $payments = [];
    
    // Process and decrypt data
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $decryptedPhone = decryptData($row['CustomerPhone'], $row['EncryptionKey']);
        $formattedPhone = substr($decryptedPhone, 0, 3) . '-' . 
                         substr($decryptedPhone, 3, 3) . '-' . 
                         substr($decryptedPhone, 6);
        
        $payments[] = [
            'PaymentNo' => $row['PaymentNo.'],
            'TableNO' => $row['TableNO'],
            'MemberID' => $row['MemberID'],
            'FirstName' => $row['FirstName'],
            'LastName' => $row['LastName'],
            'PhoneNumber' => $formattedPhone,
            'PaymentTotalPrice' => $row['PaymentTotalPrice'],
            'DateTime' => $row['DateTime'],
            'PointsEarned' => $row['PointsEarned']
        ];
    }

} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Payment | Conveyor Belt Sushi</title>
    <link rel="stylesheet" href="admin-style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>

<body>
    <header>
        <nav>
            <ul>
                <li><div class="category-card"><a href="admin-customer.php"><i class='bx bx-user'></i>Customer</a></div></li>
                <li><div class="category-card"><a href="admin-order.php"><i class='bx bx-receipt'></i>Order</a></div></li>
                <li><div class="category-card"><a href="admin-payment.php"><i class='bx bx-money'></i>Payment</a></div></li>
                <li><div class="category-card"><a href="admin-menuitem.php"><i class='bx bx-restaurant'></i>Menu Item</a></div></li>
                <li><div class="category-card"><a href="login.php"><i class='bx bx-log-out'></i>Log Out</a></div></li>
            </ul>
        </nav>
    </header>

    <div class="wrapper-admin">
        <div class="header">
            <h1>Payment Information</h1>
        </div>

        <div class="content-area">
            <table>
                <thead>
                    <tr>
                        <th>Payment NO.</th>
                        <th>Table NO.</th>
                        <th>Member ID</th>
                        <th>First name</th>
                        <th>Last name</th>
                        <th>Phone number</th>
                        <th>Total Price</th>
                        <th>Date Time</th>
                        <th>Points Earned</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($payments)): ?>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo str_pad($payment["PaymentNo"], 10, "0", STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($payment["TableNO"]); ?></td>
                                <td><?php echo htmlspecialchars($payment["MemberID"]); ?></td>
                                <td><?php echo htmlspecialchars($payment["FirstName"]); ?></td>
                                <td><?php echo htmlspecialchars($payment["LastName"]); ?></td>
                                <td><?php echo htmlspecialchars($payment["PhoneNumber"]); ?></td>
                                <td><?php echo htmlspecialchars($payment["PaymentTotalPrice"]); ?>฿</td>
                                <td><?php echo htmlspecialchars($payment["DateTime"]); ?></td>
                                <td><?php echo htmlspecialchars($payment["PointsEarned"]); ?></td>
                                <td>
                                    <div class='action-buttons'>
                                        <button class='delete-btn' onclick='deletePayment(<?php echo $payment["PaymentNo"]; ?>)'>
                                            <i class='bx bx-trash'></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan='10'>No payments found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function deletePayment(paymentNo) {
        if (confirm('Are you sure you want to delete this payment?')) {
            window.location.href = 'delete-payment.php?id=' + paymentNo;
        }
    }
    </script>
</body>
</html>
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

    // Query to get order information with customer and menu item details
    $sql = "SELECT o.OrderID, o.TableNO, o.MemberID, 
            c.FirstName, c.LastName, c.PhoneNumber, c.EncryptionKey,
            oi.MenuItemName, m.Price
            FROM Order_ o
            JOIN Customer c ON o.MemberID = c.MemberID
            JOIN OrderItem oi ON o.OrderID = oi.OrderID
            JOIN MenuItem m ON oi.MenuItemName = m.MenuItemName
            ORDER BY o.OrderID";

    $stmt = $pdo->query($sql);
    $orders = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $decryptedPhone = decryptData($row['PhoneNumber'], $row['EncryptionKey']);
        $orders[] = [
            'OrderID' => $row['OrderID'],
            'TableNO' => $row['TableNO'],
            'MemberID' => $row['MemberID'],
            'FirstName' => $row['FirstName'],
            'LastName' => $row['LastName'],
            'PhoneNumber' => $decryptedPhone,
            'MenuItemName' => $row['MenuItemName'],
            'Price' => $row['Price']
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
    <title>Admin Order | Conveyor Belt Sushi</title>
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
            <h1>Order Information</h1>
        </div>

        <div class="content-area">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Table NO.</th>
                        <th>Member ID</th>
                        <th>First name</th>
                        <th>Last name</th>
                        <th>Menu item</th>
                        <th>Price</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order["OrderID"]); ?></td>
                                <td><?php echo htmlspecialchars($order["TableNO"]); ?></td>
                                <td><?php echo htmlspecialchars($order["MemberID"]); ?></td>
                                <td><?php echo htmlspecialchars($order["FirstName"]); ?></td>
                                <td><?php echo htmlspecialchars($order["LastName"]); ?></td>
                                <td><?php echo htmlspecialchars($order["MenuItemName"]); ?></td>
                                <td><?php echo htmlspecialchars($order["Price"]); ?>฿</td>
                                <td>
                                    <div class='action-buttons'>
                                        <button class='delete-btn' onclick='deleteOrder(<?php echo $order["OrderID"]; ?>)'>
                                            <i class='bx bx-trash'></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan='8'>No orders found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function deleteOrder(orderId) {
        if (confirm('Are you sure you want to delete this order?')) {
            window.location.href = 'delete-order.php?id=' + orderId;
        }
    }
    </script>
</body>
</html>
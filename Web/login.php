<?php
session_start();
require_once('db-connect.php');
require_once('encryption_util.php');

// Add initial database connection for table list
try {
    $pdo = getDatabaseConnection('customer');
    
    // Get all tables and their status
    $tableQuery = "
        SELECT 
            s.TableNO,
            CASE WHEN c.TableNO IS NOT NULL THEN 1 ELSE 0 END as isOccupied
        FROM Seat s
        LEFT JOIN Customer c ON s.TableNO = c.TableNO
        ORDER BY s.TableNO
    ";
    $tableStmt = $pdo->query($tableQuery);
    $tables = $tableStmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching tables: " . $e->getMessage());
    $tables = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = $_POST['firstName'];
    $phoneNumber = $_POST['phoneNumber'];
    $tableNo = $_POST['table-no'] ?? '';
    
    // Check for admin login
    if ($firstName === 'adminminmin' && $phoneNumber === 'sushiroisthebest') {
        try {
            $pdo = getDatabaseConnection('admin');
            $_SESSION['userType'] = 'admin';
            $_SESSION['loggedin'] = true;
            $_SESSION['FirstName'] = 'Admin';
            header("Location: admin-customer.php");
            exit();
        } catch(PDOException $e) {
            $error = "Invalid admin credentials";
        }
    } else {
        try {
            $pdo = getDatabaseConnection('customer');
            // Get all tables and their status
            try {
                $tableQuery = "
                    SELECT 
                        s.TableNO,
                        CASE WHEN c.TableNO IS NOT NULL THEN 1 ELSE 0 END as isOccupied
                    FROM Seat s
                    LEFT JOIN Customer c ON s.TableNO = c.TableNO
                    ORDER BY s.TableNO
                ";
                $tableStmt = $pdo->query($tableQuery);
                $tables = $tableStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                error_log("Error fetching tables: " . $e->getMessage());
                $tables = [];
            }
            // First get the customer's encryption key
            $stmt = $pdo->prepare("SELECT * FROM Customer WHERE FirstName = ?");
            $stmt->execute([$firstName]);
            $customer = $stmt->fetch();
            
            if ($customer) {
                // Decrypt the stored phone number and compare
                $decryptedPhone = decryptData($customer['PhoneNumber'], $customer['EncryptionKey']);
                
                if ($decryptedPhone === $phoneNumber) {
                    // Begin transaction
                    $pdo->beginTransaction();
                    
                    try {
                        // Update customer's table number
                        $updateStmt = $pdo->prepare("UPDATE Customer SET TableNO = ? WHERE MemberID = ?");
                        $updateStmt->execute([$tableNo, $customer['MemberID']]);

                        // Create new order
                        $orderStmt = $pdo->prepare("INSERT INTO Order_ (MemberID, TableNO) VALUES (?, ?)");
                        $orderStmt->execute([$customer['MemberID'], $tableNo]);
                        $newOrderID = $pdo->lastInsertId();

                        // Set session variables
                        $_SESSION['userType'] = 'customer';
                        $_SESSION['MemberID'] = $customer['MemberID'];
                        $_SESSION['FirstName'] = $customer['FirstName'];
                        $_SESSION['LastName'] = $customer['LastName'];
                        $_SESSION['PhoneNumber'] = $decryptedPhone;
                        $_SESSION['Email'] = decryptData($customer['Email'], $customer['EncryptionKey']);
                        $_SESSION['Points'] = $customer['Points'];
                        $_SESSION['TableNo'] = $tableNo;
                        $_SESSION['OrderID'] = $newOrderID;
                        $_SESSION['loggedin'] = true;
                        $_SESSION['login_time'] = time();

                        $pdo->commit();
                        header("Location: home.php");
                        exit();
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $error = "Error setting up session: " . $e->getMessage();
                    }
                } else {
                    $error = "Invalid credentials";
                }
            } else {
                $error = "Invalid credentials";
            }
        } catch(PDOException $e) {
            $error = "Login failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login | Conveyor Belt Sushi</title>
        <link rel="stylesheet" href="login-style.css">
        <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
        <style>
            .occupied-table {
                color: #ff0000;
            }
        </style>
    </head>

    <body>
        <div class="wrapper-login">
            <form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post" id="loginForm">
                <div class="table-select">
                    <label for="table-no">Table No.</label>
                    <select name="table-no" id="table-no">
                        <option value="" disabled selected>Select table</option>
                        <?php
                        if (!empty($tables)) {
                            foreach ($tables as $table) {
                                $occupied = $table['isOccupied'] ? ' (Occupied)' : '';
                                $class = $table['isOccupied'] ? ' class="occupied-table"' : '';
                                echo "<option value='" . htmlspecialchars($table['TableNO']) . "'" . $class . 
                                    ($table['isOccupied'] ? ' disabled' : '') . ">" . 
                                    htmlspecialchars($table['TableNO']) . $occupied . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <h1>Login</h1>

                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="input-box">
                    <input type="text" name="firstName" placeholder="Enter your first name.." required>
                    <i class='bx bxs-user'></i>
                </div>
                
                <div class="input-box">
                    <input type="text" name="phoneNumber" placeholder="Enter your phone number.." required>
                    <i class='bx bxs-phone'></i>
                </div>
                    
                <button type="submit" class="btn">Confirm</button>
                
                <div class="register-link">
                    <p>
                        Don't have an account?
                        <a href="register.php">Register</a>
                    </p>
                </div>
            </form>
        </div>

        <script>
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                const firstName = document.querySelector('input[name="firstName"]').value;
                const phoneNumber = document.querySelector('input[name="phoneNumber"]').value;
                const tableNo = document.getElementById('table-no');
                
                // Remove required attribute for admin login
                if (firstName === 'adminminmin' && phoneNumber === 'sushiroisthebest') {
                    tableNo.removeAttribute('required');
                } else {
                    tableNo.setAttribute('required', 'required');
                }
            });
        </script>
    </body>
</html>
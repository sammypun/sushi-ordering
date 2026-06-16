<?php
session_start();
require_once('db-connect.php');
require_once('encryption_util.php');

try {
    $pdo = getDatabaseConnection('admin');
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Generate a unique 10-digit MemberID
        do {
            $memberID = str_pad(mt_rand(1, 9999999999), 10, '0', STR_PAD_LEFT);
            $check = $pdo->prepare("SELECT COUNT(*) FROM Customer WHERE MemberID = ?");
            $check->execute([$memberID]);
        } while ($check->fetchColumn() > 0);
        
        // Generate encryption key for this customer
        $encryptionKey = generateSecureKey();
        
        // Remove non-digits from phone number and encrypt sensitive data
        $phoneNumber = preg_replace('/\D/', '', $_POST['phoneNumber']);
        $encryptedPhone = encryptData($phoneNumber, $encryptionKey);
        $encryptedEmail = $_POST['email'] ? encryptData($_POST['email'], $encryptionKey) : null;
        $encryptedDOB = $_POST['birthdate'] ? encryptData($_POST['birthdate'], $encryptionKey) : null;
        
        $stmt = $pdo->prepare("
            INSERT INTO Customer (MemberID, PhoneNumber, FirstName, LastName, Email, DOB, Points, EncryptionKey) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $memberID,
            $encryptedPhone,
            $_POST['firstName'],
            $_POST['lastName'],
            $encryptedEmail,
            $encryptedDOB,
            $_POST['points'] ?: 0,
            $encryptionKey
        ]);

        header("Location: admin-customer.php");
        exit();
    } catch(PDOException $e) {
        $error = "Error adding customer: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Add Customer | Conveyor Belt Sushi</title>
        <link rel="stylesheet" href="admin-style.css">
        <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
        <style>
            .form-container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .form-group label {
                display: block;
                margin-bottom: 5px;
                font-weight: 500;
                color: #333;
            }

            .form-group input {
                width: 100%;
                padding: 12px;
                border: 1px solid #ddd;
                border-radius: 8px;
                font-size: 14px;
                transition: border-color 0.3s;
            }

            .form-group input:focus {
                outline: none;
                border-color: #ffa500;
            }

            .button-group {
                display: flex;
                justify-content: center;
                gap: 10px;
                margin-top: 30px;
            }

            .submit-btn, .cancel-btn {
                padding: 12px 30px;
                border: none;
                border-radius: 25px;
                cursor: pointer;
                font-size: 16px;
                font-weight: 500;
                transition: all 0.3s ease;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 120px;
            }

            .submit-btn {
                background-color: #ffa500dd;
                color: black;
            }

            .submit-btn:hover {
                background-color: rgb(255, 190, 70);
            }

            .cancel-btn {
                background-color: #ff0000dd;
                color: white;
            }

            .cancel-btn:hover {
                background-color: rgb(255, 70, 70);
            }

            .error-message {
                background-color: #ff00001a;
                color: #ff0000;
                padding: 12px;
                border-radius: 8px;
                text-align: center;
                margin-bottom: 20px;
            }

            /* Required field indicator */
            .required-field::after {
                content: "*";
                color: #ff0000;
                margin-left: 4px;
            }
        </style>
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
                <h1>Add New Customer</h1>
            </div>

            <div class="form-container">
                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="" onsubmit="return validateForm()">
                    <div class="form-group">
                        <label for="firstName" class="required-field">First Name</label>
                        <input type="text" id="firstName" name="firstName" required maxlength="50"
                               placeholder="Enter first name">
                    </div>

                    <div class="form-group">
                        <label for="lastName">Last Name</label>
                        <input type="text" id="lastName" name="lastName" maxlength="50"
                               placeholder="Enter last name">
                    </div>

                    <div class="form-group">
                        <label for="phoneNumber" class="required-field">Phone Number</label>
                        <input type="tel" id="phoneNumber" name="phoneNumber" 
                               pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}" required
                               placeholder="XXX-XXX-XXXX">
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" maxlength="255"
                               placeholder="Enter email address">
                    </div>

                    <div class="form-group">
                        <label for="birthdate">Birthdate</label>
                        <input type="date" id="birthdate" name="birthdate">
                    </div>

                    <div class="form-group">
                        <label for="points">Points</label>
                        <input type="number" id="points" name="points" min="0" value="0"
                               placeholder="Enter points">
                    </div>

                    <div class="button-group">
                        <button type="submit" class="submit-btn">Add Customer</button>
                        <a href="admin-customer.php" class="cancel-btn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <script>
            // Phone number formatting
            document.getElementById('phoneNumber').addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '').substring(0,10);
                let formatted = '';
                
                if (value.length >= 3) {
                    formatted += value.substring(0,3) + '-';
                    if (value.length >= 6) {
                        formatted += value.substring(3,6) + '-';
                        formatted += value.substring(6);
                    } else {
                        formatted += value.substring(3);
                    }
                } else {
                    formatted = value;
                }
                
                e.target.value = formatted;
            });

            // Form validation
            function validateForm() {
                const phoneNumber = document.getElementById('phoneNumber').value.replace(/\D/g, '');
                if (phoneNumber.length !== 10) {
                    alert('Please enter a valid 10-digit phone number');
                    return false;
                }
                
                const firstName = document.getElementById('firstName').value.trim();
                if (!firstName) {
                    alert('Please enter a first name');
                    return false;
                }
                
                return true;
            }
        </script>
    </body>
</html>
<?php
session_start();
require_once('db-connect.php');
require_once('encryption_util.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo = getDatabaseConnection('customer');
        
        // Generate memberID
        $memberID = mt_rand(1000000000, 9999999999);
        
        // Get form data
        $firstName = $_POST['firstname'];
        $lastName = $_POST['lastname'];
        $phoneNumber = $_POST['phoneNumber'];
        $email = $_POST['email'];
        $dob = $_POST['DOB'];
        
        // Generate a secure key for this customer
        $encryptionKey = generateSecureKey();
        
        // Encrypt sensitive data
        $encryptedPhone = encryptData($phoneNumber, $encryptionKey);
        $encryptedEmail = encryptData($email, $encryptionKey);
        $encryptedDOB = encryptData($dob, $encryptionKey);
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Insert customer data
        $sql = "INSERT INTO Customer 
                (MemberID, PhoneNumber, FirstName, LastName, Email, DOB, Points, EncryptionKey) 
                VALUES (?, ?, ?, ?, ?, ?, 0, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $memberID,
            $encryptedPhone,
            $firstName,
            $lastName,
            $encryptedEmail,
            $encryptedDOB,
            $encryptionKey
        ]);
        
        $pdo->commit();
        
        // Redirect to login page
        header("Location: login.php");
        exit();
        
    } catch(PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error_message = "Registration failed. Please try again.";
        error_log("Registration error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Register | Conveyor Belt Sushi</title> 
        <link rel="stylesheet" href="register-style.css">
        <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    </head>

    <body>
        <div class="wrapper-register">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <h1>Register</h1>
                
                <?php if (isset($error_message)): ?>
                    <div class="error-message"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <div class="input-box">
                    <label>First Name</label>
                    <input type="text" placeholder="Enter first name" name="firstname" required>
                </div>
                
                <div class="input-box">
                    <label>Last Name</label>
                    <input type="text" placeholder="Enter last name" name="lastname" required>
                </div>
    
                <div class="input-box">
                    <label>Phone Number</label>
                    <input type="tel" id="phoneNumber" name="phoneNumber" placeholder="Enter phone number" required>
                </div>

                <div class="input-box">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="Enter email address" required>
                </div>

                <div class="input-box">
                    <label>Birthdate</label>
                    <input type="date" name="DOB" required>
                </div>
                
                <br>
                <button type="submit" class="btn">Create an account</button>
            </form>
        </div>
    </body>
</html>
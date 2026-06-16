<?php
session_start();
require_once('db-connect.php');
require_once('encryption_util.php');

try {
    $pdo = getDatabaseConnection('admin');

    // Fetch all customers with their encryption keys
    $stmt = $pdo->query("SELECT * FROM Customer ORDER BY FirstName");
    $encryptedCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Decrypt sensitive information for each customer
    $customers = array_map(function($customer) {
        try {
            return [
                'MemberID' => $customer['MemberID'],
                'FirstName' => $customer['FirstName'],
                'LastName' => $customer['LastName'],
                'PhoneNumber' => $customer['PhoneNumber'] ? decryptData($customer['PhoneNumber'], $customer['EncryptionKey']) : null,
                'Email' => $customer['Email'] ? decryptData($customer['Email'], $customer['EncryptionKey']) : null,
                'DOB' => $customer['DOB'] ? decryptData($customer['DOB'], $customer['EncryptionKey']) : null,
                'Points' => $customer['Points'],
                'TableNO' => $customer['TableNO'],
                'EncryptionKey' => $customer['EncryptionKey']
            ];
        } catch (Exception $e) {
            error_log("Decryption error for customer {$customer['MemberID']}: " . $e->getMessage());
            return null;
        }
    }, $encryptedCustomers);

    // Remove any failed decryption attempts
    $customers = array_filter($customers);

    // Handle Edit Customer Form Submission
    if (isset($_POST['edit_customer'])) {
        try {
            // Get the customer's encryption key
            $keyStmt = $pdo->prepare("SELECT EncryptionKey FROM Customer WHERE MemberID = ?");
            $keyStmt->execute([$_POST['edit_memberID']]);
            $encryptionKey = $keyStmt->fetchColumn();

            // Encrypt the updated data
            $phoneNumber = preg_replace('/\D/', '', $_POST['edit_phoneNumber']);
            
            $stmt = $pdo->prepare("
                UPDATE Customer 
                SET PhoneNumber = ?,
                    FirstName = ?,
                    LastName = ?,
                    Email = ?,
                    DOB = ?,
                    Points = ?
                WHERE MemberID = ?
            ");
            
            $stmt->execute([
                encryptData($phoneNumber, $encryptionKey),
                $_POST['edit_firstName'],
                $_POST['edit_lastName'],
                $_POST['edit_email'] ? encryptData($_POST['edit_email'], $encryptionKey) : null,
                $_POST['edit_birthdate'] ? encryptData($_POST['edit_birthdate'], $encryptionKey) : null,
                $_POST['edit_points'] ?: 0,
                $_POST['edit_memberID']
            ]);

            header("Location: admin-customer.php");
            exit();
        } catch(PDOException $e) {
            error_log("Error updating customer: " . $e->getMessage());
            $error = "Error updating customer: " . $e->getMessage();
        }
    }
} catch(PDOException $e) {
    error_log("Error fetching customers: " . $e->getMessage());
    $error = "Error fetching customers: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Customer | Conveyor Belt Sushi</title>
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

        .required-field::after {
            content: "*";
            color: #ff0000;
            margin-left: 4px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow: auto;
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            position: relative;
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from { transform: translateY(-100px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .modal-header h2 {
            margin: 0;
            color: #333;
            font-weight: 500;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close:hover {
            color: #333;
        }

        .error-message {
            background-color: #ff00001a;
            color: #ff0000;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }

        .success-message {
            background-color: #4CAF5033;
            color: #4CAF50;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
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
            <h1>Customer Management</h1>
        </div>

        <?php if (isset($_GET['message'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_GET['message']); ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="content-area">
            <table>
                <thead>
                    <tr>
                        <th>Member ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Phone Number</th>
                        <th>Email</th>
                        <th>Date of Birth</th>
                        <th>Points</th>
                        <th>Edit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($customer['MemberID']); ?></td>
                            <td><?php echo htmlspecialchars($customer['FirstName']); ?></td>
                            <td><?php echo htmlspecialchars($customer['LastName'] ?? ''); ?></td>
                            <td><?php 
                                if ($customer['PhoneNumber']) {
                                    $phone = $customer['PhoneNumber'];
                                    echo substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6);
                                }
                            ?></td>
                            <td><?php echo htmlspecialchars($customer['Email'] ?? ''); ?></td>
                            <td><?php echo $customer['DOB'] ? date('Y-m-d', strtotime($customer['DOB'])) : ''; ?></td>
                            <td><?php echo htmlspecialchars($customer['Points']); ?></td>
                            <td class="action-buttons">
                                <button class="edit-btn" onclick='openEditModal(<?php echo json_encode([
                                    "memberID" => $customer["MemberID"],
                                    "firstName" => $customer["FirstName"],
                                    "lastName" => $customer["LastName"],
                                    "phoneNumber" => $customer["PhoneNumber"],
                                    "email" => $customer["Email"],
                                    "dob" => $customer["DOB"],
                                    "points" => $customer["Points"]
                                ]); ?>)'><i class='bx bx-edit'></i></button>
                                <button class="delete-btn" onclick="deleteCustomer('<?php echo $customer['MemberID']; ?>')">
                                    <i class='bx bx-trash'></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <a href="add-customer.php" class="add-new-button">
                <i class='bx bx-plus'></i>Add New Customer
            </a>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Customer</h2>
                <span class="close">&times;</span>
            </div>
            <div class="form-container">
                <form id="editForm" method="POST" action="" onsubmit="return validateEditForm()">
                    <input type="hidden" id="edit_memberID" name="edit_memberID">
                    
                    <div class="form-group">
                        <label for="edit_firstName">First Name</label>
                        <input type="text" id="edit_firstName" name="edit_firstName" required maxlength="50">
                    </div>

                    <div class="form-group">
                        <label for="edit_lastName">Last Name</label>
                        <input type="text" id="edit_lastName" name="edit_lastName" maxlength="50">
                    </div>

                    <div class="form-group">
                        <label for="edit_phoneNumber">Phone Number</label>
                        <input type="tel" id="edit_phoneNumber" name="edit_phoneNumber" pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" id="edit_email" name="edit_email" maxlength="255">
                    </div>

                    <div class="form-group">
                        <label for="edit_birthdate">Birthdate</label>
                        <input type="date" id="edit_birthdate" name="edit_birthdate">
                    </div>

                    <div class="form-group">
                        <label for="edit_points">Points</label>
                        <input type="number" id="edit_points" name="edit_points" min="0">
                    </div>

                    <div class="button-group">
                        <button type="submit" name="edit_customer" class="submit-btn">Save Changes</button>
                        <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('editModal');
        const closeBtn = document.getElementsByClassName('close')[0];

        function openEditModal(customerData) {
            modal.style.display = 'block';
            document.getElementById('edit_memberID').value = customerData.memberID;
            document.getElementById('edit_firstName').value = customerData.firstName;
            document.getElementById('edit_lastName').value = customerData.lastName;
            
            if (customerData.phoneNumber) {
                const phone = customerData.phoneNumber;
                const formattedPhone = `${phone.substring(0,3)}-${phone.substring(3,6)}-${phone.substring(6)}`;
                document.getElementById('edit_phoneNumber').value = formattedPhone;
            }
            
            document.getElementById('edit_email').value = customerData.email || '';
            document.getElementById('edit_birthdate').value = customerData.dob || '';
            document.getElementById('edit_points').value = customerData.points;
        }
        function closeModal() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }

        closeBtn.onclick = closeModal;

        document.getElementById('edit_phoneNumber').addEventListener('input', function(e) {
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

        function validateEditForm() {
            const phoneNumber = document.getElementById('edit_phoneNumber').value.replace(/\D/g, '');
            if (phoneNumber.length !== 10) {
                alert('Please enter a valid 10-digit phone number');
                return false;
            }
            
            const firstName = document.getElementById('edit_firstName').value.trim();
            if (!firstName) {
                alert('Please enter a first name');
                return false;
            }
            
            return true;
        }

        function deleteCustomer(memberID) {
            if (confirm('Are you sure you want to delete this customer?')) {
                window.location.href = 'delete-customer.php?id=' + memberID;
            }
        }
    </script>
</body>
</html>
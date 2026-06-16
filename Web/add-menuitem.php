<?php
require_once('db-connect.php');

try {
    $pdo = getDatabaseConnection('admin');
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Initialize error variable
$error = null;

// Get existing categories from MenuItem table
try {
    $stmt = $pdo->query("SELECT DISTINCT MenuCategory FROM MenuItem ORDER BY MenuCategory");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    $error = "Error loading categories: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get and sanitize input
        $menuItemName = trim($_POST['menuItemName']);
        $plateColor = $_POST['plateColor'];
        $menuCategory = trim($_POST['menuCategory']);
        $price = floatval($_POST['price']);
        
        // Validate inputs
        if (empty($menuItemName) || empty($menuCategory) || $price < 0) {
            throw new Exception("Please fill in all required fields correctly.");
        }

        // Check if menu item name already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM MenuItem WHERE MenuItemName = ?");
        $stmt->execute([$menuItemName]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("A menu item with this name already exists.");
        }

        // Insert new menu item
        $stmt = $pdo->prepare("INSERT INTO MenuItem (MenuItemName, PlateColor, MenuCategory, Price) VALUES (?, ?, ?, ?)");
        $stmt->execute([$menuItemName, $plateColor, $menuCategory, $price]);

        // Redirect on success
        header("Location: admin-menuitem.php");
        exit();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <!-- Head section remains the same -->
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Add Menu Item | Conveyor Belt Sushi</title>
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

            .form-group input,
            .form-group select {
                width: 100%;
                padding: 12px;
                border: 1px solid #ddd;
                border-radius: 8px;
                font-size: 14px;
                transition: border-color 0.3s;
            }

            .form-group input:focus,
            .form-group select:focus {
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
            label::after {
                content: "*";
                color: #ff0000;
                margin-left: 4px;
            }

            /* Ensure select dropdowns match input styling */
            select {
                background-color: white;
                cursor: pointer;
            }

            select:invalid {
                color: #757575;
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
                <h1>Add New Menu Item</h1>
            </div>

            <div class="form-container">
                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="" onsubmit="return validateForm()">
                    <div class="form-group">
                        <label for="menuItemName">Menu Item Name</label>
                        <input type="text" id="menuItemName" name="menuItemName" required maxlength="50"
                               placeholder="Enter menu item name">
                    </div>

                    <div class="form-group">
                        <label for="plateColor">Plate Color</label>
                        <select id="plateColor" name="plateColor" required>
                            <option value="" disabled selected>Select plate color</option>
                            <option value="Red">Red (฿40)</option>
                            <option value="Silver">Silver (฿60)</option>
                            <option value="Gold">Gold (฿80)</option>
                            <option value="Black">Black (฿120)</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="menuCategory">Menu Category</label>
                        <select id="menuCategory" name="menuCategory" required>
                            <option value="" disabled selected>Select category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>">
                                    <?php echo htmlspecialchars($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="price">Price (฿)</label>
                        <input type="number" id="price" name="price" required min="0" step="20" value="40"
                               placeholder="Enter price">
                    </div>

                    <div class="button-group">
                        <button type="submit" class="submit-btn">Add Menu Item</button>
                        <a href="admin-menuitem.php" class="cancel-btn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function validateForm() {
                var menuItemName = document.getElementById('menuItemName').value.trim();
                var plateColor = document.getElementById('plateColor').value;
                var menuCategory = document.getElementById('menuCategory').value;
                var price = document.getElementById('price').value;

                if (!menuItemName || !menuCategory || !price) {
                    alert('Please fill in all required fields.');
                    return false;
                }

                return true;
            }
        </script>
    </body>
</html>
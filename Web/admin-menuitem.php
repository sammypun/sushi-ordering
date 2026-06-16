<?php
session_start();
require_once('db-connect.php');

try {
    $pdo = getDatabaseConnection('admin');

    if (isset($_POST['edit_menuitem'])) {
        try {
            $stmt = $pdo->prepare("
                UPDATE MenuItem 
                SET MenuItemName = ?,
                    PlateColor = ?,
                    MenuCategory = ?,
                    Price = ?
                WHERE MenuItemName = ?
            ");
            
            $stmt->execute([
                $_POST['edit_menuItemName'],
                $_POST['edit_plateColor'],
                $_POST['edit_menuCategory'],
                $_POST['edit_price'],
                $_POST['original_name']
            ]);

            header("Location: admin-menuitem.php?message=Menu item successfully updated");
            exit();
        } catch(PDOException $e) {
            error_log("Error updating menu item: " . $e->getMessage());
            $error = "Error updating menu item: " . $e->getMessage();
        }
    }

    $stmt = $pdo->query("SELECT * FROM MenuItem ORDER BY MenuCategory, MenuItemName");
    $menuItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "Error fetching menu items: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Menu Item | Conveyor Belt Sushi</title>
    <link rel="stylesheet" href="admin-style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .form-container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #333; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; transition: border-color 0.3s; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #ffa500; }
        .button-group { display: flex; justify-content: center; gap: 10px; margin-top: 30px; }
        .submit-btn, .cancel-btn { padding: 12px 30px; border: none; border-radius: 25px; cursor: pointer; font-size: 16px; font-weight: 500; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; min-width: 120px; }
        .submit-btn { background-color: #ffa500dd; color: black; }
        .submit-btn:hover { background-color: rgb(255, 190, 70); }
        .cancel-btn { background-color: #ff0000dd; color: white; }
        .cancel-btn:hover { background-color: rgb(255, 70, 70); }
        .required-field::after { content: "*"; color: #ff0000; margin-left: 4px; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow: auto; }
        .modal-content { background-color: white; margin: 5% auto; padding: 20px; border-radius: 20px; width: 90%; max-width: 600px; position: relative; animation: modalSlideIn 0.3s ease-out; }
        @keyframes modalSlideIn { from { transform: translateY(-100px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #ddd; }
        .modal-header h2 { margin: 0; color: #333; font-weight: 500; }
        .close { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; transition: color 0.3s; }
        .close:hover { color: #333; }
        .error-message { background-color: #ff00001a; color: #ff0000; padding: 12px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
        .success-message { background-color: #4CAF5033; color: #4CAF50; padding: 12px; border-radius: 8px; text-align: center; margin: 20px 0; }
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
            <h1>Menu Item Management</h1>
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
                        <th>Name</th>
                        <th>Plate Color</th>
                        <th>Menu Category</th>
                        <th>Price</th>
                        <th>Edit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($menuItems as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['MenuItemName']); ?></td>
                            <td><?php echo htmlspecialchars($item['PlateColor']); ?></td>
                            <td><?php echo htmlspecialchars($item['MenuCategory']); ?></td>
                            <td><?php echo htmlspecialchars($item['Price']); ?>฿</td>
                            <td class="action-buttons">
                                <button class="edit-btn" onclick='openEditModal(<?php echo json_encode($item); ?>)'><i class='bx bx-edit'></i></button>
                                <button class="delete-btn" onclick="deleteMenuItem('<?php echo htmlspecialchars($item['MenuItemName']); ?>')"><i class='bx bx-trash'></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <a href="add-menuitem.php" class="add-new-button">
                <i class='bx bx-plus'></i>Add New Menu Item
            </a>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Menu Item</h2>
                <span class="close">&times;</span>
            </div>
            <form id="editForm" method="POST" action="" onsubmit="return validateEditForm()">
                <input type="hidden" id="original_name" name="original_name">
                
                <div class="form-group">
                    <label for="edit_menuItemName" class="required-field">Menu Item Name</label>
                    <input type="text" id="edit_menuItemName" name="edit_menuItemName" required maxlength="50">
                </div>

                <div class="form-group">
                    <label for="edit_plateColor">Plate Color</label>
                    <select id="edit_plateColor" name="edit_plateColor">
                        <option value="Red">Red (฿40)</option>
                        <option value="Silver">Silver (฿60)</option>
                        <option value="Gold">Gold (฿80)</option>
                        <option value="Black">Black (฿120)</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_menuCategory" class="required-field">Menu Category</label>
                    <select id="edit_menuCategory" name="edit_menuCategory" required>
                        <option value="Nigiri">Nigiri</option>
                        <option value="Gunkan">Gunkan</option>
                        <option value="Appetizers">Appetizers</option>
                        <option value="Noodles/Soup">Noodles/Soup</option>
                        <option value="Desserts/Beverages">Desserts/Beverages</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_price" class="required-field">Price (฿)</label>
                    <input type="number" id="edit_price" name="edit_price" min="0" step="0.01" required>
                </div>

                <div class="button-group">
                    <button type="submit" name="edit_menuitem" class="submit-btn">Save Changes</button>
                    <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('editModal');
        const closeBtn = document.getElementsByClassName('close')[0];

        function openEditModal(menuItem) {
            modal.style.display = 'block';
            document.getElementById('original_name').value = menuItem.MenuItemName;
            document.getElementById('edit_menuItemName').value = menuItem.MenuItemName;
            document.getElementById('edit_plateColor').value = menuItem.PlateColor;
            document.getElementById('edit_menuCategory').value = menuItem.MenuCategory;
            document.getElementById('edit_price').value = menuItem.Price;
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

        function validateEditForm() {
            const price = document.getElementById('edit_price').value;
            if (price < 0) {
                alert('Price cannot be negative');
                return false;
            }
            
            const name = document.getElementById('edit_menuItemName').value.trim();
            if (!name) {
                alert('Please enter a menu item name');
                return false;
            }
            
            return true;
        }

        function deleteMenuItem(menuItemName) {
            if (confirm('Are you sure you want to delete this menu item?')) {
                window.location.href = 'delete-menuitem.php?name=' + encodeURIComponent(menuItemName);
            }
        }
    </script>
</body>
</html>
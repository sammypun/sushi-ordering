<?php
session_start();
require_once('db-connect.php');

// Check for customer privileges
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'customer') {
    header("Location: login.php");
    exit();
}

// Session timeout after 2 hours
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 7200)) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}

try {
    $pdo = getDatabaseConnection('customer');
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Initialize variables
$category = isset($_GET['category']) ? $_GET['category'] : 'Nigiri';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$itemsPerPage = 8;

// Store customer information in variables for easy access
$memberID = $_SESSION['MemberID'];
$firstName = $_SESSION['FirstName'];
$lastName = $_SESSION['LastName'] ?? '';
$tableNo = $_SESSION['TableNo'];
$points = $_SESSION['Points'];

// Define available categories
$categories = ['Nigiri', 'Gunkan', 'Noodles/Soup', 'Appetizers', 'Desserts/Beverages'];

// Initialize debug string
$debug = "";

try {
    // List all categories in database for debugging
    $catQuery = "SELECT DISTINCT MenuCategory FROM MenuItem";
    $catStmt = $pdo->query($catQuery);
    $debug .= "<!-- Debug: Available categories in database: ";
    while($row = $catStmt->fetch(PDO::FETCH_ASSOC)) {
        $debug .= $row['MenuCategory'] . ", ";
    }
    $debug .= " -->\n";

    // Get total number of menu items for the current category
    $totalQuery = "SELECT COUNT(*) as total FROM MenuItem WHERE MenuCategory = ?";
    $stmtTotal = $pdo->prepare($totalQuery);
    $stmtTotal->execute([$category]);
    $totalRow = $stmtTotal->fetch(PDO::FETCH_ASSOC);
    $totalItems = $totalRow['total'];
    $totalPages = max(1, ceil($totalItems / $itemsPerPage));

    $debug .= "<!-- Debug: Total items for category $category: $totalItems -->\n";

    // Calculate offset
    $offset = ($page - 1) * $itemsPerPage;
    
    // Debug query construction
    $queryStr = "SELECT MenuItemName, PlateColor, Price FROM MenuItem WHERE MenuCategory = ? LIMIT ? OFFSET ?";
    $debug .= "<!-- Debug: Query: $queryStr -->\n";
    $debug .= "<!-- Debug: Parameters: category=$category, limit=$itemsPerPage, offset=$offset -->\n";

    // Get menu items
    $stmt = $pdo->prepare($queryStr);
    $stmt->bindValue(1, $category, PDO::PARAM_STR);
    $stmt->bindValue(2, $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $debug .= "<!-- Debug: Result rows: " . count($result) . " -->\n";

    // Query to check if there are any unserved orders
    $unservedOrdersQuery = "
        SELECT COUNT(*) as unserved_count 
        FROM OrderItem 
        WHERE OrderID = ? AND Status = 0
    ";
    $unservedStmt = $pdo->prepare($unservedOrdersQuery);
    $unservedStmt->execute([$_SESSION['OrderID']]);
    $unservedResult = $unservedStmt->fetch(PDO::FETCH_ASSOC);
    $unservedCount = $unservedResult['unserved_count'];
    
    // Add this data attribute to the Check Bill button
    $checkBillDisabled = $unservedCount > 0 ? 'disabled' : '';

} catch (Exception $e) {
    error_log($e->getMessage());
    $debug .= "<!-- Debug: Error occurred: " . htmlspecialchars($e->getMessage()) . " -->\n";
    $result = [];
    $totalPages = 1;
    $checkBillDisabled = 'disabled';
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Menu | Conveyor Belt Sushi</title> 
        <link rel="stylesheet" href="home-style.css">
        <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
        <style>
            /* Additional styles for menu items */
            .menu-item {
                padding-bottom: 0 !important;
                height: auto !important;
                min-height: 120px;
                display: flex;
                justify-content: center;
                align-items: center;
                background-color: #f5f5f5;
            }

            .menu-item-content {
                width: 100%;
                height: 100%;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                padding: 15px;
            }

            .menu-item h3 {
                margin-bottom: 8px;
                font-size: 16px;
                text-align: center;
            }

            .menu-item .price {
                font-size: 14px;
                font-weight: 500;
            }

            .CheckBill-button:disabled {
                opacity: 0.5;
                cursor: not-allowed;
                background-color: #cccccc;
            }

            .status-check-warning {
                color: #dc3545;
                font-size: 14px;
                margin-top: 5px;
                text-align: center;
                display: none;
            }

            /* Plate color styles */
            .plate-red { background-color: #e7a1a1; }
            .plate-black { background-color: #9e9c9c; }
            .plate-silver { background-color: #d0d0d0; }
            .plate-gold { background-color: #f9e4af; }
        </style>
    </head>
    
    <body>
        <?php echo $debug; // Output debug information in HTML comments ?>
        <header>
          <nav aria-label="Main navigation">
            <ul>
              <?php foreach ($categories as $cat): ?>
                <li>
                    <div class="category-card" role="button" tabindex="0">
                        <a href="?category=<?php echo urlencode($cat); ?>" 
                           <?php if($category === $cat) echo 'class="active"'; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </a>
                    </div>
                </li>
              <?php endforeach; ?>
            </ul>
          </nav>
          
        </header>
      
        <section class="content">
            <!-- Left Panel-->
             
            <div class="left-panel">
                <div class="menu-catalogue">
                    <div class="grid-container">
                        <?php
                        if (!empty($result)) {
                            foreach ($result as $row) {
                                $debug .= "<!-- Debug: Processing item " . htmlspecialchars($row['MenuItemName']) . " -->\n";
                                echo '<div class="grid-item menu-item plate-' . strtolower($row['PlateColor']) . '" 
                                        role="button" tabindex="0" 
                                        onclick="addToOrder(\'' . htmlspecialchars($row['MenuItemName']) . '\', ' . htmlspecialchars($row['Price']) . ')">';
                                echo '<div class="menu-item-content">';
                                echo '<h3>' . htmlspecialchars($row['MenuItemName']) . '</h3>';
                                echo '<p class="price">฿' . htmlspecialchars($row['Price']) . '</p>';
                                echo '</div>';
                                echo '</div>';
                            }
                            
                            // Fill remaining grid items if less than 8 items
                            $remainingItems = $itemsPerPage - count($result);
                            for ($i = 0; $i < $remainingItems; $i++) {
                                echo '<div class="grid-item" role="img" aria-label="Empty menu slot" tabindex="0"></div>';
                            }
                        } else {
                            $debug .= "<!-- Debug: No results found for this page -->\n";
                            for ($i = 0; $i < $itemsPerPage; $i++) {
                                echo '<div class="grid-item" role="img" aria-label="Menu item placeholder" tabindex="0"></div>';
                            }
                        }
                        ?>
                    </div>
                    <div class="pagination">
                        <?php 
                        $prevPage = max(1, $page - 1);
                        $nextPage = min($totalPages, $page + 1);
                        ?>
                        <button class="prev-btn" 
                            <?php if($page <= 1) echo 'disabled'; ?>
                            onclick="location.href='home.php?category=<?php echo urlencode($category); ?>&page=<?php echo $prevPage; ?>'">
                            < Prev
                        </button>
                        <div class="page-indicator"><?php echo $page; ?>/<?php echo $totalPages; ?></div>
                        <button class="next-btn" 
                            <?php if($page >= $totalPages) echo 'disabled'; ?>
                            onclick="location.href='home.php?category=<?php echo urlencode($category); ?>&page=<?php echo $nextPage; ?>'">
                            Next >
                        </button>
                    </div>
                </div>
                <div class="user-info">
                    <p><span class="user-info-label">Welcome,</span> <?php echo htmlspecialchars($firstName . ' ' . $lastName); ?></p>
                    <p><span class="user-info-label">Table:</span> <?php echo htmlspecialchars($tableNo); ?></p>
                    <p><span class="user-info-label">Points:</span> <?php echo htmlspecialchars($points); ?></p>
                    <a href="logout.php" class="logout-link">Logout</a>
                </div>
            </div>
        
            <!-- Right Panel-->
            <div class="right-panel">
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="orderhistory.php" class="action-button-link">
                        <button class="OrderHistory-button">Order History</button>
                    </a>
                    
                    <a href="checkbill.php" class="action-button-link">
                        <button class="CheckBill-button" <?php echo $checkBillDisabled; ?>>Check Bill</button>
                    </a>

                    <div id="statusCheckWarning" class="status-check-warning">
                        Please confirm all orders have been received before checking bill
                    </div>
                </div>
                
                <!-- Order Table -->
                <div class="table-container">
                    <table aria-label="Order list">
                        <thead>
                            <tr>
                                <th>Order List</th>
                                <th>Amount</th>
                                <th>Add</th>
                                <th>Drop</th>
                                <th>Cancel</th>
                            </tr>
                        </thead>
                        <tbody id="orderTableBody">
                            <!-- Order items will be dynamically added here -->
                        </tbody>
                    </table>
                </div>

                <!-- Order Button -->
                <button class="order-button">Order</button>
            </div>
        </section>

        <!-- Check Bill Modal -->
        <div id="checkBillModal" class="modal">
            <div class="modal-content">
                <h2>Confirm Check Bill</h2>
                <p>Are you sure you want to check your bill? This will finalize your current session.</p>
                <div class="modal-buttons">
                    <button type="button" class="modal-confirm" onclick="confirmCheckBill()">Confirm</button>
                    <button type="button" class="modal-cancel" onclick="closeModal('checkBillModal')">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Order Confirmation Modal -->
        <div id="orderModal" class="modal">
            <div class="modal-content">
                <h2>Confirm Order</h2>
                <p>Are you sure you want to place this order?</p>
                <div class="modal-buttons">
                    <button type="button" class="modal-confirm" onclick="confirmOrder()">Confirm</button>
                    <button type="button" class="modal-cancel" onclick="closeModal('orderModal')">Cancel</button>
                </div>
            </div>
        </div>

<script>
    // Order management
    let orderItems = {};
    const MAX_TOTAL_QUANTITY = 4;

    function loadSavedOrders() {
        const savedOrders = localStorage.getItem('orderItems');
        if (savedOrders) {
            orderItems = JSON.parse(savedOrders);
            updateOrderTable();
        }
    }

    function saveOrders() {
        localStorage.setItem('orderItems', JSON.stringify(orderItems));
    }

    function getTotalQuantity() {
        return Object.values(orderItems).reduce((sum, item) => sum + item.quantity, 0);
    }

    function addToOrder(name, price) {
        const currentTotal = getTotalQuantity();
        
        if (currentTotal >= MAX_TOTAL_QUANTITY && !orderItems[name]) {
            alert('Maximum total of 4 items allowed per order.');
            return;
        }

        if (orderItems[name]) {
            if (currentTotal >= MAX_TOTAL_QUANTITY) {
                alert('Maximum total of 4 items allowed per order.');
                return;
            }
            orderItems[name].quantity++;
        } else {
            orderItems[name] = {
                quantity: 1,
                price: price
            };
        }
        updateOrderTable();
        saveOrders();
    }

    function updateOrderTable() {
        const tbody = document.getElementById('orderTableBody');
        tbody.innerHTML = '';
        const totalQuantity = getTotalQuantity();
        
        for (const [name, item] of Object.entries(orderItems)) {
            const tr = document.createElement('tr');
            const addButtonDisabled = totalQuantity >= MAX_TOTAL_QUANTITY ? 'disabled' : '';
            
            tr.innerHTML = `
                <td>${name}</td>
                <td>${item.quantity}</td>
                <td class="add-drop-buttons">
                    <button onclick="modifyQuantity('${name}', 1)" ${addButtonDisabled}>+</button>
                </td>
                <td class="add-drop-buttons">
                    <button onclick="modifyQuantity('${name}', -1)">-</button>
                </td>
                <td>
                    <button class="cancel-btn" onclick="removeItem('${name}')">🗑️</button>
                </td>
            `;
            tbody.appendChild(tr);
        }

        if (totalQuantity > 0) {
            const totalRow = document.createElement('tr');
            totalRow.innerHTML = `
                <td colspan="5" style="text-align: right; font-weight: bold;">
                    Total Items: ${totalQuantity}/4
                </td>
            `;
            tbody.appendChild(totalRow);
        }

        // Update order button state
        const orderButton = document.querySelector('.order-button');
        if (totalQuantity === 0) {
            orderButton.disabled = true;
            orderButton.style.opacity = '0.5';
            orderButton.style.cursor = 'not-allowed';
        } else {
            orderButton.disabled = false;
            orderButton.style.opacity = '1';
            orderButton.style.cursor = 'pointer';
        }
    }

    function modifyQuantity(name, change) {
        if (orderItems[name]) {
            const currentTotal = getTotalQuantity();
            
            if (change > 0 && currentTotal >= MAX_TOTAL_QUANTITY) {
                alert('Maximum total of 4 items allowed per order.');
                return;
            }

            orderItems[name].quantity += change;
            
            if (orderItems[name].quantity <= 0) {
                removeItem(name);
            } else {
                updateOrderTable();
                saveOrders();
            }
        }
    }

    function removeItem(name) {
        delete orderItems[name];
        updateOrderTable();
        saveOrders();
    }

    // Modal handling
    async function showModal(modalId) {
    if (modalId === 'orderModal' && getTotalQuantity() === 0) {
        alert('Please add items to your order first');
        return;
    }
    
    if (modalId === 'checkBillModal') {
        // Check if all orders are served
        try {
            const response = await fetch('check_order_status.php');
            const data = await response.json();
            
            if (!data.allServed) {
                document.getElementById('statusCheckWarning').style.display = 'block';
                return;
            }
            document.getElementById('statusCheckWarning').style.display = 'none';
        } catch (error) {
            console.error('Error checking order status:', error);
            return;
        }
    }
    
    document.getElementById(modalId).style.display = 'block';
}

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    function confirmCheckBill() {
        window.location.href = 'checkbill.php';
    }

    function confirmOrder() {
        const items = Object.entries(orderItems).map(([name, details]) => ({
            name: name,
            quantity: details.quantity
        }));

        fetch('order_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(items)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                orderItems = {};
                localStorage.removeItem('orderItems');
                updateOrderTable();
                closeModal('orderModal');
                alert('Order placed successfully!');
            } else {
                alert(data.message || 'Error placing order');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error placing order. Please try again.');
        });
    }

    // Initialize everything when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        loadSavedOrders();

        // Order button click handler
        const orderButton = document.querySelector('.order-button');
        if (orderButton) {
            orderButton.onclick = () => showModal('orderModal');
        }

        // Check bill button
        const checkBillButton = document.querySelector('.CheckBill-button');
        if (checkBillButton) {
            checkBillButton.onclick = function(e) {
                e.preventDefault();
                showModal('checkBillModal');
            };
        }

        // Close modals on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        };
    });
</script>

        <?php 
        if (isset($stmt)) {
            $stmt->close();
        }
        if (isset($conn)) {
            $conn->close();
        }
        ?>
    </body>
</html>
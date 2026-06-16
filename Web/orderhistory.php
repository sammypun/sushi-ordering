<?php
session_start();
require_once('db-connect.php');

// Check for customer privileges
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'customer' || !isset($_SESSION['OrderID'])) {
    header("Location: login.php");
    exit();
}

try {
    $pdo = getDatabaseConnection('customer');

    // Get only the order items for the current session's OrderID
    $stmt = $pdo->prepare("
        SELECT 
            oi.OrderItemID,
            oi.MenuItemName,
            oi.ItemAmount,
            oi.Status,
            o.OrderID,
            CASE 
                WHEN p.OrderID IS NOT NULL THEN 1
                ELSE 0
            END as isPaid
        FROM Order_ o
        JOIN OrderItem oi ON o.OrderID = oi.OrderID
        LEFT JOIN Payment p ON o.OrderID = p.OrderID
        WHERE o.OrderID = ? 
        ORDER BY oi.OrderItemID ASC
    ");
    
    $stmt->execute([$_SESSION['OrderID']]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Error fetching order history: " . $e->getMessage());
    $orderItems = [];
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Order History | Conveyor Belt Sushi</title>
        <link rel="stylesheet" href="orderhistory-style.css">
        <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
        <style>
            .check-bill-button:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
        </style>
    </head>

    <body>
        <button onclick="window.history.back()" class="back-arrow">
            <i class='bx bx-arrow-back'></i>
        </button>

        <div class="wrapper-history">
            <div class="header">
                <h1>Order History</h1>
            </div>

            <!-- History Table -->
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Item</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orderItems)): ?>
                        <?php 
                        $currentOrderId = null;
                        foreach ($orderItems as $item): 
                            $rowClass = $item['isPaid'] ? ' class="paid-order"' : '';
                        ?>
                            <tr<?php echo $rowClass; ?>>
                                <td>
                                    <?php 
                                    if ($currentOrderId !== $item['OrderID']) {
                                        echo htmlspecialchars($item['OrderID']);
                                        $currentOrderId = $item['OrderID'];
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['MenuItemName']); ?></td>
                                <td><?php echo htmlspecialchars($item['ItemAmount']); ?></td>
                                <td>
                                    <?php if ($item['Status']): ?>
                                        <span class="status-served">Served</span>
                                    <?php else: ?>
                                        <div class="checkbox-wrapper">
                                            <input type="checkbox" 
                                                class="status-checkbox" 
                                                data-orderitem-id="<?php echo $item['OrderItemID']; ?>"
                                                onchange="handleStatusChange(this)">
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">No orders found for this session</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- CheckBill Button -->
            <button onclick="showCheckBillModal()" class="check-bill-button" id="checkBillBtn" disabled>
                Check Bill
            </button>
        </div>

        <!-- Check Bill Modal -->
        <div id="checkBillModal" class="modal">
            <div class="modal-content">
                <h2>Confirm Check Bill</h2>
                <p>Are you sure you want to check your bill? This will finalize your current session.</p>
                <div class="modal-buttons">
                    <button class="modal-confirm" onclick="confirmCheckBill()">Confirm</button>
                    <button class="modal-cancel" onclick="closeModal()">Cancel</button>
                </div>
            </div>
        </div>

        <script>
            function handleStatusChange(checkbox) {
                const orderItemId = checkbox.dataset.orderitemId;
                const isChecked = checkbox.checked;

                updateSingleStatus(orderItemId, isChecked)
                    .then(response => {
                        if (!response.success) {
                            throw new Error('Status update failed');
                        }
                        checkAllServed();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        checkbox.checked = !isChecked; // Revert the checkbox
                    });
            }

            function updateSingleStatus(orderItemId, status) {
                return fetch('update_single_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        orderItemId: orderItemId,
                        status: status
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                });
            }

            async function checkAllServed() {
                // Count checked boxes and served items
                const checkedBoxes = document.querySelectorAll('.status-checkbox:checked').length;
                const servedSpans = document.querySelectorAll('.status-served').length;
                const totalItems = document.querySelectorAll('.status-checkbox').length + servedSpans;
                
                // Enable button if all items are served
                const checkBillBtn = document.getElementById('checkBillBtn');
                if (checkBillBtn) {
                    checkBillBtn.disabled = (checkedBoxes + servedSpans) !== totalItems;
                }
            }

            function showCheckBillModal() {
                if (!document.getElementById('checkBillBtn').disabled) {
                    const modal = document.getElementById('checkBillModal');
                    modal.style.display = 'block';
                }
            }

            function closeModal() {
                const modal = document.getElementById('checkBillModal');
                modal.style.display = 'none';
            }

            function confirmCheckBill() {
                window.location.href = 'checkbill.php';
            }

            // Initialize everything when the page loads
            document.addEventListener('DOMContentLoaded', function() {
                const checkboxes = document.querySelectorAll('.status-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', () => handleStatusChange(checkbox));
                });
                checkAllServed();
            });

            // Close modal when clicking outside
            window.onclick = function(event) {
                const modal = document.getElementById('checkBillModal');
                if (event.target === modal) {
                    closeModal();
                }
            }
        </script>
    </body>
</html>
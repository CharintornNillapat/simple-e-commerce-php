<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT o.id, p.name, o.quantity, o.total_price, o.order_date, o.status 
        FROM orders o 
        JOIN products p ON o.product_id = p.id 
        WHERE o.user_id = ? 
        ORDER BY o.order_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$orders = $stmt->get_result();

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_order'])) {
    $order_id = $_POST['order_id'];
    $sql = "SELECT status, quantity, product_id FROM orders WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $order_id, $user_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if ($order && in_array($order['status'], ['pending', 'processing'])) {
        $quantity = $order['quantity'];
        $product_id = $order['product_id'];

        // Restore stock in products table
        $sql = "UPDATE products SET quantity = quantity + ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $quantity, $product_id);
        $stmt->execute();

        // Update order status to cancelled
        $sql = "UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $order_id, $user_id);
        $stmt->execute();

        header('Location: order_history.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History</title>
    <link rel="stylesheet" href="dashboard_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            max-width: 800px;
        }
        .form-grid div {
            margin-bottom: 1rem;
        }
        .form-grid label {
            display: block;
            margin-bottom: 0.25rem;
            font-weight: 500;
        }
        .form-grid input, .form-grid textarea, .form-grid select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .action-card {
            background-color: #f9f9f9;
            padding: 1.5rem;
            border-radius: 4px;
        }
        .action-btn {
            padding: 0.5rem 1rem;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 2px;
        }
        .action-btn.cancel {
            background-color: #e74c3c;
        }
        .action-btn:hover {
            background-color: #2980b9;
        }
        .action-btn.cancel:hover {
            background-color: #c0392b;
        }
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-history"></i> Order History</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li class="nav-item">
                        <a href="index.php" class="nav-link">
                            <i class="fas fa-store"></i>
                            <span>Products</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="cart.php" class="nav-link">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Cart</span>
                        </a>
                    </li>
                    <li class="nav-item active">
                        <a href="order_history.php" class="nav-link">
                            <i class="fas fa-history"></i>
                            <span>Order History</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>

                            <l>
                        </l>
                    </li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span>Logged in as: <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                </div>
            </div>
        </aside>
        <main class="main-content">
            <div class="content-header">
                <h1>Order History</h1>
                <div class="breadcrumb">
                    <span>Home</span> / <span class="current">Order History</span>
                </div>
            </div>
            <div class="quick-actions">
                <div class="action-grid">
                    <div class="action-card" style="overflow-x: auto; padding: 1.5rem;">
                        <?php
                        if ($orders->num_rows == 0) {
                            echo "<p style='padding: 1rem;'>No orders found.</p>";
                        } else {
                            echo "<table><thead><tr><th>Order ID</th><th>Product</th><th>Quantity</th><th>Total Price</th><th>Date</th><th>Status</th><th>Action</th></tr></thead><tbody>";
                            while ($order = $orders->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $order['id'] . "</td>";
                                echo "<td>" . htmlspecialchars($order['name']) . "</td>";
                                echo "<td>" . $order['quantity'] . "</td>";
                                echo "<td>$" . number_format($order['total_price'], 2) . "</td>";
                                echo "<td>" . $order['order_date'] . "</td>";
                                echo "<td>" . htmlspecialchars($order['status']) . "</td>";
                                if (in_array($order['status'], ['pending', 'processing'])) {
                                    echo "<td><form method='POST' style='display: inline;'><input type='hidden' name='order_id' value='" . $order['id'] . "'><button type='submit' name='cancel_order' class='action-btn cancel'>Cancel</button></form></td>";
                                } else {
                                    echo "<td>-</td>";
                                }
                                echo "</tr>";
                            }
                            echo "</tbody></table>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order History</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Order History</h2>
        <?php if (isset($_SESSION['name'])): ?>
            <p>Logged in as: <?php echo htmlspecialchars($_SESSION['name']); ?> (<a href="logout.php">Logout</a>)</p>
        <?php endif; ?>
        <nav>
            <a href="index.php">Back to Home</a>
            <a href="order.php">View Cart</a>
        </nav>
        <?php
        if ($orders->num_rows == 0) {
            echo "<p>No orders found.</p>";
        } else {
            echo "<table><thead><tr><th>Order ID</th><th>Product</th><th>Quantity</th><th>Total Price</th><th>Date</th><th>Status</th></tr></thead><tbody>";
            while ($order = $orders->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $order['id'] . "</td>";
                echo "<td>" . htmlspecialchars($order['name']) . "</td>";
                echo "<td>" . $order['quantity'] . "</td>";
                echo "<td>$" . number_format($order['total_price'], 2) . "</td>";
                echo "<td>" . $order['order_date'] . "</td>";
                echo "<td>" . htmlspecialchars($order['status']) . "</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
        }
        ?>
    </div>
</body>
</html>
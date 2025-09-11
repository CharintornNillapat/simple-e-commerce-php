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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="flex min-h-screen">
        <aside class="w-64 bg-gray-800 text-white p-4">
            <div class="sidebar-header mb-6">
                <h2 class="text-xl flex items-center"><i class="fas fa-history mr-2"></i> Order History</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li class="nav-item mb-2">
                        <a href="index.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-store mr-2"></i>
                            <span>Products</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="cart.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'cart.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-shopping-cart mr-2"></i>
                            <span>Cart</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="order_history.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'order_history.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-history mr-2"></i>
                            <span>Order History</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="logout.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="sidebar-footer mt-auto">
                <div class="user-info flex items-center">
                    <i class="fas fa-user-circle mr-2"></i>
                    <span>Logged in as: <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                </div>
            </div>
        </aside>
        <main class="flex-1 p-6">
            <div class="content-header mb-6">
                <h1 class="text-2xl font-bold">Order History</h1>
                <div class="breadcrumb text-gray-600">
                    <span>Home</span> / <span class="current">Order History</span>
                </div>
            </div>
            <div class="quick-actions">
                <div class="action-grid">
                    <div class="action-card bg-gray-100 p-6 overflow-x-auto">
                        <?php
                        if ($orders->num_rows == 0) {
                            echo "<p class='text-center p-4'>No orders found.</p>";
                        } else {
                            echo "<table class='w-full text-left'><thead><tr class='border-b'><th class='p-2'>Order ID</th><th class='p-2'>Product</th><th class='p-2'>Quantity</th><th class='p-2'>Total Price</th><th class='p-2'>Date</th><th class='p-2'>Status</th><th class='p-2'>Action</th></tr></thead><tbody>";
                            while ($order = $orders->fetch_assoc()) {
                                echo "<tr class='border-b'>";
                                echo "<td class='p-2'>" . $order['id'] . "</td>";
                                echo "<td class='p-2'>" . htmlspecialchars($order['name']) . "</td>";
                                echo "<td class='p-2'>" . $order['quantity'] . "</td>";
                                echo "<td class='p-2'>$" . number_format($order['total_price'], 2) . "</td>";
                                echo "<td class='p-2'>" . $order['order_date'] . "</td>";
                                echo "<td class='p-2'>" . htmlspecialchars($order['status']) . "</td>";
                                if (in_array($order['status'], ['pending', 'processing'])) {
                                    echo "<td class='p-2'><form method='POST' class='inline-block'><input type='hidden' name='order_id' value='" . $order['id'] . "'><button type='submit' name='cancel_order' class='px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600'>Cancel</button></form></td>";
                                } else {
                                    echo "<td class='p-2'>-</td>";
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
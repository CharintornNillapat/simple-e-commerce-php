<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle remove item
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_item'])) {
    $cart_id = $_POST['cart_id'];
    $sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $cart_id, $user_id);
    $stmt->execute();
    header('Location: cart.php');
    exit;
}

// Handle confirm purchase
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_purchase'])) {
    $sql = "SELECT c.id, c.product_id, c.quantity, p.price, p.quantity as stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $cart_items = $stmt->get_result();

    $can_proceed = true;
    while ($item = $cart_items->fetch_assoc()) {
        if ($item['quantity'] > $item['stock']) {
            $can_proceed = false;
            $error = "Insufficient stock for " . $item['product_id'] . ". Available: " . $item['stock'];
            break;
        }
    }

    if ($can_proceed) {
        $cart_items->data_seek(0);
        while ($item = $cart_items->fetch_assoc()) {
            $total_price = $item['price'] * $item['quantity'];
            $sql = "INSERT INTO orders (user_id, product_id, quantity, total_price, status) VALUES (?, ?, ?, ?, 'pending')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iiid', $user_id, $item['product_id'], $item['quantity'], $total_price);
            $stmt->execute();

            $new_quantity = $item['stock'] - $item['quantity'];
            $sql = "UPDATE products SET quantity = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $new_quantity, $item['product_id']);
            $stmt->execute();
        }
        $sql = "DELETE FROM cart WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        header('Location: confirm_purchase.php');
    }
}

// Fetch cart items
$sql = "SELECT c.id, c.product_id, c.quantity, p.name, p.price, c.added_at FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$cart_items = $stmt->get_result();

$total = 0;
while ($item = $cart_items->fetch_assoc()) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart</title>
    <link rel="stylesheet" href="dashboard_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-shopping-cart"></i> Cart</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li class="nav-item">
                        <a href="index.php" class="nav-link">
                            <i class="fas fa-store"></i>
                            <span>Products</span>
                        </a>
                    </li>
                    <li class="nav-item active">
                        <a href="cart.php" class="nav-link">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Cart</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="order_history.php" class="nav-link">
                            <i class="fas fa-history"></i>
                            <span>Order History</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
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
                <h1>Your Cart</h1>
                <div class="breadcrumb">
                    <span>Home</span> / <span class="current">Cart</span>
                </div>
            </div>
            <div class="quick-actions">
                <div class="action-grid">
                    <?php if (isset($error)) echo "<p class='error' style='margin: 1rem;'>$error</p>"; ?>
                    <?php if ($cart_items->num_rows == 0): ?>
                        <p style="padding: 2rem;">Your cart is empty.</p>
                    <?php else: ?>
                        <div class="action-card" style="overflow-x: auto; padding: 1.5rem;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th>Added At</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $cart_items->data_seek(0); while ($item = $cart_items->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                            <td><?php echo $item['added_at']; ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" name="remove_item" class="action-btn" style="padding: 0.5rem 1rem; background: #e74c3c;">Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            <p style="margin-top: 1rem; font-size: 1.2rem;">Total: $<?php echo number_format($total, 2); ?></p>
                            <form method="POST" style="margin-top: 1rem;">
                                <button type="submit" name="confirm_purchase" class="action-btn" style="width: 100%;">Confirm Purchase</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
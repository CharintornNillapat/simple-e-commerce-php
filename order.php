<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$total = 0;
$error = '';

if (isset($_POST['remove_item'])) {
    $cart_id = $_POST['cart_id'];
    $sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $cart_id, $user_id);
    $stmt->execute();
    header('Location: order.php');
    exit;
}

if (isset($_POST['confirm_purchase'])) {
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
        $cart_items->data_seek(0); // Reset pointer
        while ($item = $cart_items->fetch_assoc()) {
            $total_price = $item['price'] * $item['quantity'];
            $sql = "INSERT INTO orders (user_id, product_id, quantity, total_price) VALUES (?, ?, ?, ?)";
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
        exit;
    }
}

// Fetch cart items
$sql = "SELECT c.id, c.product_id, c.quantity, p.name, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$cart_items = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cart</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Your Cart</h2>
        <?php if (isset($_SESSION['name'])): ?>
            <p>Logged in as: <?php echo htmlspecialchars($_SESSION['name']); ?> (<a href="logout.php">Logout</a>)</p>
        <?php endif; ?>
        <nav>
            <a href="index.php">Back to Home</a>
        </nav>
        <?php
        if ($error) echo "<p class='error'>$error</p>";
        if ($cart_items->num_rows == 0) {
            echo "<p>Your cart is empty.</p>";
        } else {
            echo "<table><thead><tr><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th><th>Action</th></tr></thead><tbody>";
            while ($item = $cart_items->fetch_assoc()) {
                $item_total = $item['price'] * $item['quantity'];
                $total += $item_total;
                echo "<tr>";
                echo "<td>" . htmlspecialchars($item['name']) . "</td>";
                echo "<td>$" . $item['price'] . "</td>";
                echo "<td>" . $item['quantity'] . "</td>";
                echo "<td>$" . number_format($item_total, 2) . "</td>";
                echo "<td><form method='POST'><input type='hidden' name='cart_id' value='" . $item['id'] . "'><button type='submit' name='remove_item'>Remove</button></form></td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
            echo "<p>Total: $" . number_format($total, 2) . "</p>";
            echo "<form method='POST'><button type='submit' name='confirm_purchase'>Confirm Purchase</button></form>";
        }
        ?>
    </div>
</body>
</html>
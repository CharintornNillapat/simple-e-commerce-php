<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in as a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: login.php');
    exit;
}

// Handle add to cart
if (isset($_GET['add_to_cart']) && isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT id, quantity FROM products WHERE id = ? AND quantity > 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if ($product) {
        $sql = "SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $user_id, $product_id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();

        if ($existing) {
            $new_quantity = $existing['quantity'] + 1;
            $sql = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iii', $new_quantity, $user_id, $product_id);
        } else {
            $quantity = 1;
            $sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iii', $user_id, $product_id, $quantity);
        }
        $stmt->execute();
    }
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Welcome to the Shop</h2>
        <?php if (isset($_SESSION['name'])): ?>
            <p>Logged in as: <?php echo htmlspecialchars($_SESSION['name']); ?> (<a href="logout.php">Logout</a>)</p>
        <?php endif; ?>
        <nav>
            <a href="orders.php">View Cart</a>
            <a href="order_history.php">Order History</a>
        </nav>
        <h3>Available Products</h3>
        <div class="product-grid">
            <?php
            // Fetch available products with image
            $sql = "SELECT id, name, price, quantity, image FROM products WHERE quantity > 0 ORDER BY order_index ASC"; // Added order_index
            $products = $conn->query($sql);
            while ($product = $products->fetch_assoc()):
            ?>
                <div class="product-card">
                    <div class="product-poster-frame">
                        <?php if ($product['image']): ?>
                            <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                            <span style="color: #888;">No Image Available</span>
                        <?php endif; ?>
                    </div>
                    <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                    <p>Price: $<?php echo $product['price']; ?></p>
                    <p>Stock: <?php echo $product['quantity']; ?></p>
                    
                    <a href="?add_to_cart=1&product_id=<?php echo $product['id']; ?>" class="button">Add to Cart</a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>
<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in (must be customer or admin)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['customer', 'admin'])) {
    header('Location: login.php');
    exit;
}

// Handle add to cart (only customers can add)
if ($_SESSION['role'] === 'customer' && isset($_GET['add_to_cart']) && isset($_GET['product_id'])) {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop</title>
    <link rel="stylesheet" href="dashboard_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-store"></i> Shop</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li class="nav-item active">
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
                <h1>Products</h1>
                <div class="breadcrumb">
                    <span>Home</span> / <span class="current">Products</span>
                </div>
            </div>
            <div class="quick-actions">
                <div class="action-grid">
                    <?php
                    $sql = "SELECT id, name, price, quantity, image FROM products WHERE quantity > 0 ORDER BY order_index ASC";
                    $products = $conn->query($sql);
                    if ($products->num_rows == 0) {
                        echo "<p style='padding: 2rem;'>No products available.</p>";
                    } else {
                        while ($product = $products->fetch_assoc()):
                    ?>
                        <div class="action-card" style="text-align: center; padding: 1.5rem;">
                            <?php if ($product['image']): ?>
                                <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="max-width: 150px; max-height: 150px; margin-bottom: 1rem;">
                            <?php else: ?>
                                <span style="color: #888;">No Image Available</span>
                            <?php endif; ?>
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p>Price: $<?php echo number_format($product['price'], 2); ?></p>
                            <p>Stock: <?php echo $product['quantity']; ?></p>
                            <a href="?add_to_cart=1&product_id=<?php echo $product['id']; ?>" class="action-btn" style="width: 100%; margin-top: 1rem;">Add to Cart</a>
                        </div>
                    <?php endwhile; } ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
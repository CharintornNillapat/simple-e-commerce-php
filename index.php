<?php
session_start();
require_once 'db_connect.php';

// Fetch products for display
$sql = "SELECT * FROM products";
$products = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shop System - Home</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Welcome to Shop System</h2>
        <?php if (isset($_SESSION['name'])): ?>
            <p class="session-info">Logged in as: <?php echo htmlspecialchars($_SESSION['name']); ?> (<a href="logout.php">Logout</a>)</p>
        <?php endif; ?>
        <nav>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="customer_login.php">Customer Login</a>
                <a href="register.php">Register</a>
            <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                <a href="dashboard.php">Back to Dashboard</a>
            <?php endif; ?>
        </nav>
        <h3>Our Products</h3>
        <div class="product-grid">
            <?php while ($product = $products->fetch_assoc()): ?>
                <div class="product-card">
                    <?php if ($product['image']): ?>
                        <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                    <?php endif; ?>
                    <h4><?php echo $product['name']; ?></h4>
                    <p>Price: $<?php echo $product['price']; ?></p>
                    <p><?php echo $product['description'] ? substr($product['description'], 0, 50) . '...' : 'No description'; ?></p>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    
</body>
</html> 
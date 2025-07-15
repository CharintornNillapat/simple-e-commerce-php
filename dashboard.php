<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit;
}

// Fetch counts for dashboard stats
$sql = "SELECT COUNT(*) as product_count FROM products";
$product_count = $conn->query($sql)->fetch_assoc()['product_count'];

$sql = "SELECT COUNT(*) as customer_count FROM users WHERE role = 'customer'";
$customer_count = $conn->query($sql)->fetch_assoc()['customer_count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Admin Dashboard</h2>
        <?php if (isset($_SESSION['name'])): ?>
            <p class="session-info">Logged in as: <?php echo htmlspecialchars($_SESSION['name']); ?> (<a href="logout.php">Logout</a>)</p>
        <?php endif; ?>
        <nav>
            <a href="index.php">Home</a>
            <a href="products.php">Manage Products</a>
            <a href="customers.php">Manage Customers</a>
            <a href="register.php">Register New Customer</a>
        </nav>
        <h3>Overview</h3>
        <p><strong>Total Products:</strong> <?php echo $product_count; ?></p>
        <p><strong>Total Customers:</strong> <?php echo $customer_count; ?></p>
        <div class="dashboard-actions">
            <h4>Quick Actions</h4>
            <p><a href="products.php" class="button">Add New Product</a></p>
            <p><a href="customers.php" class="button">Add New Customer</a></p>
        </div>
    </div>
</body>
</html>
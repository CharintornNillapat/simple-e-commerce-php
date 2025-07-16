<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit;
}

// Fetch counts with minimal queries
$product_count = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$customer_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")->fetch_assoc()['count'];
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
        <?php if (isset($_SESSION['name'])): ?>
            <p>Logged in as: <?php echo htmlspecialchars($_SESSION['name']); ?> (<a href="logout.php">Logout</a>)</p>
        <?php endif; ?>
        <nav>
            <a href="index.php">Home</a>
            <a href="products.php">Manage Products</a>
            <a href="customers.php">Manage Customers</a>
            <a href="register.php">Register</a>
        </nav>
        <h2>Dashboard</h2>
        <p>Total Products: <?php echo $product_count; ?></p>
        <p>Total Customers: <?php echo $customer_count; ?></p>
        <p><a href="products.php">Add Product</a> | <a href="customers.php">Add Customer</a></p>
    </div>
</body>
</html>
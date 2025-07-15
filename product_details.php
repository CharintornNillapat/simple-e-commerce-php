<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: products.php');
    exit;
}

$id = $_GET['id'];
$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header('Location: products.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Details</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Product Details</h2>
        <?php if (isset($_SESSION['name'])): ?>
            <p class="session-info">Logged in as: <?php echo htmlspecialchars($_SESSION['name']); ?> (<a href="logout.php">Logout</a>)</p>
        <?php endif; ?>
        <nav>
            <a href="products.php">Back to Products</a>
            <a href="index.php">Home</a>
            <a href="register.php">Register New Customer</a>
        </nav>
        <h3><?php echo $product['name']; ?></h3>
        <?php if ($product['image']): ?>
            <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
        <?php endif; ?>
        <p><strong>Price:</strong> $<?php echo $product['price']; ?></p>
        <p><strong>Description:</strong> <?php echo $product['description'] ?: 'No description'; ?></p>
        <p><strong>Created At:</strong> <?php echo $product['created_at']; ?></p>
    </div>
</body>
</html>
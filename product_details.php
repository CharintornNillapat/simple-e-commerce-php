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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Product Details</title>
    <link rel="stylesheet" href="dashboard_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-store"></i> Admin Panel</h2>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item active">
                        <a href="products.php" class="nav-link">
                            <i class="fas fa-box"></i>
                            <span>Products</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="customers.php" class="nav-link">
                            <i class="fas fa-users"></i>
                            <span>Customers</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="orders.php" class="nav-link">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Orders</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="admin_reorder_products.php" class="nav-link">
                            <i class="fas fa-sort"></i>
                            <span>Reorder Products</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <h1>Product Details</h1>
                <div class="breadcrumb">
                    <span>Home</span> / <span class="current">Products</span> / <span class="current">Details</span>
                </div>
            </div>

            <div class="quick-actions">
                <div class="action-grid">
                    <div class="action-card" style="padding: 2rem; text-align: left;">
                        <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                        <?php if ($product['image']): ?>
                            <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image" style="max-width: 300px; max-height: 300px; margin-bottom: 1rem;">
                        <?php endif; ?>
                        <p><strong>Price:</strong> $<?php echo number_format($product['price'], 2); ?></p>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($product['description'] ?: 'No description'); ?></p>
                        <p><strong>SKU:</strong> <?php echo htmlspecialchars($product['sku'] ?: 'N/A'); ?></p>
                        <p><strong>Quantity:</strong> <?php echo $product['quantity']; ?></p>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category'] ?: 'N/A'); ?></p>
                        <p><strong>Weight (kg):</strong> <?php echo number_format($product['weight'] ?: 0, 2); ?></p>
                        <p><strong>Brand:</strong> <?php echo htmlspecialchars($product['brand'] ?: 'N/A'); ?></p>
                        <p><strong>Created At:</strong> <?php echo $product['created_at']; ?></p>
                        <a href="products.php?edit=<?php echo $product['id']; ?>" class="action-btn" style="margin-top: 1rem; display: inline-block;">Edit Product</a>
                        <a href="products.php" class="action-btn" style="margin-top: 1rem; margin-left: 1rem; display: inline-block;">Back to Products</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
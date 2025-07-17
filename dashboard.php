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
$order_count = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$pending_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="dashboard_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-store"></i> Dashboard </h2>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="nav-item active">
                        <a href="dashboard.php" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
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
                <h1>Dashboard Overview</h1>
                <div class="breadcrumb">
                    <span>Home</span> / <span class="current">Dashboard</span>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon products">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $product_count; ?></h3>
                        <p>Total Products</p>
                    </div>
                    <div class="stat-action">
                        <a href="products.php">Manage <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon customers">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $customer_count; ?></h3>
                        <p>Total Customers</p>
                    </div>
                    <div class="stat-action">
                        <a href="customers.php">Manage <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orders">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $order_count; ?></h3>
                        <p>Total Orders</p>
                    </div>
                    <div class="stat-action">
                        <a href="orders.php">View All <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $pending_orders; ?></h3>
                        <p>Pending Orders</p>
                    </div>
                    <div class="stat-action">
                        <a href="orders.php?status=pending">Review <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="action-grid">
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-plus"></i>
                        </div>
                        <h3>Add Product</h3>
                        <p>Add new products to your inventory</p>
                        <a href="products.php" class="action-btn">Add Product</a>
                    </div>
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-sort"></i>
                        </div>
                        <h3>Reorder Products</h3>
                        <p>Organize product display order</p>
                        <a href="admin_reorder_products.php" class="action-btn">Reorder</a>
                    </div>

                    <div class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <h3>View Store</h3>
                        <p>See how customers view your store</p>
                        <a href="index.php" class="action-btn">View Store</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
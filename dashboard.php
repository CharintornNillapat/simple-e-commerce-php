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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-800 text-white p-4">
            <div class="sidebar-header mb-6">
                <h2 class="text-xl flex items-center"><i class="fas fa-store mr-2"></i> Dashboard</h2>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="nav-item mb-2">
                        <a href="dashboard.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="products.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-box"></i>
                            <span>Products</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="customers.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-users"></i>
                            <span>Customers</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="orders.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Orders</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="admin_reorder_products.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'admin_reorder_products.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-sort"></i>
                            <span>Reorder Products</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer mt-auto">
                <div class="user-info flex items-center">
                    <i class="fas fa-user-circle mr-2"></i>
                    <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                </div>
                <a href="logout.php" class="block text-white hover:bg-gray-700 p-2 rounded mt-4">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-6">
            <div class="content-header mb-6">
                <h1 class="text-2xl font-bold">Dashboard Overview</h1>
                <div class="breadcrumb text-gray-600">
                    <span>Home</span> / <span class="current">Dashboard</span>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="stat-card flex items-center p-4 bg-white rounded shadow">
                    <div class="stat-icon text-yellow-500 mr-4">
                        <i class="fas fa-box text-2xl"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="text-xl font-semibold"><?php echo $product_count; ?></h3>
                        <p class="text-gray-600">Total Products</p>
                    </div>
                    <div class="stat-action ml-auto">
                        <a href="products.php" class="text-blue-500 hover:underline">Manage <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>

                <div class="stat-card flex items-center p-4 bg-white rounded shadow">
                    <div class="stat-icon text-green-500 mr-4">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="text-xl font-semibold"><?php echo $customer_count; ?></h3>
                        <p class="text-gray-600">Total Customers</p>
                    </div>
                    <div class="stat-action ml-auto">
                        <a href="customers.php" class="text-blue-500 hover:underline">Manage <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>

                <div class="stat-card flex items-center p-4 bg-white rounded shadow">
                    <div class="stat-icon text-blue-500 mr-4">
                        <i class="fas fa-shopping-cart text-2xl"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="text-xl font-semibold"><?php echo $order_count; ?></h3>
                        <p class="text-gray-600">Total Orders</p>
                    </div>
                    <div class="stat-action ml-auto">
                        <a href="orders.php" class="text-blue-500 hover:underline">View All <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>

                <div class="stat-card flex items-center p-4 bg-white rounded shadow">
                    <div class="stat-icon text-red-500 mr-4">
                        <i class="fas fa-clock text-2xl"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="text-xl font-semibold"><?php echo $pending_orders; ?></h3>
                        <p class="text-gray-600">Pending Orders</p>
                    </div>
                    <div class="stat-action ml-auto">
                        <a href="orders.php?status=pending" class="text-blue-500 hover:underline">Review <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2 class="text-xl font-semibold mb-6">Quick Actions</h2>
                <div class="action-grid grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="action-card flex flex-col items-center p-6 bg-white rounded shadow">
                        <div class="action-icon text-green-500 mb-4">
                            <i class="fas fa-plus text-3xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold mb-2">Add Product</h3>
                        <p class="text-gray-600 mb-4 text-center">Add new products to your inventory</p>
                        <a href="products.php" class="mt-auto bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Add Product</a>
                    </div>
                    <div class="action-card flex flex-col items-center p-6 bg-white rounded shadow">
                        <div class="action-icon text-purple-500 mb-4">
                            <i class="fas fa-sort text-3xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold mb-2">Reorder Products</h3>
                        <p class="text-gray-600 mb-4 text-center">Organize product display order</p>
                        <a href="admin_reorder_products.php" class="mt-auto bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Reorder</a>
                    </div>
                    <div class="action-card flex flex-col items-center p-6 bg-white rounded shadow">
                        <div class="action-icon text-indigo-500 mb-4">
                            <i class="fas fa-home text-3xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold mb-2">View Store</h3>
                        <p class="text-gray-600 mb-4 text-center">See how customers view your store</p>
                        <a href="index.php" class="mt-auto bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">View Store</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-800 text-white p-4">
            <div class="sidebar-header mb-6">
                <h2 class="text-xl flex items-center"><i class="fas fa-store mr-2"></i> Admin Panel</h2>
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
                <h1 class="text-2xl font-bold">Product Details</h1>
                <div class="breadcrumb text-gray-600">
                    <span>Home</span> / <span class="current">Products</span> / <span class="current">Details</span>
                </div>
            </div>

            <div class="quick-actions">
                <div class="action-grid">
                    <div class="action-card p-8 text-left">
                        <h2 class="text-2xl font-bold mb-4"><?php echo htmlspecialchars($product['name']); ?></h2>
                        <?php if ($product['image']): ?>
                            <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="max-w-[300px] max-h-[300px] mb-4">
                        <?php endif; ?>
                        <p class="mb-2"><strong>Price:</strong> $<span class="font-semibold"><?php echo number_format($product['price'], 2); ?></span></p>
                        <p class="mb-2"><strong>Description:</strong> <?php echo htmlspecialchars($product['description'] ?: 'No description'); ?></p>
                        <p class="mb-2"><strong>SKU:</strong> <?php echo htmlspecialchars($product['sku'] ?: 'N/A'); ?></p>
                        <p class="mb-2"><strong>Quantity:</strong> <?php echo $product['quantity']; ?></p>
                        <p class="mb-2"><strong>Category:</strong> <?php echo htmlspecialchars($product['category'] ?: 'N/A'); ?></p>
                        <p class="mb-2"><strong>Weight (kg):</strong> <?php echo number_format($product['weight'] ?: 0, 2); ?></p>
                        <p class="mb-2"><strong>Brand:</strong> <?php echo htmlspecialchars($product['brand'] ?: 'N/A'); ?></p>
                        <p class="mb-2"><strong>Created At:</strong> <?php echo $product['created_at']; ?></p>
                        <div class="mt-4">
                            <a href="products.php?edit=<?php echo $product['id']; ?>" class="inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mr-2">Edit Product</a>
                            <a href="products.php" class="inline-block bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Back to Products</a>
                        </div>
                    </div>
                </div> e
            </div>
        </main>
    </div>
</body>
</html>
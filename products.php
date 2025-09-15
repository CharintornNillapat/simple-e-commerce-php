<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit;
}

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $sql = "DELETE FROM products WHERE id = ? AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: products.php');
    exit;
}

// Fetch products
$sql = "SELECT id, name, category, price, quantity, image, status FROM products ORDER BY name ASC";
$products = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Products</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="flex min-h-screen">
        <aside class="w-64 bg-gray-800 text-white p-4">
            <div class="sidebar-header mb-6">
                <h2 class="text-xl flex items-center"><i class="fas fa-store mr-2"></i> Admin Panel</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li class="nav-item mb-2">
                        <a href="dashboard.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item active mb-2">
                        <a href="products.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded bg-gray-700">
                            <i class="fas fa-box mr-2"></i> Products
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="customers.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-users mr-2"></i> Customers
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="orders.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-shopping-cart mr-2"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="admin_reorder_products.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'admin_reorder_products.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-sort mr-2"></i> Reorder Products
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="product_posts.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'product_posts.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-edit mr-2"></i> Product Posts
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
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
            </div>
        </aside>
        <main class="flex-1 p-6">
            <div class="content-header mb-6">
                <h1 class="text-2xl font-bold">Products Management</h1>
                <div class="breadcrumb text-gray-600">
                    <span>Home</span> / <span class="current">Products</span>
                </div>
            </div>
            <div class="quick-actions mb-6">
                <a href="product_posts.php" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Add New Product</a>
            </div>
            <div class="quick-actions">
                <div class="action-grid">
                    <div class="action-card overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b">
                                    <th class="p-2">Name</th>
                                    <th class="p-2">Category</th>
                                    <th class="p-2">Price</th>
                                    <th class="p-2">Stock</th>
                                    <th class="p-2">Image</th>
                                    <th class="p-2">Status</th>
                                    <th class="p-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($product = $products->fetch_assoc()): ?>
                                    <tr class="border-b">
                                        <td class="p-2"><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td class="p-2"><?php echo htmlspecialchars($product['category']); ?></td>
                                        <td class="p-2">$<?php echo number_format($product['price'], 2); ?></td>
                                        <td class="p-2"><?php echo $product['quantity']; ?></td>
                                        <td class="p-2">
                                            <?php if ($product['image']): ?>
                                                <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-16 h-16 object-cover">
                                            <?php else: ?>
                                                No Image
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-2"><?php echo htmlspecialchars($product['status']); ?></td>
                                        <td class="p-2">
                                            <a href="product_posts.php?id=<?php echo $product['id']; ?>" class="text-blue-500 hover:underline mr-2">Edit</a>
                                            <a href="?delete=<?php echo $product['id']; ?>" class="text-red-500 hover:underline" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
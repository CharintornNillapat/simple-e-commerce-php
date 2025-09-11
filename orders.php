<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit;
}

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $sql = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $status, $order_id);
    $stmt->execute();
    header('Location: orders.php');
    exit;
}

// Handle order search/filter
$search_query = '';
$orders = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $search_query = $_POST['search_query'];
    $sql = "SELECT o.id, u.name AS user_name, p.name AS product_name, o.quantity, o.total_price, o.order_date, o.status 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            JOIN products p ON o.product_id = p.id 
            WHERE o.status LIKE ? OR u.name LIKE ? OR p.name LIKE ?";
    $like_query = '%' . $search_query . '%';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $like_query, $like_query, $like_query);
    $stmt->execute();
    $orders = $stmt->get_result();
} else {
    $sql = "SELECT o.id, u.name AS user_name, p.name AS product_name, o.quantity, o.total_price, o.order_date, o.status 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            JOIN products p ON o.product_id = p.id 
            ORDER BY o.order_date DESC";
    $orders = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Orders</title>
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
                <h1 class="text-2xl font-bold">Orders</h1>
                <div class="breadcrumb text-gray-600">
                    <span>Home</span> / <span class="current">Orders</span>
                </div>
            </div>

            <div class="stats-grid grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="stat-card flex items-center p-4 bg-white rounded shadow">
                    <div class="stat-icon text-blue-500 mr-4">
                        <i class="fas fa-shopping-cart text-2xl"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="text-xl font-semibold"><?php echo $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count']; ?></h3>
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
                        <h3 class="text-xl font-semibold"><?php echo $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")->fetch_assoc()['count']; ?></h3>
                        <p class="text-gray-600">Pending Orders</p>
                    </div>
                    <div class="stat-action ml-auto">
                        <a href="orders.php?status=pending" class="text-blue-500 hover:underline">Review <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>
            </div>

            <div class="quick-actions">
                <h2 class="text-xl font-semibold mb-4">Order Management</h2>
                <div class="action-grid">
                    <div class="action-card p-8 text-left">
                        <h3 class="text-lg font-semibold mb-4">Search Orders</h3>
                        <form method="POST" class="flex gap-4 mb-6">
                            <input type="text" name="search_query" placeholder="Search by status, user, or product" value="<?php echo htmlspecialchars($search_query); ?>" class="flex-1 p-2 border border-gray-300 rounded">
                            <button type="submit" name="search" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Search</button>
                            <?php if ($search_query): ?>
                                <a href="orders.php" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">Clear Search</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="quick-actions mt-6">
                <h2 class="text-xl font-semibold mb-4">Order List</h2>
                <div class="action-grid">
                    <div class="action-card overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b">
                                    <th class="p-2">Order ID</th>
                                    <th class="p-2">User</th>
                                    <th class="p-2">Product</th>
                                    <th class="p-2">Quantity</th>
                                    <th class="p-2">Total Price</th>
                                    <th class="p-2">Date</th>
                                    <th class="p-2">Status</th>
                                    <th class="p-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $orders->fetch_assoc()): ?>
                                <tr class="border-b">
                                    <td class="p-2"><?php echo $order['id']; ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($order['user_name']); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($order['product_name']); ?></td>
                                    <td class="p-2"><?php echo $order['quantity']; ?></td>
                                    <td class="p-2">$<?php echo number_format($order['total_price'], 2); ?></td>
                                    <td class="p-2"><?php echo $order['order_date']; ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($order['status']); ?></td>
                                    <td class="p-2">
                                        <form method="POST" class="inline-block">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <select name="status" onchange="this.form.submit()" class="p-1 border border-gray-300 rounded">
                                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            </select>
                                            <input type="hidden" name="update_status">
                                        </form>
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
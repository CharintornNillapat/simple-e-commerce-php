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
                    <li class="nav-item active">
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
                <h1>Orders</h1>
                <div class="breadcrumb">
                    <span>Home</span> / <span class="current">Orders</span>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon orders">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count']; ?></h3>
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
                        <h3><?php echo $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")->fetch_assoc()['count']; ?></h3>
                        <p>Pending Orders</p>
                    </div>
                    <div class="stat-action">
                        <a href="orders.php?status=pending">Review <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>

            <div class="quick-actions">
                <h2>Order Management</h2>
                <div class="action-grid">
                    <div class="action-card" style="padding: 2rem; text-align: left;">
                        <h3>Search Orders</h3>
                        <form method="POST" class="search-form" style="margin-bottom: 1rem;">
                            <input type="text" name="search_query" placeholder="Search by status, user, or product" value="<?php echo htmlspecialchars($search_query); ?>">
                            <button type="submit" name="search">Search</button>
                            <?php if ($search_query): ?>
                                <a href="orders.php" class="action-btn" style="padding: 0.75rem 1.5rem;">Clear Search</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="quick-actions">
                <h2>Order List</h2>
                <div class="action-grid">
                    <div class="action-card" style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>User</th>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Total Price</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $orders->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                    <td><?php echo $order['quantity']; ?></td>
                                    <td>$<?php echo number_format($order['total_price'], 2); ?></td>
                                    <td><?php echo $order['order_date']; ?></td>
                                    <td><?php echo htmlspecialchars($order['status']); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <select name="status" onchange="this.form.submit()" style="padding: 0.25rem;">
                                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
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
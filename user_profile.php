<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$sql = "SELECT name, phone_number, email, address, created_at FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Fetch order history, grouping by order ID to sum total_price
$sql = "SELECT id, SUM(total_price) AS total, status, order_date 
        FROM orders 
        WHERE user_id = ? 
        GROUP BY id, status, order_date 
        ORDER BY order_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$orders = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex min-h-screen">
        <aside class="w-64 bg-gray-800 text-white p-4">
            <div class="sidebar-header mb-6">
                <h2 class="text-xl flex items-center"><i class="fas fa-store mr-2"></i> Shop</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li class="nav-item mb-2">
                        <a href="index.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-store mr-2"></i> Products
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="cart.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'cart.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-shopping-cart mr-2"></i> Cart
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="order_history.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'order_history.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-history mr-2"></i> Order History
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="user_profile.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'user_profile.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-user mr-2"></i> My Profile
                        </a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item mb-2">
                            <a href="logout.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item mb-2">
                            <a href="login.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded">
                                <i class="fas fa-sign-in-alt mr-2"></i> Login
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="sidebar-footer mt-auto">
                <div class="user-info flex items-center">
                    <i class="fas fa-user-circle mr-2"></i>
                    <span>Logged in as: <?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Guest'; ?></span>
                </div>
            </div>
        </aside>
        <main class="flex-1 p-6">
            <div class="content-header mb-6">
                <h1 class="text-2xl font-bold">My Profile</h1>
                <div class="breadcrumb text-gray-600">
                    <a href="index.php" class="hover:underline">Home</a> / <span class="current">My Profile</span>
                </div>
            </div>
            <div class="quick-actions">
                <div class="action-grid">
                    <div class="action-card bg-white p-6 rounded shadow">
                        <h2 class="text-xl font-semibold mb-4">Profile Details</h2>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name'] ?? 'N/A'); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></p>
                        <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($user['phone_number'] ?? 'N/A'); ?></p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($user['address'] ?? 'N/A'); ?></p>
                        <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($user['created_at'] ?? '')); ?></p>
                    </div>
                    <div class="action-card bg-white p-6 rounded shadow mt-6">
                        <h2 class="text-xl font-semibold mb-4">Order History</h2>
                        <?php if ($orders->num_rows > 0): ?>
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="border-b">
                                        <th class="p-2">Order ID</th>
                                        <th class="p-2">Total</th>
                                        <th class="p-2">Status</th>
                                        <th class="p-2">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($order = $orders->fetch_assoc()): ?>
                                        <tr class="border-b">
                                            <td class="p-2"><?php echo htmlspecialchars($order['id']); ?></td>
                                            <td class="p-2">$<?php echo number_format($order['total'], 2); ?></td>
                                            <td class="p-2"><?php echo htmlspecialchars($order['status']); ?></td>
                                            <td class="p-2"><?php echo date('F j, Y', strtotime($order['order_date'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-gray-600">No orders found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
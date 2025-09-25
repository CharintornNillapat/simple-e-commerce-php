<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle remove item
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_item'])) {
    $cart_id = $_POST['cart_id'];
    $sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $cart_id, $user_id);
    $stmt->execute();
    header('Location: cart.php');
    exit;
    
}

// Handle confirm purchase
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_purchase'])) {
    $sql = "SELECT c.id, c.product_id, c.quantity, p.price, p.quantity as stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $cart_items = $stmt->get_result();

    $can_proceed = true;
    while ($item = $cart_items->fetch_assoc()) {
        if ($item['quantity'] > $item['stock']) {
            $can_proceed = false;
            $error = "Insufficient stock for " . $item['product_id'] . ". Available: " . $item['stock'];
            break;
        }
    }

    if ($can_proceed) {
        $cart_items->data_seek(0);
        while ($item = $cart_items->fetch_assoc()) {
            $total_price = $item['price'] * $item['quantity'];
            $sql = "INSERT INTO orders (user_id, product_id, quantity, total_price, status) VALUES (?, ?, ?, ?, 'pending')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iiid', $user_id, $item['product_id'], $item['quantity'], $total_price);
            $stmt->execute();

            $new_quantity = $item['stock'] - $item['quantity'];
            $sql = "UPDATE products SET quantity = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $new_quantity, $item['product_id']);
            $stmt->execute();
        }
        $sql = "DELETE FROM cart WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        header('Location: confirm_purchase.php');
    }
}

// Fetch cart items
$sql = "SELECT c.id, c.product_id, c.quantity, p.name, p.price, c.added_at FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$cart_items = $stmt->get_result();

$total = 0;
while ($item = $cart_items->fetch_assoc()) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="flex min-h-screen">
        <aside class="w-64 bg-gray-800 text-white p-4">
            <div class="sidebar-header mb-6">
                <h2 class="text-xl flex items-center"><i class="fas fa-shopping-cart mr-2"></i> Cart</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li class="nav-item mb-2">
                        <a href="index.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-store mr-2"></i>
                            <span>Products</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="cart.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'cart.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-shopping-cart mr-2"></i>
                            <span>Cart</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="order_history.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'order_history.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-history mr-2"></i>
                            <span>Order History</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="user_profile.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'user_profile.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-user mr-2"></i> My Profile
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="logout.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="sidebar-footer mt-auto">
                <div class="user-info flex items-center">
                    <i class="fas fa-user-circle mr-2"></i>
                    <span>Logged in as: <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                </div>
            </div>
        </aside>
        <main class="flex-1 p-6">
            <div class="content-header mb-6">
                <h1 class="text-2xl font-bold">Your Cart</h1>
                <div class="breadcrumb text-gray-600">
                    <span>Home</span> / <span class="current">Cart</span>
                </div>
            </div>
            <div class="quick-actions">
                <div class="action-grid">
                    <?php if (isset($error)): ?>
                        <p class="text-red-500 text-center mb-4"><?php echo $error; ?></p>
                    <?php endif; ?>
                    <?php if ($cart_items->num_rows == 0): ?>
                        <p class="text-center p-8">Your cart is empty.</p>
                    <?php else: ?>
                        <div class="action-card overflow-x-auto p-6">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="border-b">
                                        <th class="p-2">Product</th>
                                        <th class="p-2">Price</th>
                                        <th class="p-2">Quantity</th>
                                        <th class="p-2">Total</th>
                                        <th class="p-2">Added At</th>
                                        <th class="p-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $cart_items->data_seek(0); while ($item = $cart_items->fetch_assoc()): ?>
                                        <tr class="border-b">
                                            <td class="p-2"><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td class="p-2">$<?php echo number_format($item['price'], 2); ?></td>
                                            <td class="p-2"><?php echo $item['quantity']; ?></td>
                                            <td class="p-2">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                            <td class="p-2"><?php echo $item['added_at']; ?></td>
                                            <td class="p-2">
                                                <form method="POST" class="inline-block">
                                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" name="remove_item" class="px-4 py-1 bg-red-500 text-white rounded hover:bg-red-600">Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            <p class="mt-4 text-lg font-semibold">Total: $<?php echo number_format($total, 2); ?></p>
                            <form method="POST" class="mt-4">
                                <button type="submit" name="confirm_purchase" class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Confirm Purchase</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
<?php
session_start();
require_once 'db_connect.php';

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if ($product_id <= 0) {
    header('Location: index.php');
    exit;
}

// Fetch product details
$sql = "SELECT p.id, p.name, p.image, p.price, p.quantity, p.category, pp.title, pp.content 
        FROM products p 
        LEFT JOIN product_posts pp ON p.id = pp.product_id 
        WHERE p.id = ? AND p.status = 'active'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name'] ?? 'Product'); ?> - Product Post</title>
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
                <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($product['name'] ?? 'Product'); ?></h1>
                <div class="breadcrumb text-gray-600">
                    <a href="index.php" class="hover:underline">Home</a> / <span class="current">Product Post</span>
                </div>
            </div>
            <div class="quick-actions">
                <div class="action-grid">
                    <div class="action-card bg-white p-6 rounded shadow">
                        <?php if ($product['image']): ?>
                            <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name'] ?? 'Product'); ?>" class="w-full h-64 object-cover mb-4 rounded">
                        <?php else: ?>
                            <div class="w-full h-64 bg-gray-200 flex items-center justify-center mb-4 rounded">No Image</div>
                        <?php endif; ?>
                        <h2 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($product['title'] ?? 'No Post Title'); ?></h2>
                        <p class="text-gray-600 mb-2">Category: <?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?></p>
                        <p class="text-gray-800 font-bold mb-2">Price: $<?php echo number_format($product['price'] ?? 0, 2); ?></p>
                        <p class="text-gray-600 mb-2">Stock: <?php echo ($product['quantity'] ?? 0) > 0 ? $product['quantity'] : 'Out of Stock'; ?></p>
                        <div class="prose" style="max-width: 100%;">
                            <?php echo $product['content'] ?? '<p>No content available.</p>'; ?>
                        </div>
                        <form method="POST" action="index.php" class="mt-4">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" name="add_to_cart" <?php echo ($product['quantity'] ?? 0) <= 0 ? 'disabled' : ''; ?> class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600 disabled:bg-gray-400 disabled:cursor-not-allowed">
                                Add to Cart
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
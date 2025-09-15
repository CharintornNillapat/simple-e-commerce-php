<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    // Redirect to login if not logged in (optional, currently commented out)
    // header('Location: login.php');
    // exit;
}

// Handle adding to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart']) && isset($_POST['product_id'])) {
    $user_id = $_SESSION['user_id'] ?? null;
    if ($user_id) {
        $product_id = $_POST['product_id'];
        $quantity = (int)$_POST['quantity'];

        // Check product availability
        $check_sql = "SELECT quantity FROM products WHERE id = ? AND status = 'active'";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('i', $product_id);
        $check_stmt->execute();
        $product = $check_stmt->get_result()->fetch_assoc();

        if ($product && $product['quantity'] >= $quantity) {
            // Check if item already exists in cart
            $cart_sql = "SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?";
            $cart_stmt = $conn->prepare($cart_sql);
            $cart_stmt->bind_param('ii', $user_id, $product_id);
            $cart_stmt->execute();
            $existing_item = $cart_stmt->get_result()->fetch_assoc();

            if ($existing_item) {
                $new_quantity = $existing_item['quantity'] + $quantity;
                $update_sql = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param('iii', $new_quantity, $user_id, $product_id);
                $update_stmt->execute();
            } else {
                $insert_sql = "INSERT INTO cart (user_id, product_id, quantity, added_at) VALUES (?, ?, ?, NOW())";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param('iii', $user_id, $product_id, $quantity);
                $insert_stmt->execute();
            }
            header('Location: cart.php');
            exit;
        } else {
            $error = "Product out of stock or unavailable.";
        }
    } else {
        header('Location: login.php');
        exit;
    }
}

$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'order_index'; // Default to order_index

$where_clause = '';
if ($search_query) {
    $search_param = "%$search_query%";
    $where_clause = "WHERE (name LIKE ? OR category LIKE ?) AND status = 'active'";
} else {
    $where_clause = "WHERE status = 'active'";
}

$order_clause = '';
switch ($sort_by) {
    case 'order_index':
        $order_clause = 'ORDER BY order_index ASC'; // Custom order set by admin
        break;
    case 'price_asc':
        $order_clause = 'ORDER BY price ASC';
        break;
    case 'price_desc':
        $order_clause = 'ORDER BY price DESC';
        break;
    case 'name_desc':
        $order_clause = 'ORDER BY name DESC';
        break;
    case 'name_asc':
    default:
        $order_clause = 'ORDER BY name ASC';
        break;
}

$sql = "SELECT * FROM products " . $where_clause . " " . $order_clause;
$stmt = $conn->prepare($sql);

if ($search_query) {
    $stmt->bind_param('ss', $search_param, $search_param);
}
$stmt->execute();
$products = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Catalog</title>
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
                <h1 class="text-2xl font-bold">Product Catalog</h1>
                <div class="breadcrumb text-gray-600">
                    <span>Home</span> / <span class="current">Products</span>
                </div>
            </div>
            <div class="search-sort flex gap-4 mb-6">
                <form method="GET" class="flex-1">
                    <div class="flex">
                        <input type="text" name="search" placeholder="Search by name or category" value="<?php echo htmlspecialchars($search_query); ?>" class="flex-1 p-2 border border-gray-300 rounded-l">
                        <button type="submit" class="bg-blue-500 text-white p-2 rounded-r hover:bg-blue-600">Search</button>
                    </div>
                </form>
                <form method="GET" class="flex items-center gap-2">
                    <select name="sort_by" onchange="this.form.submit()" class="p-2 border border-gray-300 rounded">
                        <option value="order_index" <?php echo $sort_by === 'order_index' ? 'selected' : ''; ?>>Custom Order</option>
                        <option value="name_asc" <?php echo $sort_by === 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                        <option value="name_desc" <?php echo $sort_by === 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                        <option value="price_asc" <?php echo $sort_by === 'price_asc' ? 'selected' : ''; ?>>Price (Low to High)</option>
                        <option value="price_desc" <?php echo $sort_by === 'price_desc' ? 'selected' : ''; ?>>Price (High to Low)</option>
                    </select>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                </form>
            </div>
            <div class="quick-actions">
                <div class="action-grid">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <?php while ($product = $products->fetch_assoc()): ?>
                            <div class="product-card bg-white p-4 rounded shadow">
                                <a href="product_post_view.php?product_id=<?php echo $product['id']; ?>">
                                    <?php if ($product['image']): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-48 object-cover mb-2 rounded">
                                    <?php else: ?>
                                        <div class="w-full h-48 bg-gray-200 flex items-center justify-center mb-2 rounded">No Image</div>
                                    <?php endif; ?>
                                </a>
                                <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="text-gray-600">Category: <?php echo htmlspecialchars($product['category']); ?></p>
                                <p class="text-gray-800 font-bold">Price: $<?php echo number_format($product['price'], 2); ?></p>
                                <p class="text-gray-600">Stock: <?php echo $product['quantity'] > 0 ? $product['quantity'] : 'Out of Stock'; ?></p>
                                <form method="POST" action="index.php" class="mt-2">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" name="add_to_cart" <?php echo $product['quantity'] <= 0 ? 'disabled' : ''; ?> class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600 disabled:bg-gray-400 disabled:cursor-not-allowed">
                                        Add to Cart
                                    </button>
                                </form>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
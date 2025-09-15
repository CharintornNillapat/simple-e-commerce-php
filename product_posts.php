<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit;
}

// Handle product post creation or update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = (float)$_POST['price'];
    $quantity = (int)$_POST['quantity'];
    $image = $_FILES['image']['name'] ? $_FILES['image']['name'] : (isset($_POST['existing_image']) ? $_POST['existing_image'] : '');
    $title = $_POST['title'];
    $content = $_POST['content'];

    // Debug: Log the POST data
    error_log("POST Data: " . print_r($_POST, true));

    if ($id > 0) {
        // Update existing product and post
        $sql = "UPDATE products SET name = ?, category = ?, price = ?, quantity = ?, image = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssdssi', $name, $category, $price, $quantity, $image, $id);
        if ($stmt->execute()) {
            $sql = "UPDATE product_posts SET title = ?, content = ?, updated_at = NOW() WHERE product_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssi', $title, $content, $id);
            if ($stmt->execute()) {
                header('Location: product_posts.php');
                exit;
            } else {
                $error = "Failed to update post: " . $conn->error;
            }
        } else {
            $error = "Failed to update product: " . $conn->error;
        }
    } else {
        // Create new product and post
        $sql = "INSERT INTO products (name, category, price, quantity, image, status) VALUES (?, ?, ?, ?, ?, 'active')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssdss', $name, $category, $price, $quantity, $image);
        if ($stmt->execute()) {
            $product_id = $conn->insert_id;
            error_log("New product ID: $product_id");

            $sql = "INSERT INTO product_posts (title, content, product_id, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssi', $title, $content, $product_id);
            if ($stmt->execute()) {
                error_log("Post inserted for product ID: $product_id");
                if ($image && move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $image)) {
                    header('Location: product_posts.php');
                    exit;
                }
            } else {
                $error = "Failed to insert post: " . $conn->error;
                // Optionally delete the product if post insertion fails
                $conn->query("DELETE FROM products WHERE id = $product_id");
            }
        } else {
            $error = "Failed to insert product: " . $conn->error;
        }
    }
    if (isset($error)) {
        error_log($error);
    }
}

// Fetch products and their posts for editing
$sql = "SELECT p.id, p.name, p.category, p.price, p.quantity, p.image, pp.title, pp.content 
        FROM products p 
        LEFT JOIN product_posts pp ON p.id = pp.product_id 
        ORDER BY p.name ASC";
$products = $conn->query($sql);

$edit_product = null;
if (isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $sql = "SELECT p.id, p.name, p.category, p.price, p.quantity, p.image, pp.title, pp.content 
            FROM products p 
            LEFT JOIN product_posts pp ON p.id = pp.product_id 
            WHERE p.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_product = $result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Product Posts</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
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
                    <li class="nav-item mb-2">
                        <a href="products.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'bg-gray-700' : ''; ?>">
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
                    <li class="nav-item active mb-2">
                        <a href="product_posts.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded bg-gray-700">
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
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>
        <main class="flex-1 p-6">
            <div class="content-header mb-6">
                <h1 class="text-2xl font-bold">Product Posts</h1>
                <div class="breadcrumb text-gray-600">
                    <span>Home</span> / <span class="current">Product Posts</span>
                </div>
            </div>
            <div class="action-card bg-white p-6 mb-6">
                <h3 class="text-xl font-semibold mb-4"><?php echo isset($_GET['id']) ? 'Edit Product Post' : 'Create New Product Post'; ?></h3>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="id" value="<?php echo isset($_GET['id']) ? (int)$_GET['id'] : ''; ?>">
                    <div>
                        <label for="name" class="block mb-1 font-medium">Product Name:</label>
                        <input type="text" id="name" name="name" value="<?php echo isset($edit_product) && $edit_product ? htmlspecialchars($edit_product['name'] ?? '') : ''; ?>" required class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="category" class="block mb-1 font-medium">Category:</label>
                        <input type="text" id="category" name="category" value="<?php echo isset($edit_product) && $edit_product ? htmlspecialchars($edit_product['category'] ?? '') : ''; ?>" required class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="price" class="block mb-1 font-medium">Price ($):</label>
                        <input type="number" id="price" name="price" step="0.01" value="<?php echo isset($edit_product) && $edit_product ? htmlspecialchars($edit_product['price'] ?? '') : ''; ?>" required class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="quantity" class="block mb-1 font-medium">Quantity:</label>
                        <input type="number" id="quantity" name="quantity" value="<?php echo isset($edit_product) && $edit_product ? htmlspecialchars($edit_product['quantity'] ?? '') : ''; ?>" required class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="image" class="block mb-1 font-medium">Image:</label>
                        <input type="file" id="image" name="image" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <?php if (isset($_GET['id']) && $edit_product && $edit_product['image']): ?>
                            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($edit_product['image']); ?>">
                            <p>Current Image: <img src="uploads/<?php echo htmlspecialchars($edit_product['image']); ?>" alt="Current" class="w-24 h-24 object-cover mt-2"></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="title" class="block mb-1 font-medium">Post Title:</label>
                        <input type="text" id="title" name="title" value="<?php echo isset($edit_product) && $edit_product ? htmlspecialchars($edit_product['title'] ?? '') : ''; ?>" required class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="content" class="block mb-1 font-medium">Content:</label>
                        <div id="editor" class="border border-gray-300 rounded"><?php echo isset($edit_product) && $edit_product ? htmlspecialchars($edit_product['content'] ?? '') : ''; ?></div>
                        <input type="hidden" name="content" id="hidden-content">
                    </div>
                    <button type="submit" class="w-full bg-blue-500 text-white p-3 rounded hover:bg-blue-600 transition duration-200"><?php echo isset($_GET['id']) ? 'Update Post' : 'Create Post'; ?></button>
                </form>
                <?php if (isset($error)): ?>
                    <p class="text-red-500 text-center mt-4"><?php echo $error; ?></p>
                <?php endif; ?>
            </div>
            <div class="quick-actions">
                <h2 class="text-xl font-semibold mb-4">Product Posts List</h2>
                <div class="action-grid">
                    <div class="action-card overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b">
                                    <th class="p-2">Product Name</th>
                                    <th class="p-2">Category</th>
                                    <th class="p-2">Price</th>
                                    <th class="p-2">Stock</th>
                                    <th class="p-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $products->data_seek(0);
                                while ($product = $products->fetch_assoc()): ?>
                                    <tr class="border-b">
                                        <td class="p-2"><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td class="p-2"><?php echo htmlspecialchars($product['category']); ?></td>
                                        <td class="p-2">$<?php echo number_format($product['price'], 2); ?></td>
                                        <td class="p-2"><?php echo $product['quantity']; ?></td>
                                        <td class="p-2">
                                            <a href="product_posts.php?id=<?php echo $product['id']; ?>" class="text-blue-500 hover:underline mr-2">Edit</a>
                                            <a href="product_posts.php?delete=<?php echo $product['id']; ?>" class="text-red-500 hover:underline" onclick="return confirm('Are you sure you want to delete this product and post?');">Delete</a>
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

    <script>
        var quill = new Quill('#editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote', 'code-block'],
                    [{ 'header': 1 }, { 'header': 2 }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'script': 'sub'}, { 'script': 'super' }],
                    [{ 'indent': '-1'}, { 'indent': '+1' }],
                    [{ 'direction': 'rtl' }],
                    [{ 'size': ['small', false, 'large', 'huge'] }],
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'font': [] }],
                    [{ 'align': [] }],
                    ['clean']
                ]
            }
        });

        document.querySelector('form').addEventListener('submit', function() {
            document.getElementById('hidden-content').value = quill.root.innerHTML;
        });

        // Pre-fill editor if editing
        <?php if (isset($_GET['id']) && $edit_product): ?>
            quill.root.innerHTML = '<?php echo str_replace("'", "\'", $edit_product['content'] ?? ''); ?>';
        <?php endif; ?>
    </script>
</body>
</html>
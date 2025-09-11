<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit;
}

// Optimized image processing function to avoid code duplication
function processImage($file, $upload_dir = 'uploads/') {
    error_log("DEBUG: processImage() called for file: " . $file['name']);
    error_log("DEBUG: File info - Size: " . $file['size'] . ", Type: " . $file['type'] . ", Error: " . $file['error']);
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $max_size = 5 * 1024 * 1024;
    
    if (!in_array($file['type'], $allowed_types) || $file['size'] > $max_size) {
        error_log("ERROR: File validation failed - Type: " . $file['type'] . ", Size: " . $file['size']);
        return ['error' => "Invalid file type or size exceeds 5MB. Allowed types: JPEG, PNG, JPG."];
    }
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
        error_log("DEBUG: Created upload directory: " . $upload_dir);
    }
    
    if (!extension_loaded('gd') || !function_exists('gd_info')) {
        error_log("ERROR: GD library is not enabled.");
        return ['error' => "Server error: Image processing is not supported."];
    }
    
    $image_name = time() . '_' . basename($file['name']);
    $target_file = $upload_dir . $image_name;
    error_log("DEBUG: Target file: " . $target_file);
    
    $source_image = @imagecreatefromstring(file_get_contents($file['tmp_name']));
    if ($source_image === false) {
        error_log("ERROR: Failed to create image from file: " . $file['tmp_name']);
        return ['error' => "Invalid image file."];
    }
    
    $original_width = imagesx($source_image);
    $original_height = imagesy($source_image);
    error_log("DEBUG: Original dimensions - Width: " . $original_width . ", Height: " . $original_height);
    
    $max_dimension = 266;
    
    // Calculate new dimensions maintaining aspect ratio
    if ($original_width > $original_height) {
        // Landscape orientation
        $new_width = $max_dimension;
        $new_height = (int)(($original_height / $original_width) * $max_dimension);
    } else {
        // Portrait or square orientation
        $new_height = $max_dimension;
        $new_width = (int)(($original_width / $original_height) * $max_dimension);
    }
    
    // Ensure minimum dimensions
    if ($new_width < 1) $new_width = 1;
    if ($new_height < 1) $new_height = 1;
    
    error_log("DEBUG: New dimensions - Width: " . $new_width . ", Height: " . $new_height);
    
    $resized_image = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG and GIF
    if ($file['type'] == 'image/png') {
        imagealphablending($resized_image, false);
        imagesavealpha($resized_image, true);
        $transparent = imagecolorallocatealpha($resized_image, 0, 0, 0, 127);
        imagefill($resized_image, 0, 0, $transparent);
        error_log("DEBUG: PNG transparency preserved");
    }
    
    // High quality resampling
    if (!imagecopyresampled($resized_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height)) {
        imagedestroy($source_image);
        imagedestroy($resized_image);
        error_log("ERROR: Failed to resize image: " . $file['tmp_name']);
        return ['error' => "Failed to resize image."];
    }
    
    error_log("DEBUG: Image resized successfully");
    
    // Save resized image based on type with error checking
    $save_success = false;
    if ($file['type'] == 'image/jpeg' || $file['type'] == 'image/jpg') {
        $save_success = imagejpeg($resized_image, $target_file, 90);
        error_log("DEBUG: Attempting to save JPEG to: " . $target_file);
    } elseif ($file['type'] == 'image/png') {
        $save_success = imagepng($resized_image, $target_file, 6);
        error_log("DEBUG: Attempting to save PNG to: " . $target_file);
    }
    
    imagedestroy($source_image);
    imagedestroy($resized_image);
    
    if (!$save_success) {
        error_log("ERROR: Failed to save image: " . $target_file);
        return ['error' => "Failed to save image."];
    }
    
    error_log("SUCCESS: Image saved successfully: " . $target_file);
    
    // Verify the file was actually created and get its size
    if (file_exists($target_file)) {
        $file_size = filesize($target_file);
        error_log("DEBUG: Final file size: " . $file_size . " bytes");
    } else {
        error_log("ERROR: File was not created: " . $target_file);
        return ['error' => "File was not created."];
    }
    
    return ['success' => true, 'image_name' => $image_name];
}

// Debug: Log POST data for troubleshooting
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log("POST Data: " . print_r($_POST, true));
    if (isset($_FILES['image'])) {
        error_log("File Data: " . print_r($_FILES, true));
    }
}

// Handle product creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $sku = $_POST['sku'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 0);
    $category = $_POST['category'] ?? '';
    $weight = $_POST['weight'] ?? 0.00;
    $brand = $_POST['brand'] ?? '';
    $image = null;

    error_log("Insert Values: $name, $description, $price, $sku, $quantity, $category, $weight, $brand, $image");

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_result = processImage($_FILES['image']);
        if (isset($image_result['error'])) {
            $error = $image_result['error'];
        } else {
            $image = $image_result['image_name'];
        }
    }

    if (!isset($error)) {
        $status = $_POST['status'] ?? 'active';

$sql = "INSERT INTO products (name, description, price, sku, quantity, category, weight, brand, image, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssdsisdsss', $name, $description, $price, $sku, $quantity, $category, $weight, $brand, $image, $status);

        if ($stmt->execute()) {
            header('Location: products.php');
            exit;
        } else {
            $error = "Failed to add product: " . $conn->error;
        }
    }
}

// Handle product update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $sku = $_POST['sku'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 0);
    $category = $_POST['category'] ?? '';
    $weight = $_POST['weight'] ?? 0.00;
    $brand = $_POST['brand'] ?? '';
    $image = $_POST['existing_image'] ?? null;

    error_log("Update Values: $name, $description, $price, $sku, $quantity, $category, $weight, $brand, $image, $id");

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_result = processImage($_FILES['image']);
        if (isset($image_result['error'])) {
            $error = $image_result['error'];
        } else {
            $image = $image_result['image_name'];
            $old_image = $_POST['existing_image'] ?? null;
            if ($old_image && file_exists('uploads/' . $old_image)) {
                unlink('uploads/' . $old_image);
            }
        }
    }

    if (!isset($error)) {
        $status = $_POST['status'] ?? 'active';

        $sql = "UPDATE products 
                SET name = ?, description = ?, price = ?, sku = ?, quantity = ?, category = ?, weight = ?, brand = ?, image = ?, status = ? 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssdsisdsssi', $name, $description, $price, $sku, $quantity, $category, $weight, $brand, $image, $status, $id);
        
        if ($stmt->execute()) {
            header('Location: products.php');
            exit;
        } else {
            $error = "Failed to update product: " . $conn->error;
        }
    }
}

// Handle product deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "SELECT image FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    $sql = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    
    if ($product['image'] && file_exists('uploads/' . $product['image'])) {
        unlink('uploads/' . $product['image']);
    }
    header('Location: products.php');
    exit;
}

// Fetch product for editing
$edit_product = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit_product = $stmt->get_result()->fetch_assoc();
}

// Optimized: Use single queries with subqueries for counts
$sql = "SELECT *, 
        (SELECT COUNT(*) FROM products) as total_products,
        (SELECT COUNT(*) FROM orders WHERE status = 'pending') as pending_orders
        FROM products ORDER BY order_index ASC";
$products_result = $conn->query($sql);

// Extract counts from first row
$stats = null;
if ($products_result->num_rows > 0) {
    $first_row = $products_result->fetch_assoc();
    $stats = [
        'total_products' => $first_row['total_products'],
        'pending_orders' => $first_row['pending_orders']
    ];
    // Reset pointer to beginning
    $products_result->data_seek(0);
}

// Fetch all orders for admin
$sql = "SELECT o.id, u.name AS user_name, p.name AS product_name, o.quantity, o.total_price, o.order_date, o.status 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        JOIN products p ON o.product_id = p.id 
        ORDER BY o.order_date DESC";
$orders = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Manage Products</title>
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
                    <?php 
                    $current_page = basename($_SERVER['PHP_SELF']);
                    $nav_items = [
                        'dashboard.php' => ['icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard'],
                        'products.php' => ['icon' => 'fas fa-box', 'label' => 'Products'],
                        'customers.php' => ['icon' => 'fas fa-users', 'label' => 'Customers'],
                        'orders.php' => ['icon' => 'fas fa-shopping-cart', 'label' => 'Orders'],
                        'admin_reorder_products.php' => ['icon' => 'fas fa-sort', 'label' => 'Reorder Products']
                    ];
                    
                    foreach ($nav_items as $page => $item): 
                        $active_class = $current_page == $page ? 'bg-gray-700' : '';
                    ?>
                    <li class="nav-item mb-2">
                        <a href="<?php echo $page; ?>" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo $active_class; ?>">
                            <i class="<?php echo $item['icon']; ?>"></i>
                            <span><?php echo $item['label']; ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
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
                <h1 class="text-2xl font-bold">Manage Products</h1>
                <div class="breadcrumb text-gray-600">
                    <span>Home</span> / <span class="current">Products</span>
                </div>
            </div>

            <div class="stats-grid grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="stat-card flex items-center p-4 bg-white rounded shadow">
                    <div class="stat-icon text-yellow-500 mr-4">
                        <i class="fas fa-box text-2xl"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="text-xl font-semibold"><?php echo $stats ? $stats['total_products'] : '0'; ?></h3>
                        <p class="text-gray-600">Total Products</p>
                    </div>
                    <div class="stat-action ml-auto">
                        <a href="products.php" class="text-blue-500 hover:underline">Manage <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>
                <div class="stat-card flex items-center p-4 bg-white rounded shadow">
                    <div class="stat-icon text-red-500 mr-4">
                        <i class="fas fa-clock text-2xl"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="text-xl font-semibold"><?php echo $stats ? $stats['pending_orders'] : '0'; ?></h3>
                        <p class="text-gray-600">Pending Orders</p>
                    </div>
                    <div class="stat-action ml-auto">
                        <a href="orders.php?status=pending" class="text-blue-500 hover:underline">Review <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>
            </div>

            <div class="quick-actions">
                <h2 class="text-xl font-semibold mb-4">Product Management</h2>
                <div class="action-grid grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="action-card flex flex-col items-center p-6 bg-white rounded shadow">
                        <div class="action-icon text-green-500 mb-4">
                            <i class="fas fa-plus text-3xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold mb-2">Add Product</h3>
                        
                        <p class="text-gray-600 mb-4 text-center">Add new products to your inventory</p>
                        <a href="?add=1" class="mt-auto bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Add Product</a>
                    </div>
                    <div class="action-card flex flex-col items-center p-6 bg-white rounded shadow">
                        <div class="action-icon text-purple-500 mb-4">
                            <i class="fas fa-sort text-3xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold mb-2">Reorder Products</h3>
                        <p class="text-gray-600 mb-4 text-center">Organize product display order</p>
                        <a href="admin_reorder_products.php" class="mt-auto bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Reorder</a>
                    </div>
                </div>
            </div>

            <?php if (isset($_GET['add']) || isset($_GET['edit'])): ?>
                <div class="action-card bg-gray-100 p-6 mt-6">
                    <h3 class="text-xl font-semibold mb-4"><?php echo isset($_GET['edit']) ? 'Edit Product' : 'Add New Product'; ?></h3>
                    <?php if (isset($error)) echo "<p class='text-red-500 text-center mb-4'>$error</p>"; ?>
                    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-2xl">
                        <?php if ($edit_product): ?>
                            <input type="hidden" name="id" value="<?php echo $edit_product['id']; ?>">
                            <input type="hidden" name="existing_image" value="<?php echo $edit_product['image']; ?>">
                        <?php endif; ?>
                        
                        <?php 
                        $form_fields = [
                            ['name' => 'name', 'label' => 'Product Name', 'type' => 'text', 'required' => true, 'placeholder' => 'Product Name'],
                            ['name' => 'price', 'label' => 'Price', 'type' => 'number', 'required' => true, 'placeholder' => 'Price', 'step' => '0.01'],
                            ['name' => 'sku', 'label' => 'SKU', 'type' => 'text', 'required' => false, 'placeholder' => 'SKU'],
                            ['name' => 'quantity', 'label' => 'Quantity', 'type' => 'number', 'required' => false, 'placeholder' => 'Quantity', 'min' => '0'],
                            ['name' => 'category', 'label' => 'Category', 'type' => 'text', 'required' => true, 'placeholder' => 'Category'],
                            ['name' => 'weight', 'label' => 'Weight (kg)', 'type' => 'number', 'required' => false, 'placeholder' => 'Weight (kg)', 'step' => '0.01', 'min' => '0'],
                            ['name' => 'brand', 'label' => 'Brand', 'type' => 'text', 'required' => false, 'placeholder' => 'Brand']
                        ];
                        
                        foreach ($form_fields as $field): 
                            $value = $edit_product ? htmlspecialchars($edit_product[$field['name']] ?? '') : '';
                        ?>
                        <div>
                            <label for="<?php echo $field['name']; ?>" class="block mb-1 font-medium"><?php echo $field['label']; ?>:</label>
                            <input type="<?php echo $field['type']; ?>" 
                                   id="<?php echo $field['name']; ?>" 
                                   name="<?php echo $field['name']; ?>" 
                                   placeholder="<?php echo $field['placeholder']; ?>" 
                                   value="<?php echo $value; ?>"
                                   <?php echo $field['required'] ? 'required' : ''; ?>
                                   <?php echo isset($field['step']) ? 'step="' . $field['step'] . '"' : ''; ?>
                                   <?php echo isset($field['min']) ? 'min="' . $field['min'] . '"' : ''; ?>
                                   class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="md:col-span-2">
                            <label for="description" class="block mb-1 font-medium">Description:</label>
                            <textarea id="description" name="description" placeholder="Description" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" style="height: 120px; resize: vertical;"><?php echo $edit_product ? $edit_product['description'] : ''; ?></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label for="image" class="block mb-1 font-medium">Image:</label>
                            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/jpg" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <?php if ($edit_product && $edit_product['image']): ?>
                            <div class="md:col-span-2">
                                <label class="block mb-1 font-medium">Current Image:</label>
                                <div class="border border-gray-300 p-2 rounded inline-block">
                                    <img src="uploads/<?php echo htmlspecialchars($edit_product['image']); ?>" 
                                         alt="Product Image" 
                                         class="max-w-xs max-h-64 object-contain rounded">
                                </div>
                            </div>
                        <?php endif; ?>
                        <div>
    <label for="status" class="block mb-1 font-medium">Status:</label>
    <select id="status" name="status" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
        <option value="active" <?php echo ($edit_product && $edit_product['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
        <option value="inactive" <?php echo ($edit_product && $edit_product['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
    </select>
</div>

                        <div class="md:col-span-2">
                            <button type="submit" name="<?php echo $edit_product ? 'update_product' : 'add_product'; ?>" class="w-full bg-blue-500 text-white p-3 rounded hover:bg-blue-600 transition duration-200">
                                <?php echo $edit_product ? 'Update Product' : 'Add Product'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div class="quick-actions mt-6">
                <h2 class="text-xl font-semibold mb-4">Product List</h2>
                <div class="action-grid">
                    <div class="action-card overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b">
                                    <?php 
                                    $table_headers = ['ID', 'Name', 'Price', 'SKU', 'Quantity', 'Category', 'Weight (kg)', 'Brand', 'Image', 'Status', 'Actions'];
                                    foreach ($table_headers as $header): 
                                    ?>
                                        <th class="p-2"><?php echo $header; ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($product = $products_result->fetch_assoc()): ?>
                                <tr class="border-b">
                                    <td class="p-2"><?php echo $product['id']; ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td class="p-2"><?php echo $product['price']; ?></td>
                                    <td class="p-2"><?php echo $product['sku'] ?: 'N/A'; ?></td>
                                    <td class="p-2"><?php echo $product['quantity'] ?: '0'; ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($product['category']) ?: 'N/A'; ?></td>
                                    <td class="p-2"><?php echo $product['weight'] ?: '0.00'; ?></td>
                                    <td class="p-2"><?php echo $product['brand'] ?: 'N/A'; ?></td>
                                    
                                    <td class="p-2">
                                        <?php if ($product['image']): ?>
                                            <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image" class="max-w-[266px] max-h-[266px]">
                                        <?php else: ?>
                                            No image
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-2"><?php echo htmlspecialchars($product['status']); ?></td>

                                    <td class="p-2">
                                        <a href="product_details.php?id=<?php echo $product['id']; ?>" class="text-blue-500 hover:underline mr-2">View</a>
                                        <a href="?edit=<?php echo $product['id']; ?>" class="text-blue-500 hover:underline mr-2">Edit</a>
                                        <a href="?delete=<?php echo $product['id']; ?>" 
                                           class="text-red-500 hover:underline" 
                                           onclick="return confirm('⚠️ WARNING: Are you sure you want to delete this product?\n\nThis action cannot be undone. If this product has existing orders, the deletion will be prevented to maintain data integrity.');">
                                           Delete
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="quick-actions mt-6">
                <h2 class="text-xl font-semibold mb-4">All Orders</h2>
                <div class="action-grid">
                    <div class="action-card overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b">
                                    <?php 
                                    $order_headers = ['Order ID', 'User', 'Product', 'Quantity', 'Total Price', 'Date', 'Status'];
                                    foreach ($order_headers as $header): 
                                    ?>
                                        <th class="p-2"><?php echo $header; ?></th>
                                    <?php endforeach; ?>
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
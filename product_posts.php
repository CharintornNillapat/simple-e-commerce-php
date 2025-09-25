<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit;
}

$message = '';
$error = '';
$product_id = (int)($_GET['id'] ?? 0);

// Handle product save (create/update + images)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_changes'])) {
    $id       = (int)($_POST['id'] ?? 0);
    $name     = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price    = (float)($_POST['price'] ?? 0.0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    $title    = trim($_POST['title'] ?? '');
    $content  = trim($_POST['content'] ?? '');

    $stmt = null;
    if ($id > 0) {
        // Update
        $stmt = $conn->prepare("UPDATE products SET name=?, category=?, price=?, quantity=? WHERE id=?");
        if ($stmt === false) {
            $error = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param('ssdii', $name, $category, $price, $quantity, $id);
            if ($stmt->execute()) {
                $stmt->close();
                $stmt = $conn->prepare("UPDATE product_posts SET title=?, content=?, updated_at=NOW() WHERE product_id=?");
                if ($stmt === false) {
                    $error = "Prepare failed: " . $conn->error;
                } else {
                    $stmt->bind_param('ssi', $title, $content, $id);
                    $message = $stmt->execute() ? "Product and post updated successfully!" : "Failed to update post: " . $conn->error;
                    $product_id = $id; // Ensure product_id is set for image handling
                }
            } else {
                $error = "Failed to update product: " . $conn->error;
            }
        }
    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO products (name, category, price, quantity, status) VALUES (?, ?, ?, ?, 'active')");
        if ($stmt === false) {
            $error = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param('ssdi', $name, $category, $price, $quantity);
            if ($stmt->execute()) {
                $product_id = $conn->insert_id;
                $stmt->close();

                $stmt = $conn->prepare("INSERT INTO product_posts (title, content, product_id, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                if ($stmt === false) {
                    $error = "Prepare failed: " . $conn->error;
                } else {
                    $stmt->bind_param('ssi', $title, $content, $product_id);
                    if ($stmt->execute()) {
                        $message = "Product and post created successfully!";
                    } else {
                        $error = "Failed to insert post: " . $conn->error;
                        $conn->query("DELETE FROM products WHERE id = " . $conn->real_escape_string($product_id));
                    }
                }
            } else {
                $error = "Failed to insert product: " . $conn->error;
            }
        }
    }
    if ($stmt) $stmt->close();

    // Create uploads directory if it doesn't exist
    $upload_dir = "uploads/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Handle main image (replace if uploaded)
    if ($product_id > 0 && isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['main_image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($ext, $allowed) && getimagesize($file['tmp_name']) !== false && $file['size'] <= 5 * 1024 * 1024) {
            $image = uniqid('main_', true) . '.' . $ext;
            $target_file = $upload_dir . $image;
            
            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                $stmt = $conn->prepare("UPDATE products SET image=? WHERE id=?");
                if ($stmt === false) {
                    $error .= " Prepare failed: " . $conn->error;
                } else {
                    $stmt->bind_param('si', $image, $product_id);
                    if ($stmt->execute()) {
                        $message .= " Main image uploaded successfully!";
                    } else {
                        $error .= " Failed to save main image to database.";
                    }
                    $stmt->close();
                }
            } else {
                $error .= " Failed to move uploaded main image.";
            }
        } else {
            $error .= " Invalid main image file (max 5MB, jpg/jpeg/png/gif only).";
        }
    }

    // Handle gallery images (multiple)
    if ($product_id > 0 && isset($_FILES['gallery_images']) && is_array($_FILES['gallery_images']['name'])) {
        $gallery_success_count = 0;
        $gallery_error_count = 0;
        
        for ($i = 0; $i < count($_FILES['gallery_images']['name']); $i++) {
            if ($_FILES['gallery_images']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            
            if ($_FILES['gallery_images']['error'][$i] === UPLOAD_ERR_OK) {
                $filename = $_FILES['gallery_images']['name'][$i];
                $tmp_name = $_FILES['gallery_images']['tmp_name'][$i];
                $file_size = $_FILES['gallery_images']['size'][$i];
                
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($ext, $allowed) && $file_size <= 5 * 1024 * 1024 && getimagesize($tmp_name) !== false) {
                    $image = uniqid('gallery_', true) . '.' . $ext;
                    $target_file = $upload_dir . $image;
                    
                    if (move_uploaded_file($tmp_name, $target_file)) {
                        $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)");
                        if ($stmt === false) {
                            $error .= " Prepare failed: " . $conn->error;
                        } else {
                            $stmt->bind_param('is', $product_id, $image);
                            if ($stmt->execute()) {
                                $gallery_success_count++;
                            } else {
                                $gallery_error_count++;
                                unlink($target_file);
                            }
                            $stmt->close();
                        }
                    } else {
                        $gallery_error_count++;
                    }
                } else {
                    $gallery_error_count++;
                }
            } else {
                $gallery_error_count++;
            }
        }
        
        if ($gallery_success_count > 0) {
            $message .= " {$gallery_success_count} gallery image(s) uploaded successfully!";
        }
        if ($gallery_error_count > 0) {
            $error .= " {$gallery_error_count} gallery image(s) failed to upload.";
        }
    }

    // Redirect to prevent form resubmission
    $redirect_url = "product_posts.php?id=" . urlencode($product_id);
    if ($message) {
        $redirect_url .= "&msg=" . urlencode($message);
    }
    if ($error) {
        $redirect_url .= "&err=" . urlencode($error);
    }
    header("Location: " . $redirect_url);
    exit;
}

// Handle image delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image']) && $product_id > 0) {
    $image_path = $_POST['image_path'] ?? '';
    $is_main = ($_POST['is_main'] ?? '') === '1';

    $stmt = $conn->prepare("SELECT image FROM products WHERE id=?");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $current_product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = null;
    $file_deleted = false;
    
    if ($is_main && $current_product && $current_product['image'] === $image_path) {
        $stmt = $conn->prepare("UPDATE products SET image=NULL WHERE id=?");
        $stmt->bind_param('i', $product_id);
        if ($stmt->execute()) {
            $file_deleted = true;
        }
    } else {
        $stmt = $conn->prepare("DELETE FROM product_images WHERE product_id=? AND image_path=?");
        $stmt->bind_param('is', $product_id, $image_path);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $file_deleted = true;
        }
    }
    
    if ($stmt) $stmt->close();
    
    if ($file_deleted && $image_path && file_exists($upload_dir . $image_path)) {
        unlink($upload_dir . $image_path);
    }

    $message = $file_deleted ? "Image deleted successfully!" : "Failed to delete image.";
    header("Location: product_posts.php?id=" . urlencode($product_id) . "&msg=" . urlencode($message));
    exit;
}

// Handle URL parameters for messages
if (isset($_GET['msg'])) {
    $message = urldecode($_GET['msg']);
}
if (isset($_GET['err'])) {
    $error = urldecode($_GET['err']);
}

// Fetch product for editing
$edit_product = null;
if ($product_id > 0) {
    $stmt = $conn->prepare("SELECT p.id, p.name, p.category, p.price, p.quantity, p.image, pp.title, pp.content 
                             FROM products p 
                             LEFT JOIN product_posts pp ON p.id=pp.product_id 
                             WHERE p.id=?");
    if ($stmt === false) {
        $error = "Prepare failed: " . $conn->error;
    } else {
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $edit_product = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

// Fetch all products
$products = $conn->query("SELECT p.id, p.name, p.category, p.price, p.quantity, p.image, pp.title, pp.content 
                           FROM products p 
                           LEFT JOIN product_posts pp ON p.id=pp.product_id 
                           ORDER BY p.name ASC");

// Fetch product images
$images = [];
if ($product_id > 0) {
    $stmt = $conn->prepare("SELECT image_path FROM product_images WHERE product_id=? ORDER BY id ASC");
    if ($stmt === false) {
        $error = "Prepare failed: " . $conn->error;
    } else {
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
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
            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <div class="action-card bg-white p-6 mb-6">
                <h3 class="text-xl font-semibold mb-4"><?php echo $product_id > 0 ? 'Edit Product Post' : 'Create New Product Post'; ?></h3>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="id" value="<?php echo $product_id; ?>">
                    <div>
                        <label for="name" class="block mb-1 font-medium">Product Name:</label>
                        <input type="text" id="name" name="name" value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>" required class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="category" class="block mb-1 font-medium">Category:</label>
                        <input type="text" id="category" name="category" value="<?php echo $edit_product ? htmlspecialchars($edit_product['category']) : ''; ?>" required class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="price" class="block mb-1 font-medium">Price ($):</label>
                        <input type="number" id="price" name="price" step="0.01" value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>" required class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="quantity" class="block mb-1 font-medium">Quantity:</label>
                        <input type="number" id="quantity" name="quantity" value="<?php echo $edit_product ? $edit_product['quantity'] : ''; ?>" required class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="main_image" class="block mb-1 font-medium">Main Image:</label>
                            <input type="file" id="main_image" name="main_image" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" accept="image/jpeg,image/jpg,image/png,image/gif">
                            <?php if ($edit_product && $edit_product['image']): ?>
                                <div class="mt-2 relative inline-block">
                                    <img src="uploads/<?php echo htmlspecialchars($edit_product['image']); ?>" alt="Main" class="w-20 h-20 object-cover rounded">
                                    <form method="POST" style="display: inline;" class="absolute top-0 right-0">
                                        <input type="hidden" name="image_path" value="<?php echo htmlspecialchars($edit_product['image']); ?>">
                                        <input type="hidden" name="is_main" value="1">
                                        <button type="submit" name="delete_image" class="bg-red-500 text-white p-1 rounded hover:bg-red-600 text-xs" onclick="return confirm('Are you sure you want to delete the main image?')">×</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label for="gallery_images" class="block mb-1 font-medium">Gallery Images:</label>
                            <input type="file" id="gallery_images" name="gallery_images[]" multiple class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" accept="image/jpeg,image/jpg,image/png,image/gif">
                            <p class="text-sm text-gray-500 mt-1">You can select multiple images at once. Max 5MB per image.</p>
                            
                            <?php if (!empty($images)): ?>
                                <div class="mt-3">
                                    <p class="text-sm font-medium text-gray-700 mb-2">Current Gallery Images:</p>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($images as $image): ?>
                                            <div class="relative">
                                                <img src="uploads/<?php echo htmlspecialchars($image['image_path']); ?>" alt="Gallery" class="w-20 h-20 object-cover rounded border">
                                                <form method="POST" style="display: inline;" class="absolute top-0 right-0">
                                                    <input type="hidden" name="image_path" value="<?php echo htmlspecialchars($image['image_path']); ?>">
                                                    <button type="submit" name="delete_image" class="bg-red-500 text-white p-1 rounded hover:bg-red-600 text-xs" onclick="return confirm('Are you sure you want to delete this gallery image?')">×</button>
                                                </form>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <label for="title" class="block mb-1 font-medium">Post Title:</label>
                        <input type="text" id="title" name="title" value="<?php echo $edit_product ? htmlspecialchars($edit_product['title']) : ''; ?>" required class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="content" class="block mb-1 font-medium">Content:</label>
                        <div id="editor" class="border border-gray-300 rounded" style="height: 300px;"></div>
                        <input type="hidden" name="content" id="hidden-content">
                    </div>
                    <button type="submit" name="save_changes" class="w-full bg-blue-500 text-white p-3 rounded hover:bg-blue-600 transition duration-200">Save Changes</button>
                </form>
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
                                if ($products && $products->num_rows > 0) {
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
                                    <?php endwhile;
                                } else {
                                    echo '<tr><td colspan="5" class="p-2 text-center text-gray-500">No products found</td></tr>';
                                }
                                ?>
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

        // Form submission handler (only for Save Changes form)
        const saveForm = document.querySelector('form[enctype="multipart/form-data"]');
        if (saveForm) {
            saveForm.addEventListener('submit', function (e) {
                document.getElementById('hidden-content').value = quill.root.innerHTML;
                // Optional: Add console log for debugging
                console.log('Form submitted with content:', quill.root.innerHTML);
            });
        }

        // Pre-fill editor if editing
        <?php if ($product_id > 0 && $edit_product && !empty($edit_product['content'])): ?>
            quill.root.innerHTML = <?php echo json_encode($edit_product['content']); ?>;
        <?php endif; ?>

        // File upload preview (optional enhancement)
        document.getElementById('gallery_images').addEventListener('change', function(e) {
            const files = e.target.files;
            if (files.length > 10) {
                alert('Please select no more than 10 images at once.');
                e.target.value = '';
                return;
            }
            
            for (let i = 0; i < files.length; i++) {
                if (files[i].size > 5 * 1024 * 1024) {
                    alert('File "' + files[i].name + '" is too large. Maximum size is 5MB.');
                    e.target.value = '';
                    return;
                }
            }
        });
    </script>
</body>
</html>
<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit;
}

// Handle product creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $image = null;

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 5 * 1024 * 1024; // 5MB in bytes
        if (in_array($_FILES['image']['type'], $allowed_types) && $_FILES['image']['size'] <= $max_size) {
            $upload_dir = 'uploads/';
            $image_name = time() . '_' . basename($_FILES['image']['name']);
            $target_file = $upload_dir . $image_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image = $image_name;
            } else {
                $error = "Failed to upload image.";
            }
        } else {
            $error = "Invalid file type or size exceeds 5MB. Allowed types: JPEG, PNG, JPG.";
        }
    }

    if (!isset($error)) {
        $sql = "INSERT INTO products (name, description, price, image) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssds', $name, $description, $price, $image);
        $stmt->execute();
        header('Location: products.php');
        exit;
    }
}

// Handle product update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $image = isset($_POST['existing_image']) ? $_POST['existing_image'] : null;

    // Handle image update
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 5 * 1024 * 1024; // 5MB in bytes
        if (in_array($_FILES['image']['type'], $allowed_types) && $_FILES['image']['size'] <= $max_size) {
            $upload_dir = 'uploads/';
            $image_name = time() . '_' . basename($_FILES['image']['name']);
            $target_file = $upload_dir . $image_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image = $image_name;
                // Delete old image if it exists
                $old_image = $_POST['existing_image'];
                if ($old_image && file_exists($upload_dir . $old_image)) {
                    unlink($upload_dir . $old_image);
                }
            } else {
                $error = "Failed to upload new image.";
            }
        } else {
            $error = "Invalid file type or size exceeds 5MB. Allowed types: JPEG, PNG, JPG.";
        }
    }

    if (!isset($error)) {
        $sql = "UPDATE products SET name = ?, description = ?, price = ?, image = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssdsi', $name, $description, $price, $image, $id);
        $stmt->execute();
        header('Location: products.php');
        exit;
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

// Fetch all products
$sql = "SELECT * FROM products";
$products = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Products</title>
    <link rel="stylesheet" href="styles.css">
    <script src="scripts.js"></script>
</head>
<body>
    <div class="container">
        <h2>Manage Products</h2>
        <?php if (isset($_SESSION['name'])): ?>
            <p class="session-info">Logged in as: <?php echo htmlspecialchars($_SESSION['name']); ?> (<a href="logout.php">Logout</a>)</p>
        <?php endif; ?>
        <nav>
            <a href="dashboard.php">Back to Dashboard</a>
            <a href="index.php">Home</a>
        </nav>
        <h3><?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></h3>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST" enctype="multipart/form-data">
            <?php if ($edit_product): ?>
                <input type="hidden" name="id" value="<?php echo $edit_product['id']; ?>">
                <input type="hidden" name="existing_image" value="<?php echo $edit_product['image']; ?>">
            <?php endif; ?>
            <input type="text" name="name" placeholder="Product Name" value="<?php echo $edit_product ? $edit_product['name'] : ''; ?>" required>
            <textarea name="description" placeholder="Description"><?php echo $edit_product ? $edit_product['description'] : ''; ?></textarea>
            <input type="number" name="price" placeholder="Price" step="0.01" value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>" required>
            <input type="file" name="image" accept="image/jpeg,image/png,image/jpg">
            <?php if ($edit_product && $edit_product['image']): ?>
                <p>Current Image: <img src="uploads/<?php echo htmlspecialchars($edit_product['image']); ?>" alt="Product Image" style="max-width: 200px; max-height: 200px;"></p>
            <?php endif; ?>
            <button type="submit" name="<?php echo $edit_product ? 'update_product' : 'add_product'; ?>">
                <?php echo $edit_product ? 'Update Product' : 'Add Product'; ?>
            </button>
        </form>
        <h3>Product List</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Image</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($product = $products->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $product['id']; ?></td>
                    <td><?php echo $product['name']; ?></td>
                    <td><?php echo $product['price']; ?></td>
                    <td><?php echo $product['image'] ? '<img src="uploads/' . htmlspecialchars($product['image']) . '" alt="Product Image" style="max-width: 100px; max-height: 100px;">' : 'No image'; ?></td>
                    <td>
                        <a href="product_details.php?id=<?php echo $product['id']; ?>">View</a>
                        <a href="products.php?edit=<?php echo $product['id']; ?>">Edit</a>
                        <a href="products.php?delete=<?php echo $product['id']; ?>" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
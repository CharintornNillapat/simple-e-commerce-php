<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit;
}

// Handle customer creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_customer'])) {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $address = $_POST['address'];
    $role = $_POST['role'];
    
    $sql = "INSERT INTO users (name, username, password, address, role) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssss', $name, $username, $password, $address, $role);
    $stmt->execute();
    header('Location: customers.php');
    exit;
}

// Handle customer update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_customer'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $username = $_POST['username'];
    $address = $_POST['address'];
    $role = $_POST['role'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
    
    if ($password) {
        $sql = "UPDATE users SET name = ?, username = ?, password = ?, address = ?, role = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssssi', $name, $username, $password, $address, $role, $id);
    } else {
        $sql = "UPDATE users SET name = ?, username = ?, address = ?, role = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssi', $name, $username, $address, $role, $id);
    }
    $stmt->execute();
    header('Location: customers.php');
    exit;
}

// Handle customer deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM users WHERE id = ? AND role = 'customer'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: customers.php');
    exit;
}

// Handle customer search
$search_query = '';
$customers = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $search_query = $_POST['search_query'];
    $sql = "SELECT * FROM users WHERE role = 'customer' AND (name LIKE ? OR username LIKE ? OR address LIKE ?)";
    $stmt = $conn->prepare($sql);
    $like_query = '%' . $search_query . '%';
    $stmt->bind_param('sss', $like_query, $like_query, $like_query);
    $stmt->execute();
    $customers = $stmt->get_result();
} else {
    $sql = "SELECT * FROM users WHERE role = 'customer'";
    $customers = $conn->query($sql);
}

// Fetch customer for editing
$edit_customer = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $sql = "SELECT * FROM users WHERE id = ? AND role = 'customer'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit_customer = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Customers</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Manage Customers</h2>
        <?php if (isset($_SESSION['name'])): ?>
            <p class="session-info">Logged in as: <?php echo htmlspecialchars($_SESSION['name']); ?> (<a href="logout.php">Logout</a>)</p>
        <?php endif; ?>
        <nav>
            <a href="dashboard.php">Back to Dashboard</a>
            <a href="index.php">Home</a>
            <a href="register.php">Register New Customer</a>
        </nav>
        <h3>Search Customers</h3>
        <form method="POST" class="search-form">
            <input type="text" name="search_query" placeholder="Search by name, username, or address" value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit" name="search">Search</button>
            <?php if ($search_query): ?>
                <a href="customers.php" class="button">Clear Search</a>
            <?php endif; ?>
        </form>
        <h3><?php echo $edit_customer ? 'Edit Customer' : 'Add New Customer'; ?></h3>
        <form method="POST">
            <?php if ($edit_customer): ?>
                <input type="hidden" name="id" value="<?php echo $edit_customer['id']; ?>">
            <?php endif; ?>
            <input type="text" name="name" placeholder="Name" value="<?php echo $edit_customer ? $edit_customer['name'] : ''; ?>" required>
            <input type="text" name="username" placeholder="Username" value="<?php echo $edit_customer ? $edit_customer['username'] : ''; ?>" required>
            <input type="password" name="password" placeholder="Password (leave blank to keep unchanged)" <?php echo $edit_customer ? '' : 'required'; ?>>
            <textarea name="address" placeholder="Address"><?php echo $edit_customer ? $edit_customer['address'] : ''; ?></textarea>
            <select name="role">
                <option value="customer" <?php echo ($edit_customer && $edit_customer['role'] == 'customer') ? 'selected' : ''; ?>>Customer</option>
            </select>
            <button type="submit" name="<?php echo $edit_customer ? 'update_customer' : 'add_customer'; ?>">
                <?php echo $edit_customer ? 'Update Customer' : 'Add Customer'; ?>
            </button>
        </form>
        <h3>Customer List</h3>
        <table>
            <thead>
                <tr>
                    <!-- <th>ID</th> -->
                    <th>Name</th>
                    <th>Username</th>
                    <th>Address</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($customer = $customers->fetch_assoc()): ?>
                <tr>
                    <!-- <td><?php echo $customer['id']; ?></td> -->
                    <td><?php echo $customer['name']; ?></td>
                    <td><?php echo $customer['username']; ?></td>
                    <td><?php echo $customer['address'] ?: 'No address'; ?></td>
                    <td><?php echo $customer['role']; ?></td>
                    <td><?php echo $customer['created_at']; ?></td>
                    <td>
                        <a href="customers.php?edit=<?php echo $customer['id']; ?>">Edit</a>
                        <a href="customers.php?delete=<?php echo $customer['id']; ?>" onclick="return confirm('Are you sure you want to delete this customer?');">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
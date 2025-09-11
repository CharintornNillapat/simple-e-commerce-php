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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Customers</title>
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
                    <li class="nav-item mb-2">
                        <a href="dashboard.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="products.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-box"></i>
                            <span>Products</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="customers.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-users"></i>
                            <span>Customers</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="orders.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Orders</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="admin_reorder_products.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'admin_reorder_products.php' ? 'bg-gray-700' : ''; ?>">
                            <i class="fas fa-sort"></i>
                            <span>Reorder Products</span>
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

        <!-- Main Content -->
        <main class="flex-1 p-6">
            <div class="content-header mb-6">
                <h1 class="text-2xl font-bold">Customers</h1>
                <div class="breadcrumb text-gray-600">
                    <span>Home</span> / <span class="current">Customers</span>
                </div>
            </div>

            <div class="stats-grid mb-6">
                <div class="stat-card flex items-center p-4 bg-white rounded shadow">
                    <div class="stat-icon text-blue-500 mr-4">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="text-xl font-semibold"><?php echo $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")->fetch_assoc()['count']; ?></h3>
                        <p class="text-gray-600">Total Customers</p>
                    </div>
                    <div class="stat-action ml-auto">
                        <a href="customers.php" class="text-blue-500 hover:underline">Manage <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>
            </div>

            <div class="quick-actions">
                <h2 class="text-xl font-semibold mb-4">Customer Management</h2>
                <div class="action-grid">
                    <div class="action-card bg-gray-100 p-8">
                        <h3 class="text-lg font-semibold mb-4">Search Customers</h3>
                        <form method="POST" class="flex gap-4 mb-6">
                            <input type="text" name="search_query" placeholder="Search by name, username, or address" value="<?php echo htmlspecialchars($search_query); ?>" class="flex-1 p-2 border border-gray-300 rounded">
                            <button type="submit" name="search" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Search</button>
                            <?php if ($search_query): ?>
                                <a href="customers.php" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">Clear Search</a>
                            <?php endif; ?>
                        </form>
                        <h3 class="text-lg font-semibold mb-4"><?php echo $edit_customer ? 'Edit Customer' : 'Add New Customer'; ?></h3>
                        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-2xl">
                            <?php if ($edit_customer): ?>
                                <input type="hidden" name="id" value="<?php echo $edit_customer['id']; ?>">
                            <?php endif; ?>
                            <div>
                                <label for="name" class="block mb-1 font-medium">Name:</label>
                                <input type="text" id="name" name="name" placeholder="Name" value="<?php echo $edit_customer ? $edit_customer['name'] : ''; ?>" required class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="username" class="block mb-1 font-medium">Username:</label>
                                <input type="text" id="username" name="username" placeholder="Username" value="<?php echo $edit_customer ? $edit_customer['username'] : ''; ?>" required class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="password" class="block mb-1 font-medium">Password:</label>
                                <input type="password" id="password" name="password" placeholder="Password (leave blank to keep unchanged)" <?php echo $edit_customer ? '' : 'required'; ?> class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="address" class="block mb-1 font-medium">Address:</label>
                                <textarea id="address" name="address" placeholder="Address" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" style="height: 120px; resize: vertical;"><?php echo $edit_customer ? $edit_customer['address'] : ''; ?></textarea>
                            </div>
                            <div>
                                <label for="role" class="block mb-1 font-medium">Role:</label>
                                <select id="role" name="role" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="customer" <?php echo ($edit_customer && $edit_customer['role'] == 'customer') ? 'selected' : ''; ?>>Customer</option>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <button type="submit" name="<?php echo $edit_customer ? 'update_customer' : 'add_customer'; ?>" class="w-full bg-blue-500 text-white p-3 rounded hover:bg-blue-600 transition duration-200">
                                    <?php echo $edit_customer ? 'Update Customer' : 'Add Customer'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="quick-actions mt-6">
                <h2 class="text-xl font-semibold mb-4">Customer List</h2>
                <div class="action-grid">
                    <div class="action-card overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b">
                                    <th class="p-2">Name</th>
                                    <th class="p-2">Username</th>
                                    <th class="p-2">Address</th>
                                    <th class="p-2">Role</th>
                                    <th class="p-2">Created At</th>
                                    <th class="p-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($customer = $customers->fetch_assoc()): ?>
                                <tr class="border-b">
                                    <td class="p-2"><?php echo htmlspecialchars($customer['name']); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($customer['username']); ?></td>
                                    <td class="p-2"><?php echo $customer['address'] ?: 'No address'; ?></td>
                                    <td class="p-2"><?php echo $customer['role']; ?></td>
                                    <td class="p-2"><?php echo $customer['created_at']; ?></td>
                                    <td class="p-2">
                                        <a href="customers.php?edit=<?php echo $customer['id']; ?>" class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 mr-2">Edit</a>
                                        <a href="customers.php?delete=<?php echo $customer['id']; ?>" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600" onclick="return confirm('Are you sure you want to delete this customer?');">Delete</a>
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
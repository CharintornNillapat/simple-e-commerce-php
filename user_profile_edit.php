<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$sql = "SELECT name, email, phone_number, address FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $address = $_POST['address'];

    $sql = "UPDATE users SET name = ?, email = ? , phone_number = ?, address = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssi', $name, $email, $phone_number, $address, $user_id);

    if ($stmt->execute()) {
        $_SESSION['name'] = $name; // Update session name
        header('Location: user_profile.php');
        exit;
    } else {
        $error = "Failed to update profile: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
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
                <h1 class="text-2xl font-bold">Edit Profile</h1>
                <div class="breadcrumb text-gray-600">
                    <a href="index.php" class="hover:underline">Home</a> / <a href="user_profile.php" class="hover:underline">My Profile</a> / <span class="current">Edit Profile</span>
                </div>
            </div>
            <div class="quick-actions">
                <div class="action-grid">
                    <div class="action-card bg-white p-6 rounded shadow">
                        <h2 class="text-xl font-semibold mb-4">Edit Your Profile</h2>
                        <?php if (isset($error)): ?>
                            <p class="text-red-500 mb-4"><?php echo $error; ?></p>
                        <?php endif; ?>
                        <form method="POST" class="space-y-4">
                            <div>
                                <label for="name" class="block mb-1 font-medium">Name:</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="email" class="block mb-1 font-medium">Email:</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="phone_number" class="block mb-1 font-medium">Phone Number:</label>
                                <input type="phone_number" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" required class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="address" class="block mb-1 font-medium">Address:</label>
                                <textarea id="address" name="address" rows="3" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                            <div class="flex space-x-4">
                                <button type="submit" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600 transition duration-200">Save Changes</button>
                                <a href="user_profile.php" class="bg-gray-500 text-white p-2 rounded hover:bg-gray-600 transition duration-200 text-center inline-block">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
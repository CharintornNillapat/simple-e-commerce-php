<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $address = $_POST['address'];
    $role = 'customer';

    // Check if username already exists
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $error = "Username already taken";
    } else {
        $sql = "INSERT INTO users (name, username, password, address, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssss', $name, $username, $password, $address, $role);
        if ($stmt->execute()) {
            $success = "Registration successful! Please <a href='customer_login.php'>login</a>.";
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Register</h2>
        <?php if (isset($_SESSION['name'])): ?>
            <p class="session-info">Logged in as: <?php echo htmlspecialchars($_SESSION['name']); ?> (<a href="logout.php">Logout</a>)</p>
        <?php endif; ?>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
        <form method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <textarea name="address" placeholder="Address" required></textarea>
            <button type="submit">Register</button>
        </form>
        <p><a href="index.php">Back to Home</a> | <a href="customer_login.php">Customer Login</a></p>
    </div>
</body>
</html>
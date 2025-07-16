<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: login.php');
    exit;
}

// Fetch the latest order for display (simplified)
$user_id = $_SESSION['user_id'];
$sql = "SELECT SUM(total_price) as total FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'] ?: 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Confirmation</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Purchase Confirmation</h2>
        <?php if (isset($_SESSION['name'])): ?>
            <p>Logged in as: <?php echo htmlspecialchars($_SESSION['name']); ?> (<a href="logout.php">Logout</a>)</p>
        <?php endif; ?>
        <nav>
            <a href="index.php">Back to Home</a>
            <a href="order.php">View Cart</a>
        </nav>
        <p>Your purchase of $<?php echo number_format($total, 2); ?> has been confirmed!</p>
        <h3>Payment Placeholder</h3>
        <p>This is a simulated payment process. In a real system, you would be redirected to a payment gateway (e.g., PayPal, Stripe).</p>
        <form method="POST" action="">
            <button type="submit" name="simulate_payment">Simulate Payment</button>
        </form>
        <?php
        if (isset($_POST['simulate_payment'])) {
            echo "<p class='success'>Payment of $" . number_format($total, 2) . " simulated successfully! Order is processing.</p>";
        }
        ?>
    </div>
</body>
</html>
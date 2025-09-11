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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-md">
        <h2 class="text-2xl font-bold text-center mb-6">Purchase Confirmation</h2>
        <?php if (isset($_SESSION['name'])): ?>
            <p class="text-center text-gray-600 mb-4">Logged in as: <?php echo htmlspecialchars($_SESSION['name']); ?> (<a href="logout.php" class="text-blue-500 hover:underline">Logout</a>)</p>
        <?php endif; ?>
        <nav class="flex justify-between mb-6">
            <a href="index.php" class="text-blue-500 hover:underline">Back to Home</a>
            <a href="orders.php" class="text-blue-500 hover:underline">View Cart</a>
        </nav>
        <p class="text-center mb-4">Your purchase of $<span class="font-semibold"><?php echo number_format($total, 2); ?></span> has been confirmed!</p>
        <h3 class="text-lg font-semibold text-center mb-2">Payment Placeholder</h3>
        <p class="text-center text-gray-600 mb-4">This is a simulated payment process. In a real system, you would be redirected to a payment gateway (e.g., PayPal, Stripe).</p>
        <form method="POST" action="" class="text-center">
            <button type="submit" name="simulate_payment" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition duration-200">Simulate Payment</button>
        </form>
        <?php
        if (isset($_POST['simulate_payment'])) {
            echo "<p class='text-green-500 text-center mt-4'>Payment of $<span class='font-semibold'>" . number_format($total, 2) . "</span> simulated successfully! Order is processing.</p>";
        }
        ?>
    </div>
</body>
</html>
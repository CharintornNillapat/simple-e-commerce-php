<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $order = $data['order'] ?? [];

    foreach ($order as $index => $id) {
        $stmt = $conn->prepare("UPDATE products SET order_index = ? WHERE id = ?");
        $stmt->bind_param('ii', $index, $id);
        $stmt->execute();
    }
    echo json_encode(['status' => 'success']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reorder Products</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        ul { list-style-type: none; padding: 0; width: 300px; }
        li { padding: 10px; margin: 5px; background: #eee; cursor: move; }
        button { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Drag and Drop to Reorder Products</h2>
        <?php if (isset($_SESSION['name'])): ?>
            <p>Logged in as: <?php echo htmlspecialchars($_SESSION['name']); ?> (<a href="logout.php">Logout</a>)</p>
        <?php endif; ?>
        <nav>
            <a href="products.php">Back to Manage Products</a>
        </nav>
        <ul id="product-list">
            <?php
            $sql = "SELECT id, name, order_index FROM products ORDER BY order_index ASC";
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()):
            ?>
                <li data-id="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></li>
            <?php endwhile; ?>
        </ul>
        <button onclick="saveOrder()">Save Order</button>
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
        <script>
            const el = document.getElementById('product-list');
            new Sortable(el, { animation: 150 });

            function saveOrder() {
                const ids = Array.from(el.children).map(li => li.dataset.id);
                fetch('admin_reorder_products.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order: ids })
                })
                .then(res => res.json())
                .then(data => alert("Order saved!"));
            }
        </script>
    </div>
</body>
</html>
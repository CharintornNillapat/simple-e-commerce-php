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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Reorder Products</title>
    <link rel="stylesheet" href="dashboard_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-store"></i> Admin Panel</h2>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="products.php" class="nav-link">
                            <i class="fas fa-box"></i>
                            <span>Products</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="customers.php" class="nav-link">
                            <i class="fas fa-users"></i>
                            <span>Customers</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="orders.php" class="nav-link">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Orders</span>
                        </a>
                    </li>
                    <li class="nav-item active">
                        <a href="admin_reorder_products.php" class="nav-link">
                            <i class="fas fa-sort"></i>
                            <span>Reorder Products</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <h1>Reorder Products</h1>
                <div class="breadcrumb">
                    <span>Home</span> / <span class="current">Reorder Products</span>
                </div>
            </div>

            <div class="quick-actions">
                <h2>Drag and Drop to Reorder</h2>
                <div class="action-grid">
                    <div class="action-card" style="padding: 2rem; text-align: center;">
                        <ul id="product-list" style="list-style-type: none; padding: 0; width: 100%;">
                            <?php
                            $sql = "SELECT id, name, order_index FROM products ORDER BY order_index ASC";
                            $result = $conn->query($sql);
                            while ($row = $result->fetch_assoc()):
                            ?>
                                <li data-id="<?= $row['id'] ?>" style="padding: 10px; margin: 5px; background: #eee; cursor: move; border-radius: 5px;">
                                    <?= htmlspecialchars($row['name']) ?>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                        <button onclick="saveOrder()" style="margin-top: 20px; padding: 10px 20px;">Save Order</button>
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
                </div>
            </div>
        </main>
    </div>
</body>
</html>
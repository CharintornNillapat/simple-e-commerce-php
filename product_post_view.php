<?php
session_start();
require_once 'db_connect.php';

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if ($product_id <= 0) {
    header('Location: index.php');
    exit;
}

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_image'])) {
    if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] == UPLOAD_ERR_OK) {
        $image = $_FILES['new_image']['name'];
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($image);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $check = getimagesize($_FILES['new_image']['tmp_name']);
        if ($check !== false) {
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($imageFileType, $allowed_types)) {
                if (move_uploaded_file($_FILES['new_image']['tmp_name'], $target_file)) {
                    $sql = "INSERT INTO product_images (product_id, image_path) VALUES (?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('is', $product_id, $image);
                    $stmt->execute();
                }
            }
        }
    }
    header('Location: product_post_view.php?product_id=' . $product_id);
    exit;
}

// Fetch product details
$sql = "SELECT p.id, p.name, p.image, p.price, p.quantity, p.category, pp.title, pp.content 
        FROM products p 
        LEFT JOIN product_posts pp ON p.id = pp.product_id 
        WHERE p.id = ? AND p.status = 'active'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

// Fetch product images
$image_sql = "SELECT image_path FROM product_images WHERE product_id = ? ORDER BY id ASC";
$image_stmt = $conn->prepare($image_sql);
$image_stmt->bind_param('i', $product_id);
$image_stmt->execute();
$images = $image_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (!$product) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name'] ?? 'Product'); ?> - Product Post</title>
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
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item mb-2">
                            <a href="user_profile.php" class="nav-link text-white hover:bg-gray-700 p-2 block rounded <?php echo basename($_SERVER['PHP_SELF']) == 'user_profile.php' ? 'bg-gray-700' : ''; ?>">
                                <i class="fas fa-user mr-2"></i> My Profile
                            </a>
                        </li>
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
                <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($product['name'] ?? 'Product'); ?></h1>
                <div class="breadcrumb text-gray-600">
                    <a href="index.php" class="hover:underline">Home</a> / <span class="current"><?php echo htmlspecialchars($product['title'] ?? $product['name']); ?></span>
                </div>
            </div>
            <section class="product-card max-w-5xl mx-auto p-6">
                <div class="product-layout flex flex-col md:flex-row gap-6">
                    <!-- Left: Gallery -->
                    <div class="product-gallery flex-1 md:max-w-[50%]">
                        <div class="carousel relative w-full h-96">
                            <div class="carousel-slides absolute top-0 left-0 w-full h-full">
                                <?php
                                $all_images = [];
                                if ($product['image']) $all_images[] = ['image_path' => $product['image']];
                                $all_images = array_merge($all_images, $images);
                                $image_count = count($all_images);
                                for ($i = 0; $i < min(5, $image_count); $i++) {
                                    echo '<div class="carousel-slide absolute w-full h-full bg-cover bg-center rounded-lg transition-opacity duration-500" style="background-image: url(\'uploads/' . htmlspecialchars($all_images[$i]['image_path']) . '\'); opacity: 0;"></div>';
                                }
                                if ($image_count === 0) {
                                    echo '<div class="carousel-slide absolute w-full h-full bg-cover bg-center rounded-lg transition-opacity duration-500" style="background-image: url(\'https://via.placeholder.com/400x400?text=No+Image\'); opacity: 0;"></div>';
                                }
                                ?>
                            </div>
                            <button class="carousel-prev absolute top-1/2 left-2 transform -translate-y-1/2 bg-gray-800 text-white p-2 rounded-full hover:bg-gray-700 focus:outline-none" onclick="plusSlides(-1)">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button class="carousel-next absolute top-1/2 right-2 transform -translate-y-1/2 bg-gray-800 text-white p-2 rounded-full hover:bg-gray-700 focus:outline-none" onclick="plusSlides(1)">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                            <div class="carousel-dots flex justify-center mt-4">
                                <?php for ($i = 0; $i < min(5, max(1, $image_count)); $i++) {
                                    echo '<span class="carousel-dot w-2.5 h-2.5 bg-gray-400 rounded-full mx-1 cursor-pointer hover:bg-gray-600 ' . ($i === 0 ? 'bg-gray-800' : '') . '" onclick="currentSlide(' . ($i + 1) . ')"></span>';
                                } ?>
                            </div>
                        </div>
                        <!-- Thumbnails -->
                        <div class="thumbnails flex gap-2 mt-4 overflow-x-auto">
                            <?php
                            foreach ($all_images as $index => $image) {
                                echo '<div class="thumbnail w-20 h-20 bg-cover bg-center rounded-lg cursor-pointer border-2 ' . ($index === 0 ? 'border-blue-500' : 'border-transparent') . '" style="background-image: url(\'uploads/' . htmlspecialchars($image['image_path']) . '\');" onclick="currentSlide(' . ($index + 1) . ')"></div>';
                            }
                            if ($image_count === 0) {
                                echo '<div class="thumbnail w-20 h-20 bg-cover bg-center rounded-lg cursor-pointer border-2 border-transparent" style="background-image: url(\'https://via.placeholder.com/80x80?text=No+Image\');"></div>';
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Right: Info -->
                    <div class="product-info flex-1 md:max-w-[50%]">
                        <h1 class="product-title text-2xl font-bold mb-2"><?php echo htmlspecialchars($product['title'] ?? $product['name']); ?></h1>
                        
                        <div class="product-rating mb-2">
                            <span class="rating text-yellow-400">★★★★☆ (4.5)</span>
                            <span class="reviews text-gray-600 ml-2">12 reviews</span>
                            <span class="sold text-gray-600 ml-2">50 sold</span>
                        </div>

                        <div class="product-price mb-2">
                            <span class="current-price text-xl font-bold text-gray-900">$<?php echo number_format($product['price'] ?? 0, 2); ?></span>
                            <span class="old-price text-sm text-gray-500 line-through ml-2">$<?php echo number_format(($product['price'] ?? 0) * 1.2, 2); ?></span>
                            <span class="discount text-green-600 ml-2">20% off</span>
                        </div>

                        <div class="product-shipping text-gray-600 mb-2">Free shipping on orders over $50</div>
                        <div class="product-quantity mb-2">
                            <label for="quantity" class="mr-2 text-gray-700">Quantity:</label>
                            <input type="number" id="quantity" name="quantity" min="1" max="<?php echo htmlspecialchars($product['quantity'] ?? 0); ?>" value="1" class="w-16 p-1 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div class="product-actions flex space-x-2 mb-4">
                            <form method="POST" action="cart.php" class="flex-1">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" name="add_to_cart" <?php echo ($product['quantity'] ?? 0) <= 0 ? 'disabled' : ''; ?> class="add-to-cart w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700 transition duration-200 disabled:bg-gray-400 disabled:cursor-not-allowed">
                                    Add to Cart
                                </button>
                            </form>
                            <button class="buy-now flex-1 bg-green-600 text-white p-2 rounded hover:bg-green-700 transition duration-200" onclick="alert('Buy Now feature coming soon!')">Buy Now</button>
                        </div>

                        <!-- Existing Content Section -->
                        <div class="product-content prose max-w-none">
                            <?php echo $product['content'] ?? '<p>No content available.</p>'; ?>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
    <script>
        let slideIndex = 1;
        showSlides(slideIndex);

        function plusSlides(n) {
            showSlides(slideIndex += n);
        }

        function currentSlide(n) {
            showSlides(slideIndex = n);
        }

        function showSlides(n) {
            let slides = document.getElementsByClassName("carousel-slide");
            let dots = document.getElementsByClassName("carousel-dot");
            let thumbnails = document.getElementsByClassName("thumbnail");
            if (n > slides.length) { slideIndex = 1 }
            if (n < 1) { slideIndex = slides.length }
            for (let i = 0; i < slides.length; i++) {
                slides[i].style.opacity = "0";
                slides[i].style.transition = "opacity 0.5s ease-in-out";
                thumbnails[i].classList.remove('border-blue-500');
                thumbnails[i].classList.add('border-transparent');
            }
            for (let i = 0; i < dots.length; i++) {
                dots[i].classList.remove('bg-gray-800');
                dots[i].classList.add('bg-gray-400');
            }
            slides[slideIndex - 1].style.opacity = "1";
            dots[slideIndex - 1].classList.remove('bg-gray-400');
            dots[slideIndex - 1].classList.add('bg-gray-800');
            thumbnails[slideIndex - 1].classList.remove('border-transparent');
            thumbnails[slideIndex - 1].classList.add('border-blue-500');
        }

        // Auto-slide every 3 seconds
        setInterval(() => plusSlides(1), 3000);
    </script>
</body>
</html>
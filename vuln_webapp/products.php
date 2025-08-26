<?php
require_once 'config.php';

$products = [];
$conn = getConnection();

$query = "SELECT * FROM products ORDER BY category, name";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - WebApp</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Product Catalog</h1>
            <nav>
                <ul>
                    <li><a href="index.html">Home</a></li>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="search.php">Search</a></li>
                    <li><a href="comments.php">Comments</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <h2>Available Products</h2>
            
            <div class="feature-grid">
                <?php foreach ($products as $product): ?>
                    <div class="feature-card">
                        <h3><?php echo $product['name']; ?></h3>
                        <p><?php echo $product['description']; ?></p>
                        <p><strong>Price: $<?php echo number_format($product['price'], 2); ?></strong></p>
                        <p><em>Category: <?php echo $product['category']; ?></em></p>
                        <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn">View Details</a>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top: 30px;">
                <h3>Categories</h3>
                <ul>
                    <li><a href="products.php?category=electronics">Electronics</a></li>
                    <li><a href="products.php?category=books">Books</a></li>
                    <li><a href="products.php?category=accessories">Accessories</a></li>
                </ul>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 WebApp. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>

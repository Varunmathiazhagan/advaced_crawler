<?php
require_once 'config.php';

if (isset($_GET['id'])) {
    $product_id = $_GET['id'];
    $conn = getConnection();
    
    // Direct query without sanitization
    $query = "SELECT * FROM products WHERE id = $product_id";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $product = $result->fetch_assoc();
    } else {
        $product = null;
    }
    
    $conn->close();
} else {
    $product = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product ? $product['name'] : 'Product Not Found'; ?> - WebApp</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Product Details</h1>
            <nav>
                <ul>
                    <li><a href="index.html">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="search.php">Search</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <?php if ($product): ?>
                <div class="form-container">
                    <h2><?php echo $product['name']; ?></h2>
                    
                    <div style="margin-bottom: 20px;">
                        <p><strong>Description:</strong> <?php echo $product['description']; ?></p>
                        <p><strong>Price:</strong> $<?php echo number_format($product['price'], 2); ?></p>
                        <p><strong>Category:</strong> <?php echo $product['category']; ?></p>
                        <p><strong>Product ID:</strong> <?php echo $product['id']; ?></p>
                    </div>
                    
                    <div class="feature-grid">
                        <div class="feature-card">
                            <h3>Add to Cart</h3>
                            <p>Purchase this item</p>
                            <a href="#" class="btn" onclick="alert('Cart functionality not implemented')">Add to Cart</a>
                        </div>
                        
                        <div class="feature-card">
                            <h3>Share Product</h3>
                            <p>Share with friends</p>
                            <a href="#" class="btn" onclick="navigator.share ? navigator.share({title: '<?php echo $product['name']; ?>', url: window.location.href}) : alert('Sharing not supported')">Share</a>
                        </div>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <h3>Related Products</h3>
                        <p>Explore similar items in the <a href="products.php"><?php echo $product['category']; ?></a> category.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-error">
                    Product not found or invalid product ID.
                </div>
                <p><a href="products.php">Browse all products</a></p>
            <?php endif; ?>
        </main>

        <footer>
            <p>&copy; 2025 WebApp. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>

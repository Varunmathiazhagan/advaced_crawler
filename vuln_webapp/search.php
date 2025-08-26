<?php
require_once 'config.php';

$search_results = [];
$search_query = '';

if (isset($_GET['q'])) {
    $search_query = $_GET['q'];
    $conn = getConnection();
    
    // Direct search query without sanitization
    $query = "SELECT * FROM products WHERE name LIKE '%$search_query%' OR description LIKE '%$search_query%'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $search_results[] = $row;
        }
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - WebApp</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Product Search</h1>
            <nav>
                <ul>
                    <li><a href="index.html">Home</a></li>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="comments.php">Comments</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <div class="search-box">
                <form method="GET" action="search.php">
                    <input type="text" name="q" placeholder="Search products..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <input type="submit" value="Search">
                </form>
            </div>

            <?php if ($search_query): ?>
                <h3>Search Results for: "<?php echo $search_query; ?>"</h3>
                
                <?php if (count($search_results) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product Name</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Category</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($search_results as $product): ?>
                                <tr>
                                    <td><?php echo $product['id']; ?></td>
                                    <td><?php echo $product['name']; ?></td>
                                    <td><?php echo $product['description']; ?></td>
                                    <td>$<?php echo number_format($product['price'], 2); ?></td>
                                    <td><?php echo $product['category']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info">
                        No products found matching your search criteria.
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div style="margin-top: 30px;">
                <h3>Search Tips:</h3>
                <ul>
                    <li>Try searching for: laptop, phone, book, coffee</li>
                    <li>Use partial words to find more results</li>
                    <li>Search in both product names and descriptions</li>
                </ul>
            </div>

            <div style="margin-top: 20px;">
                <h4>Advanced Search Examples:</h4>
                <p>Try these search terms to explore our database:</p>
                <ul>
                    <li><a href="search.php?q=electronics">electronics</a></li>
                    <li><a href="search.php?q=book">book</a></li>
                    <li><a href="search.php?q=high">high</a></li>
                    <li><a href="search.php?q=' OR '1'='1">Advanced Query</a></li>
                </ul>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 WebApp. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>

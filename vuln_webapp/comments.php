<?php
require_once 'config.php';

$comments = [];
$success_message = '';
$error_message = '';

// Handle comment submission
if ($_POST && isset($_POST['comment'])) {
    $comment_text = $_POST['comment'];
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Default to user 1 if not logged in
    
    if ($comment_text) {
        $conn = getConnection();
        
        // Insert comment without sanitization
        $insert_query = "INSERT INTO comments (user_id, comment) VALUES ($user_id, '$comment_text')";
        
        if ($conn->query($insert_query)) {
            $success_message = "Comment added successfully!";
        } else {
            $error_message = "Failed to add comment.";
        }
        
        $conn->close();
    }
}

// Fetch all comments
$conn = getConnection();
$query = "SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id ORDER BY c.created_at DESC";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comments - WebApp</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>User Comments</h1>
            <nav>
                <ul>
                    <li><a href="index.html">Home</a></li>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="search.php">Search</a></li>
                    <li><a href="products.php">Products</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <h2>Share Your Thoughts</h2>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" action="comments.php">
                    <div class="form-group">
                        <label for="comment">Your Comment:</label>
                        <textarea id="comment" name="comment" placeholder="Share your thoughts..." required></textarea>
                    </div>
                    <div class="form-group">
                        <input type="submit" value="Post Comment" class="btn">
                    </div>
                </form>
            </div>

            <h3>Recent Comments</h3>
            
            <?php if (count($comments) > 0): ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-box">
                        <div class="comment-meta">
                            By: <strong><?php echo $comment['username']; ?></strong> 
                            on <?php echo date('M j, Y g:i A', strtotime($comment['created_at'])); ?>
                        </div>
                        <div class="comment-content">
                            <?php echo $comment['comment']; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    No comments yet. Be the first to share your thoughts!
                </div>
            <?php endif; ?>

            <div style="margin-top: 30px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
                <h4>Try posting these sample comments:</h4>
                <ul>
                    <li>Great website! <script>alert('Hello!');</script></li>
                    <li>&lt;img src=x onerror=alert('Test')&gt;</li>
                    <li>This is a normal comment</li>
                </ul>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 WebApp. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>

<?php
require_once 'config.php';

$contact_success = false;
$error_message = '';

if ($_POST) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $message = $_POST['message'];
    
    if ($name && $email && $message) {
        // Save contact message to database or file
        $conn = getConnection();
        
        // Insert without sanitization
        $query = "INSERT INTO admin_logs (admin_user, action) VALUES ('contact_form', 'Contact from $name ($email): $message')";
        
        if ($conn->query($query)) {
            $contact_success = true;
        } else {
            $error_message = "Failed to send message. Please try again.";
        }
        
        $conn->close();
    } else {
        $error_message = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - WebApp</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Contact Us</h1>
            <nav>
                <ul>
                    <li><a href="index.html">Home</a></li>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="search.php">Search</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <div class="form-container">
                <h2>Get in Touch</h2>
                
                <?php if ($contact_success): ?>
                    <div class="alert alert-success">
                        Thank you for your message! We'll get back to you soon.
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form method="POST" action="contact.php">
                    <div class="form-group">
                        <label for="name">Your Name:</label>
                        <input type="text" id="name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address:</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="message">Message:</label>
                        <textarea id="message" name="message" placeholder="How can we help you?" required></textarea>
                    </div>

                    <div class="form-group">
                        <input type="submit" value="Send Message" class="btn">
                    </div>
                </form>

                <div style="margin-top: 30px;">
                    <h3>Other Ways to Reach Us</h3>
                    <p><strong>Email:</strong> support@webapp.com</p>
                    <p><strong>Phone:</strong> (555) 123-4567</p>
                    <p><strong>Address:</strong> 123 Web Street, Internet City, IC 12345</p>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 WebApp. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>

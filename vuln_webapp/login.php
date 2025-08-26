<?php
require_once 'config.php';

$error_message = '';
$success_message = '';

if ($_POST) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if ($username && $password) {
        $conn = getConnection();
        
        // Direct SQL query without sanitization
        $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $success_message = "Welcome back, " . $user['username'] . "!";
            
            // Redirect after successful login
            if ($user['role'] == 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: profile.php");
            }
            exit();
        } else {
            $error_message = "Invalid username or password!";
        }
        
        $conn->close();
    } else {
        $error_message = "Please fill in all fields!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - WebApp</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>WebApp Login</h1>
            <nav>
                <ul>
                    <li><a href="index.html">Home</a></li>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="search.php">Search</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <div class="form-container">
                <h2>Login to Your Account</h2>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-error"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <input type="submit" value="Login" class="btn">
                    </div>
                </form>

                <p style="text-align: center; margin-top: 20px;">
                    Don't have an account? <a href="register.php">Register here</a>
                </p>

                <div style="margin-top: 30px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
                    <h4>Test Accounts:</h4>
                    <p><strong>Admin:</strong> admin / admin123</p>
                    <p><strong>User:</strong> john / password</p>
                    <p><strong>User:</strong> alice / 123456</p>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 WebApp. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>

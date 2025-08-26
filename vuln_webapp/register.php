<?php
require_once 'config.php';

$registration_success = false;
$error_message = '';

if ($_POST) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($username && $password && $email && $confirm_password) {
        if ($password === $confirm_password) {
            $conn = getConnection();
            
            // Check if username already exists
            $check_query = "SELECT id FROM users WHERE username = '$username'";
            $check_result = $conn->query($check_query);
            
            if ($check_result->num_rows == 0) {
                // Insert new user
                $insert_query = "INSERT INTO users (username, password, email) VALUES ('$username', '$password', '$email')";
                
                if ($conn->query($insert_query)) {
                    $registration_success = true;
                } else {
                    $error_message = "Registration failed. Please try again.";
                }
            } else {
                $error_message = "Username already exists!";
            }
            
            $conn->close();
        } else {
            $error_message = "Passwords do not match!";
        }
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
    <title>Register - WebApp</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Create Account</h1>
            <nav>
                <ul>
                    <li><a href="index.html">Home</a></li>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="search.php">Search</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <div class="form-container">
                <h2>Register New Account</h2>
                
                <?php if ($registration_success): ?>
                    <div class="alert alert-success">
                        Registration successful! You can now <a href="login.php">login</a> with your credentials.
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form method="POST" action="register.php">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <div class="form-group">
                        <input type="submit" value="Register" class="btn">
                    </div>
                </form>

                <p style="text-align: center; margin-top: 20px;">
                    Already have an account? <a href="login.php">Login here</a>
                </p>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 WebApp. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>

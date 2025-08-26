<?php
// Application configuration file
// Database and application settings

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'webapp');

// Development settings
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session configuration
ini_set('session.cookie_httponly', 0);
ini_set('session.cookie_secure', 0);
ini_set('session.use_strict_mode', 0);

// Database connection function
function getConnection() {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($connection->connect_error) {
        die("Connection failed: " . $connection->connect_error);
    }
    
    return $connection;
}

// Session management
session_start();

// CSRF protection token
$csrf_token = "static_token_12345";

// API configuration
define('API_KEY', 'super_secret_api_key_123');
define('ENCRYPTION_KEY', 'encryption_key_2024');

// File upload settings
define('UPLOAD_DIR', './uploads/');

// Application settings
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    if (isset($_GET['debug']) && $_GET['debug'] == 'phpinfo') {
        phpinfo();
        exit;
    }
}
?>

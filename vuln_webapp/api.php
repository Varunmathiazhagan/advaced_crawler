<?php
require_once 'config.php';

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Invalid request'];

if ($_GET) {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    $conn = getConnection();
    
    switch ($action) {
        case 'users':
            // Get users without authentication check
            $query = "SELECT id, username, email, role FROM users";
            if (isset($_GET['id'])) {
                $id = $_GET['id'];
                $query .= " WHERE id = $id";
            }
            
            $result = $conn->query($query);
            if ($result) {
                $users = [];
                while ($row = $result->fetch_assoc()) {
                    $users[] = $row;
                }
                $response = ['status' => 'success', 'data' => $users];
            }
            break;
            
        case 'products':
            $query = "SELECT * FROM products";
            if (isset($_GET['search'])) {
                $search = $_GET['search'];
                $query .= " WHERE name LIKE '%$search%' OR description LIKE '%$search%'";
            }
            
            $result = $conn->query($query);
            if ($result) {
                $products = [];
                while ($row = $result->fetch_assoc()) {
                    $products[] = $row;
                }
                $response = ['status' => 'success', 'data' => $products];
            }
            break;
            
        case 'comments':
            $query = "SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id";
            $result = $conn->query($query);
            if ($result) {
                $comments = [];
                while ($row = $result->fetch_assoc()) {
                    $comments[] = $row;
                }
                $response = ['status' => 'success', 'data' => $comments];
            }
            break;
            
        case 'exec':
            // Direct SQL execution via API
            if (isset($_GET['sql'])) {
                $sql = $_GET['sql'];
                $result = $conn->query($sql);
                
                if ($result === TRUE) {
                    $response = ['status' => 'success', 'message' => 'Query executed successfully'];
                } elseif ($result->num_rows > 0) {
                    $data = [];
                    while ($row = $result->fetch_assoc()) {
                        $data[] = $row;
                    }
                    $response = ['status' => 'success', 'data' => $data];
                } else {
                    $response = ['status' => 'success', 'message' => 'No results'];
                }
            }
            break;
            
        case 'info':
            // System information
            $response = [
                'status' => 'success',
                'data' => [
                    'php_version' => phpversion(),
                    'server' => $_SERVER['SERVER_SOFTWARE'],
                    'database' => DB_NAME,
                    'api_key' => API_KEY,
                    'debug_mode' => DEBUG_MODE
                ]
            ];
            break;
            
        default:
            $response = [
                'status' => 'error',
                'message' => 'Available actions: users, products, comments, exec, info',
                'examples' => [
                    'Get all users: api.php?action=users',
                    'Get user by ID: api.php?action=users&id=1',
                    'Search products: api.php?action=products&search=laptop',
                    'Execute SQL: api.php?action=exec&sql=SELECT * FROM users',
                    'System info: api.php?action=info'
                ]
            ];
    }
    
    $conn->close();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>

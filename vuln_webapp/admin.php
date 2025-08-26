<?php
require_once 'config.php';

// Simple admin check
$is_admin = false;
if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
    $is_admin = true;
}

$users = [];
$logs = [];
$stats = [];

if ($is_admin) {
    $conn = getConnection();
    
    // Get all users
    $user_query = "SELECT * FROM users ORDER BY created_at DESC";
    $user_result = $conn->query($user_query);
    if ($user_result) {
        while ($row = $user_result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    
    // Get recent logs
    $log_query = "SELECT * FROM admin_logs ORDER BY timestamp DESC LIMIT 10";
    $log_result = $conn->query($log_query);
    if ($log_result) {
        while ($row = $log_result->fetch_assoc()) {
            $logs[] = $row;
        }
    }
    
    // Get statistics
    $stats_queries = [
        'total_users' => "SELECT COUNT(*) as count FROM users",
        'total_products' => "SELECT COUNT(*) as count FROM products",
        'total_comments' => "SELECT COUNT(*) as count FROM comments"
    ];
    
    foreach ($stats_queries as $key => $query) {
        $result = $conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats[$key] = $row['count'];
        }
    }
    
    $conn->close();
}

// Handle admin actions
if ($_POST && $is_admin) {
    $action = $_POST['action'];
    $conn = getConnection();
    
    if ($action == 'delete_user' && isset($_POST['user_id'])) {
        $user_id = $_POST['user_id'];
        $delete_query = "DELETE FROM users WHERE id = $user_id";
        $conn->query($delete_query);
        
        // Log the action
        $log_query = "INSERT INTO admin_logs (admin_user, action) VALUES ('{$_SESSION['username']}', 'Deleted user ID: $user_id')";
        $conn->query($log_query);
    }
    
    if ($action == 'execute_query' && isset($_POST['sql_query'])) {
        $sql_query = $_POST['sql_query'];
        $result = $conn->query($sql_query);
        
        // Log the action
        $log_query = "INSERT INTO admin_logs (admin_user, action) VALUES ('{$_SESSION['username']}', 'Executed SQL: $sql_query')";
        $conn->query($log_query);
    }
    
    $conn->close();
    header("Location: admin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - WebApp</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Administration Panel</h1>
            <nav>
                <ul>
                    <li><a href="index.html">Home</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <?php if (!$is_admin): ?>
                <div class="alert alert-error">
                    Access denied. Administrator privileges required.
                </div>
                <p><a href="login.php">Please login as administrator</a></p>
            <?php else: ?>
                
                <h2>System Statistics</h2>
                <div class="feature-grid">
                    <div class="feature-card">
                        <h3>Total Users</h3>
                        <p style="font-size: 2em; color: #667eea;"><?php echo $stats['total_users']; ?></p>
                    </div>
                    <div class="feature-card">
                        <h3>Total Products</h3>
                        <p style="font-size: 2em; color: #667eea;"><?php echo $stats['total_products']; ?></p>
                    </div>
                    <div class="feature-card">
                        <h3>Total Comments</h3>
                        <p style="font-size: 2em; color: #667eea;"><?php echo $stats['total_comments']; ?></p>
                    </div>
                </div>

                <h3>User Management</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo $user['username']; ?></td>
                                <td><?php echo $user['email']; ?></td>
                                <td><?php echo $user['role']; ?></td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="submit" value="Delete" class="btn" onclick="return confirm('Are you sure?')">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h3>SQL Query Console</h3>
                <div class="form-container">
                    <form method="POST">
                        <input type="hidden" name="action" value="execute_query">
                        <div class="form-group">
                            <label for="sql_query">Execute SQL Query:</label>
                            <textarea id="sql_query" name="sql_query" placeholder="SELECT * FROM users;" style="font-family: monospace;"></textarea>
                        </div>
                        <div class="form-group">
                            <input type="submit" value="Execute Query" class="btn">
                        </div>
                    </form>
                    
                    <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
                        <h4>Quick Queries:</h4>
                        <ul>
                            <li>SELECT * FROM users</li>
                            <li>SELECT * FROM products</li>
                            <li>SELECT * FROM comments</li>
                            <li>SHOW TABLES</li>
                        </ul>
                    </div>
                </div>

                <h3>Recent Activity Logs</h3>
                <?php if (count($logs) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Admin User</th>
                                <th>Action</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo $log['admin_user']; ?></td>
                                    <td><?php echo $log['action']; ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($log['timestamp'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info">No activity logs found.</div>
                <?php endif; ?>

            <?php endif; ?>
        </main>

        <footer>
            <p>&copy; 2025 WebApp. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>

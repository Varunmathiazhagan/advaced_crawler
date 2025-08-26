<?php
require_once 'config.php';

$upload_message = '';
$upload_success = false;

// Create upload directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

if ($_POST && isset($_FILES['uploaded_file'])) {
    $file = $_FILES['uploaded_file'];
    $filename = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    
    if ($file['error'] === 0) {
        // No file type validation - allows any file type
        $destination = UPLOAD_DIR . $filename;
        
        if (move_uploaded_file($file_tmp, $destination)) {
            $upload_success = true;
            $upload_message = "File uploaded successfully! <a href='$destination' target='_blank'>View file</a>";
            
            // Log the upload
            $conn = getConnection();
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
            $log_query = "INSERT INTO admin_logs (admin_user, action) VALUES ('user_$user_id', 'File uploaded: $filename')";
            $conn->query($log_query);
            $conn->close();
        } else {
            $upload_message = "Failed to upload file.";
        }
    } else {
        $upload_message = "Upload error: " . $file['error'];
    }
}

// List uploaded files
$uploaded_files = [];
if (is_dir(UPLOAD_DIR)) {
    $files = scandir(UPLOAD_DIR);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $uploaded_files[] = $file;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload - WebApp</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>File Upload</h1>
            <nav>
                <ul>
                    <li><a href="index.html">Home</a></li>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="products.php">Products</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <div class="form-container">
                <h2>Upload Files</h2>
                
                <?php if ($upload_message): ?>
                    <div class="alert <?php echo $upload_success ? 'alert-success' : 'alert-error'; ?>">
                        <?php echo $upload_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="upload.php" enctype="multipart/form-data">
                    <div class="upload-area">
                        <h3>Choose File to Upload</h3>
                        <input type="file" name="uploaded_file" required>
                        <p>Upload any file type (images, documents, etc.)</p>
                    </div>
                    
                    <div class="form-group">
                        <input type="submit" value="Upload File" class="btn">
                    </div>
                </form>

                <?php if (count($uploaded_files) > 0): ?>
                    <h3>Uploaded Files</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Filename</th>
                                <th>Size</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($uploaded_files as $file): ?>
                                <tr>
                                    <td><?php echo $file; ?></td>
                                    <td><?php echo filesize(UPLOAD_DIR . $file); ?> bytes</td>
                                    <td>
                                        <a href="<?php echo UPLOAD_DIR . $file; ?>" target="_blank" class="btn">View</a>
                                        <a href="download.php?file=<?php echo urlencode($file); ?>" class="btn">Download</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <div style="margin-top: 30px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
                    <h4>Upload Guidelines:</h4>
                    <ul>
                        <li>All file types are supported</li>
                        <li>Maximum file size: Based on server configuration</li>
                        <li>Files are stored in the uploads directory</li>
                        <li>Uploaded files are accessible via direct URL</li>
                    </ul>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 WebApp. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>

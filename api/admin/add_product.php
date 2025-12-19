<?php
// Start output buffering to catch any unwanted output
ob_start();

// Suppress any output that might interfere with JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once '../database.php';
require_once '../sanitize.php';

// Clear any output that might have been generated
ob_clean();

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Validate required fields
$label = sanitize_string($_POST['label'] ?? '', 255);
$description = sanitize_string($_POST['description'] ?? '', 1000);
$price = floatval($_POST['price'] ?? 0);
$category = sanitize_string($_POST['category'] ?? '', 100);
$unit = sanitize_string($_POST['unit'] ?? '', 50);

if (empty($label) || empty($description) || $price <= 0 || empty($category) || empty($unit)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit;
}

// Handle image upload
$image_url = '';
if (isset($_FILES['image'])) {
    $upload_error = $_FILES['image']['error'];
    
    if ($upload_error !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        $error_msg = $error_messages[$upload_error] ?? 'Unknown upload error: ' . $upload_error;
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Image upload error: ' . $error_msg]);
        exit;
    }
    
    $upload_dir = '../uploads/products/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
            exit;
        }
    }
    
    // Check if directory is writable
    if (!is_writable($upload_dir)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Upload directory is not writable']);
        exit;
    }
    
    $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.']);
        exit;
    }
    
    // Generate unique filename
    $filename = uniqid('product_', true) . '.' . $file_extension;
    $file_path = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
        $image_url = 'uploads/products/' . $filename;
    } else {
        $error = error_get_last();
        $error_msg = $error ? $error['message'] : 'Unknown error';
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Failed to upload image: ' . $error_msg]);
        exit;
    }
} else {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'No image file provided']);
    exit;
}

init_db();
$conn = get_db_connection();

$query = "INSERT INTO products (label, description, price, image_url, category, unit, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

$result = execute_sql($conn, $query, [$label, $description, $price, $image_url, $category, $unit]);

if ($result !== false) {
    $product_id = last_insert_id($conn, 'products');
    close_connection($conn);
    ob_end_clean(); // Clear output buffer before sending JSON
    echo json_encode(['success' => true, 'message' => 'Product added successfully', 'product_id' => $product_id, 'image_url' => $image_url]);
    exit;
} else {
    $error_msg = $GLOBALS['use_postgres'] ? pg_last_error($conn) : ($conn->lastErrorMsg() ?? 'Database error');
    close_connection($conn);
    error_log("Failed to insert product: " . $error_msg);
    ob_end_clean(); // Clear output buffer before sending JSON
    echo json_encode(['success' => false, 'message' => 'Failed to add product to database: ' . $error_msg]);
    exit;
}
?>


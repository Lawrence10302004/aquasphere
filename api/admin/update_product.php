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

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if this is a multipart form (file upload) or JSON
$is_multipart = !empty($_FILES['image']);

if ($is_multipart) {
    // Handle form data with file upload
    $product_id = sanitize_int($_POST['id'] ?? 0);
    
    if ($product_id <= 0) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }
    
    $label = sanitize_string($_POST['label'] ?? '', 255);
    $description = sanitize_string($_POST['description'] ?? '', 1000);
    $price = floatval($_POST['price'] ?? 0);
    $category = sanitize_string($_POST['category'] ?? '', 100);
    $unit = sanitize_string($_POST['unit'] ?? '', 50);
    $image_url = '';
    
    // Handle image upload if provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_error = $_FILES['image']['error'];
        
        // Upload directory setup (same as add_product.php)
        $upload_base = '';
        $is_volume = false;
        
        if (!empty($_ENV['RAILWAY_VOLUME_PATH'])) {
            $upload_base = rtrim($_ENV['RAILWAY_VOLUME_PATH'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $is_volume = true;
        } elseif (!empty($_ENV['UPLOAD_DIR'])) {
            $upload_base = rtrim($_ENV['UPLOAD_DIR'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        } else {
            $root_dir = dirname(__DIR__, 2);
            $upload_base = $root_dir . DIRECTORY_SEPARATOR;
        }
        
        if ($is_volume) {
            $upload_dir = $upload_base . 'products' . DIRECTORY_SEPARATOR;
        } else {
            $upload_dir = $upload_base . 'uploads' . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR;
        }
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            @mkdir($upload_dir, 0777, true);
        }
        
        // Check if directory is writable
        if (!is_writable($upload_dir)) {
            @chmod($upload_dir, 0777);
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
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
            exit;
        }
    }
} else {
    // Handle JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }
    
    $product_id = sanitize_int($input['id'] ?? 0);
    $label = sanitize_string($input['label'] ?? '', 255);
    $description = sanitize_string($input['description'] ?? '', 1000);
    $price = floatval($input['price'] ?? 0);
    $category = sanitize_string($input['category'] ?? '', 100);
    $unit = sanitize_string($input['unit'] ?? '', 50);
    $image_url = sanitize_string($input['image_url'] ?? '', 500);
}

if (empty($label) || empty($description) || $price <= 0 || empty($category) || empty($unit)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit;
}

init_db();
$conn = get_db_connection();

// Check if product exists
$check_query = "SELECT id FROM products WHERE id = ?";
$check_result = execute_sql($conn, $check_query, [$product_id]);

if ($GLOBALS['use_postgres']) {
    $product_exists = pg_fetch_assoc($check_result) !== false;
} else {
    $product_exists = $check_result->fetchArray(SQLITE3_ASSOC) !== false;
}

if (!$product_exists) {
    close_connection($conn);
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

// Build update query - only update image_url if provided
$update_fields = [];
$update_params = [];

$update_fields[] = "label = ?";
$update_params[] = $label;

$update_fields[] = "description = ?";
$update_params[] = $description;

$update_fields[] = "price = ?";
$update_params[] = $price;

$update_fields[] = "category = ?";
$update_params[] = $category;

$update_fields[] = "unit = ?";
$update_params[] = $unit;

// Only update image_url if a new one is provided
if (!empty($image_url)) {
    $update_fields[] = "image_url = ?";
    $update_params[] = $image_url;
}

$update_fields[] = "updated_at = CURRENT_TIMESTAMP";
$update_params[] = $product_id;

$query = "UPDATE products SET " . implode(', ', $update_fields) . " WHERE id = ?";
$result = execute_sql($conn, $query, $update_params);

if ($result !== false) {
    close_connection($conn);
    ob_end_clean();
    echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
    exit;
} else {
    $error_msg = $GLOBALS['use_postgres'] ? pg_last_error($conn) : ($conn->lastErrorMsg() ?? 'Database error');
    close_connection($conn);
    error_log("Failed to update product: " . $error_msg);
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Failed to update product: ' . $error_msg]);
    exit;
}
?>


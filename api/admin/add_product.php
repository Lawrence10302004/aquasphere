<?php
session_start();
require_once '../database.php';
require_once '../sanitize.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Validate required fields
$label = sanitize_input($_POST['label'] ?? '');
$description = sanitize_input($_POST['description'] ?? '');
$price = floatval($_POST['price'] ?? 0);
$category = sanitize_input($_POST['category'] ?? '');
$unit = sanitize_input($_POST['unit'] ?? '');

if (empty($label) || empty($description) || $price <= 0 || empty($category) || empty($unit)) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit;
}

// Handle image upload
$image_url = '';
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/products/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
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
        echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Please upload an image']);
    exit;
}

init_db();
$conn = get_db_connection();

$query = "INSERT INTO products (label, description, price, image_url, category, unit, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

$result = execute_sql($conn, $query, [$label, $description, $price, $image_url, $category, $unit]);

if ($result) {
    $product_id = last_insert_id($conn, 'products');
    close_connection($conn);
    echo json_encode(['success' => true, 'message' => 'Product added successfully', 'product_id' => $product_id, 'image_url' => $image_url]);
} else {
    close_connection($conn);
    echo json_encode(['success' => false, 'message' => 'Failed to add product']);
}
?>


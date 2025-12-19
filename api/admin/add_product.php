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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Validate required fields
$label = sanitize_input($input['label'] ?? '');
$description = sanitize_input($input['description'] ?? '');
$price = floatval($input['price'] ?? 0);
$image = sanitize_input($input['image'] ?? '');
$icon = sanitize_input($input['icon'] ?? '');
$category = sanitize_input($input['category'] ?? '');
$unit = sanitize_input($input['unit'] ?? '');

if (empty($label) || empty($description) || $price <= 0 || empty($icon) || empty($category) || empty($unit)) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit;
}

// Ensure icon class has 'fa-' prefix if not present
if (!str_starts_with($icon, 'fa-')) {
    $icon = 'fa-' . $icon;
}

init_db();
$conn = get_db_connection();

$query = "INSERT INTO products (label, description, price, image_url, icon_class, category, unit, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

$result = execute_sql($conn, $query, [$label, $description, $price, $image, $icon, $category, $unit]);

if ($result) {
    $product_id = last_insert_id($conn, 'products');
    close_connection($conn);
    echo json_encode(['success' => true, 'message' => 'Product added successfully', 'product_id' => $product_id]);
} else {
    close_connection($conn);
    echo json_encode(['success' => false, 'message' => 'Failed to add product']);
}
?>


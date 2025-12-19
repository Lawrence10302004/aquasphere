<?php
session_start();
require_once '../database.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$product_id = intval($input['id']);

init_db();
$conn = get_db_connection();

$query = "DELETE FROM products WHERE id = ?";
$result = execute_sql($conn, $query, [$product_id]);

if ($result !== false) {
    if ($GLOBALS['use_postgres']) {
        $affected = pg_affected_rows($result);
    } else {
        $affected = $conn->changes();
    }
    
    close_connection($conn);
    
    if ($affected > 0) {
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
} else {
    close_connection($conn);
    echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
}
?>


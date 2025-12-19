<?php
// Start output buffering to catch any unexpected output
ob_start();

// Disable error display to prevent HTML in JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set headers first
header('Content-Type: application/json');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    ob_end_flush();
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    ob_end_flush();
    exit;
}

try {
    require_once '../database.php';

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['id'])) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        ob_end_flush();
        exit;
    }

    $product_id = intval($input['id']);

    if ($product_id <= 0) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        ob_end_flush();
        exit;
    }

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
        
        ob_clean();
        if ($affected > 0) {
            echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Product not found']);
        }
        ob_end_flush();
        exit;
    } else {
        close_connection($conn);
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
        ob_end_flush();
        exit;
    }
} catch (Exception $e) {
    if (isset($conn)) {
        close_connection($conn);
    }
    error_log("Error deleting product: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: Failed to delete product']);
    ob_end_flush();
    exit;
}
?>



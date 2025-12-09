<?php
/**
 * Save persisted user state (all localStorage-equivalent data)
 * Supports partial updates - only provided fields are updated
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];

init_db();
$conn = get_db_connection();

// Build dynamic UPDATE query with only provided fields
$updates = [];
$params = [];
$param_num = 1;

$fields_map = [
    'cart' => 'saved_cart',
    'delivery_address' => 'delivery_address',
    'selected_cart_items' => 'selected_cart_items',
    'checkout_items' => 'checkout_items',
    'pending_order_id' => 'pending_order_id',
    'pending_checkout_items' => 'pending_checkout_items',
    'payment_redirect_time' => 'payment_redirect_time',
    'paymongo_checkout_url' => 'paymongo_checkout_url',
    'payment_page_url' => 'payment_page_url',
    'pending_cancellation_orders' => 'pending_cancellation_orders'
];

foreach ($fields_map as $input_key => $db_column) {
    // Use array_key_exists so null values are included (to clear fields)
    if (array_key_exists($input_key, $input)) {
        $value = $input[$input_key];
        // Convert arrays/objects to JSON strings
        if (is_array($value) || is_object($value)) {
            $value_json = json_encode($value);
        } else {
            $value_json = $value !== null ? (string)$value : null;
        }
        
        if ($GLOBALS['use_postgres']) {
            $updates[] = "$db_column = $" . $param_num;
        } else {
            $updates[] = "$db_column = ?";
        }
        $params[] = $value_json;
        $param_num++;
    }
}

// Always update updated_at
if ($GLOBALS['use_postgres']) {
    $updates[] = "updated_at = CURRENT_TIMESTAMP";
} else {
    $updates[] = "updated_at = CURRENT_TIMESTAMP";
}

if (!empty($updates)) {
    if ($GLOBALS['use_postgres']) {
        $params[] = $user_id;
        $query = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = $" . $param_num;
    } else {
        $params[] = $user_id;
        $query = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
    }
    
    $result = execute_sql($conn, $query, $params);
    $success = $result !== false;
} else {
    // No fields to update
    $success = true;
}

close_connection($conn);

if (!$success) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save state']);
    exit;
}

echo json_encode(['success' => true]);
?>


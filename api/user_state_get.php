<?php
/**
 * Get persisted user state (all localStorage-equivalent data)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

$user_id = $_SESSION['user_id'];

init_db();
$conn = get_db_connection();

$query = "SELECT saved_cart, delivery_address, selected_cart_items, checkout_items, 
                 pending_order_id, pending_checkout_items, payment_redirect_time, 
                 paymongo_checkout_url, payment_page_url, pending_cancellation_orders 
          FROM users WHERE id = ?";
$result = execute_sql($conn, $query, [$user_id]);

// Default values
$state = [
    'cart' => [],
    'delivery_address' => null,
    'selected_cart_items' => [],
    'checkout_items' => [],
    'pending_order_id' => null,
    'pending_checkout_items' => null,
    'payment_redirect_time' => null,
    'paymongo_checkout_url' => null,
    'payment_page_url' => null,
    'pending_cancellation_orders' => []
];

if ($result !== false) {
    if ($GLOBALS['use_postgres']) {
        $row = pg_fetch_assoc($result);
    } else {
        $row = $result->fetchArray(SQLITE3_ASSOC);
    }
    if ($row) {
        // Map database columns to response keys
        $field_map = [
            'saved_cart' => 'cart',
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
        
        foreach ($field_map as $db_col => $response_key) {
            if (isset($row[$db_col]) && !empty($row[$db_col])) {
                $decoded = json_decode($row[$db_col], true);
                if ($decoded !== null) {
                    $state[$response_key] = $decoded;
                } else {
                    // If JSON decode fails, try as string (for simple values)
                    $state[$response_key] = $row[$db_col];
                }
            }
        }
    }
}

close_connection($conn);

echo json_encode([
    'success' => true,
    ...$state
]);
?>


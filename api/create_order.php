<?php
/**
 * Create Order Endpoint
 * Creates a new order in the database
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'database.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$user_id = intval($input['user_id'] ?? 0);
$items = $input['items'] ?? [];
$delivery_address = $input['delivery_address'] ?? null;
$payment_method = $input['payment_method'] ?? 'COD';
$delivery_date = $input['delivery_date'] ?? null;
$delivery_time = $input['delivery_time'] ?? null;

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Valid user_id is required']);
    exit;
}

if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Order items are required']);
    exit;
}

if (!$delivery_address) {
    echo json_encode(['success' => false, 'message' => 'Delivery address is required']);
    exit;
}

// Calculate total
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += floatval($item['price']) * intval($item['quantity']);
}

$delivery_fee = 50;
$total_amount = $subtotal + $delivery_fee;

// Initialize database
init_db();
$conn = get_db_connection();

// Check if paymongo_source_id column exists, add if not
$check_column_query = $GLOBALS['use_postgres'] 
    ? "SELECT column_name FROM information_schema.columns WHERE table_name='orders' AND column_name='paymongo_source_id'"
    : "PRAGMA table_info(orders)";

$column_check = execute_sql($conn, $check_column_query);
$column_exists = false;

if ($GLOBALS['use_postgres']) {
    $column_exists = pg_fetch_assoc($column_check) !== false;
} else {
    while ($row = $column_check->fetchArray(SQLITE3_ASSOC)) {
        if ($row['name'] === 'paymongo_source_id') {
            $column_exists = true;
            break;
        }
    }
}

if (!$column_exists) {
    $alter_query = "ALTER TABLE orders ADD COLUMN paymongo_source_id " . get_text_type();
    execute_sql($conn, $alter_query);
}

// Create order
$query = "INSERT INTO orders (user_id, delivery_date, delivery_time, delivery_address, total_amount, payment_method, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

$result = execute_sql($conn, $query, [
    $user_id,
    $delivery_date,
    $delivery_time,
    json_encode($delivery_address), // Store address as JSON
    $total_amount,
    $payment_method
]);

if ($result === false) {
    close_connection($conn);
    echo json_encode(['success' => false, 'message' => 'Failed to create order']);
    exit;
}

// Get order ID
$order_id = last_insert_id($conn, 'orders');

// Insert order items
foreach ($items as $item) {
    $item_query = "INSERT INTO order_items (order_id, product_name, product_price, quantity, subtotal) VALUES (?, ?, ?, ?, ?)";
    $item_price = floatval($item['price']);
    $item_quantity = intval($item['quantity']);
    $item_subtotal = $item_price * $item_quantity;
    
    execute_sql($conn, $item_query, [
        $order_id,
        $item['name'] ?? $item['product_name'] ?? 'Unknown Product',
        $item_price,
        $item_quantity,
        $item_subtotal
    ]);
}

// Insert initial "pending" status into history
$history_query = "INSERT INTO order_status_history (order_id, user_id, status, payment_method, created_at) VALUES (?, ?, 'pending', ?, CURRENT_TIMESTAMP)";
$history_result = execute_sql($conn, $history_query, [$order_id, $user_id, $payment_method]);
if ($history_result === false) {
    error_log("Failed to insert order status history: " . ($GLOBALS['use_postgres'] ? pg_last_error($conn) : "SQLite error"));
}

close_connection($conn);

echo json_encode([
    'success' => true,
    'order_id' => $order_id,
    'total_amount' => $total_amount,
    'message' => 'Order created successfully'
]);
?>


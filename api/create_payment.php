<?php
/**
 * PayMongo Payment Source Creation
 * Creates a GCash payment source via PayMongo API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Load environment variables from .env file
require_once 'load_env.php';
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
$amount = floatval($input['amount'] ?? 0);
$order_id = $input['order_id'] ?? null;
$redirect_url = $input['redirect_url'] ?? null;

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit;
}

if (!$redirect_url) {
    echo json_encode(['success' => false, 'message' => 'Redirect URL is required']);
    exit;
}

// PayMongo API Configuration
// Load from environment variable (set in .env file or server environment)
$paymongo_secret_key = $_ENV['PAYMONGO_SECRET_KEY'] ?? getenv('PAYMONGO_SECRET_KEY');

if (!$paymongo_secret_key) {
    echo json_encode([
        'success' => false,
        'message' => 'PayMongo secret key not configured. Please set PAYMONGO_SECRET_KEY in environment variables or .env file.'
    ]);
    exit;
}
$paymongo_api_url = 'https://api.paymongo.com/v1';

// Determine if using sandbox or live
$is_sandbox = strpos($paymongo_secret_key, 'sk_test_') === 0;
if ($is_sandbox) {
    $paymongo_api_url = 'https://api.paymongo.com/v1'; // Sandbox uses same URL
}

// Create payment source (GCash)
$source_data = [
    'data' => [
        'attributes' => [
            'amount' => intval($amount * 100), // Convert to centavos
            'currency' => 'PHP',
            'type' => 'gcash',
            'redirect' => [
                'success' => $redirect_url . '?status=success',
                'failed' => $redirect_url . '?status=failed'
            ]
        ]
    ]
];

// Make API call to PayMongo
$ch = curl_init($paymongo_api_url . '/sources');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($source_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Basic ' . base64_encode($paymongo_secret_key . ':')
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Payment gateway error: ' . $curl_error
    ]);
    exit;
}

$response_data = json_decode($response, true);

if ($http_code === 201 && isset($response_data['data']['attributes']['redirect']['checkout_url'])) {
    // Success - return checkout URL
    $checkout_url = $response_data['data']['attributes']['redirect']['checkout_url'];
    $source_id = $response_data['data']['id'];
    
    // Store payment source ID in order if order_id is provided
    if ($order_id) {
        $conn = get_db_connection();
        init_db();
        
        // Check if paymongo_payment_id column exists, if not add it
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
        
        // Update order with payment source ID
        $update_query = "UPDATE orders SET paymongo_source_id = ? WHERE id = ?";
        execute_sql($conn, $update_query, [$source_id, $order_id]);
        close_connection($conn);
    }
    
    echo json_encode([
        'success' => true,
        'checkout_url' => $checkout_url,
        'source_id' => $source_id
    ]);
} else {
    // Error from PayMongo
    $error_message = $response_data['errors'][0]['detail'] ?? 'Unknown error from payment gateway';
    echo json_encode([
        'success' => false,
        'message' => 'Payment gateway error: ' . $error_message,
        'debug' => $is_sandbox ? $response_data : null
    ]);
}
?>


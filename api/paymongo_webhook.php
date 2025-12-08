<?php
/**
 * PayMongo Webhook Handler
 * Handles payment status updates from PayMongo
 */

header('Content-Type: application/json');
require_once 'database.php';

// Get webhook signature from header
$webhook_signature = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';

// Get raw request body
$payload = file_get_contents('php://input');
$payload_data = json_decode($payload, true);

// Verify webhook signature (optional but recommended for production)
// For now, we'll process the webhook without signature verification in sandbox mode
// In production, you should verify the signature using PayMongo's webhook secret

// Log webhook received
error_log("PayMongo webhook received: " . $payload);

// Check if this is a source.chargeable event
if (isset($payload_data['data']['type']) && $payload_data['data']['type'] === 'source.chargeable') {
    $source_id = $payload_data['data']['id'];
    $attributes = $payload_data['data']['attributes'];
    
    // Find order by source ID
    $conn = get_db_connection();
    init_db();
    
    // Check if paymongo_source_id column exists
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
    
    if ($column_exists) {
        // Find order by source ID
        $query = "SELECT id, total_amount FROM orders WHERE paymongo_source_id = ?";
        $result = execute_sql($conn, $query, [$source_id]);
        
        if ($result !== false) {
            if ($GLOBALS['use_postgres']) {
                $order = pg_fetch_assoc($result);
            } else {
                $order = $result->fetchArray(SQLITE3_ASSOC);
            }
            
            if ($order) {
                // Create payment intent to charge the source
                $paymongo_secret_key = $_ENV['PAYMONGO_SECRET_KEY'] ?? 'sk_test_xxxxxxxxxxxxx';
                $paymongo_api_url = 'https://api.paymongo.com/v1';
                
                $payment_data = [
                    'data' => [
                        'attributes' => [
                            'amount' => intval($order['total_amount'] * 100),
                            'currency' => 'PHP',
                            'source' => [
                                'id' => $source_id,
                                'type' => 'source'
                            ],
                            'description' => 'Order #' . $order['id']
                        ]
                    ]
                ];
                
                // Create payment
                $ch = curl_init($paymongo_api_url . '/payments');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Basic ' . base64_encode($paymongo_secret_key . ':')
                ]);
                
                $payment_response = curl_exec($ch);
                $payment_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                $payment_result = json_decode($payment_response, true);
                
                if ($payment_http_code === 201 && isset($payment_result['data']['attributes']['status'])) {
                    $payment_status = $payment_result['data']['attributes']['status'];
                    
                    // Update order status based on payment status
                    if ($payment_status === 'paid') {
                        $update_query = "UPDATE orders SET status = 'paid', updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                        execute_sql($conn, $update_query, [$order['id']]);
                        error_log("Order #{$order['id']} marked as paid via PayMongo");
                    } elseif ($payment_status === 'failed') {
                        $update_query = "UPDATE orders SET status = 'payment_failed', updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                        execute_sql($conn, $update_query, [$order['id']]);
                        error_log("Order #{$order['id']} payment failed via PayMongo");
                    }
                }
            }
        }
    }
    
    close_connection($conn);
}

// Always return 200 to acknowledge webhook receipt
http_response_code(200);
echo json_encode(['received' => true]);
?>


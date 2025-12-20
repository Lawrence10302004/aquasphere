<?php
/**
 * Database Initialization Endpoint
 * Call this once after deployment to initialize the database
 * Or it will auto-initialize on first database operation
 */

header('Content-Type: application/json');
require_once 'database.php';

try {
    // Initialize database (creates tables if they don't exist)
    init_db();
    
    echo json_encode([
        'success' => true,
        'message' => 'Database initialized successfully',
        'tables' => ['users', 'system_settings', 'otp_verification', 'orders', 'order_items']
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database initialization failed: ' . $e->getMessage()
    ]);
}
?>


<?php
/**
 * Health Check Endpoint for Railway
 * Used to verify the application is running
 */

header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'service' => 'AquaSphere API',
    'version' => '1.0.0'
];

// Check database connection (optional, won't fail if not available)
try {
    require_once 'database.php';
    $conn = get_db_connection();
    $health['database'] = 'connected';
    $health['db_type'] = $GLOBALS['use_postgres'] ? 'PostgreSQL' : 'SQLite';
    close_connection($conn);
} catch (Exception $e) {
    $health['database'] = 'not_available';
    $health['database_error'] = $e->getMessage();
}

echo json_encode($health, JSON_PRETTY_PRINT);
?>


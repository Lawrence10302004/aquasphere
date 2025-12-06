<?php
/**
 * Admin Dashboard Statistics API
 */

header('Content-Type: application/json');
require_once '../database.php';

// Get statistics
$conn = get_db_connection();

// Total users
$query = "SELECT COUNT(*) as count FROM users";
$result = execute_sql($conn, $query);
if ($GLOBALS['use_postgres']) {
    $row = pg_fetch_assoc($result);
    $total_users = $row['count'];
} else {
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $total_users = $row['count'];
}

// Total orders
$query = "SELECT COUNT(*) as count FROM orders";
$result = execute_sql($conn, $query);
if ($GLOBALS['use_postgres']) {
    $row = pg_fetch_assoc($result);
    $total_orders = $row['count'];
} else {
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $total_orders = $row['count'];
}

// Pending deliveries
$query = "SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'preparing', 'delivering')";
$result = execute_sql($conn, $query);
if ($GLOBALS['use_postgres']) {
    $row = pg_fetch_assoc($result);
    $pending_deliveries = $row['count'];
} else {
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $pending_deliveries = $row['count'];
}

// Today's orders
$query = "SELECT COUNT(*) as count FROM orders WHERE DATE(order_date) = CURRENT_DATE";
$result = execute_sql($conn, $query);
if ($GLOBALS['use_postgres']) {
    $row = pg_fetch_assoc($result);
    $today_orders = $row['count'];
} else {
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $today_orders = $row['count'];
}

// Check email configuration
$email_configured = !empty(get_system_setting('brevo_api_key')) && 
                    !empty(get_system_setting('brevo_sender_email')) &&
                    get_system_setting('enable_email_notifications', '0') === '1';

close_connection($conn);

echo json_encode([
    'success' => true,
    'stats' => [
        'total_users' => (int)$total_users,
        'total_orders' => (int)$total_orders,
        'pending_deliveries' => (int)$pending_deliveries,
        'today_orders' => (int)$today_orders,
        'email_configured' => $email_configured
    ]
]);
?>


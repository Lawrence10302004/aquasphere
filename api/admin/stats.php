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
$query = "SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'preparing', 'shipped', 'out_for_delivery')";
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

// Total revenue
$query = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status != 'cancelled'";
$result = execute_sql($conn, $query);
if ($GLOBALS['use_postgres']) {
    $row = pg_fetch_assoc($result);
    $total_revenue = $row['total'];
} else {
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $total_revenue = $row['total'];
}

// Today's revenue
$query = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE DATE(order_date) = CURRENT_DATE AND status != 'cancelled'";
$result = execute_sql($conn, $query);
if ($GLOBALS['use_postgres']) {
    $row = pg_fetch_assoc($result);
    $today_revenue = $row['total'];
} else {
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $today_revenue = $row['total'];
}

// Orders by status
$status_counts = [];
$statuses = ['pending', 'preparing', 'shipped', 'out_for_delivery', 'delivered', 'cancelled', 'paid'];
foreach ($statuses as $status) {
    $query = "SELECT COUNT(*) as count FROM orders WHERE status = ?";
    $result = execute_sql($conn, $query, [$status]);
    if ($GLOBALS['use_postgres']) {
        $row = pg_fetch_assoc($result);
        $status_counts[$status] = (int)$row['count'];
    } else {
        $row = $result->fetchArray(SQLITE3_ASSOC);
        $status_counts[$status] = (int)$row['count'];
    }
}

// Recent orders (last 10)
$query = "SELECT o.id, o.order_date, o.total_amount, o.status, u.username 
          FROM orders o 
          LEFT JOIN users u ON o.user_id = u.id 
          ORDER BY o.order_date DESC 
          LIMIT 10";
$result = execute_sql($conn, $query);
$recent_orders = [];
if ($result !== false) {
    if ($GLOBALS['use_postgres']) {
        while ($row = pg_fetch_assoc($result)) {
            $recent_orders[] = $row;
        }
    } else {
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $recent_orders[] = $row;
        }
    }
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
        'total_revenue' => floatval($total_revenue),
        'today_revenue' => floatval($today_revenue),
        'status_counts' => $status_counts,
        'recent_orders' => $recent_orders,
        'email_configured' => $email_configured
    ]
]);
?>


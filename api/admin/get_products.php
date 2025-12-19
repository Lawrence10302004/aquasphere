<?php
session_start();
require_once '../database.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

init_db();
$conn = get_db_connection();

$query = "SELECT * FROM products ORDER BY created_at DESC";
$result = execute_sql($conn, $query);

$products = [];
if ($result !== false) {
    if ($GLOBALS['use_postgres']) {
        while ($row = pg_fetch_assoc($result)) {
            $products[] = $row;
        }
    } else {
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $products[] = $row;
        }
    }
}

close_connection($conn);
echo json_encode(['success' => true, 'products' => $products]);
?>


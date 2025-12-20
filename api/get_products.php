<?php
// Start output buffering to catch any unwanted output
ob_start();

// Suppress any output that might interfere with JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'database.php';

// Clear any output that might have been generated
ob_clean();

header('Content-Type: application/json');

init_db();
$conn = get_db_connection();

$query = "SELECT id, label, description, price, image_url, category, unit, created_at, updated_at FROM products ORDER BY created_at DESC";
$result = execute_sql($conn, $query);

$products = [];
if ($result !== false) {
    if ($GLOBALS['use_postgres']) {
        while ($row = pg_fetch_assoc($result)) {
            // Ensure image_url is included and not null
            if (!isset($row['image_url'])) {
                $row['image_url'] = '';
            }
            $products[] = $row;
        }
    } else {
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            // Ensure image_url is included and not null
            if (!isset($row['image_url'])) {
                $row['image_url'] = '';
            }
            $products[] = $row;
        }
    }
}

close_connection($conn);
ob_end_clean(); // Clear output buffer before sending JSON

// Ensure proper JSON encoding with no errors
$json = json_encode(['success' => true, 'products' => $products], JSON_UNESCAPED_SLASHES);

if ($json === false) {
    $error = json_last_error_msg();
    error_log("JSON encoding error: " . $error);
    echo json_encode(['success' => false, 'message' => 'Error encoding products data']);
    exit;
}

echo $json;
exit;


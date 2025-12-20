<?php
/**
 * Get Current User Endpoint
 * Returns the currently logged-in user from session
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

require_once 'database.php';

// Initialize DB and fetch user record
init_db();
$conn = get_db_connection();

$user_id = intval($_SESSION['user_id']);

if ($GLOBALS['use_postgres']) {
    $query = "SELECT id, username, email, first_name, last_name, gender, date_of_birth, is_admin, created_at, last_login 
              FROM users 
              WHERE id = $1";
    $result = pg_query_params($conn, $query, [$user_id]);
    $user = $result ? pg_fetch_assoc($result) : null;
} else {
    $query = "SELECT id, username, email, first_name, last_name, gender, date_of_birth, is_admin, created_at, last_login 
              FROM users 
              WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $user = $result ? $result->fetchArray(SQLITE3_ASSOC) : null;
}

close_connection($conn);

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// Return user data
echo json_encode([
    'success' => true,
    'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'] ?? '',
        'first_name' => $user['first_name'] ?? '',
        'last_name' => $user['last_name'] ?? '',
        'gender' => $user['gender'] ?? '',
        'date_of_birth' => $user['date_of_birth'] ?? '',
        'is_admin' => $user['is_admin'] ?? 0,
        'created_at' => $user['created_at'] ?? null,
        'last_login' => $user['last_login'] ?? null
    ]
]);
?>

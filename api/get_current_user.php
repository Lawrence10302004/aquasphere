<?php
/**
 * Get Current User Profile Data
 * Returns the profile data of the currently logged-in user
 */

// Start output buffering
ob_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set headers
header('Content-Type: application/json');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    ob_clean();
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    ob_end_flush();
    exit;
}

try {
    require_once 'database.php';
    
    $conn = get_db_connection();
    $user_id = $_SESSION['user_id'];
    
    // Get user data from database
    $query = "SELECT id, username, email, first_name, last_name, gender, date_of_birth, created_at, last_login FROM users WHERE id = ?";
    $result = execute_sql($conn, $query, [$user_id]);
    
    if ($GLOBALS['use_postgres']) {
        $user = pg_fetch_assoc($result);
    } else {
        $user = $result->fetchArray(SQLITE3_ASSOC);
    }
    
    close_connection($conn);
    
    if ($user) {
        // Format date if needed
        if ($user['date_of_birth']) {
            // Convert database date format to display format if needed
            $date = new DateTime($user['date_of_birth']);
            $user['date_of_birth'] = $date->format('Y-m-d');
            $user['birthday'] = $date->format('Y-m-d');
        }
        
        // Map database fields to frontend fields
        $userData = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'] ?? '',
            'firstName' => $user['first_name'] ?? '',
            'lastName' => $user['last_name'] ?? '',
            'first_name' => $user['first_name'] ?? '',
            'last_name' => $user['last_name'] ?? '',
            'gender' => $user['gender'] ?? '',
            'birthday' => $user['date_of_birth'] ?? '',
            'date_of_birth' => $user['date_of_birth'] ?? '',
            'created_at' => $user['created_at'] ?? '',
            'last_login' => $user['last_login'] ?? ''
        ];
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'user' => $userData
        ]);
        ob_end_flush();
        exit;
    } else {
        close_connection($conn);
        ob_clean();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        ob_end_flush();
        exit;
    }
} catch (Exception $e) {
    if (isset($conn)) {
        close_connection($conn);
    }
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    ob_end_flush();
    exit;
}
?>


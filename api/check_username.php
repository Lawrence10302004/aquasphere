<?php
/**
 * API endpoint to check if username exists
 */

header('Content-Type: application/json');
require_once 'database.php';

$username = trim($_GET['username'] ?? '');

if (empty($username)) {
    echo json_encode(['success' => true, 'exists' => false]);
    exit;
}

$user = get_user_by_username($username);
echo json_encode(['success' => true, 'exists' => !empty($user)]);
?>


<?php
/**
 * API endpoint to check if email exists
 */

header('Content-Type: application/json');
require_once 'database.php';

$email = trim($_GET['email'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => true, 'exists' => false]);
    exit;
}

$user = get_user_by_email($email);
echo json_encode(['success' => true, 'exists' => !empty($user)]);
?>


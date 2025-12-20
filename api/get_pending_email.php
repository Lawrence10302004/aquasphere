<?php
/**
 * Get Pending Email from Session
 */

header('Content-Type: application/json');
session_start();

$email = $_SESSION['pending_email'] ?? '';

if (empty($email)) {
    echo json_encode([
        'success' => false,
        'message' => 'No pending registration found'
    ]);
} else {
    echo json_encode([
        'success' => true,
        'email' => $email
    ]);
}
?>


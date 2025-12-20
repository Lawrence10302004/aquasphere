<?php
/**
 * Logout Handler
 */

session_start();
session_destroy();

// Clear remember me cookie
if (isset($_COOKIE['aquasphere_remember'])) {
    setcookie('aquasphere_remember', '', time() - 3600, '/');
}

// Handle redirect parameter
$redirect = $_GET['redirect'] ?? 'login.html';
if ($redirect === 'index.html') {
    header('Location: ../index.html');
} else {
    header('Location: ../login.html');
}
exit;
?>


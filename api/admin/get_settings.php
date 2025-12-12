<?php
/**
 * Get System Settings API
 */

header('Content-Type: application/json');
require_once '../database.php';
require_once '../sanitize.php';

// Check if user is admin (you should implement proper session/auth check)
// For now, we'll just return the settings

$settings = get_all_system_settings();

// Set defaults for missing settings
$defaults = [
    'brevo_api_key' => '',
    'brevo_sender_email' => '',
    'brevo_sender_name' => 'AquaSphere',
    'enable_email_notifications' => '0',
    'site_name' => 'AquaSphere',
    'site_description' => 'Clean water delivery service',
    'max_users' => '1000',
    'session_timeout' => '30',
    'password_min_length' => '8',
    'max_login_attempts' => '5',
    'enable_two_factor' => '0'
];

foreach ($defaults as $key => $default_value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $default_value;
    }
}

// Don't return the API key value for security (just indicate if it exists)
if (isset($settings['brevo_api_key']) && !empty($settings['brevo_api_key'])) {
    $settings['brevo_api_key'] = '***SAVED***'; // Placeholder to indicate key exists
}

echo json_encode([
    'success' => true,
    'settings' => $settings
]);
?>


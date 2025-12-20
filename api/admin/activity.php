<?php
/**
 * Recent Activity API
 */

header('Content-Type: application/json');
require_once '../database.php';

// Placeholder for recent activity
// In a real system, you would have an activity log table
$activities = [
    [
        'username' => 'System',
        'action' => 'System initialized',
        'time' => date('Y-m-d H:i:s')
    ]
];

echo json_encode([
    'success' => true,
    'activities' => $activities
]);
?>


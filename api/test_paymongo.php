<?php
/**
 * Test PayMongo Configuration
 * Use this to verify your PayMongo secret key is configured correctly
 */

header('Content-Type: application/json');
require_once 'load_env.php';

// Check if key is set
$paymongo_secret_key = $_ENV['PAYMONGO_SECRET_KEY'] ?? getenv('PAYMONGO_SECRET_KEY') ?? $_SERVER['PAYMONGO_SECRET_KEY'] ?? null;

$result = [
    'key_configured' => !empty($paymongo_secret_key),
    'key_prefix' => $paymongo_secret_key ? substr($paymongo_secret_key, 0, 8) . '...' : 'NOT SET',
    'key_length' => $paymongo_secret_key ? strlen($paymongo_secret_key) : 0,
    'is_sandbox' => $paymongo_secret_key && strpos($paymongo_secret_key, 'sk_test_') === 0,
    'sources_checked' => [
        '_ENV' => isset($_ENV['PAYMONGO_SECRET_KEY']),
        'getenv' => !empty(getenv('PAYMONGO_SECRET_KEY')),
        '_SERVER' => isset($_SERVER['PAYMONGO_SECRET_KEY'])
    ]
];

echo json_encode($result, JSON_PRETTY_PRINT);
?>


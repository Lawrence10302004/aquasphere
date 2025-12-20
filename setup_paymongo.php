<?php
/**
 * Quick Setup Script for PayMongo
 * Run this once to create your .env file with the PayMongo secret key
 * 
 * Usage: php setup_paymongo.php
 */

$envFile = __DIR__ . '/.env';

// Prompt for secret key
echo "🔑 PayMongo Setup\n";
echo "================\n\n";
echo "Enter your PayMongo Secret Key (starts with sk_test_ or sk_live_): ";
$handle = fopen("php://stdin", "r");
$secretKey = trim(fgets($handle));
fclose($handle);

if (empty($secretKey) || (strpos($secretKey, 'sk_test_') !== 0 && strpos($secretKey, 'sk_live_') !== 0)) {
    echo "❌ Error: Invalid secret key format. Key must start with 'sk_test_' or 'sk_live_'\n";
    exit(1);
}

// Check if .env already exists
if (file_exists($envFile)) {
    echo "⚠️  .env file already exists!\n";
    echo "Current contents:\n";
    echo file_get_contents($envFile) . "\n\n";
    echo "Do you want to update it? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($line) !== 'y') {
        echo "Setup cancelled.\n";
        exit(0);
    }
}

// Create .env file
$envContent = "# PayMongo Configuration\n";
$envContent .= "# IMPORTANT: This file contains sensitive information and should NOT be committed to git\n";
$envContent .= "# The .gitignore file should prevent this from being committed\n\n";
$envContent .= "PAYMONGO_SECRET_KEY={$secretKey}\n";

if (file_put_contents($envFile, $envContent)) {
    echo "✅ .env file created successfully!\n";
    echo "📁 Location: " . $envFile . "\n";
    echo "🔑 PayMongo Secret Key: {$secretKey}\n\n";
    echo "⚠️  Remember: Never commit this file to git!\n";
    echo "✅ The .gitignore file will prevent it from being committed.\n";
} else {
    echo "❌ Error: Could not create .env file.\n";
    echo "Please check file permissions and try again.\n";
    exit(1);
}
?>


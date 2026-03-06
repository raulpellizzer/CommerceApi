<?php
/**
 * Generate API Credentials
 * 
 * Creates secure API key and secret for client authentication
 * 
 * Usage: php generate_api_credentials.php
 */
$rootDir = dirname(dirname(__DIR__));

require $rootDir . '/vendor/autoload.php';
require $rootDir . '/bootstrap.php';

use Src\Model\ApiClientModel;
use Src\System\DatabaseConnector;

echo "============================================\n";
echo "API Client Credential Generator\n";
echo "Production-Grade Security\n";
echo "============================================\n\n";

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable($rootDir);
$dotenv->load();

// Connect to database
$dbConnector = new DatabaseConnector();
$db = $dbConnector->getConnection(null);

$apiClientModel = new ApiClientModel($db);

// Prompt for client details
echo "Enter client details:\n";
echo "---------------------------------------------\n";

$clientName = readline("Client Name (e.g., 'WPF Desktop App'): ");
$clientVersion = readline("Client Version (default: 1.0.0): ") ?: '1.0.0';
$clientType = readline("Client Type [wpf_desktop/mobile_ios/mobile_android/web] (default: wpf_desktop): ") ?: 'wpf_desktop';
$rateLimitMinute = readline("Rate Limit (per minute, default: 60): ") ?: 60;
$rateLimitHour = readline("Rate Limit (per hour, default: 1000): ") ?: 1000;

echo "\n";

// Generate secure credentials
echo "Generating secure credentials...\n";

// Generate API Key (32 random bytes, hex encoded)
$apiKey = bin2hex(random_bytes(16)) . '_' . time();

// Generate API Secret (64 random bytes, hex encoded)
$apiSecretRaw = bin2hex(random_bytes(32));

// Create client
$clientId = $apiClientModel->createClient([
    'api_key' => $apiKey,
    'api_secret' => $apiSecretRaw,
    'client_name' => $clientName,
    'client_version' => $clientVersion,
    'client_type' => $clientType,
    'rate_limit_per_minute' => (int)$rateLimitMinute,
    'rate_limit_per_hour' => (int)$rateLimitHour,
    'notes' => 'Generated on ' . date('Y-m-d H:i:s')
]);

if (!$clientId) {
    echo "\n❌ ERROR: Failed to create API client\n";
    exit(1);
}

echo "\n✅ API Client created successfully!\n";
echo "============================================\n\n";

echo "Client ID: {$clientId}\n\n";

echo "API Credentials:\n";
echo "---------------------------------------------\n";
echo "API_KEY={$apiKey}\n";
echo "API_SECRET={$apiSecretRaw}\n";
echo "---------------------------------------------\n\n";

echo "⚠️  IMPORTANT SECURITY NOTES:\n";
echo "1. Add these to your .env file:\n";
echo "   WPF_APP_API_KEY={$apiKey}\n";
echo "   WPF_APP_API_SECRET={$apiSecretRaw}\n\n";

echo "2. NEVER commit these to version control!\n";
echo "3. Store API_SECRET securely in your WPF app\n";
echo "4. These credentials cannot be recovered if lost\n\n";

echo "Rate Limits:\n";
echo "- Per Minute: {$rateLimitMinute} requests\n";
echo "- Per Hour: {$rateLimitHour} requests\n\n";

echo "Next Steps:\n";
echo "1. Update .env on your API server\n";
echo "2. Embed credentials in your WPF app (encrypted)\n";
echo "3. Test authentication with a sample request\n\n";

echo "============================================\n";
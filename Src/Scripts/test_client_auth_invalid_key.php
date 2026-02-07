<?php
/**
 * Test Client Authentication - Invalid API Key
 * Should return 403 Forbidden
 */

$rootDir = dirname(dirname(__DIR__));
require $rootDir . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable($rootDir);
$dotenv->load();

echo "============================================\n";
echo "Testing Client Authentication - INVALID API Key\n";
echo "============================================\n\n";

// Configuration
$apiUrl = 'https://raulpellizzer.com/Dev/CommerceApi/products';

// WRONG API KEY
$apiKey = 'INVALID_KEY_12345';
$apiSecret = $_ENV['WPF_APP_API_SECRET'];

$userEmail = 'raul1.pellizzer1@gmail.com';  // ← UPDATE THIS
$userPassword = '11111111';             // ← UPDATE THIS

echo "API URL: {$apiUrl}\n";
echo "API Key: {$apiKey} (INVALID)\n\n";

// Build request
$method = 'GET';
$body = '';
$timestamp = time();

$parsedUrl = parse_url($apiUrl);
$uri = $parsedUrl['path'];

if (isset($parsedUrl['query']) && $parsedUrl['query'] !== '') {
    $uri .= '?' . $parsedUrl['query'];
}

$message = $method . '|' . $uri . '|' . $body . '|' . $timestamp;
$signature = hash_hmac('sha256', $message, $apiSecret);

echo "Sending request with invalid API key...\n\n";

// Make request
$ch = curl_init($apiUrl);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-Key: ' . $apiKey,
    'X-Signature: ' . $signature,
    'X-Timestamp: ' . $timestamp,
]);

curl_setopt($ch, CURLOPT_USERPWD, $userEmail . ':' . $userPassword);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "============================================\n";
echo "RESPONSE\n";
echo "============================================\n\n";

echo "HTTP Status: {$httpCode}\n\n";

if ($httpCode === 403) {
    echo "✅ TEST PASSED! Invalid API key correctly rejected!\n\n";
} else {
    echo "❌ TEST FAILED! Expected 403, got {$httpCode}\n\n";
}

echo "Response:\n";
$decoded = json_decode($response, true);
if ($decoded) {
    echo json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
} else {
    echo $response . "\n";
}
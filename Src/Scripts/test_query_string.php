<?php
/**
 * Test Request with Query String
 * Tests signature with URL parameters
 */

$rootDir = dirname(dirname(__DIR__));
require $rootDir . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable($rootDir);
$dotenv->load();

echo "============================================\n";
echo "Testing Request with Query String\n";
echo "============================================\n\n";

// Configuration - URL with query parameter
$apiUrl = 'https://raulpellizzer.com/Dev/CommerceApi/products?stockcontrol=1';
$apiKey = $_ENV['WPF_APP_API_KEY'];
$apiSecret = $_ENV['WPF_APP_API_SECRET'];

$userEmail = 'raul1.pellizzer1@gmail.com';  // ← UPDATE THIS
$userPassword = '11111111';             // ← UPDATE THIS

echo "API URL: {$apiUrl}\n\n";

// Build request
$method = 'GET';
$body = '';
$timestamp = time();

$parsedUrl = parse_url($apiUrl);
$uri = $parsedUrl['path'];

// This should include the query string
if (isset($parsedUrl['query']) && $parsedUrl['query'] !== '') {
    $uri .= '?' . $parsedUrl['query'];
}

echo "Request Details:\n";
echo "  Method: {$method}\n";
echo "  URI: {$uri}\n";
echo "  Query String: " . ($parsedUrl['query'] ?? 'none') . "\n";
echo "  Timestamp: {$timestamp}\n\n";

$message = $method . '|' . $uri . '|' . $body . '|' . $timestamp;
$signature = hash_hmac('sha256', $message, $apiSecret);

echo "Message: \"{$message}\"\n";
echo "Signature: {$signature}\n\n";

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

echo "Sending request...\n\n";
$response = curl_exec($ch);

if ($response === false) {
    echo "❌ cURL Error: " . curl_error($ch) . "\n";
    curl_close($ch);
    exit(1);
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "============================================\n";
echo "RESPONSE\n";
echo "============================================\n\n";

echo "HTTP Status: {$httpCode}\n\n";

if ($httpCode === 200) {
    echo "✅ SUCCESS! Query string handled correctly!\n\n";
    $decoded = json_decode($response, true);
    if ($decoded) {
        echo "Response:\n";
        echo json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "❌ FAILED! Expected 200, got {$httpCode}\n\n";
    echo "Response:\n";
    echo $response . "\n";
}
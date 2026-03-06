<?php
/**
 * Test Client Authentication - Valid Request
 */

$rootDir = dirname(dirname(__DIR__));
require $rootDir . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable($rootDir);
$dotenv->load();

echo "============================================\n";
echo "Testing Client Authentication - VALID Request\n";
echo "============================================\n\n";

// Configuration
$apiUrl = 'https://raulpellizzer.com/Dev/CommerceApi/products';
$apiKey = $_ENV['WPF_APP_API_KEY'];
$apiSecret = $_ENV['WPF_APP_API_SECRET'];

// User credentials
$userEmail = 'raul1.pellizzer1@gmail.com';  // ← UPDATE THIS
$userPassword = '11111111';             // ← UPDATE THIS

echo "API URL: {$apiUrl}\n";
echo "API Key: {$apiKey}\n";
echo "User: {$userEmail}\n\n";

// Build request components
$method = 'GET';
$body = '';
$timestamp = time();

// Extract URI exactly as server sees it
$parsedUrl = parse_url($apiUrl);
$uri = $parsedUrl['path'];

if (isset($parsedUrl['query']) && $parsedUrl['query'] !== '') {
    $uri .= '?' . $parsedUrl['query'];
}

echo "Request Details:\n";
echo "  Method: {$method}\n";
echo "  URI: {$uri}\n";
echo "  Body: (empty)\n";
echo "  Timestamp: {$timestamp}\n\n";

// Generate HMAC signature
$message = $method . '|' . $uri . '|' . $body . '|' . $timestamp;

echo "Message to Sign:\n";
echo "  \"{$message}\"\n\n";

$signature = hash_hmac('sha256', $message, $apiSecret);

echo "Generated Signature:\n";
echo "  {$signature}\n\n";

// Make request
$ch = curl_init($apiUrl);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-Key: ' . $apiKey,
    'X-Signature: ' . $signature,
    'X-Timestamp: ' . $timestamp,
]);

curl_setopt($ch, CURLOPT_USERPWD, $userEmail . ':' . $userPassword);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

echo "Sending request...\n\n";
$response = curl_exec($ch);

if ($response === false) {
    echo "❌ cURL Error: " . curl_error($ch) . "\n";
    curl_close($ch);
    exit(1);
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

echo "============================================\n";
echo "RESPONSE\n";
echo "============================================\n\n";

echo "HTTP Status: {$httpCode}\n\n";

if ($httpCode === 200) {
    echo "✅ SUCCESS! Client authentication passed!\n\n";
    echo "Response Body:\n";
    $decoded = json_decode($body, true);
    if ($decoded) {
        echo json_encode($decoded, JSON_PRETTY_PRINT) . "\n\n";
    } else {
        echo $body . "\n\n";
    }
} else {
    echo "❌ FAILED! Expected 200, got {$httpCode}\n\n";
    echo "Response Body:\n";
    echo $body . "\n\n";
    echo "Response Headers:\n";
    echo $headers . "\n\n";
}
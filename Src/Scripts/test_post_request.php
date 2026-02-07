<?php
/**
 * Test POST Request with Body
 * Tests signature with request body
 */

$rootDir = dirname(dirname(__DIR__));
require $rootDir . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable($rootDir);
$dotenv->load();

echo "============================================\n";
echo "Testing POST Request with Body\n";
echo "============================================\n\n";

// Configuration
$apiUrl = 'https://raulpellizzer.com/Dev/CommerceApi/clients';
$apiKey = $_ENV['WPF_APP_API_KEY'];
$apiSecret = $_ENV['WPF_APP_API_SECRET'];

$userEmail = 'raul1.pellizzer1@gmail.com';  // ← UPDATE THIS
$userPassword = '11111111';             // ← UPDATE THIS

// Request body (JSON)
$requestData = [
    'Clients' => [
        [
            'Name' => 'Test Client',
            'Address' => '123 Test St',
            'PhoneNumber' => '555-1234',
            'Neighborhood' => 'Test Area',
            'Extras' => 'Test notes from API test script'
        ]
    ]
];

$body = json_encode($requestData);

echo "API URL: {$apiUrl}\n";
echo "Request Body:\n";
echo json_encode($requestData, JSON_PRETTY_PRINT) . "\n\n";

// Build signature
$method = 'POST';
$timestamp = time();

$parsedUrl = parse_url($apiUrl);
$uri = $parsedUrl['path'];

if (isset($parsedUrl['query']) && $parsedUrl['query'] !== '') {
    $uri .= '?' . $parsedUrl['query'];
}

// IMPORTANT: Signature includes the body!
$message = $method . '|' . $uri . '|' . $body . '|' . $timestamp;
$signature = hash_hmac('sha256', $message, $apiSecret);

echo "Signature Details:\n";
echo "  Method: {$method}\n";
echo "  URI: {$uri}\n";
echo "  Body Length: " . strlen($body) . " bytes\n";
echo "  Timestamp: {$timestamp}\n";
echo "  Signature: {$signature}\n\n";

// Make request
$ch = curl_init($apiUrl);

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-Key: ' . $apiKey,
    'X-Signature: ' . $signature,
    'X-Timestamp: ' . $timestamp,
]);

curl_setopt($ch, CURLOPT_USERPWD, $userEmail . ':' . $userPassword);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

echo "Sending POST request...\n\n";
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

if ($httpCode === 201 || $httpCode === 200) {
    echo "✅ SUCCESS! POST request with body authenticated!\n\n";
} else {
    echo "❌ FAILED! Expected 201 or 200, got {$httpCode}\n\n";
}

echo "Response:\n";
$decoded = json_decode($response, true);
if ($decoded) {
    echo json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
} else {
    echo $response . "\n";
}
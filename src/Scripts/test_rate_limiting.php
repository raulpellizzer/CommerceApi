<?php
/**
 * Test Rate Limiting
 * Makes many requests to test rate limiting
 */

$rootDir = dirname(dirname(__DIR__));
require $rootDir . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable($rootDir);
$dotenv->load();

echo "============================================\n";
echo "Testing Rate Limiting\n";
echo "============================================\n\n";

// Configuration
$apiUrl = 'https://raulpellizzer.com/Dev/CommerceApi/products';
$apiKey = $_ENV['WPF_APP_API_KEY'];
$apiSecret = $_ENV['WPF_APP_API_SECRET'];

$userEmail = 'raul1.pellizzer1@gmail.com';  // ← UPDATE THIS
$userPassword = '11111111';             // ← UPDATE THIS

$requestCount = 65; // Exceeds 60/min limit

echo "Sending {$requestCount} requests (limit is 60/min)...\n\n";

$successCount = 0;
$rateLimitedCount = 0;
$otherErrors = 0;

// Parse URL once
$parsedUrl = parse_url($apiUrl);
$uriBase = $parsedUrl['path'];

for ($i = 1; $i <= $requestCount; $i++) {
    // Build request
    $method = 'GET';
    $body = '';
    $timestamp = time();
    
    $uri = $uriBase;
    if (isset($parsedUrl['query']) && $parsedUrl['query'] !== '') {
        $uri .= '?' . $parsedUrl['query'];
    }

    $message = $method . '|' . $uri . '|' . $body . '|' . $timestamp;
    $signature = hash_hmac('sha256', $message, $apiSecret);

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

    if ($httpCode === 200) {
        $successCount++;
        echo "Request {$i}: ✅ 200 OK\n";
    } elseif ($httpCode === 429) {
        $rateLimitedCount++;
        echo "Request {$i}: ⚠️  429 Rate Limited\n";
    } else {
        $otherErrors++;
        echo "Request {$i}: ❌ {$httpCode} Error\n";
    }

    // Small delay to avoid overwhelming server
    usleep(10000); // 10ms
}

echo "\n============================================\n";
echo "RESULTS\n";
echo "============================================\n\n";

echo "Total Requests: {$requestCount}\n";
echo "Successful (200): {$successCount}\n";
echo "Rate Limited (429): {$rateLimitedCount}\n";
echo "Other Errors: {$otherErrors}\n\n";

if ($rateLimitedCount > 0) {
    echo "✅ TEST PASSED! Rate limiting is working!\n";
    echo "   First ~60 requests succeeded, rest were rate limited.\n";
} else {
    echo "❌ TEST FAILED! No rate limiting detected\n";
    echo "   All {$successCount} requests succeeded (should have hit limit)\n";
}
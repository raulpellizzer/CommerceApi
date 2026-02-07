<?php
/**
 * Debug Client Authentication
 * 
 * Shows detailed information about the request and what might be failing
 */

$rootDir = dirname(dirname(__DIR__));
require $rootDir . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable($rootDir);
$dotenv->load();

echo "============================================\n";
echo "Client Authentication Debug\n";
echo "============================================\n\n";

// 1. Check environment variables
echo "STEP 1: Environment Variables\n";
echo "---------------------------------------------\n";
echo "WPF_APP_API_KEY: " . ($_ENV['WPF_APP_API_KEY'] ?? '❌ NOT SET') . "\n";
echo "WPF_APP_API_SECRET: " . (isset($_ENV['WPF_APP_API_SECRET']) ? '✅ SET (' . strlen($_ENV['WPF_APP_API_SECRET']) . ' chars)' : '❌ NOT SET') . "\n";
echo "RATE_LIMIT_ENABLED: " . ($_ENV['RATE_LIMIT_ENABLED'] ?? 'not set (defaults to true)') . "\n";
echo "REQUEST_AUDIT_ENABLED: " . ($_ENV['REQUEST_AUDIT_ENABLED'] ?? 'not set (defaults to true)') . "\n\n";

if (!isset($_ENV['WPF_APP_API_KEY']) || !isset($_ENV['WPF_APP_API_SECRET'])) {
    echo "❌ ERROR: API credentials not found in .env file!\n";
    echo "Please add them to your .env file.\n\n";
    exit(1);
}

// 2. Check database connection and api_clients table
echo "STEP 2: Database Check\n";
echo "---------------------------------------------\n";

try {
    require_once $rootDir . '/Src/System/DatabaseConnector.php';
    $dbConnector = new Src\System\DatabaseConnector();
    $db = $dbConnector->getConnection(null);
    
    echo "✅ Database connection: OK\n";
    
    // Check if api_clients table exists
    $stmt = $db->query("SHOW TABLES LIKE 'api_clients'");
    if ($stmt->rowCount() > 0) {
        echo "✅ api_clients table: EXISTS\n";
        
        // Check if our API key exists
        $stmt = $db->prepare("SELECT * FROM api_clients WHERE api_key = :key");
        $stmt->execute(['key' => $_ENV['WPF_APP_API_KEY']]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($client) {
            echo "✅ API Key found in database\n";
            echo "   - Client ID: {$client['id']}\n";
            echo "   - Client Name: {$client['client_name']}\n";
            echo "   - Is Active: " . ($client['is_active'] ? '✅ YES' : '❌ NO') . "\n";
            echo "   - Is Locked: " . ($client['is_locked'] ? '❌ YES' : '✅ NO') . "\n";
            echo "   - Rate Limit (min): {$client['rate_limit_per_minute']}\n";
            echo "   - Rate Limit (hour): {$client['rate_limit_per_hour']}\n";
        } else {
            echo "❌ API Key NOT found in database!\n";
            echo "   API Key: {$_ENV['WPF_APP_API_KEY']}\n\n";
            echo "Run: php generate_api_credentials.php\n\n";
            exit(1);
        }
    } else {
        echo "❌ api_clients table: NOT FOUND\n";
        echo "Run the migration: mysql < migrations/001_create_api_clients.sql\n\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n\n";
    exit(1);
}

echo "\n";

// 3. Generate a test signature
echo "STEP 3: Signature Generation\n";
echo "---------------------------------------------\n";

$apiUrl = 'https://raulpellizzer.com/Dev/CommerceApi/products';
$method = 'GET';
$uri = parse_url($apiUrl, PHP_URL_PATH);
if (parse_url($apiUrl, PHP_URL_QUERY)) {
    $uri .= '?' . parse_url($apiUrl, PHP_URL_QUERY);
}
$body = '';
$timestamp = time();

echo "Method: {$method}\n";
echo "URI: {$uri}\n";
echo "Body: (empty)\n";
echo "Timestamp: {$timestamp}\n\n";

$message = $method . '|' . $uri . '|' . $body . '|' . $timestamp;
echo "Message to sign:\n";
echo "  \"{$message}\"\n\n";

$signature = hash_hmac('sha256', $message, $_ENV['WPF_APP_API_SECRET']);
echo "Generated Signature:\n";
echo "  {$signature}\n\n";

// 4. Check what headers will be sent
echo "STEP 4: Request Headers\n";
echo "---------------------------------------------\n";
echo "X-API-Key: {$_ENV['WPF_APP_API_KEY']}\n";
echo "X-Signature: {$signature}\n";
echo "X-Timestamp: {$timestamp}\n\n";

// 5. Check middleware files exist
echo "STEP 5: Middleware Files\n";
echo "---------------------------------------------\n";

$files = [
    'ClientAuthMiddleware' => $rootDir . '/Src/Middleware/ClientAuthMiddleware.php',
    'RateLimiter' => $rootDir . '/Src/System/RateLimiter.php',
    'ApiClientModel' => $rootDir . '/Src/Model/ApiClientModel.php',
];

foreach ($files as $name => $path) {
    if (file_exists($path)) {
        echo "✅ {$name}: EXISTS\n";
    } else {
        echo "❌ {$name}: NOT FOUND\n";
        echo "   Expected at: {$path}\n";
    }
}

echo "\n";

// 6. Check ApiController integration
echo "STEP 6: ApiController Integration\n";
echo "---------------------------------------------\n";

$apiControllerPath = $rootDir . '/Src/Controller/ApiController.php';
if (file_exists($apiControllerPath)) {
    $content = file_get_contents($apiControllerPath);
    
    if (strpos($content, 'ClientAuthMiddleware') !== false) {
        echo "✅ ClientAuthMiddleware imported\n";
    } else {
        echo "❌ ClientAuthMiddleware NOT imported\n";
    }
    
    if (strpos($content, 'new ClientAuthMiddleware') !== false) {
        echo "✅ ClientAuthMiddleware instantiated\n";
    } else {
        echo "❌ ClientAuthMiddleware NOT instantiated\n";
    }
    
    if (strpos($content, 'validateClient()') !== false) {
        echo "✅ validateClient() called\n";
    } else {
        echo "❌ validateClient() NOT called\n";
        echo "\nYou need to add this to ApiController.php:\n";
        echo "---\n";
        echo "\$client = \$this->clientAuth->validateClient();\n";
        echo "---\n";
    }
} else {
    echo "❌ ApiController.php not found\n";
}

echo "\n";

// 7. Test actual request
echo "STEP 7: Making Test Request\n";
echo "---------------------------------------------\n";

$testUrl = 'https://raulpellizzer.com/Dev/CommerceApi/products'; // Change if needed
$userEmail = 'raul1.pellizzer1@gmail.com'; // Change to your test user
$userPassword = '11111111'; // Change to your test password

echo "URL: {$testUrl}\n";
echo "User: {$userEmail}\n\n";

$ch = curl_init($testUrl);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-Key: ' . $_ENV['WPF_APP_API_KEY'],
    'X-Signature: ' . $signature,
    'X-Timestamp: ' . $timestamp,
]);

curl_setopt($ch, CURLOPT_USERPWD, $userEmail . ':' . $userPassword);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);

// Capture verbose output
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

echo "Sending request...\n\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

rewind($verbose);
$verboseLog = stream_get_contents($verbose);

curl_close($ch);

echo "HTTP Status: {$httpCode}\n\n";

if ($httpCode === 200) {
    echo "✅ SUCCESS!\n\n";
} else {
    echo "❌ FAILED\n\n";
}

echo "Response:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT) . "\n\n";

echo "cURL Verbose Log:\n";
echo $verboseLog . "\n";

echo "\n============================================\n";
echo "Check the api_request_log table for details:\n";
echo "SELECT * FROM api_request_log ORDER BY id DESC LIMIT 5;\n";
echo "============================================\n";
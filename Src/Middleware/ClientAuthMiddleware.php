<?php
namespace Src\Middleware;

use Src\Model\ApiClientModel;
use Src\System\RateLimiter;

/**
 * Client Authentication Middleware
 * 
 * Validates API client credentials using HMAC-SHA256 request signing
 */
class ClientAuthMiddleware
{
    private $dbConnection;
    private $apiClientModel;
    private $rateLimiter;
    private $auditEnabled;
    
    private const SIGNATURE_MAX_AGE = 300; // 5 minutes default
    private const REQUIRED_HEADERS = ['HTTP_X_API_KEY', 'HTTP_X_SIGNATURE', 'HTTP_X_TIMESTAMP'];
    
    public function __construct($dbConnection)
    {
        $this->dbConnection = $dbConnection;
        $this->apiClientModel = new ApiClientModel($dbConnection);
        $this->rateLimiter = new RateLimiter($dbConnection);
        $this->auditEnabled = $_ENV['REQUEST_AUDIT_ENABLED'] ?? true;
    }
    
    /**
     * Validate client authentication
     * 
     * @return array Client data if valid
     * @throws Exception on validation failure (exits script)
     */
    public function validateClient()
    {
        $startTime = microtime(true);
        
        try {
            // Extract headers
            $headers = $this->extractHeaders();
            
            // Validate headers exist
            $this->validateHeadersPresent($headers);
            
            // Validate timestamp (prevent replay attacks)
            $this->validateTimestamp($headers['timestamp']);
            
            // Get and validate API client
            $client = $this->validateApiKey($headers['apiKey']);
            
            // Check rate limits
            $this->checkRateLimits($client);
            
            // Validate IP restrictions (if any)
            $this->validateIpRestrictions($client);
            
            // Build and validate signature
            $this->validateSignature($headers, $client);
            
            // Update client usage stats
            $this->updateClientStats($client);
            
            // Audit log (async recommended in production)
            if ($this->auditEnabled) {
                $this->logRequest($client, true, $startTime);
            }
            
            return $client;
            
        } catch (\Exception $e) {
            if ($this->auditEnabled) {
                $this->logRequest(
                    isset($client) ? $client : ['api_key' => $headers['apiKey'] ?? 'unknown'],
                    false,
                    $startTime,
                    $e->getMessage()
                );
            }
            
            $this->respondUnauthorized('Client authentication failed');
        }
    }
    
    /**
     * Extract authentication headers
     */
    private function extractHeaders()
    {
        return [
            'apiKey' => $_SERVER['HTTP_X_API_KEY'] ?? null,
            'signature' => $_SERVER['HTTP_X_SIGNATURE'] ?? null,
            'timestamp' => $_SERVER['HTTP_X_TIMESTAMP'] ?? null,
            'deviceId' => $_SERVER['HTTP_X_DEVICE_ID'] ?? null,
            'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'ipAddress' => $this->getClientIp(),
        ];
    }
    
    /**
     * Validate required headers are present
     */
    private function validateHeadersPresent($headers)
    {
        foreach (self::REQUIRED_HEADERS as $headerKey) {
            $key = strtolower(str_replace('HTTP_X_', '', $headerKey));
            $key = str_replace('_', '', ucwords($key, '_'));
            $key = lcfirst($key);
            
            if (empty($headers[$key])) {
                error_log('Missing header: ' . $headerKey);
                throw new \Exception('Missing required authentication header');
            }
        }
    }
    
    /**
     * Validate timestamp is within acceptable range
     */
    private function validateTimestamp($timestamp)
    {
        // Validate format
        if (!is_numeric($timestamp)) {
            throw new \Exception('Invalid timestamp format');
        }
        
        $now = time();
        $maxAge = $_ENV['SIGNATURE_MAX_AGE_SECONDS'] ?? self::SIGNATURE_MAX_AGE;
        $age = abs($now - $timestamp);
        
        if ($age > $maxAge) {
            error_log("Request timestamp too old: {$age}s (max: {$maxAge}s)");
            throw new \Exception('Request expired - timestamp outside valid window');
        }
        
        // Detect clock skew (future timestamps)
        if ($timestamp > $now + 30) {
            error_log("Request timestamp in future: " . ($timestamp - $now) . "s ahead");
            throw new \Exception('Invalid timestamp - check system clock');
        }
    }
    
    /**
     * Validate API key and retrieve client
     */
    private function validateApiKey($apiKey)
    {
        if (empty($apiKey)) {
            throw new \Exception('API key required');
        }
        
        $client = $this->apiClientModel->getClientByApiKey($apiKey);
        
        if (!$client) {
            error_log('Invalid API key attempted: ' . substr($apiKey, 0, 8) . '...');
            throw new \Exception('Invalid API key');
        }
        
        // Check if active
        if (!$client['is_active']) {
            error_log('Inactive API key attempted: ' . $apiKey);
            throw new \Exception('API client is disabled');
        }
        
        // Check if locked
        if ($client['is_locked']) {
            error_log('Locked API key attempted: ' . $apiKey . ' - Reason: ' . $client['locked_reason']);
            throw new \Exception('API client is locked');
        }
        
        // Check expiration
        if ($client['expires_at'] && strtotime($client['expires_at']) < time()) {
            error_log('Expired API key attempted: ' . $apiKey);
            throw new \Exception('API client credentials expired');
        }
        
        return $client;
    }
    
    /**
     * Check rate limits
     */
    private function checkRateLimits($client)
    {
        if (!($_ENV['RATE_LIMIT_ENABLED'] ?? true)) {
            return; // Rate limiting disabled
        }
        
        $apiKey = $client['api_key'];
        
        // Check per-minute limit
        if (!$this->rateLimiter->checkLimit(
            'apikey:' . $apiKey . ':minute',
            $client['rate_limit_per_minute'],
            60
        )) {
            error_log("Rate limit exceeded (minute) for API key: {$apiKey}");
            $this->respondRateLimited('Rate limit exceeded - too many requests per minute');
        }
        
        // Check per-hour limit
        if (!$this->rateLimiter->checkLimit(
            'apikey:' . $apiKey . ':hour',
            $client['rate_limit_per_hour'],
            3600
        )) {
            error_log("Rate limit exceeded (hour) for API key: {$apiKey}");
            $this->respondRateLimited('Rate limit exceeded - too many requests per hour');
        }
    }
    
    /**
     * Validate IP restrictions
     */
    private function validateIpRestrictions($client)
    {
        if (empty($client['allowed_ips'])) {
            return; // No IP restrictions
        }
        
        $allowedIps = json_decode($client['allowed_ips'], true);
        if (!$allowedIps || !is_array($allowedIps)) {
            return;
        }
        
        $clientIp = $this->getClientIp();
        
        // Check if client IP is in allowed list
        if (!in_array($clientIp, $allowedIps)) {
            // Check for CIDR ranges
            $allowed = false;
            foreach ($allowedIps as $allowedIp) {
                if ($this->ipInRange($clientIp, $allowedIp)) {
                    $allowed = true;
                    break;
                }
            }
            
            if (!$allowed) {
                error_log("IP restriction violation: {$clientIp} not in allowed list for API key: {$client['api_key']}");
                throw new \Exception('Access denied from this IP address');
            }
        }
    }
    
    /**
     * Validate HMAC signature
     */
    private function validateSignature($headers, $client)
    {
        // Get request details
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];
        $body = file_get_contents('php://input');
        
        // Build canonical message (same format as client)
        // Format: METHOD|URI|BODY|TIMESTAMP
        $message = $method . '|' . $uri . '|' . $body . '|' . $headers['timestamp'];
        
        // Get API secret from environment (for production)
        // In multi-client scenarios, decrypt from database
        $apiSecret = $this->getApiSecret($client);
        
        // Generate expected signature
        $expectedSignature = hash_hmac('sha256', $message, $apiSecret);
        
        // Constant-time comparison (prevent timing attacks)
        if (!hash_equals($expectedSignature, $headers['signature'])) {
            error_log('Invalid signature for API key: ' . $client['api_key']);
            
            // Update failed request counter
            $this->apiClientModel->incrementFailedRequests($client['id']);
            
            throw new \Exception('Invalid request signature');
        }
    }
    
    /**
     * Get API secret for client
     * In production: decrypt from secure storage or use environment variable
     */
    private function getApiSecret($client)
    {
        // Option 1: Single app (WPF) - use environment variable
        if ($client['client_type'] === 'wpf_desktop') {
            return $_ENV['WPF_APP_API_SECRET'];
        }
        
        // Option 2: Multiple clients - decrypt from database
        // You would store encrypted secrets and decrypt here
        // For now, fall back to environment
        return $_ENV['WPF_APP_API_SECRET'];
    }
    
    /**
     * Update client usage statistics
     */
    private function updateClientStats($client)
    {
        $this->apiClientModel->updateClientUsage($client['id']);
    }
    
    /**
     * Log API request for audit trail
     */
    private function logRequest($client, $success, $startTime, $errorMessage = null)
    {
        $responseTime = (microtime(true) - $startTime) * 1000; // ms
        
        $this->apiClientModel->logApiRequest([
            'api_client_id' => $client['id'] ?? null,
            'api_key' => $client['api_key'] ?? 'unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'request_uri' => $_SERVER['REQUEST_URI'],
            'request_ip' => $this->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'authenticated_user' => $this->getAuthenticatedUser(),
            'http_status_code' => $success ? 200 : 403,
            'response_time_ms' => round($responseTime),
            'signature_valid' => $success,
            'timestamp_valid' => $success,
            'error_message' => $errorMessage
        ]);
    }
    
    /**
     * Get authenticated user from Basic Auth headers
     */
    private function getAuthenticatedUser()
    {
        return $_SERVER['PHP_AUTH_USER'] ?? null;
    }
    
    /**
     * Get real client IP address (handles proxies)
     */
    private function getClientIp()
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',    // Cloudflare
            'HTTP_X_FORWARDED_FOR',     // Standard proxy
            'HTTP_X_REAL_IP',           // Nginx
            'REMOTE_ADDR'               // Direct connection
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Handle comma-separated IPs (X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0'; // Fallback
    }
    
    /**
     * Check if IP is in CIDR range
     */
    private function ipInRange($ip, $range)
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }
        
        list($subnet, $mask) = explode('/', $range);
        
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - $mask);
        
        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
    
    /**
     * Respond with 403 Forbidden
     */
    private function respondUnauthorized($message)
    {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => '403',
            'message' => $message,
            'error_code' => 'CLIENT_AUTH_FAILED'
        ]);
        exit;
    }
    
    /**
     * Respond with 429 Too Many Requests
     */
    private function respondRateLimited($message)
    {
        http_response_code(429);
        header('Content-Type: application/json');
        header('Retry-After: 60');
        echo json_encode([
            'status' => '429',
            'message' => $message,
            'error_code' => 'RATE_LIMIT_EXCEEDED',
            'retry_after_seconds' => 60
        ]);
        exit;
    }
}
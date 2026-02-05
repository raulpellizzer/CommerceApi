<?php
namespace Src\System;

/**
 * Rate Limiter
 * 
 * Rate limiting with multiple storage backends
 * Supports: Database, Redis, APCu
 */
class RateLimiter
{
    private $dbConnection;
    private $storage;
    private $redisConnection;
    
    const STORAGE_DATABASE = 'database';
    const STORAGE_REDIS = 'redis';
    const STORAGE_APCU = 'apcu';
    
    public function __construct($dbConnection)
    {
        $this->dbConnection = $dbConnection;
        $this->storage = $_ENV['RATE_LIMIT_STORAGE'] ?? self::STORAGE_DATABASE;
        
        // Initialize Redis if configured
        if ($this->storage === self::STORAGE_REDIS) {
            $this->initializeRedis();
        }
    }
    
    /**
     * Check if action is allowed under rate limit
     * 
     * @param string $key Unique identifier for rate limit
     * @param int $limit Maximum requests allowed
     * @param int $windowSeconds Time window in seconds
     * @return bool True if allowed, false if limit exceeded
     */
    public function checkLimit($key, $limit, $windowSeconds)
    {
        switch ($this->storage) {
            case self::STORAGE_REDIS:
                return $this->checkLimitRedis($key, $limit, $windowSeconds);
                
            case self::STORAGE_APCU:
                return $this->checkLimitApcu($key, $limit, $windowSeconds);
                
            case self::STORAGE_DATABASE:
            default:
                return $this->checkLimitDatabase($key, $limit, $windowSeconds);
        }
    }
    
    /**
     * Rate limiting using Redis (RECOMMENDED for production)
     */
    private function checkLimitRedis($key, $limit, $windowSeconds)
    {
        if (!$this->redisConnection) {
            error_log('Redis not available, falling back to database');
            return $this->checkLimitDatabase($key, $limit, $windowSeconds);
        }
        
        try {
            $currentWindow = floor(time() / $windowSeconds);
            $redisKey = "ratelimit:{$key}:{$currentWindow}";
            
            // Increment counter
            $count = $this->redisConnection->incr($redisKey);
            
            // Set expiration on first request
            if ($count === 1) {
                $this->redisConnection->expire($redisKey, $windowSeconds * 2);
            }
            
            return $count <= $limit;
            
        } catch (\Exception $e) {
            error_log('Redis error: ' . $e->getMessage());
            return $this->checkLimitDatabase($key, $limit, $windowSeconds);
        }
    }
    
    /**
     * Rate limiting using APCu
     */
    private function checkLimitApcu($key, $limit, $windowSeconds)
    {
        if (!function_exists('apcu_inc')) {
            error_log('APCu not available, falling back to database');
            return $this->checkLimitDatabase($key, $limit, $windowSeconds);
        }
        
        try {
            $currentWindow = floor(time() / $windowSeconds);
            $cacheKey = "ratelimit_{$key}_{$currentWindow}";
            
            $count = apcu_inc($cacheKey, 1, $success);
            
            if (!$success) {
                apcu_store($cacheKey, 1, $windowSeconds * 2);
                $count = 1;
            }
            
            return $count <= $limit;
            
        } catch (\Exception $e) {
            error_log('APCu error: ' . $e->getMessage());
            return $this->checkLimitDatabase($key, $limit, $windowSeconds);
        }
    }
    
    /**
     * Rate limiting using database (fallback)
     */
    private function checkLimitDatabase($key, $limit, $windowSeconds)
    {
        try {
            $currentWindow = floor(time() / $windowSeconds);
            $trackingKey = $key . ':' . $currentWindow;
            $windowStart = date('Y-m-d H:i:s', $currentWindow * $windowSeconds);
            $expiresAt = date('Y-m-d H:i:s', ($currentWindow + 2) * $windowSeconds);
            
            // Insert or increment
            $stmt = $this->dbConnection->prepare('
                INSERT INTO rate_limit_tracking (
                    tracking_key,
                    request_count,
                    window_start,
                    expires_at
                ) VALUES (
                    :tracking_key,
                    1,
                    :window_start,
                    :expires_at
                )
                ON DUPLICATE KEY UPDATE
                    request_count = request_count + 1
            ');
            
            $stmt->execute([
                'tracking_key' => $trackingKey,
                'window_start' => $windowStart,
                'expires_at' => $expiresAt
            ]);
            
            // Get current count
            $stmt = $this->dbConnection->prepare('
                SELECT request_count
                FROM rate_limit_tracking
                WHERE tracking_key = :tracking_key
            ');
            
            $stmt->execute(['tracking_key' => $trackingKey]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            $count = $row ? $row['request_count'] : 1;
            
            return $count <= $limit;
            
        } catch (\Exception $e) {
            error_log('Database rate limit error: ' . $e->getMessage());
            return true; // Fail open
        }
    }
    
    /**
     * Initialize Redis connection
     */
    private function initializeRedis()
    {
        if (!class_exists('Redis')) {
            error_log('Redis extension not installed');
            $this->storage = self::STORAGE_DATABASE;
            return;
        }
        
        try {
            $this->redisConnection = new \Redis();
            
            $host = $_ENV['REDIS_HOST'] ?? '127.0.0.1';
            $port = $_ENV['REDIS_PORT'] ?? 6379;
            $password = $_ENV['REDIS_PASSWORD'] ?? null;
            
            $this->redisConnection->connect($host, $port, 2.5);
            
            if ($password) {
                $this->redisConnection->auth($password);
            }
            
            $this->redisConnection->ping();
            
        } catch (\Exception $e) {
            error_log('Failed to connect to Redis: ' . $e->getMessage());
            $this->redisConnection = null;
            $this->storage = self::STORAGE_DATABASE;
        }
    }
    
    /**
     * Clean up expired rate limit data
     */
    public function cleanup()
    {
        if ($this->storage !== self::STORAGE_DATABASE) {
            return;
        }
        
        try {
            $stmt = $this->dbConnection->prepare('
                DELETE FROM rate_limit_tracking
                WHERE expires_at < NOW()
            ');
            $stmt->execute();
        } catch (\Exception $e) {
            error_log('Error cleaning up rate limits: ' . $e->getMessage());
        }
    }
}
<?php
namespace Src\Model;

/**
 * API Client Model
 * 
 * Manages API client credentials, devices, and audit logging
 */
class ApiClientModel
{
    private $dbConnection;
    
    public function __construct($dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }
    
    /**
     * Get client by API key
     */
    public function getClientByApiKey($apiKey)
    {
        try {
            $stmt = $this->dbConnection->prepare('
                SELECT 
                    id,
                    api_key,
                    client_name,
                    client_version,
                    client_type,
                    is_active,
                    is_locked,
                    locked_reason,
                    rate_limit_per_minute,
                    rate_limit_per_hour,
                    allowed_ips,
                    expires_at
                FROM api_clients
                WHERE api_key = :api_key
                LIMIT 1
            ');
            
            $stmt->execute(['api_key' => $apiKey]);
            $client = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return $client ?: null;
            
        } catch (\Exception $e) {
            error_log('Error fetching API client: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update client usage statistics
     */
    public function updateClientUsage($clientId)
    {
        try {
            $stmt = $this->dbConnection->prepare('
                UPDATE api_clients
                SET 
                    total_requests = total_requests + 1,
                    last_used_at = NOW()
                WHERE id = :id
            ');
            
            $stmt->execute(['id' => $clientId]);
            
        } catch (\Exception $e) {
            error_log('Error updating client usage: ' . $e->getMessage());
        }
    }
    
    /**
     * Increment failed request counter
     */
    public function incrementFailedRequests($clientId)
    {
        try {
            $stmt = $this->dbConnection->prepare('
                UPDATE api_clients
                SET 
                    failed_requests = failed_requests + 1,
                    last_error_at = NOW()
                WHERE id = :id
            ');
            
            $stmt->execute(['id' => $clientId]);
            
        } catch (\Exception $e) {
            error_log('Error updating failed requests: ' . $e->getMessage());
        }
    }
    
    /**
     * Log API request for audit trail
     */
    public function logApiRequest($data)
    {
        try {
            $stmt = $this->dbConnection->prepare('
                INSERT INTO api_request_log (
                    api_client_id,
                    api_key,
                    request_method,
                    request_uri,
                    request_ip,
                    user_agent,
                    authenticated_user,
                    http_status_code,
                    response_time_ms,
                    signature_valid,
                    timestamp_valid
                ) VALUES (
                    :api_client_id,
                    :api_key,
                    :request_method,
                    :request_uri,
                    :request_ip,
                    :user_agent,
                    :authenticated_user,
                    :http_status_code,
                    :response_time_ms,
                    :signature_valid,
                    :timestamp_valid
                )
            ');
            
            $stmt->execute([
                'api_client_id' => $data['api_client_id'],
                'api_key' => $data['api_key'],
                'request_method' => $data['request_method'],
                'request_uri' => substr($data['request_uri'], 0, 500),
                'request_ip' => $data['request_ip'],
                'user_agent' => $data['user_agent'],
                'authenticated_user' => $data['authenticated_user'],
                'http_status_code' => $data['http_status_code'],
                'response_time_ms' => $data['response_time_ms'],
                'signature_valid' => $data['signature_valid'] ? 1 : 0,
                'timestamp_valid' => $data['timestamp_valid'] ? 1 : 0
            ]);
            
        } catch (\Exception $e) {
            // Don't fail request if logging fails, just log error
            error_log('Error logging API request: ' . $e->getMessage());
        }
    }
    
    /**
     * Create new API client
     */
    public function createClient($data)
    {
        try {
            $stmt = $this->dbConnection->prepare('
                INSERT INTO api_clients (
                    api_key,
                    api_secret_hash,
                    client_name,
                    client_version,
                    client_type,
                    rate_limit_per_minute,
                    rate_limit_per_hour,
                    notes
                ) VALUES (
                    :api_key,
                    :api_secret_hash,
                    :client_name,
                    :client_version,
                    :client_type,
                    :rate_limit_per_minute,
                    :rate_limit_per_hour,
                    :notes
                )
            ');
            
            $result = $stmt->execute([
                'api_key' => $data['api_key'],
                'api_secret_hash' => password_hash($data['api_secret'], PASSWORD_BCRYPT, ['cost' => 12]),
                'client_name' => $data['client_name'],
                'client_version' => $data['client_version'] ?? '1.0.0',
                'client_type' => $data['client_type'] ?? 'wpf_desktop',
                'rate_limit_per_minute' => $data['rate_limit_per_minute'] ?? 60,
                'rate_limit_per_hour' => $data['rate_limit_per_hour'] ?? 1000,
                'notes' => $data['notes'] ?? null
            ]);
            
            return $result ? $this->dbConnection->lastInsertId() : null;
            
        } catch (\Exception $e) {
            error_log('Error creating API client: ' . $e->getMessage());
            return null;
        }
    }
}
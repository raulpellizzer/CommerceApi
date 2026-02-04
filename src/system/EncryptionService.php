<?php
namespace Src\System;

/**
 * EncryptionService
 * 
 * Handles AES-256-GCM encryption/decryption for sensitive data
 * Uses a single master encryption key from environment variables
 */
class EncryptionService
{
    private $encryptionKey;
    
    /**
     * Constructor
     * Loads encryption key from environment variables
     * 
     * @throws \Exception if encryption key is invalid
     */
    public function __construct()
    {
        $this->encryptionKey = $_ENV['API_ENCRYPTION_KEY'] ?? '';
        
        if (empty($this->encryptionKey)) {
            throw new \Exception('API_ENCRYPTION_KEY not found in environment variables');
        }
        
        if (strlen($this->encryptionKey) !== 32) {
            throw new \Exception('API_ENCRYPTION_KEY must be exactly 32 bytes (256 bits). Current length: ' . strlen($this->encryptionKey));
        }
    }
    
    /**
     * Decrypt data that was encrypted with AES-256-GCM
     * 
     * @param string $encryptedData Base64 encoded encrypted data
     * @param string $iv Base64 encoded initialization vector (12 bytes)
     * @param string $tag Base64 encoded authentication tag (16 bytes)
     * @return string|null Decrypted data or null on failure
     */
    public function decrypt($encryptedData, $iv, $tag)
    {
        try {

            if (empty($encryptedData) || empty($iv) || empty($tag)) {
                error_log('Decryption error: Missing required parameters');
                return null;
            }
            
            $encryptedBinary = base64_decode($encryptedData, true);
            $ivBinary = base64_decode($iv, true);
            $tagBinary = base64_decode($tag, true);
            
            if ($encryptedBinary === false || $ivBinary === false || $tagBinary === false) {
                error_log('Decryption error: Invalid base64 encoding');
                return null;
            }
            
            if (strlen($ivBinary) !== 12) {
                error_log('Decryption error: Invalid IV length (expected 12 bytes, got ' . strlen($ivBinary) . ')');
                return null;
            }
            
            if (strlen($tagBinary) !== 16) {
                error_log('Decryption error: Invalid tag length (expected 16 bytes, got ' . strlen($tagBinary) . ')');
                return null;
            }
            
            $decrypted = openssl_decrypt(
                $encryptedBinary,
                'aes-256-gcm',
                $this->encryptionKey,
                OPENSSL_RAW_DATA,
                $ivBinary,
                $tagBinary
            );

            if ($decrypted === false) {
                error_log('Decryption error: Authentication failed - data may be corrupted or key is incorrect');
                return null;
            }
            
            return $decrypted;
            
        } catch (\Exception $e) {
            error_log('Decryption exception: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Encrypt data with AES-256-GCM
     * 
     * Returns authenticated encryption - the tag ensures data integrity
     * Each encryption uses a unique random IV for security
     * 
     * @param string $plaintext Data to encrypt
     * @return array|null ['encrypted' => string, 'iv' => string, 'tag' => string] (all base64 encoded) or null on failure
     */
    public function encrypt($plaintext)
    {
        try {

            if ($plaintext === null || $plaintext === '') {
                throw new \Exception('Cannot encrypt empty data');
            }

            $iv = random_bytes(12);

            $tag = '';
            
            $encrypted = openssl_encrypt(
                $plaintext,
                'aes-256-gcm',
                $this->encryptionKey,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($encrypted === false) {
                throw new \Exception('Encryption failed - openssl_encrypt returned false');
            }
            
            return [
                'encrypted' => base64_encode($encrypted),
                'iv' => base64_encode($iv),
                'tag' => base64_encode($tag)
            ];
            
        } catch (\Exception $e) {
            error_log('Encryption exception: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Convenience method: Encrypt and return as a single JSON string
     * Useful for storing encrypted data in a single database column
     * 
     * @param string $plaintext Data to encrypt
     * @return string|null JSON string containing encrypted data, IV, and tag, or null on failure
     */
    public function encryptToJson($plaintext)
    {
        $encrypted = $this->encrypt($plaintext);
        
        if ($encrypted === null) {
            return null;
        }
        
        return json_encode($encrypted);
    }
    
    /**
     * Convenience method: Decrypt from a JSON string
     * 
     * @param string $json JSON string from encryptToJson()
     * @return string|null Decrypted data or null on failure
     */
    public function decryptFromJson($json)
    {
        if (empty($json)) {
            error_log('Decryption error: Empty JSON input');
            return null;
        }
        
        $data = json_decode($json, true);
        
        if (!is_array($data) || !isset($data['encrypted']) || !isset($data['iv']) || !isset($data['tag'])) {
            error_log('Decryption error: Invalid JSON format - missing required fields');
            return null;
        }
        
        return $this->decrypt($data['encrypted'], $data['iv'], $data['tag']);
    }
}
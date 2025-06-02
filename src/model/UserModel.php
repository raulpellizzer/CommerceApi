<?php
namespace Src\Model;

class UserModel
{
    private $dbConnection;
    private $user;
    private $pass;
    private $tenantDbName;

    /**
     * UserModel constructor
     *
     * @param object $dbConnection Database connection object
     */
    public function __construct($dbConnection, $user, $pass)
    {
        $this->dbConnection = $dbConnection;
        $this->user = $user;
        $this->pass = $pass;
    }

    /**
     * Authenticate user
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool True if authenticated, false otherwise
     */
    public function authenticate()
    {
        try {
            // Hash email for lookup
            $emailHash = hash('sha256', strtolower(trim($this->user)));

            $query = "SELECT is_active, 
                tenant_name, 
                password, 
                is_verified, 
                email_hash, 
                email_encrypted, 
                email_iv, 
                email_tag 
                FROM user 
                WHERE email_hash = :emailHash";

            $statement = $this->dbConnection->prepare($query);
            $res = $statement->execute([
                'emailHash' => $emailHash
            ]);
            $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

            if ($statement->rowCount() > 0) {
                
                // TO-DO: CHECK IF rowCount() >= 2 (in the very unlikely case of multiple users with the same email hash (same password))
                // Loop through the rows decrypting the email and checking the password to find the correct user

                // Decrypt the user email
                $userEmailDecrypted = openssl_decrypt(
                    $rows[0]['email_encrypted'],
                    'aes-256-gcm',
                    $_ENV['ENCRYPTION_KEY'],
                    OPENSSL_RAW_DATA,
                    $rows[0]['email_iv'],
                    $rows[0]['email_tag']
                );

                if ($this->user === $userEmailDecrypted 
                    && password_verify($this->pass, $rows[0]['password']) 
                    && $rows[0]['is_active'] == 1
                    && $rows[0]['is_verified'] == 1) {
                        
                    $this->tenantDbName = $rows[0]['tenant_name'];
                    return true;

                } else {
                    return false;
                }

            } else
                return false;

        } catch (\Exception $e) {

            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get user database name to connect to client's database
     *
     * @return string User database name
     */
    public function getUserDatabase($authUser)
    {
        return $this->tenantDbName;
    }
}
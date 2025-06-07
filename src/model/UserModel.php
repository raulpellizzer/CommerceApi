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
            $userIsAuthenticated = false;

            // Hash email for lookup
            $emailHash = hash('sha256', strtolower(trim($this->user)));

            $query = "SELECT is_active, 
                tenant_database, 
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
                
                // To-do: Test for different user with same pass
                foreach ($rows as $row) {
                    // Decrypt the user email
                    $userEmailDecrypted = openssl_decrypt(
                        $row['email_encrypted'],
                        'aes-256-gcm',
                        $_ENV['ENCRYPTION_KEY'],
                        OPENSSL_RAW_DATA,
                        $row['email_iv'],
                        $row['email_tag']
                    );

                    if (strtolower(trim($this->user)) === $userEmailDecrypted 
                        && password_verify($this->pass, $row['password']) 
                        && $row['is_active'] == 1
                        && $row['is_verified'] == 1) {
                            
                        $this->tenantDbName = $row['tenant_database'];
                        $userIsAuthenticated = true;
                    }
                }
            }

            return $userIsAuthenticated;

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

    /**
     * Get tenant plan type
     *
     * @return string Tenant plan type
     */
    public function getTenantPlanType() 
    {
        try {
            $query = "SELECT plan_type
                FROM user 
                WHERE tenant_database = :tenant_database";

            $statement = $this->dbConnection->prepare($query);
            $res = $statement->execute([
                'tenant_database' => $this->tenantDbName
            ]);
            $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
            return $rows[0]['plan_type'] ?? 'free';

        } catch (\Exception $e) {
            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'Error fetching tenant plan type: ' . $e->getMessage()
            ]);
        }
    }
}
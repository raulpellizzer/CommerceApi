<?php
namespace Src\Model;

class UserModel
{
    private $dbConnection;
    private $user;
    private $pass;

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
            $query = "SELECT * FROM CommApiUsers WHERE Username = :username";
            $statement = $this->dbConnection->prepare($query);

            $res = $statement->execute([
                'username' => $this->user
            ]);

            $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

            if ($this->user == $rows[0]['Username'] 
                && password_verify($this->pass, $rows[0]['Pass']) 
                && rows[0]['IsActive'] == 1) {
                    
                return true;
            } else {
                return false;
            }

        } catch (\Exception $e) {

            $this->logger->logMessage([
                'currentDateTime' => date('Y-m-d H:i:s'),
                'file' => __CLASS__,
                'function' => __FUNCTION__,
                'message' => $e->getMessage(),
                'args' => null,
                'stackTrace' => print_r(debug_backtrace(), true),   
                'type' => 'Error',
                'category' => 'Auth'
            ]);

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
     * @return array User database name
     */
    public function getUserDatabase($authUser)
    {
        try {
            
            $query = "SELECT TenantDbName FROM CommApiUsers WHERE Username = :username";
            $statement = $this->dbConnection->prepare($query);

            $res = $statement->execute([
                'username' => $authUser
            ]);

            $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if ($statement->rowCount() > 0) {
                return $rows[0];
            } else {
                return null;
            }

        } catch (\Exception $e) {

            $this->logger->logMessage([
                'currentDateTime' => date('Y-m-d H:i:s'),
                'file' => __CLASS__,
                'function' => __FUNCTION__,
                'message' => $e->getMessage(),
                'args' => null,
                'stackTrace' => print_r(debug_backtrace(), true),   
                'type' => 'Error',
                'category' => 'Auth'
            ]);

            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }
}
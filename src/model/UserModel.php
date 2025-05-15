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
        $query = "SELECT * FROM ApiUsers WHERE Username = :username AND Pass = :password";
        $statement = $this->dbConnection->prepare($query);

        $res = $statement->execute([
            'username' => $this->user,
            'password' => $this->pass
        ]);

        if ($statement->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }
}
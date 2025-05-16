<?php
namespace Src\System;

class DatabaseConnector
{
    private $dbConnection = null;
    private $host;
    private $db;
    private $user;
    private $pass;

    public function __construct()
    {
        $this->host = $_ENV['DB_HOST'];
        $this->db   = $_ENV['DB_NAME'];
        $this->user = $_ENV['DB_USER'];
        $this->pass = $_ENV['DB_PASSWORD'];
    }

    private function connect($host, $db, $user, $pass)
    {
        try {
            $this->dbConnection = new \PDO(
                'mysql:host=' . $host . ';dbname=' . $db, $user, $pass, array(
                \PDO::ATTR_PERSISTENT => true));

            $this->dbConnection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();
        }
    }

    /**
     * Get the database connection
     *
     * Handles connection to the database between Api Auth DB and User DB.
     * CommerceApiUsers db is responsible for authentication and user database name.
     * $database is the database name to connect to the user's database.
     * First connection is to the auth database (CommerceApiUsers) to check user authentication. Once authenticated, the database name is retrieved for the given auth user.
     * Second connection ($this->dbConnection->getConnection(...)) ($database not null) is to the user's database (e.g. userModel->getUserDatabase($_SERVER['PHP_AUTH_USER'])) to perform operations.
     * 
     * @param string|null $database The database name to connect to
     * @return \PDO|null The database connection object
     */
    function getConnection($database)
    {
        if ($database === null) {

            if ($this->dbConnection === null) {
                $this->connect($this->host, $this->db, $this->user, $this->pass);
            }

        } else {
            if ($this->dbConnection === null) {
                $this->connect($this->host, $database, $this->user, $this->pass);
            }
        }
        
        return $this->dbConnection;
    }
}
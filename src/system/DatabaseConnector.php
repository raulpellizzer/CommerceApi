<?php
namespace Src\System;

class DatabaseConnector
{
    private $dbConnection = null;
    private $host;
    private $port;
    private $db;
    private $user;
    private $pass;

    public function __construct()
    {
        $this->host = $_ENV['DB_HOST'];
        //$this->port = $_ENV['DB_PORT'];
        $this->db   = $_ENV['DB_NAME'];
        $this->user = $_ENV['DB_USER'];
        $this->pass = $_ENV['DB_PASSWORD'];
    }

    private function connect($host, $db, $user, $pass)
    {
        try {
            $this->dbConnection = new \PDO(
                'mysql:host=' . $host . ';dbname=' . $db, $user, $pass
            );

            $this->dbConnection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();
        }
    }

    function getConnection()
    {
        if ($this->dbConnection === null) {
            $this->connect($this->host, $this->db, $this->user, $this->pass);
        }

        return $this->dbConnection;
    }

    function closeConnection()
    {
        $this->dbConnection = null;
    }
}


    
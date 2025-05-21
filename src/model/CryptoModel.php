<?php
namespace Src\Model;

class CryptoModel
{
    private $dbConnection;

    /**
     * CryptoModel constructor
     *
     * @param object $dbConnection Database connection object
     */
    public function __construct($dbConnection)
    {
        $this->dbConnection = $dbConnection;
        $this->logger = new LogModel($this->dbConnection);
    }

    /**
     * Retrieve encryption keys
     *
     * @return array Array of bits
     */
    public function getKeys() {
        return 'Keys';
    }

}
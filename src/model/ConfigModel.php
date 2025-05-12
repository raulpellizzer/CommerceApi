<?php
namespace Src\Model;

class ConfigModel
{
    private $dbConnection;

    /**
     * ConfigModel constructor
     *
     * @param object $dbConnection Database connection object
     */
    public function __construct($dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    // implement getConfigs()
    



}
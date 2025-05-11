<?php
namespace Src\Model;

class ReportModel 
{
    private $dbConnection;

    /**
     * ReportModel constructor
     *
     * @param object $dbConnection Database connection object
     */
    public function __construct($dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }


}
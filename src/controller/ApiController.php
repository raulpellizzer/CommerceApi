<?php
namespace Src\Controller;

class ApiController
{
    private $dbConnection;

    public function __construct($dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    public function EndPointIsUp()
    {
        return json_encode([
            'status' => 'ok',
            'message' => 'API is up and running'
        ]);
    }
}
<?php
namespace Src\Controller;

class ApiController
{
    private $dbConnection;
    private $requestMethod;

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

    public function processRequest($requestMethod)
    {
        switch ($requestMethod) {
            case 'GET':
                return $this->EndPointIsUp();
            case 'POST':
                // Handle POST request
                break;
            case 'PUT':
                // Handle PUT request
                break;
            case 'DELETE':
                // Handle DELETE request
                break;
            default:
                return json_encode([
                    'status' => 'error',
                    'message' => 'Invalid request method'
                ]);
        }
    }
}
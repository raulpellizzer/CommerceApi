<?php
namespace Src\Controller;
use Src\Model\LogModel;

/**
 * LogController class
 *
 * Handles the logging of API requests.
 */
class LogController
{
    private $requestMethod;
    private $logModel;

    /**
     * LogController constructor
     *
     * @param string $requestMethod The HTTP request method (GET, POST, PUT, DELETE)
     * @param object $dbConnection Database connection object
     */
    public function __construct($requestMethod, $dbConnection)
    {
        $this->requestMethod = $requestMethod;
        $this->logModel = new LogModel($dbConnection);
    }

    /**
     * Process the incoming /logs request
     *
     * @return string JSON response
     */
    public function processRequest()
    {
        switch ($this->requestMethod) {
            case 'POST':
                return $this->logModel->logMessage();

            default:
                return json_encode([
                    'status' => '400',
                    'message' => 'Invalid request method'
                ]);
        }
    }
}
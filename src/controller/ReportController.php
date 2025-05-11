<?php
namespace Src\Controller;
use Src\Model\ReportModel;

/**
     * ReportController constructor
     *
     * @param string $requestMethod The HTTP request method (GET, POST, PUT, DELETE)
     * @param object $reportModel ReportModel object
     */
class ReportController
{
    private $requestMethod;
    private $reportModel;

    /**
     * ReportController constructor
     *
     * @param string $requestMethod The HTTP request method (GET, POST, PUT, DELETE)
     * @param object $dbConnection Database connection object
     */
    public function __construct($requestMethod, $dbConnection)
    {
        $this->requestMethod = $requestMethod;
        $this->reportModel = new ReportModel($dbConnection);
    }

    /**
     * Process the incoming /reports request
     *
     * @return string JSON response
     */
    public function processRequest()
    {
        switch ($this->requestMethod) {
            case 'GET':
                return json_encode([
                    'status' => '200',
                    'message' => 'Reports endpoint is up'
                ]);
                //return $this->reportModel->ToBeImplemented(); // think of how to diferentiate between reports
            
            default:
                return json_encode([
                    'status' => '400',
                    'message' => 'Invalid request method'
                ]);
        }
    }

}

<?php
namespace Src\Controller;
use Src\Controller\ProductController;

class ApiController
{   
    private $dbConnection;

    /**
     * ApiController constructor
     *
     * @param object $dbConnection Database connection object
     */
    public function __construct($dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    /**
     * Check if the API endpoint is up
     *
     * @return string JSON response
     */
    public function EndPointIsUp()
    {
        return json_encode([
            'status' => 'ok',
            'message' => 'API is up and running'
        ]);
    }

    /**
     * Process the incoming request
     *
     * @param string $requestMethod The HTTP request method (GET, POST, PUT, DELETE)
     * @param array $uri The URI segments
     * @return string JSON response
     */
    public function processRequest($requestMethod, $uri)
    {
        $resource = "";

        if (isset($uri[2])) {
            $resource = trim($uri[2]);

            if($resource === 'products') {
                $stockControl = isset($_GET['stockcontrol']) ? $_GET['stockcontrol'] : null;
                $productController = new ProductController($requestMethod, $this->dbConnection, $stockControl);
                $response = $productController->processRequest();
                
                return $response;

            } else if($resource === 'reports') {
                $reportType = isset($_GET['reporttype']) ? $_GET['reporttype'] : null;
                $beginDate = isset($_GET['begindate']) ? $_GET['begindate'] : null;
                $endDate = isset($_GET['enddate']) ? $_GET['enddate'] : null;

                if ($reportType !== null && $beginDate !== null && $endDate !== null) {
                    $reportController = new ReportController($requestMethod, $this->dbConnection, $reportType, $beginDate, $endDate);
                    $response = $reportController->processRequest();
                    return $response;
                    
                } else {
                    return json_encode([
                        'status' => '400',
                        'message' => 'Report types or dates not specified'
                    ]);
                }
                
            } else if($resource === 'reports') {
                $configController = new ConfigController($requestMethod, $this->dbConnection);
                $response = $configController->processRequest();
            
            } else {
                return json_encode([
                    'status' => '404',
                    'message' => 'Resource not found'
                ]);
            }
        }
    }
}
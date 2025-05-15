<?php
namespace Src\Controller;
use Src\Controller\ProductController;
use Src\Model\UserModel;

class ApiController
{   
    private $dbConnection;
    private $userModel;

    /**
     * ApiController constructor
     *
     * @param object $dbConnection Database connection object
     */
    public function __construct($dbConnection, $user, $pass)
    {
        $this->dbConnection = $dbConnection;
        $this->userModel = new UserModel($dbConnection, $user, $pass);
    }

    /**
     * Check if the API endpoint is up
     *
     * @return string JSON response
     */
    public function EndPointIsUp()
    {
        return json_encode([
            'status' => '200',
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

        if($this->userModel->authenticate() === false) {
            http_response_code(401);
            return json_encode([
                'status' => '401',
                'message' => 'Unauthorized'
            ]);

        } else {
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
                    
                } else if($resource === 'configs') {
                    $configController = new ConfigController($requestMethod, $this->dbConnection);
                    $response = $configController->processRequest();
                    return $response;
                
                } else if($resource === 'sales') {
                    $saleController = new SaleController($requestMethod, $this->dbConnection);
                    $response = $saleController->processRequest();
                    return $response;
                
                } else if($resource === 'logs') {
                    $logController = new LogController($requestMethod, $this->dbConnection);
                    $response = $logController->processRequest();
                    return $response;
                
                } else if(Trim($resource) === '') {
                    $response = $this->EndPointIsUp();
                    return $response;
                
                } else {
                    http_response_code(404);
                    return json_encode([
                        'status' => '404',
                        'message' => 'Resource not found'
                    ]);
                }
            }
        }
    }
}
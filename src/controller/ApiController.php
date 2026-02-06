<?php
namespace Src\Controller;
use Src\Model\UserModel;
use Src\Middleware\ClientAuthMiddleware;
use Src\System;

class ApiController
{   
    private $dbConnection;
    private $userModel;
    private $settingsController;
    private $clientAuth;

    /**
     * ApiController constructor
     *
     * @param object $dbConnection Database connection object
     */
    public function __construct($dbConnection, $user, $pass)
    {
        $this->dbConnection = $dbConnection;
        $this->userModel = new UserModel($dbConnection, $user, $pass);
        $this->settingsController = new SettingsController($dbConnection);
        $this->clientAuth = new ClientAuthMiddleware($dbConnection);
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

        // Client Authentication
        $client = $this->clientAuth->validateClient();

        // Authenticate the user
        if($this->userModel->authenticate() === false) {
            http_response_code(401);
            return json_encode([
                'status' => '401',
                'message' => 'Unauthorized'
            ]);

        } else {

            $apiSettings = $this->settingsController->getApiSettings();
            $apiSettings = json_decode($apiSettings, true);

            // Debug key for testing when in maintenance mode
            if (isset($_GET['debug-key']) && $_GET['debug-key'] === $_ENV['API_DEBUG_KEY'] )
                $apiIsAvailable = 1;
            else
                $apiIsAvailable = $apiSettings['data'][0]['IsApiOnline'];

            // Check if the API is available
            if ($apiIsAvailable === 1) {

                $tenantPlanType = $this->userModel->getTenantPlanType();
                $planController = new PlanController($requestMethod, $this->dbConnection);
                $planFeatureDataMapping = $planController->planModel->getPlanData();
                $availableFeatures = $planController->planModel->GetAvailableFeatures($planFeatureDataMapping, $tenantPlanType);

                if (isset($uri[3])) {
                    $resource = trim($uri[3]);

                    if($resource === 'plan') {
                        return $planFeatureDataMapping;

                    } else {

                        // Connect to the tenant database and proceed with the request
                        // related to tenant resources
                        $this->connectTenantDb();
                        
                        if($resource === 'products') {
                            $stockControl = isset($_GET['stockcontrol']) ? $_GET['stockcontrol'] : null;
                            $productController = new ProductController($requestMethod, $this->dbConnection, $stockControl, $availableFeatures);
                            $response = $productController->processRequest();
                            return $response;

                        } else if($resource === 'clients') {
                            $clientResource = trim(isset($uri[4]) ? $uri[4] : '');
                            $clientId = isset($_GET['clientid']) ? $_GET['clientid'] : null;
                            $saleId = isset($_GET['saleid']) ? $_GET['saleid'] : null;

                            $clientController = new ClientController($requestMethod, $this->dbConnection, $availableFeatures, $clientResource, $clientId, $saleId);
                            $response = $clientController->processRequest();
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
                            $configController = new ConfigController($requestMethod, $this->dbConnection, $availableFeatures);
                            $response = $configController->processRequest();
                            return $response;
                        
                        } else if($resource === 'sales') {
                            $saleController = new SaleController($requestMethod, $this->dbConnection, $availableFeatures);
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

            } else {
                http_response_code(503);
                return json_encode([
                    'status' => '503',
                    'message' => 'API is currently unavailable'
                ]);
            }
        }
    }

    /**
     * Connect to the tenant database
     * Designed for multi tenant architecture.
     *
     * @return void
     */
    public function connectTenantDb() {
        $dbName = $this->userModel->getUserDatabase($_SERVER['PHP_AUTH_USER']);

        $this->dbConnection = null;
        $this->dbConnection = new System\DatabaseConnector();
        $this->dbConnection = $this->dbConnection->getConnection($dbName);
    }
}
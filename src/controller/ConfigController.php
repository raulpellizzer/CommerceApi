<?php
namespace Src\Controller;
use Src\Model\ConfigModel;

/**
     * ConfigController constructor
     *
     * @param string $requestMethod The HTTP request method (GET, POST, PUT, DELETE)
     * @param object $productModel ProductModel object
     */
class ConfigController
{
    private $requestMethod;
    private $configModel;

    /**
     * ConfigController constructor
     *
     * @param string $requestMethod The HTTP request method (GET, POST, PUT, DELETE)
     * @param object $dbConnection Database connection object
     */
    public function __construct($requestMethod, $dbConnection)
    {
        $this->requestMethod = $requestMethod;
        $this->configModel = new ConfigModel($dbConnection);
    }

    /**
     * Process the incoming /configs request
     *
     * @return string JSON response
     */
    public function processRequest()
    {
        switch ($this->requestMethod) {
            case 'GET':
                return $this->configModel->getConfigs();

            case 'PUT':
                return $this->configModel->updateConfig();

            default:
                return json_encode([
                    'status' => '400',
                    'message' => 'Invalid request method'
                ]);
        }
    }
}
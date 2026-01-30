<?php
namespace Src\Controller;
use Src\Model\CryptoModel;

/**
     * CryptoController constructor
     *
     * @param string $requestMethod The HTTP request method (GET, POST, PUT, DELETE)
     * @param object $cryptoModel ProductModel object
     */
class CryptoController
{
    private $requestMethod;
    private $cryptoModel;

    /**
     * ProductController constructor
     *
     * @param string $requestMethod The HTTP request method (GET, POST, PUT, DELETE)
     * @param object $dbConnection Database connection object
     * @param string $stockControl Stock control parameter
     */
    public function __construct($requestMethod, $dbConnection)
    {
        $this->requestMethod = $requestMethod;
        $this->cryptoModel = new CryptoModel($dbConnection);
    }

    /**
     * Process the incoming /keys request
     *
     * @return string JSON response
     */
    public function processRequest()
    {
        switch ($this->requestMethod) {
            case 'GET':
                return $this->cryptoModel->getKeys();
                
            default:
                return json_encode([
                    'status' => '400',
                    'message' => 'Invalid request method'
                ]);
        }
    }
}
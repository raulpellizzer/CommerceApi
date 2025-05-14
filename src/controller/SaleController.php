<?php
namespace Src\Controller;
use Src\Model\SaleModel;

/**
     * SaleController constructor
     *
     * @param string $requestMethod The HTTP request method (GET, POST, PUT, DELETE)
     * @param object $productModel ProductModel object
     */
class SaleController
{
    private $requestMethod;
    private $saleModel;

    /**
     * SaleController constructor
     *
     * @param string $requestMethod The HTTP request method (GET, POST, PUT, DELETE)
     * @param object $dbConnection Database connection object
     */
    public function __construct($requestMethod, $dbConnection)
    {
        $this->requestMethod = $requestMethod;
        $this->saleModel = new SaleModel($dbConnection);
    }

    /**
     * Process the incoming /sales request
     *
     * @return string JSON response
     */
    public function processRequest()
    {
        switch ($this->requestMethod) {
            case 'GET':
                return $this->saleModel->getLastSale();

            case 'POST':
                return $this->saleModel->createSale();

            default:
                return json_encode([
                    'status' => '400',
                    'message' => 'Invalid request method'
                ]);
        }
    }
}
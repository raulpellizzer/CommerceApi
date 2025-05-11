<?php
namespace Src\Controller\Products;
use Src\Model\Products\ProductModel;

/**
     * ApiController constructor
     *
     * @param string $requestMethod The HTTP request method (GET, POST, PUT, DELETE)
     * @param object $productModel ProductModel object
     */
class ProductController
{
    private $requestMethod;
    private $productModel;

    /**
     * ProductController constructor
     *
     * @param string $requestMethod The HTTP request method (GET, POST, PUT, DELETE)
     * @param object $dbConnection Database connection object
     */
    public function __construct($requestMethod, $dbConnection)
    {
        $this->requestMethod = $requestMethod;
        $this->productModel = new ProductModel($dbConnection);
    }

    /**
     * Process the incoming /products request
     *
     * @return string JSON response
     */
    public function processRequest()
    {
        switch ($this->requestMethod) {
            case 'GET':
                return $this->productModel->getAllProducts();
            case 'POST':
                return $this->productModel->createProduct();
            case 'PUT':
                return $this->productModel->updateProduct();
            case 'DELETE':
                return $this->productModel->deleteProduct();
            default:
                return json_encode([
                    'status' => '400',
                    'message' => 'Invalid request method'
                ]);
        }
    }
}

    
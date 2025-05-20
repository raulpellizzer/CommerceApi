<?php
namespace Src\Model;

class ProductModel
{
    private $dbConnection;
    private $stockControl;

    /**
     * ProductModel constructor
     *
     * @param object $dbConnection Database connection object
     * @param string $stockControl Stock control parameter
     */
    public function __construct($dbConnection, $stockControl)
    {
        $this->dbConnection = $dbConnection;
        $this->stockControl = $stockControl;
        $this->logger = new LogModel($this->dbConnection);
    }

    /**
     * Get all products from the database
     *
     * @return array Array of products
     */
    public function getAllProducts()
    {
        try {
            if ($this->stockControl === 'true') {
                $sql = 'SELECT * FROM Products WHERE Stock > 0';
            } else {
                $sql = 'SELECT * FROM Products';
            }

            $stmt = $this->dbConnection->prepare($sql);
            $res = $stmt->execute();

            if (!$res) {
                http_response_code(400);
                return json_encode([
                    'status' => '400',
                    'message' => 'Error fetching products'
                ]);
            }

            http_response_code(200);
            return json_encode($stmt->fetchAll(\PDO::FETCH_ASSOC));

        } catch (\Exception $e) {

            $this->logger->logMessage([
                'currentDateTime' => date('Y-m-d H:i:s'),
                'file' => __CLASS__,
                'function' => __FUNCTION__,
                'message' => $e->getMessage(),
                'args' => null,
                'stackTrace' => print_r(debug_backtrace(), true),   
                'type' => 'Error',
                'category' => 'Product'
            ]);

            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Checks in barcode already exists in the database
     *
     * @return string JSON response
     */
    private function checkProductExists($barcode)
    {
        try {
            $statement = $this->dbConnection->prepare('SELECT * FROM Products WHERE Barcode = :barcode');
            $statement->execute([
                'barcode' => $barcode
            ]);
            return $statement->rowCount() > 0;

        } catch (\Exception $e) {
            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Create a new product in the database
     *
     * @return string JSON response
     */
    public function createProducts()
    {
        try {
            $_POST = json_decode(file_get_contents("php://input"), true);

            if (isset($_POST['Products'])) {
                $products = $_POST['Products'];

                if (count($products) === 1) {
                    $productExists = $this->checkProductExists($products[0]['BarCode']);
                    if ($productExists) {
                        http_response_code(400);
                        return json_encode([
                            'status' => '400',
                            'message' => 'Product already exists'
                        ]);
                    }
                }

                foreach ($products as $product) {

                    $statement = $this->dbConnection->prepare('INSERT INTO Products (Barcode, Stock, Price, Name)
                    VALUES (:barcode, :stock, :price, :name)');

                    $res = $statement->execute([
                        'barcode' => $product['BarCode'],
                        'stock' => $product['Stock'],
                        'price' => $product['Price'],
                        'name' => $product['Name'],
                    ]);

                    if ($res) {
                        $this->logger->logMessage([
                            'currentDateTime' => date('Y-m-d H:i:s'),
                            'file' => __CLASS__,
                            'function' => __FUNCTION__,
                            'message' => 'Creating Product',
                            'args' => 'barCode: ' . $product['BarCode'] . ', stock: ' . $product['Stock'] . ', price: ' . $product['Price'] . ', name: ' . $product['Name'],
                            'stackTrace' => null,
                            'type' => 'Info',
                            'category' => 'Product'
                        ]);
                    }
                }

                http_response_code(201);
                return json_encode([
                    'status' => '201',
                    'message' => 'Products created successfully'
                ]);

            } else {

                $this->logger->logMessage([
                    'currentDateTime' => date('Y-m-d H:i:s'),
                    'file' => __CLASS__,
                    'function' => __FUNCTION__,
                    'message' => 'Creating Product - Bad request 400',
                    'args' => '_POST -> products not present',
                    'stackTrace' => null,
                    'type' => 'Error',
                    'category' => 'Product'
                ]);

                http_response_code(400);
                return json_encode([
                    'status' => '400',
                    'message' => 'Product already exists'
                ]);
            }

        } catch (\Exception $e) {

            $this->logger->logMessage([
                'currentDateTime' => date('Y-m-d H:i:s'),
                'file' => __CLASS__,
                'function' => __FUNCTION__,
                'message' => $e->getMessage(),
                'args' => null,
                'stackTrace' => print_r(debug_backtrace(), true),   
                'type' => 'Error',
                'category' => 'Product'
            ]);

            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update an existing product in the database
     *
     * @return string JSON response
     */
    public function updateProducts()
    {
        try {
            $_POST = json_decode(file_get_contents("php://input"), true);

            if (isset($_POST['Products'])) {
                $products = $_POST['Products'];

                foreach ($products as $product) {

                    $statement = $this->dbConnection->prepare('UPDATE Products SET Stock = :stock, Price = :price, Name = :name WHERE BarCode = :barcode');
                    $res = $statement->execute([
                        'barcode' => $product['BarCode'],
                        'stock' => $product['Stock'],
                        'price' => $product['Price'],
                        'name' => $product['Name'],
                    ]);

                    if ($res) {
                        $this->logger->logMessage([
                            'currentDateTime' => date('Y-m-d H:i:s'),
                            'file' => __CLASS__,
                            'function' => __FUNCTION__,
                            'message' => 'Updating Product Data',
                            'args' => 'barCode: ' . $product['BarCode'] . ', stock: ' . $product['Stock'] . ', price: ' . $product['Price'] . ', name: ' . $product['Name'],
                            'stackTrace' => null,
                            'type' => 'Info',
                            'category' => 'Product'
                        ]);
                    }
                }

                http_response_code(201);
                return json_encode([
                    'status' => '201',
                    'message' => 'Products updated successfully'
                ]);

            } else {

                $this->logger->logMessage([
                    'currentDateTime' => date('Y-m-d H:i:s'),
                    'file' => __CLASS__,
                    'function' => __FUNCTION__,
                    'message' => 'Updating Product - Bad request 400',
                    'args' => '_POST -> products not present',
                    'stackTrace' => null,
                    'type' => 'Error',
                    'category' => 'Product'
                ]);

                http_response_code(400);
                return json_encode([
                    'status' => '400',
                    'message' => 'Product already exists'
                ]);
            }

        } catch (\Exception $e) {

            $this->logger->logMessage([
                'currentDateTime' => date('Y-m-d H:i:s'),
                'file' => __CLASS__,
                'function' => __FUNCTION__,
                'message' => $e->getMessage(),
                'args' => null,
                'stackTrace' => print_r(debug_backtrace(), true),   
                'type' => 'Error',
                'category' => 'Product'
            ]);

            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete a product from the database
     *
     * @return string JSON response
     */
    public function deleteProducts()
    {
        try {
            $_POST = json_decode(file_get_contents("php://input"), true);
            $statement = $this->dbConnection->prepare('DELETE FROM Products WHERE BarCode = :barcode');

            $res = $statement->execute([
                'barcode' => $_POST['BarCode']
            ]);

            if (!$res) {
                http_response_code(400);
                return json_encode([
                    'status' => '400',
                    'message' => 'Error deleting product'
                ]);
            } else {

                $this->logger->logMessage([
                    'currentDateTime' => date('Y-m-d H:i:s'),
                    'file' => __CLASS__,
                    'function' => __FUNCTION__,
                    'message' => 'Deleting Product',
                    'args' => 'barCode: ' . $_POST['BarCode'],
                    'stackTrace' => null,
                    'type' => 'Info',
                    'category' => 'Product'
                ]);

                http_response_code(200);
                return json_encode([
                    'status' => '200',
                    'message' => 'Product deleted successfully'
                ]);
            }

        } catch (\Exception $e) {

            $this->logger->logMessage([
                'currentDateTime' => date('Y-m-d H:i:s'),
                'file' => __CLASS__,
                'function' => __FUNCTION__,
                'message' => $e->getMessage(),
                'args' => null,
                'stackTrace' => print_r(debug_backtrace(), true),   
                'type' => 'Error',
                'category' => 'Product'
            ]);

            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }
}
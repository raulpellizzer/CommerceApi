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

        } catch (\PDOException $e) {
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

        } catch (\PDOException $e) {
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
    public function createProduct()
    {
        try {
            $_POST = json_decode(file_get_contents("php://input"), true);

            $productExists = $this->checkProductExists($_POST['barCode']);
            if ($productExists) {
                http_response_code(400);
                return json_encode([
                    'status' => '400',
                    'message' => 'Product already exists'
                ]);
                
            } else {

                $statement = $this->dbConnection->prepare('INSERT INTO Products (Barcode, Stock, Price, Name)
                VALUES (:barcode, :stock, :price, :name)');

                $res = $statement->execute([
                    'barcode' => $_POST['barCode'],
                    'stock' => $_POST['stock'],
                    'price' => $_POST['price'],
                    'name' => $_POST['name'],
                ]);

                if (!$res) {
                    http_response_code(400);
                    return json_encode([
                        'status' => '400',
                        'message' => 'Error creating product'
                    ]);
                } else {
                    http_response_code(201);
                    return json_encode([
                        'status' => '201',
                        'message' => 'Product created successfully'
                    ]);
                }
            }

        } catch (\PDOException $e) {
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
    public function updateProduct()
    {
        try {
            $_POST = json_decode(file_get_contents("php://input"), true);
            $statement = $this->dbConnection->prepare('UPDATE Products SET Stock = :stock, Price = :price, Name = :name WHERE BarCode = :barcode');

            $res = $statement->execute([
                'stock' => $_POST['stock'],
                'price' => $_POST['price'],
                'name' => $_POST['name'],
                'barcode' => $_POST['barCode']
            ]);

            if (!$res) {
                http_response_code(400);
                return json_encode([
                    'status' => '400',
                    'message' => 'Error updating product'
                ]);
            } else {
                http_response_code(200);
                return json_encode([
                    'status' => '200',
                    'message' => 'Product updated successfully'
                ]);
            }

        } catch (\PDOException $e) {
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
    public function deleteProduct()
    {
        try {
            $_POST = json_decode(file_get_contents("php://input"), true);
            $statement = $this->dbConnection->prepare('DELETE FROM Products WHERE BarCode = :barcode');

            $res = $statement->execute([
                'barcode' => $_POST['barCode']
            ]);

            if (!$res) {
                http_response_code(400);
                return json_encode([
                    'status' => '400',
                    'message' => 'Error deleting product'
                ]);
            } else {
                http_response_code(200);
                return json_encode([
                    'status' => '200',
                    'message' => 'Product deleted successfully'
                ]);
            }

        } catch (\PDOException $e) {
            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }
}
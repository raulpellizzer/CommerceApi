<?php
namespace Src\Model\Products;

class ProductModel
{
    private $dbConnection;

    /**
     * ProductModel constructor
     *
     * @param object $dbConnection Database connection object
     */
    public function __construct($dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    /**
     * Get all products from the database
     *
     * @return array Array of products
     */
    public function getAllProducts()
    {
        try {
            $sql = 'SELECT * FROM products';
            $stmt = $this->dbConnection->prepare($sql);
            $res = $stmt->execute();

            if (!$res) {
                return json_encode([
                    'status' => '400',
                    'message' => 'Error fetching products'
                ]);
            }

            return json_encode([
                'status' => '200',
                'message' => 'Products found',
                'data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)
            ]);

        } catch (\PDOException $e) {
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
        $statement = $this->dbConnection->prepare('SELECT * FROM products WHERE Barcode = :barcode');
        $statement->execute([
            'barcode' => $barcode
        ]);

        return $statement->rowCount() > 0;
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
                return json_encode([
                    'status' => '400',
                    'message' => 'Product already exists'
                ]);
                
            } else {

                $statement = $this->dbConnection->prepare('INSERT INTO products (Barcode, Stock, Price, Name)
                VALUES (:barcode, :stock, :price, :name)');

                $res = $statement->execute([
                    'barcode' => $_POST['barCode'],
                    'stock' => $_POST['stock'],
                    'price' => $_POST['price'],
                    'name' => $_POST['name'],
                ]);

                if (!$res) {
                    return json_encode([
                        'status' => '400',
                        'message' => 'Error creating product'
                    ]);
                } else {
                    return json_encode([
                        'status' => '201',
                        'message' => 'Product created successfully'
                    ]);
                }
            }

        } catch (\PDOException $e) {
            return json_encode([
                'status' => '500',
                'message' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }
}
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
}
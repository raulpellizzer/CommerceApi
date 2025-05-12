<?php
namespace Src\Model;

class SaleModel
{
    private $dbConnection;

    /**
     * SaleModel constructor
     *
     * @param object $dbConnection Database connection object
     */
    public function __construct($dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    /**
     * Create a new sale in the database
     *
     * @return string JSON response
     */
    public function createSale()
    {
        try {

            















            // Assuming you have a POST request with sale data
            $data = json_decode(file_get_contents("php://input"), true);
            $productId = $data['product_id'];
            $quantity = $data['quantity'];

            // Insert sale into the database
            $sql = 'INSERT INTO sales (product_id, quantity) VALUES (:product_id, :quantity)';
            $stmt = $this->dbConnection->prepare($sql);
            $stmt->bindParam(':product_id', $productId);
            $stmt->bindParam(':quantity', $quantity);
            $res = $stmt->execute();

            if (!$res) {
                return json_encode([
                    'status' => '400',
                    'message' => 'Error creating sale'
                ]);
            }

            return json_encode([
                'status' => '200',
                'message' => 'Sale created successfully'
            ]);
            

        } catch (\PDOException $e) {
            return json_encode([
                'status' => '500',
                'message' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }
}
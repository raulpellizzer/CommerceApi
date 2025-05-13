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
            $saleData = json_decode(file_get_contents("php://input"), true);
            $this->dbConnection->beginTransaction();

            // Get next SaleId
            $stmt = $this->dbConnection->prepare('SELECT MAX(SaleId) as MaxSaleId FROM Sales');
            $stmt->execute();
            $saleId = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $nextSaleId = $saleId[0]['MaxSaleId'] + 1;

            // Insert to Sales table
            $stmt = $this->dbConnection->prepare('INSERT INTO Sales (SaleId, Total, SaleDate) VALUES (:saleId, :totalValue, :saleDate)');
            $stmt->execute([
                'saleId' => $nextSaleId,
                'totalValue' => $saleData['total'],
                'saleDate' => $saleData['saleDate']
            ]);

            // Insert to SaleDetails table
            foreach ($saleData['productCart'] as $product) {
                $stmt = $this->dbConnection->prepare('INSERT INTO SaleDetails (SaleId, ProductId, ProductQuantity) VALUES (:saleId, :productId, :productQuantity)');
                $stmt->execute([
                    'saleId' => $nextSaleId,
                    'productId' => $product['productId'],
                    'productQuantity' => $product['quantity']
                ]);
            }

            // Update product stock
            if($saleData['updateStock'] === 'True') {
                foreach ( array_keys($saleData['sellStockUpdate']) as $key ) {
                    $stmt = $this->dbConnection->prepare('UPDATE Products SET Stock = Stock - :productQuantity WHERE BarCode = :barCode');
                    $stmt->execute([
                        'productQuantity' => $saleData['sellStockUpdate'][$key],
                        'barCode' => $key
                    ]);
                }
            }

            $this->dbConnection->commit();
            http_response_code(200);
            return json_encode([
                'status' => '200',
                'message' => 'Sale created successfully',
            ]);

        } catch (\PDOException $e) {
            $this->dbConnection->rollBack();
            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }
}
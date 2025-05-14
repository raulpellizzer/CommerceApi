<?php
namespace Src\Model;
require_once __DIR__ . '/LogModel.php';

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
        $this->logger = new LogModel($this->dbConnection);
    }

    /**
     * Get the last sale from the database
     *
     * @return string JSON response
     */
    public function getLastSale() {
        try {
            $stmt = $this->dbConnection->prepare('SELECT SaleId, Total, SaleDate FROM Sales ORDER BY SaleId DESC LIMIT 1');
            $stmt->execute();
            $lastSale = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($lastSale)) {
                http_response_code(404);
                return json_encode([
                    'status' => '404',
                    'message' => 'No sales found'
                ]);
            }

            http_response_code(200);
            return json_encode($lastSale);

        } catch (\PDOException $e) {

            $this->logger->logMessage([
                'currentDateTime' => date('Y-m-d H:i:s'),
                'file' => __CLASS__,
                'function' => __FUNCTION__,
                'message' => $e->getMessage(),
                'args' => null,
                'stackTrace' => print_r(debug_backtrace(), true),   
                'type' => 'Error',
                'category' => 'Sale'
            ]);

            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
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

            $this->logger->logMessage([
                'currentDateTime' => date('Y-m-d H:i:s'),
                'file' => __CLASS__,
                'function' => __FUNCTION__,
                'message' => 'New Sale',
                'args' => print_r($saleData, true),
                'stackTrace' => null,
                'type' => 'Info',
                'category' => 'Sale'
            ]);

            $this->dbConnection->commit();
            http_response_code(200);
            return json_encode([
                'status' => '200',
                'message' => 'Sale created successfully',
            ]);

        } catch (\PDOException $e) {
            $this->dbConnection->rollBack();

            $this->logger->logMessage([
                'currentDateTime' => date('Y-m-d H:i:s'),
                'file' => __CLASS__,
                'function' => __FUNCTION__,
                'message' => $e->getMessage(),
                'args' => print_r($saleData, true),
                'stackTrace' => print_r(debug_backtrace(), true),   
                'type' => 'Error',
                'category' => 'Sale'
            ]);

            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }
}
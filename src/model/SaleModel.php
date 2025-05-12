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

            echo $saleData['saleDate'] . "\n";
            echo $saleData['total'] . "\n";
            die();

            //var_dump($saleData);

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

            // continue here to SaleDetails - wip











            
            

        } catch (\PDOException $e) {
            return json_encode([
                'status' => '500',
                'message' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }
}
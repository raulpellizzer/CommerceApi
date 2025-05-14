<?php
namespace Src\Model;
require_once __DIR__ . '/LogModel.php';

class ReportModel 
{
    private $dbConnection;

    /**
     * ReportModel constructor
     *
     * @param object $dbConnection Database connection object
     * @param string $reportType Type of report to generate
     * @param string $beginDate Start date for the report
     * @param string $endDate End date for the report
     */
    public function __construct($dbConnection, $reportType, $beginDate, $endDate)
    {
        $this->dbConnection = $dbConnection;
        $this->reportType = $reportType;
        $this->beginDate = $beginDate;
        $this->endDate = $endDate;
        $this->logger = new LogModel($this->dbConnection);
    }

    /**
     * Get the report based on the report type
     * Report types allowed: Sales, ProductExitOrder, ProductBestSeller, SaleByDay
     *
     * @return string JSON response
     */
    public function getReportData()
    {
        try {
            switch ($this->reportType) {
                case 'Sales':
                    $sql = 'SELECT * FROM Sales WHERE SaleDate BETWEEN :beginDate AND :endDate';
                    break;

                case 'ProductExitOrder':
                    $sql = 'SELECT sales.SaleDate, products.Name, saleDetails.ProductQuantity FROM Sales as sales ' .
                        'INNER JOIN SaleDetails as saleDetails ON sales.SaleId = saleDetails.SaleId ' .
                        'INNER JOIN Products as products ON products.ProductId = saleDetails.ProductId ' .
                        'WHERE sales.SaleDate BETWEEN :beginDate AND :endDate ' . 
                        'ORDER BY sales.SaleDate asc';
                    break;

                case 'ProductBestSeller':
                    $sql = 'SELECT products.BarCode, products.Name, SUM(saleDetails.ProductQuantity) as SumProductQuantity FROM SaleDetails as saleDetails ' . 
                        'INNER JOIN Products as products on products.ProductId = saleDetails.ProductId ' . 
                        'INNER JOIN Sales as sales ON saleDetails.SaleId = sales.SaleId ' . 
                        'WHERE sales.SaleDate BETWEEN :beginDate AND :endDate ' . 
                        'GROUP BY saleDetails.ProductId ' . 
                        'ORDER BY SumProductQuantity desc';
                    break;

                case 'SaleByDay':
                    $sql = 'SELECT DATE(SaleDate) as SaleDate, SUM(Total) as TotalPerDay FROM Sales ' .
                        'WHERE SaleDate BETWEEN :beginDate AND :endDate ' . 
                        'GROUP BY SaleDate ' . 
                        'ORDER BY SaleDate asc';
                    break;

                default:
                    http_response_code(400);
                    return json_encode([
                        'status' => '400',
                        'message' => 'Invalid report type'
                    ]);
            }

            $statement = $this->dbConnection->prepare($sql);
            $statement->execute([
                'beginDate' => $this->beginDate,
                'endDate' => $this->endDate
            ]);

            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if ($statement->rowCount() > 0) {
                http_response_code(200);
                return json_encode($result);

            } else {
                http_response_code(404);
                return json_encode([
                    'status' => '404',
                    'message' => 'No report data found for the given dates'
                ]);
            }

        } catch (\PDOException $e) {

            $this->logger->logMessage([
                'currentDateTime' => date('Y-m-d H:i:s'),
                'file' => __CLASS__,
                'function' => __FUNCTION__,
                'message' => $e->getMessage(),
                'args' => 'beginDate: ' . $this->beginDate . ', endDate: ' . $this->endDate,
                'stackTrace' => print_r(debug_backtrace(), true),   
                'type' => 'Error',
                'category' => 'Report'
            ]);

            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }
}
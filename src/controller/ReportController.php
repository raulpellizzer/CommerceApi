<?php
namespace Src\Controller;
use Src\Model\ReportModel;

/**
     * ReportController constructor
     *
     * @param string $requestMethod The HTTP request method (GET, POST, PUT, DELETE)
     * @param object $reportModel ReportModel object
     */
class ReportController
{
    private $requestMethod;
    private $reportModel;

    /**
     * ReportController constructor
     *
     * @param string $requestMethod The HTTP request method (GET, POST, PUT, DELETE)
     * @param object $dbConnection Database connection object
     * @param string $reportType Type of report to generate
     * @param string $beginDate Start date for the report
     * @param string $endDate End date for the report
     */
    public function __construct($requestMethod, $dbConnection, $reportType, $beginDate, $endDate)
    {
        $this->requestMethod = $requestMethod;
        $this->reportModel = new ReportModel($dbConnection, $reportType, $beginDate, $endDate);
    }

    /**
     * Process the incoming /reports?reporttype=abcd request
     *
     * @return string JSON response
     */
    public function processRequest()
    {
        switch ($this->requestMethod) {
            case 'GET':
                return $this->reportModel->getReportData();

            default:
                return json_encode([
                    'status' => '400',
                    'message' => 'Invalid request method'
                ]);
        }
    }
}

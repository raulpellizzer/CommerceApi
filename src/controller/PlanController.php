<?php
namespace Src\Controller;
use Src\Model\PlanModel;

class PlanController
{
    private $requestMethod;
    public $planModel;

    /**
     * PlanController constructor
     *
     * @param object $dbConnection Database connection object
     */
    public function __construct($requestMethod, $dbConnection)
    {
        $this->requestMethod = $requestMethod;
        $this->planModel = new PlanModel($dbConnection);
    }

    /**
     * Process the incoming /plan request for plans
     *
     * @return string JSON response
     */
    public function processRequest()
    {
        switch ($this->requestMethod) {
            case 'GET':
                return $this->planModel->getPlanData();
                
            default:
                return json_encode([
                    'status' => '400',
                    'message' => 'Invalid request method'
                ]);
        }
    }
}
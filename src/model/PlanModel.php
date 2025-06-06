<?php
namespace Src\Model;

class PlanModel
{
    private $dbConnection;

    /**
     * PlanModel constructor
     *
     * @param object $dbConnection Database connection object
     */
    public function __construct($dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    /**
     * Get plan data from the database
     *
     * @return string JSON response with plan data
     */
    public function getPlanData()
    {
        try {

            $query = "SELECT pf.PlanId, ft.Id as FeatureId, ft.Name as FeatureName FROM PlanFeature AS pf INNER JOIN Feature AS ft ON pf.FeatureId = ft.Id";
            
            $statement = $this->dbConnection->prepare($query);
            $statement->execute();
            $planFeatureData = $statement->fetchAll(\PDO::FETCH_ASSOC);

            $query = "select plan_type from user where email_hash = :emailHash";
            $statement = $this->dbConnection->prepare($query);
            $emailHash = hash('sha256', strtolower(trim($_SERVER['PHP_AUTH_USER'])));
            $statement->execute(['emailHash' => $emailHash]);
            $userPlanType = $statement->fetchColumn();

            return json_encode([
                'PlanFeatures' => $planFeatureData,
                'UserPlanType' => $userPlanType,
                'Status' => '200',
            ]);

        } catch (\Exception $e) {
            return json_encode([
                'status' => '500',
                'message' => 'Internal Server Error: ' . $e->getMessage()
            ]);
        }
    }
}
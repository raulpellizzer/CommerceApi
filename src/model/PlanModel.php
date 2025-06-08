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

            $query = "SELECT plan_type FROM user WHERE email_hash = :emailHash";
            $statement = $this->dbConnection->prepare($query);
            $emailHash = hash('sha256', strtolower(trim($_SERVER['PHP_AUTH_USER'])));
            $statement->execute(['emailHash' => $emailHash]);
            $userPlanType = $statement->fetchColumn();

            $query = "SELECT created_date as CreatedDate, tenant_name as TenantName FROM user WHERE email_hash = :emailHash";
            $statement = $this->dbConnection->prepare($query);
            $statement->execute(['emailHash' => $emailHash]);
            $subscriptionData = $statement->fetchAll(\PDO::FETCH_ASSOC);

            return json_encode([
                'PlanFeatures' => $planFeatureData,
                'UserPlanType' => $userPlanType,
                'UserSubscriptionData' => $subscriptionData,
                'Status' => '200',
            ]);

        } catch (\Exception $e) {
            return json_encode([
                'status' => '500',
                'message' => 'Internal Server Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get available features based on the plan type
     *
     * @param array $planFeatureDataMapping Array of plan feature data
     * @param string $tenantPlanType The plan type of the tenant
     * @return array List of available features for the tenant's plan type
     */
    public function GetAvailableFeatures($planFeatureDataMapping, $tenantPlanType)
    {
        $planFeatureDataMapping = json_decode($planFeatureDataMapping, true);
        $planFeatureDataMapping = $planFeatureDataMapping['PlanFeatures'];
        $availableFeatures = [];
        $tenantPlanType = (int) $tenantPlanType;

        foreach ($planFeatureDataMapping as $feature) {

            if ($tenantPlanType >= $feature['PlanId']) {
                $availableFeatures[] = [
                    $feature['FeatureName'] => true
                ];
            } else {
                $availableFeatures[] = [
                    $feature['FeatureName'] => false
                ];
            }
        }

        return $availableFeatures;
    }
}
<?php
namespace Src\Model;

class ConfigModel
{
    private $dbConnection;

    /**
     * ConfigModel constructor
     *
     * @param object $dbConnection Database connection object
     */
    public function __construct($dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    /**
     * Get all configurations from the database
     *
     * @return array Array of configurations
     */
    public function getConfigs()
    {
        try {
            $sql = 'SELECT * FROM Configuration';
            $stmt = $this->dbConnection->prepare($sql);
            $res = $stmt->execute();

            if (!$res) {
                http_response_code(400);
                return json_encode([
                    'status' => '400',
                    'message' => 'Error fetching settings'
                ]);
            }

            http_response_code(200);
            return json_encode($stmt->fetchAll(\PDO::FETCH_ASSOC));

        } catch (\PDOException $e) {
            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update configuration in the database
     *
     * @return string JSON response
     */
    public function updateConfig()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $this->dbConnection->beginTransaction();

            foreach($data as $itemConfig) { 
                $statement = $this->dbConnection->prepare('UPDATE Configuration SET ConfigValue = :configValue WHERE ConfigDescription = :configDescription');
                $statement->execute([
                    'configValue' => $itemConfig['configValue'],
                    'configDescription' => $itemConfig['configDescription']
                ]);
            }

            $this->dbConnection->commit();
            http_response_code(200);
            return json_encode([
                'status' => '200',
                'message' => 'Settings updated successfully'
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
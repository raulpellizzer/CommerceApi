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
                return json_encode([
                    'status' => '400',
                    'message' => 'Error fetching settings'
                ]);
            }

            return json_encode([
                'status' => '200',
                'message' => 'Settings found',
                'data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)
            ]);

        } catch (\PDOException $e) {
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

            foreach($data as $itemConfig) { 
                $statement = $this->dbConnection->prepare('UPDATE Configuration SET ConfigValue = :configValue WHERE ConfigDescription = :configDescription');
                $res = $statement->execute([
                    'configValue' => $itemConfig['configValue'],
                    'configDescription' => $itemConfig['configDescription']
                ]);
            }

            if (!$res) {
                return json_encode([
                    'status' => '400',
                    'message' => 'Error updating settings'
                ]);
            } else {
                return json_encode([
                    'status' => '200',
                    'message' => 'Settings updated successfully'
                ]);
            }

        } catch (\PDOException $e) {
            return json_encode([
                'status' => '500',
                'message' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }
}
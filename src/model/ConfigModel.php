<?php
namespace Src\Model;
//require_once __DIR__ . '/LogModel.php';

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
        $this->logger = new LogModel($this->dbConnection);
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

            $this->logger->logMessage([
                'currentDateTime' => date('Y-m-d H:i:s'),
                'file' => __CLASS__,
                'function' => __FUNCTION__,
                'message' => $e->getMessage(),
                'args' => null,
                'stackTrace' => print_r(debug_backtrace(), true),   
                'type' => 'Error',
                'category' => 'Config'
            ]);

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
                    'configValue' => $itemConfig['ConfigValue'],
                    'configDescription' => $itemConfig['ConfigDescription']
                ]);
            }

            $this->logger->logMessage([
                'currentDateTime' => date('Y-m-d H:i:s'),
                'file' => __CLASS__,
                'function' => __FUNCTION__,
                'message' => 'Saved settings successfully',
                'args' => print_r($data, true),
                'stackTrace' => null,
                'type' => 'Info',
                'category' => 'Config'
            ]);

            $this->dbConnection->commit();
            http_response_code(200);
            return json_encode([
                'status' => '200',
                'message' => 'Settings updated successfully'
            ]);

        } catch (\PDOException $e) {
            $this->dbConnection->rollBack();

            $this->logger->logMessage([
                'currentDateTime' => date('Y-m-d H:i:s'),
                'file' => __CLASS__,
                'function' => __FUNCTION__,
                'message' => $e->getMessage(),
                'args' => null,
                'stackTrace' => print_r(debug_backtrace(), true),   
                'type' => 'Error',
                'category' => 'Config'
            ]);

            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }
}
<?php
namespace Src\Model;
use Src\System\EncryptionService;

class ConfigModel
{
    private $dbConnection;
    private $logger;
    private $availableFeatures;
    private $encryptionService;

    /**
     * ConfigModel constructor
     *
     * @param object $dbConnection Database connection object
     */
    public function __construct($dbConnection, $availableFeatures)
    {
        $this->dbConnection = $dbConnection;
        $this->logger = new LogModel($this->dbConnection);
        $this->availableFeatures = $availableFeatures;
        $this->encryptionService = new EncryptionService();
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

            $encryptedConfigs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $decryptedConfigs = [];

            foreach ($encryptedConfigs as $config) {

                $decryptedValue = $this->encryptionService->decrypt(
                    $config['ConfigValue'],
                    $config['Config_IV'],
                    $config['Config_Tag']
                );

                $decryptedConfigs[] = [
                    'ConfigDescription' => $config['ConfigDescription'],
                    'ConfigValue' => $decryptedValue
                ];
            }

            http_response_code(200);
            return json_encode($decryptedConfigs);

        } catch (\Exception $e) {

            $this->logger->logMessage([
                'currentDateTime' => date('Y-m-d H:i:s'),
                'file' => __CLASS__,
                'function' => __FUNCTION__,
                'message' => 'Error in ConfigModel::getConfigs: ' . $e->getMessage(),
                'args' => null,
                'stackTrace' => print_r(debug_backtrace(), true),   
                'type' => 'Error',
                'category' => 'Config'
            ]);

            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'An error occurred processing your request'
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

                /* CONFIG FEATURE GATING */
                if (!$this->availableFeatures['Config View'])
                    continue;

                /* STOCK CONTROL FEATURE GATING */
                if (!$this->availableFeatures['Stock Control'] && $itemConfig['ConfigDescription'] === 'StockControlEnabled')
                    continue;

                $configValueEncryptedData = $this->encryptionService->encrypt($itemConfig['ConfigValue']);
                $statement = $this->dbConnection->prepare('UPDATE Configuration SET ConfigValue = :configValue, Config_IV = :configIV, Config_Tag = :configTag
                                                            WHERE ConfigDescription = :configDescription');
                                                            
                $statement->execute([
                    'configValue' => $configValueEncryptedData['encrypted'],
                    'configIV' => $configValueEncryptedData['iv'],
                    'configTag' => $configValueEncryptedData['tag'],
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

        } catch (\Exception $e) {
            $this->dbConnection->rollBack();

            $this->logger->logMessage([
                'currentDateTime' => date('Y-m-d H:i:s'),
                'file' => __CLASS__,
                'function' => __FUNCTION__,
                'message' => 'Error in ConfigModel::updateConfig: ' . $e->getMessage(),
                'args' => null,
                'stackTrace' => print_r(debug_backtrace(), true),   
                'type' => 'Error',
                'category' => 'Config'
            ]);

            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'An error occurred processing your request'
            ]);
        }
    }
}
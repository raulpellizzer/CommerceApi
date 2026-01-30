<?php
namespace Src\Model;

class CryptoModel
{
    private $dbConnection;

    /**
     * CryptoModel constructor
     *
     * @param object $dbConnection Database connection object
     */
    public function __construct($dbConnection)
    {
        $this->dbConnection = $dbConnection;
        $this->logger = new LogModel($this->dbConnection);
    }

    /**
     * Retrieve encryption keys
     *
     * @return array Array of bits
     */
    public function getKeys() {
        try {
            
            $sql = 'SELECT * FROM UsefulKeys';

            $stmt = $this->dbConnection->prepare($sql);
            $res = $stmt->execute();

            if (!$res) {
                http_response_code(400);
                return json_encode([
                    'status' => '400',
                    'message' => 'Error fetching keys'
                ]);
            }

            http_response_code(200);
            return json_encode($stmt->fetchAll(\PDO::FETCH_ASSOC));

        } catch (\Exception $e) {

            $this->logger->logMessage([
                'currentDateTime' => date('Y-m-d H:i:s'),
                'file' => __CLASS__,
                'function' => __FUNCTION__,
                'message' => $e->getMessage(),
                'args' => null,
                'stackTrace' => print_r(debug_backtrace(), true),   
                'type' => 'Error',
                'category' => 'Crypto'
            ]);

            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }
}
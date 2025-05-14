<?php
namespace Src\Model;

class LogModel
{
    private $dbConnection;

    /**
     * LogModel constructor
     *
     * @param object $dbConnection Database connection object
     */
    public function __construct($dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    /**
     * Log a message in the database
     *
     * @return string JSON response
     */
    public function logMessage($logData)
    {
        try {
            $stmt = $this->dbConnection->prepare('INSERT INTO Logs (ExecutionTime, File, Function, Message, Args, StackTrace, Type, Category) VALUES (:logDate, :fileName, :functionName, :logMessage, :args, :stackTrace, :type, :category)');
            $res = $stmt->execute([
                'logDate' => $logData['currentDateTime'],
                'fileName' => $logData['file'],
                'functionName' => $logData['function'],
                'logMessage' => $logData['message'],
                'args' => $logData['args'],
                'stackTrace' => $logData['stackTrace'],
                'type' => $logData['type'],
                'category' => $logData['category']
            ]);

            if (!$res) {
                http_response_code(400);
                return json_encode([
                    'status' => '400',
                    'message' => 'Error creating log entry'
                ]);
            } else {
                http_response_code(200);
                return json_encode([
                    'status' => '200',
                    'message' => 'Log entry created successfully'
                ]);
            }

        } catch (\PDOException $e) {
            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'Error creating log entry: ' . $e->getMessage()
            ]);
        }
    }
}
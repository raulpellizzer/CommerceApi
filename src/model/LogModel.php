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
    public function logMessage()
    {
        try {
            $logData = json_decode(file_get_contents("php://input"), true);

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
                return json_encode([
                    'status' => '400',
                    'message' => 'Error creating log entry'
                ]);
            } else {
                return json_encode([
                    'status' => '200',
                    'message' => 'Log entry created successfully'
                ]);
            }

        } catch (\PDOException $e) {
            return json_encode([
                'status' => '500',
                'message' => 'Error creating log entry: ' . $e->getMessage()
            ]);
        }
    }
}
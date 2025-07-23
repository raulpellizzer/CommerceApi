<?php
namespace Src\Model;

class ClientModel
{
    private $dbConnection;
    private $availableFeatures;

    public function __construct($dbConnection, $availableFeatures)
    {
        $this->dbConnection = $dbConnection;
        $this->availableFeatures = $availableFeatures;
        $this->logger = new LogModel($this->dbConnection);
    }

    /**
     * Create a new client in the database
     *
     * @return string JSON response
     */
    public function createClients()
    {
        try {
            $_POST = json_decode(file_get_contents("php://input"), true);

            if (isset($_POST['Clients'])) {
                $clients = $_POST['Clients'];
                
                if (count($clients) === 1) {
                    $clientExists = $this->checkClientExists($products[0]['Name']);
                    if ($clientExists) {
                        http_response_code(400);
                        return json_encode([
                            'status' => '400',
                            'message' => 'Client already registered'
                        ]);
                    }
                }

                foreach ($clients as $client) {

                    $statement = $this->dbConnection->prepare('INSERT INTO Clients (Name, Address, PhoneNumber)
                    VALUES (:name, :address, :phonenumber)');

                    $res = $statement->execute([
                        'name' => $client['Name'],
                        'address' => $client['Address'],
                        'phonenumber' => $client['PhoneNumber'],
                    ]);

                    if ($res) {
                        $this->logger->logMessage([
                            'currentDateTime' => date('Y-m-d H:i:s'),
                            'file' => __CLASS__,
                            'function' => __FUNCTION__,
                            'message' => 'Creating Client',
                            'args' => 'name: ' . $client['Name'] . ', address: ' . $client['Address'] . ', phoneNumber: ' . $client['PhoneNumber'],
                            'stackTrace' => null,
                            'type' => 'Info',
                            'category' => 'Client'
                        ]);
                    }
                }

                http_response_code(201);
                return json_encode([
                    'status' => '201',
                    'message' => 'Clients created successfully'
                ]);

            } else {

                $this->logger->logMessage([
                    'currentDateTime' => date('Y-m-d H:i:s'),
                    'file' => __CLASS__,
                    'function' => __FUNCTION__,
                    'message' => 'Creating Client - Bad request 400',
                    'args' => '_POST -> clients not present',
                    'stackTrace' => null,
                    'type' => 'Error',
                    'category' => 'Client'
                ]);

                http_response_code(400);
                return json_encode([
                    'status' => '400',
                    'message' => 'Client bad request'
                ]);
            }

        } catch (\Exception $e) {

            $this->logger->logMessage([
                'currentDateTime' => date('Y-m-d H:i:s'),
                'file' => __CLASS__,
                'function' => __FUNCTION__,
                'message' => $e->getMessage(),
                'args' => null,
                'stackTrace' => print_r(debug_backtrace(), true),   
                'type' => 'Error',
                'category' => 'Client'
            ]);

            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Checks if client(name) already exists in the database
     *
     * @return string JSON response
     */
    public function checkClientExists($name) 
    {
        try {
            $statement = $this->dbConnection->prepare('SELECT * FROM Clients WHERE Name = :name');
            $statement->execute([
                'name' => $name
            ]);
            return $statement->rowCount() > 0;

        } catch (\Exception $e) {
            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }



}
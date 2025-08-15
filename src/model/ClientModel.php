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
     * Get all clients from the database
     *
     * @return array Array of clients
     */
    public function getClients()
    {
        try {
            $sql = 'SELECT * FROM Clients';

            $stmt = $this->dbConnection->prepare($sql);
            $res = $stmt->execute();

            if (!$res) {
                http_response_code(400);
                return json_encode([
                    'status' => '400',
                    'message' => 'Error fetching clients'
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

                /* CLIENT FEATURE GATING */
                if (!$this->availableFeatures['Client Module']) {
                    http_response_code(403);
                    return json_encode([
                        'status' => '403',
                        'message' => 'Client module not available for current plan'
                    ]);
                }
                
                if (count($clients) === 1) {
                    $clientExists = $this->checkClientExists($clients[0]['Name']);
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

    /**
     * Update an existing client in the database
     *
     * @return string JSON response
     */
    public function updateClients()
    {
        try {
            $_POST = json_decode(file_get_contents("php://input"), true);

            if (isset($_POST['Clients'])) {
                $clients = $_POST['Clients'];

                /* CLIENT FEATURE GATING */
                if (!$this->availableFeatures['Client Module']) {
                    http_response_code(403);
                    return json_encode([
                        'status' => '403',
                        'message' => 'Client module not available for current plan'
                    ]);
                }

                foreach ($clients as $client) {

                    $statement = $this->dbConnection->prepare('UPDATE Clients SET Address = :address, PhoneNumber = :phoneNumber, Name = :name WHERE ClientId = :clientID');
                    $res = $statement->execute([
                        'address' => $client['Address'],
                        'phoneNumber' => $client['PhoneNumber'],
                        'name' => $client['Name'],
                        'clientID' => $client['ClientId']
                    ]);

                    if ($res) {
                        $this->logger->logMessage([
                            'currentDateTime' => date('Y-m-d H:i:s'),
                            'file' => __CLASS__,
                            'function' => __FUNCTION__,
                            'message' => 'Updating Client Data',
                            'args' => 'ClientId: ' . $client['ClientId'] . ', address: ' . $client['Address'] . ', phoneNumber: ' . $client['PhoneNumber'] . ', name: ' . $client['Name'],
                            'stackTrace' => null,
                            'type' => 'Info',
                            'category' => 'Client'
                        ]);
                    }
                }

                http_response_code(201);
                return json_encode([
                    'status' => '201',
                    'message' => 'Clients updated successfully'
                ]);

            } else {

                $this->logger->logMessage([
                    'currentDateTime' => date('Y-m-d H:i:s'),
                    'file' => __CLASS__,
                    'function' => __FUNCTION__,
                    'message' => 'Updating Client - Bad request 400',
                    'args' => '_POST -> clients not present',
                    'stackTrace' => null,
                    'type' => 'Error',
                    'category' => 'Client'
                ]);

                http_response_code(400);
                return json_encode([
                    'status' => '400',
                    'message' => 'Client already exists'
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
     * Delete a client from the database
     *
     * @return string JSON response
     */
    public function deleteClients()
    {
        try {
            $_POST = json_decode(file_get_contents("php://input"), true);

            if (isset($_POST['Clients'])) {
                $clients = $_POST['Clients'];

                /* CLIENT FEATURE GATING */
                if (!$this->availableFeatures['Client Module']) {
                    http_response_code(403);
                    return json_encode([
                        'status' => '403',
                        'message' => 'Client module not available for current plan'
                    ]);
                }

                foreach ($clients as $client) {

                    $statement = $this->dbConnection->prepare('DELETE FROM Clients WHERE ClientId = :clientId');
                    $res = $statement->execute([
                        'clientId' => $client['ClientId']
                    ]);

                    if ($res) {
                        $this->logger->logMessage([
                            'currentDateTime' => date('Y-m-d H:i:s'),
                            'file' => __CLASS__,
                            'function' => __FUNCTION__,
                            'message' => 'Deleting Client',
                            'args' => 'clientId: ' . $client['ClientId'] . ', name: ' . $client['Name'],
                            'stackTrace' => null,
                            'type' => 'Info',
                            'category' => 'Client'
                        ]);
                    }
                }

                http_response_code(200);
                return json_encode([
                    'status' => '200',
                    'message' => 'Clients deleted successfully'
                ]);

            } else {

                $this->logger->logMessage([
                    'currentDateTime' => date('Y-m-d H:i:s'),
                    'file' => __CLASS__,
                    'function' => __FUNCTION__,
                    'message' => 'Deleting Client - Bad request 400',
                    'args' => '_POST -> clients not present',
                    'stackTrace' => null,
                    'type' => 'Error',
                    'category' => 'Client'
                ]);

                http_response_code(400);
                return json_encode([
                    'status' => '400',
                    'message' => 'Bad request'
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
     * Get client sales data
     *
     * @param string $clientId The ID of the client
     * @return string JSON response
     */
    public function getClientSalesByClientId($clientId)
    {
        try {
            $sql = 'SELECT s.SaleId, c.Name, s.PaymentMethod, s.PaymentInstallment, s.Total, s.SaleDate  ' .
                'FROM Sales s ' .
                'INNER JOIN Clients c on s.ClientId = c.ClientId ' .
                'WHERE s.ClientId = :clientId ' . 
                'ORDER BY s.SaleDate ASC';

            $statement = $this->dbConnection->prepare($sql);
            $statement->execute(['clientId' => $clientId]);

            if ($statement->rowCount() > 0) {
                http_response_code(200);
                return json_encode($statement->fetchAll(\PDO::FETCH_ASSOC));
            } else {
                http_response_code(404);
                return json_encode([
                    'status' => '404',
                    'message' => 'No sales found for this client'
                ]);
            }

        } catch (\Exception $e) {
            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get client sales details by sale ID
     *
     * @param string $saleId The ID of the sale
     * @return string JSON response
     */
    public function getClientSalesDetailsBySaleId($saleId)
    {
        try {
            $sql = 'SELECT s.SaleId, c.Name as ClientName, s.PaymentMethod, s.PaymentInstallment, s.Total, s.SaleDate,  ' .
                'sd.ProductId, p.BarCode, sd.ProductQuantity, p.Name as ProductName, p.Price as PricePerUnit ' .
                'FROM Sales as s ' .
                'INNER JOIN SaleDetails sd on s.SaleId = sd.SaleId ' . 
                'INNER JOIN Products p on sd.ProductId = p.ProductId ' . 
                'INNER JOIN Clients c on s.ClientId = c.ClientId ' . 
                'WHERE s.SaleId = :saleId ';

            $statement = $this->dbConnection->prepare($sql);
            $statement->execute(['saleId' => $saleId]);

            if ($statement->rowCount() > 0) {
                http_response_code(200);
                return json_encode($statement->fetchAll(\PDO::FETCH_ASSOC));
            } else {
                http_response_code(404);
                return json_encode([
                    'status' => '404',
                    'message' => 'No sales found for this id'
                ]);
            }

        } catch (\Exception $e) {
            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }
}
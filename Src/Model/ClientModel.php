<?php
namespace Src\Model;
use Src\System\EncryptionService;

class ClientModel
{
    private $dbConnection;
    private $availableFeatures;
    private $encryptionService;

    public function __construct($dbConnection, $availableFeatures)
    {
        $this->dbConnection = $dbConnection;
        $this->availableFeatures = $availableFeatures;
        $this->logger = new LogModel($this->dbConnection);
        $this->encryptionService = new EncryptionService();
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

            $encryptedClients = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $decryptedClients = [];

            foreach ($encryptedClients as $client) {

                $decryptedName = $this->encryptionService->decrypt(
                    $client['Name'],
                    $client['Name_IV'],
                    $client['Name_Tag']
                );

                $decryptedAddress = $this->encryptionService->decrypt(
                    $client['Address'],
                    $client['Address_IV'],
                    $client['Address_Tag']
                );

                $decryptedNeighborhood = $this->encryptionService->decrypt(
                    $client['Neighborhood'],
                    $client['Neighborhood_IV'],
                    $client['Neighborhood_Tag']
                );

                $decryptedExtras = $this->encryptionService->decrypt(
                    $client['Extras'],
                    $client['Extras_IV'],
                    $client['Extras_Tag']
                );

                $decryptedPhoneNumber = $this->encryptionService->decrypt(
                    $client['PhoneNumber'],
                    $client['PhoneNumber_IV'],
                    $client['PhoneNumber_Tag']
                );

                $decryptedClients[] = [
                    'ClientId' => $client['ClientId'],
                    'Name' => $decryptedName,
                    'Address' => $decryptedAddress,
                    'Neighborhood' => $decryptedNeighborhood,
                    'Extras' => $decryptedExtras,
                    'PhoneNumber' => $decryptedPhoneNumber
                ];
            }

            http_response_code(200);
            return json_encode($decryptedClients);

        } catch (\Exception $e) {

            $this->logger->logMessage([
                'currentDateTime' => date('Y-m-d H:i:s'),
                'file' => __CLASS__,
                'function' => __FUNCTION__,
                'message' => 'Error in ClientModel::getClients: ' . $e->getMessage(),
                'args' => null,
                'stackTrace' => print_r(debug_backtrace(), true),   
                'type' => 'Error',
                'category' => 'Client'
            ]);

            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'An error occurred processing your request'
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

                foreach ($clients as $client) {

                    $clientNameEncryptedData = $this->encryptionService->encrypt($client['Name']);
                    $clientAddressEncryptedData = $this->encryptionService->encrypt($client['Address']);
                    $clientNeighborhoodEncryptedData = $this->encryptionService->encrypt($client['Neighborhood']);
                    $clientExtrasEncryptedData = $this->encryptionService->encrypt($client['Extras']);
                    $clientPhoneNumberEncryptedData = $this->encryptionService->encrypt($client['PhoneNumber']);

                    $statement = $this->dbConnection->prepare('INSERT INTO Clients (Name, Name_IV, Name_Tag, Address, Address_IV, Address_Tag, Neighborhood, 
                        Neighborhood_IV, Neighborhood_Tag, Extras, Extras_IV, Extras_Tag, PhoneNumber, PhoneNumber_IV, PhoneNumber_Tag)
                        VALUES (:name, :nameIV, :nameTag, :address, :addressIV, :addressTag, :neighborhood, :neighborhoodIV, :neighborhoodTag, :extras, :extrasIV, :extrasTag, :phonenumber, :phonenumberIV, :phonenumberTag)');

                    $res = $statement->execute([
                        'name' => $clientNameEncryptedData['encrypted'],
                        'nameIV' => $clientNameEncryptedData['iv'],
                        'nameTag' => $clientNameEncryptedData['tag'],
                        'address' => $clientAddressEncryptedData['encrypted'],
                        'addressIV' => $clientAddressEncryptedData['iv'],
                        'addressTag' => $clientAddressEncryptedData['tag'],
                        'neighborhood' => $clientNeighborhoodEncryptedData['encrypted'],
                        'neighborhoodIV' => $clientNeighborhoodEncryptedData['iv'],
                        'neighborhoodTag' => $clientNeighborhoodEncryptedData['tag'],
                        'extras' => $clientExtrasEncryptedData['encrypted'],
                        'extrasIV' => $clientExtrasEncryptedData['iv'],
                        'extrasTag' => $clientExtrasEncryptedData['tag'],
                        'phonenumber' => $clientPhoneNumberEncryptedData['encrypted'],
                        'phonenumberIV' => $clientPhoneNumberEncryptedData['iv'],
                        'phonenumberTag' => $clientPhoneNumberEncryptedData['tag'],
                    ]);

                    if ($res) {
                        $this->logger->logMessage([
                            'currentDateTime' => date('Y-m-d H:i:s'),
                            'file' => __CLASS__,
                            'function' => __FUNCTION__,
                            'message' => 'Creating Client',
                            'args' => 'name: ' . $client['Name'] . ', address: ' . $client['Address'] .  ', neighborhood: ' . $client['Neighborhood'] . ', extras: ' . $client['Extras'] . ', phoneNumber: ' . $client['PhoneNumber'],
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
                'message' => 'Error in ClientModel::createClients: ' . $e->getMessage(),
                'args' => null,
                'stackTrace' => print_r(debug_backtrace(), true),   
                'type' => 'Error',
                'category' => 'Client'
            ]);

            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'An error occurred processing your request'
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

                    $clientNameEncryptedData = $this->encryptionService->encrypt($client['Name']);
                    $clientAddressEncryptedData = $this->encryptionService->encrypt($client['Address']);
                    $clientNeighborhoodEncryptedData = $this->encryptionService->encrypt($client['Neighborhood']);
                    $clientExtrasEncryptedData = $this->encryptionService->encrypt($client['Extras']);
                    $clientPhoneNumberEncryptedData = $this->encryptionService->encrypt($client['PhoneNumber']);

                    $statement = $this->dbConnection->prepare('UPDATE Clients SET Name = :name, Name_IV = :name_iv, Name_Tag = :name_tag, Address = :address, Address_IV = :address_iv, 
                        Address_Tag = :address_tag, Neighborhood = :neighborhood, Neighborhood_IV = :neighborhood_iv, Neighborhood_Tag = :neighborhood_tag, Extras = :extras, Extras_IV = :extras_iv, 
                        Extras_Tag = :extras_tag, PhoneNumber = :phoneNumber, PhoneNumber_IV = :phoneNumber_iv, PhoneNumber_Tag = :phoneNumber_tag 
                            WHERE ClientId = :clientID');

                    $res = $statement->execute([
                        'name' => $clientNameEncryptedData['encrypted'],
                        'name_iv' => $clientNameEncryptedData['iv'],
                        'name_tag' => $clientNameEncryptedData['tag'],
                        'address' => $clientAddressEncryptedData['encrypted'],
                        'address_iv' => $clientAddressEncryptedData['iv'],
                        'address_tag' => $clientAddressEncryptedData['tag'],
                        'neighborhood' => $clientNeighborhoodEncryptedData['encrypted'],
                        'neighborhood_iv' => $clientNeighborhoodEncryptedData['iv'],
                        'neighborhood_tag' => $clientNeighborhoodEncryptedData['tag'],
                        'extras' => $clientExtrasEncryptedData['encrypted'],
                        'extras_iv' => $clientExtrasEncryptedData['iv'],
                        'extras_tag' => $clientExtrasEncryptedData['tag'],
                        'phoneNumber' => $clientPhoneNumberEncryptedData['encrypted'],
                        'phoneNumber_iv' => $clientPhoneNumberEncryptedData['iv'],
                        'phoneNumber_tag' => $clientPhoneNumberEncryptedData['tag'],
                        'clientID' => $client['ClientId']
                    ]);

                    if ($res) {
                        $this->logger->logMessage([
                            'currentDateTime' => date('Y-m-d H:i:s'),
                            'file' => __CLASS__,
                            'function' => __FUNCTION__,
                            'message' => 'Updating Client Data',
                            'args' => 'ClientId: ' . $client['ClientId'] . ', address: ' . $client['Address'] . ', neighborhood: ' . $client['Neighborhood'] . ', extras: ' . $client['Extras'] . ', phoneNumber: ' . $client['PhoneNumber'] . ', name: ' . $client['Name'],
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
                'message' => 'Error in ClientModel::updateClients: ' . $e->getMessage(),
                'args' => null,
                'stackTrace' => print_r(debug_backtrace(), true),   
                'type' => 'Error',
                'category' => 'Client'
            ]);

            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'An error occurred processing your request'
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
                'message' => 'Error in ClientModel::deleteClients: ' . $e->getMessage(),
                'args' => null,
                'stackTrace' => print_r(debug_backtrace(), true),   
                'type' => 'Error',
                'category' => 'Client'
            ]);

            http_response_code(500);
            return json_encode([
                'status' => '500',
                'message' => 'An error occurred processing your request'
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

            $sql = 'SELECT s.SaleId, c.Name, c.Name_IV, c.Name_Tag, s.PaymentMethod, s.PaymentInstallment, s.Total, s.SaleDate  ' .
                'FROM Sales s ' .
                'INNER JOIN Clients c on s.ClientId = c.ClientId ' .
                'WHERE s.ClientId = :clientId ' . 
                'ORDER BY s.SaleDate ASC';

            $statement = $this->dbConnection->prepare($sql);
            $statement->execute(['clientId' => $clientId]);
            $clientRows = $statement->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($clientRows as &$row) {
                $decryptedName = $this->encryptionService->decrypt(
                    $row['Name'],
                    $row['Name_IV'],
                    $row['Name_Tag']
                );
                $row['Name'] = $decryptedName;
            }

            if ($statement->rowCount() > 0) {
                http_response_code(200);
                return json_encode($clientRows);
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
                'message' => 'An error occurred processing your request'
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
            $sql = 'SELECT s.SaleId, c.Name as ClientName, c.Name_IV, c.Name_Tag, s.PaymentMethod, s.PaymentInstallment, s.Total, s.SaleDate,  ' .
                'sd.ProductId, p.BarCode, sd.ProductQuantity, p.Name as ProductName, p.SalePrice as SalePricePerUnit, p.PurchasePrice as PurchasePricePerUnit ' .
                'FROM Sales as s ' .
                'INNER JOIN SaleDetails sd on s.SaleId = sd.SaleId ' . 
                'INNER JOIN Products p on sd.ProductId = p.ProductId ' . 
                'INNER JOIN Clients c on s.ClientId = c.ClientId ' . 
                'WHERE s.SaleId = :saleId ';

            $statement = $this->dbConnection->prepare($sql);
            $statement->execute(['saleId' => $saleId]);

            if ($statement->rowCount() > 0) {
                $salesDetailsRows = $statement->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($salesDetailsRows as &$row) {
                    $decryptedClientName = $this->encryptionService->decrypt(
                        $row['ClientName'],
                        $row['Name_IV'],
                        $row['Name_Tag']
                    );
                    $row['ClientName'] = $decryptedClientName;
                }

                http_response_code(200);
                return json_encode($salesDetailsRows);
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
                'message' => 'An error occurred processing your request'
            ]);
        }
    }
}
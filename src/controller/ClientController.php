<?php
namespace Src\Controller;
use Src\Model\ClientModel;

/**
     * ClientController constructor
     *
     * @param string $requestMethod The HTTP request method (GET, POST, PUT, DELETE)
     * @param object $clientModel ClientModel object
     * @param object $dbConnection Database connection object
     * @param array $availableFeatures Available features for tenant
     * @param string $clientResource The specific client resource being accessed, if any
     * @param string|null $clientId The ID of the client being accessed (if applicable)
     */
class ClientController 
{
    private $requestMethod;
    private $clientModel;
    private $clientResource;
    private $clientId;

    public function __construct($requestMethod, $dbConnection, $availableFeatures, $clientResource, $clientId)
    {
        $this->requestMethod = $requestMethod;
        $this->clientModel = new ClientModel($dbConnection, $availableFeatures);
        $this->clientResource = $clientResource;
        $this->clientId = $clientId;
    }

    /**
     * Process the incoming /clients request
     *
     * @return string JSON response
     */
    public function processRequest()
    {
        if ($this->clientResource === 'getclientsales') {
            if ($this->clientId === null) {
                return json_encode([
                    'status' => '400',
                    'message' => 'Client ID is required for this resource'
                ]);
            }
            $response = $this->clientModel->getClientSalesByClientId($this->clientId);
            return $response;

        } else {
            switch ($this->requestMethod) {
                case 'GET':
                    return $this->clientModel->getClients();

                case 'POST':
                    return $this->clientModel->createClients();

                case 'PUT':
                    return $this->clientModel->updateClients();

                case 'DELETE':
                    return $this->clientModel->deleteClients();

                default:
                    return json_encode([
                        'status' => '400',
                        'message' => 'Invalid request method'
                    ]);
            }
        }
    }
}
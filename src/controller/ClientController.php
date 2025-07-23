<?php
namespace Src\Controller;
use Src\Model\ClientModel;

/**
     * ClientController constructor
     *
     * @param string $requestMethod The HTTP request method (GET, POST, PUT, DELETE)
     * @param object $clientModel ClientModel object
     */
class ClientController 
{
    private $requestMethod;
    private $clientModel;

    public function __construct($requestMethod, $dbConnection, $availableFeatures)
    {
        $this->requestMethod = $requestMethod;
        $this->clientModel = new ClientModel($dbConnection, $availableFeatures);
    }

    /**
     * Process the incoming /clients request
     *
     * @return string JSON response
     */
    public function processRequest()
    {
        switch ($this->requestMethod) {
            case 'GET':
                return $this->clientModel->getAllClients();

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
<?php
namespace Src\Model;

class SettingsModel
{
    private $dbConnection;

    /**
     * SettingsModel constructor
     *
     * @param object $dbConnection Database connection object
     */
    public function __construct($dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    /**
     * Get the settings for the application
     *
     * @return string JSON response
     */
    public function getApiSettings()
    {
        $query = "SELECT * FROM ApiSettings";
        $stmt = $this->dbConnection->prepare($query);
        $stmt->execute();
        $settings = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return json_encode([
            'status' => '200',
            'data' => $settings
        ]);
    }
}
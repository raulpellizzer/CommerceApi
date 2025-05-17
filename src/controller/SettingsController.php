<?php
namespace Src\Controller;
use Src\Model\SettingsModel;

/**
 * SettingsController class
 *
 * This class handles the settings-related operations for the application.
 * */
class SettingsController
{
    private $dbConnection;
    private $settingsModel;

    /**
     * SettingsController constructor
     *
     * @param object $dbConnection Database connection object
     */
    public function __construct($dbConnection)
    {
        $this->dbConnection = $dbConnection;
        $this->settingsModel = new SettingsModel($dbConnection);
    }

    /**
     * Get the settings for the application
     *
     * @return string JSON response
     */
    public function getApiSettings()
    {
        return $this->settingsModel->getApiSettings();
    }
}

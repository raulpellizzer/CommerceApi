<?php
use Src\System\DatabaseConnector;
require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$dbConnection = new DatabaseConnector();
$dbConnection = $dbConnection->getConnection();

echo var_dump($dbConnection);
if ($dbConnection) {
    echo "Database connection established successfully.\n";
} else {
    echo "Failed to connect to the database.\n";
}
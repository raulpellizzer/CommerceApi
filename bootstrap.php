<?php
use Src\System\DatabaseConnector;
require 'vendor/autoload.php';
//require_once __DIR__ . '/Src/System/DatabaseConnector.php'; // required for web server if autoload is not working

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$dbConnection = new DatabaseConnector();
$dbConnection = $dbConnection->getConnection();
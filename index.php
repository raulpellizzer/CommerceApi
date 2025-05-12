<?php
	header("Access-Control-Allow-Origin: *");
	header("Content-Type: application/json; charset=UTF-8");
	header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
	header("Access-Control-Max-Age: 3600");
	header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

	require "bootstrap.php";
	use Src\Controller\ApiController;

	$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	$uri = explode( '/', $uri );

	// Endpoints must start with /CommerceApi
	if ($uri[1] !== 'CommerceApi') {
		header("HTTP/1.1 404 Not Found");
		exit();
	}

	$requestMethod = $_SERVER["REQUEST_METHOD"];

	$apiController = new ApiController($dbConnection);
	$response = $apiController->processRequest($requestMethod, $uri);

	echo $response;
	return $response;
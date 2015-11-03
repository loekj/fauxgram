<?php

use Controller\APIController;
use Database\APIDatabase;

require_once __DIR__.'/../vendor/autoload.php';

# Requests from the same server don't have a HTTP_ORIGIN header
if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
    $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
}


try {
	# Create database layer object
	$db_obj = new APIDatabase();

	# Create API handler object
    $API = new APIController($_REQUEST, $_SERVER['HTTP_ORIGIN'], $db_obj);
    echo $API->processAPI();
} catch (Exception $e) {

	# Catch any uncaught exception
	header("HTTP/1.1 500 Internal Server Error");
    echo json_encode($e->getMessage());
}
?>
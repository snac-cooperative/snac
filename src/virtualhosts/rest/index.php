<?php
/**
 * Landing page of public api interface
 *
 * Creates an instance of the Rest class and runs it
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 * the Regents of the University of California
 */
 error_reporting(E_ALL);

// SNAC Autoload function
function snac_autoload ($pClassName) {
    include("../../" . str_replace("\\", "/", $pClassName) . ".php");
}
spl_autoload_register("snac_autoload");

// Namespace shortcuts
use \snac\client\rest\Rest as Rest;


// Get the request body for processing
$input = file_get_contents("php://input");
if ($input == null) {
	// Header for JSON
	header("Content-Type: application/json");
	echo "{\"error\": \"Unknown request.\"}\n";
	exit(1);
}

// Parse the JSON input
$jsonInput = json_decode($input,true);
if ($jsonInput == null) {
	// Header for JSON
	header("Content-Type: application/json");
	echo "{\"error\": \"Could not parse JSON request.\"}\n";
	exit(1);
}

// Instantiate and run the server
$server = new Rest($jsonInput);
$server->run();

// Return the content type and output of the server
foreach ($server->getResponseHeaders() as $header)
	header($header);
echo $server->getResponse();

// Exit
exit();

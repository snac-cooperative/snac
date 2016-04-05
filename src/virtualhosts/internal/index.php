<?php
/**
 * Landing page of internal server api
 *
 * Creates an instance of the Server class and runs it
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

/**
 * Load and instantiate the server
 */
include ("../../../vendor/autoload.php");


/**
 * Load dependencies
 */
include ("../../../vendor/autoload.php");

/**
 * If debug is on, turn on error reporting
 */
if (\snac\Config::$DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}


// Namespace shortcuts
use \snac\server\Server as Server;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

// Set up the global log stream
$log = new StreamHandler(\snac\Config::$LOG_DIR . \snac\Config::$SERVER_LOGFILE, Logger::DEBUG);

try {
    // Get the request body for processing
    $input = file_get_contents("php://input");
    if ($input == null) {
        throw new \snac\exceptions\SNACInputException("No input given to the server");
    }
    
    // Parse the JSON input
    $jsonInput = json_decode($input, true);
    if ($jsonInput == null) {
        throw new \snac\exceptions\SNACInputException("Could not parse input");
    }
    
    // Instantiate and run the server
    $server = new Server($jsonInput);
    $server->run();
    
    // Return the content type and output of the server
    foreach ($server->getResponseHeaders() as $header)
        header($header);
    echo $server->getResponse();
} catch (Exception $e) {
    header("Content-Type: application/json");
    die($e);
}
// Exit
exit();

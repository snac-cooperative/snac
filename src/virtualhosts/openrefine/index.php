<?php
/**
 * Landing page for the OpenRefine endpoint
 *
 * Loads the input, instantiates, and runs the OpenRefine client
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

/**
 * Load and instantiate the rest api
 */
include ("../../../vendor/autoload.php");

// Namespace shortcuts
use \snac\client\openrefine\OpenRefine as OpenRefine;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

// Set up the global log stream
$loglevel = Logger::WARNING;
if (\snac\Config::$DEBUG_MODE) {
    $loglevel = Logger::DEBUG;
}
$log = new StreamHandler(\snac\Config::$LOG_DIR . \snac\Config::$REST_LOGFILE, $loglevel);

try {
    // Get the request body for processing
    $input = $_REQUEST;
    foreach ($input as &$part) {
        if ($decode = json_decode($part, true))
            $part = $decode;
    }
    // Be correct with foreach pass by reference
    unset($part);
    
    // Get the request body for processing
    $jsonInput = null;
    $inputbody = file_get_contents("php://input");
    if ($inputbody != null) {
        // Parse the JSON input
        $jsonInput = json_decode($inputbody, true);
    } 
    
    // Instantiate and run the server
    $server = new OpenRefine($input, $jsonInput);
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

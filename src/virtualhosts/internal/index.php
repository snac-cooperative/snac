<?php
/**
 * Landing page of internal server api
 *
 * Creates an instance of the Server class and runs it
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 * the Regents of the University of California
 */

// SNAC Autoload function
function snac_autoload ($pClassName) {
    include("../../" . str_replace("\\", "/", $pClassName) . ".php");
}
spl_autoload_register("snac_autoload");

// Namespace shortcuts
use \snac\server\Server as Server;

// Header for JSON
header("Content-Type: application/json");

// Instantiate and run the server
$server = new Server();
$server->run();

// Exit
exit();

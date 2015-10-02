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

// SNAC Autoload function
function snac_autoload ($pClassName) {
    include("../../" . str_replace("\\", "/", $pClassName) . ".php");
}
spl_autoload_register("snac_autoload");

// Namespace shortcuts
use \snac\client\rest\Rest as Rest;

// Instantiate and run the server
$server = new Rest();
$server->run();

// Exit
exit();

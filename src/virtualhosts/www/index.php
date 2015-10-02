<?php
/**
 * Landing page of public web interface
 *
 * Creates an instance of the WebUI class and runs it
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
use \snac\client\webui\WebUI as WebUI;

// Instantiate and run the server
$server = new WebUI();
$server->run();

// Exit
exit();

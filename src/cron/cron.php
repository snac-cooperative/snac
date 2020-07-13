<?php
/**
 * Cron Job Runner 
 *
 * This script will read from the command line and pass arguments to the Cron interface.
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */


/**
 * Load dependencies
 */
include ("../../vendor/autoload.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Namespace shortcuts
use \snac\client\cron\Cron as Cron;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

// Set up the global log stream
$loglevel = Logger::DEBUG;
$log = new StreamHandler(\snac\Config::$LOG_DIR . \snac\Config::$CRON_LOGFILE, $loglevel);

/**
 * Run the Cron client if there was a command, else print the error message and exit
 */
if (isset($argv[1])) {
    $input = array( "command" => $argv[1] );

    $server = new Cron($input);
    $server->run();

    // Return the content type and output of the server
    echo $server->getResponse();

} else {
    echo "Cron Job Script\n".
        "  usage: php cron.php task_name\n\n";
    exit(1);
}
?>

<?php
/**
 * PHPUnit Bootstrap Loader File
 *
 * Loads all dependencies of the tests using Composer's autoloader, then
 * create the log file approprate for the tests.
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

/**
 * Load the dependencies
 */
include ("vendor/autoload.php");

/**
 * Namespace shortcuts
 */
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;


/*
 * Set up the global log stream
 */ 
$log = new StreamHandler(\snac\Config::$LOG_DIR . \snac\Config::$UNITTEST_LOGFILE, Logger::DEBUG);
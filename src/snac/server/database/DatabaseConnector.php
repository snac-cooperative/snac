<?php
/**
 * Database Connector Class File
 *
 * Contains the thin-layer database connector that handles exceptions
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

namespace snac\server\database;

use \snac\Config as Config;

/**
 * Database Connector Class
 * 
 * This class provides a thin layer in front of the standard PHP Postgres library functions, so that
 * correct error handling may happen throughout the code.  The methods in this class throw the appropriate SNAC
 * Exception object when something goes wrong during database connection and use.
 * 
 * @author Robbie Hott
 *
 */
class DatabaseConnector {
    
    private $dbHandle = null;
    
    public function __construct() {
        
        // Read the configuration file
        $host = Config::$DATABASE["host"];
        $port = Config::$DATABASE["port"];
        $database = Config::$DATABASE["database"];
        $password = Config::$DATABASE["password"];
        $user = Config::$DATABASE["user"];
        
        try {
            // Try to connect to the database
            $this->dbHandle = \pg_connect("host=$host port=$port dbname=$database user=$user password=$password");

            // If the connection does not throw an exception, but the connector is false, then throw.
            if ($this->dbHandle === false) {
                throw new Exception("Unable to connect to back-end database.");
            }
        } catch (Exception $e) {
            // Replace any exceptions with the SNAC Database Exception and re-throw back out.
            throw new \snac\exceptions\SNACDatabaseException($e->getMessage());
        }
        
    }
    
    
}
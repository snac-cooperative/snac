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
use snac\exceptions\SNACDatabaseException;

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
    
    /**
     * @var \resource Database handle for postgres connection
     */
    private $dbHandle = null;
    
    /**
     * Constructor
     * 
     * Opens the connection to the database on construct
     * 
     * @throws \snac\exceptions\SNACDatabaseException
     */
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
    
    /**
     * Prepare A Statement
     * 
     * Calls php postgres pg_prepare method.  The statement should be named, and the query given.
     *  
     * @param string $statementName Name for the statement (allows multiple prepares)
     * @param string $query Query to prepare (with $1, $2, .. placeholders)
     */
    public function prepare($statementName, $query) {
        try {
            $result = \pg_prepare($this->dbHandle, $statementName, $query);
            
            // Check for error
            if ($result === false) {
                $errorMessage = \pg_last_error($this->dbHandle);
                throw new Exception("Database Prepare Error: " . $errorMessage);
            }
        } catch (\Exception $e) {
            // Replace any exceptions with the SNAC Database Exception and re-throw back out
            throw new \snac\exceptions\SNACDatabaseException($e->getMessage());
        }
    }
    
    /**
     * Execute a prepared database statement
     * 
     * Executes the statement prepared earlier as $statementName, with the given array of values used to fill the
     * placeholders in the prepared statement.  Any values passed in the array will be converted to strings.
     * 
     * @param string $statementName Statement name to execute
     * @param mixed[] $values Parameters to fill the prepared statement (will be cast to string)
     * @throws \snac\exceptions\SNACDatabaseException
     * @return \resource Postgres resource for the result
     */
    public function execute($statementName, $values) {
        try {
            $result = \pg_execute($this->dbHandle, $statementName, $values);
           
            // Check for error
            if ($result === false) {
                $errorMessage = \pg_last_error($this->dbHandle);
                throw new Exception("Database Execute Error: " . $errorMessage);
            }
            
            $resultError = \pg_result_error($result);
            if ($resultError === false) {
                throw new Exception("Database Execute Error: Could not return results -- malformed result");
            } else if (!empty($resultError)) {
                throw new Exception("Database Execute Error: " . $resultError);
            }
            
            return $result;
        } catch (\Exception $e) {
            // Replace any exceptions with the SNAC Database Exception and re-throw back out
            throw new \snac\exceptions\SNACDatabaseException($e->getMessage());
        }
    }

    /**
     * Prepare and Execute a database statement
     *
     * Handles both the prepare and execute stages.
     * 
     * @param string $query Query to prepare (with $1, $2, .. placeholders)
     * @param mixed[] $values Parameters to fill the prepared statement (will be cast to string)
     * @throws \snac\exceptions\SNACDatabaseException
     * @return \resource Postgres resource for the result
     */
    public function query($query, $values) {
        $this->prepare("", $query);
        return $this->execute("", $values);
    }
    
    /**
     * Fetch the next row
     * 
     * Fetches the next row from the given resource and returns it as an associative array.
     * 
     * @param \resource $resource Postgres result resource (From $db->execute())
     * @return string[] Next row from the database as an associative array, or false if no rows to return
     * @throws \snac\exceptions\SNACDatabaseException
     */
    public function fetchRow($resource) {
        try {
            $row = \pg_fetch_assoc($resource); 
            return $row;
        } catch (\Exception $e) {
            // Replace any exceptions with the SNAC Database Exception and re-throw back out
            throw new \snac\exceptions\SNACDatabaseException($e->getMessage());
        }
    }
    
    
}
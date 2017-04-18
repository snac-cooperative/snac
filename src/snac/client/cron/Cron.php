<?php

/**
 * Cron Client File
 *
 * Contains the main Cron class that instantiates and runs Cron Jobs
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2017 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\client\cron;

use \snac\client\util\ServerConnect as ServerConnect;

/**
 * Cron Class
 *
 * This is the main Cron class. It should be instantiated, then the run()
 * method called to run the cron jobs.
 *
 * @author Robbie Hott
 */
class Cron implements \snac\interfaces\ServerInterface {

    /**
     * Input parameters from the querier
     *
     * @var array Associative array of the input query
     */
    private $input = null;

    /**
     * Response text
     *
     * @var string response
     */
    private $response = "";
    
    /**
     * @var \Monolog\Logger $logger the logger for this server
     */
    private $logger;

    /**
     * Constructor
     *
     * Requires the input to the server as an associative array
     *
     * @param array $input Input to the server
     */
    public function __construct($input) {
        global $log;

        $this->input = $input;

        // create a log channel
        $this->logger = new \Monolog\Logger('Cron');
        $this->logger->pushHandler($log);
    }

    /**
     * Run Method
     *
     * Starts the server
     */
    public function run() {

        $user = new \snac\data\User();
        $user->setUserName("system@localhost");
        $user->generateTemporarySession(1);

        $this->logger->addDebug("Creating Server Connection.");
        $this->connect = new ServerConnect($user);
        
        $this->logger->addDebug("Starting System User Session.");
        // Start the session
        $query = [
            "command" => "start_session"
        ];
        $serverResponse = $this->connect->query($query);

        $this->logger->addDebug("Handling input.", $this->input);
        switch($this->input["command"]) {
            case "reports":
                $this->logger->addDebug("Running Report Query.");
                $query = [
                    "command" => "report_general_generate"
                ];
                $serverResponse = $this->connect->query($query);
        } 

        
        // End the session
        $this->logger->addDebug("Ending System User Session.");
        $query = [
            "command" => "end_session"
        ];
        $serverResponse = $this->connect->query($query);

        $this->logger->addDebug("Finished Cron Task.");
        return;
    }

    /**
     * Get Response Headers
     *
     * Returns the headers for the server's return value. This will likely
     * usually be the JSON content header.
     *
     * @return array headers for output
     */
    public function getResponseHeaders() {
        return null; 
    }

    /**
     * Get Return Statement
     *
     * This should compile the Server's response statement. Currently, it returns a
     * JSON-encoded string or other content value that can be echoed to generate the
     * appropriate response. This should usually be JSON, but may be the contents of a file
     * to be downloaded by the user, so we leave it flexible rather than returning an
     * associative array.
     *
     * @return string server response appropriately encoded
     */
    public function getResponse() {
        return $this->response;
    }
}


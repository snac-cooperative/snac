<?php
/**
 * Connect Class Util file
 *
 * Contains the connection class between the clients and server
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\client\util;

/**
 * Server Connect Utility Class
 *
 * Queries the server with the given query (associative array) and returns the backend
 * server's response (as associative array).
 *
 * @author Robbie Hott
 *        
 */
class ServerConnect {

    /**
     *
     * @var string The URL for the backend server
     */
    private $serverURL;

    private $logger = null;

    /**
     * Default constructor
     */
    public function __construct() {
        global $log;

        $this->serverURL = \snac\Config::$INTERNAL_SERVERURL;
        
        // create a log channel
        $this->logger = new \Monolog\Logger('ServerConnect');
        $this->logger->pushHandler($log);
        
    }

    /**
     * Perform Server Query
     *
     * Uses CURL to query the server by converting the given array into JSON, sending that
     * to the back-end server via PUT, then returns the server's response as an associative
     * array.
     *
     * @param array $query Associative array of the query information to send to the
     *        back-end server
     */
    public function query($query) {

        $this->logger->addDebug("Sending the following server query", $query); 
        // Encode the query as json
        $data = json_encode($query);
        
        // Use CURL to send request to the internal server
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->serverURL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, 
                array (
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($data)
                ));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        
        // Return the server response as associative array
        $return = json_decode($response, true);
        if ($return == null) {
            $this->logger->addDebug("Got the following improper server response", array($response));
            return $response;
        }
        
        $this->logger->addDebug("Got the following server response", $return);
        return $return;
    }
}

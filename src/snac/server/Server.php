<?php
/*
 * Server Class File
 *
 * Contains the main server class that instantiates the main server
 *
 * License:
 * 
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and 
 * the Regents of the University of California
 */
namespace snac\server;


/**
 * Server Class
 *
 * This is the main server class.  It should be instantiated, then the run() 
 * method called to start the server running.
 *
 * @author Robbie Hott
 */

class Server implements \snac\interfaces\ServerInterface {
	
	/**
	 * Input parameters from the querier
	 * @var array Associative array of the input query
	 */
	private $input = null;
	
	/**
	 * Headers for response
	 * @var array Response headers
	 */
	private $responseHeaders = array("Content-Type: application/json");
	
	/**
	 * Constructor
	 * 
	 * Requires the input to the server as an associative array
	 * @param array $input Input to the server
	 */
	public function __construct($input) {
		$this->input = $input;
	}

    /**
     * Run Method
     *
     * Starts the server
     */
    public function run() {
        return;
    }
    
    /**
     * Get Response Headers
     * 
     * Returns the headers for the server's return value.  This will likely
     * usually be the JSON content header.
     * @return array headers for output
     */
    public function getResponseHeaders() {
    	return $this->responseHeaders;
    }
    
    /**
     * Get Return Statement
     * 
     * This should compile the Server's response statement.  Currently, it returns a
     * JSON-encoded string or other content value that can be echoed to generate the
     * appropriate response. This should usually be JSON, but may be the contents of a file
     * to be downloaded by the user, so we leave it flexible rather than returning an
     * associative array.
     * 
     * @return string server response appropriately encoded
     */
    public function getResponse() {
    	// TODO: Fill in body
    	
    	$response = array("Generic Response" => "Successfully Queried");
    	return json_encode($response);
    }
}

<?php
/**
 * Web Interface Class File
 *
 * Contains the main web interface class that instantiates the web ui
 *
 * License:
 * 
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and 
 * the Regents of the University of California
 */
namespace snac\client\webui;


use snac\interfaces\ServerInterface;
use \snac\client\util\ServerConnect as ServerConnect;

/**
 * WebUI Class
 *
 * This is the main web user interface class.  It should be instantiated, then the run() 
 * method called to start the webui handler.
 *
 * @author Robbie Hott
 */
class WebUI implements \snac\interfaces\ServerInterface {
	

	private $input = null;

	/**
	 * Response text
	 * @var string response
	 */
	private $response = "";
	
	public function __construct($input) {
		$this->input = $input;
		return;
	}

    public function run() {		
    	
    	$connect = new ServerConnect();
		
		$serverResponse = $connect->query($this->input);
		
		$this->response = "<html><body><h1>Server Response</h1><pre>" . print_r($serverResponse, true) . "</pre></body></html>";

        return;
    }
    
    public function getResponse() {
    	return $this->response;
    }
    
    public function getResponseHeaders() {
    	return array("Content-Type: text/html");
    }

}

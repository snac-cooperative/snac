<?php
/*
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
/**
 * WebUI Class
 *
 * This is the main web user interface class.  It should be instantiated, then the run() 
 * method called to start the webui handler.
 *
 * @author Robbie Hott
 */


class WebUI implements \snac\interfaces\ServerInterface {

	public function __construct($input) {
		return;
	}

    public function run() {

        return;
    }
    
    public function getResponse() {
    	return "<html><body><h1>Successfully made response</h1></body></html>";
    }
    
    public function getResponseHeaders() {
    	return array("Content-Type: text/html");
    }

}

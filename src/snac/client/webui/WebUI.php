<?php
/**
 * Web Interface Class File
 *
 * Contains the main web interface class that instantiates the web ui
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\client\webui;

use snac\interfaces\ServerInterface;
use \snac\client\util\ServerConnect as ServerConnect;

/**
 * WebUI Class
 *
 * This is the main web user interface class. It should be instantiated, then the run()
 * method called to start the webui handler.
 *
 * @author Robbie Hott
 */
class WebUI implements \snac\interfaces\ServerInterface {

    /**
     * @var array $input input for the web server
     */
    private $input = null;

    /**
     * Response text
     *
     * @var string $response  generated response for the web server
     */
    private $response = "";

    /**
     * Constructor
     * 
     * Takes the input parameters to the web server as an associative array.  These will likely
     * be the GET or POST variables from the user's web browser.
     * 
     * @param array $input web input as an associative array
     */
    public function __construct($input) {

        $this->input = $input;
        return;
    }

    /**
     * Run Function
     * 
     * Runs the web server on the input and produces the response.
     * 
     * {@inheritDoc}
     * @see \snac\interfaces\ServerInterface::run()
     */
    public function run() {

        $connect = new ServerConnect();

        if (isset($this->input["reconcile"])) {
            // Create the new identity to search
            $identity = new \snac\data\Constellation();
            $name = new \snac\data\NameEntry();
            $name->setOriginal($_GET['q']);
            $identity->addNameEntry($name);
            $this->input["command"] = "reconcile";
            $this->input["constellation"] = $identity->toArray();
        }

        $serverResponse = $connect->query($this->input);
        
        $this->response = "<html><body><h1>Server Response</h1><pre>" . print_r($serverResponse, true) .
                 "</pre></body></html>";
        
        return;
    }

    /**
     * Returns the web server's response (as a string)
     * 
     * {@inheritDoc}
     * @see \snac\interfaces\ServerInterface::getResponse()
     */
    public function getResponse() {

        return $this->response;
    }

    /**
     * Returns the headers for the web server's response (as array of strings)
     * 
     * {@inheritDoc}
     * @see \snac\interfaces\ServerInterface::getResponseHeaders()
     */
    public function getResponseHeaders() {

        return array (
                "Content-Type: text/html"
        );
    }
}

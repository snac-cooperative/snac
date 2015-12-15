<?php

/**
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
 *            the Regents of the University of California
 */
namespace snac\server;

/**
 * Server Class
 *
 * This is the main server class. It should be instantiated, then the run()
 * method called to start the server running.
 *
 * @author Robbie Hott
 */
class Server implements \snac\interfaces\ServerInterface {

    /**
     * Input parameters from the querier
     *
     * @var array Associative array of the input query
     */
    private $input = null;

    /**
     * Headers for response
     *
     * @var array Response headers
     */
    private $responseHeaders = array (
            "Content-Type: application/json"
        );

    /**
     * Response Array
     * @var string[] Response
     */
    private $response = array();

    /**
     *
     * @var int Timing information (ms)
     */
    private $timing = 0;

    /**
     * Constructor
     *
     * Requires the input to the server as an associative array
     *
     * @param array $input Input to the server
     */
    public function __construct($input) {

        $this->input = $input;
        $this->timing = $_SERVER["REQUEST_TIME_FLOAT"];
        $this->response = array(
                "request" => $this->input,
        );
    }

    /**
     * Run Method
     *
     * Starts the server
     */
    public function run() {

        // TODO: Simple plumbing that needs to be rewritten with the Workflow engine

        switch ($this->input["command"]) {

            case "reconcile":

                break;
            case "edit":
                if (isset($this->input["arkid"])) {
                    // Editing the given ark id by reading querying the current HRT
                    
                    // split on ark:/
                    $tmp = explode("ark:/", $this->input["arkid"]);
                    if (isset($tmp[1])) {
                        $pieces = explode("/", $tmp[1]);
                        if (count($pieces) == 2) {
                            $filename = "http://socialarchive.iath.virginia.edu/snac/data/".$pieces[0]."-".$pieces[1].".xml";
                            // Create new parser for this file and parse it
                            $parser = new \snac\util\EACCPFParser();
                            $id = $parser->parseFile($filename);
                            $this->response["constellation"] = $id->toArray();
                            return;
                        }
                    }
                } else if (isset($this->input["constellationid"])) {
                    // Editing the given constellation id by reading the database
                    $db = new \snac\server\database\DBUtil();
                    $constellation = $db->selectConstellation(
                        array(
                            "version"=> $this->input["version"],
                            "main_id" => $this->input["constellationid"] 
                        ), // version number -- how do I get this??
                        "system"); // no idea how to get this now
                    $this->response["constellation"] = $constellation->toArray();
                    return;
                }
                // break; // no longer breaking to allow th edefault case to give an error if neither matches
            default:
                throw new \snac\exceptions\SNACUnknownCommandException("Command: " . $this->input["command"]);    

        }

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

        return $this->responseHeaders;
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
        // TODO: Fill in body
        $this->response["timing"] =round((microtime(true) - $this->timing) * 1000, 2);
        return json_encode($this->response, JSON_PRETTY_PRINT);
    }
}

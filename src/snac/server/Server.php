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
        $this->timing = $_SERVER["REQUEST_TIME_FLOAT"];
        $this->response = array(
                "request" => $this->input,
        );
        
        
        // create a log channel
        $this->logger = new \Monolog\Logger('Server');
        $this->logger->pushHandler($log);
    }

    /**
     * Run Method
     *
     * Starts the server
     */
    public function run() {

        $this->logger->addDebug("Server starting to handle request", $this->input);
        // TODO: Simple plumbing that needs to be rewritten with the Workflow engine

        switch ($this->input["command"]) {

            case "vocabulary":
                $db = new \snac\server\database\DBUtil();
                $this->response["results"] = $db->searchVocabulary(
                    $this->input["type"],
                    $this->input["query_string"]);
                break;
            case "reconcile":

                break;
            case "user_information":
                $this->response["editing"] = array();
                $this->response["user"] = $this->input["user"];
                $db = new \snac\server\database\DBUtil();
                $this->logger->addDebug("Getting list of locked constellations to user");
                $editList = $db->listConstellationsLockedToUser();
                $this->logger->addDebug("Got list of locked constellations to user");
                
                $this->response["editing"] = array();
                foreach ($editList as $constellation) {
                    $item = array(
                        "id" => $constellation->getID(),
                        "version" => $constellation->getVersion(),
                        "nameEntry" => $constellation->getPreferredNameEntry()->getOriginal()
                    );
                    $this->logger->addDebug("User was currently editing", $item);
                    array_push($this->response["editing"], $item);
                }
                
                // Give the editing list back in alphabetical order
                usort($this->response["editing"], function($a, $b) {
                    return $a['nameEntry'] <=> $b['nameEntry'];
                });
                
                break;
            case "insert_constellation":
                try {
                    $db = new \snac\server\database\DBUtil();
                    if (isset($this->input["constellation"])) {
                        $constellation = new \snac\data\Constellation($this->input["constellation"]);
                        $result = $db->writeConstellation($constellation, "Demo updates for now");
                        if (isset($result) && $result != null) {
                            $this->logger->addDebug("successfully wrote constellation");
                            $this->response["constellation"] = $result->toArray();
                            $this->response["result"] = "success";
                        } else {
                            $this->logger->addDebug("writeConstellation returned a null result");
                            $this->response["result"] = "failure";
                        }
                    } else {
                        $this->logger->addDebug("Constellation input value wasn't set to write");
                        $this->response["result"] = "failure";
                    }
                } catch (Exception $e) {
                    $this->logger->addError("writeConstellation threw an exception");
                    $this->response["result"] = "failure";
                }
                
                break;
            case "update_constellation":
                try {
                    $db = new \snac\server\database\DBUtil();
                    if (isset($this->input["constellation"])) {
                        $constellation = new \snac\data\Constellation($this->input["constellation"]);
                        $result = $db->writeConstellation($constellation, "Demo updates for now");
                        if (isset($result) && $result != null) {
                            $this->logger->addDebug("successfully wrote constellation");
                            $this->response["constellation"] = $result->toArray();
                            $this->response["result"] = "success";
                        } else {
                            $this->logger->addDebug("writeConstellation returned a null result");
                            $this->response["result"] = "failure";
                        }
                    } else {
                        $this->logger->addDebug("Constellation input value wasn't set to write");
                        $this->response["result"] = "failure";
                    }
                } catch (Exception $e) {
                    $this->logger->addError("writeConstellation threw an exception");
                    $this->response["result"] = "failure";
                }
                
                break;

            case "publish_constellation":
                try {
                    $this->logger->addDebug("Publishing constellation");
                    $db = new \snac\server\database\DBUtil();
                    if (isset($this->input["constellation"])) {
                        $constellation = new \snac\data\Constellation($this->input["constellation"]);
                        $result = $db->writeConstellationStatus($constellation->getID(), "published", "User published constellation");
                        if (isset($result) && $result !== false) {
                            $this->logger->addDebug("successfully published constellation");
                            $constellation->setVersion($result);
                            $this->response["constellation"] = $constellation->toArray();
                            $this->response["result"] = "success";

                            $this->logger->addDebug("Successfully published constellation");
                            
                            // add to elastic search
                            $eSearch = null;
                            if (\snac\Config::$USE_ELASTIC_SEARCH) {
                                $eSearch = \Elasticsearch\ClientBuilder::create()
                                ->setHosts([\snac\Config::$ELASTIC_SEARCH_URI])
                                ->setRetries(0)
                                ->build();
                            }

                            $this->logger->addDebug("Created elastic search client to update");
                            
                            if ($eSearch != null) {
                                $params = [
                                        'index' => \snac\Config::$ELASTIC_SEARCH_BASE_INDEX,
                                        'type' => \snac\Config::$ELASTIC_SEARCH_BASE_TYPE,
                                        'id' => $constellation->getID(),
                                        'body' => [
                                                'nameEntry' => $constellation->getPreferredNameEntry()->getOriginal(),
                                                'arkID' => $constellation->getArk(),
                                                'id' => $constellation->getID()
                                        ]
                                ];
                            
                                $eSearch->index($params);
                                $this->logger->addDebug("Updated elastic search with newly published constellation");
                            }
                            
                        } else {
                            $this->logger->addDebug("could not publish the constellation");
                            $this->response["result"] = "failure";
                        }
                    } else {
                        $this->logger->addDebug("no constellation given to publish");
                        $this->response["result"] = "failure";
                    }
                } catch (Exception $e) {
                    $this->logger->addError("publishing constellation threw an exception");
                    $this->response["result"] = "failure";
                }
            
                break;
            case "read":
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
                    
                    try {
                        // Read the constellation
                        $this->logger->addDebug("Reading constellation from the database");
                        $constellation = null;
                        if (isset($this->input["version"])) {
                            $constellation = $db->readConstellation(
                                $this->input["constellationid"], 
                                $this->input["version"]);
                        } else {
                            $constellation = $db->readPublishedConstellationByID(
                                    $this->input["constellationid"]);
                            
                            if ($constellation === false) {
                                // This means that the Constellation doesn't have a published version!
                                throw new \snac\exceptions\SNACInputException("Constellation with id " . 
                                        $this->input["constellationid"] . " does not have a published version.");
                            }
                        }
                        $this->logger->addDebug("Finished reading constellation from the database");
                        $this->response["constellation"] = $constellation->toArray();
                        $this->logger->addDebug("Serialized constellation for output to client");
                    } catch (Exception $e) {
                        $this->response["error"] = $e;
                    }
                    return;
                } else if (isset($this->input["testid"])) {
                    if ($this->input["testid"] == 1) {
                        // Create new parser for this file and parse it
                        $parser = new \snac\util\EACCPFParser();
                        $id = $parser->parseFile("http://shannonvm.village.virginia.edu/~jh2jf/test_record.xml");
                        $this->response["constellation"] = $id->toArray();
                        return;
                    }
                }
                // break; // no longer breaking to allow the default case to give an error if neither matches
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

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
                $this->response["user"] = $this->input["user"];
                $db = new \snac\server\database\DBUtil();
                $this->logger->addDebug("Getting list of locked constellations to user");
                
                // First look for constellations editable
                $editList = $db->listConstellationsWithStatusForUser("locked editing");
               
                $this->response["editing"] = array();
                if ($editList !== false) {
                    foreach ($editList as $constellation) {
                        $item = array(
                            "id" => $constellation->getID(),
                            "version" => $constellation->getVersion(),
                            "nameEntry" => $constellation->getPreferredNameEntry()->getOriginal()
                        );
                        $this->logger->addDebug("User was currently editing", $item);
                        array_push($this->response["editing"], $item);
                    }
                }
                
                // Give the editing list back in alphabetical order
                usort($this->response["editing"], function($a, $b) {
                    return $a['nameEntry'] <=> $b['nameEntry'];
                });

                // Next look for currently editing constellations
                $editList = $db->listConstellationsWithStatusForUser("currently editing");
                 
                $this->response["editing_lock"] = array();
                if ($editList !== false) {
                    foreach ($editList as $constellation) {
                        $item = array(
                                "id" => $constellation->getID(),
                                "version" => $constellation->getVersion(),
                                "nameEntry" => $constellation->getPreferredNameEntry()->getOriginal()
                        );
                        $this->logger->addDebug("User was currently editing", $item);
                        array_push($this->response["editing_lock"], $item);
                    }
                }
                
                // Give the editing list back in alphabetical order
                usort($this->response["editing_lock"], function($a, $b) {
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
                } catch (\Exception $e) {
                    $this->logger->addError("writeConstellation threw an exception");
                    $this->response["result"] = "failure";
                }
                
                break;
            case "update_constellation":
                if (isset($this->input["constellation"])) {
                    $constellation = new \snac\data\Constellation($this->input["constellation"]);
                    
                    try {
                        $validation = new \snac\server\validation\ValidationEngine();
                        $hasOperationValidator = new \snac\server\validation\validators\HasOperationValidator();
                        $validation->addValidator($hasOperationValidator);
                        
                        $success = $validation->validateConstellation($constellation);
                       
                    } catch (\snac\exceptions\SNACValidationException $e) {
                        // If the Constellation has no changes, then don't do anything and allow the "update"
                        // but don't do anything
                        $this->response["constellation"] = $constellation->toArray();
                        $this->response["result"] = "success";
                        break;
                    }
                    
                    
                    try {
                        $db = new \snac\server\database\DBUtil();
                        if (isset($this->input["constellation"])) {
                            $result = $db->writeConstellation($constellation, "Demo updates for now");
                            if (isset($result) && $result != null) {
                                $this->logger->addDebug("successfully wrote constellation");
                                $this->response["constellation"] = $result->toArray();
                                $this->response["result"] = "success";
                            } else {
                                $this->logger->addDebug("writeConstellation returned a null result");
                                $this->response["result"] = "failure";
                            }
                        }
                    } catch (\Exception $e) {
                        $this->logger->addError("writeConstellation threw an exception");
                        // Rethrow it, since we just wanted a log statement
                        throw $e;
                    }
                } else {
                    $this->logger->addDebug("Constellation input value wasn't set to write");
                    $this->response["result"] = "failure";
                }
                break;
            
            case "unlock_constellation":
                try {
                    $this->logger->addDebug("Lowering the lock on the constellation");
                    $db = new \snac\server\database\DBUtil();
                    if (isset($this->input["constellation"])) {
                        $constellation = new \snac\data\Constellation($this->input["constellation"]);
                        
                        $currentStatus = $db->readConstellationStatus($constellation->getID());
                        
                        // TODO This will change when users are present
                        if ($currentStatus == "currently editing") {
                            $result = $db->writeConstellationStatus($constellation->getID(), "locked editing", 
                                "User finished editing constellation");
                            
                        
                            if (isset($result) && $result !== false) {
                                $this->logger->addDebug("successfully unlocked constellation");
                                $constellation->setVersion($result);
                                $this->response["constellation"] = $constellation->toArray();
                                $this->response["result"] = "success";
                                
                                
                            } else {
                                
                                $this->logger->addDebug("could not unlock the constellation");
                                $this->response["result"] = "failure";
                            }
                        } else {
                            $this->logger->addDebug("constellation was not locked");
                            $this->response["result"] = "failure";
                        }
                    } else {
                        $this->logger->addDebug("no constellation given to unlock");
                        $this->response["result"] = "failure";
                    }
                } catch (\Exception $e) {
                    $this->logger->addError("unlocking constellation threw an exception");
                    $this->response["result"] = "failure";
                    throw $e;
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
                                                'entityType' => $constellation->getEntityType()->getID(),
                                                'arkID' => $constellation->getArk(),
                                                'id' => $constellation->getID(),
                                                'timestamp' => date('c')
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
                } catch (\Exception $e) {
                    $this->logger->addError("publishing constellation threw an exception");
                    $this->response["result"] = "failure";
                    throw $e;
                }
            
                break;
            case "search":
                // ElasticSearch Handler
                $eSearch = null;
                if (\snac\Config::$USE_ELASTIC_SEARCH) {
                    $this->logger->addDebug("Creating ElasticSearch Client");
                    $eSearch = \Elasticsearch\ClientBuilder::create()
                    ->setHosts([\snac\Config::$ELASTIC_SEARCH_URI])
                    ->setRetries(0)
                    ->build();
                
                
                    $params = [
                            'index' => \snac\Config::$ELASTIC_SEARCH_BASE_INDEX,
                            'type' => \snac\Config::$ELASTIC_SEARCH_BASE_TYPE,
                            'body' => [
                                    'query' => [
                                            'query_string' => [
                                                    'fields' => ["nameEntry"],
                                                    'query' => '*'.$this->input["term"].'*'
                                            ]
                                    ]
                            ]
                    ];
                    $this->logger->addDebug("Defined parameters for search", $params);
                
                    $results = $eSearch->search($params);
                
                    $this->logger->addDebug("Completed Elastic Search", $results);
                
                    $return = array();
                    foreach ($results["hits"]["hits"] as $i => $val) {
                        array_push($return, array(
                                "id"=>$val["_source"]["id"],
                                "label"=>$val["_source"]["nameEntry"],
                                "value"=>$val["_source"]["nameEntry"]
                        ));
                    }
                
                    $this->logger->addDebug("Created search response to the user", $return);
                
                    // Send the response back to the web client
                    $this->response = json_encode($return, JSON_PRETTY_PRINT);
                    array_push($this->responseHeaders, "Content-Type: text/json");
                    return;
                } else {
                    $this->response = json_encode(array("result" => "Not Using ElasticSearch"), JSON_PRETTY_PRINT);
                    array_push($this->responseHeaders, "Content-Type: text/json");
                
                }
                break;
            case "recently_published":
                // ElasticSearch Handler
                $eSearch = null;
                if (\snac\Config::$USE_ELASTIC_SEARCH) {
                    $this->logger->addDebug("Creating ElasticSearch Client");
                    $eSearch = \Elasticsearch\ClientBuilder::create()
                    ->setHosts([\snac\Config::$ELASTIC_SEARCH_URI])
                    ->setRetries(0)
                    ->build();

                    $db = new \snac\server\database\DBUtil();
                
                    $params = [
                        'index' => \snac\Config::$ELASTIC_SEARCH_BASE_INDEX,
                        'type' => \snac\Config::$ELASTIC_SEARCH_BASE_TYPE,
                        'body' => [
                                'sort' => [
                                        'timestamp' => [
                                               "order" => "desc"
                                        ]
                                ]
                        ]
                    ];
                    $this->logger->addDebug("Defined parameters for search", $params);
                
                    $results = $eSearch->search($params);
                
                    $this->logger->addDebug("Completed Elastic Search", $results);
                
                    $return = array();
                    foreach ($results["hits"]["hits"] as $i => $val) {
                        array_push($return, $db->readPublishedConstellationByID($val["_source"]["id"], true)->toArray());
                    }
                
                    $this->logger->addDebug("Created search response to the user", $return);
                
                    // Send the response back to the web client
                    $this->response["constellation"] = $return;
                    $this->response["result"] = "success";
                    return;
                } else {
                    $this->response["result"] = "Not Using ElasticSearch";
                
                }
                break;
            case "read":
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
                    
                    // TODO should lock constellation
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
                    
                    // TODO should lock constellation
                    try {
                        $db = new \snac\server\database\DBUtil();
                        // Read the constellation
                        $this->logger->addDebug("Reading constellation from the database");
                        
                        $cId = $this->input["constellationid"];
                        $status = $db->readConstellationStatus($cId);
                        // TODO This must change when users are present
                        if ( $status == "published" || $status == "locked editing") {
                            // Can edit this!
                            
                            // lock the constellation to the user as currently editing
                            $db->writeConstellationStatus($cId, "currently editing");
                            
                            // read the constellation into response
                            $constellation = $db->readConstellation($cId);
                            $this->logger->addDebug("Finished reading constellation from the database");
                            $this->response["constellation"] = $constellation->toArray();
                            $this->logger->addDebug("Serialized constellation for output to client");
                        } else {
                            throw new \snac\exceptions\SNACPermissionException("User is not allowed to edit this constellation.");
                        }
                        
                        
                    } catch (\Exception $e) {
                        // Leaving a catch block for logging purposes
                        throw $e;
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
                
                break;
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

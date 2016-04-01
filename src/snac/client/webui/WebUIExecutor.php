<?php
/**
 * Web Interface Executor Class File
 *
 * Contains the WebUIExector class that performs all the tasks for the Web UI
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\client\webui;

use \snac\interfaces\ServerInterface;
use \snac\client\util\ServerConnect as ServerConnect;

/**
 * WebUIExecutor Class
 *
 * Contains functions that the WebUI's workflow engine needs to complete its work.
 *
 * @author Robbie Hott
 */
class WebUIExecutor {

    private $connect = null;

    /**
     * @var \Monolog\Logger $logger Logger for this server
     */
    private $logger = null;

    /**
     * Constructor
     *
     */
    public function __construct() {
        global $log;
        
        // set up server connection
        $this->connect = new ServerConnect();

        // create a log channel
        $this->logger = new \Monolog\Logger('WebUIExec');
        $this->logger->pushHandler($log);
        
        return;
    }

    
    
    public function displayEditPage(&$input, &$display, &$user) {

        $query = $input;
        $query["user"] = $user->toArray();
        $this->logger->addDebug("Sending query to the server", $query);
        $serverResponse = $this->connect->query($query);
        $this->logger->addDebug("Received server response", array($serverResponse));
        if (isset($serverResponse["constellation"])) {
            $display->setTemplate("edit_page");
            $constellation = $serverResponse["constellation"];
            if (\snac\Config::$DEBUG_MODE == true) {
                $display->addDebugData("constellationSource", json_encode($serverResponse["constellation"], JSON_PRETTY_PRINT));
                $display->addDebugData("serverResponse", json_encode($serverResponse, JSON_PRETTY_PRINT));
            }
            $this->logger->addDebug("Setting constellation data into the page template");
            $display->setData($constellation);
        } else {
                $this->logger->addDebug("Error page being drawn");
                $display->setTemplate("error_page");
                $this->logger->addDebug("Setting error data into the error page template");
                $display->setData($serverResponse["error"]);
        }
    }
    
    public function displayNewEditPage(&$display) {
        $display->setTemplate("edit_page");
        $constellation = new \snac\data\Constellation();
        $constellation->setOperation(\snac\data\Constellation::$OPERATION_INSERT);
        $constellation->addNameEntry(new \snac\data\NameEntry());
        if (\snac\Config::$DEBUG_MODE == true) {
            $display->addDebugData("constellationSource", json_encode($constellation, JSON_PRETTY_PRINT));
        }
        $this->logger->addDebug("Setting constellation data into the page template");
        $display->setData($constellation);
    }
    
    public function displayViewPage(&$input, &$display, &$user) {
        $query = array();
        $query["constellationid"] = $input["constellationid"];
        $query["command"] = "read";
        if (isset($user) && $user != null)
            $query["user"] = $user->toArray();
            $this->logger->addDebug("Sending query to the server", $query);
            $serverResponse = $this->connect->query($query);
            $this->logger->addDebug("Received server response");
            if (isset($serverResponse["constellation"])) {
                $display->setTemplate("view_page");
                $constellation = $serverResponse["constellation"];
                if (\snac\Config::$DEBUG_MODE == true) {
                    $display->addDebugData("constellationSource", json_encode($serverResponse["constellation"], JSON_PRETTY_PRINT));
                    $display->addDebugData("serverResponse", json_encode($serverResponse, JSON_PRETTY_PRINT));
                }
                $this->logger->addDebug("Setting constellation data into the page template");
                $display->setData($constellation);
            } else {
                $this->logger->addDebug("Error page being drawn");
                $display->setTemplate("error_page");
                $this->logger->addDebug("Setting error data into the error page template");
                $display->setData($serverResponse["error"]);
            }
    }
    
    public function displayPreviewPage(&$input, &$display) {

        // If just previewing, then all the information should come VIA post to build the preview
        $mapper = new \snac\client\webui\util\ConstellationPostMapper();
        
        // Get the constellation object
        $constellation = $mapper->serializeToConstellation($input);
        
        if ($constellation != null) {
            $display->setTemplate("view_page");
            if (\snac\Config::$DEBUG_MODE == true) {
                $display->addDebugData("constellationSource", json_encode($constellation, JSON_PRETTY_PRINT));
            }
            $this->logger->addDebug("Setting constellation data into the page template");
            $display->setData($constellation);
        }
    }
    
    public function displayDashboardPage(&$display, &$user) {
        $display->setTemplate("dashboard");
        // Ask the server for a list of records to edit
        $ask = array("command"=>"user_information",
                "user" => $user->toArray()
        );
        $this->logger->addDebug("Sending query to the server", $ask);
        $serverResponse = $this->connect->query($ask);
        $this->logger->addDebug("Received server response", array($serverResponse));
        $this->logger->addDebug("Setting dashboard data into the page template");
        
        $recentConstellations = $this->connect->query(array(
                "command"=>"recently_published"
        ))["constellation"];
        
        $recents = array();
        foreach ($recentConstellations as $constellationArray) {
            $constellation = new \snac\data\Constellation($constellationArray);
            array_push($recents, array(
                    "id"=>$constellation->getID(),
                    "nameEntry"=>$constellation->getPreferredNameEntry()->getOriginal()));
        }
        $serverResponse["recents"] = $recents;
       
        $display->setData($serverResponse);
    }
    
    public function displayProfilePage(&$display, &$user) {
        $display->setTemplate("profile_page");
        // Ask the server for a list of records to edit
        $ask = array("command"=>"user_information",
                "user" => $user->toArray()
        );
        $this->logger->addDebug("Sending query to the server", $ask);
        $serverResponse = $this->connect->query($ask);
        $this->logger->addDebug("Received server response", $ask);
        $this->logger->addDebug("Setting dashboard data into the page template");
        $display->setData($serverResponse);
    }
    
    public function displayLandingPage(&$display) {

        // Get the list of recently published constellations
        
        $request = array();
        $request["command"] = "recently_published";
        $recentConstellations = $this->connect->query($request)["constellation"];
        
        $recents = array();
        foreach ($recentConstellations as $constellationArray) {
            $constellation = new \snac\data\Constellation($constellationArray);
            array_push($recents, array(
                    "id"=>$constellation->getID(),
                    "nameEntry"=>$constellation->getPreferredNameEntry()->getOriginal()));
        }
        
        $display->setData(array("recents"=>$recents));
        $display->setTemplate("landing_page");
    }
    
    public function saveConstellation(&$input, &$user) {
        $mapper = new \snac\client\webui\util\ConstellationPostMapper();
        
        // Get the constellation object
        $constellation = $mapper->serializeToConstellation($input);
        
        $this->logger->addDebug("writing constellation", $constellation->toArray());
        
        // Build a data structure to send to the server
        $request = array("command"=>"update_constellation");
        
        // Send the query to the server
        $request["constellation"] = $constellation->toArray();
        $request["user"] = $user->toArray();
        $serverResponse = $this->connect->query($request);
        
        $response = array();
        $response["server_debug"] = $serverResponse;
        
        if (!is_array($serverResponse)) {
            $this->logger->addDebug("server's response: $serverResponse");
        } else {
            if (isset($serverResponse["result"]))
                $response["result"] = $serverResponse["result"];
                if (isset($serverResponse["error"])) {
                    $response["error"] = $serverResponse["error"];
                }
                // Get the server's response constellation
                if (isset($serverResponse["constellation"])) {
                    $this->logger->addDebug("server's response written constellation", $serverResponse["constellation"]);
                    $updatedConstellation = new \snac\data\Constellation($serverResponse["constellation"]);
                    $mapper->reconcile($updatedConstellation);
        
                    $response["updates"] = $mapper->getUpdates();
                }
        }
        
        return $response;
    }

    public function saveAndPublishConstellation(&$input, &$user) {

        $mapper = new \snac\client\webui\util\ConstellationPostMapper();
        
        // Get the constellation object
        $constellation = $mapper->serializeToConstellation($input);
        
        $this->logger->addDebug("writing constellation", $constellation->toArray());
        
        // Build a data structure to send to the server
        $request = array (
                "command" => "update_constellation"
        );
        
        // Send the query to the server
        $request["constellation"] = $constellation->toArray();
        $request["user"] = $user->toArray();
        $serverResponse = $this->connect->query($request);
        
        $response = array ();
        $response["server_debug"] = array ();
        $response["server_debug"]["update"] = $serverResponse;
        
        if (! is_array($serverResponse)) {
            $this->logger->addDebug("server's response: $serverResponse");
        } else {
            
            if (isset($serverResponse["constellation"])) {
                $this->logger->addDebug("server's response written constellation", $serverResponse["constellation"]);
            }
            
            if (isset($serverResponse["result"]) && $serverResponse["result"] == "success" &&
                     isset($serverResponse["constellation"])) {
                $request["command"] = "publish_constellation";
                $request["constellation"] = $serverResponse["constellation"];
                $serverResponse = $this->connect->query($request);
                $response["server_debug"]["publish"] = $serverResponse;
                if (isset($serverResponse["result"]))
                    $response["result"] = $serverResponse["result"];
                if (isset($serverResponse["error"]))
                    $response["error"] = $serverResponse["error"];
            }
        }
        
        return $response;
    }
    

    public function saveAndUnlockConstellation(&$input, &$user) {
    
        $mapper = new \snac\client\webui\util\ConstellationPostMapper();
    
        // Get the constellation object
        $constellation = $mapper->serializeToConstellation($input);
    
        $this->logger->addDebug("writing constellation", $constellation->toArray());
    
        // Build a data structure to send to the server
        $request = array (
                "command" => "update_constellation"
        );
    
        // Send the query to the server
        $request["constellation"] = $constellation->toArray();
        $request["user"] = $user->toArray();
        $serverResponse = $this->connect->query($request);
    
        $response = array ();
        $response["server_debug"] = array ();
        $response["server_debug"]["update"] = $serverResponse;
    
        if (! is_array($serverResponse)) {
            $this->logger->addDebug("server's response: $serverResponse");
        } else {
    
            if (isset($serverResponse["constellation"])) {
                $this->logger->addDebug("server's response written constellation", $serverResponse["constellation"]);
            }
    
            if (isset($serverResponse["result"]) && $serverResponse["result"] == "success" &&
                    isset($serverResponse["constellation"])) {
                        $request["command"] = "unlock_constellation";
                        $request["constellation"] = $serverResponse["constellation"];
                        $serverResponse = $this->connect->query($request);
                        $response["server_debug"]["unlock"] = $serverResponse;
                        if (isset($serverResponse["result"]))
                            $response["result"] = $serverResponse["result"];
                            if (isset($serverResponse["error"]))
                                $response["error"] = $serverResponse["error"];
                    }
        }
    
        return $response;
    }
    

    public function unlockConstellation(&$input, &$user) {
    
        $constellation = null;
        if (isset($input["constellationid"])) {
            $constellation = new \snac\data\Constellation();
            $constellation->setID($input["constellationid"]);
        } else {
            $mapper = new \snac\client\webui\util\ConstellationPostMapper();
        
            // Get the constellation object
            $constellation = $mapper->serializeToConstellation($input);
        }
        
        $this->logger->addDebug("unlocking constellation", $constellation->toArray());
    
        // Build a data structure to send to the server
        $request = array (
                "command" => "unlock_constellation"
        );
    
        // Send the query to the server
        $request["constellation"] = $constellation->toArray();
        $request["user"] = $user->toArray();
        $serverResponse = $this->connect->query($request);
    
        $response = array ();
        $response["server_debug"] = array ();
        $response["server_debug"]["unlock"] = $serverResponse;
        if (isset($serverResponse["result"]))
            $response["result"] = $serverResponse["result"];
        if (isset($serverResponse["error"]))
            $response["error"] = $serverResponse["error"];
        
        return $response;
    }

    public function publishConstellation(&$input, &$user) {
    
        $mapper = new \snac\client\webui\util\ConstellationPostMapper();
    
        // Get the constellation object
        $constellation = $mapper->serializeToConstellation($input);
    
        $this->logger->addDebug("publishing constellation", $constellation->toArray());
    
        // Build a data structure to send to the server
        $request = array (
                "command" => "publish_constellation"
        );
    
        // Send the query to the server
        $request["constellation"] = $constellation->toArray();
        $request["user"] = $user->toArray();
        $serverResponse = $this->connect->query($request);
        
        $response = array ();
        $response["server_debug"] = array ();
        $response["server_debug"]["publish"] = $serverResponse;
        if (isset($serverResponse["result"]))
            $response["result"] = $serverResponse["result"];
        if (isset($serverResponse["error"]))
            $response["error"] = $serverResponse["error"];
        
        return $response;
    }

    public function performNameSearch(&$input) {

        $this->logger->addDebug("Searching for a Constellation");
        
        $start = 0;
        if (isset($input["start"]) && is_numeric($input["start"]))
            $start = $input["start"];
        
        $count = 10;
        if (isset($input["count"]) && is_numeric($input["count"]))
            $count = $input["count"];
            
            // ElasticSearch Handler
        $eSearch = null;
        if (\snac\Config::$USE_ELASTIC_SEARCH) {
            $this->logger->addDebug("Creating ElasticSearch Client");
            $eSearch = \Elasticsearch\ClientBuilder::create()->setHosts([ 
                    \snac\Config::$ELASTIC_SEARCH_URI
            ])->setRetries(0)->build();
            
            $params = [ 
                    'index' => 'rtest',
                    'type' => 'prototype_name_search',
                    'body' => [ 
                            'query' => [ 
                                    'query_string' => [ 
                                            'fields' => [ 
                                                    "nameEntry"
                                            ],
                                            'query' => '*' . $input["term"] . '*'
                                    ]
                            ],
                            'from' => $start,
                            'size' => $count
                    ]
            ];
            $this->logger->addDebug("Defined parameters for search", $params);
            
            $results = $eSearch->search($params);
            
            $this->logger->addDebug("Completed Elastic Search", $results);
            
            $return = array ();
            foreach ($results["hits"]["hits"] as $i => $val) {
                array_push($return, 
                        array (
                                "id" => $val["_source"]["id"],
                                "label" => $val["_source"]["nameEntry"],
                                "value" => $val["_source"]["nameEntry"]
                        ));
            }
            
            $response = array();
            $response["total"] = $results["hits"]["total"];
            $response["results"] = $return;
            
            if ($response["total"] == 0 || $count == 0) {
                $response["pagination"] = 0;
                $response["page"] = 0;
            } else {
                $response["pagination"] = ceil($response["total"] / $count);
                $response["page"] = floor($start / $count);
            }
            $this->logger->addDebug("Created search response to the user", $response);
            
            return $response;
        }
        return array (
                    "notice" => "Not Using ElasticSearch"
        );
    }

    public function performVocabularySearch(&$input) {

        $this->logger->addDebug("Requesting Vocabulary");
        // Check what kind of vocabulary is wanted, and ask server for it
        $request = array ();
        $request["command"] = "vocabulary";
        $request["type"] = $input["type"];
        if (isset($request["type"])) {
            if (strpos($request["type"], "ic_") !== false) {
                $this->logger->addDebug("Requesting Sources as Vocabulary List");
                // This is a query into a constellation for "vocabulary"
                if (isset($input["id"]) && isset($input["version"])) {
                    $serverResponse = $this->connect->query(
                            array (
                                    "constellationid" => $input["id"],
                                    "version" => $input["version"],
                                    "command" => "read"
                            ));
                    $this->logger->addDebug("tried to get the constellation with response", $serverResponse);
                    if (isset($serverResponse["constellation"])) {
                        $constellation = new \snac\data\Constellation($serverResponse["constellation"]);
                        $response = array ();
                        $response["results"] = array ();
                        foreach ($constellation->getSources() as $source) {
                            array_push($response["results"], 
                                    array (
                                            "id" => $source->getID(),
                                            "text" => $source->getDisplayName()
                                    ));
                        }
                        $this->logger->addDebug("created the following response list of sources", $response);
                        return $response;
                    }
                }
            } else {
                $this->logger->addDebug("Requesting Controlled Vocabulary List");
                // This is a strict query for a controlled vocabulary term
                $queryString = "";
                if (isset($input["q"]))
                    $queryString = $input["q"];
                $request["query_string"] = $queryString;
                
                // Send the query to the server
                $serverResponse = $this->connect->query($request);
                
                foreach ($serverResponse["results"] as $k => $v)
                    $serverResponse["results"][$k]["text"] = $v["value"];
                    
                    // Send the response back to the web client
                return $serverResponse;
            }
        }
        
        return array ();
    }
    
    public function setConstellationStatus(&$input, $status, &$user) {
        
    }

    public function createUser($googleUser, $googleToken) {
        $user = new \snac\data\User();
        $avatar = $googleUser->getAvatar();
        $avatarSmall = null;
        $avatarLarge = null;
        if ($avatar != null) {
            $avatar = str_replace("?sz=50", "", $avatar);
            $avatarSmall = $avatar . "?sz=20";
            $avatarLarge = $avatar . "?sz=250";
        }
        $user->setAvatar($avatar);
        $user->setAvatarSmall($avatarSmall);
        $user->setAvatarLarge($avatarLarge);
        $user->setEmail($googleUser->getEmail());
        $user->setFirstName($googleUser->getFirstName());
        $user->setFullName($googleUser->getName());
        $user->setLastName($googleUser->getLastName());
        $user->setToken($googleToken);
        $user->setUserid($googleUser->getId());
        
        return $user;
    }
 
}

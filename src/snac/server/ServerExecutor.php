<?php

/**
 * Server Executor Class File
 *
 * Contains the ServerExector class that performs all the tasks for the main Server
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2016 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server;

use League\OAuth2\Client\Token\AccessToken;
/**
 * Server Executor Class
 *
 *  Contains functions that the Server's workflow engine needs to complete its work.
 *
 * @author Robbie Hott
 */
class ServerExecutor {

    /**
     * @var \snac\server\database\DBUtil Constellation Storage Object
     */
    private $cStore = null;
    
    /**
     * @var \snac\server\database\DBUser User Storage Object
     */
    private $uStore = null;
    
    /**
     * @var \snac\data\User Current user object
     */
    private $user = null;
    

    /**
     * @var \Monolog\Logger $logger the logger for this server
     */
    private $logger;
    
    public function __construct() {
        global $log;
        
        $this->cStore = new \snac\server\database\DBUtil();
        $this->uStore = new \snac\server\database\DBUser();
        
        // create a log channel
        $this->logger = new \Monolog\Logger('ServerExec');
        $this->logger->pushHandler($log);
    }
    
    /**
     * Authenticate User
     * 
     * Authenticates the user by checking the user store (dbuser)
     * 
     * @param string[] $user User information to check
     * @return boolean true if user authenticated, false if not
     */
    public function authenticateUser($user) {
        if ($user != null) {
            $this->logger->addDebug("Attempting to authenticate user", $user);
            
            $tmpUser = new \snac\data\User($user);
            
            // Google OAuth Settings (from Config)
            $clientId     = \snac\Config::$OAUTH_CONNECTION["google"]["client_id"];
            $clientSecret = \snac\Config::$OAUTH_CONNECTION["google"]["client_secret"];
            // Change this if you are not using the built-in PHP server
            $redirectUri  = \snac\Config::$OAUTH_CONNECTION["google"]["redirect_uri"];
            // Initialize the provider
            $provider = new \League\OAuth2\Client\Provider\Google(compact('clientId', 'clientSecret', 'redirectUri'));
            
            try {
                $this->logger->addDebug("Trying to connect to OAuth2 Server to get user details");
                
                $accessToken = new AccessToken($tmpUser->getToken());
                
                $ownerDetails = $provider->getResourceOwner($accessToken);
                
                if ($ownerDetails->getEmail() != $tmpUser->getEmail()) {
                    // This user's token doesn't match the user's email
                    $this->logger->addDebug("Email mismatch from the user and OAuth details");
                    return false;
                }
                $this->logger->addDebug("Successfully got user details from OAuth2 Server");
            } catch (\Exception $e) {
                $this->logger->addDebug("Could not get user details from OAuth2 Server: ".$e->getMessage());
                return false;
            }

            $this->logger->addDebug("User is valid from OAuth details");
            //$this->user = $this->uStore->checkSessionActive($tmpUser);
            $this->user = $tmpUser;
            $this->user->setUserID(1);
            $this->logger->addDebug("User is valid from SNAC details");
            
            return true;
        }
        
        // If the user is null, then we're okay on authentication (no permissions)
        return true;
    }
    
    public function startSession() {
        $response = array();
        
        if ($this->user != null) {
            $response["user"] = $this->user->toArray();
            $response["result"] = "success";
        } else {
            $response["result"] = "failure";
        }
        
        return $response;
    }
    
    public function endSession() {
        $response = array();
        
        if ($this->user != null) {
            $this->uStore->removeSession($user);
            $response["user"] = $this->user->toArray();
            $response["result"] = "success";
        } else {
            $response["result"] = "failure";
        }
        
        return $response;
    }
    
    public function searchVocabulary(&$input) {
        $response = array();
        $response["results"] = $this->cStore->searchVocabulary(
                $input["type"],
                $input["query_string"]);
        return $response;
    }
    
    public function userInformation() {
        $response = array();

        if ($this->user == null) {
            return $response;
        }
        
        $this->logger->addDebug("Getting list of locked constellations to user");
        
        // First look for constellations editable
        $editList = $this->cStore->listConstellationsWithStatusForUser($this->user, "locked editing");
        
        $response["editing"] = array ();
        if ($editList !== false) {
            foreach ($editList as $constellation) {
                $item = array (
                        "id" => $constellation->getID(),
                        "version" => $constellation->getVersion(),
                        "nameEntry" => $constellation->getPreferredNameEntry()->getOriginal()
                );
                $this->logger->addDebug("User was currently editing", $item);
                array_push($response["editing"], $item);
            }
        }
        
        // Give the editing list back in alphabetical order
        usort($response["editing"], 
                function ($a, $b) {
                    return $a['nameEntry'] <=> $b['nameEntry'];
                });
        
        // Next look for currently editing constellations
        $editList = $this->cStore->listConstellationsWithStatusForUser($this->user, "currently editing");
        
        $response["editing_lock"] = array ();
        if ($editList !== false) {
            foreach ($editList as $constellation) {
                $item = array (
                        "id" => $constellation->getID(),
                        "version" => $constellation->getVersion(),
                        "nameEntry" => $constellation->getPreferredNameEntry()->getOriginal()
                );
                $this->logger->addDebug("User was currently editing", $item);
                array_push($response["editing_lock"], $item);
            }
        }
        
        // Give the editing list back in alphabetical order
        usort($response["editing_lock"], 
                function ($a, $b) {
                    return $a['nameEntry'] <=> $b['nameEntry'];
                });
        return $response;
    }
    
    public function insertConstellation(&$input) {
        $response = array();

        try {
            if (isset($input["constellation"])) {
                $constellation = new \snac\data\Constellation($input["constellation"]);
                $result = $this->cStore->writeConstellation($this->user, $constellation, "Insert of Constellation");
                if (isset($result) && $result != null) {
                    $this->logger->addDebug("successfully wrote constellation");
                    $response["constellation"] = $result->toArray();
                    $response["result"] = "success";
                } else {
                    $this->logger->addDebug("writeConstellation returned a null result");
                    $response["result"] = "failure";
                }
            } else {
                $this->logger->addDebug("Constellation input value wasn't set to write");
                $response["result"] = "failure";
            }
        } catch (\Exception $e) {
            $this->logger->addError("writeConstellation threw an exception");
            $response["result"] = "failure";
        }
        return $response;
    }
    
    public function updateConstellation(&$input) {
        $response = array();
        if (isset($input["constellation"])) {
            $constellation = new \snac\data\Constellation($input["constellation"]);
        
            try {
                $validation = new \snac\server\validation\ValidationEngine();
                $hasOperationValidator = new \snac\server\validation\validators\HasOperationValidator();
                $validation->addValidator($hasOperationValidator);
        
                $success = $validation->validateConstellation($constellation);
                 
            } catch (\snac\exceptions\SNACValidationException $e) {
                // If the Constellation has no changes, then don't do anything and allow the "update"
                // but don't do anything
                $response["constellation"] = $constellation->toArray();
                $response["result"] = "success";
                return $response;
            }
        
        
            try {
                if (isset($input["constellation"])) {
                    $result = $this->cStore->writeConstellation($this->user, $constellation, "Demo updates for now");
                    if (isset($result) && $result != null) {
                        $this->logger->addDebug("successfully wrote constellation");
                        $response["constellation"] = $result->toArray();
                        $response["result"] = "success";
                    } else {
                        $this->logger->addDebug("writeConstellation returned a null result");
                        $response["result"] = "failure";
                    }
                }
            } catch (\Exception $e) {
                $this->logger->addError("writeConstellation threw an exception");
                // Rethrow it, since we just wanted a log statement
                throw $e;
            }
        } else {
            $this->logger->addDebug("Constellation input value wasn't set to write");
            $response["result"] = "failure";
        }
        return $response;
    }
    
    public function unlockConstellation(&$input) {
        $response = array();
        try {
            $this->logger->addDebug("Lowering the lock on the constellation");
            if (isset($input["constellation"])) {
                $constellation = new \snac\data\Constellation($input["constellation"]);
        
                $currentStatus = $this->cStore->readConstellationStatus($constellation->getID());
        
                // TODO This will change when users are present
                // TODO IF this constellation is in the list of currently editing for the user, then unlock it
                if ($currentStatus == "currently editing") {
                    $result = $this->cStore->writeConstellationStatus($this->user, $constellation->getID(), "locked editing",
                            "User finished editing constellation");
        
        
                    if (isset($result) && $result !== false) {
                        $this->logger->addDebug("successfully unlocked constellation");
                        $constellation->setVersion($result);
                        $response["constellation"] = $constellation->toArray();
                        $response["result"] = "success";
        
        
                    } else {
        
                        $this->logger->addDebug("could not unlock the constellation");
                        $response["result"] = "failure";
                    }
                } else {
                    $this->logger->addDebug("constellation was not locked");
                    $response["result"] = "failure";
                }
            } else {
                $this->logger->addDebug("no constellation given to unlock");
                $response["result"] = "failure";
            }
        } catch (\Exception $e) {
            $this->logger->addError("unlocking constellation threw an exception");
            $response["result"] = "failure";
            throw $e;
        }
        return $response;
    }
    
    public function publishConstellation(&$input) {

        $response = array();
        try {
            $this->logger->addDebug("Publishing constellation");
            if (isset($input["constellation"])) {
                $constellation = new \snac\data\Constellation($input["constellation"]);
                $result = $this->cStore->writeConstellationStatus($this->user, $constellation->getID(), "published", "User published constellation");
                if (isset($result) && $result !== false) {
                    $this->logger->addDebug("successfully published constellation");
                    $constellation->setVersion($result);
                    $response["constellation"] = $constellation->toArray();
                    $response["result"] = "success";
        
        
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
                    $response["result"] = "failure";
                }
            } else {
                $this->logger->addDebug("no constellation given to publish");
                $response["result"] = "failure";
            }
        } catch (\Exception $e) {
            $this->logger->addError("publishing constellation threw an exception");
            $response["result"] = "failure";
            throw $e;
        }
        return $response;
        
    }
    
    public function readConstellation(&$input) {
        $this->logger->addDebug("Reading constellation");
        $reponse = array();
        
        if (isset($input["arkid"])) {
            // Reading the given ark id by reading querying the current HRT
        
            // split on ark:/
            $tmp = explode("ark:/", $input["arkid"]);
            if (isset($tmp[1])) {
                $pieces = explode("/", $tmp[1]);
                if (count($pieces) == 2) {
                    $filename = "http://socialarchive.iath.virginia.edu/snac/data/".$pieces[0]."-".$pieces[1].".xml";
                    // Create new parser for this file and parse it
                    $parser = new \snac\util\EACCPFParser();
                    $id = $parser->parseFile($filename);
                    $response["constellation"] = $id->toArray();
                }
            }
        } else if (isset($input["constellationid"])) {
            // Reading the given constellation id by reading the database
        
            try {
                // Read the constellation
                $this->logger->addDebug("Reading constellation from the database");
                $constellation = null;
                if (isset($input["version"])) {
                    $constellation = $this->cStore->readConstellation(
                            $input["constellationid"],
                            $input["version"]);
                } else {
                    $constellation = $this->cStore->readPublishedConstellationByID(
                            $input["constellationid"]);
        
                    if ($constellation === false) {
                        // This means that the Constellation doesn't have a published version!
                        throw new \snac\exceptions\SNACInputException("Constellation with id " .
                                $input["constellationid"] . " does not have a published version.");
                    }
                }
                $this->logger->addDebug("Finished reading constellation from the database");
                $response["constellation"] = $constellation->toArray();
                $this->logger->addDebug("Serialized constellation for output to client");
            } catch (Exception $e) {
                $response["error"] = $e;
            }
        } else if (isset($input["testid"])) {
            if ($input["testid"] == 1) {
                // Create new parser for this file and parse it
                $parser = new \snac\util\EACCPFParser();
                $id = $parser->parseFile("http://shannonvm.village.virginia.edu/~jh2jf/test_record.xml");
                $response["constellation"] = $id->toArray();
            }
        }
        return $response;
        
    }
    
    public function editConstellation(&$input) {
        $this->logger->addDebug("Editing Constellation");
        $response = array();
        
        if (isset($input["arkid"])) {
            // Editing the given ark id by reading querying the current HRT
        
            // split on ark:/
            $tmp = explode("ark:/", $input["arkid"]);
            if (isset($tmp[1])) {
                $pieces = explode("/", $tmp[1]);
                if (count($pieces) == 2) {
                    $filename = "http://socialarchive.iath.virginia.edu/snac/data/".$pieces[0]."-".$pieces[1].".xml";
                    // Create new parser for this file and parse it
                    $parser = new \snac\util\EACCPFParser();
                    $id = $parser->parseFile($filename);
                    $response["constellation"] = $id->toArray();
                }
            }
        } else if (isset($input["constellationid"])) {
            // Editing the given constellation id by reading the database
        
            try {
                // Read the constellation
                $this->logger->addDebug("Reading constellation from the database");
        
                $cId = $input["constellationid"];
                $status = $this->cStore->readConstellationStatus($cId);
                // TODO This must change when users are present
                if ( $status == "published" || $status == "locked editing") {
                    // Can edit this!
        
                    // lock the constellation to the user as currently editing
                    $this->cStore->writeConstellationStatus($this->user, $cId, "currently editing");
        
                    // read the constellation into response
                    $constellation = $this->cStore->readConstellation($cId);
                    $this->logger->addDebug("Finished reading constellation from the database");
                    $response["constellation"] = $constellation->toArray();
                    $this->logger->addDebug("Serialized constellation for output to client");
                } else {
                    throw new \snac\exceptions\SNACPermissionException("User is not allowed to edit this constellation.");
                }
        
        
            } catch (\Exception $e) {
                // Leaving a catch block for logging purposes
                throw $e;
            }
        } else if (isset($this->input["testid"])) {
            if ($this->input["testid"] == 1) {
                // Create new parser for this file and parse it
                $parser = new \snac\util\EACCPFParser();
                $id = $parser->parseFile("http://shannonvm.village.virginia.edu/~jh2jf/test_record.xml");
                $response["constellation"] = $id->toArray();
            }
        }
        return $response;
    }
    
    public function getRecentlyPublished() {
        $response = array();
        
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
                array_push($return, $this->cStore->readPublishedConstellationByID($val["_source"]["id"], true)->toArray());
            }
        
            $this->logger->addDebug("Created search response to the user", $return);
        
            // Send the response back to the web client
            $response["constellation"] = $return;
            $response["result"] = "success";
        } else {
            $response["result"] = "Not Using ElasticSearch";
        }
        return $response;
    }
    
    /**
     * Get Public User
     * 
     * Gets the default public user, which only has permission to view and no dashboard permissions
     * 
     * @return \snac\data\User Public user
     */
    public function getDefaultPublicUser() {
        //$user = $this->uStore->getPublicUser();
        $user = new \snac\data\User();
        
        return $user;
    }
}

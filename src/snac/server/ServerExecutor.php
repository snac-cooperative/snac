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
    
    /**
     * Constructor
     * 
     * @param string[] $user The user array from the Server's input
     */
    public function __construct($user = null) {
        global $log;
        
        $this->cStore = new \snac\server\database\DBUtil();
        $this->uStore = new \snac\server\database\DBUser();
        
        /*
         * Create the user and fill in their userID from the database We assume that a non-null $user at least
         * has a valid email. If the getUserName() is null, then user the email as userName.
         *
         * readUser() will check getUserID(), getUserName() and even getEmail().
         *
         * The expectation is that userID or userName will have valid values. If not then the user probably
         * lost their userid, so just pull back the first user with the email address in getEmail(). There is
         * also the expectation that the case of missing both userID and userName is very rare.
         */ 
        if ($user != null) {
            $this->user = new \snac\data\User($user);
            $tmpUser = $this->uStore->readUser($this->user);
            if ($tmpUser !== false) {
                $this->user->setUserID($tmpUser->getUserID());
            }
        }
        
        // create a log channel
        $this->logger = new \Monolog\Logger('ServerExec');
        $this->logger->pushHandler($log);
    }

    private function checkOAuth($user) {
        // Google OAuth Settings (from Config)
        $clientId     = \snac\Config::$OAUTH_CONNECTION["google"]["client_id"];
        $clientSecret = \snac\Config::$OAUTH_CONNECTION["google"]["client_secret"];
        // Change this if you are not using the built-in PHP server
        $redirectUri  = \snac\Config::$OAUTH_CONNECTION["google"]["redirect_uri"];
        // Initialize the provider
        $provider = new \League\OAuth2\Client\Provider\Google(compact('clientId', 'clientSecret', 'redirectUri'));

        try {
            $this->logger->addDebug("Trying to connect to OAuth2 Server to get user details");

            $accessToken = new AccessToken($user->getToken());

            $ownerDetails = $provider->getResourceOwner($accessToken);

            if ($ownerDetails->getEmail() != $user->getEmail()) {
                // This user's token doesn't match the user's email
                $this->logger->addDebug("Email mismatch from the user and OAuth details");
                return false;
            }
            $this->logger->addDebug("Successfully got user details from OAuth2 Server");
        } catch (\Exception $e) {
            $this->logger->addDebug("Could not get user details from OAuth2 Server: ".$e->getMessage());
            return false;
        }

        // Could connect using the token, emails matched, so all's good
        return true;
    }
    
    /**
     * Authenticate User
     * 
     * Authenticates the user by checking the user store (dbuser)
     * 
     * @param \snac\data\User $user User information to check
     * @return boolean true if user authenticated, false if not
     */
    public function authenticateUser($user) {
        if ($user != null) {
            $this->logger->addDebug("Attempting to authenticate user", $user->toArray());
            
            // If the user exists in our database and we know about this token,
            // then we'll let them continue. Else, we will try to authenticate
            // with Google, check their information, and add them to the database. 
            
            // Check that the user has a session in the database
            //$this->user = $this->uStore->checkSessionActive($user);
            $this->user = false;

            if ($user->getUserName() != null) {
                // For purposes of authentication, the UserName is required

                $this->user = $this->uStore->readUser($user);

                if ($this->user === false) {
                    // The user wasn't found in the database

                    // For version 1.1.0, we will go ahead and add them
                    // if the token is valid
                    if ($this->checkOAuth($user))
                        $this->user = $this->uStore->createUser($user);
                    else
                        throw new \snac\exceptions\SNACUserException("Invalid OAuth user");
                }
            } else {
                throw new \snac\exceptions\SNACUserException("Username required for login");
            }

            if ($this->user !== false && $this->uStore->sessionExists($this->user)) {
                if ($this->uStore->sessionActive($this->user)) {
                    // The session is still active
                    $this->logger->addDebug("User is valid from SNAC details");
                    return true;
                } else {
                    // The session has expired, so we will be nice and extend
                    $this->logger->addDebug("User is valid from SNAC details");
                    return $this->uStore->sessionExtend($this->user);
                }

            } else if ($this->user !== false && $this->user->getToken() != null) {
                // Try to add the session (check google first)

                if ($this->checkOAuth($this->user) &&
                    $this->uStore->addSession($this->user)) {
                    // Google approved the session and we successfully added it
                    return true;
                } else {
                    throw new \snac\exceptions\SNACUserException("User did not have a valid session to capture");
                }
            } else {
                throw new \snac\exceptions\SNACUserException("User did not have session");
            }

            $this->logger->addDebug("Something went wrong checking user in SNAC");
            return false;
        }
        
        // If the user is null, then we're okay on authentication (no permissions)
        return true;
    }
    
    /**
     * Start the user session
     * 
     * Starts the user session (authenticates the user, if needed), and fills out the response
     * with a sucess or failure based on whether or not the user was successfully authenticated, as
     * well as the user information (snac ID) which may be useful to the web ui and other clients
     * 
     * @return string[] The response to send to the client
     */
    public function startSession() {
        $response = array();
        
        // TODO In the future, we may want to put Google OAuth here so we don't check the user
        // against Google for each operation on the server
        $this->authenticateUser($this->user);
        
        if ($this->user != null) {
            $response["user"] = $this->user->toArray();
            $response["result"] = "success";
        } else {
            $response["result"] = "failure";
        }
        
        return $response;
    }
    
    /**
     * End user session
     * 
     * This ends the current user's session by using DBUser's removeSession method.
     * 
     * @return string[] The response to send to the client
     */
    public function endSession() {
        $response = array();
        
        if ($this->user != null) {
            $this->uStore->removeSession($this->user);
            $response["user"] = $this->user->toArray();
            $response["result"] = "success";
        } else {
            $response["result"] = "failure";
        }
        
        return $response;
    }
    
    /**
     * Search Vocabulary
     * 
     * Searches the vocabulary from the database, based on the input given and returns
     * a list of results
     * 
     * @param string[] $input Direct server input
     * @return string[] The response to send to the client
     */
    public function searchVocabulary(&$input) {
        $response = array();
        $response["results"] = $this->cStore->searchVocabulary(
                $input["type"],
                $input["query_string"]);
        return $response;
    }
    
    /**
     * Get User Information
     * 
     * Gets the user information, including their user information from the database as well
     * as the list of constellations they have in each stage of editing/review.  Creates and returns
     * an array of the user information to return to the client.
     * 
     * @return string[] The response to send to the client
     */
    public function userInformation() {
        $response = array();

        if ($this->user == null) {
            $response["result"] = "failure";
            return $response;
        }
        $response["result"] = "success";
        
        $response["user"] = $this->user->toArray();
        
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
    
    /**
     * Insert Constellation
     * 
     * Uses DBUtil to write a new constellation to the database.  
     * 
     * @param string[] $input Input array from the Server object
     * @return string[] The response to send to the client
     */
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
    
    /**
     * Update Constellation
     * 
     * Uses DBUtil to update a constellation (from the input) in the database.  If no operation is set on the
     * Constellation, it returns a success as if it wrote, but without modifying the database.
     * 
     * @param string[] $input Input array from the Server object
     * @throws \snac\exceptions\SNACException
     * @return string[] The response to send to the client
     */
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
    
    /**
     * Unlock Constellation
     * 
     * Lowers the lock on a constellation from "currently editing" to "locked editing."  The constellation
     * must be given in the input.
     * 
     * @param string[] $input Input array from the Server object
     * @throws \snac\exceptions\SNACException
     * @return string[] The response to send to the client
     */
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
    
    /**
     * Publish Constellation
     * 
     * Updates the status of the given input's constellation to "published."  On successful publish, this method
     * also updates the Elastic Search indices to include the new version of this Constellation, if ES is being used
     * in this install.
     * 
     * @param string[] $input Input array from the Server object
     * @throws \Exception
     * @return string[] The response to send to the client
     */
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
    
    /**
     * Read Constellation
     * 
     * Looks for a constellationid, arkid, or testid in the input, and then reads the constellation data and
     * creates a Constellation object.  The object is converted to an array and put in the response to send to the
     * user.
     * 
     * If given an ark or test id, this method will use the parser to read the latest version of the EAC-CPF and
     * create a Constellation, without going through the database.
     * 
     * If given a constellationid, it reads the constellation from the database.  If trying to read a constellation
     * without a published version, an exception is thrown.
     * 
     * @param string[] $input Input array from the Server object
     * @throws \snac\exceptions\SNACInputException
     * @return string[] The response to send to the client
     */
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
                
                // Get the list of constellations locked editing for this user
                $inList = false;
                if ($this->user != null) {
                    $editable = $this->cStore->listConstellationsWithStatusForUser($this->user);
                    if ($editable !== false) {
                        foreach ($editable as $cEdit) {
                            if ($cEdit->getID() == $constellation->getID()) {
                                $inList = true;
                                break;
                            }
                        }
                    }
                }
                if ($this->cStore->readConstellationStatus($constellation->getID()) == "published" || $inList) {
                    $constellation->setStatus("editable");
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
    
    /**
     * Edit Constellation
     * 
     * Similar to readConstellation, this method returns a Constellation on the response.  If the client provided
     * an ark id, this constellation is generated by using the EAC-CPF parser.  If the client provided a 
     * constellation id, it upgrades the status to "currently editing" and then returns the constellation in the response.
     * 
     * @param string[] $input Input array from the Server object
     * @throws \snac\exceptions\SNACPermissionException
     * @return string[] The response to send to the client
     */
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
                    $success = $this->cStore->writeConstellationStatus($this->user, $cId, "currently editing");
                    if ($success === false) {
                        $this->logger->addError("Writing Constellation Status failed", array("user"=>$this->user, "id"=>$cId));
                    }
        
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
    
    /**
     * Get Recently Published
     * 
     * Uses Elastic Search to get the most recently published Constellations.  Then, takes the ES results and 
     * looks them up in our database to get summary constellations for each of the most recently published versions.
     * Puts them as a list on the response for the client.
     * 
     * @return string[] The response to send to the client
     */
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

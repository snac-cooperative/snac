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

use \snac\interfaces\ServerInterface;
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
     * Response headers
     *
     * @var string[] $responseHeaders response headers for the web server
     */
    private $responseHeaders = null;


    /**
     * @var \Monolog\Logger $logger Logger for this server
     */
    private $logger = null;

    /**
     * Constructor
     *
     * Takes the input parameters to the web server as an associative array.  These will likely
     * be the GET or POST variables from the user's web browser.
     *
     * @param array $input web input as an associative array
     */
    public function __construct($input) {
        global $log;

        $this->responseHeaders = array();
        $this->input = $input;
        if (!isset($this->input["command"]))
            $this->input["command"] = "";
        

        // create a log channel
        $this->logger = new \Monolog\Logger('WebUI');
        $this->logger->pushHandler($log);
        
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

        $this->logger->debug("Starting to handle user request", $this->input);
        $connect = new ServerConnect();


        // Start the session
        session_name("SNACWebUI");
        session_start();


        // Create the display for local templates
        $display = new display\Display();

        // Replace these with your token settings
        // Create a project at https://console.developers.google.com/
        $clientId     = \snac\Config::$OAUTH_CONNECTION["google"]["client_id"];
        $clientSecret = \snac\Config::$OAUTH_CONNECTION["google"]["client_secret"];
        // Change this if you are not using the built-in PHP server
        $redirectUri  = \snac\Config::$OAUTH_CONNECTION["google"]["redirect_uri"];
        // Initialize the provider
        $provider = new \League\OAuth2\Client\Provider\Google(compact('clientId', 'clientSecret', 'redirectUri'));
        $_SESSION['oauth2state'] = $provider->getState();
        
        // These are the things you are allowed to do without logging in.
        $publicCommands = array(
                "login",
                "login2",
                "search",
                "view"
        );
        
        // Null user object unless set by the session
        $user = null;

        // If the user is not logged in, send to the home screen
        if (empty($_SESSION['token'])) {
            // If the user wants to do something, but hasn't logged in, then
            // send them to the home page.
            if (!empty($this->input["command"]) &&
                !(in_array($this->input["command"], $publicCommands)))
                $this->input["command"] = "login";

        } else {
            $token = unserialize($_SESSION['token']);
            $ownerDetails = unserialize($_SESSION['user_details']);
            $user = $this->createUser($ownerDetails, $token);
            $display->setUserData($user->toArray());
        }


        // Display the current information
        if ($this->input["command"] == "edit") {
            $query = $this->input;
            $query["user"] = $user->toArray();
            $this->logger->addDebug("Sending query to the server", $query);
            $serverResponse = $connect->query($query);
            $this->logger->addDebug("Received server response");
            $display->setTemplate("edit_page");
            if (isset($serverResponse["constellation"])) {
                $constellation = $serverResponse["constellation"];
                if (\snac\Config::$DEBUG_MODE == true) {
                    $display->addDebugData("constellationSource", json_encode($serverResponse["constellation"], JSON_PRETTY_PRINT));
                    $display->addDebugData("serverResponse", json_encode($serverResponse, JSON_PRETTY_PRINT));
                }
                $this->logger->addDebug("Setting constellation data into the page template");
                $display->setData($constellation);
            }
        } else if ($this->input["command"] == "new") {
            $display->setTemplate("edit_page");
            $constellation = new \snac\data\Constellation();
            $constellation->setOperation(\snac\data\Constellation::$OPERATION_INSERT);
            $constellation->addNameEntry(new \snac\data\NameEntry());
            if (\snac\Config::$DEBUG_MODE == true) {
                $display->addDebugData("constellationSource", json_encode($constellation, JSON_PRETTY_PRINT));
            }
            $this->logger->addDebug("Setting constellation data into the page template");
            $display->setData($constellation);
        } else if ($this->input["command"] == "view") {
            $query = array(); //$this->input;
            $query["constellationid"] = $this->input["constellationid"];
            $query["command"] = "read";
            if (isset($user) && $user != null)
                $query["user"] = $user->toArray();
            $this->logger->addDebug("Sending query to the server", $query);
            $serverResponse = $connect->query($query);
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
        } else if ($this->input["command"] == "preview") {
            // If just previewing, then all the information should come VIA post to build the preview
            $mapper = new \snac\client\webui\util\ConstellationPostMapper();
      
            // Get the constellation object
            $constellation = $mapper->serializeToConstellation($this->input);
            
            if ($constellation != null) {
                $display->setTemplate("view_page");
                if (\snac\Config::$DEBUG_MODE == true) {
                    $display->addDebugData("constellationSource", json_encode($constellation, JSON_PRETTY_PRINT));
                }
                $this->logger->addDebug("Setting constellation data into the page template");
                $display->setData($constellation);
            }
        } else if ($this->input["command"] == "dashboard") {
            $display->setTemplate("dashboard");
            // Ask the server for a list of records to edit
            $ask = array("command"=>"user_information",
                    "user" => $user->toArray()
            );
            $this->logger->addDebug("Sending query to the server", $ask);
            $serverResponse = $connect->query($ask);
            $this->logger->addDebug("Received server response", array($serverResponse));
            $this->logger->addDebug("Setting dashboard data into the page template");
            
            $recentConstellations = $connect->query(array(
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
        } else if ($this->input["command"] == "profile") {
            $display->setTemplate("profile_page");
            // Ask the server for a list of records to edit
            $ask = array("command"=>"user_information",
                    "user" => $user->toArray()
            );
            $this->logger->addDebug("Sending query to the server", $ask);
            $serverResponse = $connect->query($ask);
            $this->logger->addDebug("Received server response", $ask);
            $this->logger->addDebug("Setting dashboard data into the page template");
            $display->setData($serverResponse);
        } else if ($this->input["command"] == "login") {
            // Destroy the old session
            session_destroy();
            // Restart the session
            session_name("SNACWebUI");
            session_start();

            // if the user wants to log in, then send them to the login server
            $authUrl = $provider->getAuthorizationUrl();
            header('Location: ' . $authUrl);
        } else if ($this->input["command"] == "login2") {

            // OAuth Stuff //

            // Try to get an access token (using the authorization code grant)
            $token = $provider->getAccessToken('authorization_code',
                array('code' => $_GET['code']));
            // Set the token in session variable
            $_SESSION['token'] = serialize($token);

            // We got an access token, let's now get the owner details
            $ownerDetails = $provider->getResourceOwner($token);

            // Set the user details in the session
            $_SESSION['user_details'] = serialize($ownerDetails);

            // Go directly to the Dashboard, do not pass Go, do not collect $200
            header('Location: index.php?command=dashboard');
        } else if ($this->input["command"] == "logout") {
            // Destroy the old session
            session_destroy();
            // Restart the session
            session_name("SNACWebUI");
            session_start();

            // Go to the homepage
            header('Location: index.php');
        } else if ($this->input["command"] == "save") {
            // If saving, this is just an ajax/JSON return.
            
            $mapper = new \snac\client\webui\util\ConstellationPostMapper();

            // Get the constellation object
            $constellation = $mapper->serializeToConstellation($this->input);

            $this->logger->addDebug("writing constellation", $constellation->toArray());

            // Build a data structure to send to the server
            $request = array("command"=>"update_constellation");

            // Send the query to the server
            $request["constellation"] = $constellation->toArray();
            $request["user"] = $user->toArray();
            $serverResponse = $connect->query($request);

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

            // Generate response to the user's web browser
            $this->response = json_encode($response, JSON_PRETTY_PRINT);
            array_push($this->responseHeaders, "Content-Type: text/json");
            return;

        } else if ($this->input["command"] == "save_publish") {
            // If saving, this is just an ajax/JSON return.
            
            $mapper = new \snac\client\webui\util\ConstellationPostMapper();

            // Get the constellation object
            $constellation = $mapper->serializeToConstellation($this->input);

            $this->logger->addDebug("writing constellation", $constellation->toArray());

            // Build a data structure to send to the server
            $request = array("command"=>"update_constellation");

            // Send the query to the server
            $request["constellation"] = $constellation->toArray();
            $request["user"] = $user->toArray();
            $serverResponse = $connect->query($request);

            $response = array();
            $response["server_debug"] = array();
            $response["server_debug"]["update"] = $serverResponse;
            
            if (!is_array($serverResponse)) {
                $this->logger->addDebug("server's response: $serverResponse");
            } else {

                if (isset($serverResponse["constellation"])) {
                    $this->logger->addDebug("server's response written constellation", $serverResponse["constellation"]);
                }
            
                if (isset($serverResponse["result"]) && $serverResponse["result"] == "success"
                        && isset($serverResponse["constellation"])) {
                    $request["command"] = "publish_constellation";
                    $request["constellation"] = $serverResponse["constellation"];
                    $serverResponse = $connect->query($request);
                    $response["server_debug"]["publish"] = $serverResponse;
                    if (isset($serverResponse["result"]))
                        $response["result"] = $serverResponse["result"];
                    if (isset($serverResponse["error"])) 
                        $response["error"] = $serverResponse["error"];
          
                }
            }

            // Generate response to the user's web browser
            $this->response = json_encode($response, JSON_PRETTY_PRINT);
            array_push($this->responseHeaders, "Content-Type: text/json");
            return;

        } else if ($this->input["command"] == "vocabulary") {
            $this->logger->addDebug("Requesting Vocabulary");
            // Check what kind of vocabulary is wanted, and ask server for it
            $request = array();
            $request["command"] = "vocabulary";
            $request["type"] = $this->input["type"];
            if (isset($request["type"])) {
                if (strpos($request["type"], "ic_") !== false) {
                    $this->logger->addDebug("Requesting Sources as Vocabulary List");
                    // This is a query into a constellation for "vocabulary"
                    if (isset($this->input["id"]) && isset($this->input["version"])) {
                        $serverResponse = $connect->query(array("constellationid"=>$this->input["id"], 
                                "version"=>$this->input["version"],
                                "command"=>"read"));
                        $this->logger->addDebug("tried to get the constellation with response", $serverResponse);
                        if (isset($serverResponse["constellation"])) {
                            $constellation = new \snac\data\Constellation($serverResponse["constellation"]);
                            $response = array();
                            $response["results"] = array();
                            foreach ($constellation->getSources() as $source) {
                                array_push($response["results"], array(
                                        "id" => $source->getID(),
                                        "text" => $source->getDisplayName()
                                ));
                            }
                            $this->logger->addDebug("created the following response list of sources", $response);
                            $this->response = json_encode($response, JSON_PRETTY_PRINT);
                        }
                    }
                } else {
                    $this->logger->addDebug("Requesting Controlled Vocabulary List");
                    // This is a strict query for a controlled vocabulary term
                    $queryString = "";
                    if (isset ($this->input["q"]))
                        $queryString = $this->input["q"];
                    $request["query_string"] = $queryString;
        
                    // Send the query to the server
                    $serverResponse = $connect->query($request);
        
                    foreach ($serverResponse["results"] as $k => $v)
                        $serverResponse["results"][$k]["text"] = $v["value"];
        
                    // Send the response back to the web client
                    $this->response = json_encode($serverResponse, JSON_PRETTY_PRINT);
                }
            }
            array_push($this->responseHeaders, "Content-Type: text/json");
            return;

        } else if ($this->input["command"] == "search") {
            $this->logger->addDebug("Searching for a Constellation");
            // ElasticSearch Handler
            $eSearch = null;
            if (\snac\Config::$USE_ELASTIC_SEARCH) {
                $this->logger->addDebug("Creating ElasticSearch Client");
                $eSearch = \Elasticsearch\ClientBuilder::create()
                ->setHosts([\snac\Config::$ELASTIC_SEARCH_URI])
                ->setRetries(0)
                ->build();
                
                
                $params = [
                        'index' => 'rtest',
                        'type' => 'prototype_name_search',
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
                $this->response = json_encode(array("notice" => "Not Using ElasticSearch"), JSON_PRETTY_PRINT);
                array_push($this->responseHeaders, "Content-Type: text/json");
                
            }
        } else {
            // The WebUI is displaying the landing page only
            
            // Get the list of recently published constellations

            $request = array();
            $request["command"] = "recently_published";
            $recentConstellations = $connect->query($request)["constellation"];
            
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
        $this->logger->addDebug("Creating response page from template with data");
        array_push($this->responseHeaders, "Content-Type: text/html");
        $this->response = $display->getDisplay();
        $this->logger->addDebug("Response page created, sending back to user");

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

        return $this->responseHeaders;
        return array (
                "Content-Type: text/html"
        );
    }
    
    private function createUser($googleUser, $googleToken) {
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

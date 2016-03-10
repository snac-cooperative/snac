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

        // If the user is not logged in, send to the home screen
        if (empty($_SESSION['token'])) {
            // If the user wants to do something, but hasn't logged in, then
            // send them to the home page.
            if (!empty($this->input["command"]) &&
                ($this->input["command"] != "login" && $this->input["command"] != "login2"))
                $this->input["command"] = "";

        } else {
            $token = unserialize($_SESSION['token']);
            $ownerDetails = unserialize($_SESSION['user_details']);
            $user = array (
                "first" => $ownerDetails->getFirstName(),
                "last" => $ownerDetails->getLastName(),
                "name" => $ownerDetails->getName(),
                "avatar" => $ownerDetails->getAvatar(),
                "email" => $ownerDetails->getEmail()
            );
            $display->setUserData($user);
        }


        // Display the current information
        if ($this->input["command"] == "edit") {
            $serverResponse = $connect->query($this->input);
            $display->setTemplate("edit_page");
            if (isset($serverResponse["constellation"])) {
                $constellation = $serverResponse["constellation"];
                if (\snac\Config::$DEBUG_MODE == true) {
                    $display->addDebugData("constellationSource", json_encode($serverResponse["constellation"], JSON_PRETTY_PRINT));
                    $display->addDebugData("serverResponse", json_encode($serverResponse, JSON_PRETTY_PRINT));
                }
                $display->setData($constellation);
            }
        } else if ($this->input["command"] == "dashboard") {
            $display->setTemplate("dashboard");
            // Ask the server for a list of records to edit
            $ask = array("command"=>"user_information");
            $serverResponse = $connect->query($ask);
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

            // Build a data structure to send to the server
            $request = array("command"=>"update_constellation");

            // Send the query to the server
            $request["constellation"] = $constellation->toArray();
            $serverResponse = $connect->query($request);
            

            $response = array();
            $response["server_debug"] = $serverResponse;
            $response["result"] = $serverResponse["result"];
            
            // Get the server's response constellation
            if (isset($serverResponse["constellation"])) {
                $updatedConstellation = new \snac\data\Constellation($serverResponse["constellation"]);
                $mapper->reconcile($updatedConstellation);
                
                $response["updates"] = $mapper->getUpdates();
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
                                        "text" => $source->getText() . " (" . $source->getURI() . ")"
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

        } else {
            $display->setTemplate("landing_page");
        }
        array_push($this->responseHeaders, "Content-Type: text/html");
        $this->response = $display->getDisplay();

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
 
}

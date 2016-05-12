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
use League\OAuth2\Client\Token\AccessToken;

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
        
        // Create an executor to perform all the actions
        $executor = new WebUIExecutor();

        // Create the display for local templates
        $display = new display\Display();


        // Code to take the site down for maintenance
        if (0) {
            $display->setTemplate("down_page");
            array_push($this->responseHeaders, "Content-Type: text/html");
            $this->response = $display->getDisplay();
            return;
        }
        // End Code for maintenance

        // Create an empty user object.  May be filled by the Session handler
        $user = null;
        

        // These are the things you are allowed to do without logging in.
        $publicCommands = array(
                "login",
                "login2",
                "search",
                "view"
        );
        

        // *****************************************
        // Session and User Information
        // *****************************************

        // Start the session
        session_name("SNACWebUI");
        session_start();

        // Google OAuth Settings (from Config)
        $clientId     = \snac\Config::$OAUTH_CONNECTION["google"]["client_id"];
        $clientSecret = \snac\Config::$OAUTH_CONNECTION["google"]["client_secret"];
        // Change this if you are not using the built-in PHP server
        $redirectUri  = \snac\Config::$OAUTH_CONNECTION["google"]["redirect_uri"];
        // Initialize the provider
        $provider = new \League\OAuth2\Client\Provider\Google(compact('clientId', 'clientSecret', 'redirectUri'));
        $_SESSION['oauth2state'] = $provider->getState();



        // If the user is not logged in, send to the home screen. If logged in, then fill in User object
        if (empty($_SESSION['snac_user'])) {
            // If the user wants to do something, but hasn't logged in, then
            // send them to the home page.
            if (!empty($this->input["command"]) &&
                !(in_array($this->input["command"], $publicCommands)))
                $this->input["command"] = "";

        } else {
            $token = unserialize($_SESSION['token']);
            $ownerDetails = unserialize($_SESSION['user_details']);
            $user = unserialize($_SESSION['snac_user']);
            
            if ($user->getToken()["expires"] <= time()) {
                // if the user's token has expired, we need to ask for a refresh
                // if the refresh is successful, then great, keep going.
                // however, if not, then we need to either return an error asking the user
                //    to log back in (if returning JSON) OR redirect them to the login page
                //    (if returning HTML)

                // startSNACSession will connect to the server and ask to start a session.  The server will
                // reissue the session and extend the token expiration if the session does already exist.
                $tmpUser = $executor->startSNACSession($user); 
                if ($tmpUser !== false) {
                    $user = $tmpUser;
                    $_SESSION["snac_user"] = serialize($user);
                } else {
                    $this->logger->addError("User was unable to restart session, but we allowed them through", array($user));
                    // TODO in Version 1.2, this needs to actually redirect them to the login page or give an error
                    // if they were actually trying to get a JSON response.
                }
            }
            
            // Create the PHP User object
            // $user = $executor->createUser($ownerDetails, $token);
            
            // Set the user information into the display object
            $display->setUserData($user->toArray());
        }
        

        // *************************************************
        // Workflow: Handle user commands, perform actions
        // *************************************************


        // Session-Level Commands
        if ($this->input["command"] == "login") {
        
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
            
            
            
            $tokenUnserialized = unserialize($_SESSION['token']);
            $ownerDetailsUnserialized = unserialize($_SESSION['user_details']);
            // Create the PHP User object
            $user = $executor->createUser($ownerDetailsUnserialized, $tokenUnserialized);
            
            $tmpUser = $executor->startSNACSession($user);

            if ($tmpUser !== false)
                $user = $tmpUser;

            $_SESSION['snac_user'] = serialize($user); 

            // Go directly to the Dashboard, do not pass Go, do not collect $200
            header('Location: index.php?command=dashboard');
        
        } else if ($this->input["command"] == "logout") {
            
            $executor->endSNACSession($user);
        
            // Destroy the old session
            session_destroy();
            // Restart the session
            session_name("SNACWebUI");
            session_start();
            $_SESSION = array();
        
            // Go to the homepage
            header('Location: index.php');
        
        // Editing, Preview, View, and Other Commands
        } else if ($this->input["command"] == "edit") {
            $executor->displayEditPage($this->input, $display, $user);
        } else if ($this->input["command"] == "new") {
            $executor->displayNewEditPage($display);
        } else if ($this->input["command"] == "view") {
            $executor->displayViewPage($this->input, $display, $user);
        } else if ($this->input["command"] == "preview") {
            $executor->displayPreviewPage($this->input, $display);
        } else if ($this->input["command"] == "dashboard") {
            $executor->displayDashboardPage($display, $user);
        } else if ($this->input["command"] == "profile") {
            $executor->displayProfilePage($display, $user);

        } else if ($this->input["command"] == "save") {
            // If saving, this is just an ajax/JSON return.
            $response = $executor->saveConstellation($this->input, $user);
            $this->response = json_encode($response, JSON_PRETTY_PRINT);
            array_push($this->responseHeaders, "Content-Type: text/json");
            return;

        } else if ($this->input["command"] == "save_unlock") {
            // If saving, this is just an ajax/JSON return.
            $response = $executor->saveAndUnlockConstellation($this->input, $user);
            $this->response = json_encode($response, JSON_PRETTY_PRINT);
            array_push($this->responseHeaders, "Content-Type: text/json");
            return;

        } else if ($this->input["command"] == "unlock") {
            // If saving, this is just an ajax/JSON return.
            $response = $executor->unlockConstellation($this->input, $user);
            // if unlocked by constellationid parameter, then send them to the dashboard.
            if (!isset($response["error"]) && !isset($this->input["entityType"])) {
                header("Location: index.php?command=dashboard");
                return;
            } else {
                $this->response = json_encode($response, JSON_PRETTY_PRINT);
                array_push($this->responseHeaders, "Content-Type: text/json");
            }
            return;

        } else if ($this->input["command"] == "save_publish") {
            // If saving, this is just an ajax/JSON return.
            $response = $executor->saveAndPublishConstellation($this->input, $user);
            $this->response = json_encode($response, JSON_PRETTY_PRINT);
            array_push($this->responseHeaders, "Content-Type: text/json");
            return;

        } else if ($this->input["command"] == "publish") {
            // If saving, this is just an ajax/JSON return.
            $response = $executor->publishConstellation($this->input, $user);
            $this->response = json_encode($response, JSON_PRETTY_PRINT);
            array_push($this->responseHeaders, "Content-Type: text/json");
            return;

        } else if ($this->input["command"] == "vocabulary") {
            $response = $executor->performVocabularySearch($this->input);
            $this->response = json_encode($response, JSON_PRETTY_PRINT);
            array_push($this->responseHeaders, "Content-Type: text/json");
            return;

        } else if ($this->input["command"] == "search") {
            $response = $executor->performNameSearch($this->input);
            $this->response = json_encode($response, JSON_PRETTY_PRINT);
            array_push($this->responseHeaders, "Content-Type: text/json");
            return;
            
        } else {
            // The WebUI is displaying the landing page only
            $executor->displayLandingPage($display);

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
    
}

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
        $display->setLanguage("english");

        // Create an empty user object.  May be filled by the Session handler
        $user = null;

        // Create an empty list of permissions.
        $permissions = array();

        // These are the things you are allowed to do without logging in.
        $publicCommands = array(
                "login",
                "login2",
                "search",
                "view",
                "details",
                "sources",
                "download",
                "error",
                "vocabulary",
                "quicksearch",
                "relations",
                "maybesame",
                "diff",
                "explore",
                "visualize",
                "history",
                "history_diff",
                "static",
                "api_help",
                "contact",
                "stats",
                "feedback"
        );

        // These are read-only commands that are allowed in read-only mode
        $readOnlyCommands = array(
            "search",
            "view",
            "details",
            "sources",
            "download",
            "error",
            "vocabulary",
            "quicksearch",
            "relations",
            "explore",
            "history_diff",
            "static",
            "api_help",
            "visualize",
            "stats",
            "history"
        );


        // Code to take the site down for maintenance
        if (\snac\Config::$SITE_OFFLINE) {
            $display->setTemplate("down_page");
            array_push($this->responseHeaders, "Content-Type: text/html");
            $this->response = $display->getDisplay();
            return;
        }
        // End Code for maintenance
        // Code to take the site into read-only mode (destroys login session)
        if (\snac\Config::$READ_ONLY) {
            // Make sure there is no session to keep track of anymore
            session_name("SNACWebUI");
            session_start();
            session_destroy();

            // Overrule commands that are not read-only
            if (!empty($this->input["command"]) &&
                    !(in_array($this->input["command"], $readOnlyCommands))) {
                $display->setTemplate("readonly_page");
                array_push($this->responseHeaders, "Content-Type: text/html");
                $this->response = $display->getDisplay();
                return;
            }
        }
        // End Code for maintenance


        // *****************************************
        // Session and User Information
        // *****************************************

        // Start the session
        session_name("SNACWebUI");
        session_start();

        // Google OAuth Settings (from Config)
        //$clientId     = \snac\Config::$OAUTH_CONNECTION["google"]["client_id"];
        //$clientSecret = \snac\Config::$OAUTH_CONNECTION["google"]["client_secret"];
        // Change this if you are not using the built-in PHP server
        //$redirectUri  = \snac\Config::$OAUTH_CONNECTION["google"]["redirect_uri"];
        // Initialize the provider
        //$provider = new \League\OAuth2\Client\Provider\Google(compact('clientId', 'clientSecret', 'redirectUri'));
        $provider = new \ChrisHemmings\OAuth2\Client\Provider\Drupal([
            'clientId'          => \snac\Config::$OAUTH_CONNECTION["drupal"]["client_id"],
            'clientSecret'      => \snac\Config::$OAUTH_CONNECTION["drupal"]["client_secret"],
            'redirectUri'       => \snac\Config::$OAUTH_CONNECTION["drupal"]["redirect_uri"],
            'baseUrl'           => \snac\Config::$OAUTH_CONNECTION["drupal"]["drupal_uri"],
        ]);
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

            // Pull out permissions from the $user object and make them available to the template. This could
            // be done faster by storing them in the session variables along with the user object
            $permissions = array();
            foreach ($user->getRoleList() as $role) {
                foreach ($role->getPrivilegeList() as $privilege) {
                    $permissions[str_replace(" ", "", $privilege->getLabel())] = true;
                }
            }
            // NOTE: For use in Twig, the spaces HAVE BEEN REMOVED from the permission labels
            $display->setPermissionData($permissions);

            // Set the user information into the executor and server connection object
            $executor->setUser($user);
            $executor->setPermissionData($permissions);
        }


        // *************************************************
        // Workflow: Handle user commands, perform actions
        // *************************************************


        switch($this->input["command"]) {

            // Session-Level Commands
            case "login":
                // Destroy the old session
                session_destroy();
                // Restart the session
                session_name("SNACWebUI");
                session_start();

                if (isset($this->input["r"])) {
                    $_SESSION['redirect_postlogin'] = $this->input["r"];
                }

                // if the user wants to log in, then send them to the login server
                $authUrl = $provider->getAuthorizationUrl();
                header('Location: ' . $authUrl);
                return;

            case "login2":
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

                $redirect = \snac\Config::$WEBUI_URL . "/dashboard";
                if (isset($_SESSION['redirect_postlogin'])) {
                    $tmp = $_SESSION['redirect_postlogin'];
                    if (strstr($tmp, 'command') !== false && strstr($tmp, 'logout') === false)
                        $redirect = htmlspecialchars_decode(urldecode($tmp));
                    unset($_SESSION['redirect_postlogin']);
                }

                $tokenUnserialized = unserialize($_SESSION['token']);
                $ownerDetailsUnserialized = unserialize($_SESSION['user_details']);
                // Create the PHP User object
                $user = $executor->createUser($ownerDetailsUnserialized, $tokenUnserialized);
                $executor->setUser($user);
                $tmpUser = $executor->startSNACSession();

                if ($tmpUser === false) {
                    session_destroy();
                    $display->setTemplate("error_page");
                    $display->setData(array(
                        "type" => "Invalid User",
                        "message" => "The Google account does not exist in our system. Please log-in again with a different account."
                    ));
                    array_push($this->responseHeaders, "Content-Type: text/html");
                    $this->response = $display->getDisplay();
                    return;

                }
                $user = $tmpUser;

                $_SESSION['snac_user'] = serialize($user);

                // Go directly to the Dashboard, do not pass Go, do not collect $200
                header("Location: $redirect");
                return;

            case "logout":
                $executor->endSNACSession();

                // Destroy the old session
                session_destroy();
                // Restart the session
                session_name("SNACWebUI");
                session_start();
                $_SESSION = array();

                // Go to the homepage
                header('Location: ' . \snac\Config::$WEBUI_URL);
                return;

            // Editing, Preview, View, and Other Commands
            case "edit":
            case "edit_part":
                if (isset($permissions["Edit"]) && $permissions["Edit"]) {
                    $executor->displayEditPage($this->input, $display);
                } else {
                    $executor->displayPermissionDeniedPage("Edit Constellation", $display);
                }
                break;
            case "new":
                if (isset($permissions["Create"]) && $permissions["Create"]) {
                    $executor->displayNewPage($display);
                } else {
                    $executor->displayPermissionDeniedPage("Create Constellation", $display);
                }
                break;
            case "new_edit":
                if (isset($permissions["Create"]) && $permissions["Create"]) {
                    $executor->displayNewEditPage($this->input, $display);
                } else {
                    $executor->displayPermissionDeniedPage("Create Constellation", $display);
                }
                break;
            case "view":
                $executor->displayViewPage($this->input, $display);
                break;
            case "details":
                $executor->displayDetailedViewPage($this->input, $display);
                break;
            case "sources":
                $executor->displaySourcesPage($this->input, $display);
                break;
            case "relations":
                $response = $executor->performRelationsQuery($this->input);
                break;
            case "maybesame":
                $response = $executor->displayMaybeSameListPage($this->input, $display);
                break;
            case "add_maybesame":
                if (isset($permissions["Publish"]) && $permissions["Publish"]) {
                    $response = $executor->addMaybeSameAssertion($this->input);
                } else {
                    $executor->displayPermissionDeniedPage("Add Maybe Same", $display);
                }
                break;
            case "assert_notsame":
                $response = $executor->processNotSameAssertion($this->input);
                break;
            case "diff":
                $response = $executor->displayMaybeSameDiffPage($this->input, $display);
                break;
            case "diff_merge":
                if (isset($permissions["Merge"]) && $permissions["Merge"]) {
                    $response = $executor->displayMaybeSameDiffPage($this->input, $display, true);
                } else {
                    $executor->displayPermissionDeniedPage("Compare Constellations for Merge", $display);
                }
                break;
            case "merge":
                if (isset($permissions["Merge"]) && $permissions["Merge"]) {
                    $response = $executor->processMerge($this->input, $display);
                } else {
                    $executor->displayPermissionDeniedPage("Merge Constellations", $display);
                }
                break;
            case "auto_merge":
                if (isset($permissions["Merge"]) && $permissions["Merge"]) {
                    $response = $executor->processAutoMerge($this->input, $display);
                } else {
                    $executor->displayPermissionDeniedPage("Merge Constellations", $display);
                }
                break;
            case "merge_cancel":
                if (isset($permissions["Merge"]) && $permissions["Merge"]) {
                    $response = $executor->cancelMerge($this->input, $display);
                } else {
                    $executor->displayPermissionDeniedPage("Merge Constellations", $display);
                }
                break;
            case "history":
                $executor->displayHistoryPage($this->input, $display);
                break;

            case "history_diff":
                $executor->displayHistoryComparePage($this->input, $display);
                break;

            case "preview":
                $executor->displayPreviewPage($this->input, $display);
                break;
            case "download":
                $this->response = $executor->handleDownload($this->input, $display, $this->responseHeaders);
                if ($display->hasTemplate()) {
                    break;
                } else {
                    return;
                }
            case "explore":
                $executor->displayGridPage($this->input, $display);
                break;

            // User and messaging commands
            case "dashboard":
                $executor->displayDashboardPage($display);
                break;
            case "profile":
                $executor->displayProfilePage($display);
                break;
            case "api_key":
                $executor->displayAPIInfoPage($display, $user);
                break;
            case "api_help":
                $executor->displayAPIHelpPage($display, $user);
                break;
            case "messages":
                $executor->displayMessageListPage($display);
                break;
            case "message_read":
                $response = $executor->readMessage($this->input);
                break;
            case "message_send":
                $response = $executor->sendMessage($this->input);
                break;
            case "message_delete":
                $response = $executor->deleteMessage($this->input);
                break;
            case "feedback":
                $response = $executor->sendFeedbackMessage($this->input);
                break;

            // visualization commands
			case "visualize":
                $response = $executor->handleVisualization($this->input, $display);
                break;

            // Administrator command (the sub method handles admin commands)
            case "administrator":
                $response = $executor->handleAdministrator($this->input, $display, $user);
                break;

            // Vocab administrator command (the sub method handles commands)
            case "vocab_administrator":
                $response = $executor->handleVocabAdministrator($this->input, $display, $user);
                break;

            // Modification commands that return JSON
            case "update_profile":
                $response = $executor->saveProfile($this->input, $user);
                break;

            case "new_reconcile":
                $response = $executor->reconcilePieces($this->input);
                break;

            case "save":
                $response = $executor->saveConstellation($this->input);
                break;

            case "save_unlock":
                $response = $executor->saveAndUnlockConstellation($this->input);
                break;

            case "unlock":
                $response = $executor->unlockConstellation($this->input);
                // if unlocked by constellationid parameter, then send them to the dashboard.
                if (!isset($response["error"]) && !isset($this->input["entityType"])) {
                    header("Location: " . \snac\Config::$WEBUI_URL ."/dashboard?message=Constellation successfully unlocked");
                    return;
                } else if (!isset($this->input["entityType"])) {
                    $executor->drawErrorPage($response, $display);
                }
                break;

            case "save_review":
                $response = $executor->saveAndSendForReviewConstellation($this->input);
                break;

            case "review":
                $response = $executor->sendForReviewConstellation($this->input);
                // if sent for review by constellationid parameter alone, then send them to the dashboard.
                if (!isset($response["error"]) && !isset($this->input["entityType"])) {
                    header("Location: " . \snac\Config::$WEBUI_URL ."/dashboard?message=Constellation successfully sent for review");
                    return;
                } else if (!isset($this->input["entityType"])) {
                    $executor->drawErrorPage($response, $display);
                }
                break;

            case "save_send":
                $response = $executor->saveAndSendConstellation($this->input);
                break;

            case "send":
                $response = $executor->sendConstellation($this->input);
                // if sent for review by constellationid parameter alone, then send them to the dashboard.
                if (!isset($response["error"]) && !isset($this->input["entityType"])) {
                    header("Location: " . \snac\Config::$WEBUI_URL ."/dashboard?message=Constellation successfully sent to editor");
                    return;
                } else if (!isset($this->input["entityType"])) {
                    $executor->drawErrorPage($response, $display);
                }
                break;

            case "save_publish":
                $response = $executor->saveAndPublishConstellation($this->input);
                break;

            case "publish":
                $response = $executor->publishConstellation($this->input);
                // if published by constellationid parameter, then send them to the dashboard.
                if (!isset($response["error"]) && !isset($this->input["entityType"])) {
                    header("Location: " . \snac\Config::$WEBUI_URL ."/dashboard?message=Constellation successfully published");
                    return;
                } else if (!isset($this->input["entityType"])) {
                    $executor->drawErrorPage($response, $display);
                }
                break;

            case "checkout":
                $response = $executor->checkoutConstellation($this->input);
                break;

            case "save_resource":
                $response = $executor->saveResource($this->input);
                break;

            case "delete":
                $response = $executor->deleteConstellation($this->input);
                // if deleted by constellationid parameter, then send them to the dashboard.
                if (!isset($response["error"]) && !isset($this->input["entityType"])) {
                    header("Location: " . \snac\Config::$WEBUI_URL ."/dashboard?message=Constellation successfully deleted");
                    return;
                } else if (!isset($this->input["entityType"])) {
                    $executor->drawErrorPage($response, $display);
                }
                break;

            // Search commands
            case "vocabulary":
                if (isset($this->input["subcommand"]) && $this->input["subcommand"] == "read")
                    $response = $executor->readVocabulary($this->input);
                else
                    $response = $executor->performVocabularySearch($this->input);
                break;

            case "quicksearch":
                $response = $executor->performNameSearch($this->input, true);
                break;

            case "search":
                if (isset($this->input["format"]) && $this->input["format"] == "json")
                    $response = $executor->searchConstellations($this->input);
                else
                    $executor->displaySearchPage($this->input, $display);
                break;

            case "resource_search":
                $response = $executor->performResourceSearch($this->input);
                break;

            case "browse":
                $executor->displayBrowsePage($display);
                break;
            case "browse_data":
                $response = $executor->performBrowseSearch($this->input);
                break;

            case "user_search":
                $response = $executor->performUserSearch($this->input);
                break;

            case "contact":
                $executor->displayContactPage($display);
                break;

            // Error command
            case "error":
                $error = array("error" => array(
                    "type" => "Not Found",
                    "message" => "The resource you were looking for does not exist."
                ));
                $response = $executor->drawErrorPage($error, $display);
                break;

            case "static":
                $found = $executor->displayStaticPage($this->input, $display);
                if (!$found)
                    array_push($this->responseHeaders, "HTTP/1.0 404 Not Found");
                break;
            case "stats":
                $response = $executor->displayStatsPage($this->input, $display);
                break;
            
            case "upload":
                $response = $executor->displayUploadPage($this->input, $display);
                break;

            // If dropping through, then show the landing page
            default:
                // The WebUI is displaying the landing page only
                // $executor->displayLandingPage($display);
                // The grid page is the "new" landing page
                $executor->displayGridPage($this->input, $display);
                break;
        }

        // If the display has been given a template, then use it.  Else, print out JSON.
        if ($display->hasTemplate()) {
            $this->logger->addDebug("Creating response page from template with data");
            array_push($this->responseHeaders, "Content-Type: text/html");
            $this->response = $display->getDisplay();
            $this->logger->addDebug("Response page created, sending back to user");
        } else {
            $this->response = json_encode($response, JSON_PRETTY_PRINT);
            array_push($this->responseHeaders, "Content-Type: application/json");
        }
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

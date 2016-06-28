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

use \snac\client\util\ServerConnect as ServerConnect;

/**
 * WebUIExecutor Class
 *
 * Contains functions that the WebUI's workflow engine needs to complete its work.
 *
 * @author Robbie Hott
 */
class WebUIExecutor {

    /**
     * @var \snac\client\util\ServerConnect $connect Connection to the server
     */
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



    /**
     * Display Edit Page
     *
     * Fills the display object with the edit page for a given user.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\client\webui\display\Display $display The display object for page creation
     * @param \snac\data\User $user The current user object
     */
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

    /**
     * Display New Simple Page
     *
     * Creates a blank "new constellation" simple edit page and loads it into the display.
     *
     * @param \snac\client\webui\display\Display $display The display object for page creation
     */
    public function displayNewPage(&$display) {
        $display->setTemplate("new_constellation_page");
        $constellation = new \snac\data\Constellation();
        $constellation->setOperation(\snac\data\Constellation::$OPERATION_INSERT);
        $constellation->addNameEntry(new \snac\data\NameEntry());
        if (\snac\Config::$DEBUG_MODE == true) {
            $display->addDebugData("constellationSource", json_encode($constellation, JSON_PRETTY_PRINT));
        }
        $this->logger->addDebug("Setting constellation data into the page template");
        $display->setData($constellation);
    }

    /**
     * Display New Edit Page
     *
     * Fills the display object with the edit page for a given user, using a constellation from the input
     * rather than from the database
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\client\webui\display\Display $display The display object for page creation
     * @param \snac\data\User $user The current user object
     */
     public function displayNewEditPage(&$input, &$display) {
         $mapper = new \snac\client\webui\util\ConstellationPostMapper();
         $mapper->allowTermLookup();

         // Get the constellation object
         $constellation = $mapper->serializeToConstellation($input);
         $this->logger->addDebug("Setting NEW constellation data", $constellation->toArray());

         $display->setTemplate("edit_page");
         if (\snac\Config::$DEBUG_MODE == true) {
             $display->addDebugData("constellationSource", json_encode($constellation, JSON_PRETTY_PRINT));
         }
         $this->logger->addDebug("Setting constellation data into the page template");
         $display->setData($constellation);
    }

    /**
     * Display View Page
     *
     * Loads the view page for a given constellation input into the display.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\client\webui\display\Display $display The display object for page creation
     * @param \snac\data\User $user The current user object
     */
    public function displayViewPage(&$input, &$display, &$user) {
        $query = array();
        $query["constellationid"] = $input["constellationid"];
        if (isset($input["version"]))
            $query["version"] = $input["version"];
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
                $display->setData(array_merge($constellation,
                    array("preview"=> (isset($input["preview"])) ? true : false)));
            } else {
                $this->logger->addDebug("Error page being drawn");
                $display->setTemplate("error_page");
                $this->logger->addDebug("Setting error data into the error page template");
                $display->setData($serverResponse["error"]);
            }
    }


    /**
     * Display Detailed View Page
     *
     * Loads the detailed view page for a given constellation input into the display.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\client\webui\display\Display $display The display object for page creation
     * @param \snac\data\User $user The current user object
     */
    public function displayDetailedViewPage(&$input, &$display, &$user) {
        $query = array();
        $query["constellationid"] = $input["constellationid"];
        if (isset($input["version"]))
            $query["version"] = $input["version"];
        $query["command"] = "read";
        if (isset($user) && $user != null)
            $query["user"] = $user->toArray();
            $this->logger->addDebug("Sending query to the server", $query);
            $serverResponse = $this->connect->query($query);
            $this->logger->addDebug("Received server response");
            if (isset($serverResponse["constellation"])) {
                $display->setTemplate("detailed_view_page");
                $constellation = $serverResponse["constellation"];
                if (\snac\Config::$DEBUG_MODE == true) {
                    $display->addDebugData("constellationSource", json_encode($serverResponse["constellation"], JSON_PRETTY_PRINT));
                    $display->addDebugData("serverResponse", json_encode($serverResponse, JSON_PRETTY_PRINT));
                }
                $this->logger->addDebug("Setting constellation data into the page template");
                $display->setData(array_merge($constellation,
                    array("preview"=> (isset($input["preview"])) ? true : false)));
            } else {
                $this->logger->addDebug("Error page being drawn");
                $display->setTemplate("error_page");
                $this->logger->addDebug("Setting error data into the error page template");
                $display->setData($serverResponse["error"]);
            }
    }

    /**
     * Start SNAC Session
     *
     * Calls to the server to start a new user's session
     *
     * @param \snac\data\User $user The current user object
     * @return boolean true on success, false otherwise
     */
    public function startSNACSession(&$user) {
        $query = array(
                "command" => "start_session",
                "user" => $user->toArray()
                );
        $serverResponse = $this->connect->query($query);
        $this->logger->addDebug("Server Responded to starting session", array($serverResponse));

        if (isset($serverResponse["result"]) && $serverResponse["result"] == "success")
            return new \snac\data\User($serverResponse["user"]);
        return false;
    }



    /**
     * End SNAC Session
     *
     * Ends the current user's session with the server by calling down with "end_session"
     *
     * @param \snac\data\User $user The current user object
     * @return boolean true on success, false otherwise
     */
    public function endSNACSession(&$user) {
        $query = array(
                "command" => "end_session",
                "user" => $user->toArray()
        );
        $serverResponse = $this->connect->query($query);

        if (isset($serverResponse["result"]) && $serverResponse["result"] == "success")
            return true;
        return false;
    }

    /**
     * Display Preview Page
     *
     * Fills the display for a view page for the constellation object passed as input.  This is useful for the
     * edit page to be able to draw a preview.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\client\webui\display\Display $display The display object for page creation
     */
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

    /**
     * Display Dashboard Page
     *
     * Fills the display object with the dashboard for the given user.
     *
     * @param \snac\client\webui\display\Display $display The display object for page creation
     * @param \snac\data\User $user The current user object
     */
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

    /**
     * Handle Administrative tasks
     *
     * Fills the display object with the requested admin page for the given user.
     *
     * @param \snac\client\webui\display\Display $display The display object for page creation
     * @param \snac\data\User $user The current user object
     */
    public function handleAdministrator(&$input, &$display, &$user) {

        if (!isset($input["subcommand"])) {
            $display->setTemplate("admin_dashboard");
            return;
        }

        switch ($input["subcommand"]) {
            case "add_user":
                $display->setData(array("title"=> "Add New User"));
                $display->setTemplate("admin_edit_user");
                break;
            case "edit_user":
                $response = array();
                if (isset($input["userid"])) {
                    $userEdit = new \snac\data\User();
                    $userEdit->setUserID($input["userid"]);
                    $ask = array("command"=>"edit_user",
                        "user" => $user->toArray(),
                        "user_edit" => $userEdit->toArray()
                    );
                    $serverResponse = $this->connect->query($ask);
                    if (!isset($serverResponse["result"]) || $serverResponse["result"] != 'success')
                        return $this->drawErrorPage($serverResponse, $display);

                    $response = array("user_edit" => $serverResponse["user"]);
                }
                $display->setData(array("title"=> "Edit User", "user"=>$response["user_edit"]));
                $display->setTemplate("admin_edit_user");
                break;
            case "edit_user_post":
                return $this->saveProfile($input, $user);
                break;
            case "users":
                $ask = array("command"=>"admin_users",
                    "user" => $user->toArray()
                );
                $serverResponse = $this->connect->query($ask);
                if (!isset($serverResponse["result"]) || $serverResponse["result"] != 'success')
                    return $this->drawErrorPage($serverResponse, $display);

                $display->setData(array("users" => $serverResponse["users"]));
                $display->setTemplate("admin_users");
                break;
            case "add_group":
                $display->setData(array("title"=> "Add New Group"));
                $display->setTemplate("admin_edit_group");
                break;
            case "edit_group":
                $response = array();
                if (isset($input["groupid"])) {
                    $userEdit = new \snac\data\Group();
                    $userEdit->setUserID($input["groupid"]);
                    $ask = array("command"=>"edit_group",
                        "user" => $user->toArray(),
                        "group_edit" => $userEdit->toArray()
                    );
                    $serverResponse = $this->connect->query($ask);
                    if (!isset($serverResponse["result"]) || $serverResponse["result"] != 'success')
                        return $this->drawErrorPage($serverResponse, $display);

                    $response = array("group_edit" => $serverResponse["group"]);
                }
                $display->setData(array(
                    "title"=> "Edit Group",
                    "group"=>$response["group_edit"],
                    "users"=>$response["users"]));
                $display->setTemplate("admin_edit_group");
                break;
            case "edit_group_post":
                $display->setTemplate("coming_soon");
                break;
            case "groups":
                $ask = array("command"=>"admin_groups",
                    "user" => $user->toArray()
                );
                $serverResponse = $this->connect->query($ask);
                if (!isset($serverResponse["result"]) || $serverResponse["result"] != 'success')
                    return $this->drawErrorPage($serverResponse, $display);

                $display->setData(array("groups" => $serverResponse["groups"]));
                $display->setTemplate("coming_soon");
                break;
            case "roles":
                $display->setTemplate("coming_soon");
                break;
            default:
                $display->setTemplate("admin_dashboard");
        }

        return false;
    }

    /**
     * Draw the Error Page
     *
     * Helper function to draw the error page when something goes wrong with the Server query.
     *
     * @param  string[] $serverResponse The response from the server
     * @param  \snac\client\webui\display\Display $display  The display object from the WebUI
     * @return boolean False, since an error occurred to get here
     */
    public function drawErrorPage(&$serverResponse, &$display) {
        $display->setTemplate("error_page");
        $display->setData(array("type" => "System Error", "message" => print_r($serverResponse, true)));
        return false;
    }

    /**
     * Display Profile Page
     *
     * Fills the display with the profile page for the given user.
     *
     * @param \snac\client\webui\display\Display $display The display object for page creation
     * @param \snac\data\User $user The current user object
     */
    public function displayProfilePage(&$display, &$user) {
        $display->setTemplate("profile_page");
        // Ask the server for a list of records to edit
        $ask = array("command"=>"user_information",
                "user" => $user->toArray()
        );
        $this->logger->addDebug("Sending query to the server", $ask);
        $serverResponse = $this->connect->query($ask);
        $this->logger->addDebug("Received server response", $serverResponse);
        $this->logger->addDebug("Setting dashboard data into the page template");
        $display->setData($serverResponse);
        $this->logger->addDebug("Finished setting dashboard data into the page template");
    }

    /**
     * Display Landing Page
     *
     * Fills the display with the default homepage for SNAC.
     *
     * @param \snac\client\webui\display\Display $display The display object for page creation
     */
    public function displayLandingPage(&$display) {

        // Get the list of recently published constellations

        $request = array();
        $request["command"] = "recently_published";
        $response = $this->connect->query($request);
        $this->logger->addDebug("Got the following response from the server for recently published", array($response));
        $recentConstellations = $response["constellation"];

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

    /**
     * Save User Profile
     *
     * Asks the server to update the profile of the user.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\data\User $user The current user object
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function saveProfile(&$input, &$user) {

        $tmpUser = new \snac\data\User();
        if (isset($input["userName"]) && $input["userName"] !== $user->getUserName()) {
            if (isset($input["userid"]) && $input["userid"] != "")
                $tmpUser->setUserID($input["userid"]);

            $tmpUser->setUserName($input["userName"]);
            $tmpUser->setEmail($input["userName"]);
            if (isset($input["affiliationid"]) && is_numeric($input["affiliationid"])) {
                $tmpAffil = new \snac\data\Constellation();
                $tmpAffil->setID($input["affiliationid"]);
                $tmpUser->setAffiliation($tmpAffil);
            }
            if (isset($input["active"]) && $input["active"] == "active")
                $tmpUser->setUserActive(true);
        } else {
            $tmpUser = new \snac\data\User($user->toArray());
        }

        $tmpUser->setFirstName($input["firstName"]);
        $tmpUser->setLastName($input["lastName"]);
        $tmpUser->setWorkPhone($input["workPhone"]);
        $tmpUser->setWorkEmail($input["workEmail"]);
        $tmpUser->setFullName($input["fullName"]);

        $this->logger->addDebug("Updated the User Object", $tmpUser->toArray());

        // Build a data structure to send to the server
        $request = array("command"=>"update_user");

        // Send the query to the server
        $request["user"] = $user->toArray();
        $request["user_update"] = $tmpUser->toArray();
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
            if (isset($serverResponse["user_update"])) {
                $response["user_update"] = $serverResponse["user_update"];
            }
        }

        // If success AND we were updating the current user, then update the session tokens
        if ($response["result"] == "success" && $tmpUser->getUserName() === $user->getUserName()) {
            $user = $tmpUser;
            $_SESSION["snac_user"] = serialize($user);
            $response["user"] = $serverResponse["user_update"];
        }

        return $response;
    }

    /**
    * Reconcile Pieces
    *
    * This method takes the constellation pieces from the input (similar to a "Save" in editing), builds
    * a Constellation out of those pieces and then asks the server to perform Identity Reconciliation
    * within SNAC on this constellation.  The results are returned to the client.
    *
    * @param string[] $input Post/Get inputs from the webui
    * @param \snac\data\User $user The current user object
    * @return string[] The web ui's response to the client (array ready for json_encode)
    */
    public function reconcilePieces(&$input, &$user) {
        $mapper = new \snac\client\webui\util\ConstellationPostMapper();

        // Get the constellation object
        $constellation = $mapper->serializeToConstellation($input);

        $this->logger->addDebug("reconciling constellation", $constellation->toArray());

        // Build a data structure to send to the server
        $request = array("command"=>"reconcile");

        // Send the query to the server
        $request["constellation"] = $constellation->toArray();
        $request["user"] = $user->toArray();
        $serverResponse = $this->connect->query($request);

        $response = array("results" => array());

        if (!is_array($serverResponse)) {
            $this->logger->addDebug("server's response: $serverResponse");
            return array($serverResponse);
        } else if (isset($serverResponse["reconciliation"])) {
            $response["result"] = $serverResponse["result"];
            foreach ($serverResponse["reconciliation"] as $k => $v) {
                if ($v["strength"] > 5.0) {
                    $response["results"][$k] = $v["identity"];
                }
            }
        }

        return $response;
    }

    /**
     * Save Constellation
     *
     * Maps the constellation given on input to a Constellation object, passes that to the server with an
     * update_constellation call.  If successful, it then maps any updates (new ids or version numbers) to the
     * Constellation object and web components from input, and returns the web ui's response (the list of
     * updates that must be made to the web ui GUI).
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\data\User $user The current user object
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
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
        if (isset($input['savemessage'])) {
            $request["message"] = $input["savemessage"];
        }
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

    /**
     * Save and Publish Constellation
     *
     * Maps the constellation given on input to a Constellation object, passes that to the server with an
     * update_constellation call.  If successful, it then maps any updates (new ids or version numbers) to the
     * Constellation object and web components from input, and returns the web ui's response (the list of
     * updates that must be made to the web ui GUI).
     *
     * After saving, it also calls to the server to have the constellation published, if the write was successful.
     *
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\data\User $user The current user object
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
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
        if (isset($input['savemessage'])) {
            $request["message"] = $input["savemessage"];
        }
        $serverResponse = $this->connect->query($request);

        $response = array ();
        $response["server_debug"] = array ();
        $response["server_debug"]["update"] = $serverResponse;
        if (isset($serverResponse["result"]))
            $response["result"] = $serverResponse["result"];
        if (isset($serverResponse["error"]))
            $response["error"] = $serverResponse["error"];

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


    /**
     * Save and Unlock Constellation
     *
     * Maps the constellation given on input to a Constellation object, passes that to the server with an
     * update_constellation call.  If successful, it then maps any updates (new ids or version numbers) to the
     * Constellation object and web components from input, and returns the web ui's response (the list of
     * updates that must be made to the web ui GUI).
     *
     * After saving, it also calls to the server to have the constellation's lock dropped from "currently editing"
     * to "locked editing," if the write was successful.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\data\User $user The current user object
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
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
        if (isset($input['savemessage'])) {
            $request["message"] = $input["savemessage"];
        }
        $serverResponse = $this->connect->query($request);

        $response = array ();
        $response["server_debug"] = array ();
        $response["server_debug"]["update"] = $serverResponse;
        if (isset($serverResponse["result"]))
            $response["result"] = $serverResponse["result"];
        if (isset($serverResponse["error"]))
            $response["error"] = $serverResponse["error"];

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


    /**
     * Unlock Constellation
     *
     * Asks the server to drop the input's constellation lock level from "currently editing" down to
     * "locked editing."
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\data\User $user The current user object
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function unlockConstellation(&$input, &$user) {

        $constellation = null;
        if (isset($input["constellationid"]) && isset($input["version"])) {
            $constellation = new \snac\data\Constellation();
            $constellation->setID($input["constellationid"]);
            $constellation->setVersion($input["version"]);
        } else if (isset($input["id"]) && isset($input["version"])) {
            $mapper = new \snac\client\webui\util\ConstellationPostMapper();

            // Get the constellation object
            $constellation = $mapper->serializeToConstellation($input);
        } else {
            return array( "result" => "failure", "error" => "No constellation or version number");
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

    /**
     * Publish Constellation
     *
     * Requests the server to publish the given constellation.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\data\User $user The current user object
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function publishConstellation(&$input, &$user) {
        $constellation = null;
        if (isset($input["constellationid"]) && isset($input["version"])) {
            $constellation = new \snac\data\Constellation();
            $constellation->setID($input["constellationid"]);
            $constellation->setVersion($input["version"]);
        } else if (isset($input["id"]) && isset($input["version"])) {
            $mapper = new \snac\client\webui\util\ConstellationPostMapper();

            // Get the constellation object
            $constellation = $mapper->serializeToConstellation($input);
        } else {
            return array( "result" => "failure", "error" => "No constellation or version number");
        }

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

    /**
     * Delete Constellation
     *
     * Requests the server to delete the given constellation.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\data\User $user The current user object
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function deleteConstellation(&$input, &$user) {
        $constellation = null;
        if (isset($input["constellationid"]) && isset($input["version"])) {
            $constellation = new \snac\data\Constellation();
            $constellation->setID($input["constellationid"]);
            $constellation->setVersion($input["version"]);
        } else if (isset($input["id"]) && isset($input["version"])) {
            $mapper = new \snac\client\webui\util\ConstellationPostMapper();

            // Get the constellation object
            $constellation = $mapper->serializeToConstellation($input);
        } else {
            return array( "result" => "failure", "error" => "No constellation or version number");
        }

        $this->logger->addDebug("deleting constellation", $constellation->toArray());

        // Build a data structure to send to the server
        $request = array (
                "command" => "delete_constellation"
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

    /**
     * Perform Name Search
     *
     * Connects to Elastic Search to perform a name search on the terms given on the input and
     * then returns the JSON-ready associative array of results.  Eventually, this will need to be handled
     * in the Server's code.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
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
                    'index' => \snac\Config::$ELASTIC_SEARCH_BASE_INDEX,
                    'type' => \snac\Config::$ELASTIC_SEARCH_BASE_TYPE,
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
                array_push($return, $val["_source"]);
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

    /**
     * Perform Vocabulary Search
     *
     * Asks the Server to search the controlled vocabulary for the given search terms.  Returns
     * the list of results as a JSON-ready web ui response.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function performVocabularySearch(&$input) {

        $this->logger->addDebug("Requesting Vocabulary");
        // Check what kind of vocabulary is wanted, and ask server for it
        $request = array ();
        $request["command"] = "vocabulary";
        $request["type"] = $input["type"];
        $request["entity_type"] = null;
        if (isset($input["entity_type"]))
            $request["entity_type"] = $input["entity_type"];
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
            } else if ($request["type"] == "affiliation") {
                // get the snac affiliations
                $serverResponse = $this->connect->query(
                    array(
                        "command" => "admin_institutions"
                    )
                );

                $response = array();
                $response["results"] = array();

                foreach ($serverResponse["constellation"] as $cData) {
                    $constellation = new \snac\data\Constellation($cData);
                    array_push($response["results"],
                        array (
                            "id" => $constellation->getID(),
                            "text" => $constellation->getPreferredNameEntry()->getOriginal()
                        )
                    );
                }

                // Give the editing list back in alphabetical order
                usort($response["results"],
                        function ($a, $b) {
                            return $a['text'] <=> $b['text'];
                        });

                return $response;
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

                $this->logger->addDebug("Sending response back to client", $serverResponse);
                    // Send the response back to the web client
                return $serverResponse;
            }
        }

        return array ();
    }

    /**
     * Create User
     *
     * Takes the Google OAuth2 user information and token and loads it into a SNAC User object.
     *
     * @param \League\OAuth2\Client\Provider\GoogleUser $googleUser User from Google OAuth Connection
     * @param \League\OAuth2\Client\Token\AccessToken $googleToken The access token
     * @return \snac\data\User SNAC User object
     */
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
        $user->setUserName($googleUser->getEmail());
        $user->setEmail($googleUser->getEmail());
        $user->setFirstName($googleUser->getFirstName());
        $user->setFullName($googleUser->getName());
        $user->setLastName($googleUser->getLastName());
        $token = array (
                "access_token" => $googleToken->getToken(),
                "expires" => $googleToken->getExpires()
        );
        $user->setToken($token);

        return $user;
    }


    /**
     * Simplify a Constellation
     *
     * Takes the given constellation and modifies it to make a simpler constellation to send the
     * templating engine.  This includes things like setting the preferred name, etc.
     *
     * @param \snac\data\Constellation $constellation The Constellation to modify
     * @return boolean True if anything was changed, false otherwise
     */
    protected function simplifyConstellation(&$constellation) {
        // Set the preferred name entry from the list (if applicable)
        $constellation->setPreferredNameEntry($constellation->getPreferredNameEntry());
        // Remove all name entries but the preferred one
        $constellation->setNameEntries(array($constellation->getPreferredNameEntry()));


        return true;
    }
}

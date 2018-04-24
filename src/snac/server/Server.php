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

        $this->logger->addDebug("Server starting to handle request", array("input" => $this->input));

        if ($this->input == null || empty($this->input)) {
            throw new \snac\exceptions\SNACInputException("No input given", 400);
        }

        $db = new \snac\server\database\DBUtil();

        // First, authenticate the user (every time to ensure they are still valid), if user information has been supplied
        $user = null;
        if (isset($this->input["user"])) {
            $user = $this->input["user"];
        }


        $executor = new \snac\server\ServerExecutor($user);


        $this->logger->addDebug("Switching on command");

        if (!isset($this->input["command"])) {
            throw new \snac\exceptions\SNACUnknownCommandException("No command given", 400);


        }

        // Decide what to do based on the command given to the server
        switch ($this->input["command"]) {

            // Vocabulary Searching
            case "vocabulary":
                $this->response = $executor->searchVocabulary($this->input);
                break;

            // Vocabulary Reading
            case "read_vocabulary":
                $this->response = $executor->readVocabulary($this->input);
                break;

            // Vocabulary Updating
            case "update_vocabulary":
                if (!$executor->hasPermission("View Admin Dashboard"))
                    throw new \snac\exceptions\SNACPermissionException("User not authorized to modify vocabulary.", 403);
                $this->response = $executor->updateVocabulary($this->input);
                break;

            // Reconciliation Engine tasks
            case "reconcile":
                $this->response = $executor->reconcileConstellation($this->input);
                break;

            // Session Management
            case "start_session":
                $this->response = $executor->startSession();
                break;

            case "end_session":
                $this->response = $executor->endSession();
                break;

            // User Management
            case "user_information":
                $this->response = $executor->userInformation();
                break;

            case "search_users":
                if (!$executor->hasPermission("Edit"))
                    throw new \snac\exceptions\SNACPermissionException("User not authorized to search users.", 403);
                $this->response = $executor->searchUsers($this->input);
                break;

            case "list_users":
                if (!$executor->hasPermission("Edit"))
                    throw new \snac\exceptions\SNACPermissionException("User not authorized to view users.", 403);
                $this->response = $executor->listUsers($this->input);
                break;

            case "user_messages":
                $this->response = $executor->userMessages();
                break;

            case "read_message":
                $this->response = $executor->readMessage($this->input);
                break;

            case "send_message":
                $this->response = $executor->sendMessage($this->input);
                break;

            case "archive_message":
                $this->response = $executor->archiveMessage($this->input);
                break;

            case "archived_messages":
                $this->response = $executor->listUserArchivedMessages();
                break;

            case "sent_messages":
                $this->response = $executor->listUserSentMessages();
                break;

            case "send_feedback":
                $this->response = $executor->sendFeedback($this->input);
                break;

            case "edit_user":
                $this->response = $executor->userInformation($this->input);
                break;

            case "update_user":
                $this->response = $executor->updateUserInformation($this->input);
                break;

            // Group Management
            case "group_information":
                $this->response = $executor->groupInformation($this->input);
                break;

            case "admin_groups":
                if (!$executor->hasPermission("Manage Groups"))
                    throw new \snac\exceptions\SNACPermissionException("User not authorized to manage groups.", 403);
                $this->response = $executor->listGroups($this->input);
                break;

            case "edit_group":
                if (!$executor->hasPermission("Manage Groups"))
                    throw new \snac\exceptions\SNACPermissionException("User not authorized to manage groups.", 403);
                $this->response = $executor->groupInformation($this->input);
                break;

            case "update_group":
                if (!$executor->hasPermission("Manage Groups"))
                    throw new \snac\exceptions\SNACPermissionException("User not authorized to manage groups.", 403);
                $this->response = $executor->updateGroupInformation($this->input);
                break;

            // institutions
            case "admin_institutions":
                $this->response = $executor->listInstitutions();
                break;

            // roles
            case "admin_roles":
                $this->response = $executor->listRoles();
                break;


            // Constellation Management
            case "insert_constellation":
                if (!$executor->hasPermission("Edit") || !$executor->hasPermission("Create"))
                    throw new \snac\exceptions\SNACPermissionException("User not authorized to insert constellations.", 403);
                $this->response = $executor->writeConstellation($this->input);
                break;
            case "update_constellation":
                if (!$executor->hasPermission("Edit"))
                    throw new \snac\exceptions\SNACPermissionException("User not authorized to update constellations.", 403);
                $this->response = $executor->writeConstellation($this->input);
                break;

            case "checkout_constellation":
                if (!$executor->hasPermission("Edit"))
                    throw new \snac\exceptions\SNACPermissionException("User not authorized to checkout constellations.", 403);
                $this->response = $executor->checkoutConstellation($this->input);
                break;

            case "unlock_constellation":
                if (!$executor->hasPermission("Edit"))
                    throw new \snac\exceptions\SNACPermissionException("User not authorized to unlock constellations.", 403);
                $this->response = $executor->unlockConstellation($this->input);
                break;

            case "publish_constellation":
                if (!$executor->hasPermission("Publish"))
                    throw new \snac\exceptions\SNACPermissionException("User not authorized to publish constellations.", 403);
                $this->response = $executor->publishConstellation($this->input);
                break;

            case "review_constellation":
                if (!$executor->hasPermission("Edit"))
                    throw new \snac\exceptions\SNACPermissionException("User not authorized to send constellation for review.", 403);
                $this->response = $executor->sendForReviewConstellation($this->input);
                break;

            case "delete_constellation":
                if (!$executor->hasPermission("Delete"))
                    throw new \snac\exceptions\SNACPermissionException("User not authorized to delete constellations.", 403);
                $this->response = $executor->deleteConstellation($this->input);
                break;

            case "reassign_constellation":
                if (!$executor->hasPermission("Change Locks"))
                    throw new \snac\exceptions\SNACPermissionException("User not authorized to reassign constellations.", 403);
                $this->response = $executor->reassignConstellation($this->input);
                break;


            case "recently_published":
                $this->response = $executor->getRecentlyPublished();
                break;
            case "random_constellations":
                $this->response = $executor->getRandomConstellations($this->input);
                break;
            case "list_constellations":
                $this->response = $executor->listConstellations($this->input);
                break;

            case "constellation_history":
                $this->response = $executor->getConstellationHistory($this->input);
                break;

            case "download_constellation":
                $this->response = $executor->downloadConstellation($this->input);
                break;

            case "constellation_read_relations":
                $this->response = $executor->readConstellationRelations($this->input);
                break;

            case "constellation_list_maybesame":
                $this->response = $executor->listMaybeSameConstellations($this->input);
                break;

            case "constellation_list_assertions":
                $this->response = $executor->listAssertions($this->input);
                break;

            case "constellation_diff":
                $this->response = $executor->diffConstellations($this->input);
                break;

            case "constellation_diff_merge":
                if (!$executor->hasPermission("Merge")) {
                    throw new \snac\exceptions\SNACPermissionException("User not authorized to merge constellations.", 403);
                }
                $this->response = $executor->diffConstellations($this->input, true);
                break;

            case "constellation_merge":
                if (!$executor->hasPermission("Merge")) {
                    throw new \snac\exceptions\SNACPermissionException("User not authorized to merge constellations.", 403);
                }
                $this->response = $executor->mergeConstellations($this->input);
                break;

            case "constellation_auto_merge":
                if (!$executor->hasPermission("Merge")) {
                    throw new \snac\exceptions\SNACPermissionException("User not authorized to merge constellations.", 403);
                }
                $this->response = $executor->autoMergeConstellations($this->input);
                break;

            case "constellation_assert":
                if (!($executor->hasPermission("Maybe Same Assertion") && $executor->hasPermission("Not Same Assertion"))) {
                    throw new \snac\exceptions\SNACPermissionException("User not authorized to make Constellation assertions.", 403);
                }
                $this->response = $executor->makeAssertion($this->input);
                break;

            case "constellation_add_maybesame":
                if (!$executor->hasPermission("Maybe Same Assertion")) {
                    throw new \snac\exceptions\SNACPermissionException("User not authorized to add maybe-same links.", 403);
                }
                $this->response = $executor->addMaybeSameConstellation($this->input);
                break;

            case "constellation_remove_maybesame":
                if (!$executor->hasPermission("Maybe Same Assertion")) {
                    throw new \snac\exceptions\SNACPermissionException("User not authorized to remove maybe-same links.", 403);
                }
                $this->response = $executor->removeMaybeSameConstellation($this->input);
                break;



            case "read":
                $this->response = $executor->readConstellation($this->input);
                break;

            case "edit":
                if (!$executor->hasPermission("Edit")) {
                    throw new \snac\exceptions\SNACPermissionException("User not authorized to edit constellations.", 403);
                }
                $this->response = $executor->editConstellation($this->input);
                break;

            case "edit_part":
                if (!$executor->hasPermission("Edit")) {
                    throw new \snac\exceptions\SNACPermissionException("User not authorized to edit constellations.", 403);
                }
                $this->response = $executor->subEditConstellation($this->input);
                break;

            case "search":
                $this->response = $executor->searchConstellations($this->input);
                break;

            case "browse":
                $this->response = $executor->browseConstellations($this->input);
                break;

            case "elastic":
                $this->response = $executor->elasticSearchQuery($this->input);
                break;

            // Resource Management
            case "insert_resource":
                if (!$executor->hasPermission("Edit") || !$executor->hasPermission("Create"))
                   throw new \snac\exceptions\SNACPermissionException("User not authorized to insert resources.");
                $this->response = $executor->writeResource($this->input);
                break;
            case "update_resource":
                if (!$executor->hasPermission("Edit") || !$executor->hasPermission("Create"))
                   throw new \snac\exceptions\SNACPermissionException("User not authorized to update resources.");
                $this->response = $executor->writeResource($this->input);
                break;
            case "read_resource":
                $this->response = $executor->readResource($this->input);
                break;
            case "resource_search":
                $this->response = $executor->searchResources($this->input);
                break;

            // Reporting
            case "stats":
                $tmp = ["type"=>"public"];
                $this->response = $executor->readReport($tmp);
                break;
            case "report":
                if (!$executor->hasPermission("View Reports"))
                    throw new \snac\exceptions\SNACPermissionException("User not authorized to view reports.", 403);
                $this->response = $executor->readReport($this->input);
                break;
            case "report_generate":
                if (!$executor->hasPermission("Generate Reports"))
                    throw new \snac\exceptions\SNACPermissionException("User not authorized to generate reports.", 403);
                $this->response = $executor->generateReport($this->input);
                break;

            // Ingest and Parsing tasks
            case "parse_eac":
                //if (!$executor->hasPermission("Create"))
                //   throw new \snac\exceptions\SNACPermissionException("User not authorized to parse Constellations.");
                $this->response = $executor->parseEACCPFToConstellation($this->input);
                break;

            default:
                throw new \snac\exceptions\SNACUnknownCommandException("Command: " . $this->input["command"], 400);

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
        $this->response["timing"] =round((microtime(true) - $this->timing) * 1000, 2);
        return json_encode($this->response, JSON_PRETTY_PRINT);
    }
}

<?php

/**
 * Server Executor Class File
 *
 * Contains the ServerExector class that performs all the tasks for the main Server
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2016 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server;

use League\OAuth2\Client\Token\AccessToken;
use snac\server\database\DBUtil;
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
     * @var \snac\server\elastic\ElasticSearchUtil Elastic Search Utility class
     */
    private $elasticSearch = null;

    /**
     * @var \snac\server\neo4j\Neo4JUtil Neo4J Utility class
     */
    private $neo4J = null;

    /**
     * @var \snac\server\mailer\Mailer Email Utility class
     */
    private $mailer = null;

    /**
     * @var \snac\data\User Current user object
     */
    private $user = null;

    /**
     * @var boolean[] List of permission for the current user, as associative array keys
     */
    private $permissions = null;


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

        // create a log channel
        $this->logger = new \Monolog\Logger('ServerExec');
        $this->logger->pushHandler($log);

        $this->cStore = new \snac\server\database\DBUtil();
        $this->uStore = new \snac\server\database\DBUser();
        $this->elasticSearch = new \snac\server\elastic\ElasticSearchUtil();
        $this->neo4J = new \snac\server\neo4j\Neo4JUtil();
        $this->mailer = new \snac\server\mailer\Mailer();
        $this->logger->addDebug("Starting ServerExecutor");

        $this->permissions = array();
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
            // authenticate user here!
            $this->logger->addDebug("Authenticating User");
            $userObj = new \snac\data\User($user);
            // authenticateUser sets $this->user
            if (!$this->authenticateUser($userObj)) {
                throw new \snac\exceptions\SNACUserException("User is not authorized");
            }
            $this->logger->addDebug("User authenticated successfully");

            $this->getUserPermissions();
        }

    }

    /**
     * Check OAuth Authentication
     *
     * Checks the User against OAuth and validates their token.
     *
     * @param \snac\data\User $user The user to validate
     * @return boolean true on success, false on failure
     */
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
     * Check if the current user has permission
     *
     * Checks whether the current user has the permission requested.  If the permissions have not been set up
     * in this server executor instance yet, it will automatically create it from the user object.
     *
     * @param  string  $permission The permission/privilege name
     * @return boolean             True if the user has that permission, false otherwise
     */
    function hasPermission($permission) {
        if ($this->permissions == null) {
            $this->getUserPermissions();
        }

        if (isset($this->permissions[$permission]) && $this->permissions[$permission] === true)
            return true;

        return false;
    }

    /**
     * Get User Permissions
     *
     * Gets the associative array of permissions for the current user. If the user is not set, then there are
     * available permissions and it will return an empty array.  The permission list is also set in the private
     * field of this class.
     *
     * @return boolean[] Associative array of permissions
     */
    function getUserPermissions() {
        $this->permissions = array();
        if ($this->user != null) {
            $user = $this->uStore->readUser($this->user);
            foreach ($user->getRoleList() as $role) {
                foreach ($role->getPrivilegeList() as $privilege) {
                    $this->permissions[$privilege->getLabel()] = true;
                }
            }
        }
        return $this->permissions;
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
        $this->logger->addDebug("Starting Authentication function");
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
                $this->logger->addDebug("Read user", array($this->user));

                if ($this->user === false) {
                    // The user wasn't found in the database
                    throw new \snac\exceptions\SNACUserException("Invalid OAuth user");
                }
            } else {
                throw new \snac\exceptions\SNACUserException("Username required for login");
            }

            $this->logger->addDebug("The user was found in the database", $this->user->toArray());
            $this->user->setToken($user->getToken());

            // Use the values passed in from the client to update the user object,
            // if applicable
            $this->user->setAvatar($user->getAvatar());
            $this->user->setAvatarSmall($user->getAvatarSmall());
            $this->user->setAvatarLarge($user->getAvatarLarge());
            if ($this->user->getFirstName() == null)
                $this->user->setFirstName($user->getFirstName());
            if ($this->user->getLastName() == null)
                $this->user->setLastName($user->getLastName());
            if ($this->user->getFullName() == null)
                $this->user->setFullName($user->getFullName());
            if ($this->user->getEmail() == null)
                $this->user->setEmail($user->getEmail());
            $this->uStore->saveUser($this->user);
            $this->logger->addDebug("Updated the user with their token", $this->user->toArray());

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

                if (isset($this->user->getToken()["authority"]) &&
                    $this->user->getToken()["authority"] == "snac") {
                    // This was a fake but legit token from SNAC
                    return true;
                } else if ($this->checkOAuth($this->user) &&
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
        $this->logger->addDebug("User object was null: no permissions but okay auth");
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
     * Update the User Information
     *
     * Calls saveUser to save the user information to the database.  Then returns the user object.
     *
     * @param string[]|null $input The input from the client
     * @return string[] The response to send to the client
     */
    public function updateUserInformation(&$input) {
        $response = array();

        $updated = new \snac\data\User($input["user_update"]);

        $success = false;
        if ($this->user->getUserName() == $updated->getUserName()) {
            $this->user->setFirstName($updated->getFirstName());
            $this->user->setLastName($updated->getLastName());
            $this->user->setFullName($updated->getFullName());
            $this->user->setEmail($updated->getEmail());
            $this->user->setWorkEmail($updated->getWorkEmail());
            $this->user->setWorkPhone($updated->getWorkPhone());
            $success = $this->uStore->saveUser($this->user);
            if ($success === true)
                $response["user_update"] = $this->user->toArray();
        } else {
            if ($this->uStore->readUser($updated) !== false) {
                $success = $this->uStore->saveUser($updated, true);
                if ($success === false) {
                    $response["error"] = "Could not save the user";
                }
            } else {
                $updated = $this->uStore->createUser($updated, true);
                if ($updated !== false)
                    $success = true;
                else
                    $response["error"] = "Could not create the user";
            }

            // If the user was successfully created or saved, then modify their groups and return the full user object
            if ($success === true) {
                $retUser = $this->uStore->readUser($updated);

                // If we have groups to modify, then update them appropriately.   To remove all groups, the parameter
                // must be sent as an empty array
                if (isset($input["groups_update"]) && is_array($input["groups_update"])) {
                    $currentGroups = $this->uStore->listGroupsForUser($retUser);

                    foreach ($currentGroups as $current) {
                        $this->uStore->removeUserFromGroup($retUser, $current);
                    }

                    foreach ($input["groups_update"] as $newGroup) {
                        $this->uStore->addUserToGroup($retUser, new \snac\data\Group($newGroup));
                    }
                }
                $response["user_update"] = $retUser->toArray();

            }
        }

        if ($success === true) {
            $response["result"] = "success";
        } else {
            $response["result"] = "failure";
        }

        return $response;
    }

    public function searchUsers(&$input) {
        if (!isset($input["query_string"]))
            throw new \snac\exceptions\SNACInputException("Query string required to search");

        $getAll = true;
        if (isset($input["filter"])) {
            if ($input["filter"] == "active")
                $getAll = false;
        }
        $roleFilter = null;
        if (isset($input["role"])) {
            $roleFilter = $input["role"];
        }

        $count = \snac\Config::$SQL_LIMIT;
        if (isset($input["count"]))
            $count = $input["count"];

        $results = $this->uStore->searchUsers(
                        $input["query_string"],
                        $count,
                        $roleFilter,
                        $getAll);


        $response = array();
        $response["results"] = array();
        foreach ($results as $result) {
            array_push($response["results"], $result->toArray(false));
        }

        return $response;
    }

    /**
     * List Users
     *
     * Calls through to DBUser to ask for the list of users
     *
     * @param  string[] $input Input array from the client
     * @return string[]        Response to send to the client including the list of Users
     */
    public function listUsers(&$input) {

        $getAll = true;
        if (isset($input["filter"])) {
            if ($input["filter"] == "active")
                $getAll = false;
        }

        $allUsers = $this->uStore->listUsers($getAll);

        $response = array();
        if (count($allUsers) > 0) {
            $response["users"] = array();
            foreach ($allUsers as $user) {
                array_push($response["users"], $user->toArray());
            }
            usort($response["users"], function($a, $b) {

                if (!isset($a["fullName"]) && isset($b["fullName"]))
                    return 1;
                else if (isset($a["fullName"]) && !isset($b["fullName"]))
                    return -1;
                else if (!isset($a["fullName"]) && !isset($b["fullName"]))
                    return 0;
                // default sort by name
                return $a["fullName"] <=> $b["fullName"];
            });
            $response["result"] = "success";
        } else {
            $response["result"] = "failure";
        }
        return $response;
    }

    /**
     * List Groups
     *
     * Calls through to DBUser to ask for the list of groups
     *
     * @param  string[] $input Input array from the client
     * @return string[]        Response to send to the client including the list of Users
     */
    public function listGroups(&$input) {

        $allGroups = $this->uStore->listGroups();
        $response = array();
        if ($allGroups !== false) {
            $response["groups"] = array();
            foreach ($allGroups as $group) {
                array_push($response["groups"], $group->toArray());
            }
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
            $this->logger->addDebug("Ending Session for user", $this->user->toArray());
            $this->uStore->removeSession($this->user);
            $response["user"] = $this->user->toArray();
            $response["result"] = "success";
        } else {
            $response["result"] = "failure";
        }
        $this->logger->addDebug("User Session ended");

        return $response;
    }

    /**
     * Read Vocabulary
     *
     * Reads the vocabulary from the database, based on the input given and returns
     * the result
     *
     * @param string[] $input Direct server input
     * @return string[] The response to send to the client
     */
    public function readVocabulary(&$input) {
        $response = array();
        if (isset($input["term_id"])) {
            $term = null;
            if (isset($input["type"]) && $input["type"] == "geoPlace") {
                $term = $this->cStore->buildGeoTerm($input["term_id"]);
            } else {
                $term = $this->cStore->populateTerm($input["term_id"]);
            }

            if ($term != null) {
                $response["term"] = $term->toArray();
                $response["result"] = "success";
            } else {
                $response["term"] = null;
                $response["result"] = "failure";
            }
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
        if (isset($input["term_id"])) {
            return $this->readVocabulary($input);
        } else {
            switch ($input["type"]) {
                default:
                    $response["results"] = array();
                    $count = 100;
                    if (isset($input["count"]))
                        $count = $input["count"];
                    $results = $this->cStore->searchVocabulary(
                        $input["type"],
                        $input["query_string"],
                        $input["entity_type"],
                        $count);
                    foreach ($results as $result)
                        array_push($response["results"], $result->toArray(false));
                    break;
            }
        }

        return $response;
    }


    /**
     * Update the Controlled Vocabulary
     *
     * Calls saveUser to save the user information to the database.  Then returns the user object.
     *
     * @param string[]|null $input The input from the client
     * @return string[] The response to send to the client
     */
    public function updateVocabulary(&$input) {
        $response = array();
        $success = false;
        $term = null;

        if (isset($input["type"]) && $input["type"] == "geo_term") {
            $term = new \snac\data\GeoTerm($input["term"]);
        } else {
            $term = new \snac\data\Term($input["term"]);
        }

        if ($term->getID() == null || $term->getID() == "") {
            // We are doing an insert
            $writtenTerm = null;
            if (isset($input["type"]) && $input["type"] == "geo_term") {
                $writtenTerm = $this->cStore->writeGeoTerm($term);
            } else {
                $writtenTerm = $this->cStore->writeVocabularyTerm($term);
            }

            if ($writtenTerm) {
                $success = true;
                $response["term"] = $writtenTerm->toArray();
            } else {
                $response["error"] = "Term could not be written";
            }
        } else {
            // Get the one out of the database
            $current = null;
            if (isset($input["type"]) && $input["type"] == "geo_term") {
                $current = $this->cStore->buildGeoTerm($term->getID());
            } else {
                $current = $this->cStore->populateTerm($term->getID());
            }

            if ($current->getType() == $term->getType() && $term->getTerm() != null && $term->getTerm() != "") {
                // The term didn't change type and does not have an empty term field, so update
                throw new \snac\exceptions\SNACPermissionException("Currently the system is not allowing updating terms.");
            }
            $response["error"] = "The term to update was not found in the database.";
        }

        if ($success === true) {
            $response["result"] = "success";
        } else {
            $response["result"] = "failure";
        }

        return $response;
    }

    /**
     * Search Resources
     *
     * Searches the resources from the database, based on the input given and returns
     * a list of results
     *
     * @param string[] $input Direct server input
     * @return string[] The response to send to the client
     */
    public function searchResources(&$input) {
        $response = array();
        if (isset($input["term"])) {
            $response = $this->elasticSearch->searchResourceIndex($input["term"]);
            // If there are results from the search, then replace them with full
            // resources from the database (rather than from ES results)
            $this->logger->addDebug("Got the following ES result", $response);
            if (isset($response["results"])) {
                $results = $response["results"];
                $response["results"] = array();
                foreach ($results as $result)
                    array_push(
                        $response["results"],
                        $this->cStore->readResource($result["id"])->toArray());
            }
        }

        $this->logger->addDebug("Returning the following resource search results", $response);
        return $response;
    }

    /**
     * Read Resource
     *
     * Given a resource id in the input, returns the resource object in the
     * response to the user.  If there is an error, it will add that.
     *
     * @param string[] $input Input array from the Server object
     * @throws \snac\exceptions\SNACInputException
     * @return string[] The response to send to the client
     */
    public function readResource(&$input) {
        $response = array();
        $resource = null;

        try {
            if (!isset($input["resourceid"])) {
                throw new \snac\exceptions\SNACInputException("No resource to read");
            }
            $id = $input["resourceid"];
            $version = null;
            if (isset($input["version"]))
                $version = $input["version"];
            $resource = $this->cStore->readResource($id, $version);

            $response["resource"] = $resource->toArray();
            $this->logger->addDebug("Serialized resource for output to client");
        } catch (Exception $e) {
            $response["error"] = $e;
        }
        return $response;
    }

    /**
     * List the SNAC roles
     *
     * List all the roles in SNAC.
     *
     * @return \snac\data\Role[] List of Roles
     */
    public function listRoles() {
        $roleList = array();
        foreach ($this->uStore->listRoles() as $role) {
            array_push($roleList, $role->toArray());
        }
        $response = array (
            "result" => "success",
            "roles" =>  $roleList
        );
        return $response;
    }

    /**
     * List the SNAC institutions
     *
     * Gets a list of constellations that are institutions in SNAC (those that individual users could be
     * affiliated with).
     *
     * @return \snac\data\Constellation[] List of Institutional Constellations
     */
    public function listInstitutions() {

        $constellationList = array();
        foreach ($this->uStore->listInstitutions() as $constellation) {
            array_push($constellationList, $constellation->toArray());
        }
        $response = array (
            "result" => "success",
            "constellation" =>  $constellationList
        );
        return $response;
    }

    /**
     * List User Messages
     *
     * Returns a list of messages sent to a given user.
     *
     * @param string[] $input Input array from the Server object
     * @return string[] The response to send to the client
     */
    public function userMessages($input = null) {
        /*
         * Get the list of Messages for the user
         */
        $response["messages"] = array();
        $messages = $this->uStore->listMessagesToUser($this->user, false);
        foreach ($messages as $message) {
            array_push($response["messages"], $message->toArray());
        }
        $response["result"] = "success";
        return $response;
    }

    /**
     * Delete Message
     *
     * Deletes the message with given message id if it exists and the user has
     * permission to delete this message (sender or recipient).  Note, this method
     * only sets the delete flag; a message is never actually deleted.
     *
     * @param string[] $input Input array from the Server object
     * @throws \snac\exceptions\SNACInputException
     * @throws \snac\exceptions\SNACPermissionException
     * @return string[] The response to send to the client
     */
    public function deleteMessage(&$input) {
        if (!isset($input["messageid"])) {
            throw new \snac\exceptions\SNACInputException("No message ID given to delete");
        }
        $response = array();

        $message = $this->uStore->getMessageByID($input["messageid"]);

        if ($message === false) {
            throw new \snac\exceptions\SNACInputException("Message does not exist");
        }
        $this->logger->addDebug("Deleting message", $message->toArray());

        if (($message->getToUser() !== null && $message->getToUser()->getUserID() === $this->user->getUserID()) ||
            ($message->getFromUser() !== null && $message->getFromUser()->getUserID() === $this->user->getUserID())) {
                $response["message"] = $message->toArray();
        } else {
            throw new \snac\exceptions\SNACPermissionException("User does not have permission to delete the message.");
        }

        $this->logger->addDebug("Starting to delete1");
        $success = $this->uStore->deleteMessage($message);
        $this->logger->addDebug("Done delete");
        if ($success)
            $response["result"] = "success";
        else
            $response["result"] = "failure";

        $this->logger->addDebug("Deleted", $response);
        return $response;

    }

    /**
     * Read Message
     *
     * Given a message id on input, read the message and return it to the client, if they
     * have permissions to read it.
     *
     * @param string[] $input Input array from the Server object
     * @throws \snac\exceptions\SNACInputException
     * @throws \snac\exceptions\SNACPermissionException
     * @return string[] The response to send to the client
     */
    public function readMessage(&$input) {
        if (!isset($input["messageid"])) {
            throw new \snac\exceptions\SNACInputException("No message ID given to read");
        }
        $response = array();

        $message = $this->uStore->getMessageByID($input["messageid"]);

        if ($message === false) {
            throw new \snac\exceptions\SNACInputException("Message does not exist");
        }

        if (($message->getToUser() !== null && $message->getToUser()->getUserID() === $this->user->getUserID()) ||
            ($message->getFromUser() !== null && $message->getFromUser()->getUserID() === $this->user->getUserID())) {
                $response["message"] = $message->toArray();
        } else {
            throw new \snac\exceptions\SNACPermissionException("User does not have permission to read the message.");
        }

        $response["result"] = "success";
        return $response;

    }

    /**
     * Send Message
     *
     * Sends a message given on input to a user, then emails them to notify them
     * of the message.
     *
     * @param string[] $input Input array from the Server object
     * @throws \snac\exceptions\SNACInputException
     * @return string[] The response to send to the client
     */
    public function sendMessage(&$input) {
        if (!isset($input["message"])) {
            throw new \snac\exceptions\SNACInputException("No message given to send");
        }
        $response = array();

        $message = new \snac\data\Message($input["message"]);

        if ($message->getFromUser() === null || $message->getFromUser()->getUserID() !== $this->user->getUserID()) {
            throw new \snac\exceptions\SNACPermissionException("User does not have permission to send messages as another user.");
        }
        $toUser = $this->uStore->readUser($message->getToUser());
        if ($toUser === false) {
            throw new \snac\exceptions\SNACUserException("Recipient User does not exist.");
        }

        $message->setToUser($toUser);

        // Send the message
        $this->uStore->writeMessage($message);

        // Email the message, if needed
        $this->mailer->sendUserMessage($message);

        $response["result"] = "success";
        return $response;
    }


    /**
     * Send Feedback
     *
     * Sends the given message as feedback to the correct user to handle feedback. It also
     * emails the user to notify them of the feedback.
     *
     * @param string[] $input Input array from the Server object
     * @throws \snac\exceptions\SNACInputException
     * @return string[] The response to send to the client
     */
    public function sendFeedback(&$input) {
        if (!isset($input["message"])) {
            throw new \snac\exceptions\SNACInputException("No feedback message given to send");
        }
        $response = array();

        $message = new \snac\data\Message($input["message"]);
        $this->logger->addDebug("Message received", $message->toArray());

        if ($message->getFromUser() === null && $message->getFromString() === null) {
            throw new \snac\exceptions\SNACPermissionException("Feedback can't be sent completely anonymously.");
        } else if ($message->getFromUser() !== null && $this->user !== null && $message->getFromUser()->getUserID() !== $this->user->getUserID()) {
            throw new \snac\exceptions\SNACPermissionException("User does not have permission to send feedback as another user.");
        } else if ($message->getFromUser() === null && $this->user !== null) {
            throw new \snac\exceptions\SNACPermissionException("Feedback can't be anonymous if the user is logged in.");
        } else if ($message->getFromUser() !== null && $this->user === null) {
            throw new \snac\exceptions\SNACPermissionException("Feedback can't be sent from a user if they are not logged in.");
        }
        $tmpUser = new \snac\data\User();
        $tmpUser->setUserName("jh2jf@virginia.edu");
        $toUser = $this->uStore->readUser($tmpUser);
        if ($toUser === false) {
            throw new \snac\exceptions\SNACUserException("Recipient User does not exist.");
        }
        $message->setToUser($toUser);

        // Send the message
        $this->uStore->writeMessage($message);
        $this->mailer->sendUserMessage($message);

        $response["result"] = "success";
        return $response;
    }

    /**
     * Get User Information
     *
     * Gets the user information, including their user information from the database as well
     * as the list of constellations they have in each stage of editing/review.  Creates and returns
     * an array of the user information to return to the client.
     *
     * @param string[]|null $input The input from the client
     * @return string[] The response to send to the client
     */
    public function userInformation($input = null) {
        $response = array();


        /*
         * Get the User object, if it exists
         */

        $user = null;
        if ($input == null) {
            $user = $this->user;
        } else {
            if (isset($input["user_edit"])) {
                $user = $this->uStore->readUser(new \snac\data\User($input["user_edit"]));
            }
        }

        if ($user == null) {
            $response["result"] = "failure";
            $response["error"] = "The user did not exist.";
            return $response;
        }
        $response["result"] = "success";

        $response["user"] = $user->toArray();

        /*
         * Get the list of Groups the User is a member of
         */
        $response["groups"] = array();
        $groups = $this->uStore->listGroupsForUser($user);
        foreach ($groups as $group) {
            array_push($response["groups"], $group->toArray());
        }

        /*
         * Get the list of Messages for the user
         */
        $response["messages"] = array();
        $messages = $this->uStore->listMessagesToUser($user, true, true);
        foreach ($messages as $message) {
            array_push($response["messages"], $message->toArray());
        }

        /*
         * Get the list of Constellations locked or checked out to the user
         *
         * "editing"      = checked out to the user for edit
         * "editing_lock" = currently locked from the user because they are editing
         */

        $this->logger->addDebug("Getting list of locked constellations to user");

        // First look for constellations editable
        $editList = $this->cStore->listConstellationsWithStatusForUser($user, "locked editing");

        $response["editing"] = array ();
        if ($editList !== false) {
            foreach ($editList as $constellation) {
                $item = array (
                        "id" => $constellation->getID(),
                        "version" => $constellation->getVersion(),
                        "nameEntry" => $constellation->getPreferredNameEntry()->getOriginal()
                );
                $this->logger->addDebug("User has checked out", $item);
                array_push($response["editing"], $item);
            }
        }

        // Give the editing list back in alphabetical order
        usort($response["editing"],
                function ($a, $b) {
                    return $a['nameEntry'] <=> $b['nameEntry'];
                });

        // Next look for currently editing constellations
        $editList = $this->cStore->listConstellationsWithStatusForUser($user, "currently editing");

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

        // Next look for sent for review constellations
        $editList = $this->cStore->listConstellationsWithStatusForUser($user, "needs review");

        $response["review_lock"] = array ();
        if ($editList !== false) {
            foreach ($editList as $constellation) {
                $item = array (
                        "id" => $constellation->getID(),
                        "version" => $constellation->getVersion(),
                        "nameEntry" => $constellation->getPreferredNameEntry()->getOriginal()
                );
                $this->logger->addDebug("User had for review", $item);
                array_push($response["review_lock"], $item);
            }
        }

        // Give the editing list back in alphabetical order
        usort($response["review_lock"],
            function ($a, $b) {
                return $a['nameEntry'] <=> $b['nameEntry'];
        });

        // Next look for needs review by this user constellations
        $editList = $this->cStore->listConstellationsWithStatusForUser($user, "needs review", null, null, true);

        $response["review"] = array ();
        if ($editList !== false) {
            foreach ($editList as $constellation) {
                $item = array (
                        "id" => $constellation->getID(),
                        "version" => $constellation->getVersion(),
                        "nameEntry" => $constellation->getPreferredNameEntry()->getOriginal()
                );
                $this->logger->addDebug("User needed to review", $item);
                array_push($response["review"], $item);
            }
        }

        // Give the editing list back in alphabetical order
        usort($response["review"],
            function ($a, $b) {
                return $a['nameEntry'] <=> $b['nameEntry'];
        });

        return $response;
    }

    /**
     * Get Group Information
     *
     * Gets the group information, including the group information from the database as well
     * as the list of users in this group
     *
     * @param string[]|null $input The input from the client
     * @return string[] The response to send to the client
     */
    public function groupInformation($input = null) {
        $response = array();

        $group = null;
        if (isset($input["group"])) {
            $group = new \snac\data\Group($input["group"]);
            $group = $this->uStore->readGroup($group);
        } else {
            return array (
                "result" => "failure"
            );
        }
        $response["group"] = $group->toArray();

        $users = $this->uStore->listUsersInGroup($group);
        $response["users"] = array();
        foreach ($users as $user) {
            array_push($response["users"], $user->toArray());
        }

        $response["result"] = "success";
        return $response;
    }

    /**
     * Update Group Information
     *
     * Updates the group information passed in to the server
     *
     * @param  string[] $input Input from the client
     * @return string[] Response to the client
     */
    public function updateGroupInformation($input = null) {
        $response = array();

        $updated = new \snac\data\Group($input["group_update"]);

        $updated = $this->uStore->writeGroup($updated);

        $currentUsers = $this->uStore->listUsersInGroup($updated);

        foreach ($currentUsers as $current) {
            $this->uStore->removeUserFromGroup($current, $updated);
        }

        foreach ($input["users_update"] as $newUser) {
            $this->uStore->addUserToGroup(new \snac\data\User($newUser), $updated);
        }

        if ($updated === false) {
            $response["result"] = "failure";
            $response["error"] = "Could not save the group";
        } else {
            $response["result"] = "success";
            $response["group_update"] = $updated->toArray();
        }
        return $response;

    }

    /**
     * Write Constellation
     *
     * Uses DBUtil to write a constellation (from the input) in the database.  If no operation is set on the
     * Constellation, it returns a success as if it wrote, but without modifying the database.
     *
     * @param string[] $input Input array from the Server object
     * @throws \snac\exceptions\SNACException
     * @return string[] The response to send to the client
     */
    public function writeConstellation(&$input) {
        $response = array();
        if (isset($input["constellation"])) {
            $constellation = new \snac\data\Constellation($input["constellation"]);
            $this->logger->addDebug("Writing Constellation Data", $input["constellation"]);
            $this->logger->addDebug("Writing Constellation toArray", $constellation->toArray());

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

                    $result = null;

                    // Get the message, if the client supplied one
                    $saveLog = null;
                    if (isset($input["message"]) && $input["message"] != null && $input["message"] != "")
                        $saveLog = $input["message"];

                    // If the constellation has an ID, then we should check that it's actually checked-out to the user
                    // and the user is currently editing it!
                    if ($constellation->getID() != null) {
                        // Read the constellation summary and make sure the last version matches the current version
                        // if they match, write, else send failure back with note about updating old version

                        $this->logger->addDebug("Constellation had an ID, so we're doing an update", array($constellation->getID()));
                        $inList = false;
                        $userList = $this->cStore->listConstellationsWithStatusForUser($this->user, "currently editing");
                        foreach ($userList as $item) {
                            if ($item->getID() == $constellation->getID() && $item->getVersion() == $constellation->getVersion()) {
                                $inList = true;
                                break;
                            }
                        }

                        if ($saveLog == null)
                            $saveLog = "Edits in Web UI";
                        if ($inList)
                            $result = $this->cStore->writeConstellation($this->user, $constellation, $saveLog);

                    // If the constellation does not currently have and ID, then we should write it and have it checked
                    // out to the user that wrote it.  Also, update the status to be currently editing
                    } else {
                        $this->logger->addDebug("Writing a new constellation");
                        if ($saveLog == null)
                            $saveLog = "New Constellation from Web UI";
                        $result = $this->cStore->writeConstellation($this->user, $constellation, $saveLog);
                        if ($result != null) {
                            $version = $this->cStore->writeConstellationStatus($this->user, $result->getID(),
                                    "currently editing", "New constellation is already in edit");
                            if ($version !== false)
                                $result->setVersion($version);
                        } else {
                            $this->logger->addDebug("Couldn't write the new constellation for some reason");
                            $response["result"] = "failure";
                            $response["error"] = "an unknown error occurred while trying to write";
                            return $response;
                        }
                    }

                    if (isset($result) && $result != null) {
                        $this->logger->addDebug("successfully wrote constellation");
                        $response["constellation"] = $result->toArray();
                        $response["result"] = "success";
                    } else {
                        $this->logger->addDebug("writeConstellation returned a null result or edits not allowed");
                        $response["result"] = "failure";
                        $response["error"] = "this version is not the current version, other edits have happened";
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
            $response["error"] = "no constellation to write";
        }
        return $response;
    }

    /**
     * Write Resource
     *
     * Writes the resource based on the input to the server.
     *
     * @param string[] $input Input array from the Server object
     * @throws \snac\exceptions\SNACException
     * @return string[] The response to send to the client
     */
    public function writeResource(&$input) {
        $response = array();
        if (isset($input["resource"])) {
            $resource = new \snac\data\Resource($input["resource"]);
            try {
                $result = $this->cStore->writeResource($resource);
                if (isset($result) && $result != false) {
                    $this->elasticSearch->writeToResourceIndices($resource);
                    $this->logger->addDebug("successfully wrote resource");
                    $response["resource"] = $result->toArray();
                    $response["result"] = "success";
                } else {
                    $this->logger->addDebug("writeResource returned a null result or edits not allowed");
                    $response["result"] = "failure";
                    $response["error"] = "could not write the resource";
                }
            } catch (\Exception $e) {
                $this->logger->addError("writeResource threw an exception");
                // Rethrow it, since we just wanted a log statement
                throw $e;
            }

        } else {
            $this->logger->addDebug("Resource input value wasn't set to write");
            $response["result"] = "failure";
            $response["error"] = "no resource to write";
        }
        return $response;
    }

    /**
     * Reassign Constellation
     *
     * Reassigns the given constellation to a different user, setting it to be "locked editing" to that user, assuming the
     * administrator user has "Change Locks" permissions.
     *
     * @param string[] $input Input array from the Server object
     * @throws \snac\exceptions\SNACException
     * @return string[] The response to send to the client
     */
    public function reassignConstellation(&$input) {
        $response = array();
        try {
            $this->logger->addDebug("Reassigning the constellation");
            if (isset($input["constellation"]) && isset($input["to_user"])) {
                $constellation = new \snac\data\Constellation($input["constellation"]);
                $toUser = new \snac\data\User($input["to_user"]);

                // Get the full User object
                $toUser = $this->uStore->readUser($toUser);
                if ($toUser === false) {
                    throw new \snac\exceptions\SNACInputException("Bad user information given.");
                }

                $currentStatus = $this->cStore->readConstellationStatus($constellation->getID());

                // Read the summary out of the database. if the version numbers match AND the constellation
                // is currently editing for the user, THEN unlock it.  Else, send back a note to the client with a failure

                // Read the current summary
                $current = $this->cStore->readConstellation($constellation->getID(), null, DBUtil::$READ_NRD);

                // If the admin user has the current version AND permission to change locks
                if ($current->getVersion() == $constellation->getVersion() && $this->hasPermission("Change Locks")) {
                    $result = $this->cStore->writeConstellationStatus($toUser, $constellation->getID(), "locked editing",
                            "Constellation reassigned by " . $this->user->getUserName());


                    if (isset($result) && $result !== false) {
                        $this->logger->addDebug("successfully reassigned constellation");
                        $constellation->setVersion($result);
                        $response["constellation"] = $constellation->toArray();
                        $response["result"] = "success";


                    } else {

                        $this->logger->addDebug("could not reassign the constellation");
                        $response["result"] = "failure";
                        $response["error"] = "writing status failed";
                    }
                } else {
                    $this->logger->addDebug("constellation versions didn't match or no permissions");
                    $response["result"] = "failure";
                    $response["error"] = "other changes have been made to this constellation";
                    $response["constellation"] = $constellation->toArray();
                }
            } else {
                $this->logger->addDebug("no constellation or user given");
                $response["result"] = "failure";
                $response["error"] = "no constellation or user given";
            }
        } catch (\Exception $e) {
            $this->logger->addError("unlocking constellation threw an exception");
            $response["result"] = "failure";
            throw $e;
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

                // Read the summary out of the database. if the version numbers match AND the constellation
                // is currently editing for the user, THEN unlock it.  Else, send back a note to the client with a failure

                // Read the current summary
                $current = $this->cStore->readConstellation($constellation->getID(), null, DBUtil::$READ_NRD);
                $info = $this->cStore->readConstellationUserStatus($constellation->getID());

                $inList = false;
                if ($info != null && $this->user->getUserID() == $info["userid"] &&
                        ($info["status"] == "currently editing" || $info["status"] == "needs review")) {
                    $inList = true;
                }

                // If this constellation is in the list of currently editing for the user OR the user has change locks permission, then unlock it
                if ($current->getVersion() == $constellation->getVersion() && ($inList || $this->hasPermission("Change Locks"))) {

                    $result = false;
                    if ($this->cStore->readConstellationStatus($constellation->getID(), -1) === "published") {
                        $this->logger->addDebug("re-publishing to unlock constellation");
                        $result = $this->cStore->writeConstellationStatus($this->user, $constellation->getID(),
                                        "published", "Republish: User canceled edit without making changes");
                        $this->updateIndexesAfterPublish($constellation->getID());
                    } else {
                        $result = $this->cStore->writeConstellationStatus($this->user, $constellation->getID(),
                                        "locked editing", "User finished editing constellation");
                    }

                    if (isset($result) && $result !== false) {
                        $this->logger->addDebug("successfully unlocked constellation");
                        $constellation->setVersion($result);
                        $response["constellation"] = $constellation->toArray();
                        $response["result"] = "success";


                    } else {

                        $this->logger->addDebug("could not unlock the constellation");
                        $response["result"] = "failure";
                        $response["error"] = "writing status failed";
                    }
                } else {
                    $this->logger->addDebug("constellation versions didn't match or was not in users currently editing list");
                    $response["result"] = "failure";
                    $response["error"] = "other changes have been made to this constellation";
                    $response["constellation"] = $constellation->toArray();
                }
            } else {
                $this->logger->addDebug("no constellation given to unlock");
                $response["result"] = "failure";
                $response["error"] = "no constellation given";
            }
        } catch (\Exception $e) {
            $this->logger->addError("unlocking constellation threw an exception");
            $response["result"] = "failure";
            throw $e;
        }
        return $response;
    }

    /**
     * Send Constellation For Review
     *
     * Lowers the lock on a constellation from editing to "needs review."  This essentially puts the
     * constellation into the list of constellations needing review.  The constellation must be given in the input.
     *
     * @param string[] $input Input array from the Server object
     * @throws \snac\exceptions\SNACException
     * @return string[] The response to send to the client
     */
    public function sendForReviewConstellation(&$input) {
        $response = array();
        try {
            $this->logger->addDebug("Marking the constellation as needs review");
            if (isset($input["constellation"])) {
                $constellation = new \snac\data\Constellation($input["constellation"]);

                $currentStatus = $this->cStore->readConstellationStatus($constellation->getID());

                // Read the summary out of the database. if the version numbers match AND the constellation
                // is currently editing for the user, THEN send it for review it.  Else, send back a note to the client with a failure

                // Read the current summary
                $current = $this->cStore->readConstellation($constellation->getID(), null, DBUtil::$READ_NRD);
                $info = $this->cStore->readConstellationUserStatus($constellation->getID());

                $inList = false;
                if ($info != null && $this->user->getUserID() == $info["userid"] &&
                        ($info["status"] == "currently editing" || $info["status"] == "locked editing")) {
                    $inList = true;
                }

                // If this constellation is in the list of currently editing for the user, then send it for review
                if ($current->getVersion() == $constellation->getVersion() && $inList) {

                    $toUser = null;
                    if (isset($input["reviewer"])) {
                        $tmpUser = new \snac\data\User($input["reviewer"]);
                        $toUser = $this->uStore->readUser($tmpUser);

                        // If the user doesn't exist, then don't allow the review to continue
                        if ($toUser === false) {
                            throw new \snac\exceptions\SNACInputException("Tried to send constellation for review to unknown user");
                        }
                        if (!$this->uStore->hasPrivilegeByLabel($toUser, "Change Locks")) {
                            throw new \snac\exceptions\SNACInputException("Tried to send constellation for review to non-reviewer");
                        }
                    }

                    $message = "User sending Constellation for review";
                    if (isset($input["message"]) && $input["message"] != null && $input["message"] != "") {
                        $message = $input["message"];
                    }
                    $result = $this->cStore->writeConstellationStatus($this->user, $constellation->getID(), "needs review",
                            $message, $toUser);


                    if (isset($result) && $result !== false) {
                        if ($toUser != null) {
                            // Send a message and email to the user asked to review:
                            $newest = $this->cStore->readConstellation($constellation->getID(), null, DBUtil::$READ_MICRO_SUMMARY);
                            $message = new \snac\data\Message();
                            $message->setToUser($toUser);
                            $message->setFromUser($this->user);
                            $message->setSubject("Constellation for review");
                            $msgBody = "<p>Please review my constellation.</p>";
                            $name = "Unknown";
                            $this->logger->addDebug("Sending message for reviewer", $newest->toArray());
                            $prefName = $newest->getPreferredNameEntry();
                            if ($prefName != null)
                                $name = $prefName->getOriginal();
                            $msgBody .= "<p style=\"text-align: center;\"><a href=\"".\snac\Config::$WEBUI_URL."?command=details&amp;constellationid=".
                                $newest->getID()."&amp;version=".$newest->getVersion()."&amp;preview=1\">$name</a></p>";
                            $message->setBody($msgBody);
                            
                            // Send the message
                            $this->uStore->writeMessage($message);

                            // Email the message, if needed
                            $this->mailer->sendUserMessage($message);
                        }
                        $this->logger->addDebug("successfully sent constellation for review");
                        $constellation->setVersion($result);
                        $response["constellation"] = $constellation->toArray();
                        $response["result"] = "success";
                    } else {
                        $this->logger->addDebug("could not send the constellation for review");
                        $response["result"] = "failure";
                        $response["error"] = "writing status failed";
                    }
                } else {
                    $this->logger->addDebug("constellation versions didn't match or was not in users currently editing list");
                    $response["result"] = "failure";
                    $response["error"] = "other changes have been made to this constellation";
                    $response["constellation"] = $constellation->toArray();
                }
            } else {
                $this->logger->addDebug("no constellation given to send for review");
                $response["result"] = "failure";
                $response["error"] = "no constellation given";
            }
        } catch (\Exception $e) {
            $this->logger->addError("sending constellation for review threw an exception");
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

                // Check the status of the constellation.  Make sure the input has the old version number (read the summary)
                // and then only publish if the user has permission (it was locked to them, etc).

                $current = $this->cStore->readConstellation($constellation->getID(), null, DBUtil::$READ_NRD);

                $info = $this->cStore->readConstellationUserStatus($constellation->getID());
                if ($info === null) {
                    throw new \snac\exceptions\SNACDatabaseException("The current constellation did not have a valid status");
                }
                $currentStatus = $info["status"];
                $currentUserID = $info["userid"];
                $currentNote = $info["note"];

                $inList = false;
                if ($currentUserID == $this->user->getUserID() &&
                    ($currentStatus == 'currently editing' || $currentStatus == 'locked editing')) {
                        $inList = true;
                    }

                $result = false;

                // If this constellation is the correct version, and the user was editing it, then publish it
                if ($current->getVersion() == $constellation->getVersion() && $inList) {
                    $result = $this->corePublish($current);
                }

                if (isset($result) && $result !== false) {
                    $this->logger->addDebug("successfully published constellation");
                    // Return the passed-in constellation from the user, with the new version number
                    $constellation->setVersion($result);
                    $constellation->setArkID($current->getArk()); // corePublish updates current's ARK
                    $response["constellation"] = $constellation->toArray();
                    $response["result"] = "success";

                    $this->updateIndexesAfterPublish($constellation->getID());
                } else {
                    $this->logger->addDebug("could not publish the constellation");
                    $response["result"] = "failure";
                    $response["error"] = "cannot publish an out-of-date copy of the constellation";
                }
            } else {
                $this->logger->addDebug("no constellation given to publish");
                $response["result"] = "failure";
                $response["error"] = "missing constellation information";
            }
        } catch (\Exception $e) {
            $this->logger->addError("publishing constellation threw an exception");
            $response["result"] = "failure";
            throw $e;
        }
        return $response;

    }

    /**
     * Publish Functionality
     *
     * This method actually does the publishing of a constellation.  If the constellation does not
     * have an ARK, it is assigned one (temporary or permanent depending on the SANDBOX_MODE config
     * variable.  It then updates the status to published.
     *
     * @param \snac\data\Constellation $constellation The constellation to publish
     * @return integer|boolean Returns the new version number on success or false on failure.
     */
    protected function corePublish(&$constellation) {
        if ($constellation->getArk() === null) {
            // We must mint an ark
            $arkManager = new \ark\ArkManager();

            $newArk = null;
            // Mint a temporary ark if we are in testing mode, else mint real ark
            if (\snac\Config::$SANDBOX_MODE) {
                $newArk = $arkManager->mintTemporaryArk();
            } else {
                $newArk = $arkManager->mintArk();
            }
            $microConstellation = new \snac\data\Constellation();
            $microConstellation->setID($constellation->getID());
            $microConstellation->setVersion($constellation->getVersion());
            $microConstellation->setArkID($newArk);
            $microConstellation->setEntityType($constellation->getEntityType());
            $microConstellation->setOperation(\snac\data\Constellation::$OPERATION_UPDATE);

            $written = $this->cStore->writeConstellation($this->user, $microConstellation,
            "System assigning new ARK to constellation", "locked editing");
            if ($written !== false && $written != null) {
                $result = $written->getVersion();
                unset($written);
            }
            $constellation->setArkID($newArk);
        }

        // If this is published, then it should point to itself in the lookup table.
        $selfDirect = array($constellation);
        $this->cStore->updateConstellationLookup($constellation, $selfDirect);

        return $this->cStore->writeConstellationStatus($this->user, $constellation->getID(),
                                                        "published", "User published constellation");
    }

    /**
     * Update Indexes after a publish
     *
     * Updates the extra indexes.  After a constellation has been published, this should be called
     * to update the status of the constellation in the various indices (Elastic Search, Neo4J).
     *
     * @param  integer $icid Identity Constellation id to index
     */
    protected function updateIndexesAfterPublish($icid) {
        $this->logger->addDebug("Updating indexes after publish");
        // Read in the constellation from the database to update elastic search
        //      currently, we need NRD, names, relations and resource relations (for counts)
        $published = $this->cStore->readPublishedConstellationByID($icid,
            DBUtil::$READ_NRD |
            DBUtil::$READ_ALL_NAMES |
            DBUtil::$READ_RELATIONS |
            DBUtil::$READ_RESOURCE_RELATIONS);

        // Update the Elastic Search Indices
        $this->elasticSearch->writeToNameIndices($published);

        // Update the Postgres Indices
        $this->cStore->updateNameIndex($published);

        // Update the Neo4J Indices
        // TODO
    }

    /**
     * Delete Constellation
     *
     * Updates the status of the given input's constellation to "deleted."  On successful delete, this method
     * also updates the Elastic Search indices to remove this Constellation, if ES is being used
     * in this install.
     *
     * @param string[] $input Input array from the Server object
     * @throws \Exception
     * @return string[] The response to send to the client
     */
    public function deleteConstellation(&$input) {

        $response = array();
        try {
            $this->logger->addDebug("Deleting constellation");
            if (isset($input["constellation"])) {
                $constellation = new \snac\data\Constellation($input["constellation"]);

                // Check the status of the constellation.  Make sure the input has the old version number (read the summary)
                // and then only publish if the user has permission (it was locked to them, etc).

                $current = $this->cStore->readConstellation($constellation->getID(), null, DBUtil::$READ_NRD);

                $inList = false;
                $userList = array_merge(
                    $this->cStore->listConstellationsWithStatusForUser($this->user, "currently editing"),
                    $this->cStore->listConstellationsWithStatusForUser($this->user, "locked editing")
                );
                foreach ($userList as $item) {
                    if ($item->getID() == $constellation->getID()) {
                        $inList = true;
                        break;
                    }
                }

                $result = false;

                // If this constellation is the correct version, and the user was editing it, then delete it
                if ($current->getVersion() == $constellation->getVersion() && $inList) {

                    // TODO: Replace this with the correct method to delete
                    $result = $constellation->getVersion();
                    //$result = $this->cStore->writeConstellationStatus($this->user, $constellation->getID(),
                      //                                                  "published", "User published constellation");
                }

                if (isset($result) && $result !== false) {
                    $this->logger->addDebug("successfully published constellation");
                    // Return the passed-in constellation from the user, with the new version number
                    $constellation->setVersion($result);
                    $response["constellation"] = $constellation->toArray();
                    $response["result"] = "success";

                    // Delete from Elastic Search Indices
                    $this->elasticSearch->deleteFromNameIndices($constellation);

                    // Delete from Postgres Indices
                    $this->cStore->deleteFromNameIndex($constellation);

                    // Delete from Neo4J Indices
                    // TODO

                } else {
                    $this->logger->addDebug("could not delete the constellation");
                    $response["result"] = "failure";
                    $response["error"] = "cannot delete an out-of-date copy of the constellation";
                }
            } else {
                $this->logger->addDebug("no constellation given to delete");
                $response["result"] = "failure";
                $response["error"] = "missing constellation information";
            }
        } catch (\Exception $e) {
            $this->logger->addError("deleting constellation threw an exception");
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
        $constellation = null;

        try {
            $constellations = $this->readConstellationFromDatabase($input);
            if ($constellations === null) {
                throw new \snac\exceptions\SNACInputException("Constellation does not exist");
            } else if (count($constellations) > 1) {
                // Send back multiple constellations
                $response["constellation"] = array();
                foreach ($constellations as $constellation) {
                    array_push($response["constellation"], $constellation->toArray());
                }
                $response["result"] = "success-notice";
                $response["message"] = [
                    "text" => "Please update your cache, the Constellation you requested has been split.",
                    "info" => [
                        "type" => "split"
                    ]
                ];
            } else {
                // Normal condition (one constellation)

                $constellation = $constellations[0];

                // Get the list of constellations locked editing for this user
                $inList = false;
                $userStatus = $this->cStore->readConstellationUserStatus($constellation->getID());
                if ($userStatus["userid"] == $this->user->getUserID() && $userStatus["status"] == 'locked editing')
                    $inList = true;

                if ($this->cStore->readConstellationStatus($constellation->getID()) == "published" || $inList) {
                    $constellation->setStatus("editable");
                } else if ($this->hasPermission("Change Locks")) {
                    $editingUser = new \snac\data\User();
                    $editingUser->setUserID($userStatus["userid"]);
                    $editingUser = $this->uStore->readUser($editingUser);
                    if ($editingUser)
                        $response["editing_user"] = $editingUser->toArray();
                }

                $response["result"] = "success";
                if ((isset($input["arkid"]) && $input["arkid"] != $constellation->getArk()) ||
                    (isset($input["constellationid"]) && $input["constellationid"] != $constellation->getID())) {

                    $response["result"] = "success-notice";
                    $response["message"] = [
                        "text" => "Please update your cache, the Constellation you requested has been merged into "
                        . $constellation->getArk() . ".",
                        "info" => [
                            "type" => "merged",
                            "redirect" => $constellation->getArk()
                        ]
                    ];
                }
                $this->logger->addDebug("Finished checking constellation status against the user");
                $response["constellation"] = $constellation->toArray();
            }
            $this->logger->addDebug("Serialized constellation for output to client");
        } catch (Exception $e) {
            $response["error"] = $e;
            $response["result"] = "failure";
        }
        return $response;

    }

    /**
     * Get Constellation History
     *
     * Gets the version history information for the constellation defined on input, up to
     * the given version (or public version).  Only returns the publicly-available versions
     * of the constellation, such as published, tombstoned, deleted, or ingest cpf.
     *
     * @param string[] $input Input array from the Server object
     * @return string[] The response to send to the client
     */
    public function getConstellationHistory(&$input) {
        $this->logger->addDebug("Reading constellation history");
        $reponse = array();
        $constellation = null;

        try {
            // Read the constellation itself
            $constellations = $this->readConstellationFromDatabase($input);
            if ($constellations == null || count($constellations) > 1) {
                throw new \snac\exceptions\SNACInputException("Constellation does not exist");
            }
            $constellation = $constellations[0];
            $response["constellation"] = $constellation->toArray();

            // TODO: This should also change to going through objects and calling toArray()
            $history = $this->cStore->listVersionHistory($constellation->getID(), $constellation->getVersion(), true);
            $response["history"] = $history;

            $this->logger->addDebug("Serialized constellation for output to client");
        } catch (Exception $e) {
            $response["error"] = $e;
        }
        return $response;

    }

    /**
     * Read Constellation From Database
     *
     * Asks the Constellation Store (DBUtil) for the constellation requested, and returns it if it exists.
     *
     * If given a constellationid, it reads the constellation from the database.  If trying to read a constellation
     * without a published version, an exception is thrown.
     *
     * @param string[] $input Input array from the Server object
     * @param boolean $includeMaintenanceHistory optional True will include maintenance history, false (default) will not
     * @param boolean $flags optional Flags to set for the read.  If left at 0, the full constellation will be read.
     * @throws \snac\exceptions\SNACInputException
     * @return null|\snac\data\Constellation[] A list of constellation objects (or null)
     */
    public function readConstellationFromDatabase(&$input,  $includeMaintenanceHistory=false, $flags = 0) {
        $constellation = null;
        $readFlags = \snac\server\database\DBUtil::$FULL_CONSTELLATION;

        if ($flags !== 0)
            $readFlags = $flags;

        if ($includeMaintenanceHistory) {
            $readFlags = $readFlags | \snac\server\database\DBUtil::$READ_MAINTENANCE_INFORMATION;
        }

        $this->logger->addDebug("Getting the current ICIDs for the requested constellation");

        $constellations = array();
        $icids = array();

        if (isset($input["arkid"])) {
            // get icids for the given ark id
            $icids = $this->cStore->getCurrentIDsForARK($input["arkid"]);
            if (empty($icids)) {
                // This means that the Constellation doesn't have a published version!
                throw new \snac\exceptions\SNACInputException("Constellation with ark " .
                        $input["arkid"] . " does not have a published version.");
            }

        } else if (isset($input["constellationid"])) {
            if (isset($input["version"])) {
                // if asking for a specific version, then just try to read this
                // id and version number.

                $this->logger->addDebug("Reading specific constellation from the database, flags=$readFlags");
                $constellation = $this->cStore->readConstellation(
                        $input["constellationid"],
                        $input["version"],
                        $readFlags);
                if ($constellation === false) {
                    throw new \snac\exceptions\SNACInputException("Constellation with id " .
                            $input["constellationid"] . " does not have version" .
                            $input["version"] . ".");
                }
                $this->logger->addDebug("Finished reading constellation from the database");
                return array($constellation);

            }

            $icids = $this->cStore->getCurrentIDsForID($input["constellationid"]);
            if (empty($icids)) {
                // This means that the Constellation doesn't have a published version!
                throw new \snac\exceptions\SNACInputException("Constellation with id " .
                        $input["constellationid"] . " does not have a published version.");
            }

        }


        $this->logger->addDebug("Reading constellation(s) from the database, flags=$readFlags");

        // If we have gotten here, we have a list of icids to read.  It is probably just one,
        // but may be multiple.
        foreach ($icids as $icid) {
            $constellation = $this->cStore->readPublishedConstellationByID($icid, $readFlags);
            if ($constellation !== false) {
                array_push($constellations, $constellation);
            }
        }

        if (count($constellations) == 0) {
            throw new \snac\exceptions\SNACInputException("Constellation does not exist.");
        }

        $this->logger->addDebug("Finished reading constellation from the database");

        return $constellations;

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
            throw new \snac\exceptions\SNACInputException("Please provide a Constellation ID for editing.  Arks are not supported.");

        } else if (isset($input["constellationid"])) {
            // Editing the given constellation id by reading the database

            try {
                // Read the constellation
                $this->logger->addDebug("Reading constellation from the database");

                $cId = $input["constellationid"];
                $info = $this->cStore->readConstellationUserStatus($cId);
                if (!is_array($info)) {
                    throw new \snac\exceptions\SNACInputException("Constellation does not have a current version");
                }

                $status = $info["status"];

                if ($info["userid"] === $this->user->getUserID() && $status === "currently editing") {
                    throw new \snac\exceptions\SNACConcurrentEditException("Constellation currently opened in another window");
                }

                // Should check the list of constellations for the user and only allow editing a "locked editing" constellation
                // if that constellation is attached to that user.  So, need to loop through the constellations for that user

                // Read the current summary
                $current = $this->cStore->readConstellation($cId, null, DBUtil::$READ_NRD);

                $inList = false;
                $userList = $this->cStore->listConstellationsWithStatusForUser($this->user, "locked editing");
                foreach ($userList as $item) {
                    if ($item->getID() == $current->getID()) {
                        $inList = true;
                        break;
                    }
                }

                // If the current status is published OR the user has that constellation locked editing (checked out to them),
                // OR the constellation needs review and the user has permission to review (TODO)
                // then the user is allowed to edit.
                if ( $status == "published" || $inList || ($status == "needs review" && $this->hasPermission("Change Locks"))) {
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
                    throw new \snac\exceptions\SNACPermissionException("Constellation is currently locked to another user.");
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
     * Sub-Edit Constellation
     *
     * Similar to readConstellation, this method returns a Constellation on the response.  If the client provided
     * an ark id, this constellation is generated by using the EAC-CPF parser.  If the client provided a
     * constellation id, it upgrades the status to "currently editing" and then returns the constellation in the response.
     *
     * @param string[] $input Input array from the Server object
     * @throws \snac\exceptions\SNACPermissionException
     * @return string[] The response to send to the client
     */
    public function subEditConstellation(&$input) {
        $this->logger->addDebug("Editing Constellation (Sub)");
        $response = array();

        if (isset($input["constellationid"])) {
            // Editing the given constellation id by reading the database

            try {
                // Read the constellation
                $this->logger->addDebug("Reading constellation from the database");

                $cId = $input["constellationid"];
                $status = $this->cStore->readConstellationStatus($cId);

                // Should check the list of constellations for the user and only allow editing a "locked editing" constellation
                // if that constellation is attached to that user.  So, need to loop through the constellations for that user

                // Read the current summary
                $current = $this->cStore->readConstellation($cId, null, DBUtil::$READ_NRD);

                $inList = false;
                $userList = $this->cStore->listConstellationsWithStatusForUser($this->user, "currently editing");
                foreach ($userList as $item) {
                    if ($item->getID() == $current->getID()) {
                        $inList = true;
                        break;
                    }
                }

                if ($inList) {
                    // Can continue editing this!

                    // read the constellation into response
                    $constellation = $this->cStore->readConstellation($cId);

                    $this->logger->addDebug("Finished reading constellation from the database");
                    $response["constellation"] = $constellation->toArray();
                    $response["result"] = "success";
                    $this->logger->addDebug("Serialized constellation for output to client");
                } else {
                    throw new \snac\exceptions\SNACPermissionException("Constellation is not currently being edited, so can not sub-edit.  Must be opened in edit first.");
                }


            } catch (\Exception $e) {
                // Leaving a catch block for logging purposes
                throw $e;
            }
        } else {
            $response["result"] = "failure";
        }
        return $response;
    }

    /**
     * Compute Constellation Diff
     *
     * Given two Constellation IDs in the input, this method reads the current version of both constellations
     * out of the database.  If they are both published, a diff of the Constellations are computed.  All data
     * components unique to Constellation 1 are returned in the "this" object; all data components unique to
     * Constellation 2 are returned in the "other" object; and all data components shared by both Constellations
     * are returned in the "intersection" object.
     *
     * If the optional parameter `$startMerge` is set to true (default is false), then this method will try to
     * check out both constellations to the current user.  User permissions to actually be able to check out the
     * Constellations is checked in the Server main code.
     *
     * @param string[] $input Input array from the Server object
     * @param boolean $startMerge optional If true, will try to check out the constellations to the user to start a merge.
     * @throws \snac\exceptions\SNACInputException
     * @throws \snac\exceptions\SNACDatabaseException
     * @return string[] The response to send to the client
     */
    public function diffConstellations(&$input, $startMerge=false) {
        $response = array();
        $this->logger->addDebug("Diffing constellations");

        if (isset($input["constellationid1"]) && isset($input["constellationid1"])) {
            // If two constellations were given
            try {
                // Read the constellation statuses
                $this->logger->addDebug("Reading constellation statuses from the database");

                $cId1 = $input["constellationid1"];
                $status1 = $this->cStore->readConstellationStatus($cId1);

                $cId2 = $input["constellationid2"];
                $status2 = $this->cStore->readConstellationStatus($cId2);

                // Right now, only published constellations can be merged, so that we can keep a "clean" history
                if ($status1 == "published" && $status2 == "published") {
                    $response["mergeable"] = true;

                    // If they asked to start the merge, then check these constellations out to that user as
                    // CURRENTLY EDITING.  This is the same level of modification as doing an edit, so we don't
                    // want the user to be able to open them for editing unless they unlock them.
                    if ($startMerge === true) {
                        $this->logger->addDebug("User is requesting to diff the constellations for a merge");

                        // lock the constellation to the user as currently editing
                        $success1 = $this->cStore->writeConstellationStatus($this->user, $cId1, "currently editing");
                        if ($success1 === false) {
                            $this->logger->addError("Writing Constellation Status failed", array("user"=>$this->user, "id"=>$cId1));
                            throw new \snac\exceptions\SNACDatabaseException("Could not open the Constellation $cId1 for Editing");
                        }
                        $success2 = $this->cStore->writeConstellationStatus($this->user, $cId2, "currently editing");
                        if ($success2 === false) {
                            $this->logger->addError("Writing Constellation Status failed", array("user"=>$this->user, "id"=>$cId2));

                            // Must unlock the first constellation if the second one failed
                            if ($success1 === true) {
                                $this->logger->addDebug("re-publishing to unlock constellation");
                                $result = $this->cStore->writeConstellationStatus($this->user, $cId1,
                                                "published", "Republish: An error occurred when trying to merge");
                                $this->updateIndexesAfterPublish($cId1);
                            }

                            throw new \snac\exceptions\SNACDatabaseException("Could not open the Constellation $cId2 for Editing");
                        }
                    }
                } else {
                    $response["mergeable"] = false;
                }
                $this->logger->addDebug("Reading Constellations from the database");
                $constellation1 = $this->cStore->readConstellation($cId1, null, \snac\server\database\DBUtil::$FULL_CONSTELLATION);
                $constellation2 = $this->cStore->readConstellation($cId2, null, \snac\server\database\DBUtil::$FULL_CONSTELLATION);

                $this->logger->addDebug("Starting Diff");
                $diffParts = $constellation1->diff($constellation2);

                $this->logger->addDebug("Finished Diff");

                if ($diffParts["this"] !== null)
                    $response["constellation1"] = $diffParts["this"]->toArray();
                else
                    $response["constellation1"] = null;

                if ($diffParts["other"] !== null)
                    $response["constellation2"] = $diffParts["other"]->toArray();
                else
                    $response["constellation2"] = null;

                if ($diffParts["intersection"] !== null)
                    $response["intersection"] = $diffParts["intersection"]->toArray();
                else {
                    $c = new \snac\data\Constellation();
                    $response["intersection"] = $c->toArray();
                }

            } catch (\Exception $e) {
                // Leaving a catch block for logging purposes
                throw $e;
            }

        } else {
            throw new \snac\exceptions\SNACInputException("Diff requires two constellation IDs");
        }

        return $response;
    }

    /**
     * Merge Constellations
     *
     * Given a constellation object and two constellation ids, this method will create a new Constellation
     * object and store it in the database (as the merged version of the two ids).  Then, it will tombstone
     * the Constellations at each of those IDs and point them to the merged version.
     *
     * This is a very complex method that handles all parts of the merge.  It follows a strict order of
     * operations to ensure that Source objects are copied to the new Constellation and that all SCM
     * objects are updated with correct Source citation ids before writing the merged Constellation.  It then
     * handles the writing/publishing and tombstoning so that the edit trail is appropriately stored.
     *
     * @param string[] $input Input array from the Server object
     * @throws \snac\exceptions\SNACPermissionException
     * @throws \snac\exceptions\SNACInputException
     * @throws \snac\exceptions\SNACDatabaseException
     * @return string[] The response to send to the client
     */
    function mergeConstellations(&$input) {
        $response = array();
        $this->logger->addDebug("Merging constellations");

        if (isset($input["constellationids"]) && is_array($input["constellationids"]) && isset($input["constellation"])) {

            if (count($input["constellationids"]) < 2) {
                throw new \snac\exceptions\SNACInputException("Must merge at least 2 Constellations");
            }

            $constellation = new \snac\data\Constellation($input["constellation"]);
            if ($constellation->isEmpty()) {
                throw new \snac\exceptions\SNACInputException("Merged constellation is empty");
            }

            $info = array();
            foreach ($input["constellationids"] as $cId) {
                if (!is_numeric($cId)) {
                    throw new \snac\exceptions\SNACInputException("Constellation ID $cId is not correctly formatted");
                }

                $info[$cId] = $this->cStore->readConstellationUserStatus($cId);

                if (!is_array($info[$cId])) {
                    throw new \snac\exceptions\SNACInputException("Constellation $cId does not have a current version");
                }

                if ($this->user->getUserID() !== $info[$cId]["userid"]) {
                    // This constellation is NOT checked out to this user, and so we cannot allow the merge
                    // to continue
                    throw new \snac\exceptions\SNACPermissionException("User trying to merge constellations not checked out to them");
                }
            }

            // We have multiple constellation ids that should be merged and the "merged" copy of the constellation
            // ACTUALLY DOING THE MERGING STEPS IN THE SYSTEM

            // Read parts of the to-merge constellations (need NRD and Sources and NameEntries)
            $originals = array();
            foreach ($input["constellationids"] as $cId) {
                $originals[$cId] = $this->cStore->readConstellation($cId, null,
                        \snac\server\database\DBUtil::$READ_NRD | \snac\server\database\DBUtil::$READ_OTHER_EXCEPT_RELATIONS
                        | \snac\server\database\DBUtil::$READ_ALL_NAMES);
            }


            // Create a version of the constellation with only Sources (for initialize step)
            $sourceConstellation = new \snac\data\Constellation();
            $sourceConstellation->setAllSources($constellation->getSources());
            $sourceConstellation->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);

            // We need an entity type, so if one is not set, we'll use the first one for now
            if ($sourceConstellation->getEntityType() == null) {
                foreach ($originals as $c) {
                    if ($c->getEntityType() != null) {
                        $sourceConstellation->setEntityType($c->getEntityType());
                        break;
                    }
                }
            }


            $this->logger->addDebug("Writing initial sources-level constellation", $sourceConstellation->toArray());

            // Write the copy of the constellation with only Source objects
            $sourceConstellation = $this->cStore->writeConstellation($this->user, $sourceConstellation, "Loading Source objects", 'initialize');
            if ($sourceConstellation === false) {
                throw new \snac\exceptions\SNACDatabaseException("Could not write the merged constellation");
            }

            // Update the constellation with the new sources, keeping a mapping to the old sources
            $constellation->setOperation(\snac\data\AbstractData::$OPERATION_UPDATE);
            $constellation->setID($sourceConstellation->getID());
            $constellation->setVersion($sourceConstellation->getVersion());
            $constellation->setAllSources(array()); // empty out the source list
            
            if ($constellation->getEntityType() == null)
                $constellation->setEntityType($sourceConstellation->getEntityType());
            
            $originalSources = array();
            foreach ($originals as $c) {
                $originalSources = array_merge($originalSources, $c->getSources());
            }
            $sourceMap = array();

            foreach ($sourceConstellation->getSources() as $source) {
                $source->setOperation(null); // remove operations, just in case
                $constellation->addSource($source);

                // put it in the mapping by original ID (we should find it!)
                foreach ($originalSources as $original) {
                    if ($source->equals($original, false)) { // don't check id, version, or operation
                        $sourceMap[$original->getID()] = $source;
                        break;
                    }
                }
            }

            // Merge the biogHists down into one
            $combinedBiogHist = new \snac\data\BiogHist();
            $combinedBiogHist->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
            foreach ($constellation->getBiogHistList() as $biogHist) {
                $combinedBiogHist->append($biogHist);
            }
            $constellation->removeAllBiogHists();
            $constellation->addBiogHist($combinedBiogHist);

            // Update all the SCMs across the Constellation
            // Note: If the Source in the citation didn't make it into the new merged Constellation,
            //       the citation link will be dropped
            foreach ($originalSources as $original) {
                $newSource = null;
                if (isset($sourceMap[$original->getID()]))
                    $newSource = $sourceMap[$original->getID()];
                $constellation->updateAllSCMCitations($original, $newSource);
            }

            // Write the new constellation in full
            $mergedICIDs = array();
            $mergedArks = array();
            foreach ($originals as $c) {
                array_push($mergedICIDs, $c->getID());
                array_push($mergedArks, $c->getArk());
            }
            $mergeNoteArray = [
                "action" => "merge",
                "icids" => $mergedICIDs,
                "arks" => $mergedArks
            ];
            $mergeNote = json_encode($mergeNoteArray, JSON_PRETTY_PRINT);
            $written = $this->cStore->writeConstellation($this->user, $constellation, $mergeNote, 'merge split');
            $this->logger->addDebug("Wrote the merged constellation", $constellation->toArray());
            if ($written === false) {
                throw new \snac\exceptions\SNACDatabaseException("Could not write the merged constellation in full");
            }

            // Publish the merged constellation and update the indexes
            $result = $this->corePublish($written);
            if (!isset($result) || $result === false) {
                $this->logger->addDebug("could not publish the constellation");
                throw new \snac\exceptions\SNACDatabaseException("Could not publish the merged constellation");
            }
            $this->logger->addDebug("successfully published constellation");
            $this->updateIndexesAfterPublish($written->getID());

            // Tombstone the other constellations and remove them from the indexes
            $tombstoneNoteArray = [
                "action" => "merge",
                "icids" => [
                    $written->getID()
                ],
                "arks" => [
                    $written->getArk()
                ]
            ];
            $tombstoneNote = json_encode($tombstoneNoteArray, JSON_PRETTY_PRINT);

            foreach ($originals as $c) {
                $success = $this->cStore->writeConstellationStatus($this->user, $c->getID(),
                                                                    "tombstone", $tombstoneNote);
                if ($success === false) {
                    $this->logger->addError("Writing Constellation Status failed",
                        array("user"=>$this->user, "id"=>$c->getID()));
                    throw new \snac\exceptions\SNACDatabaseException("Could not tombstone Constellation " .
                        $c->getID());
                }
                $this->elasticSearch->deleteFromNameIndices($c);
                $this->cStore->deleteFromNameIndex($c);
            }

            // Remove maybe-same links between the originals, if they exist
            // This touches both directions and c->c, i.e. it is inefficient, but it should still work
            foreach ($originals as $c) {
                foreach ($originals as $d) {
                    $this->cStore->removeMaybeSameLink($c, $d);
                    $this->cStore->removeMaybeSameLink($d, $c);
                }
            }

            // Update any maybe-same links that point to an original to point to the written version
            foreach ($originals as $c) {
                $this->cStore->updateMaybeSameLinks($c, $written);
            }

            // Update the constellation lookup table
            // Note: corePublish() will update the lookup for written->written
            $redirectWritten = array($written);
            foreach ($originals as $c) {
                $this->cStore->updateConstellationLookup($c, $redirectWritten);
            }

            // Return the fully-completed constellation from the database
            $fullWritten = $this->cStore->readConstellation($written->getID());
            
            // Merge completed successfully!
            if ($fullWritten != null) {
                $response["result"] = "success";
                $response["constellation"] = $fullWritten->toArray();
            } else {
                $response["result"] = "failure";
            }

        } else {
            throw new \snac\exceptions\SNACInputException("Merge requires two constellation IDs and a merged constellation");
        }

        return $response;
    }

    /**
     * List MaybeSames for Constellation
     *
     * This method looks up the constellation and any maybeSame relations (possible merges) for the constellation.
     *
     * @param string[] $input Input array from the Server object
     * @throws \snac\exceptions\SNACPermissionException
     * @return string[] The response to send to the client
     */
    public function listMaybeSameConstellations(&$input) {
        $this->logger->addDebug("Listing MaybeSame For Constellation");
        $response = array();

        if (isset($input["constellationid"])) {
            // Editing the given constellation id by reading the database

            try {
                // Read the constellation
                $this->logger->addDebug("Reading constellation from the database");

                $cId = $input["constellationid"];
                $status = $this->cStore->readConstellationStatus($cId);

                // Right now, only published constellations can be merged, so that we can keep a "clean" history
                if ( $status == "published" ) {
                    $response["mergeable"] = true;
                } else {
                    $response["mergeable"] = false;
                }

                // read the constellation into response
                $constellation = $this->cStore->readConstellation($cId, null, \snac\server\database\DBUtil::$READ_SHORT_SUMMARY);
                $this->logger->addDebug("Finished reading constellation from the database");
                $response["constellation"] = $constellation->toArray();

                $maybeSame = $this->cStore->listMaybeSameConstellations($cId,\snac\server\database\DBUtil::$READ_SHORT_SUMMARY);

                $response["maybe_same"] = array();
                foreach ($maybeSame as $key => $ms) {
                    $response["maybe_same"][$key] = array(
                        "constellation" => $ms->toArray(),
                        "mergeable" => ($this->cStore->readConstellationStatus($ms->getID()) == "published")
                    );
                }

            } catch (\Exception $e) {
                // Leaving a catch block for logging purposes
                throw $e;
            }
        }
        return $response;
    }

    /**
     * Download/Serialize a Constellation
     *
     * This method handles the downloading of content in any type. Download tasks include serializing a
     * constellation as EAC-CPF XML, and downloading the XML (a string) as a file.
     *
     * @param string[] $input Input array from the Server object
     * @throws \snac\exceptions\SNACInputException
     * @return string[] The response to send to the client
     */
    public function downloadConstellation($input) {
        if (!isset($input["type"])) {
            throw new \snac\exceptions\SNACInputException("No download type specified");
        }


        $constellations = $this->readConstellationFromDatabase($input, true);
        if ($constellations == null || count($constellations) > 1) {
            throw new \snac\exceptions\SNACInputException("Constellation does not exist");
        }
        $constellation = $constellations[0];

        // The downloaded version should include maybesame relations

        // Get the mayBeSameAs term from the database so we can populate the maybe same links
        $tmp = $this->cStore->searchVocabulary("relation_type", "mayBeSameAs");
        $maybeSameType = new \snac\data\Term();
        if (isset($tmp[0])) {
            $maybeSameType = $tmp[0];
        } else {
            $maybeSameType->setType("relation_type");
            $maybeSameType->setTerm("mayBeSameAs");
        }

        // Add the maybe same constellations into the downloaded version as Constellation Relations
        $maybesames = $this->cStore->listMaybeSameConstellations($constellation->getID(),\snac\server\database\DBUtil::$READ_MICRO_SUMMARY);
        foreach ($maybesames as $maybesame) {
            $relation = new \snac\data\ConstellationRelation();
            $relation->setSourceConstellation($constellation->getID());
            $relation->setSourceArkID($constellation->getArk());
            $relation->setTargetConstellation($maybesame->getID());
            $relation->setTargetArkID($maybesame->getArk());
            $relation->setTargetEntityType($maybesame->getEntityType());
            $relation->setContent($maybesame->getPreferredNameEntry()->getOriginal());
            $relation->setType($maybeSameType);
            $constellation->addRelation($relation);
        }

        $response = null;
        switch($input["type"]) {
            case "constellation_json":
                $response["file"] = array();
                $response["file"]["mime-type"] = "application/json";
                $response["file"]["filename"] = $this->arkToFilename($constellation->getArkID()).".json";
                $response["file"]["content"] = base64_encode(json_encode($constellation->toArray(), JSON_PRETTY_PRINT));
                break;
            case "eac-cpf":
                $response["file"] = array();
                $response["file"]["mime-type"] = "text/xml";
                $response["file"]["filename"] = $this->arkToFilename($constellation->getArkID()).".xml";

                $serializer = new \snac\util\EACCPFSerializer();
                $response["file"]["content"] = base64_encode($serializer->serialize($constellation));
                break;
            default:
                throw new \snac\exceptions\SNACInputException("Unknown download file type: " . $input["type"]);
        }

        return $response;
    }


    /**
    * Convert an ARK to Filename
    *
    * This method converts an ark with "ark:/" to a filename by stripping out everything up to and
    * including "ark:/", then replacing any slashes in the remainder with a hyphens.  If the string does
    * not include "ark:/", this method will just return the filename "constellation."
    *
    * This does not include the extension on the filename.
    *
    * @param string $ark The ark to convert
    * @return string The filename based on the ark (without an extension)
    */
    public function arkToFilename($ark) {
        $filename = "constellation";
        if (!stristr($ark, 'ark:/'))
            return $filename;

        $pieces = explode("ark:/", $ark);
        if (isset($pieces[1])) {
            $filename = str_replace('/', "-", $pieces[1]);
        }
        return $filename;
    }

    /**
     * List Constellations
     *
     * Lists the Constellations with a given status for any user.  If the requesting user needs permission
     * to actually get the list of constellations, this method also checks permissions.
     *
     * @param string[] $input Input array from the Server object
     * @throws \snac\exceptions\SNACPermissionException
     * @return string[] The response to send to the client
     */
    public function listConstellations(&$input) {

        $status = "published";
        if (isset($input["status"]))
            $status = $input["status"];

        $hasPermission = false;

        switch ($status) {

            case "needs review":
                if ($this->hasPermission("Change Locks"))
                    $hasPermission = true;
                break;

            case "published":
                $hasPermission = true;
                break;

            //default is no permission
        }

        if (!$hasPermission) {
            //throw permission denied
            throw new \snac\exceptions\SNACPermissionException("User does not have permission to list constellations with this status");
        }

        $list = $this->cStore->listConstellationsWithStatusForAny($status);

        //TODO: may want to rewrite this as a list of Constellation objects
        $response["results"] = array ();
        if ($list !== false) {
            foreach ($list as $constellation) {
                $item = array (
                    "id" => $constellation->getID(),
                    "version" => $constellation->getVersion(),
                    "nameEntry" => $constellation->getPreferredNameEntry()->getOriginal()
                );
                $this->logger->addDebug("Needs Review", $item);
                array_push($response["results"], $item);
            }
        }
        // Give the needs review back in alphabetical order
        usort($response["results"],
            function ($a, $b) {
                return $a['nameEntry'] <=> $b['nameEntry'];
            });

        return $response;

    }

    /**
     * Read Relations for Constellation
     *
     * Reads the relations for the constellation (both in-edges and out-edges).  Returns the two
     * lists in the response.
     *
     * @param string[] $input Input array from the Server object
     * @return string[] The response to send to the client
     * @throws \snac\exceptions\SNACInputException
     */
    public function readConstellationRelations(&$input) {
        $response = array();

        $constellations = $this->readConstellationFromDatabase($input, false, \snac\server\database\DBUtil::$READ_MICRO_SUMMARY);

        if ($constellations === null || count($constellations) > 1) {
            throw new \snac\exceptions\SNACInputException("Constellation not found");
        }

        // Use the first entry in the list
        $constellation = $constellations[0];

        if (\snac\Config::$USE_NEO4J) {
            // If using Neo4J, then ask Neo4J.  It will be a faster response time.
            $return = array("in" => array(), "out" => array());

            $results = $this->neo4J->listConstellationInEdges($constellation);
            foreach ($results as $i => $val) {
                // optionally, we could call readConstellation for a fuller constellation
                $related = new \snac\data\Constellation();
                //$related->setID($val["_source"]["id"]);
                //$related->setArkID($val["_source"]["arkID"]);
                $relatedName = new \snac\data\NameEntry();
                //$relatedName->setOriginal($val["_source"]["nameEntry"]);
                $related->addNameEntry($relatedName);
                // TODO: put in the relationship pointing back to the queried constellation for context
                array_push($return["in"], $related->toArray());
            }

            // This makes less sense since they are available within the original constellation data.
            // Leaving here for now
            $results = $this->neo4J->listConstellationOutEdges($constellation);
            foreach ($results as $i => $val) {
                // optionally, we could call readConstellation for a fuller constellation
                $related = new \snac\data\Constellation();
                //$related->setID($val["_source"]["id"]);
                //$related->setArkID($val["_source"]["arkID"]);
                $relatedName = new \snac\data\NameEntry();
                //$relatedName->setOriginal($val["_source"]["nameEntry"]);
                $related->addNameEntry($relatedName);
                // TODO: put in the relationships pointing from the queried constellation for context
                array_push($return["out"], $related->toArray());
            }

            $this->logger->addDebug("Created neo4J constellation relations response to the user", $return);

            // Send the response back to the web client
            $response = $return;
            $response["result"] = "success";
        } else {
            // If not using Neo4J, then we must ask DBUtil to get the information from Postgres.
            $return = array("in" => array(), "out" => array());
            $this->logger->addDebug("Getting In Edges from Postgres");
            $results = $this->cStore->listConstellationInEdges($constellation);
            foreach ($results as $result) {
                array_push($return["in"], array("constellation" => $result["constellation"]->toArray(),
                                                "relation" => $result["relation"]->toArray()));
            }

            $this->logger->addDebug("Reading full constellation for out edges");
            $fullConstellation = $this->cStore->readPublishedConstellationByID($constellation->getID(),
                                        \snac\server\database\DBUtil::$READ_MICRO_SUMMARY
                                        | \snac\server\database\DBUtil::$READ_RELATIONS);

            $this->logger->addDebug("Parsing out edges and grabbing micro summaries");
            foreach ($fullConstellation->getRelations() as $rel) {
                $target = $this->cStore->readPublishedConstellationByID($rel->getTargetConstellation(),
                                                \snac\server\database\DBUtil::$READ_MICRO_SUMMARY);
                if ($target) {
                    array_push($return["out"], array(
                        "constellation" => $target->toArray(),
                        "relation" => $rel->toArray()
                    ));
                }
            }

            $this->logger->addDebug("Created postgres constellation relations response to the user");

            $response = $return;
            $response["result"] = "success";
        }
        return $response;
    }

    /**
     * Get Random Constellations
     *
     * Uses Elastic Search to get a random collection of published Constellations.  Then, takes the ES results and
     * looks them up in our database to get summary constellations for each of the most recently published versions.
     * Puts them as a list on the response for the client.
     *
     * @param string[] $input Input array from the Server object
     * @return string[] The response to send to the client
     */
    public function getRandomConstellations(&$input) {
        $response = array();

        $withImages = false;
        if (isset($input["images"]) && $input["images"] == true) {
            $withImages = true;
        }

        if (\snac\Config::$USE_ELASTIC_SEARCH) {

            $results = $this->elasticSearch->listRandomConstellations(
                        \snac\Config::$ELASTIC_SEARCH_BASE_INDEX,
                        \snac\Config::$ELASTIC_SEARCH_BASE_TYPE,
                        $withImages);

            $return = array();
            foreach ($results as $i => $val) {
                $related = new \snac\data\Constellation();
                $related->setID($val["_source"]["id"]);
                $related->setArkID($val["_source"]["arkID"]);
                $relatedName = new \snac\data\NameEntry();
                $relatedName->setOriginal($val["_source"]["nameEntry"]);
                $related->addNameEntry($relatedName);
                if ($withImages && $val["_source"]["hasImage"]) {
                    $image = new \snac\data\Image();
                    $image->setURL($val["_source"]["imageURL"]);
                    if (isset($val["_source"]["imageMeta"]) && $val["_source"]["imageMeta"] !== null) {
                        $meta = $val["_source"]["imageMeta"];
                        if (isset($meta["infoURL"])) {
                            $image->setInfoURL($meta["infoURL"]);
                        }
                        if (isset($meta["info"])) {
                            $image->setInfo($meta["info"]);
                        }
                        if (isset($meta["author"]) && isset($meta["author"]["name"])) {
                            $image->setAuthor($meta["author"]["name"]);
                        }
                        if (isset($meta["author"]) && isset($meta["author"]["url"])) {
                            $image->setAuthorURL($meta["author"]["url"]);
                        }
                        if (isset($meta["license"]) && isset($meta["license"]["name"])) {
                            $image->setLicense($meta["license"]["name"]);
                        }
                        if (isset($meta["license"]) && isset($meta["license"]["url"])) {
                            $image->setLicenseURL($meta["license"]["url"]);
                        }

                    }
                    $related->addImage($image);
                }
                array_push($return, $related->toArray());
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

        if (\snac\Config::$USE_ELASTIC_SEARCH) {

            $results = $this->elasticSearch->listRecentlyUpdated(
                        \snac\Config::$ELASTIC_SEARCH_BASE_INDEX,
                        \snac\Config::$ELASTIC_SEARCH_BASE_TYPE);

            $return = array();
            foreach ($results as $i => $val) {
                $related = new \snac\data\Constellation();
                $related->setID($val["_source"]["id"]);
                $related->setArkID($val["_source"]["arkID"]);
                $relatedName = new \snac\data\NameEntry();
                $relatedName->setOriginal($val["_source"]["nameEntry"]);
                $related->addNameEntry($relatedName);
                array_push($return, $related->toArray());
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

    public function browseConstellations(&$input) {
        $response = array();

        $term = "";
        $position = "after";
        if (isset($input["term"]) && $input["term"] != "") {
            $term = $input["term"];
            // only update the position if the term is not null
            if (isset($input["position"]) && ($input["position"] == "middle" || $input["position"] == "before"))
                $position = $input["position"];
        }
        $entityType = null;
        if (isset($input["entity_type"]))
            $entityType = $input["entity_type"];
        $icid = 0;
        if (isset($input["icid"]))
            $icid = $input["icid"];

        $results = $this->cStore->browseNameIndex($term, $position, $entityType, $icid);

        $response["results"] = $results;
        $response["result"] = "success";

        return $response;
    }

    /**
     * Search For Constellation
     *
     * Given a search term, starting point (0+), and a count of results, search SNAC for a
     * constellation matching that name heading and return the list of results.
     *
     * @param string[] $input Input array from the Server object
     * @return string[] The response to send to the client
     */
    public function searchConstellations(&$input) {
        $response = array();

        // error condition
        if (!isset($input["term"]) || !isset($input["start"]) || !isset($input["count"])) {
            $this->logger->addDebug("Something was not set correctly", $input);
            return $response;
        }

        if ($input["entity_type"] === "")
            $input["entity_type"] = null;

        if (!isset($input["search_type"])) {
            $input["search_type"] = "default";
        }

        if (\snac\Config::$USE_ELASTIC_SEARCH) {
            switch($input["search_type"]) {
                case "autocomplete":
                    $response = $this->elasticSearch->searchMainIndexAutocomplete($input["term"], $input["entity_type"],
                                                                        $input["start"], $input["count"]);
                    break;
                case "advanced":
                    $response = $this->elasticSearch->searchMainIndexAdvanced($input["term"], $input["entity_type"],
                                                                        $input["start"], $input["count"]);
                    break;
                default:
                    $response = $this->elasticSearch->searchMainIndexWithDegree($input["term"], $input["entity_type"],
                                                                            $input["start"], $input["count"]);
            }


            $searchResults = array();
            // Update the ES search results to include information from the constellation
            foreach ($response["results"] as $k => $result) {
                $constellation = $this->cStore->readPublishedConstellationByID($result["id"], DBUtil::$READ_SHORT_SUMMARY);
                array_push($searchResults, $constellation->toArray());
            }
            $response["results"] = $searchResults;
            $response["count"] = $input["count"];
            $response["term"] = $input["term"];
            $response["search_type"] = $input["search_type"];

            // Limit the search results, if specified in the configuration
            if (isset(\snac\Config::$MAX_SEARCH_RESULT_PAGES) &&
                    $response["pagination"] > \snac\Config::$MAX_SEARCH_RESULT_PAGES)
                $response["pagination"] = \snac\Config::$MAX_SEARCH_RESULT_PAGES;

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

    /**
     * Reconcile Constellation
     *
     * Given a Constellation on the input, this runs a staged Reconciliation Engine against the
     * database and returns the list of possible matches.
     *
     * @param string[] $input Input array from the Server object
     * @throws \snac\exceptions\SNACPermissionException
     * @return string[] The response to send to the client
     */
    public function reconcileConstellation(&$input) {
        $engine = new \snac\server\identityReconciliation\ReconciliationEngine();

        // Add stages to run
        $engine->addStage("ElasticOriginalNameEntry");
        $engine->addStage("ElasticNameOnly");
        $engine->addStage("ElasticSeventyFive");
        $engine->addStage("MultiStage", "ElasticNameOnly", "SNACDegree");

        // Add post-processing stages
        $engine->addPostProcessingStage("OriginalLength");
        $engine->addPostProcessingStage("EntityTypeFilter");

        // The original-length-difference stage skewed the results beyond recognition.  It should
        // be re-considered before being included in the reconciliation process.  A lighter weighting
        // could make it beneficial.
        // $engine->addPostProcessingStage("OriginalLengthDifference");

        // Run the reconciliation engine against this identity
        $constellation = new \snac\data\Constellation($input["constellation"]);
        $engine->reconcile($constellation);


        $results = array();
        // Strip the Constellations out of the results and return them
        foreach ($engine->getResults() as $k => $v) {
            $results[$k] = $v->toArray();
        }
        return array("reconciliation" => $results, "result" => 'success');
    }

    /**
     * Read Report
     *
     * Reads the report the user requested from the database and returns it to the user.  If
     * the report doesn't exist, this method will return a failing result.
     *
     * @param string[] $input Input array from the Server object
     * @return string[] The response to send to the client
     */
    public function readReport(&$input) {
        $reportName = "General Report";
        switch ($input["type"]) {
            case "holdings":
                $reportName = "Holdings";
                break;
            case "general":
            default:
                break;
        }
        $report = $this->cStore->readReport($reportName);

        if ($report && $report != null && !empty($report))
            return array("result" => "success",
                         "reports" => json_decode($report["report"], true),
                         "timestamp" => $report["timestamp"]);

        return array("result" => "failure");
    }

    /**
     * Generate Report
     *
     * Generates the report asked for by the user, then stores it in the database for
     * later consumption.  A success/failure notice is returned to the user.  The user should
     * call readReport() to get the report back for viewing.
     *
     * @param string[] $input Input array from the Server object
     * @return string[] The response to send to the client
     */
    public function generateReport(&$input) {
        $reportEngine = new \snac\server\reporting\ReportingEngine();
        $reportName = "General Report";
        switch ($input["type"]) {
            case "holdings":
                $reportName = "Holdings";
                $reportEngine->addReport("AllHoldingInstitutions");
                break;
            case "general":
            default:
                $reportEngine->addReport("NumConstellations");
                $reportEngine->addReport("NumConstellationsByType");
                $reportEngine->addReport("TopEditorsThisWeek");
                $reportEngine->addReport("PublishesLastMonth");
                $reportEngine->addReport("TopHoldingInstitutions");
                break;
        }
        $reportEngine->setPostgresConnector($this->cStore->sqlObj()->connectorObj());

        $report = json_encode($reportEngine->runReports(), JSON_PRETTY_PRINT);

        $this->cStore->storeReport($reportName, $report, $this->user);

        return array("result" => "success");
    }
}

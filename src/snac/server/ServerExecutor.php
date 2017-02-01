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
use PhpParser\Node\Stmt\Break_;
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
            $this->uStore->removeSession($this->user);
            $response["user"] = $this->user->toArray();
            $response["result"] = "success";
        } else {
            $response["result"] = "failure";
        }

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
                    $response["results"] = $this->cStore->searchVocabulary(
                        $input["type"],
                        $input["query_string"],
                        $input["entity_type"]);
                    break;
            }
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

                $inList = false;
                $userList = $this->cStore->listConstellationsWithStatusForUser($this->user, "currently editing");
                foreach ($userList as $item) {
                    if ($item->getID() == $constellation->getID()) {
                        $inList = true;
                        break;
                    }
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

                // If this constellation is in the list of currently editing for the user, then send it for review
                if ($current->getVersion() == $constellation->getVersion() && $inList) {
                    $result = $this->cStore->writeConstellationStatus($this->user, $constellation->getID(), "needs review",
                            "User sending Constellation for review");


                    if (isset($result) && $result !== false) {
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

                list($currentStatus, $currentUserID, $currentNote) = $this->cStore->readConstellationUserStatus($constellation->getID());

                $inList = false;
                if ($currentUserID == $this->user->getUserID() &&
                    ($currentStatus == 'currently editing' || $currentStatus == 'locked editing')) {
                        $inList = true;
                    }

                $result = false;

                // If this constellation is the correct version, and the user was editing it, then publish it
                if ($current->getVersion() == $constellation->getVersion() && $inList) {
                    if ($current->getArk() === null) {
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
                        $microConstellation->setID($current->getID());
                        $microConstellation->setVersion($current->getVersion());
                        $microConstellation->setArkID($newArk);
                        $microConstellation->setEntityType($current->getEntityType());
                        $microConstellation->setOperation(\snac\data\Constellation::$OPERATION_UPDATE);

                        $written = $this->cStore->writeConstellation($this->user, $microConstellation,
                        "System assigning new ARK to constellation", "locked editing");
                        if ($written !== false && $written != null) {
                            $result = $written->getVersion();
                            unset($written);
                        }
                        $constellation->setArkID($newArk);
                    }

                    $result = $this->cStore->writeConstellationStatus($this->user, $constellation->getID(),
                                                                    "published", "User published constellation");
                }

                if (isset($result) && $result !== false) {
                    $this->logger->addDebug("successfully published constellation");
                    // Return the passed-in constellation from the user, with the new version number
                    $constellation->setVersion($result);
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

        $this->elasticSearch->writeToNameIndices($published);
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


                    $this->elasticSearch->deleteFromNameIndices($constellation);

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
            $constellation = $this->readConstellationFromDatabase($input);

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
            $this->logger->addDebug("Finished checking constellation status against the user");
            $response["constellation"] = $constellation->toArray();
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
     * @return null|\snac\data\Constellation The constellation object (or null)
     */
    public function readConstellationFromDatabase(&$input,  $includeMaintenanceHistory=false, $flags = 0) {
        $constellation = null;
        $readFlags = \snac\server\database\DBUtil::$FULL_CONSTELLATION;

        if ($flags !== 0)
            $readFlags = $flags;

        if ($includeMaintenanceHistory) {
            $readFlags = $readFlags | \snac\server\database\DBUtil::$READ_MAINTENANCE_INFORMATION;
        }

        $this->logger->addDebug("Reading constellation from the database, flags=$readFlags");
        if (isset($input["arkid"])) {
            // Reading the given ark id
            $constellation = $this->cStore->readPublishedConstellationByARK(
                    $input["arkid"],
                    $readFlags
                );
            if ($constellation === false) {
                // This means that the Constellation doesn't have a published version!
                throw new \snac\exceptions\SNACInputException("Constellation with ark " .
                        $input["arkid"] . " does not have a published version.");
            }

        } else if (isset($input["constellationid"])) {
            // Reading the given constellation id by reading the database
                // Read the constellation
            if (isset($input["version"])) {
                $constellation = $this->cStore->readConstellation(
                        $input["constellationid"],
                        $input["version"],
                        $readFlags);
            } else {
                $constellation = $this->cStore->readPublishedConstellationByID(
                        $input["constellationid"],
                        $readFlags);
                if ($constellation === false) {
                    // This means that the Constellation doesn't have a published version!
                    throw new \snac\exceptions\SNACInputException("Constellation with id " .
                            $input["constellationid"] . " does not have a published version.");
                }
            }
        }
        $this->logger->addDebug("Finished reading constellation from the database");

        return $constellation;

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
                if ( $status == "published" || $inList || ($status == "needs review" && 1)) {
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


        $constellation = $this->readConstellationFromDatabase($input, true);
        if ($constellation == null) {
            throw new \snac\exceptions\SNACInputException("Constellation does not exist");
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

        $constellation = $this->readConstellationFromDatabase($input, false, \snac\server\database\DBUtil::$READ_MICRO_SUMMARY);

        if ($constellation === null) {
            throw new \snac\exceptions\SNACInputException("Constellation not found");
        }

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
                array_push($return["out"], array(
                    "constellation" => $this->cStore->readPublishedConstellationByID($rel->getTargetConstellation(),
                                                    \snac\server\database\DBUtil::$READ_MICRO_SUMMARY)->toArray(),
                    "relation" => $rel->toArray()
                ));
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
        $engine->addStage("OriginalLength");
        $engine->addStage("MultiStage", "ElasticNameOnly", "OriginalLengthDifference");
        $engine->addStage("MultiStage", "ElasticNameOnly", "SNACDegree");

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
}

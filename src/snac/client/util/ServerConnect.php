<?php
/**
 * Connect Class Util file
 *
 * Contains the connection class between the clients and server
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\client\util;

/**
 * Server Connect Utility Class
 *
 * Queries the server with the given query (associative array) and returns the backend
 * server's response (as associative array).
 *
 * @author Robbie Hott
 *
 */
class ServerConnect {

    /**
     * @var string The URL for the backend server
     */
    private $serverURL;

    /**
     * @var \snac\data\User User to connect to the server as
     */
    private $user = null;

    /**
     * @var \Monolog\Logger $logger Logger for this server connection
     */
    private $logger = null;

    /**
     * Default constructor
     *
     * Creates a connection to the server with the given user object.
     *
     * @param \snac\data\User $user User object to use in making server requests
     */
    public function __construct($user = null) {
        global $log;

        $this->serverURL = \snac\Config::$INTERNAL_SERVERURL;

        if ($user != null)
            $this->user = $user;

        // create a log channel
        $this->logger = new \Monolog\Logger('ServerConnect');
        $this->logger->pushHandler($log);

    }

    /**
     * Set User
     *
     * Set the user to make server connections.
     *
     * @param \snac\data\User|null $user User object
     */
    public function setUser(&$user = null) {
        if ($user != null)
            $this->user = $user;
    }

    /**
     * Perform Server Query
     *
     * Uses CURL to query the server by converting the given array into JSON, sending that
     * to the back-end server via PUT, then returns the server's response as an associative
     * array.
     *
     * @param array $query Associative array of the query information to send to the
     *        back-end server
     * @return string[] The response from the server
     */
    public function query($query) {
        $userQuery = array();
        if ($this->user != null)
            $userQuery["user"] = $this->user->toArray();
        $realQuery = array_merge($query, $userQuery);

        $this->logger->addDebug("Sending the following server query", $realQuery);
        // Encode the query as json
        $data = json_encode($realQuery);

        // Use CURL to send request to the internal server
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->serverURL);
        curl_setopt($ch, CURLOPT_HTTPHEADER,
                array (
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($data)
                ));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);


        // Return the server response as associative array
        $return = json_decode($response, true);
        if ($return == null) {
            $this->logger->addDebug("Got the following improper server response", array($response));
            return $response;
        }

        $this->logger->addDebug("Got the following server response", $return);
        return $return;
    }



    /**
     * Lookup Vocabulary Term
     *
     * Custom Query to lookup a vocabulary term by ID
     *
     * @param int The ID to look up in the database
     * @return \snac\data\Term The term found
     */
    public function lookupTerm($id) {

        $request = array ();
        $request["command"] = "vocabulary";
        $request["term_id"] = $id;

        $response = $this->query($request);

        if (isset($response["term"])) {
            $term = new \snac\data\Term($response["term"]);
            return $term;
        }

        return null;
    }

    public function lookupResource($id, $version=null) {
        $request = array ();
        $request["command"] = "read_resource";
        $request["resourceid"] = $id;
        $request["version"] = $version;

        $response = $this->query($request);

        if (isset($response["resource"])) {
            $resource = new \snac\data\Resource($response["resource"]);
            return $resource;
        }

        $resource = new \snac\data\Resource();
        $resource->setID($id);
        $resource->setVersion($version);
        return $resource;
    }
}

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
     * Constructor
     * 
     * Takes the input parameters to the web server as an associative array.  These will likely
     * be the GET or POST variables from the user's web browser.
     * 
     * @param array $input web input as an associative array
     */
    public function __construct($input) {

        $this->responseHeaders = array();
        $this->input = $input;
        if (!isset($this->input["command"]))
            $this->input["command"] = "";
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
            if (isset($serverResponse["constellation"]))
                $display->setData($serverResponse["constellation"]);
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
            
            $constellation = $this->serializeToConstellation();

            // Build a data structure to send to the server
            $request = array("command"=>"update_constellation");
            
            



            // Send the query to the server
            $request["constellation"] = $constellation->toArray();
            $serverResponse = $connect->query($request);
            

            // Generate response to the user's web browser
            //$response = array(); 
            $response = $serverResponse;
            $this->response = json_encode($response, JSON_PRETTY_PRINT);
            array_push($this->responseHeaders, "Content-Type: text/json");
            return;

        } else if ($this->input["command"] == "vocabulary") {
            // Check what kind of vocabulary is wanted, and ask server for it
            $request = array();
            $request["command"] = "vocabulary";
            $request["type"] = $this->input["type"];
            $request["query_string"] = $this->input["q"];
            
            // Send the query to the server
            $serverResponse = $connect->query($request);

            foreach ($serverResponse["results"] as $k => $v) 
                $serverResponse["results"][$k]["text"] = $v["value"];

            // Send the response back to the web client
            $this->response = json_encode($serverResponse, JSON_PRETTY_PRINT);
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
    
    /**
     * Serialize post data to Constellation
     * 
     * Takes the POST data from a SAVE operation and generates
     * a Constellation object to be used by the rest of the system
     * 
     * @return \snac\data\Constellation
     */
    private function serializeToConstellation() {
        $constellation = new \snac\data\Constellation();
        
        // Rework the input into arrays of sections
        $nested = array();
        $nested["nameEntry"] = array();
        $nested["otherID"] = array();
        $nested["source"] = array();
        $nested["resourceRelation"] = array();
        $nested["constellationRelation"] = array();
        $nested["subject"] = array();
        $nested["occupation"] = array();
        $nested["place"] = array();
        
        foreach ($this->input as $k => $v) {
            // Try to split on underscore
            $parts = explode("_", $k);
            if (count($parts) == 1) {
                // only one piece: non-repeating
                // key => value ==> nested[key] = value
                $nested[$k] = $v;
            } else if (count($parts) == 2) {
                // two pieces: single-val repeating
                // key_index => value ==> nested[key][index] = value
                $nested[$parts[0]][$parts[1]] = $v;
            } else if (count($parts) == 3) {
                // three parts: mulitple-vals repeating
                // key_subkey_index => value ==> nested[key][index][subkey] = value
                if (!isset($nested[$parts[0]][$parts[2]]))
                    $nested[$parts[0]][$parts[2]] = array();
                    $nested[$parts[0]][$parts[2]][$parts[1]] = $v;
            }
        }
        
        // TODO
        if (isset($nested["ark"]))
            $constellation->setArkID($nested["ark"]);
        if (isset($nested["constellationid"]))
            $constellation->setID($nested["constellationid"]);
        if (isset($nested["version"]))
            $constellation->setVersion($nested["version"]);
        if (isset($nested["entityType"]))
            $constellation->setEntityType($nested["entityType"]);
        if (isset($nested["gender"]))
            $constellation->setGender($nested["gender"]);
        if (isset($nested["nationality"]))
            $constellation->setNationality($nested["nationality"]);
        if (isset($nested["languageCode"]))
            $constellation->setLanguageUsed($nested["languageCode"], ""); // TODO
        if (isset($nested["scriptCode"]))
            $constellation->setScriptUsed($nested["scriptCode"], ""); // TODO
        if (isset($nested["biogHist"]))
            $constellation->addBiogHist($nested["biogHist"]);
        foreach ($nested["nameEntry"] as $data) {
            $nameEntry = new \snac\data\NameEntry();
            $nameEntry->setID($data["id"]);
            $nameEntry->setVersion($data["version"]);
            $nameEntry->setOriginal($data["original"]);
            $constellation->addNameEntry($nameEntry);
        }
        foreach ($nested["otherID"] as $data) {
            $constellation->addOtherRecordID($data["type"], $data["href"]);
        }
        foreach ($nested["source"] as $data) {
            $constellation->addSource("simple", $data["href"]); // TODO
        }
        foreach ($nested["resourceRelation"] as $data) {
            $relation = new \snac\data\ResourceRelation();
            $relation->setID($data["id"]);
            $relation->setVersion($data["version"]);
            $relation->setContent($data["content"]);
            $relation->setRole($data["role"]);
            $relation->setLink($data["link"]);
            $relation->setDocumentType($data["documentType"]);
            $relation->setSource($data["source"]);
            $constellation->addResourceRelation($relation);
        }
        foreach ($nested["constellationRelation"] as $data) {
            $relation = new \snac\data\ConstellationRelation();
            $relation->setID($data["id"]);
            $relation->setVersion($data["version"]);
            $relation->setContent($data["content"]);
            $relation->setTargetArkID($data["targetArkID"]);
            $relation->setTargetType($data["targetEntityType"]);
            $relation->setType($data["type"]);
            $constellation->addRelation($relation);
        }
        foreach ($nested["subject"] as $data) {
            $constellation->addSubject($data);
        }
        foreach ($nested["function"] as $data) {
            $fn = new \snac\data\SNACFunction();
            $fn->setID($data["id"]);
            $fn->setVersion($data["version"]);
            $fn->setTerm($data["term"]);
            $constellation->addFunction($fn);
        }
        foreach ($nested["occupation"] as $data) {
            $occupation = new \snac\data\Occupation();
            $occupation->setID($data["id"]);
            $occupation->setVersion($data["version"]);
            $occupation->setTerm($data["term"]);
            $constellation->addOccupation($occupation);
        }
        foreach ($nested["place"] as $data) {
            $place = new \snac\data\Place();
            $place->setID($data["id"]);
            $place->setVersion($data["version"]);
            $placeEntry = new \snac\data\PlaceEntry();
            $placeEntry->setOriginal($data["original"]);
            $placeEntry->setID($data["originalid"]);
            $placeEntry->setVersion($data["originalversion"]);
            $bestMatch = new \snac\data\PlaceEntry();
            $bestMatch->setOriginal($data["bestMatch"]);
            $bestMatch->setID($data["bestMatchid"]);
            $bestMatch->setVersion($data["bestMatchversion"]);
            $placeEntry->setBestMatch($bestMatch);
            $place->addPlaceEntry($placeEntry);
            $constellation->addPlace($place);
        }
        
        return $constellation;
    }
}

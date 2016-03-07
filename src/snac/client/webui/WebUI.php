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

            // Get the constellation object
            $constellation = $this->serializeToConstellation();

            // Build a data structure to send to the server
            $request = array("command"=>"update_constellation");

            // Send the query to the server
            $request["constellation"] = $constellation->toArray();
            $serverResponse = $connect->query($request);

            // Generate response to the user's web browser
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
    
    private function getOperation($data) {
        if (isset($data['operation'])) {
            $op = $data["operation"];
            if ($op == "insert") {
                return \snac\data\AbstractData::$OPERATION_INSERT;
            } else if ($op == "update") {
                return \snac\data\AbstractData::$OPERATION_UPDATE;
            } else if ($op == "delete") {
                return \snac\data\AbstractData::$OPERATION_DELETE;
            }
            
            return null;
        }
        return null;
    }
    
    private function parseSCM($scm) {
        // parse through the SCM array
        //TODO
        return array();
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
        $nested["gender"] = array();
        $nested["exist"] = array();
        $nested["biogHist"] = array();
        $nested["language"] = array();
        $nested["nationality"] = array();
        $nested["function"] = array();
        $nested["legalStatus"] = array();
        $nested["conventionDeclaration"] = array();
        $nested["generalContext"] = array();
        $nested["structureOrGenealogy"] = array();
        $nested["mandate"] = array();
        $nested["nameEntry"] = array();
        $nested["sameAs"] = array();
        $nested["source"] = array();
        $nested["resourceRelation"] = array();
        $nested["constellationRelation"] = array();
        $nested["subject"] = array();
        $nested["occupation"] = array();
        $nested["place"] = array();

        foreach ($this->input as $k => $v) {
            // Try to split on underscore
            $parts = explode("_", $k);
            
            // Empty should be null
            if ($v == "")
                $v = null;
            
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
            } else if (count($parts) == 4) {
                // four parts: controlled vocabulary repeating
                // key_subkey_subsubkey_index => value ==> nested[key][index][subkey][subsubkey] = value
                if (!isset($nested[$parts[0]][$parts[3]]))
                    $nested[$parts[0]][$parts[3]] = array();
                if (!isset($nested[$parts[0]][$parts[3]][$parts[1]]))
                    $nested[$parts[0]][$parts[3]][$parts[1]] = array();
                $nested[$parts[0]][$parts[3]][$parts[1]][$parts[2]] = $v;
            } else if (count($parts) == 5) {
                // five parts: scm repeating
                // scm_key_subkey_subindex_index => value ==> nested[key][index][scm][subindex][subkey] = value
                if (!isset($nested[$parts[1]][$parts[4]]))
                    $nested[$parts[1]][$parts[4]] = array();
                if (!isset($nested[$parts[1]][$parts[4]][$parts[0]]))
                    $nested[$parts[1]][$parts[4]][$parts[0]] = array();
                if (!isset($nested[$parts[1]][$parts[4]][$parts[0]][$parts[3]]))
                    $nested[$parts[1]][$parts[4]][$parts[0]][$parts[3]] = array();
                if (!isset($nested[$parts[1]][$parts[4]][$parts[0]][$parts[3]][$parts[2]]))
                    $nested[$parts[1]][$parts[4]][$parts[0]][$parts[3]][$parts[2]] = array();
                $nested[$parts[1]][$parts[4]][$parts[0]][$parts[3]][$parts[2]] = $v;
            } else if (count($parts) == 6) {
                // six parts: scm repeating
                // scm_key_subkey_subsubkey_subindex_index => value ==> nested[key][index][scm][subindex][subkey][subsubkey] = value
                //{{short}}_scm_languagescript_id_{{j}}_{{i}}
                if (!isset($nested[$parts[1]][$parts[5]]))
                    $nested[$parts[1]][$parts[5]] = array();
                if (!isset($nested[$parts[1]][$parts[5]][$parts[0]]))
                    $nested[$parts[1]][$parts[5]][$parts[0]] = array();
                if (!isset($nested[$parts[1]][$parts[5]][$parts[0]][$parts[4]]))
                    $nested[$parts[1]][$parts[5]][$parts[0]][$parts[4]] = array();
                if (!isset($nested[$parts[1]][$parts[5]][$parts[0]][$parts[4]][$parts[2]]))
                    $nested[$parts[1]][$parts[5]][$parts[0]][$parts[4]][$parts[2]] = array();
                if (!isset($nested[$parts[1]][$parts[5]][$parts[0]][$parts[4]][$parts[2]][$parts[3]]))
                    $nested[$parts[1]][$parts[5]][$parts[0]][$parts[4]][$parts[2]][$parts[3]] = array();
                $nested[$parts[1]][$parts[5]][$parts[0]][$parts[4]][$parts[2]][$parts[3]] = $v;
            }
        }
        
 
        // TODO
        if (isset($nested["ark"]))
            $constellation->setArkID($nested["ark"]);
        if (isset($nested["constellationid"]))
            $constellation->setID($nested["constellationid"]);
        if (isset($nested["version"]))
            $constellation->setVersion($nested["version"]);
        if (isset($nested["operation"]))
            $constellation->setOperation($this->getOperation($nested));
        if (isset($nested["entityType"])) {
            $term = new \snac\data\Term();
            $term->setID($nested["entityType"]);
            $constellation->setEntityType($term);
        }
        foreach ($nested["gender"] as $data) {
            $term = new \snac\data\Term();
            $term->setID($data["term"]["id"]);
            $gender = new \snac\data\Gender();
            $gender->setID($data["id"]);
            $gender->setVersion($data["id"]);
            $gender->setTerm($term);
            $gender->setOperation($this->getOperation($data));
            
            // Example
            $gender->setAllSNACControlMetadata($this->parseSCM($gender["scm"]));
            
            $constellation->setGender($gender);
        }

        foreach ($nested["exist"] as $data) {
            $date = new \snac\data\SNACDate();
            $date->setID($data["id"]);
            $date->setVersion($data["version"]);
            $date->setOperation($this->getOperation($data));

            $date->setNote($data["note"]);
            $type = new \snac\data\Term();
            $type->setID($data["starttype"]["id"]);
            $date->setFromDate($data["startoriginal"], $data["start"], $type);
            $date->setFromDateRange($data["startnotBefore"], $data["startnotAfter"]);
            $type = new \snac\data\Term();
            $type->setID($data["endtype"]["id"]);
            $date->setFromDate($data["endoriginal"], $data["end"], $type);
            $date->setToDateRange($data["endnotBefore"], $data["endnotAfter"]);
            
            $constellation->addDate($date);
        }
        

        foreach ($nested["biogHist"] as $data) {
            $bh = new \snac\data\BiogHist();
            $bh->setID($data["id"]);
            $bh->setVersion($data["version"]);
            $bh->setOperation($this->getOperation($data));
            
            $bh->setText($data["text"]);
            
            $lang = new \snac\data\Language();
            $lang->setID($data["language"]["id"]);
            $lang->setVersion($data["language"]["version"]);
            $lang->setOperation($this->getOperation($data));
            
            $term = new \snac\data\Term();
            $term->setID($data["languagelanguage"]["id"]);
            $lang->setLanguage($term);
            
            $term = new \snac\data\Term();
            $term->setID($data["languagescript"]["id"]);
            $lang->setScript($term);
            
            $bh->setLanguage($lang);
            
            $constellation->addBiogHist($bh);
        }

        foreach ($nested["language"] as $data) {
            $lang = new \snac\data\Language();
            $lang->setID($data["id"]);
            $lang->setVersion($data["version"]);
            $lang->setOperation($this->getOperation($data));
            
            $term = new \snac\data\Term();
            $term->setID($data["language"]["id"]);
            $lang->setLanguage($term);
            
            $term = new \snac\data\Term();
            $term->setID($data["script"]["id"]);
            $lang->setScript($term);

            $constellation->addLanguageUsed($lang);
        }

        foreach ($nested["nationality"] as $data) {
            $nationality = new \snac\data\Nationality();
            $nationality->setID($data["id"]);
            $nationality->setVersion($data["version"]);
            $nationality->setOperation($this->getOperation($data));

            $term = new \snac\data\Term();
            $term->setID($data["term"]["id"]);
            $nationality->setTerm($term);

            $constellation->addNationality($nationality);
        }

        foreach ($nested["function"] as $data) {
            $fun = new \snac\data\SNACFunction();
            $fun->setID($data["id"]);
            $fun->setVersion($data["version"]);
            $fun->setOperation($this->getOperation($data));

            $term = new \snac\data\Term();
            $term->setID($data["term"]["id"]);
            $fun->setTerm($term);

            $constellation->addFunction($fun);
        }

        foreach ($nested["legalStatus"] as $data) {
            $legalStatus = new \snac\data\LegalStatus();
            $legalStatus->setID($data["id"]);
            $legalStatus->setVersion($data["version"]);
            $legalStatus->setOperation($this->getOperation($data));

            $term = new \snac\data\Term();
            $term->setID($data["term"]["id"]);
            $legalStatus->setTerm($term);

            $constellation->addLegalStatus($legalStatus);
        }

        foreach ($nested["conventionDeclaration"] as $data) {
            $conventionDeclaration = new \snac\data\ConventionDeclaration();
            $conventionDeclaration->setID($data["id"]);
            $conventionDeclaration->setVersion($data["version"]);
            $conventionDeclaration->setOperation($this->getOperation($data));
            
            $conventionDeclaration->setText($data["text"]);
            
            $constellation->addConventionDeclaration($conventionDeclaration);
        }

        foreach ($nested["generalContext"] as $data) {
            $generalContext = new \snac\data\GeneralContext();
            $generalContext->setID($data["id"]);
            $generalContext->setVersion($data["version"]);
            $generalContext->setOperation($this->getOperation($data));
            
            $generalContext->setText($data["text"]);
            
            $constellation->addGeneralContext($generalContext);
        }

        foreach ($nested["structureOrGenealogy"] as $data) {
            $structureOrGenealogy = new \snac\data\StructureOrGenealogy();
            $structureOrGenealogy->setID($data["id"]);
            $structureOrGenealogy->setVersion($data["version"]);
            $structureOrGenealogy->setOperation($this->getOperation($data));
            
            $structureOrGenealogy->setText($data["text"]);
            
            $constellation->addStructureOrGenealogy($structureOrGenealogy);
        }

        foreach ($nested["mandate"] as $data) {
            $mandate = new \snac\data\Mandate();
            $mandate->setID($data["id"]);
            $mandate->setVersion($data["version"]);
            $mandate->setOperation($this->getOperation($data));
            
            $mandate->setText($data["text"]);
            
            $constellation->addMandate($mandate);
        }

        foreach ($nested["nameEntry"] as $data) {
            $nameEntry = new \snac\data\NameEntry();
            $nameEntry->setID($data["id"]);
            $nameEntry->setVersion($data["version"]);
            $nameEntry->setOperation($this->getOperation($data));

            $nameEntry->setOriginal($data["original"]);
            $nameEntry->setPreferenceScore($data["preferenceScore"]);

            $lang = new \snac\data\Language();
            $lang->setID($data["language"]["id"]);
            $lang->setVersion($data["language"]["version"]);
            $lang->setOperation($this->getOperation($data));
            
            $term = new \snac\data\Term();
            $term->setID($data["languagelanguage"]["id"]);
            $lang->setLanguage($term);
            
            $term = new \snac\data\Term();
            $term->setID($data["languagescript"]["id"]);
            $lang->setScript($term);
            
            $nameEntry->setLanguage($lang);

            $constellation->addNameEntry($nameEntry);
        }

        foreach ($nested["sameAs"] as $data) {
            $sameas = new \snac\data\SameAs();
            $sameas->setID($data["id"]);
            $sameas->setVersion($data["version"]);
            $sameas->setOperation($this->getOperation($data));

            $sameas->setText($data["text"]);
            $sameas->setURI($data["uri"]);

            $type = new \snac\data\Term();
            $type->setID($data["type"]["id"]);
            $sameas->setType($type);

            $constellation->addOtherRecordID($sameas);
        }

        foreach ($nested["source"] as $data) {
            $source = new \snac\data\Source();
            $source->setID($data["id"]);
            $source->setVersion($data["version"]);
            $source->setOperation($this->getOperation($data));

            $source->setText($data["text"]);
            $source->setURI($data["uri"]);
            $source->setNote($data["note"]);

            $lang = new \snac\data\Language();
            $lang->setID($data["language"]["id"]);
            $lang->setVersion($data["language"]["version"]);
            $lang->setOperation($this->getOperation($data));
            
            $term = new \snac\data\Term();
            $term->setID($data["languagelanguage"]["id"]);
            $lang->setLanguage($term);
            
            $term = new \snac\data\Term();
            $term->setID($data["languagescript"]["id"]);
            $lang->setScript($term);
            
            $source->setLanguage($lang);

            $constellation->addSource($source);
        }

        foreach ($nested["resourceRelation"] as $data) {
            $relation = new \snac\data\ResourceRelation();
            $relation->setID($data["id"]);
            $relation->setVersion($data["version"]);
            $relation->setOperation($this->getOperation($data));

            $relation->setContent($data["content"]);
            $relation->setLink($data["link"]);
            $relation->setSource($data["source"]);
            $relation->setNote($data["note"]);

            $type = new \snac\data\Term();
            $type->setID($data["documentType"]["id"]);
            $relation->setDocumentType($type);

            $role = new \snac\data\Term();
            $role->setID($data["role"]["id"]);
            $relation->setRole($role);

            $constellation->addResourceRelation($relation);
        }

        foreach ($nested["constellationRelation"] as $data) {
            $relation = new \snac\data\ConstellationRelation();
            $relation->setID($data["id"]);
            $relation->setVersion($data["version"]);
            $relation->setOperation($this->getOperation($data));

            $relation->setTargetConstellation($data["targetID"]);
            $relation->setTargetArkID($data["targetArkID"]);
            $relation->setContent($data["content"]);
            $relation->setNote($data["note"]);

            $type = new \snac\data\Term();
            $type->setID($data["type"]["id"]);
            $relation->setType($type);

            $constellation->addRelation($relation);
        }

        foreach ($nested["subject"] as $data) {
            $subject = new \snac\data\Subject();
            $subject->setID($data["id"]);
            $subject->setVersion($data["version"]);
            $subject->setOperation($this->getOperation($data));

            $term = new \snac\data\Term();
            $term->setID($data["term"]["id"]);
            $subject->setTerm($term);

            $constellation->addSubject($subject);
        }

        foreach ($nested["occupation"] as $data) {
            $occupation = new \snac\data\Occupation();
            $occupation->setID($data["id"]);
            $occupation->setVersion($data["version"]);
            $occupation->setOperation($this->getOperation($data));

            $term = new \snac\data\Term();
            $term->setID($data["term"]["id"]);
            $occupation->setTerm($term);

            $constellation->addOccupation($occupation);
        }

        foreach ($nested["place"] as $data) {
            $place = new \snac\data\Place();
            $place->setID($data["id"]);
            $place->setVersion($data["version"]);
            $place->setOperation($this->getOperation($data));

            $place->setOriginal($data["original"]);
            $place->setScore($data["score"]);
            $place->setNote($data["note"]);

            $term = new \snac\data\Term();
            $term->setID($data["type"]["id"]);
            $place->setType($term);

            $term = new \snac\data\Term();
            $term->setID($data["role"]["id"]);
            $place->setRole($term);

            $geoterm = new \snac\data\GeoTerm();
            $geoterm->setID($data["geoplace"]["id"]);
            $place->setGeoTerm($geoterm);
            

            if ($data["confirmed"] === "true")
                $place->confirm();
            else
                $place->deconfirm();

            $constellation->addPlace($place);
        }
        
  
        return $constellation;
    }
}

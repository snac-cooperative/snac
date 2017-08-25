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
     * @var \snac\data\User $user The user in the current session
     */
    private $user = null;

    /**
     * @var boolean[] $permissions Associative array of permissions for this user
     */
    private $permissions = null;

    /**
     * @var \Monolog\Logger $logger Logger for this server
     */
    private $logger = null;

    /**
     * Constructor
     *
     * @param \snac\data\User|null $user The current user object
     */
    public function __construct(&$user = null) {
        global $log;

        $this->permissions = array();

        // set up server connection
        $this->connect = new ServerConnect($user);
        $this->user = $user;

        // create a log channel
        $this->logger = new \Monolog\Logger('WebUIExec');
        $this->logger->pushHandler($log);

        return;
    }

    /**
     * Set User
     *
     * Set the user object to use when connecting with the Server
     *
     * @param \snac\data\User|null $user User object
     */
    public function setUser(&$user = null) {
        $this->connect->setUser($user);
        $this->user = $user;
    }

    /**
     * Set User Permissions Data
     *
     * Sets the permissions bitfield (as an associative array) for the user connected
     * to this session.  To maintain compatibility with Twig and other client-side scripts,
     * permission/privilege labels must have spaces and special characters removed.
     *
     * @param boolean[] $data Associative array of Permission to boolean flag
     */
    public function setPermissionData($data) {
        $this->permissions = $data;
    }


	/**
     * Get Connection Graph Data
     *
     * Gets the data that drives the IC-IC connection graph.  This method
     * uses a direct connection to Neo4J to read the graph information. It takes
     * two parameters as input:
     *  - degree: the degrees of separation from the query node
     *  - delta:  the level of importance for connected nodes, 0 = bring back all
     *            nodes of any importance, 20 = bring back only important nodes
     *            (proportional to the number of resource relations per node)
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param string[][] $constellation Constellation array object from the server
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function getConnectionGraphData(&$input, &$constellation) {
	
        $neo4j = new \snac\client\webui\util\Neo4JUtil();
        $deg = 2; 
        if ((isset($input["degree"])) && 
            (($input["degree"] == 1) || ($input["degree"] == 2) || ($input["degree"] == 3) || ($input["degree"] == 4))) { 
            $deg = $input["degree"]; 
        }
        $dlt = 10; 
        if ((isset($input["delta"])) && 
            ($input["delta"] >= 0) && ($input["delta"] <= 20)) { 
            $dlt = $input["delta"]; 
        }
        
        $alchemy_data = $neo4j->getAlchemyData($constellation["id"], $deg, $dlt);
        
        return $alchemy_data;
	
    }

    /**
     * Handle Visualization Commands
     *
     * Acts on the subcommand argument passed in the input, and returns either the desired
     * visualization view or the data required by a visualization.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\client\webui\display\Display $display The display object for page creation
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function handleVisualization(&$input, &$display) {
        if (isset($input["subcommand"])) {
            $serverResponse = $this->getConstellation($input, $display, "summary");
            $constellation = $serverResponse["constellation"];
            if (\snac\Config::$DEBUG_MODE == true) {
                $display->addDebugData("constellationSource", json_encode($serverResponse["constellation"], JSON_PRETTY_PRINT));
                $display->addDebugData("serverResponse", json_encode($serverResponse, JSON_PRETTY_PRINT));
            }
            if (isset($serverResponse["constellation"])) {
                switch ($input["subcommand"]) {
                    case "connection_data":
                        return $this->getConnectionGraphData($input, $constellation);
                        break;
                    case "connection_graph":
                        $display->setTemplate("neo4j_graph_page");
                        $display->setData($constellation);
                        return;
                    case "radial_graph":
                        $display->setTemplate("radial_graph_page");
                        $display->setData($constellation);
                        return;
                }
            } else {
                // No constellation error
                $this->logger->addDebug("Error page being drawn");
                $this->drawErrorPage($serverResponse, $display);
            }
        }
        // No subcommand error
        $this->logger->addDebug("Error page being drawn");
        $this->drawErrorPage("Subcommand required", $display);
    }
	
    /**
     * Display Edit Page
     *
     * Fills the display object with the edit page for a given user.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\client\webui\display\Display $display The display object for page creation
     */
    public function displayEditPage(&$input, &$display) {
        $query = $input;
        $constellation = null;
        // If they are asking for a part and they haven't been given a constellation ID (new page), then let them through anyway
        if ($input["command"] == "edit_part" && isset($input["part"]) && (!isset($input["constellationid"]) || $input["constellationid"] == '') ) {
            $c = new \snac\data\Constellation();
            $constellation = $c->toArray();
        } else {
            $this->logger->addDebug("Sending query to the server", $query);
            $serverResponse = $this->connect->query($query);
            $this->logger->addDebug("Received server response", array($serverResponse));
            if (isset($serverResponse["constellation"]))
                $constellation = $serverResponse["constellation"];
        }

        if ($constellation != null) {
            if ($input["command"] == "edit_part" && isset($input["part"]))
                $display->setTemplate("edit_tabs/".$input["part"]);
            else
                $display->setTemplate("edit_page");
            if (\snac\Config::$DEBUG_MODE == true) {
                $display->addDebugData("constellationSource", json_encode($constellation, JSON_PRETTY_PRINT));
                if (isset($serverResponse))
                    $display->addDebugData("serverResponse", json_encode($serverResponse, JSON_PRETTY_PRINT));
            }
            
                
            $this->logger->addDebug("Setting constellation data into the page template");
            $display->setData(array_merge(
                $constellation,
                array("reviewNote" => isset($serverResponse["review_note"]) ? $serverResponse["review_note"] : null)
            ));
        } else {
                $this->logger->addDebug("Error page being drawn");
                $this->drawErrorPage($serverResponse, $display);
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
     * Get Constellation
     *
     * Query the server to read a full Constellation object.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\client\webui\display\Display $display The display object for page creation
     * @return string[] The response from the server. It is a json_decode'ed response from curl.
     */
    protected function getConstellation(&$input, &$display, $summary=false) {
        $query = array();
        if (isset($input["constellationid"]))
            $query["constellationid"] = $input["constellationid"];
        if (isset($input["version"]))
            $query["version"] = $input["version"];
        if (isset($input["arkid"]))
            $query["arkid"] = $input["arkid"];
        $query["command"] = "read";
        if ($summary !== false) {
            $query["type"] = $summary;
        }

        $this->logger->addDebug("Sending query to the server", $query);
        $serverResponse = $this->connect->query($query);
        $this->logger->addDebug("Received server response");
        return $serverResponse;
    }

    /**
     * Display Browse Page
     *
     * Loads the browse page into the display.
     *
     * @param \snac\client\webui\display\Display $display The display object for page creation
     */
    public function displayBrowsePage(&$display) {
        $display->setTemplate("browse_page");
    }

    /**
     * Perform Browse Search
     *
     * Performs the browsing search for the browse page, returning the list
     * to be displayed to the user.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function performBrowseSearch(&$input) {
        $term = "";
        $position = "middle";
        $entityType = "";
        $icid = 0;

        if (isset($input["entity_type"]))
            $entityType = $input["entity_type"];
        if (isset($input["position"]))
            $position = $input["position"];
        if (isset($input["term"]))
            $term = $input["term"];
        if (isset($input["ic_id"]))
            $icid = $input["ic_id"];

        $query = array(
            "command" => "browse",
            "term" => $term,
            "entity_type" => $entityType,
            "position" => $position,
            "icid" => $icid
        );

        // Query the server for the elastic search results
        $serverResponse = $this->connect->query($query);

        return $serverResponse;

    }


    /**
    * Display Search Page
    *
    * Loads the search page for a given query input into the display.
    *
    * @param string[] $input Post/Get inputs from the webui
    * @param \snac\client\webui\display\Display $display The display object for page creation
    */
    public function displaySearchPage(&$input, &$display) {
        if (!isset($input["term"]))
            $input["term"] = "";

        if (!isset($input["entity_type"]))
            $input["entity_type"] = "";

        if (isset($input["q"])) {
            $input["term"] = $input["q"];
        }
        $results = $this->performNameSearch($input);
        if (isset($results["results"])) {
            $results["query"] = $input["term"];
            $results["entityType"] = $input["entity_type"];
            $results["searchType"] = $results["search_type"];
            $display->setTemplate("search_page");
            $display->setData($results);
        } else {
            $this->logger->addDebug("Error page being drawn");
            $this->drawErrorPage($results, $display);
        }
    }

    /**
     * Display View Page
     *
     * Loads the view page for a given constellation input into the display.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\client\webui\display\Display $display The display object for page creation
     */
    public function displayViewPage(&$input, &$display) {
        $message = null;
        if (isset($input["part"]) && $input["part"] == "relations")
            $serverResponse = $this->getConstellation($input, $display, false);
        else
            $serverResponse = $this->getConstellation($input, $display, "summary");
        if (isset($serverResponse["constellation"])) {
            if (isset($serverResponse["constellation"]["dataType"])) {
                // We have only ONE constellation, so display
                $constellation = $serverResponse["constellation"];
                $editingUser = null;
                $holdings = array();
                
                if (isset($input["part"]) && $input["part"] == "relations") {
                    $display->setTemplate("view_page_relations");
                    $this->logger->addDebug("Getting Holding institution information from the resource relations");
                    $c = new \snac\data\Constellation($constellation);
                    foreach ($c->getResourceRelations() as $resourceRel) {
                        if ($resourceRel->getResource() !== null && $resourceRel->getResource()->getRepository() != null) {
                            $repo = $resourceRel->getResource()->getRepository();
                            $holdings[$repo->getID()] = array(
                                "name" => $repo->getPreferredNameEntry()->getOriginal()
                            );
                            foreach ($repo->getPlaces() as $place) {
                                if ($place->getGeoTerm() != null) {
                                    $holdings[$repo->getID()]["latitude"] = $place->getGeoTerm()->getLatitude();
                                    $holdings[$repo->getID()]["longitude"] = $place->getGeoTerm()->getLongitude();
                                }
                            }
                        }
                    }
                    // Sort the holding institutions alphabetically
                    usort($holdings, function($a, $b) {
                        return $a["name"] <=> $b["name"];
                    });

                } else {
                    $display->setTemplate("view_page");
                    if (isset($serverResponse["editing_user"]))
                        $editingUser = $serverResponse["editing_user"];

                    if (\snac\Config::$DEBUG_MODE == true) {
                        $display->addDebugData("constellationSource", json_encode($serverResponse["constellation"], JSON_PRETTY_PRINT));
                        $display->addDebugData("serverResponse", json_encode($serverResponse, JSON_PRETTY_PRINT));
                    }

                }
               
                // Check for a redirect
                if ($serverResponse["result"] == "success-notice") {
                    $message = $serverResponse["message"];
                }

                $this->logger->addDebug("Setting constellation data into the page template");

                $display->setData(array_merge(
                    $constellation,
                    array(
                        "preview"=> (isset($input["preview"])) ? true : false,
                        "maybeSameCount"=> (isset($serverResponse["maybesame_count"])) ? $serverResponse["maybesame_count"] : 0,
                        "message" => $message,
                        "holdings" => $holdings,
                        "editingUser" => $editingUser)
                    )
                );
            } else {
                // We have multiple constellations, so redirect to split page
                $this->displaySplitChoicePage($serverResponse, $display);
            }
        } else {
            $this->logger->addDebug("Error page being drawn");
            $this->drawErrorPage($serverResponse, $display);
        }
    }

    /**
     * Display Split Choice Page
     *
     * Loads the display with the split choice page.  This page is used when there are multiple
     * Constellations returned by the server for any given single id/ark.  The user in that case
     * must choose which Constellation to view.
     *
     * @param string[] $serverResponse The server response (associative array) containing multiple constellations
     * @param \snac\client\webui\display\Display $display The display object for page creation
     */
    public function displaySplitChoicePage(&$serverResponse, &$display) {
        $display->setTemplate("split_choice_page");

        // Check for a mesage
        if ($serverResponse["result"] == "success-notice") {
            $message = $serverResponse["message"];
        }

        $this->logger->addDebug("Setting constellation data into the page template");

        $display->setData(
            array(
                "constellations" => $serverResponse["constellation"],
                "message" => $message
            )
        );
    }


    /**
     * Display Detailed View Page
     *
     * Loads the detailed view page for a given constellation input into the display.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\client\webui\display\Display $display The display object for page creation
     */
    public function displayDetailedViewPage(&$input, &$display) {
        $serverResponse = array();
        
        if (isset($input["part"]) && ($input["part"] == "constellationRelations" || 
            $input["part"] == "resourceRelations"))
            $serverResponse = $this->getConstellation($input, $display, false);
        else
            $serverResponse = $this->getConstellation($input, $display, "summary_meta");
        
        if (isset($serverResponse["constellation"])) {
            $editingUser = null;
            if (isset($serverResponse["editing_user"]))
                $editingUser = $serverResponse["editing_user"];

            if (isset($input["part"]))
                $display->setTemplate("detailed_view_tabs/".$input["part"]);
            else
                $display->setTemplate("detailed_view_page");

            $constellation = $serverResponse["constellation"];
            if (\snac\Config::$DEBUG_MODE == true) {
                $display->addDebugData("constellationSource", json_encode($serverResponse["constellation"], JSON_PRETTY_PRINT));
                $display->addDebugData("serverResponse", json_encode($serverResponse, JSON_PRETTY_PRINT));
            }
            
            $this->logger->addDebug("Getting Holding institution information from the resource relations");
            $c = new \snac\data\Constellation($constellation);
            $holdings = array();
            foreach ($c->getResourceRelations() as $resourceRel) {
                if ($resourceRel->getResource() !== null && $resourceRel->getResource()->getRepository() != null) {
                    $repo = $resourceRel->getResource()->getRepository();
                    $holdings[$repo->getID()] = array(
                        "name" => $repo->getPreferredNameEntry()->getOriginal()
                    );
                    foreach ($repo->getPlaces() as $place) {
                        if ($place->getGeoTerm() != null) {
                            $holdings[$repo->getID()]["latitude"] = $place->getGeoTerm()->getLatitude();
                            $holdings[$repo->getID()]["longitude"] = $place->getGeoTerm()->getLongitude();
                        }
                    }
                }
            }
            // Sort the holding institutions alphabetically
            usort($holdings, function($a, $b) {
                return $a["name"] <=> $b["name"];
            });
            
            $this->logger->addDebug("Setting constellation data into the page template");
            $display->setData(array_merge(
                $constellation,
                array("preview"=> (isset($input["preview"])) ? true : false,
                    "maybeSameCount"=> (isset($serverResponse["maybesame_count"])) ? $serverResponse["maybesame_count"] : 0,
                    "holdings" => $holdings,
                    "editingUser" => $editingUser)
            ));
        } else {
            $this->logger->addDebug("Error page being drawn");
            $this->drawErrorPage($serverResponse, $display);
        }
    }
    
    /**
     * Add Not-same Assertion 
     *
     * Add a not-same assertion between the Constellations given by the parameters.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function processNotSameAssertion(&$input) {
        if (isset($input["assertcount"]) && is_numeric($input["assertcount"]) && $input["assertcount"] > 1) {
            $count = $input["assertcount"];
            $icids = array();
            for ($i = 1; $i <= $count; $i++) {
                if (!isset($input["constellationid" .$i])) {
                    $this->logger->addDebug("Error page being drawn");
                    $this->drawErrorPage(["error" => "Could not Make Assertion"], $display);
                }
                array_push($icids, $input["constellationid" . $i]);
            }

            // Ask the server to do the merge
            $query = [
                "constellationids" => $icids
            ];

            if (isset($input["assert"]) && $input["assert"] == "true") {
                $query["command"] = "constellation_assert";
                $query["type"] = "not_same";
                
                if (!isset($input["statement"]) || $input["statement"] == "") {
                   return array("response" => "failure", "error" => "Need statement with assertion"); 
                }
                $query["assertion"] = $input["statement"];

            } else {
                $query["command"] = "constellation_remove_maybesame";
            }

            $this->logger->addDebug("Asking server to make the assertion");
            $serverResponse = $this->connect->query($query);
            $this->logger->addDebug("Received server response", array($serverResponse));
            
            return $serverResponse;

        }   

    }

    /**
     * Add Maybe-same Assertion 
     *
     * Add a maybe-same assertion between the Constellations given by the parameters.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function addMaybeSameAssertion(&$input) {
        if (isset($input["maybesamecount"]) && is_numeric($input["maybesamecount"]) && $input["maybesamecount"] > 1) {
            $count = $input["maybesamecount"];
            $icids = array();
            for ($i = 1; $i <= $count; $i++) {
                if (!isset($input["constellationid" .$i])) {
                    $this->logger->addDebug("Error page being drawn");
                    $this->drawErrorPage(["error" => "Could not Make Assertion"], $display);
                }
                array_push($icids, $input["constellationid" . $i]);
            }

            // Ask the server to do the merge
            $query = [
                "constellationids" => $icids
            ];

            $query["command"] = "constellation_add_maybesame";
            
            if (isset($input["statement"])) {
                $query["assertion"] = $input["statement"];
            }

            $this->logger->addDebug("Asking server to make the maybe-same assertion");
            $serverResponse = $this->connect->query($query);
            $this->logger->addDebug("Received server response", array($serverResponse));
            
            return $serverResponse;
        }
        return "An error occurred";   

    }

    /**
     * Display MaybeSame List Page
     *
     * Fills the display object with the maybe-same list page.  If the user has permission
     * and the server says that the constellations are mergeable, it will also add merge buttons.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\client\webui\display\Display $display The display object for page creation
     */
    public function displayMaybeSameListPage(&$input, &$display) {

        $query = array(
            "command" => "constellation_list_maybesame",
            "constellationid" => $input["constellationid"]
        );
        $this->logger->addDebug("Sending query to the server", $query);
        $serverResponse = $this->connect->query($query);
        $this->logger->addDebug("Received server response", array($serverResponse));
        if (isset($serverResponse["constellation"])) {
            $display->setTemplate("maybesame_list_page");
            $displayData = array(
                "constellation" => $serverResponse["constellation"],
                "mergeable" => $serverResponse["mergeable"]
            );
            if (isset($serverResponse["maybe_same"])) {
                $displayData["maybeSameList"] = $serverResponse["maybe_same"];
            }
            if (\snac\Config::$DEBUG_MODE == true) {
                $display->addDebugData("serverResponse", json_encode($serverResponse, JSON_PRETTY_PRINT));
            }
            $display->setData($displayData);
        } else {
            $this->logger->addDebug("Error page being drawn");
            $this->drawErrorPage($serverResponse, $display);
        }
    }
    
    /**
     * Display Constellation History Compare Page
     *
     * Loads the view page in comparison mode for the two versions of the
     * given constellation.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\client\webui\display\Display $display The display object for page creation
     */
    public function displayHistoryComparePage(&$input, &$display) {
        $query = array();
        if (isset($input["constellationid"]))
            $query["constellationid1"] = $input["constellationid"];
            $query["constellationid2"] = $input["constellationid"];
        if (isset($input["version1"]))
            $query["version1"] = $input["version1"];
        if (isset($input["version2"]))
            $query["version2"] = $input["version2"];
        $query["command"] = "constellation_diff";

        $serverResponse = $this->connect->query($query);

        if (isset($serverResponse["intersection"])) {
            $display->setTemplate("view_page");
            $constellation = $serverResponse["intersection"];

            $this->logger->addDebug("Setting constellation data into the page template");

            $display->setData(array_merge(
                $constellation,
                array(
                    "comparison"=> true,
                    "old" => $serverResponse["constellation1"],
                    "new" => $serverResponse["constellation2"])
                )
            );
        } else {
            $this->logger->addDebug("Error page being drawn");
            $this->drawErrorPage($serverResponse, $display);
        }
    }

    /**
     * Display Constellation History Page
     *
     * Loads the version history page for a given constellation input into the display.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\client\webui\display\Display $display The display object for page creation
     */
    public function displayHistoryPage(&$input, &$display) {
        $query = array();
        if (isset($input["constellationid"]))
            $query["constellationid"] = $input["constellationid"];
        if (isset($input["version"]))
            $query["version"] = $input["version"];
        if (isset($input["arkid"]))
            $query["arkid"] = $input["arkid"];
        $query["command"] = "constellation_history";

        $serverResponse = $this->connect->query($query);

        if (isset($serverResponse["constellation"])) {
            $display->setTemplate("history_page");
            if (\snac\Config::$DEBUG_MODE == true) {
                $display->addDebugData("serverResponse", json_encode($serverResponse, JSON_PRETTY_PRINT));
            }
            $this->logger->addDebug("Setting constellation data into the page template");
            $display->setData($serverResponse);
        } else {
            $this->logger->addDebug("Error page being drawn");
            $this->drawErrorPage($serverResponse, $display);
        }
    }

    /**
     * Process a Merge
     *
     * Takes the merged input data from the merge page, converts the merged Constellation into a
     * Constellation object and asks the server to perform the merge.  If the merge is successful,
     * this method will load the new (merged) Constellation into the detailed view template for
     * display to the user.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\client\webui\display\Display $display The display object for page creation
     */
    public function processMerge(&$input, &$display) {
        // All the information should come VIA post to build the preview
        $mapper = new \snac\client\webui\util\ConstellationPostMapper();
        $mapper->allowTermLookup();
        $mapper->mapAsNewConstellation();

        // Serialize constellation object
        $constellation = $mapper->serializeToConstellation($input);

        if ($constellation != null && isset($input["constellationid1"]) && isset($input["constellationid2"])) {
            // Ask the server to do the merge
            $query = [
                "command" => "constellation_merge",
                "constellationids" => [
                    $input["constellationid1"],
                    $input["constellationid2"]
                ],
                "constellation" => $constellation->toArray()
            ];
            $this->logger->addDebug("Asking server to do the merge");
            $serverResponse = $this->connect->query($query);
            $this->logger->addDebug("Received server response", array($serverResponse));

            if (isset($serverResponse["constellation"])) {
                $display->setTemplate("detailed_view_page");
                if (\snac\Config::$DEBUG_MODE == true) {
                    $display->addDebugData("constellationSource", json_encode($serverResponse["constellation"], JSON_PRETTY_PRINT));
                    $display->addDebugData("serverResponse", json_encode($serverResponse, JSON_PRETTY_PRINT));
                }

                // Since this was just merged, it is currently editable
                $serverResponse["constellation"]["status"] = "editable";

                $this->logger->addDebug("Setting constellation data into the page template");
                $display->setData($serverResponse["constellation"]);
            } else {
                $this->logger->addDebug("Error page being drawn");
                $this->drawErrorPage($serverResponse, $display);
            }
        }
    }

    /**
     * Process Automatic Merge
     *
     * Takes a list of Constellation IDs as input from the user and try to merge.  If the merge is successful,
     * this method will load the new (merged) Constellation into the detailed view template for
     * display to the user.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\client\webui\display\Display $display The display object for page creation
     */
    public function processAutoMerge(&$input, &$display) {

        if (isset($input["mergecount"]) && is_numeric($input["mergecount"]) && $input["mergecount"] > 1) {
            $count = $input["mergecount"];
            $icids = array();
            for ($i = 1; $i <= $count; $i++) {
                if (!isset($input["constellationid" .$i])) {
                    $this->logger->addDebug("Error page being drawn");
                    $this->drawErrorPage(["error" => "Could not auto-merge"], $display);
                }
                array_push($icids, $input["constellationid" . $i]);
            }

            // Ask the server to do the merge
            $query = [
                "command" => "constellation_auto_merge",
                "constellationids" => $icids
            ];
            
            $this->logger->addDebug("Asking server to do the auto merge");
            $serverResponse = $this->connect->query($query);
            $this->logger->addDebug("Received server response", array($serverResponse));

            if (isset($serverResponse["constellation"])) {
                $display->setTemplate("detailed_view_page");
                if (\snac\Config::$DEBUG_MODE == true) {
                    $display->addDebugData("constellationSource", json_encode($serverResponse["constellation"], JSON_PRETTY_PRINT));
                    $display->addDebugData("serverResponse", json_encode($serverResponse, JSON_PRETTY_PRINT));
                }

                // Since this was just merged, it is currently editable
                $serverResponse["constellation"]["status"] = "editable";

                $this->logger->addDebug("Setting constellation data into the page template");
                $display->setData($serverResponse["constellation"]);
            } else {
                $this->logger->addDebug("Error page being drawn");
                $this->drawErrorPage($serverResponse, $display);
            }
        }
    }

    /**
     * Cancel a merge
     *
     * Tries to cancel a merge by asking the server to unlock both of the Constellations
     * originally locked for the merge.  If successful, then returns JSON response to the client.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function cancelMerge(&$input) {
        $request1 = array (
            "constellationid" => $input["constellationid1"],
            "version" => $input["version1"]
        );
        $response1 = $this->unlockConstellation($request1);

        $request2 = array (
            "constellationid" => $input["constellationid2"],
            "version" => $input["version2"]
        );
        $response2 = $this->unlockConstellation($request2);

        $response = array();

        $response["server_debug"] = array();
        $response["server_debug"]["unlock1"] = $response1;
        $response["server_debug"]["unlock2"] = $response2;
        if (isset($response1["error"]))
            $response["error"] = $response1["error"];
        if (isset($response2["error"]))
            $response["error"] = $response2["error"];

        if (!isset($response1["error"]) && !isset($response2["error"])) {
            // successfully unlocked both constellations
            $response["result"] = "success";
        } else {
            $response["result"] = "failure";
        }
        return $response;
    }

    /**
     * Display MaybeSame Diff Page
     *
     * Fills the display object with the maybe-same diff page.  If the user has permission
     * and the server says that the constellations are mergeable, it will also add merge functionality.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\client\webui\display\Display $display The display object for page creation
     * @param boolean $forMerge optional Whether or not this display should include merging, default is false
     */
    public function displayMaybeSameDiffPage(&$input, &$display, $forMerge=false) {

        $command = "constellation_diff";
        if ($forMerge)
            $command = "constellation_diff_merge";

        $query = array(
            "command" => $command,
            "constellationid1" => $input["constellationid1"],
            "constellationid2" => $input["constellationid2"]
        );
        $this->logger->addDebug("Sending query to the server", $query);
        $serverResponse = $this->connect->query($query);
        $this->logger->addDebug("Received server response", array($serverResponse));
        if (isset($serverResponse["intersection"])) {
            if ($forMerge === false || ($forMerge === true && $serverResponse["mergeable"] === true)) {
                // Can only merge if the webUI has requested diff to merge (forMerge) and
                // the server says these two are mergeable
                $merging = $forMerge && $serverResponse["mergeable"];
                $mergeable = $serverResponse["mergeable"];

                $display->setTemplate("maybesame_diff_page");
                $displayData = array(
                    "constellation1" => $serverResponse["constellation1"],
                    "constellation2" => $serverResponse["constellation2"],
                    "intersection" => $serverResponse["intersection"],
                    "mergeable" => $mergeable,
                    "merging" => $merging
                );
                if (isset($serverResponse["assertion"]))
                    $displayData["assertion"] = $serverResponse["assertion"];

                if (\snac\Config::$DEBUG_MODE == true) {
                    $display->addDebugData("serverResponse", json_encode($serverResponse, JSON_PRETTY_PRINT));
                }
                $display->setData($displayData);
            } else {
                // We were able to do the diff, the user wanted to merge, but the server told us we couldn't merge
                if (isset($serverResponse["assertion"])) {
                    // We have a reason why
                    $assert = new \snac\data\Assertion($serverResponse["assertion"]);
                    $message = "<p>Constellations were asserted to be different by <strong>" .
                        $assert->getUser()->getFullName() . "</strong>.  They gave the following reasoning for this assertion:</p>" .
                        $assert->getText();
                    $this->drawErrorPage(["error" => ["type" => "Constellation Merge Error", "message"=> $message]], $display);
                } else {
                    $this->drawErrorPage(["error" => ["type" => "Constellation Merge Error", "message"=> "Could not open both Constellations for editing to perform a merge."]], $display);
                }
            }
        } else {
            $this->logger->addDebug("Error page being drawn - no intersection");
            $this->drawErrorPage($serverResponse, $display);
        }
    }

    /**
     * Start SNAC Session
     *
     * Calls to the server to start a new user's session
     *
     * @return boolean true on success, false otherwise
     */
    public function startSNACSession() {
        $query = array(
                "command" => "start_session"
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
     * @return boolean true on success, false otherwise
     */
    public function endSNACSession() {
        $query = array(
                "command" => "end_session"
        );
        $serverResponse = $this->connect->query($query);

        if (isset($serverResponse["result"]) && $serverResponse["result"] == "success")
            return true;
        return false;
    }

    /**
     * Display Static Page
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\client\webui\display\Display $display The display object for page creation
     */
    public function displayStaticPage(&$input, &$display) {
        if (!isset($input["page"]) || !$display->setStaticDisplay($input["page"])) {
            $error = array("error" => array(
                "type" => "Not Found",
                "message" => "The resource you were looking for does not exist."
            ));
            $this->drawErrorPage($error, $display);
            return false;
        }
        return true;
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
        $mapper->allowTermLookup();
        $mapper->mapAsNewConstellation();


        // Get the constellation object
        $constellation = $mapper->serializeToConstellation($input);

        if ($constellation != null) {
            $display->setTemplate("detailed_view_page");
            if (isset($input["view"]) && $input["view"] == "hrt") {
                $display->setTemplate("view_page");
            }

            if (\snac\Config::$DEBUG_MODE === true) {
                $display->addDebugData("constellationSource", $constellation->toJSON());
            }
            $this->logger->addDebug("Setting constellation data into the page template");
            $display->setData(array_merge($constellation->toArray(), array("preview" => true)));
        }
    }

    /**
     * Display Grid Page
     *
     * Fills the display object with the explore grid.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\client\webui\display\Display $display The display object for page creation
     */
    public function displayGridPage(&$input, &$display) {
        $display->setTemplate("grid_page");

        $otherInfo = array();

        if (isset($input["redirected"])) {
            $otherInfo["message"] = "redirected";
        }

        $randomQuery = $this->connect->query(array(
                "command"=>"random_constellations",
                "images" => true
            ));

        if (isset($randomQuery["constellation"]) && $randomQuery["constellation"] != null) {
            $randomConstellations = $randomQuery["constellation"];
        }

        $display->setData(array_merge($randomQuery, $otherInfo));
    }


    /**
     * Display Dashboard Page
     *
     * Fills the display object with the dashboard for the given user.
     *
     * @param \snac\client\webui\display\Display $display The display object for page creation
     */
    public function displayDashboardPage(&$display) {
        $display->setTemplate("dashboard");
        // Ask the server for a list of records to edit
        $ask = array("command"=>"user_information"
        );
        $this->logger->addDebug("Sending query to the server", $ask);
        $serverResponse = $this->connect->query($ask);
        $this->logger->addDebug("Received server response", array($serverResponse));
        $this->logger->addDebug("Setting dashboard data into the page template");

        $needsReview = $this->connect->query(array(
            "command"=>"list_constellations",
            "status"=>"needs review"
        ));
        if (isset($needsReview["results"]))
            $serverResponse["needs_review"] = $needsReview["results"];


        $recentQuery = $this->connect->query(array(
                "command"=>"recently_published"
            ));

        if (isset($recentQuery["constellation"]) && $recentQuery["constellation"] != null) {
            $recentConstellations = $recentQuery["constellation"];

            $recents = array();
            foreach ($recentConstellations as $constellationArray) {
                $constellation = new \snac\data\Constellation($constellationArray);
                array_push($recents, array(
                        "id"=>$constellation->getID(),
                        "nameEntry"=>$constellation->getPreferredNameEntry()->getOriginal()));
            }
            $serverResponse["recents"] = $recents;
        }

        $display->setData($serverResponse);
    }

    /**
     * Handle download tasks
     *
     * This method handles the downloading of content in any type. Download tasks include serializing a
     * constellation as EAC-CPF XML, and downloading the XML (a string) as a file.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\client\webui\display\Display $display The display object for page creation
     * @param string[] $headers Response headers for the return
     * @return string The response to the client (The content of the file)
     */
    public function handleDownload(&$input, &$display, &$headers) {
        if (!isset($input["type"])) {
            return $this->drawErrorPage("Content Type not specified", $display);
        }

        $query = array();
        if (isset($input["constellationid"]))
            $query["constellationid"] = $input["constellationid"];
        if (isset($input["version"]))
            $query["version"] = $input["version"];
        if (isset($input["arkid"]))
            $query["arkid"] = $input["arkid"];
        $query["type"] = $input["type"];
        $query["command"] = "download_constellation";

        $this->logger->addDebug("Sending query to the server", $query);
        $serverResponse = $this->connect->query($query);
        $this->logger->addDebug("Received server response");
        /*
            Ask server to "download_constellation" with the type parameter and constellationid, arkid, etc.

            $input["type"]

            Server will give the following response:

            $response["file"] = array();
            $response["file"]["mime-type"] = "text/json";
            $response["file"]["filename"] = $this->arkToFilename($constellation->getArkID()).".json";
            $response["file"]["content"] = base64_encode(json_encode($constellation, JSON_PRETTY_PRINT));
        */


        if (isset($serverResponse["file"])) {
            array_push($headers, "Content-Type: " . $serverResponse["file"]["mime-type"]);
            array_push($headers, 'Content-Disposition: inline; filename="'.$serverResponse["file"]["filename"].'"');
            return base64_decode($serverResponse["file"]["content"]);
        } else {
            $this->drawErrorPage("Download error occurred", $display);
        }

        return null;
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
     * Display Message Center
     *
     * Loads the display with the message center (message list) template.  It also asks
     * the server for the list of unread messages for the current user.
     *
     * @param \snac\client\webui\display\Display $display The display object for page creation
     */
    public function displayMessageListPage(&$display) {
        $ask = array("command"=>"user_messages");
        $serverResponse = $this->connect->query($ask);
        if (!isset($serverResponse["result"]) || $serverResponse["result"] != 'success')
            return $this->drawErrorPage($serverResponse, $display);

        $display->setData($serverResponse);
        $display->setTemplate("message_list");
    }

    /**
     * Read Message
     *
     * Asks the server to read the message with given ID.  The user must have permission
     * to read the message or the server will return an error.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @return string The response to the client 
     */
    public function readMessage(&$input) {
        $ask = array("command"=>"read_message",
                    "messageid"=>$input["messageid"]);
        $serverResponse = $this->connect->query($ask);

        return $serverResponse;
    }

    /**
     * Delete Message
     * 
     * Asks the server to delete the message given as input by ID.  The user must have
     * permission to delete the message or the server will return an error.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @return string The response to the client 
     */
    public function deleteMessage(&$input) {
        $ask = array("command"=>"delete_message",
                    "messageid"=>$input["messageid"]);
        $serverResponse = $this->connect->query($ask);

        return $serverResponse;
    }

    /**
     * Send a Feedback Message
     *
     * Sends the feedback given in the input to the server's feedback mechanism.  If no
     * user information is given, it will send the user's IP address for verification.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @return string The response to the client
     */
    public function sendFeedbackMessage(&$input) {
        $response = array();
        if (isset($input["subject"]) && isset($input["body"]) && isset($input["token"])) {

            // confirm the token first
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, array(
                "secret" => "6LdjGCwUAAAAAMRzQh-sVk9DF7ST9oVBGLDIogHv",
                "response" => $input["token"]
            ));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $verifyResponse = curl_exec($ch);
            curl_close($ch);
            $verified = array ("success" => false);

            if ($verifyResponse != null) {
                $verified = json_decode($verifyResponse, true);
            }

            if (isset($verified["success"]) && $verified["success"] === true) {
                // split out the users to send to
                $message = new \snac\data\Message();
                $message->setSubject($input["subject"]);
                $message->setBody($input["body"]);
                if (isset($this->user) && $this->user !== null) {
                    $message->setFromUser($this->user);
                } else {
                    // set this message from the IP address:
                    $message->setFromString("anonymous_user@".$_SERVER['REMOTE_ADDR']);
                }

                if (isset($input["screenshot"])) {
                    $message->setAttachmentContent($input["screenshot"]);
                    $message->setAttachmentFilename("screenshot.png"); 
                }

                $ask = array("command"=>"send_feedback",
                            "message"=>$message->toArray());
                $serverResponse = $this->connect->query($ask);

                if (!isset($serverResponse["result"]) || $serverResponse["result"] != 'success') {
                    $response["result"] = "error";
                }
                $response["result"] = "success";
            } else {
                $response["result"] = "error";
                $this->logger->addWarning("Failed feedback attempt", array_merge($input, $verified));
            }
        } else {
            $response["result"] = "error";
        }
        return $response;
    }


    /**
     * Send Message
     *
     * Asks the server to send the message given in the input.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @return string The response to the client 
     */
    public function sendMessage(&$input) {
        $response = array();
        if (isset($input["to_user"]) && isset($input["subject"]) && isset($input["body"])) {
            // split out the users to send to
            $toUsers = explode(",", $input["to_user"]);
            $message = new \snac\data\Message();
            $message->setSubject($input["subject"]);
            $message->setBody($input["body"]);
            $message->setFromUser($this->user);

            $errors = array();

            foreach ($toUsers as $toUserID) {
                $toUser = new \snac\data\User();
                $toUser->setUserID(trim($toUserID));
                $message->setToUser($toUser);

                $ask = array("command"=>"send_message",
                            "message"=>$message->toArray());
                $serverResponse = $this->connect->query($ask);

                if (!isset($serverResponse["result"]) || $serverResponse["result"] != 'success') {
                    array_push($errors, trim($toUserName));
                }
            }

            if (!empty($errors)) {
                $response["message"] = "Could not send to: " . implode(", ", $errors) . ".";
            }

            if (count($errors) < count($toUsers)) {
                $response["result"] = "success";
            }
        } else {
            $response["result"] = "error";
        }

        return $response;
    }

    /**
     * Handle Administrative tasks
     *
     * Fills the display object with the requested admin page for the given user.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\client\webui\display\Display $display The display object for page creation
     * @param \snac\data\User $user The current user object
     */
    public function handleAdministrator(&$input, &$display, &$user) {

        if (!isset($input["subcommand"])) {
            $input["subcommand"] = "dashboard";
        }

        switch ($input["subcommand"]) {
            case "add_user":
                // Ask the server for all the Roles
                $ask = array("command"=>"admin_roles"
                );
                $serverResponse = $this->connect->query($ask);
                if (!isset($serverResponse["result"]) || $serverResponse["result"] != 'success')
                    return $this->drawErrorPage($serverResponse, $display);

                $display->setData(array(
                    "title"=> "Add New User",
                    "roles"=> $serverResponse["roles"]
                ));
                $display->setTemplate("admin_edit_user");
                break;
            case "edit_user":
                if (!isset($input["userid"])) {
                    return $this->drawErrorPage("Missing UserID", $display);
                }
                $userEdit = new \snac\data\User();
                $userEdit->setUserID($input["userid"]);
                $ask = array("command"=>"edit_user",
                    "user_edit" => $userEdit->toArray()
                );
                $serverResponse = $this->connect->query($ask);
                if (!isset($serverResponse["result"]) || $serverResponse["result"] != 'success')
                    return $this->drawErrorPage($serverResponse, $display);
                $userEdit = $serverResponse["user"];
                $userGroups = $serverResponse["groups"];

                // Ask the server for all the Roles
                $ask = array("command"=>"admin_roles"
                );
                $serverResponse = $this->connect->query($ask);
                if (!isset($serverResponse["result"]) || $serverResponse["result"] != 'success')
                    return $this->drawErrorPage($serverResponse, $display);

                $display->setData(array(
                    "title"=> "Edit User",
                    "user"=>$userEdit,
                    "roles" => $serverResponse["roles"],
                    "groups" => $userGroups
                ));
                $display->setTemplate("admin_edit_user");
                break;
            case "activity_user":
                if (!isset($input["userid"])) {
                    return $this->drawErrorPage("Missing UserID", $display);
                }
                $userEdit = new \snac\data\User();
                $userEdit->setUserID($input["userid"]);
                $ask = array("command"=>"edit_user",
                    "user_edit" => $userEdit->toArray()
                );
                $serverResponse = $this->connect->query($ask);
                if (!isset($serverResponse["result"]) || $serverResponse["result"] != 'success')
                    return $this->drawErrorPage($serverResponse, $display);
                $userEdit = $serverResponse["user"];
                $userGroups = $serverResponse["groups"];

                $serverResponse["title"] = "User Activity";
                $display->setData($serverResponse);
                $display->setTemplate("admin_user_activity");
                break;
            case "edit_user_post":
                return $this->saveProfile($input, $user);
                break;
            case "users":
                $ask = array("command"=>"list_users"
                );
                $serverResponse = $this->connect->query($ask);
                if (!isset($serverResponse["result"]) || $serverResponse["result"] != 'success')
                    return $this->drawErrorPage($serverResponse, $display);

                $display->setData(array("users" => $serverResponse["users"]));
                $display->setTemplate("admin_users");
                break;
            case "user_list":
                $ask = array("command"=>"list_users",
                    "filter" => "active"
                );
                return $this->connect->query($ask);
                break;
            case "add_group":
                $display->setData(array("title"=> "Add New Group"));
                $display->setTemplate("admin_edit_group");
                break;
            case "edit_group":
                if (!isset($input["groupid"])) {
                    return $this->drawErrorPage("Missing GroupID", $display);
                }
                $groupEdit = new \snac\data\Group();
                $groupEdit->setID($input["groupid"]);
                $ask = array("command"=>"edit_group",
                    "group" => $groupEdit->toArray()
                );
                $serverResponse = $this->connect->query($ask);
                if (!isset($serverResponse["result"]) || $serverResponse["result"] != 'success')
                    return $this->drawErrorPage($serverResponse, $display);

                $display->setData(array(
                    "title"=> "Edit Group",
                    "group"=>$serverResponse["group"],
                    "users"=>$serverResponse["users"]));
                $display->setTemplate("admin_edit_group");
                break;
            case "edit_group_post":
                return $this->saveGroup($input);
                break;
            case "group_list":
                $ask = array("command"=>"admin_groups"
                );
                return $this->connect->query($ask);
                break;
            case "groups":
                $ask = array("command"=>"admin_groups"
                );
                $serverResponse = $this->connect->query($ask);
                if (!isset($serverResponse["result"]) || $serverResponse["result"] != 'success')
                    return $this->drawErrorPage($serverResponse, $display);

                $display->setData(array("groups" => $serverResponse["groups"]));
                $display->setTemplate("admin_groups");
                break;
            case "roles":
                $ask = array("command"=>"admin_roles"
                );
                $serverResponse = $this->connect->query($ask);
                if (!isset($serverResponse["result"]) || $serverResponse["result"] != 'success')
                    return $this->drawErrorPage($serverResponse, $display);
                $roles = array();
                foreach ($serverResponse["roles"] as $role) {
                    if (isset($role["privilegeList"]))
                        array_push($roles, $role);
                }
                usort($roles, function($a, $b) {
                    return count($a["privilegeList"]) <=> count($b["privilegeList"]);
                });

                $display->setData(array("roles" => $roles));
                $display->setTemplate("admin_roles");
                break;
            case "dashboard":
                if (isset($this->permissions["ViewAdminDashboard"]) && $this->permissions["ViewAdminDashboard"]) {
                    $display->setTemplate("admin_dashboard");
                } else {
                    $this->displayPermissionDeniedPage("Admin Dashboard", $display);
                }
                break;

            case "unlock_constellation":
                return $this->unlockConstellation($input);
                break;

            case "reassign_constellation":
                return $this->reassignConstellation($input);
                break;


            case "report_general":
                $ask = array(
                    "command"=>"report",
                    "type" => "general"
                );
                $serverResponse = $this->connect->query($ask);
                if (!isset($serverResponse["result"]) || $serverResponse["result"] != 'success')
                    return $this->drawErrorPage($serverResponse, $display);
                $display->setData($serverResponse);
                $display->setTemplate("report_general_page");
                break;

            case "report_holdings":
                $ask = array(
                    "command"=>"report",
                    "type" => "holdings"
                );
                $serverResponse = $this->connect->query($ask);
                if (!isset($serverResponse["result"]) || $serverResponse["result"] != 'success')
                    return $this->drawErrorPage($serverResponse, $display);
                $display->setData($serverResponse);
                $display->setTemplate("report_list_page");
                break;

            default:
                $this->displayPermissionDeniedPage("Administrator", $display);
        }

        return false;
    }

    /**
     * Handle Vocabulary Administrative tasks
     *
     * Fills the display object with the requested vocab admin page for the given user.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\client\webui\display\Display $display The display object for page creation
     * @param \snac\data\User $user The current user object
     */
    public function handleVocabAdministrator(&$input, &$display, &$user) {

        if (!isset($input["subcommand"])) {
            $input["subcommand"] = "dashboard";
        }

        switch ($input["subcommand"]) {
            case "search":
                if (isset($this->permissions["ViewAdminDashboard"]) && $this->permissions["ViewAdminDashboard"]) {
                    $display->setTemplate("vocab_search");
                } else {
                    $this->displayPermissionDeniedPage("Vocabulary Search", $display);
                }
                break;
            case "geosearch":
                if (isset($this->permissions["ViewAdminDashboard"]) && $this->permissions["ViewAdminDashboard"]) {
                    $display->setTemplate("vocab_geosearch");
                } else {
                    $this->displayPermissionDeniedPage("Vocabulary Search", $display);
                }
                break;
            case "add_term":
                $display->setData(array(
                    "title"=> "Add New Vocabulary Term"
                ));
                $display->setTemplate("vocab_edit_term");
                break;
            case "add_term_post":
                return $this->saveVocabularyTerm($input, $user);
                break;
            case "add_geoterm":
                $display->setData(array(
                    "title"=> "Add New Geopgraphic Vocabulary Term"
                ));
                $display->setTemplate("vocab_edit_geoterm");
                break;
            case "add_geoterm_post":
                // maybe reuse the same save function?
                return $this->saveVocabularyTerm($input, $user);
                break;
            case "dashboard":
                if (isset($this->permissions["ViewAdminDashboard"]) && $this->permissions["ViewAdminDashboard"]) {
                    $display->setTemplate("vocab_dashboard");
                } else {
                    $this->displayPermissionDeniedPage("Vocabulary Dashboard", $display);
                }
                break;
            default:
                $this->displayPermissionDeniedPage("Vocabulary Administrator", $display);
        }

        return false;
    }



    /**
    * Display the Permission Denied Page
    *
    * Helper function to draw the permission denied page.
    *
    * @param  string $command The resource that the user was trying to access
    * @param  \snac\client\webui\display\Display $display  The display object from the WebUI
    * @return boolean False, since an error occurred to get here
    */
    public function displayPermissionDeniedPage($command, &$display) {
        $display->setTemplate("permission_denied");
        $display->setData(array("command" => $command));
        return false;
    }


    /**
    * Display the Concurrent Edit Error Page
    *
    * Helper function to draw the concurrent edit error page.
    *
    * @param  string $command The resource that the user was trying to access
    * @param  \snac\client\webui\display\Display $display  The display object from the WebUI
    * @return boolean False, since an error occurred to get here
    */
    public function displayConcurrentEditErrorPage($command, &$display) {
        $display->setTemplate("concurrent_edit_error");
        $display->setData(array("command" => $command));
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
    public function drawErrorPage($serverResponse, &$display) {
        $this->logger->addDebug("Drawing Error page", array($serverResponse));
        if (is_array($serverResponse) && isset($serverResponse["error"]) && isset($serverResponse["error"]["type"])) {
            if ($serverResponse["error"]["type"] == "Permission Error") {
                return $this->displayPermissionDeniedPage(null, $display);
            } else if ($serverResponse["error"]["type"] == "Concurrent Edit Error") {
                return $this->displayConcurrentEditErrorPage(null, $display);
            }
            $display->setTemplate("error_page");
            $display->setData($serverResponse["error"]);
        } else if (is_array($serverResponse)) {
            $display->setTemplate("error_page");
            $display->setData(array("type" => "System Error", "message" => print_r($serverResponse, true), "display" => "pre"));
        } else {
            $this->logger->addDebug("Drawing the text version of the error page");
            $display->setTemplate("error_page");
            $display->setData(array("type" => "System Error", "message" => $serverResponse, "display" => "pre"));
        }
        return false;
    }

    /**
     * Display API Info Page
     *
     * Fills the display with the API information page for the given user.
     *
     * @param \snac\client\webui\display\Display $display The display object for page creation
     * @param \snac\data\User $user The current user object
     */
    public function displayAPIInfoPage(&$display, &$user) {
        $display->setTemplate("api_info_page");
        $smallUser = new \snac\data\User();
        $smallUser->setUserID($user->getUserID());
        $smallUser->setUserName($user->getUserName());
        $smallUser->setFullName($user->getFullName());
        $smallUser->setToken($user->getToken());
        $display->setData([
            "restURL" => \snac\Config::$REST_URL,
            "user" => json_encode($smallUser->toArray(), JSON_PRETTY_PRINT)
        ]);
    }

    /**
     * Display API Help Page
     *
     * Fills the display with the API help information by reading the REST API's command
     * documentation.
     *
     * @param \snac\client\webui\display\Display $display The display object for page creation
     */
    public function displayAPIHelpPage(&$display) {
        $commands = json_decode(file_get_contents(\snac\Config::$REST_COMMAND_FILE), true);
        $display->setTemplate("api_help_page");
        $display->setData([
            "commands" => $commands
        ]);
    }
    
    /**
     * Display Contact Us Page
     *
     * @param \snac\client\webui\display\Display $display The display object for page creation
     */
    public function displayContactPage(&$display) {
        $display->setTemplate("contact_page");
    }

    /**
     * Display Profile Page
     *
     * Fills the display with the profile page for the given user.
     *
     * @param \snac\client\webui\display\Display $display The display object for page creation
     */
    public function displayProfilePage(&$display) {
        $display->setTemplate("profile_page");
        // Ask the server for a list of records to edit
        $ask = array("command"=>"user_information"
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
        if (!isset($response["constellation"])) {
            return $this->drawErrorPage($response, $display);
        }
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
        $groups = null;
        // Not editing the current user
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

            // If not editing the current user, then we can update their groups
            $groups = array();
            foreach ($input as $key => $value) {
                if (strstr($key, "groupid_")) {
                    $groupAdd = new \snac\data\Group();
                    $groupAdd->setID($value);
                    array_push($groups, $groupAdd->toArray());
                }
            }


        } else {
            $tmpUser = new \snac\data\User($user->toArray());
        }

        $tmpUser->setFirstName($input["firstName"]);
        $tmpUser->setLastName($input["lastName"]);
        $tmpUser->setWorkPhone($input["workPhone"]);
        $tmpUser->setWorkEmail($input["workEmail"]);
        $tmpUser->setFullName($input["fullName"]);

        foreach ($input as $key => $value) {
            if (substr($key, 0, 5) == "role_") {
                $role = new \snac\data\Role();
                $role->setID($value);
                $tmpUser->addRole($role);
            }
        }

        $this->logger->addDebug("Updated the User Object", $tmpUser->toArray());

        // Build a data structure to send to the server
        $request = array("command"=>"update_user");

        // Send the query to the server
        $request["user_update"] = $tmpUser->toArray();

        // Send the groups if we're doing an update
        if ($groups != null)
            $request["groups_update"] = $groups;

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
     * Save Group Information
     *
     * Asks the server to update a group's information.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function saveGroup(&$input) {

        $group = new \snac\data\Group();
        if (isset($input["groupid"]) && $input["groupid"] != "")
            $group->setID($input["groupid"]);
        $group->setLabel($input["groupName"]);
        $group->setDescription($input["groupDescription"]);

        // Create a list of shadow user objects to put in this group
        $users = array();
        foreach ($input as $key => $value) {
            if (strstr($key, "userid_")) {
                $userAdd = new \snac\data\User();
                $userAdd->setUserID($value);
                array_push($users, $userAdd->toArray());
            }
        }

        $this->logger->addDebug("Updated the Group Object", $group->toArray());

        // Build a data structure to send to the server
        $request = array("command"=>"update_group");

        // Send the query to the server
        $request["group_update"] = $group->toArray();
        $request["users_update"] = $users;
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
            if (isset($serverResponse["group_update"])) {
                $response["group_update"] = $serverResponse["group_update"];
            }
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
    * @return string[] The web ui's response to the client (array ready for json_encode)
    */
    public function reconcilePieces(&$input) {
        $mapper = new \snac\client\webui\util\ConstellationPostMapper();

        // Get the constellation object
        $constellation = $mapper->serializeToConstellation($input);

        $this->logger->addDebug("reconciling constellation", $constellation->toArray());

        // Build a data structure to send to the server
        $request = array("command"=>"reconcile");

        // Send the query to the server
        $request["constellation"] = $constellation->toArray();
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
     * Save Resource
     *
     * Maps the resoource given on input to a Resource object, passes that to the server with an
     * update_resource call.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function saveResource(&$input) {
        $mapper = new \snac\client\webui\util\ResourcePostMapper();

        // Get the resource object
        $resource = $mapper->serializeToResource($input);

        $this->logger->addDebug("writing resource", $resource->toArray());

        // Build a data structure to send to the server
        $request = array("command"=>"update_resource");

        // Send the query to the server
        $request["resource"] = $resource->toArray();
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
            if (isset($serverResponse["resource"])) {
                $this->logger->addDebug("server's response written resource", $serverResponse["resource"]);
                $resource = new \snac\data\Resource($serverResponse["resource"]);

                $response["resource"] = $resource->toArray();
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
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function saveConstellation(&$input) {
        $mapper = new \snac\client\webui\util\ConstellationPostMapper();

        // Get the constellation object
        $constellation = $mapper->serializeToConstellation($input);

        $this->logger->addDebug("writing constellation", $constellation->toArray());

        // Build a data structure to send to the server
        $request = array("command"=>"update_constellation");

        // Send the query to the server
        $request["constellation"] = $constellation->toArray();
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
                    $this->logger->addDebug("Requires the following UI updates", array($response["updates"]));
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
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function saveAndPublishConstellation(&$input) {

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
     * Save and Send Constellation to Editor
     *
     * Maps the constellation given on input to a Constellation object, passes that to the server with an
     * update_constellation call.  If successful, it then maps any updates (new ids or version numbers) to the
     * Constellation object and web components from input, and returns the web ui's response (the list of
     * updates that must be made to the web ui GUI).
     *
     * After saving, it also calls to the server to have the constellation sent to an editor, if the write was successful.
     *
     *
     * @param string[] $input Post/Get inputs from the webui
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function saveAndSendConstellation(&$input) {

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
                $request["command"] = "reassign_constellation";
                $request["constellation"] = $serverResponse["constellation"];

                // Add editor if we have it
                if (isset($input["editor"]) && $input["editor"] != "") {
                    $editor = new \snac\data\User();
                    $editor->setUserName($input["editor"]);
                    $request["to_user"] = $editor->toArray();
                    $this->logger->addDebug("Sending Constellation to ".$input["editor"], $editor->toArray());
                    
                    $serverResponse = $this->connect->query($request);
                    $response["server_debug"]["send"] = $serverResponse;
                    if (isset($serverResponse["result"]))
                        $response["result"] = $serverResponse["result"];
                    if (isset($serverResponse["error"]))
                        $response["error"] = $serverResponse["error"];
                } else {
                    $serverResponse = array( "result" => "failure", "error" => "No editor to send constellation");
                    $response["server_debug"]["send"] = $serverResponse;
                    if (isset($serverResponse["result"]))
                        $response["result"] = $serverResponse["result"];
                    if (isset($serverResponse["error"]))
                        $response["error"] = $serverResponse["error"];
                }

            }
        }

        return $response;
    }
    /**
     * Save and Send Constellation For Review
     *
     * Maps the constellation given on input to a Constellation object, passes that to the server with an
     * update_constellation call.  If successful, it then maps any updates (new ids or version numbers) to the
     * Constellation object and web components from input, and returns the web ui's response (the list of
     * updates that must be made to the web ui GUI).
     *
     * After saving, it also calls to the server to have the constellation sent for review, if the write was successful.
     *
     *
     * @param string[] $input Post/Get inputs from the webui
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function saveAndSendForReviewConstellation(&$input) {

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
                $request["command"] = "review_constellation";
                $request["constellation"] = $serverResponse["constellation"];

                // Add reviewer if we have it
                if (isset($input["reviewer"]) && $input["reviewer"] != "") {
                    $reviewer = new \snac\data\User();
                    $reviewer->setUserID($input["reviewer"]);
                    $request["reviewer"] = $reviewer->toArray();
                    $this->logger->addDebug("Sending for review to ".$input["reviewer"], $reviewer->toArray());
                }

                $serverResponse = $this->connect->query($request);
                $response["server_debug"]["review"] = $serverResponse;
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
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function saveAndUnlockConstellation(&$input) {

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
     * Reassign Constellation
     *
     * Asks the server to reassign the input's constellation to a different user
     *
     * @param string[] $input Post/Get inputs from the webui
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function reassignConstellation(&$input) {

        $constellation = null;
        if (isset($input["constellationid"]) && isset($input["version"])) {
            $constellation = new \snac\data\Constellation();
            $constellation->setID($input["constellationid"]);
            $constellation->setVersion($input["version"]);
        } else {
            return array( "result" => "failure", "error" => "No constellation or version number");
        }

        $toUser = null;
        if (isset($input["userid"])) {
            $toUser = new \snac\data\User();
            $toUser->setUserID($input["userid"]);
        } else {
            return array( "result" => "failure", "error" => "No user id given");
        }

        $this->logger->addDebug("reassigning constellation", $constellation->toArray());
        $this->logger->addDebug("reassigning to user", $toUser->toArray());

        // Build a data structure to send to the server
        $request = array (
            "command" => "reassign_constellation",
            "constellation" => $constellation->toArray(),
            "to_user" => $toUser->toArray()
        );

        // Send the query to the server
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
     * Unlock Constellation
     *
     * Asks the server to drop the input's constellation lock level from "currently editing" down to
     * "locked editing."
     *
     * @param string[] $input Post/Get inputs from the webui
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function unlockConstellation(&$input) {

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
     * Checkout Constellation
     *
     * Requests the server to check out the given constellation to the user.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function checkoutConstellation(&$input) {
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

        $this->logger->addDebug("checking out constellation", $constellation->toArray());

        // Build a data structure to send to the server
        $request = array (
                "command" => "checkout_constellation"
        );

        // Send the query to the server
        $request["constellation"] = $constellation->toArray();
        $serverResponse = $this->connect->query($request);

        $response = $serverResponse;
        return $response;
    }

    /**
     * Publish Constellation
     *
     * Requests the server to publish the given constellation.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function publishConstellation(&$input) {
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
     * Send Constellation to User
     *
     * Requests the server to send the given constellation to a specific user.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function sendConstellation(&$input) {
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



        $this->logger->addDebug("sending constellation", $constellation->toArray());

        // Build a data structure to send to the server
        $request = array (
                "command" => "reassign_constellation"
        );
        
        // Add reviewer if we have it
        if (isset($input["editor"]) && $input["editor"] != "") {
            $editor = new \snac\data\User();
            $editor->setUserName($input["editor"]);
            $request["to_user"] = $editor->toArray();
            $this->logger->addDebug("Sending Constellation to ".$input["editor"], $editor->toArray());
        } else {
            return array( "result" => "failure", "error" => "No editor to send constellation");
        }

        // Add a message if we have it
        if (isset($input['savemessage'])) {
            $request["message"] = $input["savemessage"];
        }

        // Send the query to the server
        $request["constellation"] = $constellation->toArray();
        $serverResponse = $this->connect->query($request);

        $response = array ();
        $response["server_debug"] = array ();
        $response["server_debug"]["send"] = $serverResponse;
        if (isset($serverResponse["result"]))
            $response["result"] = $serverResponse["result"];
        if (isset($serverResponse["error"]))
            $response["error"] = $serverResponse["error"];

        return $response;
    }

    /**
     * Send Constellation for Review
     *
     * Requests the server to send the given constellation for review.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function sendForReviewConstellation(&$input) {
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



        $this->logger->addDebug("sending constellation for review", $constellation->toArray());

        // Build a data structure to send to the server
        $request = array (
                "command" => "review_constellation"
        );
        
        // Add reviewer if we have it
        if (isset($input["reviewer"]) && $input["reviewer"] != "") {
            $reviewer = new \snac\data\User();
            $reviewer->setUserID($input["reviewer"]);
            $request["reviewer"] = $reviewer->toArray();
            $this->logger->addDebug("Sending for review to ".$input["reviewer"], $reviewer->toArray());
        }

        // Add a message if we have it
        if (isset($input['savemessage'])) {
            $request["message"] = $input["savemessage"];
        }

        // Send the query to the server
        $request["constellation"] = $constellation->toArray();
        $serverResponse = $this->connect->query($request);

        $response = array ();
        $response["server_debug"] = array ();
        $response["server_debug"]["review"] = $serverResponse;
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
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function deleteConstellation(&$input) {
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
     * Perform a name search on the terms given on the input by requesting the results from the server and
     * then returns the JSON-ready associative array of results.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param boolean $autocomplete optional Whether to do a search using autocomplete or not (default false)
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function performNameSearch(&$input, $autocomplete=false) {
        if (!isset($input["term"])) {
            return array ("total" => 0, "results" => array());
        }

        if (!isset($input["entity_type"]))
            $input["entity_type"] = "";

        $query = array(
            "command" => "search",
            "term" => $input["term"],
            "entity_type" => $input["entity_type"],
            "start" => isset($input["start"]) ? $input["start"] : 0,
            "count" => isset($input["count"]) ? $input["count"] : 10
        );

        if ($autocomplete) {
            $query["search_type"] = "autocomplete";
        } else if (isset($input["search_type"]) && $input["search_type"] === "advanced") {
            $query["search_type"] = "advanced";
        }

        // Query the server for the elastic search results
        $serverResponse = $this->connect->query($query);

        return $serverResponse;

    }

    /**
     * Query server for relations
     *
     * Asks the server for the relations (in- and out-edges) for the given id or ark
     * in the user input.  Returns the Server response directly, used by Javascript
     * on the client.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function performRelationsQuery(&$input) {
        $query = array();
        if (isset($input["constellationid"]))
            $query["constellationid"] = $input["constellationid"];
        if (isset($input["version"]))
            $query["version"] = $input["version"];
        if (isset($input["arkid"]))
            $query["arkid"] = $input["arkid"];
        $query["command"] = "constellation_read_relations";

        $this->logger->addDebug("Sending query to the server", $query);
        $serverResponse = $this->connect->query($query);
        $this->logger->addDebug("Received server response");
        return $serverResponse;

    }

    /**
     * Perform Resource Search
     *
     * Requests the server to perform a search of resource URLs to display the results.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function performResourceSearch(&$input) {
        if (!isset($input["term"])) {
            return array ("total" => 0, "results" => array());
        }

        // Query the server for the elastic search results
        $serverResponse = $this->connect->query(array(
            "command" => "resource_search",
            "term" => $input["term"]
        ));

        return $serverResponse;

    }

    /**
     * Read Vocabulary Term
     *
     * Asks the server for the controlled vocabulary term information.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @return string[] The server's response with the vocabulary for the given term id
     */
    public function readVocabulary(&$input) {
        if (!isset($input["type"]) || !isset($input["id"])) {
            return array ("result" => "failure");
        }

        $serverResponse = $this->connect->query(array(
            "command" => "read_vocabulary",
            "type" => $input["type"],
            "term_id" => $input["id"]
        ));

        return $serverResponse;
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
                                    $source->toArray());
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
                if (isset($input["count"]))
                    $request["count"] = $input["count"];

                // Send the query to the server
                $serverResponse = $this->connect->query($request);

                if (!isset($serverResponse["results"]))
                    return $serverResponse;

                if (isset($input["format"]) && $input["format"] == "term") {
                    // keep the results as normal Term elements
                } else {
                    $results = $serverResponse["results"];
                    $serverResponse["results"] = array();

                    foreach ($results as $k => $v) {
                        $serverResponse["results"][$k]["id"] = $v["id"];

                        if (isset($v["name"])) {
                            // This is a geoplace term
                            $addendum = "";
                            if ($v["administrationCode"] != null && $v["countryCode"] != null) {
                                $addendum = " (".$v["administrationCode"] . ", " . $v["countryCode"].")";
                            } else if ($v["administrationCode"] != null) {
                                $addendum = "(".$v["administrationCode"].")";
                            } else if ($v["countryCode"] != null) {
                                $addendum = "(".$v["countryCode"].")";
                            }
                            $serverResponse["results"][$k]["value"] = $v["name"] . $addendum;
                        } else {
                            // This is a controlled vocab term
                            $serverResponse["results"][$k]["value"] = $v["term"];
                        }

                        $serverResponse["results"][$k]["text"] = $serverResponse["results"][$k]["value"];
                    }
                }

                $this->logger->addDebug("Sending response back to client", $serverResponse);
                    // Send the response back to the web client
                return $serverResponse;
            }
        }

        return array ();
    }

    /**
     * Perform User Search
     *
     * Asks the Server to search the current users of the system for the given search terms.  Returns
     * the list of results as a JSON-ready web ui response.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function performUserSearch(&$input) {
        $this->logger->addDebug("Searching users");

        $request = array ();
        $request["command"] = "search_users";
        // This is a strict query for a controlled vocabulary term
        $queryString = "";
        if (isset($input["q"]))
            $queryString = $input["q"];
        $request["query_string"] = $queryString;
        if (isset($input["count"]))
            $request["count"] = $input["count"];
        if (isset($input["role"]))
            $request["role"] = $input["role"];

        // Send the query to the server
        $serverResponse = $this->connect->query($request);

        if (!isset($serverResponse["results"]))
            return $serverResponse;

        if (isset($input["format"]) && $input["format"] == "term") {
            // keep the results as normal Term elements
        } else {
            $results = $serverResponse["results"];
            $serverResponse["results"] = array();

            foreach ($results as $k => $v) {
                $serverResponse["results"][$k]["id"] = $v["userid"];
                $serverResponse["results"][$k]["value"] = $v["fullName"] ." (".$v["userName"].")";
                $serverResponse["results"][$k]["text"] = $serverResponse["results"][$k]["value"];
            }
        }

        $this->logger->addDebug("Sending response back to client", $serverResponse);
            // Send the response back to the web client
        return $serverResponse;
    }

    /**
     * Save Vocabulary Term
     *
     * Asks the server to update the controlled vocabulary term.
     *
     * @param string[] $input Post/Get inputs from the webui
     * @param \snac\data\User $user The current user object
     * @return string[] The web ui's response to the client (array ready for json_encode)
     */
    public function saveVocabularyTerm(&$input, &$user) {

        // Build a data structure to send to the server
        $request = array("command"=>"update_vocabulary");

        $term = null;

        if ($input["type"] === "geo_term") {
            // Geographic Term
            $term = new \snac\data\GeoTerm();
            $term->setID($input["id"]);
            $term->setURI($input["uri"]);
            $term->setName($input["name"]);
            $term->setAdministrationCode($input["administrationCode"]);
            $term->setCountryCode($input["countryCode"]);
            $term->setLatitude($input["latitude"]);
            $term->setLongitude($input["longitude"]);
            $request["type"] = "geo_term";
        } else {
            // Standard Term object
            $term = new \snac\data\Term();
            $term->setType($input["type"]);
            $term->setID($input["id"]);
            $term->setURI($input["uri"]);
            $term->setDescription($input["description"]);
            $term->setTerm($input["term"]);
            $request["type"] = "term";
        }

        $request["term"] = $term->toArray();

        // Send the query to the server
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
        }

        return $response;
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

<?php

/**
 * Constellation Post Mapper Class File
 *
 * Contains the mapper class between Constellations and POST data from the WebUI
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2016 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\client\webui\util;

/**
 * Constellation POST Mapper
 *
 * This utility class provides the methods to convert input POST variables from the web user interface
 * into a PHP Constellation.  It also provides ways to get the input id mappings from a secondary constellation
 * that has more information (i.e. the constellation after having performed a server update and database write)
 *
 * @author Robbie Hott
 *
 */
class ConstellationPostMapper {

    /**
     * @var \snac\data\Constellation The constellation created from the original POST input
     */
    private $constellation = null;

    /**
     * @var mixed[] A mapping of fields to Constellation data objects
     */
    private $mapping = null;

    /**
     * @var string[][]  The nested form of the input from the POST
     */
    private $nested = null;

    /**
     * @var string[] Updates to be performed on the website
     */
    private $updates = null;

    /**
     * @var boolean Whether or not to look up Term values in the database
     */
    private $lookupTerms = false;

    /**
    * @var \snac\client\util\ServerConnect Whether or not to look up Term values in the database
    */
    private $lookupTermsConnector = null;

    /**
     * @var \Monolog\Logger $logger Logger for this class
     */
    private $logger = null;

    /**
     * Constructor
     */
    public function __construct() {
        global $log;
        $this->mapping = array();

        // create a log channel
        $this->logger = new \Monolog\Logger('ConstellationPOSTMapper');
        $this->logger->pushHandler($log);
    }

    /**
     * Allow Term Lookups
     *
     * Calling this method allows the PostMapper to connect to the server and
     * use the vocabulary search mechanism to look up terms.
     */
    public function allowTermLookup() {
        $this->lookupTerms = true;
        $this->lookupTermsConnector = new \snac\client\util\ServerConnect();
    }

    /**
     * Disallow Term Lookups
     *
     * By default, the PostMapper is not allowed to query the server and look
     * up any terms using the vocabulary search mechanism. Calling this method
     * returns the PostMapper to that default behavior.
     */
    public function disallowTermLookup() {
        $this->lookupTerms = false;
        $this->lookupTermsConnector = null;
    }


    /**
     * Parse a boolean
     *
     * Parses a boolean string or variable into an actual boolean.
     *
     * @param string|boolean $boolean The boolean value to parse
     * @return boolean The boolean value of the parameter
     */
    private function parseBoolean($boolean) {
        $type = gettype($boolean);
        switch($type) {
            case "boolean":
                return $boolean;
                break;
            case "string":
                if ($boolean == "true")
                    return true;
                else
                    return false;
                break;
            default:
                return false;
        }
    }

    /**
     * Get Operation
     *
     * Gets the operation from the parameter, if it exists.  If not, it returns null
     *
     * @param string[][] $data The input POST data
     * @return string|NULL The operation associated with this data
     */
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

    /**
     * Add to Mapping
     *
     * Adds a data object and id field mapping from the interface into the list of all mappings.
     *
     * @param string $shortName short name of the field
     * @param integer $id id of the post data field
     * @param string[][] $data POST data for this object
     * @param \snac\data\AbstractData $object Constellation Data object for this data
     */
    private function addToMapping($shortName, $id, $data, $object) {
        // If there is an operation, then we must add it
        if ($data["operation"] != "") {
            $map = array();
            $map["type"] = $shortName;
            $map["idField"] = $shortName . "_id_" . $id;
            $map["versionField"] = $shortName . "_version_" . $id;
            $map["operation"] = $this->getOperation($data);
            $map["object"] = $object;

            $this->logger->addDebug("Adding to mapping", $map);
            array_push($this->mapping, $map);
        }
    }

    /**
     * Parse SCM
     *
     * Parses the SCM out of the parameter and returns a list of SCM objects
     *
     * @param string[][] $objectWithSCM Array with SCM data included
     * @param string $short The short name of the containing object, as defined in the edit page
     * @param int $i The ID of the containing object, as defined in the edit page
     * @return \snac\data\SNACControlMetadata[] Array of snac control metadata
     */
    private function parseSCM($objectWithSCM, $short, $i) {
        // parse through the SCM array
        if (! isset($objectWithSCM) || $objectWithSCM == null || ! isset($objectWithSCM["scm"]) ||
                 $objectWithSCM["scm"] == null || $objectWithSCM["scm"] == "")
            return array ();

        $scmArray = array ();

        foreach ($objectWithSCM["scm"] as $j => $scm) {
            if (($scm["id"] == null || $scm["id"] == "") && $scm["operation"] != "insert")
                continue;
            $scmObject = new \snac\data\SNACControlMetadata();
            if ($scm["id"] != "")
                $scmObject->setID($scm["id"]);
            if ($scm["version"] != "")
                $scmObject->setVersion($scm["version"]);
            $scmObject->setOperation($this->getOperation($scm));
            $scmObject->setSubCitation($scm["subCitation"]);
            $scmObject->setSourceData($scm["sourceData"]);
            $scmObject->setNote($scm["note"]);

            $scmObject->setDescriptiveRule($this->parseTerm($scm["descriptiveRule"]));

            $scmObject->setLanguage($this->parseSubLanguage($scm, "scm_". $short, $j . "_". $i));

            if (isset($scm["citation"]) && isset($scm["citation"]["id"]) && $scm["citation"]["id"] != "") {
                foreach ($this->constellation->getSources() as $source) {
                    if ($source->getID() == $scm["citation"]["id"]) {
                        $scmObject->setCitation($source);
                        break;
                    }
                }
            }

            // short, i, post data, php object
            // need:
            $this->addToMapping("scm_".$short, $j . "_". $i, $scm, $scmObject);

            array_push($scmArray, $scmObject);
        }

        return $scmArray;
    }

    /**
     * Parse Term
     *
     * Parses and creates a Term object if the information exists in the data given.
     *
     * @param string[][] $data  Data to inspect for term object
     * @return NULL|\snac\data\Term Correct Term object or null if no term
     */
    private function parseTerm($data) {
        $term = null;
        if (isset($data) && $data != null && isset($data["id"]) && $data["id"] != "" && $data["id"] != null) {
            if ($this->lookupTerms) {
                $term = $this->lookupTermsConnector->lookupTerm($data["id"]);
            } else {
                $term = new \snac\data\Term();
                $term->setID($data["id"]);
            }
        }
        return $term;
    }

    /**
     * Parse a sub-language
     *
     * Parses a language that is an integral part of another object, such as an SCM,
     * BiogHist, NameEntry, etc.
     *
     * @param string[][] $object The string array to be parsed, which is the object containing a language
     * @param string $short The short name for this object's type (from the web page)
     * @param string|integer $i The id of the object on the page (not the DB ID)
     * @return \snac\data\Language The language object found when parsing the array
     */
    private function parseSubLanguage($object, $short, $i) {

        // If there is no language to parse, then just return null and don't do anything
        if ($object["language"]["id"] == "" &&
                $object["language"]["version"] == "" &&
                (!isset($object["languagelanguage"]) ||
                $object["languagelanguage"]["id"] == "") &&
                (!isset($object["languagescript"]) ||
                $object["languagescript"]["id"] == "") ) {
            return null;
        }

        $lang = new \snac\data\Language();
        if ($object["language"]["id"] != "")
            $lang->setID($object["language"]["id"]);
        if ($object["language"]["version"] != "")
            $lang->setVersion($object["language"]["version"]);

        if ($lang->getID() == null && $lang->getVersion() == null &&
                $this->getOperation($object) == \snac\data\Language::$OPERATION_UPDATE) {
            $lang->setOperation(\snac\data\Language::$OPERATION_INSERT);
        } else {
            $lang->setOperation($this->getOperation($object));
        }

        $lang->setLanguage($this->parseTerm($object["languagelanguage"]));

        $lang->setScript($this->parseTerm($object["languagescript"]));

        $this->addToMapping($short . "_language", $i, $object, $lang);

        return $lang;
    }

    /**
     * Get list of updates
     *
     * Gets the list of updates to be replayed on the web user interface.  This returns
     * an array of key value pairs of the website's inputs.
     *
     * @return string[] list of updates to perform
     */
    public function getUpdates() {
        return $this->updates;
    }

    /**
     * Get Match Info
     *
     * Returns the mapping information for the given object.
     *
     * @param \snac\data\AbstractData $object  The object for which to get mapping info
     * @return mixed[] The mapping information, which includes edit-page references and a reference to the object
     */
    private function getMatchInfo($object) {
        foreach ($this->mapping as $map) {
            if ($object->equals($map["object"], false)) {
                return $map;
            }
        }
        return null;
    }

    /**
     * Reconcile Objects
     *
     * Reconciles two objects.  If they match, this method fills in the class' updates field
     * to reflect that the incoming object should be linked to this object.  It adds the new ID
     * and version number to be eventually returned to the edit interface.
     *
     * @param \snac\data\AbstractData $object Main object to reconcile
     * @param \snac\data\AbstractData $other Object to reconcile against
     * @param boolean $checkLang optional Whether or not to check the language
     * @return boolean true if the objects reconcile (equal) or false otherwise
     */
    public function reconcileObject($object, $other, $checkLang = false) {
        if ($object == null && $other == null) {
            return true;
        }

        if ($object == null || $other == null) {
            return false;
        }

        $success = false;

        if ($object->getOperation() != null &&
                $object->equals($other, false) && $object->getOperation() == $other->getOperation()) {
            // loose equality (not checking IDs, since they may not exist)
            $piece = $this->getMatchInfo($object);
            
            if ($piece != null && !empty($piece)) {
                $this->logger->addDebug("Reconciling an object", array("info"=>$piece, "object"=>$object->toArray(), "other"=>$other->toArray()));

                // Other object is the one that we received from the server (with new ID and/or version)
                $this->updates[$piece["idField"]] = $other->getID();
                $this->updates[$piece["versionField"]] = $other->getVersion();
            }
            // Set success to be true (they matched)
            $success = true;
        }

        // This is highly inefficient!
        if ($object->getDateList() != null) {
            foreach($object->getDateList() as $date) {
                foreach ($other->getDateList() as $otherdate) {
                    $this->reconcileObject($date, $otherdate);
                }
            }
        }

        // This is highly inefficient!
        if ($object->getSNACControlMetadata() != null) {
            foreach($object->getSNACControlMetadata() as $scm) {
                foreach ($other->getSNACControlMetadata() as $otherscm) {
                    $this->reconcileObject($scm, $otherscm, true);
                }
            }
        }

        // Reconcile the language if we need to, based on the parameter
        if ($checkLang) {
           $this->reconcileObject($object->getLanguage(), $other->getLanguage());
        }

        return $success;
    }

    /**
     * Reconcile Constellation
     *
     * Reconciles the differences between the given constellation and the one already created
     * from the POST data.
     *
     * @param \snac\data\Constellation $constellation The Constellation object to reconcile
     */
    public function reconcile($constellation) {

        // First, check the constellation id
        if ($this->constellation->getID() != $constellation->getID()) {
                    $this->updates["constellationid"] = $constellation->getID();
        }

        // Then, the version number
        if ($this->constellation->getVersion() != $constellation->getVersion()) {
                    $this->updates["version"] = $constellation->getVersion();
        }

        // We need to parse the whole thing, all the way down...

        foreach ($this->constellation->getBiogHistList() as $biogHist) {
            foreach ($constellation->getBiogHistList() as $other) {
                $this->reconcileObject($biogHist, $other, true);
            }

        }

        foreach ($this->constellation->getConventionDeclarations() as $cd) {
            foreach ($constellation->getConventionDeclarations() as $other) {
                $this->reconcileObject($cd, $other);
            }
        }

        foreach ($this->constellation->getDateList() as $date) {
            foreach ($constellation->getDateList() as $other) {
                $this->reconcileObject($date, $other);
            }
        }

        foreach ($this->constellation->getFunctions() as $fn) {
            foreach ($constellation->getFunctions() as $other) {
                $this->reconcileObject($fn, $other);
            }
        }

        foreach ($this->constellation->getGenders() as $gender) {
            foreach ($constellation->getGenders() as $other) {
                $this->logger->addDebug("Reconciling Gender", array("obj" => $gender->toArray(), "other"=>$other->toArray()));
                $this->logger->addDebug("Reconciling Gender", array("obj" => print_r($gender->toArray(), true), "other"=>print_r($other->toArray(), true)));
                $this->logger->addDebug("Gender Match is " . (int) $gender->equals($other, false));
                $this->reconcileObject($gender, $other);
            }
        }

        foreach ($this->constellation->getGeneralContexts() as $gc) {
            foreach ($constellation->getGeneralContexts() as $other) {
                $this->reconcileObject($gc, $other);
            }
        }

        foreach ($this->constellation->getLanguagesUsed() as $languageUsed) {
            foreach ($constellation->getLanguagesUsed() as $other) {
                $this->reconcileObject($languageUsed, $other);
            }
        }

        foreach ($this->constellation->getLegalStatuses() as $legalStatus) {
            foreach ($constellation->getLegalStatuses() as $other) {
                $this->reconcileObject($legalStatus, $other);
            }
        }

        foreach ($this->constellation->getMaintenanceEvents() as $maintenanceEvent) {
            foreach ($constellation->getMaintenanceEvents() as $other) {
                $this->reconcileObject($maintenanceEvent, $other);
            }
        }

        foreach ($this->constellation->getMandates() as $mandate) {
            foreach ($constellation->getMandates() as $other) {
                $this->reconcileObject($mandate, $other);
            }
        }

        foreach ($this->constellation->getNameEntries() as $nameEntry) {
            foreach ($constellation->getNameEntries() as $other) {
                if ($this->reconcileObject($nameEntry, $other, true)) {
                    foreach ($nameEntry->getContributors() as $contributor) {
                        foreach ($other->getContributors() as $otherContrib) {
                            $this->reconcileObject($contributor, $otherContrib);
                        }
                    }
                    foreach ($nameEntry->getComponents() as $component) {
                        foreach ($other->getComponents() as $otherComponent) {
                            $this->reconcileObject($component, $otherComponent);
                        }
                    }
                }
            }
        }

        foreach ($this->constellation->getNationalities() as $nationality) {
            foreach ($constellation->getNationalities() as $other) {
                $this->reconcileObject($nationality, $other);
            }
        }

        foreach ($this->constellation->getOccupations() as $occupation) {
            foreach ($constellation->getOccupations() as $other) {
                $this->reconcileObject($occupation, $other);
            }
        }

        foreach ($this->constellation->getOtherRecordIDs() as $otherid) {
            foreach ($constellation->getOtherRecordIDs() as $other) {
                $this->reconcileObject($otherid, $other);
            }
        }

        foreach ($this->constellation->getEntityIDs() as $otherid) {
            foreach ($constellation->getEntityIDs() as $other) {
                $this->reconcileObject($otherid, $other);
            }
        }

        foreach ($this->constellation->getPlaces() as $place) {
            foreach ($constellation->getPlaces() as $other) {
                if ($this->reconcileObject($place, $other)) {
                    foreach ($place->getAddress() as $addressLine) {
                        foreach ($other->getAddress() as $otherAddressLine) {
                            $this->reconcileObject($addressLine, $otherAddressLine);
                        }
                    }
                }
            }
        }

        foreach ($this->constellation->getRelations() as $relation) {
            foreach ($constellation->getRelations() as $other) {
                $this->reconcileObject($relation, $other);
            }
        }

        foreach ($this->constellation->getResourceRelations() as $relation) {
            foreach ($constellation->getResourceRelations() as $other) {
                $this->reconcileObject($relation, $other);
            }
        }

        foreach ($this->constellation->getSNACControlMetadata() as $scm) {
            foreach ($constellation->getSNACControlMetadata() as $other) {
                $this->reconcileObject($scm, $other, true);
            }
        }

        foreach ($this->constellation->getSources() as $source) {
            foreach ($constellation->getSources() as $other) {
                $this->reconcileObject($source, $other);
            }
        }

        foreach ($this->constellation->getStructureOrGenealogies() as $sog) {
            foreach ($constellation->getStructureOrGenealogies() as $other) {
                $this->reconcileObject($sog, $other);
            }
        }

        foreach ($this->constellation->getSubjects() as $subject) {
            foreach ($constellation->getSubjects() as $other) {
                $this->reconcileObject($subject, $other);
            }
        }


    }


    /**
     * Parse array to SNACDate
     *
     * Parses an array of date information from the web ui into a date object for adding to the Constellation's data objects
     *
     * @param string $short The short name of the date in the user interface, i.e. `exist` or `nameEntry_date_1`
     * @param string $k The ui-level container ID for the object (i.e. name entry on-page id this date is for)
     * @param string[] $data The associative array describing one date or dateRange
     */
    function parseDate($short, $k, $data) {
        if ($data["id"] == "" && $data["operation"] != "insert")
            return null;
        $date = new \snac\data\SNACDate();
        $date->setID($data["id"]);
        $date->setVersion($data["version"]);
        $date->setOperation($this->getOperation($data));

        $date->setNote($data["note"]);
        $date->setFromDate($data["startoriginal"], $data["start"], $this->parseTerm($data["starttype"]));
        $date->setFromDateRange($data["startnotBefore"], $data["startnotAfter"]);

        if ($data["isrange"] === "true")
            $date->setRange(true);
        else
            $date->setRange(false);

        $date->setToDate($data["endoriginal"], $data["end"], $this->parseTerm($data["endtype"]));
        $date->setToDateRange($data["endnotBefore"], $data["endnotAfter"]);

        $date->setAllSNACControlMetadata($this->parseSCM($data, $short, $k));

        return $date;
    }

    /**
     * Serialize post data to Constellation
     *
     * Takes the POST data from a SAVE operation and generates
     * a Constellation object to be used by the rest of the system
     *
     * @param string[][] $postData The POST input data from the WebUI user interface
     * @return \snac\data\Constellation
     */
    public function serializeToConstellation($postData) {

        $this->constellation = new \snac\data\Constellation();

        // Rework the input into arrays of sections
        $nested = array ();
        $nested["gender"] = array ();
        $nested["exist"] = array ();
        $nested["biogHist"] = array ();
        $nested["language"] = array ();
        $nested["nationality"] = array ();
        $nested["function"] = array ();
        $nested["legalStatus"] = array ();
        $nested["conventionDeclaration"] = array ();
        $nested["generalContext"] = array ();
        $nested["structureOrGenealogy"] = array ();
        $nested["mandate"] = array ();
        $nested["nameEntry"] = array ();
        $nested["sameAs"] = array ();
        $nested["entityID"] = array ();
        $nested["source"] = array ();
        $nested["resourceRelation"] = array ();
        $nested["constellationRelation"] = array ();
        $nested["subject"] = array ();
        $nested["occupation"] = array ();
        $nested["place"] = array ();
        // container to hold SCM for the overall constellation
        $nested["constellation"] = array ();

        foreach ($postData as $k => $v) {
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
                if (! isset($nested[$parts[0]][$parts[2]]))
                    $nested[$parts[0]][$parts[2]] = array ();
                $nested[$parts[0]][$parts[2]][$parts[1]] = $v;
            } else if (count($parts) == 4) {
                // four parts: controlled vocabulary repeating
                // key_subkey_subsubkey_index => value ==> nested[key][index][subkey][subsubkey] = value
                if (! isset($nested[$parts[0]][$parts[3]]))
                    $nested[$parts[0]][$parts[3]] = array ();
                if (! isset($nested[$parts[0]][$parts[3]][$parts[1]]))
                    $nested[$parts[0]][$parts[3]][$parts[1]] = array ();
                $nested[$parts[0]][$parts[3]][$parts[1]][$parts[2]] = $v;
            } else if (count($parts) == 5 && $parts[0] == "scm") {
                // five parts: scm repeating
                // scm_key_subkey_subindex_index => value ==> nested[key][index][scm][subindex][subkey] = value
                if (! isset($nested[$parts[1]][$parts[4]]))
                    $nested[$parts[1]][$parts[4]] = array ();
                if (! isset($nested[$parts[1]][$parts[4]][$parts[0]]))
                    $nested[$parts[1]][$parts[4]][$parts[0]] = array ();
                if (! isset($nested[$parts[1]][$parts[4]][$parts[0]][$parts[3]]))
                    $nested[$parts[1]][$parts[4]][$parts[0]][$parts[3]] = array ();
                $nested[$parts[1]][$parts[4]][$parts[0]][$parts[3]][$parts[2]] = $v;
            } else if (count($parts) == 5 && $parts[0] != "scm") {
                // five parts: non-scm repeating
                // nameEntry_contributor_23_name_0
                // nameEntry_contributor_{{j}}_id_{{i}}
                // key, index = nameEntry, 0
                // subkey, index = contributor, 23
                // subsubkey = name
                // 0___1______2_________3________4
                // key_subkey_subindex_subsubkey_index => value ==>
                //                      nested[key][index][subkey][subindex][subsubkey] = value
                if (! isset($nested[$parts[0]][$parts[4]]))
                    $nested[$parts[0]][$parts[4]] = array ();
                if (! isset($nested[$parts[0]][$parts[4]][$parts[1]]))
                    $nested[$parts[0]][$parts[4]][$parts[1]] = array ();
                if (! isset($nested[$parts[0]][$parts[4]][$parts[1]][$parts[2]]))
                    $nested[$parts[0]][$parts[4]][$parts[1]][$parts[2]] = array ();
                $nested[$parts[0]][$parts[4]][$parts[1]][$parts[2]][$parts[3]] = $v;
            } else if (count($parts) == 6 && $parts[0] == "scm") {
                // six parts: scm repeating
                // scm_key_subkey_subsubkey_subindex_index => value ==> nested[key][index][scm][subindex][subkey][subsubkey] = value
                // {{short}}_scm_languagescript_id_{{j}}_{{i}}
                if (! isset($nested[$parts[1]][$parts[5]]))
                    $nested[$parts[1]][$parts[5]] = array ();
                if (! isset($nested[$parts[1]][$parts[5]][$parts[0]]))
                    $nested[$parts[1]][$parts[5]][$parts[0]] = array ();
                if (! isset($nested[$parts[1]][$parts[5]][$parts[0]][$parts[4]]))
                    $nested[$parts[1]][$parts[5]][$parts[0]][$parts[4]] = array ();
                if (! isset($nested[$parts[1]][$parts[5]][$parts[0]][$parts[4]][$parts[2]]))
                    $nested[$parts[1]][$parts[5]][$parts[0]][$parts[4]][$parts[2]] = array ();
                $nested[$parts[1]][$parts[5]][$parts[0]][$parts[4]][$parts[2]][$parts[3]] = $v;
            } else if (count($parts) == 6 && $parts[0] != "scm") {
                // six parts: non-scm repeating
                // nameEntry_contributor_23_type_id_0
                // key, index = nameEntry, 0
                // subkey, subindex = contributor, 23
                // subsubkey = type
                // subsubsubkey = id
                // 0___1______2________3_________4____________5
                // key_subkey_subindex_subsubkey_subsubsubkey_index => value ==>
                //                      nested[key][index][subkey][subindex][subsubkey][subsubsubkey] = value
                if (! isset($nested[$parts[0]][$parts[5]]))
                    $nested[$parts[0]][$parts[5]] = array ();
                if (! isset($nested[$parts[0]][$parts[5]][$parts[1]]))
                    $nested[$parts[0]][$parts[5]][$parts[1]] = array ();
                if (! isset($nested[$parts[0]][$parts[5]][$parts[1]][$parts[2]]))
                    $nested[$parts[0]][$parts[5]][$parts[1]][$parts[2]] = array ();
                if (! isset($nested[$parts[0]][$parts[5]][$parts[1]][$parts[2]][$parts[3]]))
                    $nested[$parts[0]][$parts[5]][$parts[1]][$parts[2]][$parts[3]] = array ();
                $nested[$parts[0]][$parts[5]][$parts[1]][$parts[2]][$parts[3]][$parts[4]] = $v;
            }
        }

        $this->logger->addDebug("parsed values", $nested);

        // NRD-level Information
        if (isset($nested["ark"]))
            $this->constellation->setArkID($nested["ark"]);
        if (isset($nested["constellationid"]))
            $this->constellation->setID($nested["constellationid"]);
        if (isset($nested["version"]))
            $this->constellation->setVersion($nested["version"]);
        if (isset($nested["operation"]))
            $this->constellation->setOperation($this->getOperation($nested));
        if (isset($nested["entityType"])) {
            $term = $this->parseTerm(array("id" => $nested["entityType"]));
            $this->constellation->setEntityType($term);
        }

            // We must do sources first, so they are available to any SCM calculations
            // That is, when SCM are added, we must match them up and send the actual Source
            // objects instead of just the ID that we get from the UI.
        foreach ($nested["source"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $source = new \snac\data\Source();
            $source->setID($data["id"]);
            $source->setVersion($data["version"]);
            $source->setOperation($this->getOperation($data));

            $source->setDisplayName($data["displayName"]);
            $source->setText($data["text"]);
            $source->setURI($data["uri"]);
            $source->setNote($data["note"]);

            $source->setLanguage($this->parseSubLanguage($data, "source", $k));

            // Right now, we're going to say this is okay because should a source have
            // other sources listed in their metadata?
            // TODO: Do sources have sources as part of their SCM?
            $source->setAllSNACControlMetadata($this->parseSCM($data, "source", $k));

            $this->addToMapping("source", $k, $data, $source);

            $this->constellation->addSource($source);
        }

        // Constellation SCM, which is hard-coded to have id=1 (see edit_page.html template)
        if (isset($nested["constellation"][1]))
            $this->constellation->setAllSNACControlMetadata($this->parseSCM($nested["constellation"][1], "constellation", 1));

        foreach ($nested["gender"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $gender = new \snac\data\Gender();
            $gender->setID($data["id"]);
            $gender->setVersion($data["id"]);
            $gender->setTerm($this->parseTerm($data["term"]));
            $gender->setOperation($this->getOperation($data));

            $gender->setAllSNACControlMetadata($this->parseSCM($data,"gender", $k));

            $this->addToMapping("gender", $k, $data, $gender);

            $this->constellation->addGender($gender);
        }

        foreach ($nested["exist"] as $k => $data) {
            $date = $this->parseDate("exist", $k, $data);
            if ($date != null) {
                $this->addToMapping("exist", $k, $data, $date);
                $this->constellation->addDate($date);
            }
        }

        foreach ($nested["biogHist"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $bh = new \snac\data\BiogHist();
            $bh->setID($data["id"]);
            $bh->setVersion($data["version"]);
            $bh->setOperation($this->getOperation($data));

            $bh->setText($data["text"]);

            $bh->setLanguage($this->parseSubLanguage($data, "biogHist", $k));


            $bh->setAllSNACControlMetadata($this->parseSCM($data, "biogHist", $k));

            $this->addToMapping("biogHist", $k, $data, $bh);

            $this->constellation->addBiogHist($bh);
        }

        foreach ($nested["language"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $lang = new \snac\data\Language();
            $lang->setID($data["id"]);
            $lang->setVersion($data["version"]);
            $lang->setOperation($this->getOperation($data));

            $lang->setLanguage($this->parseTerm($data["language"]));

            $lang->setScript($this->parseTerm($data["script"]));

            $lang->setAllSNACControlMetadata($this->parseSCM($data, "language", $k));

            $this->addToMapping("language", $k, $data, $lang);

            $this->constellation->addLanguageUsed($lang);
        }

        foreach ($nested["nationality"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $nationality = new \snac\data\Nationality();
            $nationality->setID($data["id"]);
            $nationality->setVersion($data["version"]);
            $nationality->setOperation($this->getOperation($data));

            $nationality->setTerm($this->parseTerm($data["term"]));

            $nationality->setAllSNACControlMetadata($this->parseSCM($data, "nationality", $k));

            $this->addToMapping("nationality", $k, $data, $nationality);

            $this->constellation->addNationality($nationality);
        }

        foreach ($nested["function"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $fun = new \snac\data\SNACFunction();
            $fun->setID($data["id"]);
            $fun->setVersion($data["version"]);
            $fun->setOperation($this->getOperation($data));

            $fun->setTerm($this->parseTerm($data["term"]));

            $fun->setAllSNACControlMetadata($this->parseSCM($data, "function", $k));

            $this->addToMapping("function", $k, $data, $fun);

            $this->constellation->addFunction($fun);
        }

        foreach ($nested["legalStatus"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $legalStatus = new \snac\data\LegalStatus();
            $legalStatus->setID($data["id"]);
            $legalStatus->setVersion($data["version"]);
            $legalStatus->setOperation($this->getOperation($data));

            $legalStatus->setTerm($this->parseTerm($data["term"]));

            $legalStatus->setAllSNACControlMetadata($this->parseSCM($data, "legalStatus", $k));

            $this->addToMapping("legalStatus", $k, $data, $legalStatus);

            $this->constellation->addLegalStatus($legalStatus);
        }

        foreach ($nested["conventionDeclaration"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $conventionDeclaration = new \snac\data\ConventionDeclaration();
            $conventionDeclaration->setID($data["id"]);
            $conventionDeclaration->setVersion($data["version"]);
            $conventionDeclaration->setOperation($this->getOperation($data));

            $conventionDeclaration->setText($data["text"]);

            $conventionDeclaration->setAllSNACControlMetadata($this->parseSCM($data, "conventionDeclaration", $k));

            $this->addToMapping("conventionDeclaration", $k, $data, $conventionDeclaration);

            $this->constellation->addConventionDeclaration($conventionDeclaration);
        }

        foreach ($nested["generalContext"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $generalContext = new \snac\data\GeneralContext();
            $generalContext->setID($data["id"]);
            $generalContext->setVersion($data["version"]);
            $generalContext->setOperation($this->getOperation($data));

            $generalContext->setText($data["text"]);

            $generalContext->setAllSNACControlMetadata($this->parseSCM($data, "generalContext", $k));

            $this->addToMapping("generalContext", $k, $data, $generalContext);

            $this->constellation->addGeneralContext($generalContext);
        }

        foreach ($nested["structureOrGenealogy"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $structureOrGenealogy = new \snac\data\StructureOrGenealogy();
            $structureOrGenealogy->setID($data["id"]);
            $structureOrGenealogy->setVersion($data["version"]);
            $structureOrGenealogy->setOperation($this->getOperation($data));

            $structureOrGenealogy->setText($data["text"]);

            $structureOrGenealogy->setAllSNACControlMetadata($this->parseSCM($data, "structureOrGenealogy", $k));

            $this->addToMapping("structureOrGenealogy", $k, $data, $structureOrGenealogy);

            $this->constellation->addStructureOrGenealogy($structureOrGenealogy);
        }

        foreach ($nested["mandate"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $mandate = new \snac\data\Mandate();
            $mandate->setID($data["id"]);
            $mandate->setVersion($data["version"]);
            $mandate->setOperation($this->getOperation($data));

            $mandate->setText($data["text"]);

            $mandate->setAllSNACControlMetadata($this->parseSCM($data, "mandate", $k));

            $this->addToMapping("mandate", $k, $data, $mandate);

            $this->constellation->addMandate($mandate);
        }

        foreach ($nested["nameEntry"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $this->logger->addDebug("Parsing Name Entry", $data);
            $nameEntry = new \snac\data\NameEntry();
            $nameEntry->setID($data["id"]);
            $nameEntry->setVersion($data["version"]);
            $nameEntry->setOperation($this->getOperation($data));

            $nameEntry->setOriginal($data["original"]);
            $nameEntry->setPreferenceScore($data["preferenceScore"]);

            $nameEntry->setLanguage($this->parseSubLanguage($data, "nameEntry", $k));

            $nameEntry->setAllSNACControlMetadata($this->parseSCM($data, "nameEntry", $k));

            // right now, update contributors if updating name entry
            if (isset($data["contributor"])) {
                foreach ($data["contributor"] as $l => $cData) {
                    if ($cData["id"] == "" && $cData["operation"] != "insert")
                        continue;
                    $this->logger->addDebug("Parsing through contributor", $cData);
                    $contributor = new \snac\data\Contributor();
                    $contributor->setID($cData["id"]);
                    $contributor->setVersion($cData["version"]);
                    if ($cData["operation"] == "insert" || $cData["operation"] == "delete")
                        $contributor->setOperation($this->getOperation($cData));
                    else {
                        $cData["operation"] = $this->getOperation($data);
                        $contributor->setOperation($this->getOperation($data));
                    }

                    $contributor->setName($cData["name"]);
                    $contributor->setType($this->parseTerm($cData["type"]));
                    $contributor->setRule($this->parseTerm($cData["rule"]));

                    $this->addToMapping("nameEntry_contributor_".$l, $k, $cData, $contributor);

                    $nameEntry->addContributor($contributor);
                }
            }

            // right now, update components if updating name entry
            if (isset($data["component"])) {
                foreach ($data["component"] as $l => $cData) {
                    if ($cData["id"] == "" && $cData["operation"] != "insert")
                        continue;
                    $this->logger->addDebug("Parsing through component", $cData);
                    $component = new \snac\data\NameComponent();
                    $component->setID($cData["id"]);
                    $component->setVersion($cData["version"]);
                    if ($cData["operation"] == "insert" || $cData["operation"] == "delete")
                        $component->setOperation($this->getOperation($cData));
                    else {
                        $cData["operation"] = $this->getOperation($data);
                        $component->setOperation($this->getOperation($data));
                    }

                    $component->setText($cData["text"]);
                    $component->setType($this->parseTerm($cData["type"]));
                    $component->setOrder($l);

                    $this->addToMapping("nameEntry_component_".$l, $k, $cData, $component);

                    $nameEntry->addComponent($component);
                }
            }

            if (isset($data["date"])) {
                foreach ($data["date"] as $l => $dData) {
                    $date = $this->parseDate("nameEntry_date_".$l, $k, $dData);
                    if ($date != null) {
                        $this->addToMapping("nameEntry_date_".$l, $k, $dData, $date);
                        $nameEntry->addDate($date);
                    }
                }
            }

            $this->addToMapping("nameEntry", $k, $data, $nameEntry);

            $this->constellation->addNameEntry($nameEntry);
        }

        foreach ($nested["sameAs"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $sameas = new \snac\data\SameAs();
            $sameas->setID($data["id"]);
            $sameas->setVersion($data["version"]);
            $sameas->setOperation($this->getOperation($data));

            $sameas->setText($data["text"]);
            $sameas->setURI($data["uri"]);

            $sameas->setType($this->parseTerm($data["type"]));

            $sameas->setAllSNACControlMetadata($this->parseSCM($data, "sameAs", $k));

            $this->addToMapping("sameAs", $k, $data, $sameas);

            $this->constellation->addOtherRecordID($sameas);
        }

        foreach ($nested["entityID"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $sameas = new \snac\data\EntityId();
            $sameas->setID($data["id"]);
            $sameas->setVersion($data["version"]);
            $sameas->setOperation($this->getOperation($data));

            $sameas->setText($data["text"]);

            $sameas->setType($this->parseTerm($data["type"]));

            $sameas->setAllSNACControlMetadata($this->parseSCM($data, "entityID", $k));

            $this->addToMapping("entityID", $k, $data, $sameas);

            $this->constellation->addEntityID($sameas);
        }

        foreach ($nested["resourceRelation"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $relation = new \snac\data\ResourceRelation();
            $relation->setID($data["id"]);
            $relation->setVersion($data["version"]);
            $relation->setOperation($this->getOperation($data));

            $relation->setContent($data["content"]);
            $relation->setLink($data["link"]);
            $relation->setSource($data["source"]);
            $relation->setNote($data["note"]);

            $relation->setDocumentType($this->parseTerm($data["documentType"]));

            $relation->setRole($this->parseTerm($data["role"]));

            $relation->setAllSNACControlMetadata($this->parseSCM($data, "resourceRelation", $k));

            $this->addToMapping("resourceRelation", $k, $data, $relation);

            $this->constellation->addResourceRelation($relation);
        }

        foreach ($nested["constellationRelation"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $relation = new \snac\data\ConstellationRelation();
            $relation->setID($data["id"]);
            $relation->setVersion($data["version"]);
            $relation->setOperation($this->getOperation($data));

            $relation->setSourceConstellation($this->constellation->getID());
            $relation->setSourceArkID($this->constellation->getArk());

            $relation->setTargetConstellation($data["targetID"]);
            $relation->setTargetArkID($data["targetArkID"]);
            $relation->setTargetEntityType($data["targetEntityType"]);

            $type = new \snac\data\Term();
            $type->setID($data["targetEntityType"]);
            $relation->setTargetEntityType($type);
            $relation->setContent($data["content"]);
            $relation->setNote($data["note"]);

            $relation->setType($this->parseTerm($data["type"]));

            $relation->setAllSNACControlMetadata($this->parseSCM($data, "constellationRelation", $k));

            $this->addToMapping("constellationRelation", $k, $data, $relation);

            $this->constellation->addRelation($relation);
        }

        foreach ($nested["subject"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $subject = new \snac\data\Subject();
            $subject->setID($data["id"]);
            $subject->setVersion($data["version"]);
            $subject->setOperation($this->getOperation($data));

            $subject->setTerm($this->parseTerm($data["term"]));

            $subject->setAllSNACControlMetadata($this->parseSCM($data, "subject", $k));

            $this->addToMapping("subject", $k, $data, $subject);

            $this->constellation->addSubject($subject);
        }

        foreach ($nested["occupation"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $occupation = new \snac\data\Occupation();
            $occupation->setID($data["id"]);
            $occupation->setVersion($data["version"]);
            $occupation->setOperation($this->getOperation($data));

            $occupation->setTerm($this->parseTerm($data["term"]));

            $occupation->setAllSNACControlMetadata($this->parseSCM($data, "occupation", $k));

            $this->addToMapping("occupation", $k, $data, $occupation);

            $this->constellation->addOccupation($occupation);
        }

        foreach ($nested["place"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $place = new \snac\data\Place();
            $place->setID($data["id"]);
            $place->setVersion($data["version"]);
            $place->setOperation($this->getOperation($data));

            $place->setOriginal($data["original"]);
            $place->setScore($data["score"]);
            $place->setNote($data["note"]);

            $place->setType($this->parseTerm($data["type"]));

            $place->setRole($this->parseTerm($data["role"]));

            if (isset($data["geoplace"]) && $data["geoplace"] != null && isset($data["geoplace"]["id"])
                    && $data["geoplace"]["id"] != null && $data["geoplace"]["id"] != "") {
                $geoterm = new \snac\data\GeoTerm();
                $geoterm->setID($data["geoplace"]["id"]);
                $place->setGeoTerm($geoterm);
            }

            if ($data["confirmed"] === "true")
                $place->confirm();
            else
                $place->deconfirm();

            $place->setAllSNACControlMetadata($this->parseSCM($data, "place", $k));
            
            // right now, update components if updating name entry
            if (isset($data["address"])) {
                foreach ($data["address"] as $l => $aData) {
                    if ($aData["id"] == "" && $aData["operation"] != "insert")
                        continue;
                    $this->logger->addDebug("Parsing through address", $aData);
                    $part = new \snac\data\AddressLine();
                    $part->setID($aData["id"]);
                    $part->setVersion($aData["version"]);
                    if ($aData["operation"] == "insert" || $aData["operation"] == "delete")
                        $part->setOperation($this->getOperation($aData));
                    else {
                        $aData["operation"] = $this->getOperation($data);
                        $part->setOperation($this->getOperation($data));
                    }

                    $part->setText($aData["text"]);
                    $part->setType($this->parseTerm($aData["type"]));
                    $part->setOrder($l);

                    $this->addToMapping("place_address_".$l, $k, $aData, $part);

                    $place->addAddressLine($part);
                }
            }


            $this->addToMapping("place", $k, $data, $place);

            $this->constellation->addPlace($place);
        }

        $this->nested = $nested;

        return $this->constellation;
    }
}

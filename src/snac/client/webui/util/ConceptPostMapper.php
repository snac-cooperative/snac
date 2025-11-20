<?php

/**
 * Concept Post Mapper Class File
 *
 * Contains the mapper class between Concept and POST data from the WebUI
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2025 the Rector and Visitors of the University of Virginia
 */
namespace snac\client\webui\util;

/**
 * Concept POST Mapper
 *
 * This utility class provides the methods to convert input POST variables from the web user interface
 * into a PHP Concept.
 *
 * @author Robbie Hott
 *
 */
class ConceptPostMapper {

    /**
     * @var \snac\data\Concept The concept created from the original POST input
     */
    private $concept = null;

    /**
     * @var mixed[] A mapping of fields to Concept data objects
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
     * @var boolean $mapAsNew Whether or not to map the POST values to a new Constellation object
     */
    private $mapAsNew = false;

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
        $this->logger = new \Monolog\Logger('ConceptPOSTMapper');
        $this->logger->pushHandler($log);

        $this->allowTermLookup();
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
     * Map to new Constellation
     *
     * Call this method to have the CPM map the Post data to a Constellation object
     * without keeping IDs or version numbers.  It will also set all data element's
     * operation as "insert".  This effectively will map it as a new Constellation for
     * having the server write it as new.
     */
    public function mapAsNewConcept() {
        $this->mapAsNew = true;
    }

    /**
     * Map to Constellation with IDs
     *
     * Call this method to have the CPM map the Post data to a Constellation object
     * while keeping the IDs and version numbers intact.
     */
    public function mapWithIDs() {
        $this->mapAsNew = false;
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

        if ($this->mapAsNew)
            return \snac\data\AbstractData::$OPERATION_INSERT;

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
            $map["operation"] = $this->getOperation($data);
            $map["object"] = $object;

            $this->logger->addDebug("Adding to mapping", $map);
            array_push($this->mapping, $map);
        }
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
            }
            // Set success to be true (they matched)
            $success = true;
        }

        return $success;
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
    public function serializeToConcept($postData) {

        $this->concept = new \snac\data\Concept();

        // Rework the input into arrays of sections
        $nested = array ();
        $nested["term"] = array ();
        $nested["relation"] = array ();
        $nested["category"] = array ();
        $nested["source"] = array ();

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
            }
        }

        $this->logger->addDebug("parsed values", $nested);

        // NRD-level Information
        if (!$this->mapAsNew) {
            if (isset($nested["conceptid"]))
                $this->concept->setID($nested["conceptid"]);
        }
        if (isset($nested["operation"]))
            $this->concept->setOperation($this->getOperation($nested));

        foreach ($nested["source"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $source = new \snac\data\Source();
            if (!$this->mapAsNew) {
                $source->setID($data["id"]);
                $source->setVersion($data["version"]);
            }
            $source->setOperation($this->getOperation($data));

            // $source->setDisplayName($data["displayName"]);
            $source->setText($data["text"]);
            $source->setCitation($data["citation"]);
            $source->setURI($data["uri"]);
            $source->setNote($data["note"]);

            $source->setLanguage($this->parseSubLanguage($data, "source", $k));

            $this->addToMapping("source", $k, $data, $source);

            $this->concept->addSource($source);
        }

        foreach ($nested["term"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $term = new \snac\data\ConceptTerm();
            if (!$this->mapAsNew) {
                $term->setID($data["id"]);
            }
            $term->setText($data["text"]); //$this->parseTerm($data["term"]));
            $term->setOperation($this->getOperation($data));
    
            $language = $this->parseTerm($data["language"]);
            $term->setLanguage($language);

            $this->addToMapping("term", $k, $data, $term);
            
            if ($data["preferred"] == "preferred")
                $this->concept->addPreferredTerm($term);
            else
                $this->concept->addTerm($term); 
        }

        foreach ($nested["relation"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            // if there is no attatched concept, then don't do it
            if ($data["relatedConcept"]["id"] == null || $data["type"]["id"] == null)
                continue;
            $relation = new \snac\data\ConceptRelationship();
            if (!$this->mapAsNew) {
                $relation->setID($data["id"]);
            }
            $relation->setOperation($this->getOperation($data));

            $relatedTerm = $this->parseTerm($data["relatedConcept"]);
            $relConcept = new \snac\data\Concept();
            $relConcept->setID($relatedTerm->getID());
            $relation->setRelatedConcept($relConcept);

            $type = $this->parseTerm($data["type"]);
            //This is a hacky-way to do this
            // TODO update concept module to use relation_type as IDs
            $relation->setType(strtolower($type->getTerm()));

            $this->addToMapping("relation", $k, $data, $relation);
            
            $this->concept->addRelationship($relation); 
        }

        foreach ($nested["category"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["operation"] != "insert")
                continue;
            $term = $this->parseTerm($data["term"]);
            $this->addToMapping("category", $k, $data, $term);
            
            $this->concept->addCategory($term); 
        }

        $this->nested = $nested;

        return $this->concept;
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
        if (!isset($object["language"]))
            return null;

        if ($object["language"]["id"] == "" &&
                $object["language"]["version"] == "" &&
                (!isset($object["languagelanguage"]) ||
                $object["languagelanguage"]["id"] == "") &&
                (!isset($object["languagescript"]) ||
                $object["languagescript"]["id"] == "") ) {
            return null;
        }

        $lang = new \snac\data\Language();
        if (!$this->mapAsNew) {
            if ($object["language"]["id"] != "")
                $lang->setID($object["language"]["id"]);
            if ($object["language"]["version"] != "")
                $lang->setVersion($object["language"]["version"]);
        }

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
}


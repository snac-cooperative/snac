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
     * @return \snac\data\SNACControlMetadata[] Array of snac control metadata
     */
    private function parseSCM($objectWithSCM, $short, $i) {
        // parse through the SCM array
        if (! isset($objectWithSCM) || $objectWithSCM == null || ! isset($objectWithSCM["scm"]) ||
                 $objectWithSCM["scm"] == null || $objectWithSCM["scm"] == "")
            return array ();
        
        $scmArray = array ();
        
        foreach ($objectWithSCM["scm"] as $j => $scm) {
            $scmObject = new \snac\data\SNACControlMetadata();
            if ($scm["id"] != "")
                $scmObject->setID($scm["id"]);
            if ($scm["version"] != "")
                $scmObject->setVersion($scm["version"]);
            $scmObject->setOperation($this->getOperation($scm));
            $scmObject->setSubCitation($scm["subCitation"]);
            $scmObject->setSourceData($scm["sourceData"]);
            $scmObject->setNote($scm["note"]);
            
            if (isset($scm["descriptiveRule"]) && isset($scm["descriptiveRule"]["id"]) &&
                     $scm["descriptiveRule"]["id"] != "") {
                $term = new \snac\data\Term();
                $term->setID($scm["descriptiveRule"]["id"]);
                $scmObject->setDescriptiveRule($term);
            }
            
            $lang = new \snac\data\Language();
            if ($scm["language"]["id"] != "")
                $lang->setID($scm["language"]["id"]);
            if ($scm["language"]["version"] != "")
                $lang->setVersion($scm["language"]["version"]);
            $lang->setOperation($this->getOperation($scm));
            // May need to set the operation to insert if the id is null and there is an update...
            
            if (isset($scm["languagelanguage"]["id"]) &&
                    $scm["languagelanguage"]["id"] != null &&
                    $scm["languagelanguage"]["id"] != "") {
                $term = new \snac\data\Term();
                $term->setID($scm["languagelanguage"]["id"]);
                $lang->setLanguage($term);
            }

            if (isset($scm["languagescript"]["id"]) &&
                    $scm["languagescript"]["id"] != null &&
                    $scm["languagescript"]["id"] != "") {
                $term = new \snac\data\Term();
                $term->setID($scm["languagescript"]["id"]);
                $lang->setScript($term);
            }
            
            $this->addToMapping("scm_".$short."_language", $j . "_". $i, $scm, $lang);
            
            $scmObject->setLanguage($lang);
            
            // short, i, post data, php object
            // need: 
            $this->addToMapping("scm_".$short, $j . "_". $i, $scm, $scmObject);
            
            array_push($scmArray, $scmObject);
        }
        
        return $scmArray;
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
    
    private function getMatchInfo($object) {
        foreach ($this->mapping as $map) {
            if ($object->equals($map["object"], false)) {
                return $map;
            }
        }
    }
    
    /**
     * @param \snac\data\AbstractData $object Main object to reconcile
     * @param \snac\data\AbstractData $other Object to reconcile against
     * @param boolean $checkLang optional Whether or not to check the language
     */
    public function reconcileObject($object, $other, $checkLang = false) {
        if ($object == null || $other == null) {
            return;
        }

        if ($object->getOperation() != null &&
                $object->equals($other, false) && $object->getOperation() == $other->getOperation()) {
            // loose equality (not checking IDs, since they may not exist)
            $piece = $this->getMatchInfo($object);
            
            $this->logger->addDebug("Reconciling an object", array("info"=>$piece, "object"=>$object->toArray(), "other"=>$other->toArray()));
            
            // Other object is the one that we received from the server (with new ID and/or version)
            $this->updates[$piece["idField"]] = $other->getID();
            $this->updates[$piece["versionField"]] = $other->getVersion();
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
        
        // Check the language if we need to, based on the parameter
        if ($checkLang) {
           $this->reconcileObject($object->getLanguage(), $other->getLanguage());
        }
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
                $this->reconcileObject($nameEntry, $other, true);
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
        
        foreach ($this->constellation->getPlaces() as $place) {
            foreach ($constellation->getPlaces() as $other) {
                $this->reconcileObject($place, $other);
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
     * Serialize post data to Constellation
     *
     * Takes the POST data from a SAVE operation and generates
     * a Constellation object to be used by the rest of the system
     *
     * @param string[][] $postData The POST input data from the WebUI user interface
     * @return \snac\data\Constellation
     */
    public function serializeToConstellation($postData) {

        $constellation = new \snac\data\Constellation();
        
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
            } else if (count($parts) == 5) {
                // five parts: scm repeating
                // scm_key_subkey_subindex_index => value ==> nested[key][index][scm][subindex][subkey] = value
                if (! isset($nested[$parts[1]][$parts[4]]))
                    $nested[$parts[1]][$parts[4]] = array ();
                if (! isset($nested[$parts[1]][$parts[4]][$parts[0]]))
                    $nested[$parts[1]][$parts[4]][$parts[0]] = array ();
                if (! isset($nested[$parts[1]][$parts[4]][$parts[0]][$parts[3]]))
                    $nested[$parts[1]][$parts[4]][$parts[0]][$parts[3]] = array ();
                if (! isset($nested[$parts[1]][$parts[4]][$parts[0]][$parts[3]][$parts[2]]))
                    $nested[$parts[1]][$parts[4]][$parts[0]][$parts[3]][$parts[2]] = array ();
                $nested[$parts[1]][$parts[4]][$parts[0]][$parts[3]][$parts[2]] = $v;
            } else if (count($parts) == 6) {
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
                if (! isset($nested[$parts[1]][$parts[5]][$parts[0]][$parts[4]][$parts[2]][$parts[3]]))
                    $nested[$parts[1]][$parts[5]][$parts[0]][$parts[4]][$parts[2]][$parts[3]] = array ();
                $nested[$parts[1]][$parts[5]][$parts[0]][$parts[4]][$parts[2]][$parts[3]] = $v;
            }
        }
        
        // Just for testing
        $arr = print_r($nested, true);
        file_put_contents("/home/jh2jf/output/webui-nested-" . date("Ymd-His") . ".txt", $arr);
        
        // NRD-level Information
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
        
        // Constellation SCM
        
        $constellation->setAllSNACControlMetadata($this->parseSCM($nested["constellation"], "constellation", 1));
        
        foreach ($nested["gender"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $term = new \snac\data\Term();
            $term->setID($data["term"]["id"]);
            $gender = new \snac\data\Gender();
            $gender->setID($data["id"]);
            $gender->setVersion($data["id"]);
            $gender->setTerm($term);
            $gender->setOperation($this->getOperation($data));
            
            $gender->setAllSNACControlMetadata($this->parseSCM($data,"gender", $k));
            
            $this->addToMapping("gender", $k, $data, $gender);
            
            $constellation->addGender($gender);
        }
        
        foreach ($nested["exist"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
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
            
            $date->setAllSNACControlMetadata($this->parseSCM($data, "exist", $k));

            $this->addToMapping("exist", $k, $data, $date);
            
            $constellation->addDate($date);
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
            
            if ($data["language"]["id"] != "" || $data["languagelanguage"]["id"] != "" ||
                     $data["languagescript"]["id"] != "") {
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
            }
            
            $bh->setAllSNACControlMetadata($this->parseSCM($data, "biogHist", $k));

            $this->addToMapping("biogHist", $k, $data, $bh);
            
            $constellation->addBiogHist($bh);
        }
        
        foreach ($nested["language"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
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
            
            $lang->setAllSNACControlMetadata($this->parseSCM($data, "language", $k));
            
            $this->addToMapping("language", $k, $data, $lang);
            
            $constellation->addLanguageUsed($lang);
        }
        
        foreach ($nested["nationality"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $nationality = new \snac\data\Nationality();
            $nationality->setID($data["id"]);
            $nationality->setVersion($data["version"]);
            $nationality->setOperation($this->getOperation($data));
            
            $term = new \snac\data\Term();
            $term->setID($data["term"]["id"]);
            $nationality->setTerm($term);
            
            $nationality->setAllSNACControlMetadata($this->parseSCM($data, "nationality", $k));
            
            $this->addToMapping("nationality", $k, $data, $nationality);
            
            $constellation->addNationality($nationality);
        }
        
        foreach ($nested["function"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $fun = new \snac\data\SNACFunction();
            $fun->setID($data["id"]);
            $fun->setVersion($data["version"]);
            $fun->setOperation($this->getOperation($data));
            
            $term = new \snac\data\Term();
            $term->setID($data["term"]["id"]);
            $fun->setTerm($term);
            
            $fun->setAllSNACControlMetadata($this->parseSCM($data, "function", $k));
            
            $this->addToMapping("function", $k, $data, $fun);
            
            $constellation->addFunction($fun);
        }
        
        foreach ($nested["legalStatus"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $legalStatus = new \snac\data\LegalStatus();
            $legalStatus->setID($data["id"]);
            $legalStatus->setVersion($data["version"]);
            $legalStatus->setOperation($this->getOperation($data));
            
            $term = new \snac\data\Term();
            $term->setID($data["term"]["id"]);
            $legalStatus->setTerm($term);
            
            $legalStatus->setAllSNACControlMetadata($this->parseSCM($data, "legalStatus", $k));
            
            $this->addToMapping("legalStatus", $k, $data, $legalStatus);
            
            $constellation->addLegalStatus($legalStatus);
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
            
            $constellation->addConventionDeclaration($conventionDeclaration);
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
            
            $constellation->addGeneralContext($generalContext);
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
            
            $constellation->addStructureOrGenealogy($structureOrGenealogy);
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
            
            $constellation->addMandate($mandate);
        }
        
        foreach ($nested["nameEntry"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $nameEntry = new \snac\data\NameEntry();
            $nameEntry->setID($data["id"]);
            $nameEntry->setVersion($data["version"]);
            $nameEntry->setOperation($this->getOperation($data));
            
            $nameEntry->setOriginal($data["original"]);
            $nameEntry->setPreferenceScore($data["preferenceScore"]);
            
            if ($data["language"]["id"] != "" || $data["languagelanguage"]["id"] != "" ||
                     $data["languagescript"]["id"] != "") {
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
            }
            
            $nameEntry->setAllSNACControlMetadata($this->parseSCM($data, "nameEntry", $k));
            
            $this->addToMapping("nameEntry", $k, $data, $nameEntry);
            
            $constellation->addNameEntry($nameEntry);
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
            
            $type = new \snac\data\Term();
            $type->setID($data["type"]["id"]);
            $sameas->setType($type);
            
            $sameas->setAllSNACControlMetadata($this->parseSCM($data, "sameAs", $k));
            
            $this->addToMapping("sameAs", $k, $data, $sameas);
            
            $constellation->addOtherRecordID($sameas);
        }
        
        foreach ($nested["source"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $source = new \snac\data\Source();
            $source->setID($data["id"]);
            $source->setVersion($data["version"]);
            $source->setOperation($this->getOperation($data));
            
            $source->setText($data["text"]);
            $source->setURI($data["uri"]);
            $source->setNote($data["note"]);
            
            if ($data["language"]["id"] != "" || $data["languagelanguage"]["id"] != "" ||
                     $data["languagescript"]["id"] != "") {
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
            }
            
            $source->setAllSNACControlMetadata($this->parseSCM($data, "source", $k));
            
            $this->addToMapping("source", $k, $data, $source);
            
            $constellation->addSource($source);
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
            
            $type = new \snac\data\Term();
            $type->setID($data["documentType"]["id"]);
            $relation->setDocumentType($type);
            
            $role = new \snac\data\Term();
            $role->setID($data["role"]["id"]);
            $relation->setRole($role);
            
            $relation->setAllSNACControlMetadata($this->parseSCM($data, "resourceRelation", $k));
            
            $this->addToMapping("resourceRelation", $k, $data, $relation);
            
            $constellation->addResourceRelation($relation);
        }
        
        foreach ($nested["constellationRelation"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
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
            
            $relation->setAllSNACControlMetadata($this->parseSCM($data, "constellationRelation", $k));
            
            $this->addToMapping("constellationRelation", $k, $data, $relation);
            
            $constellation->addRelation($relation);
        }
        
        foreach ($nested["subject"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $subject = new \snac\data\Subject();
            $subject->setID($data["id"]);
            $subject->setVersion($data["version"]);
            $subject->setOperation($this->getOperation($data));
            
            $term = new \snac\data\Term();
            $term->setID($data["term"]["id"]);
            $subject->setTerm($term);
            
            $subject->setAllSNACControlMetadata($this->parseSCM($data, "subject", $k));
            
            $this->addToMapping("subject", $k, $data, $subject);
            
            $constellation->addSubject($subject);
        }
        
        foreach ($nested["occupation"] as $k => $data) {
            // If the user added an object, but didn't actually edit it
            if ($data["id"] == "" && $data["operation"] != "insert")
                continue;
            $occupation = new \snac\data\Occupation();
            $occupation->setID($data["id"]);
            $occupation->setVersion($data["version"]);
            $occupation->setOperation($this->getOperation($data));
            
            $term = new \snac\data\Term();
            $term->setID($data["term"]["id"]);
            $occupation->setTerm($term);
            
            $occupation->setAllSNACControlMetadata($this->parseSCM($data, "occupation", $k));
            
            $this->addToMapping("occupation", $k, $data, $occupation);
            
            $constellation->addOccupation($occupation);
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
            
            $place->setAllSNACControlMetadata($this->parseSCM($data, "place", $k));

            $this->addToMapping("place", $k, $data, $place);
            
            $constellation->addPlace($place);
        }
        
        $this->constellation = $constellation;
        $this->nested = $nested;
        
        return $constellation;
    }
}
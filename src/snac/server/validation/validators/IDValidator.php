<?php

/**
 * ID Validator Class File
 *
 * Contains the ID validator class
 * 
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server\validation\validators;

/**
 * ID Validator 
 * 
 * Validates that any IDs used for Constellation pieces exist in the database. This
 * validator does NOT check that Term IDs are appropriate.  It only checks that any
 * piece of the constellation uses an existing ID and that no IDs are duplicated.
 *
 * @author Robbie Hott
 *
 */
class IDValidator extends \snac\server\validation\validators\Validator {
    
    /**
     * @var \snac\data\Constellation $constellation The original constellation out of the database
     */
    private $constellation = null;
    
    /**
     * @var int[][] $seen The IDs that have been seen so far
     */
    private $seen;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->validatorName = "IDValidator";
        parent::__construct();
        
        $this->seen = array();
        $this->seen["biogHist"] = array();
        $this->seen[""] = array();
    }
    
    /**
     * Grab a copy of the constellation out of the database, based on the ID
     * 
     * @param \snac\data\Constellation $constellation constellation
     */
    public function setConstellation($constellation) {
        // Get the ID for this constellation
        $id = $constellation->getID();
        
        if ($id == null) {
            // We don't have the ID, so we can't do anything
            return false;
        }
        
        // Get the Constellation out of the database
        $dbutil = new \snac\server\database\DBUtil();
        
        //TODO Replace this with the actual code to get the constellation out of the database
        $this->constellation = $constellation;
        
        return true;
            
    }
    
    /**
     * Validate the root of the constellation
     * 
     * @param \snac\data\Constellation $constellation constellation root to validate
     * @return boolean true if valid, false otherwise
     */
    public function validateRoot($constellation) {
        if ($constellation->getID() != null && $this->constellation == null) {
            // Could not get the constellation
            $this->addError("The constellation ID does not exist", $constellation);
            return false;
        }
        return true;
    }
    
    /**
     * Check whether IDs persist down SCM
     * 
     * @param \snac\data\AbstractData $object
     * @return boolean
     */
    private function checkSCM($object) {
        $success = true;
        foreach ($object->getSNACControlMetadata() as $scm) {
            if ($scm != null && $scm->getID() != null) {
                $this->addError("Object with no ID has SNACControlMetadata with ID", $object);
                $success = false;
            } else if ($scm != null && $scm->getID() == null && 
                    (($scm->getCitation() != null && $scm->getCitation()->getID() != null) ||
                     ($scm->getLanguage() != null && $scm->getLanguage()->getID() != null))) {
                $this->addError("Object with no ID has SNACControlMetadata with no ID with subelements that have ID", $object);
                $success = false;
            }
        }
        return $success;
    }
    
    
    /**
     * Check an object for language with ID
     * 
     * Checks that the object has no language object with an ID
     * 
     * @param \snac\data\AbstractData $object object to check
     * @return boolean true if no langauge with ID, false otherwise
     */
    private function checkLanguage($object) {
        if ($object->getLanguage() != null) {
            if ($object->getLanguage()->getID() != null) {
                $this->addError("Object with no ID has Language with ID", $object);
                return false;
            } else {
                return $this->checkSCM($object->getLanguage());
            }
        }
        return true;
    }
    

    /**
     * Check an object for dates with ID
     * 
     * Checks that the object has no date objects with IDs
     * 
     * @param \snac\data\AbstractData $object object to check
     * @return boolean true if no dates with ID, false otherwise
     */
    private function checkDates($object) {
        $success = true;
        if ($object->getDateList() != null) {
            foreach ($object->getDateList() as $date) {
                if ($date != null) {
                    if ($date->getID() != null) {
                        $this->addError("Object with no ID has a Date with ID", $object);
                        $success = false;   
                    } else {
                        $success = $success && $this->checkSCM($date);
                    }
                }
            }
        }
        return $success;
    }
    
    /**
     * Validate all SCMetadata
     * 
     * Loops over each SCM of an object, and validates it individually
     * 
     * @param \snac\data\AbstractData $object Object to be validated
     * @param \snac\data\AbstractData $realObject The real object to validate against
     * @return boolean true if all SCM validate, false otherwise
     */
    private function validateAllSCM($object, $realObject) {
        $success = true;
        foreach ($object->getSNACControlMetadata() as $scm) {
            $success = $success && $this->validateSNACControlMetadata($scm, $realObject);
        }
        return $success;
    }
    
    /**
     * Validate all Dates
     * 
     * Loops over each Date of an object, and validates it individually
     * 
     * @param \snac\data\AbstractData $object Object to be validated
     * @param \snac\data\AbstractData $realObject The real object to validate against
     * @return boolean true if all dates validate, false otherwise
     */
    private function validateAllDates($object, $realObject) {
        $success = true;
        foreach ($object->getDateList() as $date) {
            $success = $success && $this->validateDate($date, $realObject);
        }
        return $success;
    }
    

    /**
     * Validate a Generic AbstractData Object
     * 
     * This method will validate any object that just has SCM and no other sub-objects.  This method takes
     * the object to be validated, an array of objects that may contain this object (this should be the list
     * from a validated parent object), and the type of the object (to check for no duplicates).  
     * 
     * This generic function also makes use of the fact that all AbstractData objects have the getDateList method that
     * always returns an array, even if empty.  This therefore automatically checks dates on any object that has them.
     * 
     * @param \snac\data\AbstractData $object The object to validate
     * @param \snac\data\AbstractData[] $realObjects A list (from a valid constellation part) possibly containing the object
     * @param string $type Sting shorthand type of this object
     * @return boolean true if valid, false otherwise.
     */
    private function validateGeneric($object, $realObjects, $type, $preID = "") {
        // If the object is null, stop and succeed (leaf node)
        if ($object == null) {
            return true;
        }
    
        // Set success to be true by default
        $success = true;
    
        // If this object has no ID, but a subobject does, then success is false
        if ($object->getID() == null) {
            $success = $success && $this->checkDates($object);
            $success = $success && $this->checkSCM($object);
            return $success;
        }
    
        // If this object has already been seen, then success is false
        if (in_array($preID . $object->getID(), $this->seen[$type])) {
            $this->addError("ID used multiple times", $object);
            $success = false;
        }
    
        // Validate this object's ID against the real object from the database, and
        // validate all it's subelements against their counterparts from the database
        foreach ($realObjects as $i => $current) {
            if ($object->getID() == $current->getID()) {
                array_push($this->seen[$type], $preID . $object->getID());
                // Validate the subelements
                $success = $success && $this->validateAllDates($object, $current);
                $success = $success && $this->validateAllSCM($object, $current);
            }
        }
    
        // Return success
        return $success;
    }

    /**
     * Validate a Generic AbstractData Object with Language
     *
     * This method will validate any object that just has SCM and no other sub-objects.  This method takes
     * the object to be validated, an array of objects that may contain this object (this should be the list
     * from a validated parent object), and the type of the object (to check for no duplicates).  This method
     * also tests the Language of the object (so, only use this with objects that have Languages)
     * 
     * This generic function also makes use of the fact that all AbstractData objects have the getDateList method that
     * always returns an array, even if empty.  This therefore automatically checks dates on any object that has them.
     *
     * @param \snac\data\AbstractData $object The object to validate
     * @param \snac\data\AbstractData[] $realObjects A list (from a valid constellation part) possibly containing the object
     * @param string $type Sting shorthand type of this object
     * @return boolean true if valid, false otherwise.
     */
    private function validateGenericWithLanguage($object, $realObjects, $type, $preID = "") {
        // If the object is null, stop and succeed (leaf node)
        if ($object == null) {
            return true;
        }
    
        // Set success to be true by default
        $success = true;
    
        // If this object has no ID, but a subobject does, then success is false
        if ($object->getID() == null) {
            $success = $success && $this->checkLanguage($object);
            $success = $success && $this->checkDates($object);
            $success = $success && $this->checkSCM($object);
            return $success;
        }
    
        // If this object has already been seen, then success is false
        if (in_array($preID . $object->getID(), $this->seen[$type])) {
            $this->addError("ID used multiple times", $object);
            $success = false;
        }
    
        // Validate this object's ID against the real object from the database, and
        // validate all it's subelements against their counterparts from the database
        foreach ($realObjects as $i => $current) {
            if ($object->getID() == $current->getID()) {
                array_push($this->seen[$type], $preID . $object->getID());
                // Validate the subelements
                $success = $success && $this->validateLanguage($object->getLanguage(), $current);
                $success = $success && $this->validateAllDates($object, $current);
                $success = $success && $this->validateAllSCM($object, $current);
            }
        }
    
        // Return success
        return $success;
    }
    
    /**
     * Validate a biog hist
     * 
     * @param \snac\data\BiogHist $biogHist BiogHist to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateBiogHist($biogHist, $context=null) {
        return $this->validateGenericWithLanguage($biogHist, $this->constellation->getBiogHistList(), "biogHist");
    }
    
    /**
     * Validate a Convention Declaration
     * 
     * @param \snac\data\ConventionDeclaration $cd ConventionDeclaration to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateConventionDeclaration($cd, $context=null) {
        return $this->validateGeneric($cd, $this->constellation->getConventionDeclarations(), "cd");
    }
    
    /**
     * Validate a Date
     * 
     * @param \snac\data\SNACDate $date SNACDate to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateDate($date, $context=null) {

        $preID = "";
        
        // Get languages from the constellation
        $list = $this->constellation->getDateList();
        // If there is a context, use that (in list form) instead, and add pre-id
        if ($context != null) {
            $list = $context->getDateList();
            $preID = $context->getID() . ":";
        }
        
        return $this->validateGeneric($date, $list, "date", $preID);
    }
    
    /**
     * Validate a Function
     * 
     * @param \snac\data\SNACFunction $fn SNACFunction to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateFunction($fn, $context=null) {
        return $this->validateGeneric($fn, $this->constellation->getFunctions(), "fn");
    }
    
    /**
     * Validate a gender
     * 
     * @param \snac\data\Gender $gender Gender to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateGender($gender, $context=null) {
        return $this->validateGeneric($gender, $this->constellation->getGenders(), "gender");
    }
    
    /**
     * Validate a general context
     * 
     * @param \snac\data\GeneralContext $gc GeneralContext to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateGeneralContext($gc, $context=null) {
        return $this->validateGeneric($gc, $this->constellation->getGeneralContexts(), "gc");
    }
    
    /**
     * Validate a language
     * 
     * @param \snac\data\Language $lang Language to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateLanguage($lang, $context=null) {
        $preID = "";

        // Get languages from the constellation
        $list = $this->constellation->getLanguagesUsed();
        // If there is a context, use that (in list form) instead, and add pre-id
        if ($context != null) {
            $list = array($context->getLanguage());
            $preID = $context->getID() . ":";
        }
        
        return $this->validateGeneric($lang, $list, "lang", $preID);
    }
    
    /**
     * Validate a legal status
     * 
     * @param \snac\data\LegalStatus $legalStatus LegalStatus to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateLegalStatus($legalStatus, $context=null) {
        return $this->validateGeneric($legalStatus, $this->constellation->getLegalStatuses(), "legalStatus");
    }
    

    
    /**
     * Validate a Maintenance Event
     * 
     * @param \snac\data\MaintenanceEvent $event MaintenanceEvent to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateMaintenanceEvent($event, $context=null) {
        return $this->validateGeneric($event, $this->constellation->getMaintenanceEvents(), "event");
    }
    
    /**
     * Validate a Mandate
     * 
     * @param \snac\data\Mandate $mandate Mandate to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateMandate($mandate, $context=null) {
        return $this->validateGeneric($mandate, $this->constellation->getMandates(), "mandate");
    }
    
    /**
     * Validate a Name Entry
     * 
     * @param \snac\data\NameEntry $nameEntry NameEntry to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateNameEntry($nameEntry, $context=null) {
        // If the nameEntry is null, stop and succeed (leaf node)
        if ($nameEntry == null) {
            return true;
        }
        
        // Set success to be true by default
        $success = true;
        
        // If this nameEntry has no ID, but a subobject does, then success is false
        if ($nameEntry->getID() == null) {
            $success = $success && $this->checkLanguage($nameEntry);
            $success = $success && $this->checkDates($nameEntry);
            $success = $success && $this->checkSCM($nameEntry);
            foreach($nameEntry->getContributors() as $contributor) {
                if ($contributor != null) {
                    if ($contributor->getID() != null) {
                        $this->addError("NameEntry without ID has Contributor with ID", $nameEntry);
                        $success = false;
                    } else {
                        $success = $success && $this->checkSCM($contributor);
                    }
                }
            }
            return $success;
        }
        
        // If this nameEntry has already been seen, then success is false
        if (in_array($nameEntry->getID(), $this->seen["nameEntry"])) {
            $this->addError("ID used multiple times", $nameEntry);
            $success = false;
        }
        
        // Validate this nameEntry's ID against the real nameEntry from the database, and
        // validate all it's subelements against their counterparts from the database
        foreach ($this->constellation->getNameEntries() as $i => $current) {
            if ($nameEntry->getID() == $current->getID()) {
                array_push($this->seen["nameEntry"], $nameEntry->getID());
                // Validate the subelements
                $success = $success && $this->validateLanguage($nameEntry->getLanguage(), $current);
                $success = $success && $this->validateAllDates($nameEntry, $current);
                foreach ($nameEntry->getContributors() as $contributor) {
                    $success = $success && $this->validateContributor($contributor, $current);
                }
                $success = $success && $this->validateAllSCM($nameEntry, $current);
            }
        }
        
        // Return success
        return $success;
    }
    
    /**
     * Validate a Nationality
     * 
     * @param \snac\data\Nationality $nationality Nationality  to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateNationality($nationality, $context=null) {
        return $this->validateGeneric($nationality, $this->constellation->getNationalities(), "nationality");
    }
    
    /**
     * Validate an Occupation
     * 
     * @param \snac\data\Occupation $occupation Occupation to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateOccupation($occupation, $context=null) {
        return $this->validateGeneric($occupation, $this->constellation->getOccupations(), "occupation");
    }
    
    /**
     * validate an Other Record ID
     * 
     * @param \snac\data\SameAs $other OtherID  to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateOtherRecordID($other, $context=null) {
        return $this->validateGeneric($other, $this->constellation->getOtherRecordIDs(), "otherID");
    }
    
    /**
     * Validate a Place
     * 
     * @param \snac\data\Place $place Place to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validatePlace($place, $context=null) {
        return $this->validateGeneric($place, $this->constellation->getPlaces(), "place");
    }
    
    /**
     * Validate a ConstellationRelation
     * 
     * @param \snac\data\ConstellationRelation $relation ConstellationRelation  to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateRelation($relation, $context=null) {
        return $this->validateGeneric($relation, $this->constellation->getRelations(), "constellationRelation");
    }
    
    /**
     * Validate a Resource Relation
     * 
     * @param \snac\data\ResourceRelation $relation ResourceRelation to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateResourceRelation($relation, $context=null) {
        return $this->validateGeneric($relation, $this->constellation->getResourceRelations(), "resourceRelation");
    }
    
    /**
     * Validate a SCM Object
     * 
     * @param \snac\data\SNACControlMetadata $scm Metadata to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateSNACControlMetadata($scm, $context=null) {
        if ($scm == null)
            return true;
        
        if ($scm->getID() == null) {
            if (($scm->getCitation() == null  ||
                        ($scm->getCitation() != null && $scm->getCitation()->getID() == null)) 
                    && ($scm->getLanguage() == null ||
                        ($scm->getLanguage() != null && $scm->getLanguage()->getID() == null)))
                return true;
            else {
                $this->addError("SNACControlMetadata with no ID has elements with ID", $scm);
                return false;
            }
        }
        
        if (in_array($scm->getID(), $this->seen["scm"])) {
            $this->addError("ID used multiple times", $scm);
            return false;
        }
    
        $scmList = $this->constellation->getSNACControlMetadata();
        if ($context != null) {
            $scmList = $context->getSNACControlMetadata();
        }
        foreach ($scmList as $i => $current) {
            if ($scm->getID() == $current->getID()) {
                array_push($this->seen["scm"], $scm->getID());
                $success = $this->validateSource($scm->getCitation(), $current);
                $success = $success && $this->validateLanguage($scm->getLanguage(), $current);
                return $success;
            }
        }
    
        return false;
    }
    
    /**
     * Validate a Source
     * 
     * @param \snac\data\Source $source Source to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateSource($source, $context=null) {

        $preID = "";
        
        // Get sources from the constellation
        $list = $this->constellation->getSources();
        // If there is a context, use that (in list form) instead, and add pre-id
        if ($context != null) {
            $list = array($context->getSource());
            $preID = $context->getID() . ":";
        }
        
        return $this->validateGenericWithLanguage($source, $list, "source", $preID);
        
    }
    
    /**
     * Validate a StructureOrGenealogy
     * 
     * @param \snac\data\StructureOrGenealogy $sog StructureOrGenealogy to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateStructureOrGenealogy($sog, $context=null) {
        return $this->validateGeneric($sog, $this->constellation->getStructureOrGenealogies(), "SoG");
    }
    
    /**
     * Validate a Subject
     * 
     * @param \snac\data\Subject $subject Subject to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateSubject($subject, $context=null) {
        return $this->validateGeneric($subject, $this->constellation->getSubjects(), "subject");
    }
    

    /**
     * Validate a Contributor
     *
     * @param \snac\data\Contributor $contributor Contributor to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateContributor($contributor, $context=null) {
        // Can not get here without context
        if ($context == null) {
            $this->addError("Invalid placement of contributor", $contributor);
            return false;
        }
        
        $preID = $context->getID() . ":";

        return $this->validateGeneric($contributor, $context->getContributors(), "contributor", $preID);
    }
    
    /**
     * Validate a Term 
     * 
     * @param \snac\data\Term $term Term to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean always returns true, since this validator does not validate terms
     */
    public function validateTerm($term, $context=null) {
        return true; // not validating terms here
    }
    
    /**
     * Validate a GeoTerm
     *
     * @param \snac\data\GeoTerm $geoTerm GeoTerm to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateGeoTerm($geoTerm, $context=null) {
        return true; // not validation geoTerms here
    }
    
}
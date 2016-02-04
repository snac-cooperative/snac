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
     * Validate a biog hist
     * 
     * @param \snac\data\BiogHist $biogHist BiogHist to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateBiogHist($biogHist, $context=null) {
        if ($biogHist == null) 
            return true;
        
        if ($biogHist->getID() == null) {
            if ($biogHist->getLanguage() == null)
                return true;
            else {
                $this->addError("BiogHist with no ID has Language with ID", $biogHist);
                return false;
            }
        }
        
        if (in_array($biogHist->getID(), $this->seen["biogHist"])) {
            $this->addError("ID used multiple times", $biogHist);
            return false;
        }
        
        foreach ($this->constellation->getBiogHistList() as $i => $current) {
            if ($biogHist->getID() == $current->getID()) {
                array_push($this->seen["biogHist"], $biogHist->getID());
                // Validate the subelement
                return $this->validateLanguage($biogHist->getLanguage(), $current);
            }
        }
        
        return false;
    }
    
    /**
     * Validate a Convention Declaration
     * 
     * @param \snac\data\ConventionDeclaration $cd ConventionDeclaration to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateConventionDeclaration($cd, $context=null) {
        if ($cd == null || $cd->getID() == null)
            return true;
        
        if (in_array($cd->getID(), $this->seen["cd"])) {
            $this->addError("ID used multiple times", $cd);
            return false;
        }
        
        foreach ($this->constellation->getConventionDeclarations() as $i => $current) {
            if ($cd->getID() == $current->getID()) {
                array_push($this->seen["cd"], $cd->getID());
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate a Date
     * 
     * @param \snac\data\SNACDate $date SNACDate to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateDate($date, $context=null) {
        if ($date == null || $date->getID() == null)
            return true;
        
        $preID = "";    
        if ($context != null)
            $preID = $context->getID() . ":";
    
        if (in_array($date->getID(), $preID . $this->seen["date"])) {
            $this->addError("ID used multiple times", $date);
            return false;
        }
        if ($context != null) {
            foreach ($context->getDateList() as $i => $current) {
                if ($date->getID() == $current->getID()) {
                    array_push($preID . $this->seen["date"], $date->getID());
                    return true;
                }
            }
        } else {
            foreach ($this->constellation->getDateList() as $i => $current) {
                if ($date->getID() == $current->getID()) {
                    array_push($this->seen["date"], $date->getID());
                    return true;
                }
            }
        }
    
        return false;
    }
    
    /**
     * Validate a Function
     * 
     * @param \snac\data\SNACFunction $fn SNACFunction to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateFunction($fn, $context=null) {
        if ($fn == null || $fn->getID() == null)
            return true;
        
        if (in_array($fn->getID(), $this->seen["fn"])) {
            $this->addError("ID used multiple times", $fn);
            return false;
        }
        
        foreach ($this->constellation->getFunctions() as $i => $current) {
            if ($fn->getID() == $current->getID()) {
                array_push($this->seen["fn"], $fn->getID());
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate a gender
     * 
     * @param \snac\data\Gender $gender Gender to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateGender($gender, $context=null) {
        if ($gender == null || $gender->getID() == null)
            return true;
        
        if (in_array($gender->getID(), $this->seen["gender"])) {
            $this->addError("ID used multiple times", $gender);
            return false;
        }
        
        foreach ($this->constellation->getGenders() as $i => $current) {
            if ($gender->getID() == $current->getID()) {
                array_push($this->seen["gender"], $gender->getID());
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate a general context
     * 
     * @param \snac\data\GeneralContext $gc GeneralContext to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateGeneralContext($gc, $context=null) {
        if ($gc == null || $gc->getID() == null)
            return true;
        
        if (in_array($gc->getID(), $this->seen["gc"])) {
            $this->addError("ID used multiple times", $gc);
            return false;
        }
        
        foreach ($this->constellation->getGeneralContexts() as $i => $current) {
            if ($gc->getID() == $current->getID()) {
                array_push($this->seen["gc"], $gc->getID());
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate a language
     * 
     * @param \snac\data\Language $lang Language to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateLanguage($lang, $context=null) {
        if ($lang == null || $lang->getID() == null)
            return true;
        
        $preID = "";    
        if ($context != null)
            $preID = $context->getID() . ":";
    
        if (in_array($lang->getID(), $preID . $this->seen["lang"])) {
            $this->addError("ID used multiple times", $lang);
            return false;
        }
        if ($context != null) {
            if ($context->getLanguage()->getID() == $current->getID()) {
                    array_push($preID . $this->seen["lang"], $lang->getID());
                    return true;
            }
        } else {
            foreach ($this->constellation->getLanguagesUsed() as $i => $current) {
                if ($lang->getID() == $current->getID()) {
                    array_push($this->seen["lang"], $lang->getID());
                    return true;
                }
            }
        }
    
        return false;
    }
    
    /**
     * Validate a legal status
     * 
     * @param \snac\data\LegalStatus $legalStatus LegalStatus to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateLegalStatus($legalStatus, $context=null) {
        if ($legalStatus == null || $legalStatus->getID() == null)
            return true;
        
        if (in_array($legalStatus->getID(), $this->seen["legalStatus"])) {
            $this->addError("ID used multiple times", $legalStatus);
            return false;
        }
        
        foreach ($this->constellation->getLegalStatuses() as $i => $current) {
            if ($legalStatus->getID() == $current->getID()) {
                array_push($this->seen["legalStatus"], $legalStatus->getID());
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate a Maintenance Event
     * 
     * @param \snac\data\MaintenanceEvent $event MaintenanceEvent to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateMaintenanceEvent($event, $context=null) {
        if ($event == null || $event->getID() == null)
            return true;
        
        if (in_array($event->getID(), $this->seen["event"])) {
            $this->addError("ID used multiple times", $event);
            return false;
        }
        
        foreach ($this->constellation->getMaintenanceEvents() as $i => $current) {
            if ($event->getID() == $current->getID()) {
                array_push($this->seen["event"], $event->getID());
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate a Mandate
     * 
     * @param \snac\data\Mandate $mandate Mandate to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateMandate($mandate, $context=null) {
        if ($mandate == null || $mandate->getID() == null)
            return true;
        
        if (in_array($mandate->getID(), $this->seen["mandate"])) {
            $this->addError("ID used multiple times", $mandate);
            return false;
        }
        
        foreach ($this->constellation->getMandates() as $i => $current) {
            if ($mandate->getID() == $current->getID()) {
                array_push($this->seen["mandate"], $mandate->getID());
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate a Name Entry
     * 
     * @param \snac\data\NameEntry $nameEntry NameEntry to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateNameEntry($nameEntry, $context=null) {
        if ($nameEntry == null)
            return true;

        if ($nameEntry->getID() == null) {
            if (empty($nameEntry->getDateList()) && $nameEntry->getLanguage() == null)
                return true;
            else {
                $this->addError("NameEntry with no ID has elements with IDs", $nameEntry);
                return false;
            }
        }
        
        if (in_array($nameEntry->getID(), $this->seen["nameEntry"])) {
            $this->addError("ID used multiple times", $nameEntry);
            return false;
        }
    
        foreach ($this->constellation->getNameEntries() as $i => $current) {
            if ($nameEntry->getID() == $current->getID()) {
                array_push($this->seen["nameEntry"], $nameEntry->getID());
                $success = $this->validateLanguage($nameEntry->getLanguage(), $current);
                foreach ($nameEntry->getDateList() as $date) {
                    $success = $success && $this->validateDate($date, $current);
                }
                return $success;
            }
        }
        
        //TODO Validate Contributor objects?
        return false;
    }
    
    /**
     * Validate a Nationality
     * 
     * @param \snac\data\Nationality $nationality Nationality  to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateNationality($nationality, $context=null) {
        if ($nationality == null || $nationality->getID() == null)
            return true;
        
        if (in_array($nationality->getID(), $this->seen["nationality"])) {
            $this->addError("ID used multiple times", $nationality);
            return false;
        }
        
        if ($this->constellation->getNationality()->getID() == $current->getID()) {
            array_push($this->seen["nationality"], $nationality->getID());
            return true;
        }
            
        return false;
    }
    
    /**
     * Validate an Occupation
     * 
     * @param \snac\data\Occupation $occupation Occupation to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateOccupation($occupation, $context=null) {
        if ($occupation == null || $occupation->getID() == null)
            return true;
        
        if (in_array($occupation->getID(), $this->seen["occupation"])) {
            $this->addError("ID used multiple times", $occupation);
            return false;
        }
        
        foreach ($this->constellation->getOccupations() as $i => $current) {
            if ($occupation->getID() == $current->getID()) {
                array_push($this->seen["occupation"], $occupation->getID());
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * validate an Other Record ID
     * 
     * @param \snac\data\SameAs $other OtherID  to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateOtherRecordID($other, $context=null) {
        if ($other == null || $other->getID() == null)
            return true;
        
        if (in_array($other->getID(), $this->seen["other"])) {
            $this->addError("ID used multiple times", $other);
            return false;
        }
        
        foreach ($this->constellation->getOtherRecordIDs() as $i => $current) {
            if ($other->getID() == $current->getID()) {
                array_push($this->seen["other"], $other->getID());
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate a Place
     * 
     * @param \snac\data\Place $place Place to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validatePlace($place, $context=null) {
        if ($place == null)
            return true;
        
        if ($place->getID() == null) {
            if (empty($place->getDateList()))
                return true;
            else {
                $this->addError("Place with no ID has elements with IDs", $place);
                return false;
            }
        }
        
        if (in_array($place->getID(), $this->seen["place"])) {
            $this->addError("ID used multiple times", $place);
            return false;
        }
    
        foreach ($this->constellation->getPlaces() as $i => $current) {
            if ($place->getID() == $current->getID()) {
                array_push($this->seen["place"], $place->getID());
                $success = true;
                foreach ($place->getDateList() as $date) {
                    $success = $success && $this->validateDate($date, $current);
                }
                return $success;
            }
        }
    
        return false;
    }
    
    /**
     * Validate a ConstellationRelation
     * 
     * @param \snac\data\ConstellationRelation $relation ConstellationRelation  to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateRelation($relation, $context=null) {
        if ($relation == null)
            return true;
        
        if ($relation->getID() == null) {
            if (empty($relation->getDateList()))
                return true;
            else {
                $this->addError("Constellation Relation with no ID has dates with IDs", $relation);
                return false;
            }
        }
        
        if (in_array($relation->getID(), $this->seen["constellation_relation"])) {
            $this->addError("ID used multiple times", $relation);
            return false;
        }
    
        foreach ($this->constellation->getRelations() as $i => $current) {
            if ($relation->getID() == $current->getID()) {
                array_push($this->seen["constellation_relation"], $relation->getID());
                
                $success = true;
                foreach ($relation->getDateList() as $date) {
                    $success = $success &&
                    $this->validateDate($date, $current);
                }
                return $success;
            }
        }
    
        return false;
    }
    
    /**
     * Validate a Resource Relation
     * 
     * @param \snac\data\ResourceRelation $relation ResourceRelation to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateResourceRelation($relation, $context=null) {
        if ($relation == null || $relation->getID() == null)
            return true;
        
        if (in_array($relation->getID(), $this->seen["resource_relation"])) {
            $this->addError("ID used multiple times", $relation);
            return false;
        }
        
        foreach ($this->constellation->getResourceRelations() as $i => $current) {
            if ($relation->getID() == $current->getID()) {
                array_push($this->seen["resource_relation"], $relation->getID());
                return true;
            }
        }
        
        return false;
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
        if ($source == null)
            return true;
        
        if ($source->getID() == null) {
            if ($source->getLanguage() == null)
                return true;
            else {
                $this->addError("Source with no ID has language with ID", $source);
                return false;
            }
        }
        
        $preID = "";
        if ($context != null)
            $preID = $context->getID() . ":";
        
        if (in_array($source->getID(), $preID . $this->seen["source"])) {
            $this->addError("ID used multiple times", $source);
            return false;
        }
        
        if ($context != null) {
            if ($source->getID() == $context->getSource()->getID()) {
                array_push($this->seen["source"], $preID.$source->getID());
                return $this->validateLanguage($source->getLanguage(), $context->getSource());
            }
        } else {
            foreach ($this->constellation->getSources() as $i => $current) {
                if ($source->getID() == $current->getID()) {
                    array_push($this->seen["source"], $preID.$source->getID());
                    return $this->validateLanguage($source->getLanguage(), $current);
                }
            }
        }
    
        return false;
    }
    
    /**
     * Validate a StructureOrGenealogy
     * 
     * @param \snac\data\StructureOrGenealogy $sog StructureOrGenealogy to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateStructureOrGenealogy($sog, $context=null) {
        if ($sog == null || $sog->getID() == null)
            return true;
        
        if (in_array($sog->getID(), $this->seen["sog"])) {
            $this->addError("ID used multiple times", $sog);
            return false;
        }
        
        foreach ($this->constellation->getStructureOrGenealogies() as $i => $current) {
            if ($sog->getID() == $current->getID()) {
                array_push($this->seen["sog"], $sog->getID());
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate a Subject
     * 
     * @param \snac\data\Subject $subject Subject to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateSubject($subject, $context=null) {
        if ($subject == null || $subject->getID() == null)
            return true;
        
        if (in_array($subject->getID(), $this->seen["subject"])) {
            $this->addError("ID used multiple times", $subject);
            return false;
        }
        
        foreach ($this->constellation->getSubjects() as $i => $current) {
            if ($subject->getID() == $current->getID()) {
                array_push($this->seen["subject"], $subject->getID());
                return true;
            }
        }
        
        return false;
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
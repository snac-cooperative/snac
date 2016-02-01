<?php

/**
 * Operation Validator Class File
 *
 * Contains the operation validator class
 * 
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server\validation\validators;

/**
 * Operation Validator 
 * 
 * Validates that the operations are consistent throughout the constellation object
 *
 * @author Robbie Hott
 *
 */
class OperationValidator extends \snac\server\validation\validators\Validator {
    
    private $constellationOperation = null;
    
    public function __construct() {
        $this->validatorName = "OperationValidator";
        parent::__construct();
    }
    
    private function checkOperationValue($operation) {
        if ($operation == \snac\data\AbstractData::$OPERATION_DELETE ||
                $operation == \snac\data\AbstractData::$OPERATION_INSERT ||
                $operation == \snac\data\AbstractData::$OPERATION_UPDATE ||
                $operation == null)
            return true;
        return false;
                
    }
    
    private function validateOperation($testOp, $parentOp = null) {
        // Set the parent operation, either to the constellation-wide op or
        // to the parameter to the function
        $op = $parentOp;
        if ($op == null) {
            $op = $this->constellationOperation;
        }
        
        if ($op == \snac\data\AbstractData::$OPERATION_DELETE) {
            return ($testOp == \snac\data\AbstractData::$OPERATION_DELETE);
        } else if ($op == \snac\data\AbstractData::$OPERATION_INSERT) {
            return ($testOp == \snac\data\AbstractData::$OPERATION_INSERT);
        } else {
            return true;
        }
        
    }
    
    private function validateAbstractData($object, $context = null) {
        // Null objects very easily success validation
        if ($object == null)
            return true;
        
        $operation = $context;
        if ($operation == null) 
            $operation = $this->constellationOperation;
        
        // Test that the operation is appropriate for its parent, if not give an error
        if ($this->checkOperationValue($object->getOperation())) {
            if (!$this->validateOperation($object->getOperation(), $operation)) {
                // If the operation doesn't validate, then a sub operation has an invalid
                // operation with relation to a parent.  So, we'll message that out:

                $this->addError("Operation \"". $object->getOperation() . "\" cannot follow " .
                        "parent's operation \"". $operation . "\"", $object);
                return false;
            }
            return true;
        }
        $this->addError("Invalid operation", $object);
        return false;
    }
    
    /**
     * Grab global constellation-level information that is needed in the validation
     * 
     * @param \snac\data\Constellation $constellation constellation
     */
    public function setConstellation($constellation) {
        if ($this->checkOperationValue($constellation->getOperation())) {
            $this->constellationOperation = $constellation->getOperation();
            return true;
        }
        return false;
            
    }
    
    /**
     * Validate the root of the constellation
     * 
     * @param \snac\data\Constellation $constellation constellation root to validate
     */
    public function validateRoot($constellation) {
        if ($this->constellationOperation != null)
            return true;
        return false;
    }
    
    /**
     * Validate a biog hist
     * 
     * @param \snac\data\BiogHist $biogHist BiogHist to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateBiogHist($biogHist, $context=null) {
        $success = $this->validateAbstractData($biogHist, $context);
        $success = $success && 
                    $this->validateLanguage($biogHist->getLanguage(), $biogHist->getOperation());
        return $success;
    }
    
    /**
     * Validate a Convention Declaration
     * 
     * @param \snac\data\ConventionDeclaration $cd ConventionDeclaration to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateConventionDeclaration($cd, $context=null) {
        return $this->validateAbstractData($cd, $context);
    }
    
    /**
     * Validate a Date
     * 
     * @param \snac\data\SNACDate $date SNACDate to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateDate($date, $context=null) {
        return $this->validateAbstractData($date, $context);
    }
    
    /**
     * Validate a Function
     * @param \snac\data\SNACFunction $fn SNACFunction to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateFunction($fn, $context=null) {
        return $this->validateAbstractData($fn, $context);
    }
    
    /**
     * Validate a gender
     * @param \snac\data\Gender $gender Gender to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateGender($gender, $context=null) {
        return $this->validateAbstractData($gender, $context);
    }
    
    /**
     * Validate a general context
     * @param \snac\data\GeneralContext $gc GeneralContext to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateGeneralContext($gc, $context=null) {
        return $this->validateAbstractData($gc, $context);
    }
    
    /**
     * Validate a language
     * @param \snac\data\Language $lang Language to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateLanguage($lang, $context=null) {
        return $this->validateAbstractData($lang, $context);
    }
    
    /**
     * Validate a legal status
     * @param \snac\data\LegalStatus $legalStatus LegalStatus to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateLegalStatus($legalStatus, $context=null) {
        return $this->validateAbstractData($legalStatus, $context);
    }
    
    /**
     * Validate a Maintenance Event
     * @param \snac\data\MaintenanceEvent $event MaintenanceEvent to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateMaintenanceEvent($event, $context=null) {
        return $this->validateAbstractData($event, $context);
    }
    
    /**
     * Validate a Mandate
     * @param \snac\data\Mandate $mandate Mandate to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateMandate($mandate, $context=null) {
        return $this->validateAbstractData($mandate, $context);
    }
    
    /**
     * Validate a Name Entry
     * @param \snac\data\NameEntry $nameEntry NameEntry to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateNameEntry($nameEntry, $context=null) {
        $success = $this->validateAbstractData($nameEntry, $context);
        $success = $success &&
                    $this->validateLanguage($nameEntry->getLanguage(), $nameEntry->getOperation());
        foreach ($nameEntry->getDateList() as $date) {
            $success = $success &&
                    $this->validateDate($date, $nameEntry->getOperation());
        }
        //TODO Validate Contributor objects?
        return $success;
    }
    
    /**
     * Validate a Nationality
     * @param \snac\data\Nationality $nationality Nationality  to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateNationality($nationality, $context=null) {
        return $this->validateAbstractData($nationality, $context);
    }
    
    /**
     * Validate an Occupation
     * @param \snac\data\Occupation $occupation Occupation to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateOccupation($occupation, $context=null) {
        return $this->validateAbstractData($occupation, $context);
    }
    
    /**
     * validate an Other Record ID
     * @param \snac\data\SameAs $other OtherID  to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateOtherRecordID($other, $context=null) {
        return $this->validateAbstractData($other, $context);
    }
    
    /**
     * Validate a Place
     * @param \snac\data\Place $place Place to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validatePlace($place, $context=null) {
        $success = $this->validateAbstractData($place, $context);
        foreach ($place->getDateList() as $date) {
            $success = $success &&
                        $this->validateDate($date, $place->getOperation());
        }
        foreach ($place->getPlaceEntries() as $placeEntry) {
            $success = $success &&
                        $this->validatePlaceEntry($placeEntry, $place->getOperation());
        }
        return $success;
    }
    
    /**
     * Validate a ConstellationRelation
     * @param \snac\data\ConstellationRelation $relation ConstellationRelation  to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateRelation($relation, $context=null) {
        $success = $this->validateAbstractData($relation, $context);
        foreach ($relation->getDateList() as $date) {
            $success = $success &&
                        $this->validateDate($date, $relation->getOperation());
        }
        return $success;
    }
    
    /**
     * Validate a Resource Relation
     * @param \snac\data\ResourceRelation $relation ResourceRelation to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateResourceRelation($relation, $context=null) {
        return $this->validateAbstractData($relation, $context);
    }
    
    /**
     * Validate a SCM Object
     * @param \snac\data\SNACControlMetadata $scm Metadata to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateSNACControlMetadata($scm, $context=null) {
        $success = $this->validateAbstractData($scm, $context);
        $success = $success && $this->validateSource($scm->getCitation(), $scm->getOperation());
        $success = $success && $this->validateLanguage($scm->getLanguage(), $scm->getOperation());
        return $success;
    }
    
    /**
     * Validate a Source
     * @param \snac\data\Source $source Source to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateSource($source, $context=null) {
        $success = $this->validateAbstractData($source, $context);
        $success = $success && 
                    $this->validateLanguage($source->getLanguage(), $source->getOperation());
        return $success;
    }
    
    /**
     * Validate a StructureOrGenealogy
     * @param \snac\data\StructureOrGenealogy $sog StructureOrGenealogy to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateStructureOrGenealogy($sog, $context=null) {
        return $this->validateAbstractData($sog, $context);
    }
    
    /**
     * Validate a Subject
     * @param \snac\data\Subject $subject Subject to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateSubject($subject, $context=null) {
        return $this->validateAbstractData($subject, $context);
    }
    
    /**
     * Validate a Term 
     * @param \snac\data\Term $term Term to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateTerm($term, $context=null) {
        return true; // not validating terms here
    }
    
    /**
     * Validate a PlaceEntry
     * @param \snac\data\PlaceEntry $placeEntry PlaceEntry to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validatePlaceEntry($placeEntry, $context=null) {
        $success = $this->validateAbstractData($placeEntry, $context);
        $success = $success && $this->validatePlaceEntry($placeEntry->getBestMatch(), $placeEntry->getOperation());
        foreach ($placeEntry->getDateList() as $date) {
            $success = $success && $this->validateDate($date, $placeEntry->getOperation());
        }
        foreach ($placeEntry->getMaybeSames() as $subEntry) {
            $success = $success && $this->validatePlaceEntry($subEntry, $placeEntry->getOperation());
        }
        return $success;
        
    }
    
}
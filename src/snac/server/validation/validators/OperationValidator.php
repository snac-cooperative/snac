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
class OperationValidator {
    
    private $constellationOperation = null;
    
    public function __construct() {
        $this->validatorName = "OperationValidator";
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
        if ($this->checkOperationValue($biogHist->getOperation())) {
            return $this->validateOperation($biogHist->getOperation());
        }
        return false;
    }
    
    /**
     * Validate a Convention Declaration
     * 
     * @param \snac\data\ConventionDeclaration $cd ConventionDeclaration to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateConventionDeclaration($cd, $context=null) {
        if ($this->checkOperationValue($cd->getOperation())) {
            return $this->validateOperation($cd->getOperation());
        }
        return false;
    }
    
    /**
     * Validate a Date
     * 
     * @param \snac\data\SNACDate $date SNACDate to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateDate($date, $context=null) {
        if ($this->checkOperationValue($date->getOperation())) {
            return $this->validateOperation($date->getOperation());
        }
        return false;
    }
    
    /**
     * Validate a Function
     * @param \snac\data\SNACFunction $fn SNACFunction to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateFunction($fn, $context=null) {
        if ($this->checkOperationValue($fn->getOperation())) {
            return $this->validateOperation($fn->getOperation());
        }
        return false;
    }
    
    /**
     * Validate a gender
     * @param \snac\data\Gender $gender Gender to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateGender($gender, $context=null) {
        if ($this->checkOperationValue($gender->getOperation())) {
            return $this->validateOperation($gender->getOperation());
        }
        return false;
    }
    
    /**
     * Validate a general context
     * @param \snac\data\GeneralContext $gc GeneralContext to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateGeneralContext($gc, $context=null) {
        if ($this->checkOperationValue($gc->getOperation())) {
            return $this->validateOperation($gc->getOperation());
        }
        return false;
    }
    
    /**
     * Validate a language
     * @param \snac\data\Language $lang Language to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateLanguage($lang, $context=null) {
        if ($this->checkOperationValue($lang->getOperation())) {
            return $this->validateOperation($lang->getOperation());
        }
        return false;
    }
    
    /**
     * Validate a legal status
     * @param \snac\data\LegalStatus $legalStatus LegalStatus to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateLegalStatus($legalStatus, $context=null) {
        if ($this->checkOperationValue($legalStatus->getOperation())) {
            return $this->validateOperation($legalStatus->getOperation());
        }
        return false;
    }
    
    /**
     * Validate a Maintenance Event
     * @param \snac\data\MaintenanceEvent $event MaintenanceEvent to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateMaintenanceEvent($event, $context=null) {
        if ($this->checkOperationValue($event->getOperation())) {
            return $this->validateOperation($event->getOperation());
        }
        return false;
    }
    
    /**
     * Validate a Mandate
     * @param \snac\data\Mandate $mandate Mandate to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateMandate($mandate, $context=null) {
        if ($this->checkOperationValue($mandate->getOperation())) {
            return $this->validateOperation($mandate->getOperation());
        }
        return false;
    }
    
    /**
     * Validate a Name Entry
     * @param \snac\data\NameEntry $nameEntry NameEntry to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateNameEntry($nameEntry, $context=null) {
        if ($this->checkOperationValue($nameEntry->getOperation())) {
            return $this->validateOperation($nameEntry->getOperation());
        }
        return false;
    }
    
    /**
     * Validate a Nationality
     * @param \snac\data\Nationality $nationality Nationality  to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateNationality($nationality, $context=null) {
        if ($this->checkOperationValue($nationality->getOperation())) {
            return $this->validateOperation($nationality->getOperation());
        }
        return false;
    }
    
    /**
     * Validate an Occupation
     * @param \snac\data\Occupation $occupation Occupation to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateOccupation($occupation, $context=null) {
        if ($this->checkOperationValue($occupation->getOperation())) {
            return $this->validateOperation($occupation->getOperation());
        }
        return false;
    }
    
    /**
     * validate an Other Record ID
     * @param \snac\data\SameAs $other OtherID  to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateOtherRecordID($other, $context=null) {
        if ($this->checkOperationValue($other->getOperation())) {
            return $this->validateOperation($other->getOperation());
        }
        return false;
    }
    
    /**
     * Validate a Place
     * @param \snac\data\Place $place Place to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validatePlace($place, $context=null) {
        if ($this->checkOperationValue($place->getOperation())) {
            return $this->validateOperation($place->getOperation());
        }
        return false;
    }
    
    /**
     * Validate a ConstellationRelation
     * @param \snac\data\ConstellationRelation $relation ConstellationRelation  to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateRelation($relation, $context=null) {
        if ($this->checkOperationValue($relation->getOperation())) {
            return $this->validateOperation($relation->getOperation());
        }
        return false;
    }
    
    /**
     * Validate a Resource Relation
     * @param \snac\data\ResourceRelation $relation ResourceRelation to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateResourceRelation($relation, $context=null) {
        if ($this->checkOperationValue($relation->getOperation())) {
            return $this->validateOperation($relation->getOperation());
        }
        return false;
    }
    
    /**
     * Validate a SCM Object
     * @param \snac\data\SNACControlMetadata $scm Metadata to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateSNACControlMetadata($scm, $context=null) {
        if ($this->checkOperationValue($scm->getOperation())) {
            return $this->validateOperation($scm->getOperation());
        }
        return false;
    }
    
    /**
     * Validate a Source
     * @param \snac\data\Source $source Source to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateSource($source, $context=null) {
        if ($this->checkOperationValue($source->getOperation())) {
            return $this->validateOperation($source->getOperation());
        }
        return false;
    }
    
    /**
     * Validate a StructureOrGenealogy
     * @param \snac\data\StructureOrGenealogy $sog StructureOrGenealogy to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateStructureOrGenealogy($sog, $context=null) {
        if ($this->checkOperationValue($sog->getOperation())) {
            return $this->validateOperation($sog->getOperation());
        }
        return false;
    }
    
    /**
     * Validate a Subject
     * @param \snac\data\Subject $subject Subject to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateSubject($subject, $context=null) {
        if ($this->checkOperationValue($subject->getOperation())) {
            return $this->validateOperation($subject->getOperation());
        }
        return false;
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
        if ($this->checkOperationValue($placeEntry->getOperation())) {
            return $this->validateOperation($placeEntry->getOperation());
        }
        return false;
    }
    
}
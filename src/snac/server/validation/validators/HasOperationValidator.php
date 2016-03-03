<?php

/**
 * Has-Operation Validator Class File
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
 * Has-Operation Validator 
 * 
 * Validates that the Constellation has at least one valid operation.
 *
 * @author Robbie Hott
 *
 */
class HasOperationValidator extends \snac\server\validation\validators\Validator {

    
    /**
     * @var boolean $hasOperation Whether or not an operation has been seen on the Constellation
     */
    private $hasOperation = false;
    
    
    /**
     * @var \snac\data\Constellation $constellation the constellation testing
     */
    private $constellation = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->validatorName = "HasOperationValidator";
        $this->hasOperation = false;
        parent::__construct();
    }
    
    /**
     * Post-Validation tear-down
     *
     * Checks to see if any of the Constellation pieces had an operation.  If none did,
     * It will add the error.
     */
    public function postValidation() {
        
        if ($this->hasOperation == false) {
            $this->addError("Constellation object did not have any legal operation value", $this->constellation);
        }
        return;
    }
    
    /**
     * Check whether an operation has a valid value
     * 
     * Checks the parameter to ensure that it is a legal operation and not null
     * 
     * @param string $operation The operation to test
     * @return boolean true if valid, false otherwise
     */
    private function hasOperationValue($operation) {
        if ($operation == \snac\data\AbstractData::$OPERATION_DELETE ||
                $operation == \snac\data\AbstractData::$OPERATION_INSERT ||
                $operation == \snac\data\AbstractData::$OPERATION_UPDATE)
            return true;
        return false;
                
    }
    

    
    /**
     * Check a given data object
     * 
     * Check that the given object, inherited from AbstractData, has an operation that is
     * compatible with either the global constellation operation or the operation passed
     * in as a parameter.
     * 
     * @param \snac\data\AbstractData $object data object to check the operation
     * @param string $context The parent operation, if it exists
     * @return boolean true if valid, false if invalid
     */
    private function validateAbstractData($object, $context = null) {
        // Null objects very easily success validation
        if ($object == null)
            return true;
        
        $success = true;
        
        // Check AbstractData-level objects
        if ($object->getSNACControlMetadata() != null) {
            foreach ($object->getSNACControlMetadata() as $scm) {
                $success = $success && $this->validateSNACControlMetadata($scm, $object->getOperation());
            }
        }
        
        
        // Test that the operation has a value
        if ($this->hasOperationValue($object->getOperation())) {
            
            // If the operation has a value, then set that we have seen at least one
            // operation during this scan.
            $this->hasOperation = true;
            return true;

        }
        return $success;
    }
    
    /**
     * Grab global constellation-level information that is needed in the validation
     * 
     * @param \snac\data\Constellation $constellation constellation
     */
    public function setConstellation($constellation) {
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
        // Test that the operation has a value
        if ($this->hasOperationValue($constellation->getOperation())) {
        
            // If the operation has a value, then set that we have seen at least one
            // operation during this scan.
            $this->hasOperation = true;
            return true;
        
        }
        return false;
    }
    
    /**
     * Validate a biog hist
     * 
     * @param \snac\data\BiogHist $biogHist BiogHist to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
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
     * @return boolean true if valid, false otherwise
     */
    public function validateConventionDeclaration($cd, $context=null) {
        return $this->validateAbstractData($cd, $context);
    }
    
    /**
     * Validate a Date
     * 
     * @param \snac\data\SNACDate $date SNACDate to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateDate($date, $context=null) {
        return $this->validateAbstractData($date, $context);
    }
    
    /**
     * Validate a Function
     * 
     * @param \snac\data\SNACFunction $fn SNACFunction to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateFunction($fn, $context=null) {
        return $this->validateAbstractData($fn, $context);
    }
    
    /**
     * Validate a gender
     * 
     * @param \snac\data\Gender $gender Gender to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateGender($gender, $context=null) {
        return $this->validateAbstractData($gender, $context);
    }
    
    /**
     * Validate a general context
     * 
     * @param \snac\data\GeneralContext $gc GeneralContext to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateGeneralContext($gc, $context=null) {
        return $this->validateAbstractData($gc, $context);
    }
    
    /**
     * Validate a language
     * 
     * @param \snac\data\Language $lang Language to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateLanguage($lang, $context=null) {
        return $this->validateAbstractData($lang, $context);
    }
    
    /**
     * Validate a legal status
     * 
     * @param \snac\data\LegalStatus $legalStatus LegalStatus to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateLegalStatus($legalStatus, $context=null) {
        return $this->validateAbstractData($legalStatus, $context);
    }
    
    /**
     * Validate a Maintenance Event
     * 
     * @param \snac\data\MaintenanceEvent $event MaintenanceEvent to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateMaintenanceEvent($event, $context=null) {
        return $this->validateAbstractData($event, $context);
    }
    
    /**
     * Validate a Mandate
     * 
     * @param \snac\data\Mandate $mandate Mandate to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateMandate($mandate, $context=null) {
        return $this->validateAbstractData($mandate, $context);
    }
    
    /**
     * Validate a Name Entry
     * 
     * @param \snac\data\NameEntry $nameEntry NameEntry to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateNameEntry($nameEntry, $context=null) {
        $success = $this->validateAbstractData($nameEntry, $context);
        $success = $success &&
                    $this->validateLanguage($nameEntry->getLanguage(), $nameEntry->getOperation());
        foreach ($nameEntry->getDateList() as $date) {
            $success = $success &&
                    $this->validateDate($date, $nameEntry->getOperation());
        }
        foreach ($nameEntry->getContributors() as $contributor) {
            $success = $success &&
                    $this->validateContributor($contributor, $nameEntry->getOperation());
        }
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
        return $this->validateAbstractData($nationality, $context);
    }
    
    /**
     * Validate an Occupation
     * 
     * @param \snac\data\Occupation $occupation Occupation to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateOccupation($occupation, $context=null) {
        return $this->validateAbstractData($occupation, $context);
    }
    
    /**
     * validate an Other Record ID
     * 
     * @param \snac\data\SameAs $other OtherID  to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateOtherRecordID($other, $context=null) {
        return $this->validateAbstractData($other, $context);
    }
    
    /**
     * Validate a Place
     * 
     * @param \snac\data\Place $place Place to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validatePlace($place, $context=null) {
        $success = $this->validateAbstractData($place, $context);
        foreach ($place->getDateList() as $date) {
            $success = $success &&
                        $this->validateDate($date, $place->getOperation());
        }
        return $success;
    }
    
    /**
     * Validate a ConstellationRelation
     * 
     * @param \snac\data\ConstellationRelation $relation ConstellationRelation  to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
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
     * 
     * @param \snac\data\ResourceRelation $relation ResourceRelation to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateResourceRelation($relation, $context=null) {
        return $this->validateAbstractData($relation, $context);
    }
    
    /**
     * Validate a SCM Object
     * 
     * @param \snac\data\SNACControlMetadata $scm Metadata to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateSNACControlMetadata($scm, $context=null) {
        $success = $this->validateAbstractData($scm, $context);
        $success = $success && $this->validateSource($scm->getCitation(), $scm->getOperation());
        $success = $success && $this->validateLanguage($scm->getLanguage(), $scm->getOperation());
        return $success;
    }
    
    /**
     * Validate a Source
     * 
     * @param \snac\data\Source $source Source to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateSource($source, $context=null) {
        $success = $this->validateAbstractData($source, $context);
        $success = $success && 
                    $this->validateLanguage($source->getLanguage(), $source->getOperation());
        return $success;
    }
    
    /**
     * Validate a StructureOrGenealogy
     * 
     * @param \snac\data\StructureOrGenealogy $sog StructureOrGenealogy to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateStructureOrGenealogy($sog, $context=null) {
        return $this->validateAbstractData($sog, $context);
    }
    
    /**
     * Validate a Subject
     * 
     * @param \snac\data\Subject $subject Subject to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateSubject($subject, $context=null) {
        return $this->validateAbstractData($subject, $context);
    }
    
    /**
     * Validate a Contributor
     *
     * @param \snac\data\Contributor $contributor Contributor to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateContributor($contributor, $context=null) {
        return $this->validateAbstractData($contributor, $context);
        
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
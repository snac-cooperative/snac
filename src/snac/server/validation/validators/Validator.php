<?php
/**
 * Validator Abstract Class File
 *
 * Contains the validator abstract class
 * 
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server\validation\validators;

use snac\server\validation\ValidationError;
/**
 * Validator Abstract Class
 *
 * Any validator written for the validation engine must extand this abstract class and provide
 * implementation for all methods below.
 * 
 * When extending the Validator class, there are a few conventions a programmer must follow:
 * * The constructor of the child class must set the `$validatorName` field
 * * The constructor of the child class must call the parent constructor, `parent::__construct()`
 * * Any validation errors must be reported by calling the `addError()` method, giving a useful
 * message for the user (string) and the object (inheriting from AbstractData) that caused the
 * validation error.
 *
 * @author Robbie Hott
 *
 */
abstract class Validator {
    
    /**
     * @var string name of this validator
     */
    protected $validatorName;
    
    /**
     * @var \snac\server\validation\ValidationError[] List of errors encountered by this validator
     */
    protected $errors;
    
    /**
     * Constructor
     * 
     * Needed to instantiate errors
     */
    public function __construct() {
        $this->errors = array();
    }
    
    /**
     * Add error to the list of errors
     * 
     * @param string $message Easy-to-understand message for the error
     * @param \snac\data\AbstractData $object The object that caused this error
     */
    protected function addError($message, $object) {
        array_push($this->errors, new ValidationError($message, $object));
    }
    
    /**
     * Get the errors as an associative array
     * 
     * @return \snac\server\validation\ValidationError[] Array of validation errors associated with this validator
     */
    public function getErrorArray() {
        $return = array();
        
        foreach ($this->errors as $i => $error) {
            $return[$i] = $error->toArray();
        }
        
        return $return;
    }
    
    /**
     * Get whether an error occurred in validation
     * 
     * @return boolean true if error occurred, false otherwise
     */
    public function errorOccurred() {
        if (count($this->errors) < 1)
            return false;
        return true;
    }
    
    /**
     * Get the name of this validator
     * 
     * @return string Name of the validator
     */
    public function getName() {
        return $this->validatorName;
    }
    
    /**
     * Set the name of this validator
     * 
     * @param string $name Name of this validator
     */
    public function setName($name) {
        $this->validatorName = $name;
    }
    
    /**
     * Set Constellation root info
     * 
     * Grab global constellation-level information that is needed in the validation
     * 
     * @param \snac\data\Constellation $constellation constellation
     */
    public abstract function setConstellation($constellation);
    
    /**
     * Validate the root of the constellation
     * 
     * @param \snac\data\Constellation $constellation constellation root to validate
     * @return boolean true if valid, false otherwise
     */
    public abstract function validateRoot($constellation);
    
    /**
     * Validate a biog hist
     * 
     * @param \snac\data\BiogHist $biogHist BiogHist to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public abstract function validateBiogHist($biogHist, $context=null);
    
    /**
     * Validate a Convention Declaration
     * 
     * @param \snac\data\ConventionDeclaration $cd ConventionDeclaration to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public abstract function validateConventionDeclaration($cd, $context=null);
    
    /**
     * Validate a Date
     * 
     * @param \snac\data\SNACDate $date SNACDate to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public abstract function validateDate($date, $context=null);
    
    /**
     * Validate a Function
     * 
     * @param \snac\data\SNACFunction $fn SNACFunction to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public abstract function validateFunction($fn, $context=null);
    
    /**
     * Validate a gender
     * 
     * @param \snac\data\Gender $gender Gender to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public abstract function validateGender($gender, $context=null);
    
    /**
     * Validate a general context
     * 
     * @param \snac\data\GeneralContext $gc GeneralContext to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public abstract function validateGeneralContext($gc, $context=null);
    
    /**
     * Validate a language
     * 
     * @param \snac\data\Language $lang Language to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public abstract function validateLanguage($lang, $context=null);
    
    /**
     * Validate a legal status
     * 
     * @param \snac\data\LegalStatus $legalStatus LegalStatus to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public abstract function validateLegalStatus($legalStatus, $context=null);
    
    /**
     * Validate a Maintenance Event
     * 
     * @param \snac\data\MaintenanceEvent $event MaintenanceEvent to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public abstract function validateMaintenanceEvent($event, $context=null);
    
    /**
     * Validate a Mandate
     * 
     * @param \snac\data\Mandate $mandate Mandate to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public abstract function validateMandate($mandate, $context=null);
    
    /**
     * Validate a Name Entry
     * 
     * @param \snac\data\NameEntry $nameEntry NameEntry to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public abstract function validateNameEntry($nameEntry, $context=null);
    
    /**
     * Validate a Nationality
     * 
     * @param \snac\data\Nationality $nationality Nationality  to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public abstract function validateNationality($nationality, $context=null);
    
    /**
     * Validate an Occupation
     * 
     * @param \snac\data\Occupation $occupation Occupation to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public abstract function validateOccupation($occupation, $context=null);
    
    /**
     * validate an Other Record ID
     * 
     * @param \snac\data\SameAs $other OtherID  to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public abstract function validateOtherRecordID($other, $context=null);
    
    /**
     * Validate a Place
     * 
     * @param \snac\data\Place $place Place to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public abstract function validatePlace($place, $context=null);
    
    /**
     * Validate a ConstellationRelation
     * 
     * @param \snac\data\ConstellationRelation $relation ConstellationRelation  to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public abstract function validateRelation($relation, $context=null);
    
    /**
     * Validate a Resource Relation
     * 
     * @param \snac\data\ResourceRelation $relation ResourceRelation to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public abstract function validateResourceRelation($relation, $context=null);
    
    /**
     * Validate a SCM Object
     * 
     * @param \snac\data\SNACControlMetadata $scm Metadata to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public abstract function validateSNACControlMetadata($scm, $context=null);
    
    /**
     * Validate a Source
     * 
     * @param \snac\data\Source $source Source to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public abstract function validateSource($source, $context=null);
    
    /**
     * Validate a StructureOrGenealogy
     * 
     * @param \snac\data\StructureOrGenealogy $sog StructureOrGenealogy to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public abstract function validateStructureOrGenealogy($sog, $context=null);
    
    /**
     * Validate a Subject
     * 
     * @param \snac\data\Subject $subject Subject to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public abstract function validateSubject($subject, $context=null);
    
    /**
     * Validate a Term 
     * 
     * @param \snac\data\Term $term Term to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public abstract function validateTerm($term, $context=null);
    
    /**
     * Validate a PlaceEntry
     * 
     * @param \snac\data\PlaceEntry $placeEntry PlaceEntry to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public abstract function validatePlaceEntry($placeEntry, $context=null);
    
}
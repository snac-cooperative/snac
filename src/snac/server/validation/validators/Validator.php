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

/**
 * Validator Abstract Class
 *
 * Any validator written for the validation engine must extand this abstract class and provide
 * implementation for all methods below.
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
     * Get the name of this validator
     * 
     * @return string Name of the validator
     */
    public function getName() {
        return $this->validatorName;
    }
    
    /**
     * Set the name of this validator
     * @param string $name Name of this validator
     */
    public function setName($name) {
        $this->validatorName = $name;
    }
    
    /**
     * Grab global constellation-level information that is needed in the validation
     * 
     * @param \snac\data\Constellation $constellation constellation
     */
    public function setConstellation($constellation);
    
    /**
     * Validate the root of the constellation
     * 
     * @param \snac\data\Constellation $constellation constellation root to validate
     */
    public function validateRoot($constellation);
    
    /**
     * Validate a biog hist
     * 
     * @param \snac\data\BiogHist $biogHist BiogHist to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateBiogHist($biogHist, $context=null);
    
    /**
     * Validate a Convention Declaration
     * 
     * @param \snac\data\ConventionDeclaration $cd ConventionDeclaration to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateConventionDeclaration($cd, $context=null);
    
    /**
     * Validate a Date
     * 
     * @param \snac\data\SNACDate $date SNACDate to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateDate($date, $context=null);
    
    /**
     * Validate a Function
     * @param \snac\data\SNACFunction $fn SNACFunction to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateFunction($fn, $context=null);
    
    /**
     * Validate a gender
     * @param \snac\data\Gender $gender Gender to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateGender($gender, $context=null);
    
    /**
     * Validate a general context
     * @param \snac\data\GeneralContext $gc GeneralContext to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateGeneralContext($gc, $context=null);
    
    /**
     * Validate a language
     * @param \snac\data\Language $lang Language to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateLanguage($lang, $context=null);
    
    /**
     * Validate a legal status
     * @param \snac\data\LegalStatus $legalStatus LegalStatus to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateLegalStatus($legalStatus, $context=null);
    
    /**
     * Validate a Maintenance Event
     * @param \snac\data\MaintenanceEvent $event MaintenanceEvent to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateMaintenanceEvent($event, $context=null);
    
    /**
     * Validate a Mandate
     * @param \snac\data\Mandate $mandate Mandate to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateMandate($mandate, $context=null);
    
    /**
     * Validate a Name Entry
     * @param \snac\data\NameEntry $nameEntry NameEntry to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateNameEntry($nameEntry, $context=null);
    
    /**
     * Validate a Nationality
     * @param \snac\data\Nationality $nationality Nationality  to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateNationality($nationality, $context=null);
    
    /**
     * Validate an Occupation
     * @param \snac\data\Occupation $occupation Occupation to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateOccupation($occupation, $context=null);
    
    /**
     * validate an Other Record ID
     * @param \snac\data\SameAs $other OtherID  to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateOtherRecordID($other, $context=null);
    
    /**
     * Validate a Place
     * @param \snac\data\Place $place Place to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validatePlace($place, $context=null);
    
    /**
     * Validate a ConstellationRelation
     * @param \snac\data\ConstellationRelation $relation ConstellationRelation  to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateRelation($relation, $context=null);
    
    /**
     * Validate a Resource Relation
     * @param \snac\data\ResourceRelation $relation ResourceRelation to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateResourceRelation($relation, $context=null);
    
    /**
     * Validate a SCM Object
     * @param \snac\data\SNACControlMetadata $scm Metadata to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateSNACControlMetadata($scm, $context=null);
    
    /**
     * Validate a Source
     * @param \snac\data\Source $source Source to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateSource($source, $context=null);
    
    /**
     * Validate a StructureOrGenealogy
     * @param \snac\data\StructureOrGenealogy $sog StructureOrGenealogy to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateStructureOrGenealogy($sog, $context=null);
    
    /**
     * Validate a Subject
     * @param \snac\data\Subject $subject Subject to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateSubject($subject, $context=null);
    
    /**
     * Validate a Term 
     * @param \snac\data\Term $term Term to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validateTerm($term, $context=null);
    
    /**
     * Validate a PlaceEntry
     * @param \snac\data\PlaceEntry $placeEntry PlaceEntry to validate
     * @param mixed[] $context optional Any context information needed for validation
     */
    public function validatePlaceEntry($placeEntry, $context=null);
    
}
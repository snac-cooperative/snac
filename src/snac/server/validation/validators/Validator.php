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
     * @param \snac\data\BiogHist $biogHist
     */
    public function validateBiogHist($biogHist);
    
    /**
     * Validate a Convention Declaration
     * 
     * @param \snac\data\ConventionDeclaration $cd
     */
    public function validateConventionDeclaration($cd);
    
    /**
     * Validate a Date
     * 
     * @param \snac\data\SNACDate $date
     */
    public function validateDate($date);
    
    /**
     * Validate a Function
     * @param \snac\data\SNACFunction $fn
     */
    public function validateFunction($fn);
    
    /**
     * Validate a gender
     * @param \snac\data\Gender $gender
     */
    public function validateGender($gender);
    
    /**
     * Validate a general context
     * @param \snac\data\GeneralContext $gc
     */
    public function validateGeneralContext($gc);
    
    /**
     * Validate a language
     * @param \snac\data\Language $lang
     */
    public function validateLanguage($lang);
    
    /**
     * Validate a legal status
     * @param \snac\data\LegalStatus $legalStatus
     */
    public function validateLegalStatus($legalStatus);
    
    /**
     * Validate a Maintenance Event
     * @param \snac\data\MaintenanceEvent $event
     */
    public function validateMaintenanceEvent($event);
    
    /**
     * Validate a Mandate
     * @param \snac\data\Mandate $mandate
     */
    public function validateMandate($mandate);
    
    /**
     * Validate a Name Entry
     * @param \snac\data\NameEntry $nameEntry
     */
    public function validateNameEntry($nameEntry);
    
    /**
     * Validate a Nationality
     * @param \snac\data\Nationality $nationality
     */
    public function validateNationality($nationality);
    
    /**
     * Validate an Occupation
     * @param \snac\data\Occupation $occupation
     */
    public function validateOccupation($occupation);
    
    public function validateOtherRecordID($other);
    
    public function validatePlace($place);
    
    public function validateRelation($relation);
    
    public function validateResourceRelation($relation);
    
    public function validateSNACControlMetadata($scm);
    
    public function validateSource($source);
    
    public function validateStructureOrGenealogy($sog);
    
    public function validateSubject($subject);
    
    public function validateTerm($term);
    
}
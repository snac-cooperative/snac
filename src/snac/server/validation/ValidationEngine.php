<?php

/**
 * Validation Engine Class File
 *
 * Contains the validation engine class
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server\validation;

/**
 * Validation Engine
 *
 * This class serves as the heart of the validation engine. It provides methods for validating a Constellation 
 * object against specified validators.
 * 
 * @author Robbie Hott
 *        
 */
class ValidationEngine {
    
    /**
     * @var validators\Validator[] Array of validators to test the Constellation against
     */
    private $validators;
    
    /**
     * Constructor
     * 
     * Initializes the validation engine
     */
    public function __construct() {
        $this->validators = array();
    }
    
    /**
     * Add a validator to run in this engine
     * 
     * @param validators\Validator $validator the instantiated validator to add
     * @return boolean true if successfully added validator, false otherwise
     */
    public function addValidator($validator) {
        if ($validator != null && $validator instanceof \snac\server\validation\validators\Validator) {
            array_push($this->validators, $validator);
            return true;
        }
        return false;
    }
    
    /**
     * Validate a Constellation
     * 
     * Validates the constellation passed in the parameter against the list of validators instantiated for this
     * validation engine. 
     * 
     * @param \snac\data\Constellation $constellation
     * @return boolean Successful validation: true on success, false on \Failure
     * @throws \snac\exceptions\SNACValidationException on serious validation errors
     */
    public function validateConstellation($constellation) {
        
        if ($constellation == null || !($constellation instanceof \snac\data\Constellation))
            return false;
        
        // For each validator, look over the constellation and validate it
        foreach ($this->validators as $validator) {
            
            // Give the validator a chance to grab constellation-level data
            $validator->setConstellation($constellation);
            
            // Validate constellation root
            $validator->validateRoot($constellation);
            
            // Validate each piece of the constellation
            foreach ($constellation->getBiogHistList() as $biogHist) {
                $validator->validateBiogHist($biogHist);
            }
            
            foreach ($constellation->getConventionDeclarations() as $cd) {
                $validator->validateConventionDeclaration($cd);
            }
            
            foreach ($constellation->getDateList() as $date) {
                $validator->validateDate($date);
            }
            
            foreach ($constellation->getFunctions() as $fn) {
                $validator->validateFunction($fn);
            }
            
            foreach ($constellation->getGenders() as $gender) {
                $validator->validateGender($gender);
            }
            
            foreach ($constellation->getGeneralContexts() as $gc) {
                $validator->validateGeneralContext($gc);
            }
            
            foreach ($constellation->getLanguagesUsed() as $languageUsed) {
                $validator->validateLanguage($languageUsed);
            }
            
            foreach ($constellation->getLegalStatuses() as $legalStatus) {
                $validator->validateLegalStatus($legalStatus);
            }
            
            foreach ($constellation->getMaintenanceEvents() as $maintenanceEvent) {
                $validator->validateMaintenanceEvent($maintenanceEvent);
            }
            
            foreach ($constellation->getMandates() as $mandate) {
                $validator->validateMandate($mandate);
            }
            
            foreach ($constellation->getNameEntries() as $nameEntry) {
                $validator->validateNameEntry($nameEntry);
            }
            
            foreach ($constellation->getNationalities() as $nationality) {
                $validator->validateNationality($nationality);
            }
            
            foreach ($constellation->getOccupations() as $occupation) {
                $validator->validateOccupation($occupation);
            }
            
            foreach ($constellation->getOtherRecordIDs() as $other) {
                $validator->validateOtherRecordID($other);
            }
            
            foreach ($constellation->getPlaces() as $place) {
                $validator->validatePlace($place);
            }
            
            foreach ($constellation->getRelations() as $relation) {
                $validator->validateRelation($relation);
            }
            
            foreach ($constellation->getResourceRelations() as $relation) {
                $validator->validateResourceRelation($relation);
            }
            
            foreach ($constellation->getSNACControlMetadata() as $scm) {
                $validator->validateSNACControlMetadata($scm);
            }
            
            foreach ($constellation->getSources() as $source) {
                $validator->validateSource($source);
            }
            
            foreach ($constellation->getStructureOrGenealogies() as $sog) {
                $validator->validateStructureOrGenealogy($sog);
            }
            
            foreach ($constellation->getSubjects() as $subject) {
                $validator->validateSubject($subject);
            }
            
        }
        
        
        
        // Successful validation
        return true;
    }
    
}
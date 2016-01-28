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
        
        // Successful validation
        return true;
    }
    
}
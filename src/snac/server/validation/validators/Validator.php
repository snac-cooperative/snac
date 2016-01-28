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
    
}
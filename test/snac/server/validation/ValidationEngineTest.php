<?php
/**
 * Validation Engine Test Class File 
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

use \snac\server\validation\ValidationEngine as ValidationEngine;


/**
 * Server Test Suite 
 * 
 * @author Robbie Hott
 *
 */
class ValidationEngineTest extends PHPUnit_Framework_TestCase {

    /**
     * Test adding a null validator
     */
    public function testAddNullValidator() {
        $ve = new ValidationEngine();
        $this->assertFalse($ve->addValidator(null), "Could add a null validator");
    }
    
    /**
     * Test adding junk as a validator
     */
    public function testAddBadValidators() {
        $ve = new ValidationEngine();
        $this->assertFalse($ve->addValidator("String"), "Could add a string as validator");
        
        $this->assertFalse($ve->addValidator(new \snac\data\Constellation()), "Could add a Constellation as validator");
    }
    
    /**
     * Test validating null
     */
    public function testNullValidation() {
        $ve = new ValidationEngine();
        $this->assertFalse($ve->validateConstellation(null), "Could validate null");
    }
    
    /**
     * Test validating non-constellations
     */
    public function testNonConstellationValidation() {
        $ve = new ValidationEngine();
        $this->assertFalse($ve->validateConstellation("Constellation")), "Could validate a string as Constellation");
        $this->assertFalse($ve->validateConstellation(new \snac\data\BiogHist())), "Could validate a a BiogHist as Constellation");
    }
   

}

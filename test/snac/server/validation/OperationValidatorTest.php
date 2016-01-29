<?php
/**
 * Operation Validator Test Class File 
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

use \snac\server\validation\ValidationEngine as ValidationEngine;
use \snac\server\validation\validators\OperationValidator;


/**
 * OperationValidator Test Suite 
 * 
 * @author Robbie Hott
 *
 */
class OperationValidatorTest extends PHPUnit_Framework_TestCase {
    
    /**
     * 
     * @var \snac\server\validation\ValidationEngine The validation engine
     */
    private $ve;
    
    public function setUp() {
        $this->ve = new ValidationEngine();
        $operationValidator = new OperationValidator();
        $this->ve->addValidator($operationValidator);
        
    }

    /**
     * Test validating an empty constellation
     */
    public function testValidateEmptyConstellation() {
        $this->assertTrue($this->ve->validateConstellation(new \snac\data\Constellation()), 
                "Could not validate an empty constellation");
    }
    
}

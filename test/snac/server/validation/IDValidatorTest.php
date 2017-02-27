<?php
/**
 * ID Validator Test Class File 
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace test\snac\server\validation;

use \snac\server\validation\ValidationEngine as ValidationEngine;
use snac\server\validation\validators\IDValidator;


/**
 * IDValidator Test Suite 
 * 
 * @author Robbie Hott
 *
 */
class IDValidatorTest extends \PHPUnit\Framework\TestCase {
    
    /**
     * 
     * @var \snac\server\validation\ValidationEngine The validation engine
     */
    private $ve;
    
    /**
     * {@inheritDoc}
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    public function setUp() {
        $this->ve = new ValidationEngine();
        $idValidator = new IDValidator();
        $this->ve->addValidator($idValidator);
        
    }

    /**
     * Test validating an empty constellation
     */
    public function testValidateEmptyConstellation() {
        $this->assertTrue($this->ve->validateConstellation(new \snac\data\Constellation()), 
                "Could not validate an empty constellation");
    }
    
    /**
     * Test validating a constellation with nothing in it, but an operation
     */
    public function testValidateConstellationNoComponentsNoID() {
        $constellation = new \snac\data\Constellation();
        $this->assertTrue($this->ve->validateConstellation($constellation), 
                "Could not validate an empty constellation with no IDs");
        
    }
    
}

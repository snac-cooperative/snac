<?php
/**
 * Has-Operation Validator Test Class File 
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

use \snac\server\validation\ValidationEngine as ValidationEngine;
use \snac\server\validation\validators\HasOperationValidator;


/**
 * HasOperationValidator Test Suite 
 * 
 * @author Robbie Hott
 *
 */
class HasOperationValidatorTest extends PHPUnit_Framework_TestCase {
    
    /**
     * 
     * @var \snac\server\validation\ValidationEngine The validation engine
     */
    private $ve;
    
    public function setUp() {
        $this->ve = new ValidationEngine();
        $hasOperationValidator = new HasOperationValidator();
        $this->ve->addValidator($hasOperationValidator);
        
    }

    /**
     * Test validating an empty constellation
     */
    public function testValidateEmptyConstellation() {
        try {
            $this->assertFalse($this->ve->validateConstellation(new \snac\data\Constellation()), 
                "Empty constellation should fail: no operation");
        } catch (\snac\exceptions\SNACValidationException $e) {
            return;
        }
        $this->fail("Empty Constellation should fail: no operation");
    }
    
    /**
     * Test validating a constellation with nothing in it, but an operation
     */
    public function testValidateConstellationNoComponents() {
        $constellation = new \snac\data\Constellation();
        $this->assertTrue($constellation->setOperation(\snac\data\AbstractData::$OPERATION_INSERT));
        $this->assertTrue($this->ve->validateConstellation($constellation), 
                "Could not validate an empty constellation with insert operation");
        $constellation->setOperation(\snac\data\Constellation::$OPERATION_UPDATE);
        $this->assertTrue($this->ve->validateConstellation($constellation), 
                "Could not validate an empty constellation with update operation");
        $constellation->setOperation(\snac\data\Constellation::$OPERATION_DELETE);
        $this->assertTrue($this->ve->validateConstellation($constellation), 
                "Could not validate an empty constellation with delete operation");
        
    }

    /**
     * Test update constellation with delete name
     */
    public function testValidateConstellationUpdateDelete() {
        $constellation = new \snac\data\Constellation();
        $constellation->setOperation(\snac\data\Constellation::$OPERATION_UPDATE);
        $nameEntry = new \snac\data\NameEntry();
        $nameEntry->setOperation(\snac\data\NameEntry::$OPERATION_DELETE);
        $constellation->addNameEntry($nameEntry);
        try {
            $this->assertTrue($this->ve->validateConstellation($constellation),
                    "Could not validate update constellation with delete nameEntry");
        } catch (\snac\exceptions\SNACValidationException $e) {
            $this->fail("Could not validate update constellation with delete nameEntry: ". $e);
        }
    }
    
    /**
     * Test null constellation operation with insert Subject
     */
    public function testValidateConstellationNullInsert() {
        $constellation = new \snac\data\Constellation();
        $constellation->setOperation(\snac\data\Constellation::$OPERATION_UPDATE);
        $subject = new \snac\data\Subject();
        $subject->setOperation(\snac\data\Subject::$OPERATION_INSERT);
        $constellation->addSubject($subject);
        try {
            $this->assertTrue($this->ve->validateConstellation($constellation),
                    "Could not validate constellation with no operation but subject with insert");
        } catch (\snac\exceptions\SNACValidationException $e) {
            $this->fail("Could not validate constellation with no operation but subject with insert: ". $e);
        }
    }
    
    /**
     * Test update constellation with insert name
     */
    public function testValidateConstellationUpdateInsert() {
        $constellation = new \snac\data\Constellation();
        $constellation->setOperation(\snac\data\Constellation::$OPERATION_UPDATE);
        $nameEntry = new \snac\data\NameEntry();
        $nameEntry->setOperation(\snac\data\NameEntry::$OPERATION_INSERT);
        $constellation->addNameEntry($nameEntry);
        try {
            $this->assertTrue($this->ve->validateConstellation($constellation),
                    "Could not validate update constellation with insert nameEntry");
        } catch (\snac\exceptions\SNACValidationException $e) {
            $this->fail("Could not validate update constellation with insert nameEntry: ". $e);
        }
    }
    

    /**
     * Test update constellation with update name
     */
    public function testValidateConstellationUpdateUpdate() {
        $constellation = new \snac\data\Constellation();
        $constellation->setOperation(\snac\data\Constellation::$OPERATION_UPDATE);
        $nameEntry = new \snac\data\NameEntry();
        $nameEntry->setOperation(\snac\data\NameEntry::$OPERATION_UPDATE);
        $constellation->addNameEntry($nameEntry);
        try {
            $this->assertTrue($this->ve->validateConstellation($constellation),
                    "Could not validate update constellation with update nameEntry");
        } catch (\snac\exceptions\SNACValidationException $e) {
            $this->fail("Could not validate update constellation with update nameEntry: ". $e);
        }
    }
}

<?php
/**
 * Operation Validator Test Class File 
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace test\snac\server\validation;

use \snac\server\validation\ValidationEngine as ValidationEngine;
use \snac\server\validation\validators\OperationValidator;


/**
 * OperationValidator Test Suite 
 * 
 * @author Robbie Hott
 *
 */
class OperationValidatorTest extends \PHPUnit_Framework_TestCase {
    
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
     * Test insert constellation with insert name
     */
    public function testValidateConstellationInsertInsert() {
        $constellation = new \snac\data\Constellation();
        $constellation->setOperation(\snac\data\Constellation::$OPERATION_INSERT);
        $nameEntry = new \snac\data\NameEntry();
        $nameEntry->setOperation(\snac\data\NameEntry::$OPERATION_INSERT);
        $constellation->addNameEntry($nameEntry);
        try {
            $this->assertTrue($this->ve->validateConstellation($constellation), 
                "Could not validate insert constellation with insert nameEntry");
        } catch (\snac\exceptions\SNACValidationException $e) {
            $this->fail("Could not validate insert constellation with insert nameEntry: ". $e);
        }
        
    }
    
    /**
     * Test insert constellation with delete name
     */
    public function testValidateConstellationInsertDelete() {
        $constellation = new \snac\data\Constellation();
        $constellation->setOperation(\snac\data\Constellation::$OPERATION_INSERT);
        $nameEntry = new \snac\data\NameEntry();
        $nameEntry->setOperation(\snac\data\NameEntry::$OPERATION_DELETE);
        $constellation->addNameEntry($nameEntry);
        try {
            $this->assertFalse($this->ve->validateConstellation($constellation), 
                "Could validate insert constellation with delete nameEntry");
        } catch (\snac\exceptions\SNACValidationException $e) {
            // We should get here!  Expecting an exception
            $this->assertCount(1, $this->ve->getErrors(), "Did not get the right number of errors");
        }
        
    }
    
    /**
     * Test insert constellation with update name
     */
    public function testValidateConstellationInsertUpdate() {
        $constellation = new \snac\data\Constellation();
        $constellation->setOperation(\snac\data\Constellation::$OPERATION_INSERT);
        $nameEntry = new \snac\data\NameEntry();
        $nameEntry->setOperation(\snac\data\NameEntry::$OPERATION_UPDATE);
        $constellation->addNameEntry($nameEntry);
        try {
            $this->assertFalse($this->ve->validateConstellation($constellation), 
                "Could validate insert constellation with update nameEntry");
        } catch (\snac\exceptions\SNACValidationException $e) {
            // We should get here!  Expecting an exception
            $this->assertCount(1, $this->ve->getErrors(), "Did not get the right number of errors");
        }
        
    }
    
    /**
     * Test delete constellation with delete name
     */
    public function testValidateConstellationDeleteDelete() {
        $constellation = new \snac\data\Constellation();
        $constellation->setOperation(\snac\data\Constellation::$OPERATION_DELETE);
        $nameEntry = new \snac\data\NameEntry();
        $nameEntry->setOperation(\snac\data\NameEntry::$OPERATION_DELETE);
        $constellation->addNameEntry($nameEntry);
        try {
            $this->assertTrue($this->ve->validateConstellation($constellation), 
                "Could not validate delete constellation with delete nameEntry");
        } catch (\snac\exceptions\SNACValidationException $e) {
            $this->fail("Could not validate delete constellation with delete nameEntry: ". $e);
        }
    }
    
    /**
     * Test delete constellation with insert name
     */
    public function testValidateConstellationDeleteInsert() {
        $constellation = new \snac\data\Constellation();
        $constellation->setOperation(\snac\data\Constellation::$OPERATION_DELETE);
        $nameEntry = new \snac\data\NameEntry();
        $nameEntry->setOperation(\snac\data\NameEntry::$OPERATION_INSERT);
        $constellation->addNameEntry($nameEntry);
        try {
            $this->assertFalse($this->ve->validateConstellation($constellation), 
                "Could validate delete constellation with insert nameEntry");
        } catch (\snac\exceptions\SNACValidationException $e) {
            // We should get here!  Expecting an exception
            $this->assertCount(1, $this->ve->getErrors(), "Did not get the right number of errors");
        }
    
    }
    
    /**
     * Test delete constellation with update name
     */
    public function testValidateConstellationDeleteUpdate() {
        $constellation = new \snac\data\Constellation();
        $constellation->setOperation(\snac\data\Constellation::$OPERATION_DELETE);
        $nameEntry = new \snac\data\NameEntry();
        $nameEntry->setOperation(\snac\data\NameEntry::$OPERATION_UPDATE);
        $constellation->addNameEntry($nameEntry);
        try {
            $this->assertFalse($this->ve->validateConstellation($constellation), 
                "Could validate delete constellation with update nameEntry");
        } catch (\snac\exceptions\SNACValidationException $e) {
            // We should get here!  Expecting an exception
            $this->assertCount(1, $this->ve->getErrors(), "Did not get the right number of errors");
        }
    
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

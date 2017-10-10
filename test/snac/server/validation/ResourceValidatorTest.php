<?php
/**
 * Resource Validator Test Class File 
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace test\snac\server\validation;

use \snac\server\validation\ValidationEngine as ValidationEngine;
use \snac\server\validation\validators\ResourceValidator;


/**
 * ResourceValidator Test Suite 
 * 
 * @author Robbie Hott
 *
 */
class ResourceValidatorTest extends \PHPUnit\Framework\TestCase {
    
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
        $validator = new ResourceValidator();
        $this->ve->addValidator($validator);
        
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

    /**
     * Test validating a constellation with null resource
     */
    public function testValidateConstellationNullResource() {
        $constellation = new \snac\data\Constellation();
        $resource = null;
        $resourceRelation = new \snac\data\ResourceRelation();
        $constellation->addResourceRelation($resourceRelation);
        try {
            $this->assertFalse($this->ve->validateConstellation($constellation), 
            "Constellation with null resource validated when it shouldn't have");
        } catch (\Exception $e) {
            // catching an exception is good
            $this->assertNotEmpty($this->ve->getErrors());
        }
    }

    /**
     * Test validating a constellation with empty resource
     */
    public function testValidateConstellationEmptyResource() {
        $constellation = new \snac\data\Constellation();
        $resource = new \snac\data\Resource();
        $resourceRelation = new \snac\data\ResourceRelation();
        $resourceRelation->setResource($resource);
        $constellation->addResourceRelation($resourceRelation);
        try {
            $this->assertFalse($this->ve->validateConstellation($constellation), 
                "Constellation with empty resource validated when it shouldn't have");
        } catch (\Exception $e) {
            // catching an exception is good
            $this->assertNotEmpty($this->ve->getErrors());
        }
    }

    /**
     * Test validating a constellation with resource containing id/version
     */
    public function testValidateConstellationWithResource() {
        $constellation = new \snac\data\Constellation();
        $resource = new \snac\data\Resource();
        $resource->setID(1);
        $resource->setVersion(2);
        $resourceRelation = new \snac\data\ResourceRelation();
        $resourceRelation->setResource($resource);
        $constellation->addResourceRelation($resourceRelation);
        $this->assertTrue($this->ve->validateConstellation($constellation), 
                "Couldn't validate constellation with resource containing id and version");
    }
}


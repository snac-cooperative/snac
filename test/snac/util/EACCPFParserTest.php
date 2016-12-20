<?php
/**
 * EAC-CPF Parser Test File
 *
 *
 * License:
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace test\snac\util;

/**
 * EAC-CPF Parser Test Suite
 * 
 * @author Robbie Hott
 *
 */
class EACCPFParserTest extends \PHPUnit_Framework_TestCase {

    /**
     * Test that giving the parser a bad filename will throw the right exception. 
     */
    public function testBadFilename() {
        $parser = new \snac\util\EACCPFParser();
        $parser->setVocabulary(new TestVocabulary());
        try {
            $parser->parseFile("Not-a-valid-filename");
        } catch (\snac\exceptions\SNACParserException $e) {
            // Catching this exception passes
            $this->assertStringStartsWith(
                "file_get_contents(Not-a-valid-filename): failed to open stream: No such file or directory", 
                $e->getMessage(),
                "The wrong exception was encountered in the code, but it still correctly throw the SNACParserException");
        } catch (\Exception $e) {
            $this->fail("Parser threw the wrong exception");
        }
    }

    /**
     * Test that the parser correctly parses a given sample file
     */
    public function testParseFile() {
        $parser = new \snac\util\EACCPFParser();
        $parser->setVocabulary(new TestVocabulary());
        try {
            // Parse the file into an identity
            $identity = $parser->parseFile("test/snac/util/eac-cpf/test1.xml");

            // Check that attributes matched the parsed versions
            $this->assertAttributeEquals("http://n2t.net/ark:/99166/w6kw9c2x", "ark", $identity);

            // Check that the entity type is person
            $this->assertEquals($identity->getEntityType()->getTerm(), "person");

            $this->assertAttributeEquals("SNAC: Social Networks and Archival Context Project", "maintenanceAgency", $identity);

            // Check that the language Used makes it through
            $this->assertNotEmpty($identity->getLanguagesUsed(), "Did not parse any languages used");
            $lang = $identity->getLanguagesUsed()[0];
            
            $this->assertEquals("eng", $lang->getLanguage()->getTerm());
            $this->assertEquals("Latn", $lang->getScript()->getTerm());


        } catch (\snac\exceptions\SNACParserException $e) {
            $this->fail("Hit exception: " . $e->getMessage());
        }

    }
    


    /**
     * Test that the parser correctly parses a given sample file and that munging the test Constellation
     * by toArray and fromArray still produces the same as the parser.
     */
    public function testParserConstellationEquality() {
        $parser = new \snac\util\EACCPFParser();
        $parser->setVocabulary(new TestVocabulary());
        $parser->setConstellationOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        try {
            // Parse the file into an identity
            $identity = $parser->parseFile("test/snac/server/database/test_record.xml");
    
            $identity2 = new \snac\data\Constellation($identity->toArray());
            
            $this->assertTrue($identity->equals($identity2), "The copy is not equal to the original");
            $this->assertTrue($identity2->equals($identity), "The original is not equal to the copy");
    
        } catch (\snac\exceptions\SNACParserException $e) {
            $this->fail("Hit exception: " . $e->getMessage());
        }
    
    }
}

/**
 * Test vocabulary for the parser tests
 * 
 * @author Robbie Hott
 *
 */
class TestVocabulary implements \snac\util\Vocabulary {

    /**
     * {@inheritDoc}
     * @see \snac\util\Vocabulary::getTermByValue()
     */
    public function getTermByValue($value, $type) {
        if ($value == null || $value == "")
            return null;
        
        $term = new \snac\data\Term();
        $term->setTerm($value);
        $term->setURI($type);
        return $term;
    }

    /**
     * {@inheritDoc}
     * @see \snac\util\Vocabulary::getTermByID()
     */
    public function getTermByID($id, $type) {
        if ($id == null || $id == "")
            return null;
        
        $term = new \snac\data\Term();
        $term->setID($id);
        $term->setURI($type);
        return $term;
    }
    
    public function getGeoTermByURI($uri) {
        $geoterm = new \snac\data\GeoTerm();
        $geoterm->setURI($uri);
        return $geoterm;
    }
    
    /**
     * Get a Resource by Resource object
     *
     * @param \snac\data\Resource $resource The resource to search
     * @return \snac\data\Resource|null The resource object found in the database
     */
    public function getResource($resource) {
        return $resource;
    }
}

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

/**
 * EAC-CPF Parser Test Suite
 * 
 * @author Robbie Hott
 *
 */
class EACCPFParserTest extends PHPUnit_Framework_TestCase {

    /**
     * Test that giving the parser a bad filename will throw the right exception. 
     */
    public function testBadFilename() {
        $parser = new \snac\util\EACCPFParser();
        try {
            $parser->parseFile("Not-a-valid-filename");
        } catch (\snac\exceptions\SNACParserException $e) {
            // Catching this exception passes
            $this->assertEquals(
                    "file_get_contents(Not-a-valid-filename): failed to open stream: No such file or directory", 
                    $e->getMessage());
        } catch (\Exception $e) {
            $this->fail("Parser threw the wrong exception");
        }
    }
    
    /**
     * Test that the parser correctly parses a given sample file
     */
    public function testParseFile() {
       $parser = new \snac\util\EACCPFParser();
       try {
           // Parse the file into an identity
           $identity = $parser->parseFile("test/snac/util/eac-cpf/test1.xml");
           
           // Check that attributes matched the parsed versions
           $this->assertAttributeEquals("http://n2t.net/ark:/99166/w6kw9c2x", "ark", $identity);
           $this->assertAttributeEquals("person", "entityType", $identity);
           $this->assertAttributeEquals("SNAC: Social Networks and Archival Context Project", "maintenanceAgency", $identity);
           $this->assertAttributeEquals("English", "constellationLanguage", $identity);
           $this->assertAttributeEquals("eng", "constellationLanguageCode", $identity);
           $this->assertAttributeEquals("Latin Alphabet", "constellationScript", $identity);
           $this->assertAttributeEquals("Latn", "constellationScriptCode", $identity);
       } catch (\snac\exceptions\SNACParserException $e) {
           $this->fail("Hit exception: " . $e->getMessage());
       }

    }
}
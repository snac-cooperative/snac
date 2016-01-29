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
           
           /*
            * $this->assertAttributeEquals("person", "entityType", $identity);
            *
            * Now that we have Term objects, we have to do a little extra work to get a string "person".
            */
           $this->assertEquals($identity->getEntityType()->getTerm(), "person");
           
           $this->assertAttributeEquals("SNAC: Social Networks and Archival Context Project", "maintenanceAgency", $identity);
           
           /*
            * The original code:
            * $this->assertAttributeEquals("English", "constellationLanguage", $identity);
            */ 

           /* 
            * $langList = $identity->getLanguage();
            * printf("\nlang: %s\n", var_export($langList, 1));
            */
           
           /*
            * The new code: If the constellation had English as the first language, this would
            * work. Unfortunately, the constellation has no languages.
            *
            * The test file used above, test1.xml, does not have a constellation language, only
            * languageDeclaration. How did this ever work?
            *
            * Running the parser above yields a constellation with no languages, and biogHist has the language
            * from languageDeclaration. That fits with what the parsing code does. Does AttributeEquals() test
            * constellationLanguage at the top of $identity, or anywhere in $identity? bioHist used to be an
            * array in Constellation.
            */ 
           if ($identity->getLanguage())
           {
               $this->assertEquals($identity->getLanguage()[0]->getTerm(),
                                   "English");
           }
           else
           {
               $this->assertEquals(true, false);
           }
           
           /*
            * Attribute "constellationLanguageCode" not found in object.
            *
            * This needs to use new Language and Term objects
            */ 
           $this->assertAttributeEquals("eng", "constellationLanguageCode", $identity);
           
           /*
            * Need to use new Language and Term to get the script.
            * 
            * Attribute "constellationScript" not found in object.
            */ 
           $this->assertAttributeEquals("Latin Alphabet", "constellationScript", $identity);

           $this->assertAttributeEquals("Latn", "constellationScriptCode", $identity);
           
       } catch (\snac\exceptions\SNACParserException $e) {
           $this->fail("Hit exception: " . $e->getMessage());
       }

    }
}
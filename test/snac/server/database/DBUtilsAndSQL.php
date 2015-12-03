<?php
/**
 * Database Connector Test File
 *
 *
 * License:
 *
 * @author Tom Laudeman
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

  // Is the use stmt part of setup?
  // use \snac\server\database\DatabaseConnector;

/**
 * Database Utils test suite
 * 
 * @author Tom Laudeman
 *
 */
class DatabaseConnectorTest extends PHPUnit_Framework_TestCase {
    
    /*
     * Can we get a random Constellation? 
     */
    public function testRandomConstellation() 
    {
        
        try
        {
            $dbu = new snac\server\database\DBUtil();
        }
        catch (Exception $ex)
        {
            $this->fail("DBUtil object creation failed");
        }
        try
        {
            list($appUserID, $role) = $dbu->getAppUserInfo('twl8n');
        }
        catch (Exception $ex)
        {
            $this->fail("Failed to get appuser info for 'twl8n'");
        }

        try
        {
            $vhInfo = $dbu->demoConstellation();
            $cObj = $dbu->selectConstellation($vhInfo, $appUserID);

            $this->assertNotNull(json_decode($server->getResponse()));

            $message = $e->getMessage();
            $this->assertEquals(
                "pg_prepare(): Query failed: ERROR:  syntax error at or near \"NOT\"\nLINE 1: NOT A POSTGRES STATEMENT;\n        ^",
                substr($message, 0));
        }
        catch (Exception $ex)
        {
            $this->fail("Failed to get a demo constellation");
        }
    }

    /*
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
    */
}
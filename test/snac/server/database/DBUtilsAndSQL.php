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
class DBUtilTest extends PHPUnit_Framework_TestCase {
    
    /*
     * Can we get a random Constellation?
     *
     *
     * https://phpunit.de/manual/current/en/writing-tests-for-phpunit.html#writing-tests-for-phpunit.data-providers
     *
     * Maybe use @dataProvider to test various records from the db for function, subject, etc.
     *
     * Check expected output: $this->expectOutputString()
     *
     * 
     */
    public function testRandomConstellation() 
    {
        printf("\nStarting DBUtil and SQL tests\n");
        $dbu = new snac\server\database\DBUtil();
        $this->assertNotNull($dbu);
        // $this->fail("DBUtil object creation failed");


        list($appUserID, $role) = $dbu->getAppUserInfo('twl8n');
        $this->assertNotNull($appUserID);
        // $this->fail("Failed to get appuser info for 'twl8n'");


        $vhInfo = $dbu->demoConstellation();
        $cObj = $dbu->selectConstellation($vhInfo, $appUserID);
        $this->assertNotNull($cObj);


        // Parse a file, write the data into the db.

        $eParser = new \snac\util\EACCPFParser();
        $constellationObj = $eParser->parseFile("/data/merge/99166-w6f2061g.xml");
        $vhInfo = $dbu->insertConstellation($constellationObj, $appUserID, $role, 'bulk ingest', 'bulk ingest of merged');

        $this->assertNotNull($vhInfo);

        // get the constellation that was just inserted. As of Dec 2015, the inserted and selected
        // constellation won't be identical due to unresolved treatment of place and maintenance data.

        $selectedConstellationObj = $dbu->selectConstellation($vhInfo, $appUserID);
        $this->assertNotNull($selectedConstellationObj);

        /* 
         * examples from other code:
         * $this->assertNotNull(json_decode($server->getResponse()));
         * $this->assertEquals(
         *     "pg_prepare(): Query failed: ERROR:  syntax error at or near \"NOT\"\nLINE 1: NOT A POSTGRES STATEMENT;\n        ^",
         *     substr($message, 0));
         * $this->fail("Failed to get a demo constellation");
         */
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
<?php
/**
 * Database Utility Test File
 *
 *
 * License:
 *
 * @author Tom Laudeman
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

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
        $dbu = new snac\server\database\DBUtil();
        $this->assertNotNull($dbu);


        list($appUserID, $role) = $dbu->getAppUserInfo('system');
        $this->assertNotNull($appUserID);

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

    }
}

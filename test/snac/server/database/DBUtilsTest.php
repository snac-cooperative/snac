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
    public function testDBUtilAll() 
    {
        global $dbu;
        $dbu = new snac\server\database\DBUtil();
        $this->assertNotNull($dbu);
    }

    public function testAppUserInfo()
    {
        global $dbu;
        list($appUserID, $role) = $dbu->getAppUserInfo('system');
        $this->assertNotNull($appUserID);

        $vhInfo = $dbu->demoConstellation();
        $cObj = $dbu->selectConstellation($vhInfo, $appUserID);
        $this->assertNotNull($cObj);

        /* 
         * Make sure that at least selectConstellation() works with reversed key order in the vhInfo arg.
         */ 
        $vhInfo = $dbu->demoConstellation();
        $reverseVhInfo = array('main_id' => $vhInfo['main_id'],
                               'version' => $vhInfo['version']);
        $reverseCObj = $dbu->selectConstellation($reverseVhInfo, $appUserID);
        $this->assertNotNull($reverseCObj);

        // Parse a file, write the data into the db.

        $eParser = new \snac\util\EACCPFParser();
        $constellationObj = $eParser->parseFile("/data/merge/99166-w6f2061g.xml");
        $vhInfo = $dbu->insertConstellation($constellationObj, $appUserID, $role, 'bulk ingest', 'bulk ingest of merged');

        $this->assertNotNull($vhInfo);

        /* 
         * Get the constellation that was just inserted. As of Dec 2015, the inserted and selected
         * constellation won't be identical due to unresolved treatment of place and maintenance data.
         */
        $selectedConstellationObj = $dbu->selectConstellation($vhInfo, $appUserID);
        $this->assertNotNull($selectedConstellationObj);
        
        /*
         * Test that updateVersionHistory() returns a new version, but keeps the same old main_id.
         *
         * How can this test that the constellation was successfully updated? We need more SQL functions to
         * look at parts of the newly updated constellation records in the various tables.
         */ 
        $existingMainId = $vhInfo['main_id'];
        $updatedVhInfo = $dbu->updateConstellation($constellationObj,
                                                   $appUserID,
                                                   $role,
                                                   'needs review',
                                                   'updating constellation for test',
                                                   $existingMainId);
        $this->assertTrue(($vhInfo['version'] < $updatedVhInfo['version']) &&
                          ($vhInfo['main_id'] == $updatedVhInfo['main_id']));

    }
}

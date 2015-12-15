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
    
    /**
     * DBUtil object for this class
     * @var $dbu DBUtil object
     */ 
    private $dbu = null;

    /**
     * Class var to hold the appUserID
     * @var $appUserID holds the appUserID
     */ 
    private $appUserID = null;

    /*
     * Any vars that aren't set up here won't be initialized, even though the other functions seem to run in
     * order. It behaves as though initializing instance vars anywhere except here don't initialize for the
     * whole class, almost as though the class where being instantiated from scratch for each test.
     */ 
    public function __construct() 
    {
        $this->dbu = new snac\server\database\DBUtil();
        list($this->appUserID, $this->role) = $this->dbu->getAppUserInfo('system');
    }

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
    private $tba = false;
    public function testDBUtilAll() 
    {
        $this->assertNotNull($this->dbu);
        $this->tba = true; // testDBUtilAll has run.
        $this->assertTrue($this->tba); // Did testDBUtilAll run?
    }

    public function testAppUserInfo()
    {
        $this->assertNotNull($this->appUserID);
        $this->assertTrue($this->tba); // Did testDBUtilAll run?
    }

    public function testDemoConstellation()
    {
        $vhInfo = $this->dbu->demoConstellation();
        $cObj = $this->dbu->selectConstellation($vhInfo, $this->appUserID);
        $this->assertNotNull($cObj);

        /* 
         * Make sure that at least selectConstellation() works with reversed key order in the vhInfo arg.
         */ 
        $vhInfo = $this->dbu->demoConstellation();
        $reverseVhInfo = array('main_id' => $vhInfo['main_id'],
                               'version' => $vhInfo['version']);
        $reverseCObj = $this->dbu->selectConstellation($reverseVhInfo, $this->appUserID);
        $this->assertNotNull($reverseCObj);
    }
        
    public function testParseToDB()
    {
        // Parse a file, write the data into the db.

        $eParser = new \snac\util\EACCPFParser();
        $constellationObj = $eParser->parseFile("/data/merge/99166-w6f2061g.xml");
        $vhInfo = $this->dbu->insertConstellation($constellationObj, $this->appUserID, $this->role, 'bulk ingest', 'bulk ingest of merged');

        $this->assertNotNull($vhInfo);

        /* 
         * Get the constellation that was just inserted. As of Dec 2015, the inserted and selected
         * constellation won't be identical due to unresolved treatment of place and maintenance data.
         */
        $selectedConstellationObj = $this->dbu->selectConstellation($vhInfo, $this->appUserID);
        $this->assertNotNull($selectedConstellationObj);
        
        /*
         * Test that updateVersionHistory() returns a new version, but keeps the same old main_id.
         *
         * How can this test that the constellation was successfully updated? We need more SQL functions to
         * look at parts of the newly updated constellation records in the various tables.
         */ 
        $existingMainId = $vhInfo['main_id'];
        $updatedVhInfo = $this->dbu->updateConstellation($constellationObj,
                                                         $this->appUserID,
                                                         $this->role,
                                                         'needs review',
                                                         'updating constellation for test',
                                                         $existingMainId);
        $this->assertTrue(($vhInfo['version'] < $updatedVhInfo['version']) &&
                          ($vhInfo['main_id'] == $updatedVhInfo['main_id']));
    }
}

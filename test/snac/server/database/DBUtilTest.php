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
     * Any vars that aren't set up in the constructor won't be initialized, even though the other functions
     * appear to run in order. Initializing instance vars anywhere except the constructor does not initialize
     * for the whole class. phpunit behaves as though the class where being instantiated from scratch for each
     * test.
     *
     * In cases where tests need to happen in order, all the ordered tests are most easily done inside one
     * test, with multiple assertions.
     */ 
    public function __construct() 
    {
        $this->dbu = new snac\server\database\DBUtil();
        list($this->appUserID, $this->role) = $this->dbu->getAppUserInfo('system');
    }

    /*
     * Make sure that table vocabulary has many entries. The real number is probably far larger than 100k, but
     * at least 100k means that someone tried to init the table.
     */ 
    public function testTableVocabularyPopulated()
    {
        /* Verbose:
         * $sql = $this->dbu->sqlObj();
         * $numRows = $sql->countVocabulary();
         *
         * Concise:
         */
        $numRows = $this->dbu->sqlObj()->countVocabulary();
        $this->assertTrue($numRows > 100000);
    }


    /*
     * Can we get a random Constellation?
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
        $this->assertNotNull($this->dbu);
        $this->tba = true; // testDBUtilAll has run.
    }

    public function testAppUserInfo()
    {
        $this->assertNotNull($this->appUserID);
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

        $demo = $this->dbu->demoConstellationList();
        // printf("%s\n", $demo);
        printf("%s\n", count(json_decode($demo)));
        // $this->assertTrue(count($demo) == 100);
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
         * Check a couple of dbInfo values for the Constellation and sub-objects.
         */
        $this->assertTrue($selectedConstellationObj->getDBInfo()['version'] > 0);
        $this->assertTrue($selectedConstellationObj->getDBInfo()['main_id'] > 0);

        /*
         * Optional assertions, depending on if our constellation has function.  This is kind of a
         * weak idea, but until we have a test constellation with all sub-object, this is it.
         */
        if (($fObj = $selectedConstellationObj->getFunctions()))
        {
            $this->assertTrue($fObj->getDBInfo()['version'] > 0);
            $this->assertTrue($fObj->getDBInfo()['main_id'] > 0);
        }
        
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

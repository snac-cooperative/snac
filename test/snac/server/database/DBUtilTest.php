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

    /**
     * Constructor
     *
     * Note about how things are different here in testing world vs normal execution:
     * 
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
        /*
         * Feb 19 2016 Holy cow. This needed to be in DBUtil. This is being down there and here. Only
         * deprecated code will use the values here.
         *
         * A flat list of the appuser.id and related role.id, both are numeric. 
         */ 
        list($this->appUserID, $this->roleID) = $this->dbu->getAppUserInfo('system');
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

        // The returned value is a json string, with 100 top level elements.
        $demo = $this->dbu->demoConstellationList();
        $this->assertTrue(count($demo) == 100);

        /* 
         * Delete a name and verify it. 
         * Need a helper function somewhere to associate object type with database table. 
         */
        $mNObj = $this->dbu->multiNameConstellation($this->appUserID);

        $preDeleteNameCount = count($mNObj->getNameEntries());
        
        /* 
         * printf("\npre count: %s using main_id: %s recordid: %s\n", 
         *        $preDeleteNameCount,
         *        $mNObj->getID(),
         *        $mNObj->getNameEntries()[0]->getID() );
         */
        
        /*
         * We need the new version of the deleted record, which becomes the max(version) of the constellation.
         *
         * Older code used $mNObj->getNameEntries()[0]->getMainID() for the mainID, but it is better that
         * mainID aka main_id exist only in the Constellation object. Or that we have a variable outside the
         * object as we do here.
         *
         * On the other hand, it might be best to delete by sending a complete php object to setDeleted() and
         * that object would contain id, version, mainID (available via getters). This would allow
         * setDeleted() to work without any out-of-band information.
         * 
         */  
        $newVersion = $this->dbu->setDeleted($this->appUserID,
                                             $this->role,
                                             'bulk ingest',
                                             'delete a name, that is: set is_deleted to true',
                                             $mNObj->getID(), // constellation main_id
                                             'name',
                                             $mNObj->getNameEntries()[0]->getID());

        /* 
         * Post delete. The delete operation mints a new version number which is returned by setDeleted().  We
         * combine the new version and the known (and unchanged main_id) to create a new vhInfo associative
         * list. Then we pass that to selectConstellation() to get the current copy of the constellation from
         * the database.
         *
         * Note: constellation object getID() returns the constellation id, not the pre-record id as with
         * getID() for all other data objects.
         * 
         */
        $postDVhInfo = array('version' => $newVersion,
                             'main_id' => $mNObj->getID());
        $postDObj = $this->dbu->selectConstellation($postDVhInfo, $this->appUserID);
        $postDeleteNameCount = count($postDObj->getNameEntries());
        $this->assertTrue($preDeleteNameCount == ($postDeleteNameCount+1));

        $undelVersion = $this->dbu->clearDeleted($this->appUserID,
                                             $this->role,
                                             'bulk ingest',
                                             'un-delete a name, that is: set is_deleted to false',
                                             $mNObj->getID(), // constellation main_id
                                             'name',
                                             $mNObj->getNameEntries()[0]->getID());
        
        /*
         * Undelete the name we just deleted, and check that we're not back to the original number of names.
         */ 

        $undeleteDVhInfo = array('version' => $undelVersion,
                                 'main_id' => $mNObj->getID());
        $unDObj = $this->dbu->selectConstellation($undeleteDVhInfo, $this->appUserID);
        $unDeleteNameCount = count($unDObj->getNameEntries());
        $this->assertTrue($preDeleteNameCount == $unDeleteNameCount);

        /*
         * Modify a name and save the modified name only. No other parts of the constellation are updated,
         * which is reasonable because no other parts have been modified. After saving, re-read the entire
         * constellation and check that the number of names is unchanged, and that we have the modified
         * name. An early bug caused names to multiply on update.
         *
         * Note: getNameEntries() returns a reference, and changes to that reference modify $unDObj in place.
         * 
         * Use that name reference so we can modify the name in place without asking for it a second time.
         */ 

        $neNameListRef = $unDObj->getNameEntries();

        $origNCount = count($neNameListRef);
        $name = $neNameListRef[0]->getOriginal();
        $modName = preg_replace('/(^.*) /', '$1xx ', $name);
        $neNameListRef[0]->setOriginal($modName);

        $modVhInfo = $this->dbu->updatePrepare($unDObj,
                                               $this->appUserID,
                                               $this->role,
                                               'needs review',
                                               'modified first alt name');
        /*
         * Feb 9 2016 This will save all names of the constellation, but that's fine for testing that saving
         * name or names does not change the number of names associated with the constellation. When we
         * implement AbstractData->$operation and setOperation() we can use that feature to only save a
         * name. When that happens we will call setOperation() on the name, and send the entire constellation
         * off for processing.
         */ 
        // $this->dbu->saveName($modVhInfo, $unDObj->getNameEntries()[0]);
        $this->dbu->saveName($modVhInfo, $unDObj);

        $modObj = $this->dbu->selectConstellation($modVhInfo, $this->appUserID);

        // printf("\n mod: $modName db: %s\n", $modObj->getNameEntries()[0]->getOriginal());

        $this->assertTrue($modName == $modObj->getNameEntries()[0]->getOriginal());

        /* 
         * printf("\n");
         * foreach ($modObj->getNameEntries() as $ne)
         * {
         *     printf("id: %s version: %s main_id: %s %s\n",
         *            $ne->getID(),
         *            $ne->getVersion(),
         *            $modObj->getID(),
         *            $ne->getOriginal());
         * }
         */

        $this->assertTrue($origNCount == count($modObj->getNameEntries()));
    }
        
    public function testParseToDB()
    {
        // Parse a file, write the data into the db.

        $eParser = new \snac\util\EACCPFParser();
        $constellationObj = $eParser->parseFile("/data/merge/99166-w6f2061g.xml");
        $vhInfo = $this->dbu->insertConstellation($constellationObj,
                                                  $this->appUserID,
                                                  $this->role,
                                                  'bulk ingest',
                                                  'bulk ingest of merged');

        $this->assertNotNull($vhInfo);

        /* 
         * Get the constellation that was just inserted. As of Dec 2015, the inserted and selected
         * constellation won't be identical due to unresolved treatment of place and maintenance data.
         */
        $selectedConstellationObj = $this->dbu->selectConstellation($vhInfo, $this->appUserID);
        $this->assertNotNull($selectedConstellationObj);

        /*
         * Check a couple of db info values for the Constellation. This is the constellation version aka
         * max(version) aka max(version_history.id) for the constellation. This is also
         * version_history.main_id aka the constellation id (which is not the per-table record id since there
         * is no singular table for a constellation).
         */
        $this->assertTrue($selectedConstellationObj->getVersion() > 0);
        $this->assertTrue($selectedConstellationObj->getID() > 0);

        /*
         * Optional assertions, depending on if our constellation has function.  This is kind of a
         * weak idea, but until we have a test constellation with all sub-object, this is it.
         *
         * Since this is a function, getVersion() returns the version of this function which may be <= the constellation.
         *
         * Also, this is the per-table record id aka table.id (not the constellation main_id).
         */
        if (($fObj = $selectedConstellationObj->getFunctions()))
        {
            $this->assertTrue($fObj->getVersion() > 0);
            $this->assertTrue($fObj->getID() > 0);
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

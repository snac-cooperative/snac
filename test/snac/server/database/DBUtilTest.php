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
         * Feb 19 2016 Holy cow. This needs to be in DBUtil, only. This is being done there and here. Only
         * deprecated code will use the values here.
         *
         * A flat list of the appuser.id and related role.id, both are numeric. 
         */ 
        // list($this->appUserID, $this->roleID) = $this->dbu->getAppUserInfo('system');
    }

    public function testUpdateContrib()
    {
        $eParser = new \snac\util\EACCPFParser();
        $cObj = $eParser->parseFile("test/snac/server/database/test_record.xml");
        $firstJSON = $cObj->toJSON();
        $retObj = $this->dbu->writeConstellation($cObj,
                                                 'bulk ingest of merged');
        $this->dbu->writeConstellationStatus($retObj->getID(), 'published', 'change status to published');

        $origContribName = $retObj->getNameEntries()[0]->getContributors()[0]->getName();
        $nameVersion = $retObj->getNameEntries()[0]->getVersion();
        $contribVersion = $retObj->getNameEntries()[0]->getContributors()[0]->getVersion();

        $retObj->getNameEntries()[0]->getContributors()[0]->setOperation(\snac\data\AbstractData::$OPERATION_UPDATE);
        $modNameID = $retObj->getNameEntries()[0]->getContributors()[0]->getID();
        $retObj->getNameEntries()[0]->getContributors()[0]->setName("TestName");

        /* 
         * printf("\ndbutiltest: pre-change id: %s to name: %s pre-change cons version: %s\n",
         *        $modNameID,
         *        $retObj->getNameEntries()[0]->getContributors()[0]->getName(),
         *        $retObj->getVersion());
         */

        // printf("\nDBUtilTest Writing cons with changed contributor name\n");
        $postWriteObj = $this->dbu->writeConstellation($retObj,
                                                     'change contributor name');
        $this->dbu->writeConstellationStatus($postWriteObj->getID(), 'published', 'probably already published, but setting again');

        // printf("\nReading constellation version: %s\n", $postWriteObj->getVersion());
        $newObj = $this->dbu->readConstellation($postWriteObj->getID(),
                                                $postWriteObj->getVersion());
        
        // printf("\npost-change cons version: %s\n", $newObj->getVersion());

        $newContribName = $newObj->getNameEntries()[0]->getContributors()[0]->getName();
        $newNameVersion = $newObj->getNameEntries()[0]->getVersion();
        $newContribVersion = $newObj->getNameEntries()[0]->getContributors()[0]->getVersion();

        /* 
         * printf("dbutiltest post change name zero nameID: %s\n", $newObj->getNameEntries()[0]->getID());
         * 
         * foreach($newObj->getNameEntries()[0]->getContributors() as $item)
         * {
         *     printf("\ndbutiltest contrib name: %s id: %s post-change cons version: %s\n json:%s\n",
         *            $item->getName(),
         *            $item->getID(),
         *            $newObj->getVersion(),
         *            $item->toJSON());
         * }
         */

        $this->assertEquals("TestName", $newContribName);
        $this->assertEquals($nameVersion, $newNameVersion);
        $this->assertTrue($newContribVersion > $contribVersion);
    }

    public function testFullCPF()
    {
        $eParser = new \snac\util\EACCPFParser();
        $cObj = $eParser->parseFile("test/snac/server/database/test_record.xml");
        $firstJSON = $cObj->toJSON();
        $retObj = $this->dbu->writeConstellation($cObj,
                                                 'bulk ingest of merged');
        /*
         * Change the status to published so that we can change it to 'locked editing' further below.  The new
         * default on insert is 'locked editing', but we want to test listConstellationsLockedToUser() and to
         * do that we want to change status and call listConstellationsLockedToUser() a second time.
         *
         * There can and should be more definitive tests of listConstellationsLockedToUser().
         */ 
        $this->dbu->writeConstellationStatus($retObj->getID(), 'published');

        $this->assertNotNull($retObj);

        /*
         * New as of March 8 2016.
         * 
         * Test constellation status change, status read, status read by version, and the number of
         * constellations the user has marked for edit.
         */ 
        $editList = $this->dbu->listConstellationsLockedToUser();
        $initialEditCount = count($editList);
        $newSVersion = $this->dbu->writeConstellationStatus($retObj->getID(), 
                                             'locked editing',
                                             'test write constellation status');
        $newStatus = $this->dbu->readConstellationStatus($retObj->getID());
        $newStatusToo = $this->dbu->readConstellationStatus($retObj->getID(), $newSVersion);

        $editList = $this->dbu->listConstellationsLockedToUser();
        $postEditCount = count($editList);
        
        $this->assertEquals('locked editing', $newStatus);
        $this->assertEquals('locked editing', $newStatusToo);
        $this->assertEquals($initialEditCount+1, $postEditCount);

        /* 
         * read from the db what we just wrote to the db
         */
        
        $readObj = $this->dbu->readConstellation($retObj->getID(), $retObj->getVersion());
        
        $secondJSON = $readObj->toJSON();

        /**
        $cfile = fopen('first_json.txt', 'w');
        fwrite($cfile, $firstJSON);
        fclose($cfile); 
        $cfile = fopen('second_json.txt', 'w');
        fwrite($cfile, $secondJSON);
        fclose($cfile); 
        **/

        /*
         * Lacking a JSON diff, use a simple sanity check on the number of lines.
         */ 

        $this->assertEquals(853, substr_count( $firstJSON, "\n" ));
        $this->assertEquals(1018, substr_count( $secondJSON, "\n" ));

        $readObj->setOperation(\snac\data\AbstractData::$OPERATION_DELETE);
        $deletedObj = $this->dbu->writeConstellation($readObj,
                                                     'test deleting a whole constellation');

        /* 
         * readPublishedConstellationByID() should return false when the constellation in question has been
         * deleted.
         *     
         * Try to get it, then test the returned value to be false.
         */
        $tryObj = $this->dbu->readPublishedConstellationByID($deletedObj->getID());
        $postDeleteJSON = "";
        if ($tryObj)
        {
            $postDeleteJSON = $tryObj->toJSON();
        }
        $this->assertFalse($tryObj);

        if (0)
        {
            // These files may be interesting when debugging
            $cfile = fopen('post_delete_json.txt', 'w');
            fwrite($cfile, $postDeleteJSON);
            fclose($cfile);
        }
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


    public function testDBUtilAll() 
    {
        $this->assertNotNull($this->dbu);
        $this->tba = true; // testDBUtilAll has run.
    }

    /*
     * DBUtil depends on some info about the current user. This just tests that we didn't forget to deal with
     * that. 
     */ 
    public function testAppUserInfo()
    {
        $this->assertNotNull($this->dbu->getAppUserID());
    }

    /*
     * Can we get a random Constellation?
     * Can we reverse the order of keys in $vhInfo?
     * Can we get 100 constellations from the db?
     * Can we delete 1 name from a multiname constellation?
     * Can we undelete the name we just deleted?
     *
     * https://phpunit.de/manual/current/en/writing-tests-for-phpunit.html#writing-tests-for-phpunit.data-providers
     *
     * Maybe use @dataProvider to test various records from the db for function, subject, etc.
     *
     * Check expected output: $this->expectOutputString()
     * 
     */
    public function testDemoConstellation()
    {
        $vhInfo = $this->dbu->demoConstellation();
        $cObj = $this->dbu->readConstellation($vhInfo['main_id'], $vhInfo['version']);
        $this->assertNotNull($cObj);

        /* 
         * Make sure that at least selectConstellation() works with reversed key order in the vhInfo arg.
         */ 
        /* Mar 2 2016 selectConstellation() will no longer be public, so this test is not meaningful.
         * $vhInfo = $this->dbu->demoConstellation();
         * $reverseVhInfo = array('main_id' => $vhInfo['main_id'],
         *                        'version' => $vhInfo['version']);
         * $reverseCObj = $this->dbu->selectConstellation($reverseVhInfo, $this->appUserID);
         * $this->assertNotNull($reverseCObj);
         */

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
        $mNObj->getNameEntries()[0]->setOperation(\snac\data\AbstractData::$OPERATION_DELETE);
        $mNObj->setOperation(null);
        $returnedDeleteObj = $this->dbu->writeConstellation($mNObj,
                                                            'delete a name, that is: set is_deleted to true');

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
        /* 
         * $postDVhInfo = array('version' => $newVersion,
         *                      'main_id' => $mNObj->getID());
         */
        $postDObj = $this->dbu->readConstellation($returnedDeleteObj->getID(),
                                                  $returnedDeleteObj->getVersion());
        $postDeleteNameCount = count($postDObj->getNameEntries());
        $this->assertEquals($preDeleteNameCount, ($postDeleteNameCount+1));

        if (1 == 0) // do not run this until undelete is updated
        {
            /*
             * Undelete the name we just deleted, and check that we're not back to the original number of names.
             */ 
            $undelVersion = $this->dbu->clearDeleted($this->appUserID,
                                                     $this->roleID,
                                                     'bulk ingest',
                                                     'un-delete a name, that is: set is_deleted to false',
                                                     $mNObj->getID(), // constellation main_id
                                                     'name',
                                                     $mNObj->getNameEntries()[0]->getID());
            
            $undeleteDVhInfo = array('version' => $undelVersion,
                                     'main_id' => $mNObj->getID());
            
            $unDObj = $this->dbu->readConstellation($undeleteDVhInfo['main_id'], $undeleteDVhInfo['version']);
            $unDeleteNameCount = count($unDObj->getNameEntries());
            $this->assertTrue($preDeleteNameCount == $unDeleteNameCount);
        }
        /*
         * Modify a name and save the modified name only. No other parts of the constellation are updated,
         * which is reasonable because no other parts have been modified. After saving, re-read the entire
         * constellation and check that the number of names is unchanged, and that we have the modified
         * name. An early bug caused names to multiply on update.
         *
         * Note: getNameEntries() returns a reference, and changes to that reference modify $postDObj in place.
         * 
         * Use that name reference so we can modify the name in place without asking for it a second time.
         */ 
        $neNameListRef = $postDObj->getNameEntries();

        $origNCount = count($neNameListRef);
        $name = $neNameListRef[0]->getOriginal();
        $modName = preg_replace('/(^.*) /', '$1xx ', $name);
        $neNameListRef[0]->setOriginal($modName);
        $neNameListRef[0]->setOperation(\snac\data\AbstractData::$OPERATION_UPDATE);
        $retObj = $this->dbu->writeConstellation($postDObj,
                                                 'modified first alt name');
        /*
         * This may be fine during testing, and simulates a record going off for review after a change.
         */ 
        $this->dbu->writeConstellationStatus($retObj->getID(), 'needs review', 'send for review after a name change');

        if (0 == 1) // old code disabled
        {
            $modVhInfo = $this->dbu->updatePrepare($unDObj,
                                                   $this->appUserID,
                                                   $this->roleID,
                                                   'needs review',
                                                   'modified first alt name');
            /*
             * Feb 9 2016 This will save all names of the constellation, but that's fine for testing that saving
             * name or names does not change the number of names associated with the constellation. When we
             * implement AbstractData->$operation and setOperation() we can use that feature to only save a
             * name. When that happens we will call setOperation() on the name, and send the entire constellation
             * off for processing.
             */ 
            $this->dbu->saveName($modVhInfo, $unDObj);
        }
        
        /*
         * Confirm that we can read the modified name back from the db.
         */  
        $modObj = $this->dbu->readConstellation($retObj->getID(), $retObj->getVersion());
        $this->assertEquals($modName, $modObj->getNameEntries()[0]->getOriginal());
        $this->assertTrue($origNCount == count($modObj->getNameEntries()));
    }
        
    /*
     * Parse a file, and write to the db.
     * Get the just-inserted constellation back from the db.
     * Verify versoin and id of constellation read from db.
     * If the constellation has a function, verify non-zero version and id for the function.
     * Update the constellation, and quickly check version and id.
     *
     */
    public function testParseToDB()
    {
        // Parse a file, write the data into the db.
        
        $eParser = new \snac\util\EACCPFParser();
        $constellationObj = $eParser->parseFile("/data/merge/99166-w6f2061g.xml");
        $retObj = $this->dbu->writeConstellation($constellationObj,
                                                 'machine ingest of hand-crafted, full CPF test record');
        // printf("\nAfter first write version: %s\n", $retObj->getVersion());
        $this->assertNotNull($retObj);

        /* 
         * Get the constellation that was just inserted. As of Dec 2015, the inserted and selected
         * constellation won't be identical due to unresolved treatment of place and maintenance data.
         *
         * Mar 4 2016 Now that writeConstellation() returns the $constellationObj with id and version filled
         * in, and now that place, date, language, SCM are all working, things are better. Still, if a partial
         * update is done, then the partial is what is returned by writeConstellation() and that won't match
         * reading the full constellation from the db.
         *
         */
        $cObj = $this->dbu->readConstellation($retObj->getID(), $retObj->getVersion());
        $this->assertNotNull($cObj);

        /*
         * Check a couple of db info values for the Constellation. This is the constellation version aka
         * max(version) aka max(version_history.id) for the constellation. This is also
         * version_history.main_id aka the constellation id (which is not the per-table record id since there
         * is no singular table for a constellation).
         */
        $this->assertTrue($cObj->getVersion() > 0);
        $this->assertTrue($cObj->getID() > 0);

        $this->assertEquals($retObj->getID(), $cObj->getID());
        $this->assertEquals($retObj->getVersion(), $cObj->getVersion());

        /*
         * Optional assertions, depending on if our constellation has function.  This is kind of a
         * weak idea, but until we have a test constellation with all sub-object, this is it.
         *
         * Since this is a function, getVersion() returns the version of this function which may be <= the constellation.
         *
         * Also, this is the per-table record id aka table.id (not the constellation main_id).
         */
        if (($fObj = $cObj->getFunctions()))
        {
            $this->assertTrue($fObj->getVersion() > 0);
            $this->assertTrue($fObj->getID() > 0);
        }
        
        /*
         * Test that an update creates a new version number.
         *
         * How can this test that the constellation was successfully updated? We need more SQL functions to
         * look at parts of the newly updated constellation records in the various tables.
         */ 
        $cObj->setOperation(\snac\data\AbstractData::$OPERATION_UPDATE);
        $updatedObj = $this->dbu->writeConstellation($cObj,
                                                     'updating constellation for test');
        $this->dbu->writeConstellationStatus($updatedObj->getID(), 'needs review');
        /* 
         * printf("\nret: %s cons: %s upd: %s\n", 
         *        $retObj->getID(),
         *        $cObj->getID(),
         *        $updatedObj->getID());
         */

        $this->assertTrue($retObj->getVersion() < $updatedObj->getVersion());
        $this->assertEquals($retObj->getID(), $updatedObj->getID());
    }
}

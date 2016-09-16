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
namespace test\snac\server\database;

/**
 * Database Utils test suite
 *
 * @author Tom Laudeman
 *
 */
class DBUtilTest extends \PHPUnit_Framework_TestCase {

    /**
     * DBUtil object for this class
     * @var \snac\server\database\DBUtil $dbu Database Connection
     */
    private $dbu = null;

    /**
     * User object
     * @var \snac\data\User $user User object
     */
    private $user = null;

    /**
     * @var \Monolog\Logger $logger the logger for this server
     *
     */
    private $logger = null;

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
     *
     * Notice that nowhere do we set up the logger. I'm guessing this is due to this test class extending
     * PHPUnit_Framework_TestCase.
     */
    public function __construct()
    {
        $this->dbu = new \snac\server\database\DBUtil();
        $dbuser = new \snac\server\database\DBUser();
        /*
         * Apr 12 2016 Use the username, not email. Username is unique, email is not. For now, username is
         * defaulted to be email address, and we create the system account with username testing@localhost.
         */
        $testUser = new \snac\data\User();
        $testUser->setUserName("testing@localhost");
        $this->user = $dbuser->readUser($testUser);
        //$dbuser = new \snac\server\database\DBUser();


    }


    /**
     * {@inheritDoc}
     * @see PHPUnit_Framework_TestCase::setUp()
     *
     * This is run before each test, not just once before all tests.
     */
    public function setUp()
    {
        // Consider creating a single parser instance here, and reusing it throughout.
    }


    /**
     * Test the new related resource code and origination name
     */ 
    public function testRelatedResource()
    {
        $eParser = new \snac\util\EACCPFParser();
        $eParser->setConstellationOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        $cObj = $eParser->parseFile("test/snac/server/database/test_record.xml");

        /*
         * Must setOperation(\snac\data\AbstractData::$OPERATION_INSERT) on ad-hoc created objects, otherwise
         * they won't be written to the db.
         */ 
        $rron = new \snac\data\RROriginationName();
        $rron->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        $rron->setName("F. R. Ute");
        $rron2 = new \snac\data\RROriginationName();
        $rron2->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        $rron2->setName("Al Dente");
        $resRelList = $cObj->getResourceRelations();
        $resRel = $resRelList[0];
        $resRel->AddRelatedResourceOriginationName($rron);
        $resRel->AddRelatedResourceOriginationName($rron2);

        $resRelList = $cObj->getResourceRelations();
        foreach ($resRelList as $resrel) {
            printf("\norig resrel id: %s\n", $resrel->getID());
            foreach ($resrel->getRelatedResourceOriginationName() as $rron) {
                printf("\norig rron: %s\n", $rron->getName());
            }
        }

        $retObj = $this->dbu->writeConstellation($this->user,
                                                 $cObj,
                                                 'test demo constellation',
                                                 'ingest cpf');

        $this->dbu->writeConstellationStatus($this->user, $retObj->getID(), 'locked editing');
        $newObj = $this->dbu->readConstellation($retObj->getID(), $retObj->getVersion());

        /*
         * Resource relation order changes during the round trip to the db, so we look at all resource
         * relations, and gather all the origination names as keys in a list. Then check for our two keys at the end.
         */ 
        $foundList = array();
        $resRelList = $newObj->getResourceRelations();
        foreach ($resRelList as $resrel) {
            foreach ($resrel->getRelatedResourceOriginationName() as $rron) {
                $foundList[$rron->getName()] = 1;
            }
        }

        $this->assertTrue(array_key_exists('F. R. Ute', $foundList));
        $this->assertTrue(array_key_exists('Al Dente', $foundList));

    }


    /**
     * Check that name components come back the correct order. Minimal check really only looks at the first
     * element, but that should be enough, especially since we will eventually replace all the vocabulary code.
     *
     * June 2015 Disabled because we're temporarily just sorting alphabetically.
     */
    public function disabled_testNameComponentOrder()
    {
        $entityTypeList = $this->dbu->searchVocabulary('entity_type', '');
        foreach($entityTypeList as $ent)
        {
            /*
             * printf("\ndbutiltest eid: %s ev: %s list: %s\n",
             *        $ent['id'],
             *        $ent['value'],
             *        var_export($this->dbu->searchVocabulary('name_component','', $ent['id']),1));
             */
            $vocabList = $this->dbu->searchVocabulary('name_component','', $ent['id']);
            if ($ent['value'] == 'person')
            {
                $this->assertEquals('Surname', $vocabList[0]['value']);
            }
            else if ($ent['value'] == 'corporateBody')
            {
                $this->assertEquals('Name', $vocabList[0]['value']);
            }
            else
            {
                $this->assertEquals('FamilyName', $vocabList[0]['value']);
            }
        }
    }



    /**
     * Check multiple related
     *
     * Check multiple versions for multiple records of related second order data: source, date, place
     * (place_link), meta (scm), language.
     *
     * Verify that multiples of each are written to the db and read back from the db when some of the multis
     * have different version numbers.
     */
    public function testMultiSecondOrderData()
    {
        $eParser = new \snac\util\EACCPFParser();
        $eParser->setConstellationOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        $cObj = $eParser->parseFile("test/snac/server/database/test_record.xml");

        $retObj = $this->dbu->writeConstellation($this->user,
                                                 $cObj,
                                                 'testing multi exist date',
                                                 'ingest cpf');
        $newRetObj = $this->dbu->readConstellation($retObj->getID(), $retObj->getVersion());

        $firstSourceCount = count($newRetObj->getSources());
        $firstPlaceCount = count($newRetObj->getPlaces());
        $firstSCMCount = count($newRetObj->getPlaces()[0]->getSNACControlMetadata());
        $placeID = $newRetObj->getPlaces()[0]->getID();
        $firstLangCount = count($newRetObj->getLanguagesUsed());
        $firstDateCount = count($newRetObj->getDateList());

        $this->assertTrue(count($cObj->getDateList()) > 1);
        $this->assertEquals(count($cObj->getDateList()), count($newRetObj->getDateList()));

        $dateObj = new \snac\data\SNACDate();
        $dateObj->setRange(true);
        $dateObj->setFromDate('1940',
                              '1940',
                              null);
        // $dateObj->setFromBC(false);
        // $dateObj->setFromDateRange($singleDate['from_not_before'], $singleDate['from_not_after']);
        $dateObj->setToDate('1960',
                            '1960',
                            null);
        // $dateObj->setToBC($this->db->pgToBool($singleDate['to_bc']));
        // $dateObj->setToDateRange($singleDate['to_not_before'], $singleDate['to_not_after']);
        // $dateObj->setNote($singleDate['descriptive_note']);
        // $dateObj->setDBInfo($singleDate['version'], $singleDate['id']);
        $dateObj->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);

        $newRetObj->addDate($dateObj);

        $newSource = new \snac\data\Source();
        $newSource->setNote("new added source");
        $newSource->setURI("http://example.com/newsource");
        $newSource->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        $newRetObj->addSource($newSource);

        // place
        $newPlace = new \snac\data\Place();
        $newPlace->setOriginal("Foo City");
        $newPlace->setNote("Test place");
        $newPlace->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        $newRetObj->addPlace($newPlace);

        // scm
        $newSCM = new \snac\data\SNACControlMetadata();
        $newSCM->setSourceData("third paragraph page 25");
        $newSCM->setNote("test scm");
        $newSCM->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        foreach($newRetObj->getPlaces() as $gObj)
        {
            if ($placeID == $gObj->getID())
            {
                $gObj->addSNACControlMetadata($newSCM);
                break;
            }
        }
        // language aka languagesUsed
        $newLang = clone($newRetObj->getLanguagesUsed()[0]);
        $newLang->setID(null);
        $newLang->setVersion(null);
        $newLang->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        // Note singular "language" for add, although get has plural.
        $newRetObj->addLanguageUsed($newLang);

        // Yes, we are re-using $retObj.
        $retObj = $this->dbu->writeConstellation($this->user,
                                                 $newRetObj,
                                                 'testing adding date to multi exist date',
                                                 'ingest cpf');
        $thirdRetObj = $this->dbu->readConstellation($retObj->getID(), $retObj->getVersion());

        $this->assertEquals(count($thirdRetObj->getSources()), $firstSourceCount+1);
        $this->assertEquals(count($thirdRetObj->getPlaces()), $firstPlaceCount+1);
        foreach($thirdRetObj->getPlaces() as $gObj)
        {
            if ($placeID == $gObj->getID())
            {
                $this->assertEquals(count($gObj->getSNACControlMetadata()), $firstSCMCount+1);
                break;
            }
        }
        $this->assertEquals(count($thirdRetObj->getLanguagesUsed()), $firstLangCount+1);
        $this->assertEquals(count($thirdRetObj->getDateList()), $firstDateCount+1);

        /*
         * Every other time this test is run, the returned dates are in a different order. Unclear why, but we
         * don't have an "order by" clause in the SQL, so changing order is sort of expected. Create the tests
         * to be independent of order. There must be 3 dates.
         */

        $firstDateList = array();
        foreach($newRetObj->getDateList() as $gObj)
        {
            array_push($firstDateList, $gObj->getFromDate());
        }
        sort($firstDateList);

        $secondDateList = array();
        foreach($thirdRetObj->getDateList() as $gObj)
        {
            array_push($secondDateList, $gObj->getFromDate());
        }
        sort($secondDateList);

        $this->assertEquals($firstDateList[0], $secondDateList[0]);
        $this->assertEquals($firstDateList[1], $secondDateList[1]);
        $this->assertEquals($firstDateList[2], $secondDateList[2]);
    }

    /**
     * Exercise listConstellationsWithStatusForUser() and listConstellationsWithStatusForAny()
     *
     * We test with 'locked editing' which is user sensitive and 'published' which is for all users.
     */
    public function testWithStatus()
    {
        // Make any previous ones published
        $objList = $this->dbu->listConstellationsWithStatusForUser($this->user, 'locked editing');
        foreach ($objList as $c) {
            $this->dbu->writeConstellationStatus($this->user, $c->getID(), "published");
        }

        // Write 6 copies of the constellation
        $eParser = new \snac\util\EACCPFParser();
        $eParser->setConstellationOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        $cObj = $eParser->parseFile("test/snac/server/database/test_record.xml");
        for ($i = 1; $i <= 6; $i++)
            $this->dbu->writeConstellation($this->user,
                                           $cObj,
                                           "TestWithStatus Database Test".$i,
                                           'ingest cpf');

        $objList = $this->dbu->listConstellationsWithStatusForUser($this->user, 'locked editing');
        $this->assertEquals(6, count($objList), "Should have 6 in locked editing for this user");

        if (count($objList) > 5)
        {
            $objList = $this->dbu->listConstellationsWithStatusForUser($this->user, 'locked editing', 5);
            $this->assertTrue(count($objList)==5);
            $objList = $this->dbu->listConstellationsWithStatusForUser($this->user, 'locked editing', 5, 1);
            $this->assertTrue(count($objList)==5);
        }

        /*
         * Assume that in 100 records of a test load, at least 20 are status published.
         */
        $objList = $this->dbu->listConstellationsWithStatusForAny('published');
        $this->assertTrue(count($objList)>=1);

        $objList = $this->dbu->listConstellationsWithStatusForAny('published', 10);
        $this->assertTrue(count($objList)==10);

        $objList = $this->dbu->listConstellationsWithStatusForAny('published', 10, 10);
        $this->assertTrue(count($objList)==10);
    }
    /**
     * Update contributor
     *
     * Modify a contributor without any changes happening to the nameEntry the contributor refers to.
     */
    public function testUpdateContrib()
    {
        $eParser = new \snac\util\EACCPFParser();
        $eParser->setConstellationOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        $cObj = $eParser->parseFile("test/snac/server/database/test_record.xml");
        $firstJSON = $cObj->toJSON();
        $retObj = $this->dbu->writeConstellation($this->user,
                                                 $cObj,
                                                 'bulk ingest of merged',
                                                 'ingest cpf');
        $this->dbu->writeConstellationStatus($this->user, $retObj->getID(), 'published', 'change status to published');

        if (0)
        {
            $cbObj = $retObj->getNameEntries()[0];
            printf("\ndbutiltest post write name operation: %s\n", $cbObj->getOperation());
            $op = call_user_func(array($cbObj, 'getOperation'));
            // call_user_func_array(array($cbObj, 'setOperation'), array('foo'));
            $cbObj->setOperation(\snac\data\AbstractData::$OPERATION_UPDATE);
            printf("\ndbutiltest after callback setOperation op: %s operation: %s\n", $op, $cbObj->getOperation());
        }
        $origContribName = $retObj->getNameEntries()[0]->getContributors()[0]->getName();
        $nameVersion = $retObj->getNameEntries()[0]->getVersion();
        $contribVersion = $retObj->getNameEntries()[0]->getContributors()[0]->getVersion();

        /*
         * All the operations are set for $retObj, and there is no way to clear them. Read the constellation
         * from disk to get a new constellation with no operations. Then modify the new copy of the constellation as planned.
         */
        $newRetObj = $this->dbu->readConstellation($retObj->getID(), $retObj->getVersion());
        unset($retObj);
        $newRetObj->getNameEntries()[0]->getContributors()[0]->setOperation(\snac\data\AbstractData::$OPERATION_UPDATE);
        $modNameID = $newRetObj->getNameEntries()[0]->getContributors()[0]->getID();
        $newRetObj->getNameEntries()[0]->getContributors()[0]->setName("TestName");

        $postWriteObj = $this->dbu->writeConstellation($this->user,
                                                       $newRetObj,
                                                       'change contributor name',
                                                       'ingest cpf');
        $this->dbu->writeConstellationStatus($this->user, $postWriteObj->getID(), 'published', 'probably already published, but setting again');

        $newObj = $this->dbu->readConstellation($postWriteObj->getID(),
                                                $postWriteObj->getVersion());

        $newContribName = $newObj->getNameEntries()[0]->getContributors()[0]->getName();
        $newNameVersion = $newObj->getNameEntries()[0]->getVersion();
        $newContribVersion = $newObj->getNameEntries()[0]->getContributors()[0]->getVersion();

        $this->assertEquals("TestName", $newContribName);
        $this->assertEquals($nameVersion, $newNameVersion);
        $this->assertTrue($newContribVersion > $contribVersion);
    }

    /**
     * Ingest the full CPF test using the optional status arg
     *
     * Write a constellation with the optional status 'ingest cpf' to test contellation creation and
     * maintenance info capture in the version_history record.
     */
    public function testFullCPFIngestCPFStatus()
    {
        $eParser = new \snac\util\EACCPFParser();
        $eParser->setConstellationOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        $cObj = $eParser->parseFile("test/snac/server/database/test_record.xml");

        $retObj = $this->dbu->writeConstellation($this->user, // $user
                                                 $cObj,       // $argObj
                                                 'testing ingest full CPF record with ingest cpf', // $note
                                                 'ingest cpf'); // $statusArg
        $this->assertTrue($cObj->equals($retObj, false), "Initial parsed constellation doesn't equal written one");

        // Get the most recent version.
        $readObj = $this->dbu->readConstellation($retObj->getID());

        /*
         * The constellation object does not contain a populated status because the server may change it. If
         * you want status you must call readConstellationStatus() and get it directly from the db.
         */
        $this->assertEquals($this->dbu->readConstellationStatus($readObj->getID()), 'locked editing');
    }

    /**
     * Test name component related code
     *
     * Test both saving and reading name components, as well as searchVocabulary() which has special behavior
     * related to name components.
     *
     */
    public function testSearchVocabularyNameComponent()
    {
        $eParser = new \snac\util\EACCPFParser();
        $eParser->setConstellationOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        $cObj = $eParser->parseFile("test/snac/server/database/test_record.xml");

        $cObj->getNameEntries()[0]->setOriginal("Foo Bar Test");
        $componentObj = new \snac\data\NameComponent();
        $componentObj->setText("Foo");
        $componentObj->setOrder(1);

        $entList = $this->dbu->searchVocabulary('entity_type', '');

        $personID = 0;
        foreach($entList as $ent)
        {
            // Only one record will match.
            if ($ent['value'] == 'person')
            {
                $personID = $ent['id'];
            }
        }

        $svList = $this->dbu->searchVocabulary('name_component', 'Surname', $personID);

        $ctObj = new \snac\data\Term();
        foreach($svList as $svocab)
        {
            // Only one record will match.
            if ($svocab['value'] == 'Surname')
            {
                $ctObj->setID($svocab['id']);
                $ctObj->setType('name_component');
                $ctObj->setTerm($svocab['value']);
            }
        }

        $componentObj->setType($ctObj);
        $componentObj->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        $cObj->getNameEntries()[0]->addComponent($componentObj);

        $retObj = $this->dbu->writeConstellation($this->user, // $user
                                                 $cObj,       // $argObj
                                                 'testing ingest full CPF record with ingest cpf', // $note
                                                 'ingest cpf'); // $statusArg
        $this->assertTrue($cObj->equals($retObj, false), "Initial parsed constellation doesn't equal written one");

        // Get the most recent version.
        $readObj = $this->dbu->readConstellation($retObj->getID());

        /*
         * Change to 1 for debugging.  Do we have an environment var for debug mode?
         */
        if (0)
        {
            $cfile = fopen('name_component_json.txt', 'w');
            $ncJSON = $readObj->toJSON();
            fwrite($cfile, $ncJSON);
            fclose($cfile);
        }

        /*
         * The constellation object does not contain a populated status because the server may change it. If
         * you want status you must call readConstellationStatus() and get it directly from the db.
         */
        $this->assertEquals($this->dbu->readConstellationStatus($readObj->getID()), 'locked editing');
    }


    public function testFullCPFDateIsRange()
    {
        $eParser = new \snac\util\EACCPFParser();
        $eParser->setConstellationOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        $cObj = $eParser->parseFile("test/snac/server/database/test_record.xml");
        /*
         * Explicitly set isRange to false on all exist dates. The bug is/was that on reading it becomes true.
         */
        foreach($cObj->getDateList() as $gObj)
        {
            /*
             * setRange() w/o "Is" and getIsRange() w/ "Is" are the setter and getter.
             */
            $gObj->setRange(false);
            $this->assertFalse($gObj->getIsRange());
        }

        /*
         * Put the new object through json and back to make sure that retains the false isRange settings.
         */
        $json = $cObj->toJSON();
        $secondObj = new \snac\data\Constellation();
        $secondObj->fromJSON($json);

        $retObj = $this->dbu->writeConstellation($this->user, // $user
                                                 $secondObj,       // $argObj
                                                 'testing ingest full CPF record with ingest cpf', // $note
                                                 'ingest cpf'); // $statusArg
        $this->assertTrue($secondObj->equals($retObj, false), "Initial parsed constellation doesn't equal written one");

        $readObj = $this->dbu->readConstellation($retObj->getID());

        foreach($readObj->getDateList() as $gObj)
        {
            /*
             * setRange() w/o "Is" and getIsRange() w/ "Is" are the setter and getter.
             */
            $this->assertFalse($gObj->getIsRange());
        }
    }



    /**
     * Insert a test record, then change the status to update and make sure that nrd is updated.
     *
     * This is similar to testFullCPFWithEditList() below.
     *
     * Parse test record, set operation to insert, write to db. We know that the entity type is person.
     *
     * .dbutiltest et term json: {
     * "id": "698",
     * "term": "person"
     * }
     *
     * The next step is the whole point of the test: does table nrd get updated when operation is update.
     *
     * Read the record from db, change entity type to family, set operation to update, write to db.
     *
     * Read the record from db and verify entity type is still family.
     *
     */
    public function testFullCPFNrdOperationUpdate()
    {
        $eParser = new \snac\util\EACCPFParser();
        $eParser->setConstellationOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        $cObj = $eParser->parseFile("test/snac/server/database/test_record.xml");
        $eParser->setConstellationOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        $firstJSON = $cObj->toJSON();

        // $cObj->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        $startingARK = $cObj->getArk();
        $startingEntity = $cObj->getEntityType()->getTerm();

        $retObj = $this->dbu->writeConstellation($this->user,
                                                 $cObj,
                                                 'ingest from file',
                                                 'ingest cpf');

        $this->assertNotNull($retObj);

        /*
         * Read from the db what we just wrote to the db.
         *
         * Assume that the vocabulary table is carved in stone, as it should be. Hard code the id 697, and if
         * someone messes with vocabulary this should break. Multilingual vocabulary will break this, and will
         * break a query to retrieve the 697, so there's not much point in trying to use a query instead of
         * simply hard coding the id.
         *
         * (old)
         *
         * wfdb=> select * from vocabulary where type='entity_type';
         *  id  |    type     |     value     | uri | description
         * -----+-------------+---------------+-----+-------------
         *  698 | entity_type | person        |     |
         *  697 | entity_type | family        |     |
         *  696 | entity_type | corporateBody |     |
         * (3 rows)
         *
         * may 24 2016 Things were added to the vocab, now the id value is different
         *
         * wfdb=> select * from vocabulary where type='entity_type';
         * id  |    type     |     value     | uri | description
         * -----+-------------+---------------+-----+-------------
         * 700 | entity_type | person        |     |
         * 699 | entity_type | family        |     |
         * 698 | entity_type | corporateBody |     |
         * (3 rows)

         */

        $readObj = $this->dbu->readConstellation($retObj->getID(), $retObj->getVersion());

        /*
         * We are expecting only one result. Search the vocab list so even if the IDs change, we will get the
         * correct id for entity_type family.
         */
        $vocabList = $this->dbu->searchVocabulary('entity_type', 'family');
        if (count($vocabList) != 1)
        {
            throw new \snac\exceptions\SNACException("Did not get exactly 1 result for 'entity_type' and 'family'.");
        }
        if ($vocabList[0]['value'] != 'family')
        {
            throw new \snac\exceptions\SNACException("Did not get expected 'family' as value.");
        }

        $readObj->getEntityType()->setID($vocabList[0]['id']);
        $readObj->getEntityType()->setTerm('family');
        $readObj->setOperation(\snac\data\AbstractData::$OPERATION_UPDATE);
        $xObj = $this->dbu->writeConstellation($this->user,
                                               $readObj,
                                               'change nrd term operation update',
                                               'ingest cpf');

        $finalObj = $this->dbu->readConstellation($xObj->getID(), $xObj->getVersion());
        $this->assertEquals($finalObj->getEntityType()->getTerm(), 'family');
    }

    /**
     * Test that an scm can use an existing source, and round trip the SCM.
     */
    public function testSourceSCM()
    {
        $eParser = new \snac\util\EACCPFParser();
        $eParser->setConstellationOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        $cObj = $eParser->parseFile("test/snac/server/database/test_record.xml");
        $cObj->getPlaces()[1]->setConfirmed(true);

        $retObj = $this->dbu->writeConstellation($this->user,
                                                 $cObj,
                                                 'ingest full CPF prior to checking adding source to scm',
                                                 'ingest cpf');

        $readObj = $this->dbu->readConstellation($retObj->getID());

        $readObj->getPlaces()[0]->getSNACControlMetadata()[0]->setCitation($readObj->getSources()[0]);
        $readObj->getPlaces()[0]->getSNACControlMetadata()[0]->setNote("adding source");
        $readObj->getPlaces()[0]->getSNACControlMetadata()[0]->setOperation(\snac\data\AbstractData::$OPERATION_UPDATE);
        /*
         * Run toJSON before write because write changes the constellation in place (apparently) even though
         * it should not be doing that.
         */
        $firstJSON = $readObj->toJSON();

        $origObj = $this->dbu->writeConstellation($this->user,
                                                  $readObj,
                                                  'adding source to scm',
                                                  'ingest cpf');

        $newObj = $this->dbu->readConstellation($readObj->getID());


        $secondJSON = $newObj->toJSON();

        /*
         * These files are sometimes useful for debugging.
         */
        /*
         * $cfile = fopen('scm_before_save.txt', 'w');
         * fwrite($cfile, $firstJSON);
         * fclose($cfile);
         * $cfile = fopen('scm_after_read.txt', 'w');
         * fwrite($cfile, $secondJSON);
         * fclose($cfile);
         */
        /*
         * We have Constellation->equals() which is a more accurate check of equality than line count.
         *
         * This compares the constellation after adding an SCM and writing, with the same constellation read
         * back from the db.
         */
        $this->assertTrue($newObj->equals($origObj, false));

        $sourceList = $newObj->getSources();

        $newSource = clone($sourceList[0]);
        $newSource->setID(null);
        $newSource->setVersion(null); // Seems like insert code should write over this.
        $newSource->setDisplayName("added source");
        $newSource->setURI("http://foo.com/bar/baz/");
        $newSource->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);

        $newObj->addSource($newSource);

        $this->dbu->writeConstellation($this->user,
                                       $newObj,
                                       'Added another source for foo.com',
                                       'ingest cpf');

        $postAddObj = $this->dbu->readConstellation($newObj->getID());

        $longerSourceList = $postAddObj->getSources();
        $this->assertEquals(count($sourceList)+1, count($longerSourceList));
    }

    /**
     * Test delete by changing status
     *
     * Change constellation status to deleted in order to delete the whole constellation.
     */
    public function testDeleteViaWriteConstellationStatus()
    {
        $eParser = new \snac\util\EACCPFParser();
        $eParser->setConstellationOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        $cObj = $eParser->parseFile("test/snac/server/database/test_record.xml");
        $cObj->getPlaces()[1]->setConfirmed(true);
        $firstJSON = $cObj->toJSON();

        $startingARK = $cObj->getArk();
        $startingEntity = $cObj->getEntityType()->getTerm();

        $retObj = $this->dbu->writeConstellation($this->user,
                                                 $cObj,
                                                 'ingest in order to delete via status',
                                                 'ingest cpf');

        $delVersion = $this->dbu->writeConstellationStatus($this->user, $retObj->getID(), 'deleted');

        global $log;
        if (! $this->logger)
        {
            // create a log channel
            $this->logger = new \Monolog\Logger('DBUtil');
            $this->logger->pushHandler($log);
        }
        $this->logger->addDebug(sprintf("delete via status version: %s ic_id: %s", $delVersion, $retObj->getID()));

        $this->assertNotFalse($delVersion);
    }

    /**
     * Read in the full test record
     *
     * Set the operation to be insert.
     *
     * After parsing, change the second place to be confirmed. It is only possible to set confirmed on a place
     * with a geoplace (aka GeoTerm), and that's why we use [1] instead of [0].
     *
     * Count how many constellations are status 'locked editing'.
     *
     * Change the status of one record and compare the count of records locked for editing, which should be initial+1.
     *
     * Read the record back from the db, and make sure the ARK and entity type are correct.
     *
     * Delete a constellation, then attempt a read and make sure it did not read.
     *
     * Apr 21 2016 Added <descriptiveNote> to existDates in test_record.xml and verified that it shows up in
     * the JSON from parsing and reading back out of the db. There's no explicit test for that here, just like
     * there's no explicit test for all the other CPF elements.
     */
    public function testFullCPFWithEditList()
    {
        $eParser = new \snac\util\EACCPFParser();
        $eParser->setConstellationOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        $cObj = $eParser->parseFile("test/snac/server/database/test_record.xml");
        $cObj->getPlaces()[1]->setConfirmed(true);
        $firstJSON = $cObj->toJSON();

        $startingARK = $cObj->getArk();
        $startingEntity = $cObj->getEntityType()->getTerm();

        $retObj = $this->dbu->writeConstellation($this->user,
                                                 $cObj,
                                                 'testing ingest of a full CPF record',
                                                 'ingest cpf');
        $this->assertTrue($cObj->equals($retObj, false), "Initial parsed constellation doesn't equal written one");

        $readObj = $this->dbu->readConstellation($retObj->getID(), $retObj->getVersion());

        $this->assertTrue($readObj->equals($retObj, false), "Written constellation is not equal to next read version");

        /*
         * Change the status to published so that we can change it to 'locked editing' further below.  The new
         * default on insert is 'locked editing', but we want to test listConstellationsWithStatusForUser() and to
         * do that we want to change status and call listConstellationsWithStatusForUser() a second time.
         *
         */
        $this->dbu->writeConstellationStatus($this->user, $retObj->getID(), 'published');

        $this->assertNotNull($retObj);


        // It defaults to 'locked editing', but be explicit anyway.
        $editList = $this->dbu->listConstellationsWithStatusForUser($this->user, 'locked editing', -1, -1);
        $initialEditCount = count($editList);


        $newSVersion = $this->dbu->writeConstellationStatus($this->user, $retObj->getID(),
                                                            'locked editing',
                                                            'test write constellation status change published to locked editing');

        // Not really necessary, since other tests will fail later if the writeConstellationStatus() fails.
        $this->assertTrue($newSVersion > 1);

        $newStatus = $this->dbu->readConstellationStatus($retObj->getID());
        $newStatusToo = $this->dbu->readConstellationStatus($retObj->getID(), $newSVersion);

        /*
         * Get the post-status-change count, and test.
         */
        // It defaults to 'locked editing', but be explicit anyway.
        $editList = $this->dbu->listConstellationsWithStatusForUser($this->user, 'locked editing', -1, -1);
        $postEditCount = count($editList);

        $this->assertEquals('locked editing', $newStatus);
        $this->assertEquals('locked editing', $newStatusToo);

        /*
         * If you get a number like 42 doesn't match expected 43, look at the optional limit and offset for
         * listConstellationsWithStatusForUser(). The default limit is 42.
         */
        $this->assertEquals($initialEditCount+1, $postEditCount);

        /*
         * Change back to published so it doesn't show up on anyone's dashboard.
         * When we have real users this won't matter as much because testing will be done with the "test" user.
         */
        $this->dbu->writeConstellationStatus($this->user, $retObj->getID(), 'published', 'change status back to published in order toclean up');

        /*
         * Change status of some other constellation. This tests a bug Robbie found on Mar 25 where
         * non-contiguous version numbers for the otherRecordID (table otherid) caused selectOtherID() to not
         * return the records.
         *
         * Get the Washington record which as of this test has just become a requirement. We can use any
         * record, so you should be able to change the ARK below to any ARK which is known to be loaded.
         *
         * Incidentally, this exercises readPublishedConstellationByARK() and selectMainID().
         */
        $washObj = $this->dbu->readPublishedConstellationByARK('http://n2t.net/ark:/99166/w6028ps4');
        $this->dbu->writeConstellationStatus($this->user, $washObj->getID(), 'published', 'modify status as part of testFullCPFWithEditList');

        /*
         * read from the db what we just wrote to the db back at the beginning.
         */
        $readObj = $this->dbu->readConstellation($retObj->getID(), $retObj->getVersion());

        $readingARK = $readObj->getArk();
        $readingEntity = $readObj->getEntityType()->getTerm();

        $this->assertEquals($startingARK, $readingARK);
        $this->assertEquals($startingEntity, $readingEntity);

        /*
         * Legalstatus is broken because all the terms are not in the db?
         */
        // $this->assertEquals("Sample legal status", $readObj->getLegalStatuses()[0]->getTerm()->getTerm());

        $secondJSON = $readObj->toJSON();

        /*
         * Before uncommenting this, copy the old files. Any time these need updating, you should diff the old
         * and new to confirm that what you think changed, changed, and nothing else.
         *
         * first is from $cObj.
         * second is from $readObj.
         */
        /*
         * $cfile = fopen('first_json.txt', 'w');
         * fwrite($cfile, $firstJSON);
         * fclose($cfile);
         * $cfile = fopen('second_json.txt', 'w');
         * fwrite($cfile, $secondJSON);
         * fclose($cfile);
         */

        /*
         * $retObj is returned by the first wrieConstellation(). $readObj is the result of readConstellation()
         * on the same constellation, to confirm that was was written is read back again.
         */
        $this->assertTrue($retObj->equals($readObj, false));

        $readObj->setOperation(\snac\data\AbstractData::$OPERATION_DELETE);
        $deletedObj = $this->dbu->writeConstellation($this->user,
                                                     $readObj,
                                                     'test deleting a whole constellation',
                                                     'ingest cpf');

        /*
         * readPublishedConstellationByID() should return false when the constellation in question has been
         * deleted.
         *
         * Try to get it, then test the returned value to be false.
         *
         * Interestingly, this now also writes a log message.
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


        $eParser = new \snac\util\EACCPFParser();
        $eParser->setConstellationOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        $cObj = $eParser->parseFile("test/snac/server/database/test_record.xml");


        $tmp = $this->dbu->writeConstellation($this->user,
                                              $cObj,
                                              'test demo constellation',
                                              'ingest cpf');


        $cObj = $this->dbu->readConstellation($tmp->getID(), $tmp->getVersion());
        $this->assertNotNull($cObj);

        /*
         * Delete a name and verify it.
         */

        $preDeleteNameCount = count($cObj->getNameEntries());

        /*
         * We need the new version of the deleted record, which becomes the max(version) of the constellation.
         *
         * Older code used $mNObj->getNameEntries()[0]->getMainID() for the mainID, but it is better that
         * mainID aka ic_id exist only in the Constellation object. Or that we have a variable outside the
         * object as we do here.
         *
         */
        $cObj->getNameEntries()[0]->setOperation(\snac\data\AbstractData::$OPERATION_DELETE);
        $cObj->setOperation(null);
        $returnedDeleteObj = $this->dbu->writeConstellation($this->user,
                                                            $cObj,
                                                            'delete a name, that is: set is_deleted to true',
                                                            'ingest cpf');

        /*
         * Post delete. The delete operation mints a new version number which is returned in the object
         * returned by writeConstellation().  We combine the new version and the known (and unchanged ic_id)
         * to create a new vhInfo associative list. Then we pass that to readConstellation() to get the
         * current copy of the constellation from the database.
         *
         * Note: constellation object getID() returns the constellation id, not the per-record id as with
         * getID() for all other data objects.
         *
         */
        $postDObj = $this->dbu->readConstellation($returnedDeleteObj->getID(),
                                                  $returnedDeleteObj->getVersion());
        $postDeleteNameCount = count($postDObj->getNameEntries());
        $this->assertEquals($preDeleteNameCount, ($postDeleteNameCount+1));

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
        $retObj = $this->dbu->writeConstellation($this->user,
                                                 $postDObj,
                                                 'modified first alt name',
                                                 'ingest cpf');
        /*
         * This may be fine during testing, and simulates a record going off for review after a change.
         */
        $this->dbu->writeConstellationStatus($this->user, $retObj->getID(), 'needs review', 'send for review after a name change');


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
        $eParser->setConstellationOperation("insert");
        $constellationObj = $eParser->parseFile("test/snac/server/database/99166-w6f2061g.xml");
        $retObj = $this->dbu->writeConstellation($this->user,
                                                 $constellationObj,
                                                 'machine ingest of hand-crafted, full CPF test record',
                                                 'ingest cpf');
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
         * version_history.ic_id aka the constellation id (which is not the per-table record id since there
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
         * Also, this is the per-table record id aka table.id (not the constellation ic_id).
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
        $updatedObj = $this->dbu->writeConstellation($this->user,
                                                     $cObj,
                                                     'updating constellation for test',
                                                     'ingest cpf');
        $this->dbu->writeConstellationStatus($this->user, $updatedObj->getID(), 'needs review');
        /*
         * printf("\nret: %s cons: %s upd: %s\n",
         *        $retObj->getID(),
         *        $cObj->getID(),
         *        $updatedObj->getID());
         */

        $this->assertTrue($retObj->getVersion() < $updatedObj->getVersion());
        $this->assertEquals($retObj->getID(), $updatedObj->getID());
    }

    /**
     * Test parsing another problem cpf
     *
     */
    public function testIngestAnotherProblemCPF()
    {
        $eParser = new \snac\util\EACCPFParser();
        $eParser->setConstellationOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        $cObj = $eParser->parseFile("test/snac/util/eac-cpf/99166-w65k3tsm.xml");

        $retObj = $this->dbu->writeConstellation($this->user,
                                                 $cObj,
                                                 'testing ingest of a full CPF record',
                                                 'ingest cpf');

        // Assert that it was written
        $this->assertNotNull($retObj, "Something went wrong when trying to write the constellation");

        $ret = $this->dbu->writeConstellationStatus($this->user, $retObj->getID(), 'published');

        // Assert that we could change the status
        $this->assertNotFalse($ret, "Error writing status to object");

        // Delete it so it's not in our way anymore
        $ret = $this->dbu->writeConstellationStatus($this->user, $retObj->getID(), 'deleted');

        // Assert that we could change the status
        $this->assertNotFalse($ret, "Error writing deleted status to object");
    }
}

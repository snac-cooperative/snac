<?php
/**
 * EAC CPF Serializer Test File
 *
 * @author Tom Laudeman
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2016 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace test\snac\util;

/**
 * EAC CPF Serializer test suite
 *
 * @author Tom Laudeman
 * @author Robbie Hott
 */
class EACCPFSerializerTest extends \PHPUnit\Framework\TestCase {

    /**
     * DBUtil object for this class
     * @var \snac\server\database\DBUtil $dbu object
     */
    private $dbu = null;

    /**
     * @var \snac\data\User $user User object
     */
    private $user = null;

    /**
     * @var \Monolog\Logger $logger the logger for this server
     *
     * See enableLogging() in this file.
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
        global $log;
        parent::__construct(); // Must call the parent constructor
        // create a log channel
        $this->logger = new \Monolog\Logger('EACCPFSerializer');
        $this->logger->pushHandler($log);
        $this->dbu = new \snac\server\database\DBUtil();
        $dbuser = new \snac\server\database\DBUser();

        $testUser = new \snac\data\User();
        $testUser->setUserName("testing@localhost");
        $this->user = $dbuser->readUser($testUser);
    }

    /**
     * {@inheritDoc}
     * @see PHPUnit_Framework_TestCase::setUp()
     *
     * This is run before each test, not just once before all tests.
     */
    public function setUp() {
    }

    /**
     * Test parsed constellation to serialize and parsed again
     */
    public function testParsedConstellationConstellation() {
        $eParser = new \snac\util\EACCPFParser();
        $eSerializer = new \snac\util\EACCPFSerializer();
        $origCon = $eParser->parseFile("test/snac/server/database/test_record.xml");
        $cpfXML = $eSerializer->serialize($origCon);
        $secondCon = $eParser->parse($cpfXML);
        $cpfXML = $eSerializer->serialize($secondCon);
        $thirdCon = $eParser->parse($cpfXML);


        $this->assertTrue($origCon->equals($secondCon), "The original parsed constellation is different than the parsed serialized constellation");
        $this->assertTrue($thirdCon->equals($secondCon), "The third parsed constellation is different than the second parsed serialized constellation");
        $this->assertTrue($thirdCon->equals($origCon), "The third parsed constellation is different than the original parsed constellation");
    }

    /**
     * Test a constellation parsed, written to the database, serialized and reparsed
     */
    public function testConstellationDatabaseConstellation() {
        $eParser = new \snac\util\EACCPFParser();
        $eSerializer = new \snac\util\EACCPFSerializer();
        $eParser->setConstellationOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        $cObj = $eParser->parseFile("test/snac/server/database/test_record.xml");
        $cObj->setArkID("test-ark:constellationdatabaseconstellation/".time());

        // Eventually, we may want this interaction to go through the server itself.

        $retObj = $this->dbu->writeConstellation($this->user,
                                                 $cObj,
                                                 null,
                                                 'ingest cpf');
        $this->dbu->writeConstellationStatus($this->user,$retObj->getID(), "published");

        // If this is published, then it should point to itself in the lookup table.
        $selfDirect = array($retObj);
        $this->dbu->updateConstellationLookup($retObj, $selfDirect);

        $fromDB = $this->dbu->readPublishedConstellationByID($retObj->getID(), \snac\server\database\DBUtil::$FULL_CONSTELLATION
                                                                | \snac\server\database\DBUtil::$READ_MAINTENANCE_INFORMATION);

        $cpfXML = $eSerializer->serialize($fromDB);
        $secondCon = $eParser->parse($cpfXML);

        $fromDates = $fromDB->getDateList();
        $secondDates = $secondCon->getDateList();

        $fromNote = "";
        foreach ($fromDates as $k => $date) {
            $fromNote .= $date->getNote();
            $fromDates[$k]->setNote(null);
        }
        $secondNote = "";
        foreach ($secondDates as $k => $date) {
            $secondNote .= $date->getNote();
            $secondDates[$k]->setNote(null);
        }
        $this->assertEquals($fromNote, $secondNote, "The combined notes from before and after serialization were not equal");

        $fromDB->setDateList($fromDates);
        $secondCon->setDateList($secondDates);

        $this->assertTrue($fromDB->equals($secondCon, false), "Constellation read from DB is not the same as the one re-serialized");
        $this->assertTrue($secondCon->equals($fromDB, false), "Constellation re-serialized is not the same as the one read from the DB");

        // Be nice and "delete" the evidence
        $this->dbu->writeConstellationStatus($this->user, $retObj->getID(), "deleted");
    }

    /**
     * Test basic XML rendering
     *
     * The simplest test is number of lines. This test is quite sensitive and a bit of a problem since this
     * has to be changed everytime the template gets an update that changes number of lines of output.
     *
     * Other tests do a Constellation->equals().
     */
    public function testRenderWithJing() {
        /*
         * A bug was revealed by /data/merge/99166-w6fr4rx5.xml  http://n2t.net/ark:/99166/w6fr4rx5
         *
         * The composer script automatically pulls in the cpf.rng file into the main repository. It should
         * then always deposit the newest rng file in vendor/npm-asset/eac-validator/rng. Use the const
         * RNG_DIR for that path.
         *
         * In other words, if the test below doesn't run due to missing cpf.rng, then you should run:
         *
         * composer update
         *
         * Jing's error and warning output goes to stdout, so we should see it in $jingResult. No need to io
         * redirect stderr from the jing command.
         *
         * Class path constants don't interpolate, so we have to use the . operator to build the full
         * filename.
         */
        $cpfRng = \snac\Config::$RNG_DIR . "/cpf.rng";
        $jingCmd = '/usr/bin/jing';
        if (file_exists($cpfRng) && file_exists($jingCmd)) {
            $xCon = $this->dbu->readPublishedConstellationByARK('http://n2t.net/ark:/99166/w6fr4rx5');
            $eSerializer = new \snac\util\EACCPFSerializer();
            $cpfXML = $eSerializer->serialize($xCon);
            /*
             * $cfile = fopen('cpf_data.txt', 'w');
             * fwrite($cfile, $xCon->toJSON());
             * fclose($cfile);
             */
            $fn = '/tmp/' . uniqid(rand(), true) . '.xml';

            $cfile = fopen($fn, 'w');
            fwrite($cfile, $cpfXML);
            fclose($cfile);
            $jingResult = `$jingCmd $cpfRng $fn`;
            $this->logger->addDebug("jing result (should be empty): $jingResult");
            $this->logger->addDebug("temp xml: $fn");

            /*
             * Delete before calling assert, so that a failed assert doesn't leave our temp file.
             *
             * Comment this out for debugging. The file is in /tmp.
             */
            unlink($fn);
            /*
             * Assertion disabled until we resolve the regex for snac:preferenceScore
             */
            // $this->assertTrue($jingResult == '');
        } else {
            $msg = "Not checking via jing. ";
            if (! file_exists($cpfRng)) {
                $msg .= "File not exists: $cpfRng ";
            }
            if (! file_exists($jingCmd)) {
                $msg .= "File not exists: $jingCmd";
            }
            $this->logger->addDebug($msg);
        }
    }
  }

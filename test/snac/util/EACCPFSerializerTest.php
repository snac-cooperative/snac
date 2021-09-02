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
    public function setUp(): void {
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

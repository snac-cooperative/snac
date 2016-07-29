<?php
/**
 * EAC CPF Serializer Test File
 *
 *
 * License:
 *
 * @author Tom Laudeman
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2016 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

/**
 * EAC CPF Serializer test suite
 * 
 * @author Tom Laudeman
 *
 */
class EACCPFSerializerTest extends PHPUnit_Framework_TestCase {
    
    /**
     * DBUtil object for this class
     * @var $dbu \snac\server\database\DBUtil object
     */ 
    private $dbu = null;
    
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
        $this->dbu = new \snac\server\database\DBUtil();
        // $dbuser = new \snac\server\database\DBUser();
        /*
         * Apr 12 2016 Use the username, not email. Username is unique, email is not. For now, username is
         * defaulted to be email address, and we create the system account with username testing@localhost.
         */ 
        /* 
         * $testUser = new \snac\data\User();
         * $testUser->setUserName("testing@localhost");
         * $this->user = $dbuser->readUser($testUser);
         */
    }

    /**
     * Enable logging
     *
     * Call this to enabled logging. For various reasons, logging is not enabled by default.
     *
     * Check that we don't have a logger before creating a new one. This can be called as often as one wants
     * with no problems.
     */
    private static function enableLogging()
    {
        global $log;
        global $logger;
        if (! $logger)
        {
            // create a log channel
            $logger = new \Monolog\Logger('EACCPFSerializer');
            $logger->pushHandler($log);
        }
    }

        /**
     * Wrap logging
     *
     * When logging is disabled, we don't want to call the logger because we don't want to generate errors. We
     * also don't want logs to just magically start up. Doing logging should be very intentional, especially
     * in a low level class like SQL. Call enableLogging() before calling logDebug().
     *
     * @param string $msg The logging messages
     *
     * @param string[] $debugArray An associative list of keys and values to send to the logger.
     */
    private static function logDebug($msg, $debugArray=array())
    {
        global $logger;
        if ($logger)
        {
            $logger->addDebug($msg, $debugArray);
        }
    }

    /**
     * {@inheritDoc}
     * @see PHPUnit_Framework_TestCase::setUp()
     *
     * This is run before each test, not just once before all tests.
     */
    public function setUp() 
    {
    }
    
    /**
     * Test basic XML rendering
     *
     * The simplest test is number of lines. Beyond that, we would need XML diff which is tricky. Number of
     * lines is also a problem since this has to be changed everytime the template gets an update that changes
     * number of lines of output.
     *
     *
     */ 
    public function testRender() {
        $cpfXML = \snac\util\EACCPFSerializer::SerializeByARK('http://snac/test/record-1', true);
        preg_match_all("/(\n)/m", $cpfXML, $matches);
        // printf("\nrec1 matches: %s\n", sizeof($matches[1]));
        $this->assertEquals(359, sizeof($matches[1]));

        $vernOne = \snac\util\EACCPFSerializer::SerializeByARK('http://n2t.net/ark:/99166/w6xd18cz');

        $xCon = $this->dbu->readPublishedConstellationByARK('http://n2t.net/ark:/99166/w6xd18cz');
        $cpfXML = \snac\util\EACCPFSerializer::SerializeCore($xCon->toArray());
        preg_match_all("/(\n)/m", $cpfXML, $matches);
        // printf("\nvern matches: %s\n", sizeof($matches[1]));

        $this->assertEquals(111, sizeof($matches[1]));
        /* 
         * Check that we get the same XML by both methods. 
         */  
        $this->assertEquals($cpfXML, $vernOne);

        /*
         * A bug was revealed by /data/merge/99166-w6fr4rx5.xml  http://n2t.net/ark:/99166/w6fr4rx5
         * 
         * The composer script automatically pulls in the cpf.rng file into the main repository. It should
         * then always deposit the newest rng file in vendor/npm-asset/eac-validator/rng
         *
         * In other words, if the test below doesn't run due to missing cpf.rng run: composer update
         *
         * Jing's error and warning output goes to stdout, so we should see it in $jingResult. No need to io
         * redirect stderr from the jing command.
         */  

        $cpfRng = 'vendor/npm-asset/eac-validator/rng/cpf.rng';
        $jingCmd = '/usr/bin/jing';
        if (file_exists($cpfRng) && file_exists($jingCmd)) {
            $xCon = $this->dbu->readPublishedConstellationByARK('http://n2t.net/ark:/99166/w6fr4rx5');
            $cpfXML = \snac\util\EACCPFSerializer::SerializeCore($xCon->toArray());
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
            $this->enableLogging();
            $this->logDebug("jing result (should be empty): $jingResult");
            $this->logDebug("temp xml: $fn");

            /*
             * Delete before calling assert, so that a failed assert doesn't leave our temp file.
             */
            unlink($fn);
            $this->assertTrue($jingResult == '');
        } else {
            $this->enableLogging();
            $this->logDebug("Not checking via jing");
        }
        /*
         * Uncomment this for debugging.
         */ 
        /* 
         * $cfile = fopen('cpf_out.xml', 'w');
         * fwrite($cfile, $cpfXML);
         * fclose($cfile); 
         */
    }

  }

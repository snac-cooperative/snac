<?php
/**
 * Server Test Class File
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace test\snac\server;
use \snac\server\Server as Server;
use function GuzzleHttp\json_decode;


/**
 * Server Test Suite
 *
 * @author Robbie Hott
 *
 */
class ServerTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var \snac\data\User The User object
     */
    private $user = null;

    /**
     * @var \snac\data\Constellation Constellation object
     */
    private $constellation = null;

    /**
     * Setup function
     *
     * Creates the User object with testing@localhost and generates a temporary session
     */
    public function setUp() {
        $this->user = new \snac\data\User();

        $this->user->setUserName("testing@localhost");
        $this->user->generateTemporarySession(1);
    }

    /**
     * Tests to ensure that the server outputs JSON when given no input
     */
    public function testJSONOutEmpty() {
        try {
            $server = new Server(null);
            $server->run();
            $this->assertNotNull(json_decode($server->getResponse()));
        } catch (\snac\exceptions\SNACInputException $e) {
            // this is good
        }

        try {
            $server = new Server(array());
            $server->run();
            $this->assertNotNull(json_decode($server->getResponse()));
        } catch (\snac\exceptions\SNACInputException $e) {
            // this is good
        }
    }

    /**
     * Tests to ensure that the server outputs JSON when given garbage input
     */
    public function testJSONOutGarbage() {
        try {
            $server = new Server(5);
            $server->run();
            $this->assertNotNull(json_decode($server->getResponse()));
        } catch (\snac\exceptions\SNACUnknownCommandException $e) {
            // this is good
        }

        try {
            $server = new Server("testing");
            $server->run();
            $this->assertNotNull(json_decode($server->getResponse()));
        } catch (\snac\exceptions\SNACUnknownCommandException $e) {
            // this is good
        }
    }

    /**
     * Tests the vocabulary query
     */
    public function testVocabulary() {
        $server = new Server( array(
           "command" => "vocabulary",
                "type" => "entity_type",
                "query_string" => "",
                "entity_type" => "",
                "user" => $this->user->toArray()
        ));
        $server->run();
        $response = $server->getResponse();
        $this->assertNotNull($response);

        if (!strstr($response, "person")
                || !strstr($response, "corporateBody")
                || !strstr($response, "family")) {
                    $this->fail("Vocabulary search didn't return entity types");
                }

    }



    /**
     * Tests that the server can start a session
     */
    public function testStartSession() {
        $server = new Server( array(
                "command" => "start_session"
        ));
        $server->run();
        $response = $server->getResponse();
        $this->assertNotNull($response);

        $response = json_decode($response, true);
        $this->assertEquals("failure", $response["result"]);


        $server = new Server( array(
                "command" => "start_session",
                "user" => $this->user->toArray()
        ));
        $server->run();
        $response = $server->getResponse();
        $this->assertNotNull($response);

        $response = json_decode($response, true);
        $this->assertEquals("success", $response["result"]);


    }


    /**
     * Tests that the server can end a session
     */
    public function testEndSession() {
        $server = new Server( array(
                "command" => "end_session"
        ));
        $server->run();
        $response = $server->getResponse();
        $this->assertNotNull($response);

        $response = json_decode($response, true);
        $this->assertEquals("failure", $response["result"]);


        $server = new Server( array(
                "command" => "end_session",
                "user" => $this->user->toArray()
        ));
        $server->run();
        $response = $server->getResponse();
        $this->assertNotNull($response);

        $response = json_decode($response, true);
        $this->assertEquals("success", $response["result"]);


    }


    /**
     * Tests getting user information from the server
     */
    public function testUserInformation() {
        $server = new Server( array(
                "command" => "user_information"
        ));
        $server->run();
        $response = $server->getResponse();
        $this->assertNotNull($response);

        $response = json_decode($response, true);
        $this->assertEquals("failure", $response["result"]);


        $server = new Server( array(
                "command" => "user_information",
                "user" => $this->user->toArray()
        ));
        $server->run();
        $response = $server->getResponse();
        $this->assertNotNull($response);

        $response = json_decode($response, true);
        $this->assertEquals("success", $response["result"]);

        $this->assertArrayHasKey("user", $response);
        $this->assertArrayHasKey("editing", $response);
        $this->assertArrayHasKey("editing_lock", $response);

    }

    /**
     * Test inserting a constellation by the server
     */
    public function testInsertConstellation() {
        $parser = new \snac\util\EACCPFParser();
        $parser->setConstellationOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        $c = $parser->parseFile("test/snac/server/database/test_record.xml");

        $server = new Server( array(
                "command" => "insert_constellation",
                "user" => $this->user->toArray(),
                "constellation" => $c->toArray()
        ));
        $server->run();
        $response = $server->getResponse();
        $this->assertNotNull($response);

        $response = json_decode($response, true);
        $this->assertEquals("success", $response["result"]);
        $this->assertArrayHasKey("constellation", $response);
        $written = new \snac\data\Constellation($response["constellation"]);


        $server = new Server( array(
                "command" => "unlock_constellation",
                "user" => $this->user->toArray(),
                "constellation" => $written->toArray()
        ));
        $server->run();
        $response = $server->getResponse();
        $this->assertNotNull($response);

        $response = json_decode($response, true);
        $this->assertEquals("success", $response["result"]);
        $this->assertArrayHasKey("constellation", $response);
        $unlocked = new \snac\data\Constellation($response["constellation"]);


        $c2 = new \snac\data\Constellation($c->toArray());


        $this->assertTrue($c->equals($c), "Same constellation is not equal");
        $this->assertTrue($c2->equals($c), "Copy constellation is not equal");

        $this->assertTrue($written->equals($c, false), "Written copy is not equal to original");
        $this->assertTrue($written->equals($unlocked, false), "Written copy is not equal to unlocked version");

        return $unlocked;
    }


    /**
     * Test Reading a constellation from the server
     * @param \snac\data\Constellation $c The Constellation object from testInsertConstellation
     * @depends testInsertConstellation
     */
    public function testReadConstellation(\snac\data\Constellation $c) {

        if ($c == null) {
            $this->fail("Depends on insert constellation");
        }

        $server = new Server( array(
                "command" => "read",
                "user" => $this->user->toArray(),
                "constellationid" => $c->getID(),
                "version" => $c->getVersion()
        ));
        $server->run();
        $response = $server->getResponse();
        $this->assertNotNull($response);

        $response = json_decode($response, true);
        $this->assertArrayHasKey("constellation", $response);
        $read = new \snac\data\Constellation($response["constellation"]);

        $this->assertTrue($read->equals($c, false), "Read copy is not equal to original");

        return $read;
    }

    /**
     * Test editing and updating a constellation by the server
     * @param \snac\data\Constellation $constellation The Constellation object from testReadConstellation
     * @depends testReadConstellation
     */
    public function testEditUpdateConstellation(\snac\data\Constellation $constellation) {
        if ($constellation == null) {
            $this->fail("Depends on read constellation");
        }
        $server = new Server( array(
                "command" => "edit",
                "user" => $this->user->toArray(),
                "constellationid" => $constellation->getID()
        ));
        $server->run();
        $response = $server->getResponse();
        $this->assertNotNull($response);

        $response = json_decode($response, true);
        $this->assertArrayHasKey("constellation", $response);
        $c = new \snac\data\Constellation($response["constellation"]);

        $this->assertTrue($c->equals($constellation, false), "Read copy is not equal to original");


        $nE = new \snac\data\NameEntry();
        $nE->setOperation(\snac\data\NameEntry::$OPERATION_INSERT);
        $nE->setOriginal("Snac Test Original Name");
        $c->addNameEntry($nE);

        $c->getSources()[0]->setOperation(\snac\data\Source::$OPERATION_DELETE);

        $server = new Server( array(
                "command" => "update_constellation",
                "user" => $this->user->toArray(),
                "constellation" => $c->toArray()
        ));
        $server->run();
        $response = $server->getResponse();
        $this->assertNotNull($response);

        $response = json_decode($response, true);
        $this->assertArrayHasKey("constellation", $response);
        $this->assertEquals("success", $response["result"]);


        $c2 = new \snac\data\Constellation($response["constellation"]);

        $this->assertTrue($c->equals($c2, false), "Updated returned copy is not equal to original");


        $server = new Server( array(
                "command" => "read",
                "user" => $this->user->toArray(),
                "constellationid" => $c2->getID(),
                "version" => $c2->getVersion()
        ));
        $server->run();
        $response = $server->getResponse();
        $this->assertNotNull($response);

        $response = json_decode($response, true);
        $this->assertArrayHasKey("constellation", $response);
        $c3 = new \snac\data\Constellation($response["constellation"]);

        // Remove the first source (should have been deleted)
        $newSources = $c->getSources();
        array_shift($newSources);
        $c->setAllSources($newSources);

        $this->assertTrue($c->equals($c3, false), "The updated copy on read is not the same as the updated original");


    }

}

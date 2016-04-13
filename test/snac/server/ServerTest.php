<?php
/**
 * Server Test Class File 
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
use \snac\server\Server as Server;
use function GuzzleHttp\json_decode;


/**
 * Server Test Suite 
 * 
 * @author Robbie Hott
 *
 */
class ServerTest extends PHPUnit_Framework_TestCase {
    
    private $user = null;
    
    public function setUp() {
        $this->user = new \snac\data\User();
        
        $this->user->setEmail("system@localhost");
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
     * Tests to ensure that the server outputs JSON when given correct input 
     */
    public function testJSONOutGood() {

        $server = new Server(array("command" => "edit"));
        $server->run();
        $this->assertNotNull(json_decode($server->getResponse()));
    }
    
    public function testVocabulary() {
        $server = new Server( array(
           "command" => "vocabulary",
                "type" => "entity_type",
                "query_string" => "",
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
    


    public function testStartSession() {
        $server = new Server( array(
                "command" => "start_session"
        ));
        $server->run();
        $response = $server->getResponse();
        $this->assertNotNull($response);
        
        $response = json_decode($response, true);
        $this->assertEquals("failure", $response["result"]);
        

        /**
        $server = new Server( array(
                "command" => "start_session",
                "user" => $this->user->toArray()
        ));
        $server->run();
        $response = $server->getResponse();
        $this->assertNotNull($response);

        $response = json_decode($response, true);
        $this->assertEquals("success", $response["result"]);
        **/

    
    }
    

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
        
        
        $c2 = new \snac\data\Constellation($c->toArray());
        
        
        $this->assertTrue($c->equals($c), "Same constellation is not equal");
        $this->assertTrue($c2->equals($c), "Copy constellation is not equal");
        
        $this->assertTrue($written->equals($c, false), "Written copy is not equal to original");
    }

}

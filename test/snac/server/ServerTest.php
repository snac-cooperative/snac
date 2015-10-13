<?php
use \snac\server\Server as Server;
class ServerTest extends PHPUnit_Framework_TestCase {

    public function testJSONOut() {

        $server = new Server(null);
        
        $this->assertNotNull(json_decode($server->getResponse()));
    }
}
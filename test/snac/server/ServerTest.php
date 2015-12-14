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


/**
 * Server Test Suite 
 * 
 * @author Robbie Hott
 *
 */
class ServerTest extends PHPUnit_Framework_TestCase {

    /**
     * Tests to ensure that the server outputs JSON when given no input 
     */
    public function testJSONOutEmpty() {

        $server = new Server(null);
        $this->assertNotNull(json_decode($server->getResponse()));

        $server = new Server(array());
        $this->assertNotNull(json_decode($server->getResponse()));
    }

    /**
     * Tests to ensure that the server outputs JSON when given garbage input
     */
    public function testJSONOutGarbage() {

        $server = new Server(5);
        $this->assertNotNull(json_decode($server->getResponse()));
        
        $server = new Server("testing");
        $this->assertNotNull(json_decode($server->getResponse()));
    }

    /**
     * Tests to ensure that the server outputs JSON when given correct input 
     */
    public function testJSONOutGood() {

        $server = new Server(array("command" => "edit"));
        $this->assertNotNull(json_decode($server->getResponse()));
    }

}

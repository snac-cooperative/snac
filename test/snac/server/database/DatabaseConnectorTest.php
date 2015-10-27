<?php
use \snac\server\database\DatabaseConnector;

class DatabaseConnectorTest extends PHPUnit_Framework_TestCase {
    
    public function testDatabaseConnection() {
        
        try {
            $db = new \snac\server\database\DatabaseConnector();
        } catch (Exception $e) {
            $this->fail("Could not connect to database");
        }
    }
}
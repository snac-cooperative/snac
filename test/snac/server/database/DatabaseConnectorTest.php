<?php
/**
 * Database Connector Test File
 *
 *
 * License:
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace test\snac\server\database;
use \snac\server\database\DatabaseConnector;

/**
 * Database Connector Test Suite
 * 
 * @author Robbie Hott
 *
 */
class DatabaseConnectorTest extends \PHPUnit_Framework_TestCase {
    
    /**
     * Tests the generic connection (part of the constructor) 
     */
    public function testDatabaseConnection() {
        
        try {
            $db = new \snac\server\database\DatabaseConnector();
        } catch (Exception $e) {
            $this->fail("Could not connect to database");
        }
    }
    
    /**
     * Tests a simple prepare statement
     */
    public function testDatabasePrepare() {
        try {
            $db = new \snac\server\database\DatabaseConnector();
            $db->prepare("testStatement", "SELECT NULL;");
        } catch (Exception $e) {
            $this->fail("Could not prepare no-op. " . $e->getMessage());
        }
    }
    
    /**
     *  Tests a bad prepare statement
     */
    public function testDatabaseBadPrepare() {
        try {
            $db = new \snac\server\database\DatabaseConnector();
            $db->prepare("testStatement3", "NOT A POSTGRES STATEMENT;");
            $this->fail("Allowed prepare statement that was garbage.");
        } catch (\snac\exceptions\SNACDatabaseException $e) {
            $message = $e->getMessage();
            $this->assertEquals("pg_prepare(): Query failed: ERROR:  syntax error at or near \"NOT\"\nLINE 1: NOT A POSTGRES STATEMENT;\n        ^", substr($message, 0));
        }
        
    }
    
    /**
     * Test an execute without a prepare
     */
    public function testDatabaseExecuteNoPrepare() {
        try {
            $db = new \snac\server\database\DatabaseConnector();
            $db->execute("testStatement2", array());
            $this->fail("Allowed execute with no prepare.");
        } catch (\snac\exceptions\SNACDatabaseException $e) {
            $message = $e->getMessage();
            $this->assertEquals("pg_execute(): Query failed: ERROR:  prepared statement \"testStatement2\" does not exist", substr($message, 0));
        }
    }
    
    /**
     * Tests an execute with a very simple prepare
     */
    public function testDatabaseExecuteSimplePrepare() {
        try {
            $db = new \snac\server\database\DatabaseConnector();
            $db->prepare("testStatementSimple", "SELECT NULL;");
            $db->execute("testStatementSimple", array());
        } catch (Exception $e) {
            $this->fail("Could not prepare and execute no-op. " . $e->getMessage());
        }
    }
    
    /**
     * Tests a query with a bad statement
     */
    public function testDatabaseQueryBadStatement() {
        try {
            $db = new \snac\server\database\DatabaseConnector();
            $db->query("NOT A POSTGRES STATEMENT;", array());
            $this->fail("Allowed query a statement that was garbage.");
        } catch (\snac\exceptions\SNACDatabaseException $e) {
            $message = $e->getMessage();
            $this->assertEquals("pg_prepare(): Query failed: ERROR:  syntax error at or near \"NOT\"\nLINE 1: NOT A POSTGRES STATEMENT;\n        ^", substr($message, 0));
        }
        
    }
    
    /**
     * Tests a query with a very simple statement
     */
    public function testDatabaseQuerySimpleStatement() {
        try {
            $db = new \snac\server\database\DatabaseConnector();
            $db->query("SELECT NULL;", array());
        } catch (Exception $e) {
            $this->fail("Could not query (prepare and execute) no-op. " . $e->getMessage());
        }
    }
}

<?php
/**
 * ServerInterface Interface File
 *
 * Contains the main server interface
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 * the Regents of the University of California
 */
namespace snac\interfaces;

/**
 * Server Interface
 *
 * This is the main server interface. Any class implementing this interface should have the
 * constructor, run method that runs the server, and response functions.
 *
 * @author Robbie Hott
 */
interface ServerInterface {

    /**
     * Constructor
     *
     * Requires the input to the server as an associative array
     *
     * @param array $input Input to the server
     */
    public function __construct($input);

    /**
     * Run Method
     *
     * Starts the server
     */
    public function run();

    /**
     * Get Response Headers
     *
     * Returns the headers for the server's return value.
     *
     * @return array headers for output
     */
    public function getResponseHeaders();

    /**
     * Get Return Statement
     *
     * This should compile the Server's response statement.
     *
     * @return string server response appropriately encoded
     */
    public function getResponse();
}

<?php
/**
 * Constellation Test File 
 *
 *
 * License:
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

/**
 * Constellation Test Suite
 * 
 * @author Robbie Hott
 *
 */
class ConstellationTest extends PHPUnit_Framework_TestCase {

    /**
     * Test that reading a JSON object, then serializing back to JSON gives the same result 
     */
    public function testJSONJSON() {
        $identity = new \snac\data\Constellation();
        $jsonIn = file_get_contents("test/snac/data/json/constellation_test.json");

        $identity->fromJSON($jsonIn);

        $this->assertEquals($jsonIn, $identity->toJSON());
    }
    
}

<?php
/**
 * Source Test File 
 *
 *
 * License:
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
use \snac\data\Source;

/**
 * Source Test Suite
 * 
 * @author Robbie Hott
 *
 */
class SourceTest extends PHPUnit_Framework_TestCase {

    /**
     * Test equal to null 
     */
    public function testEqualNull() {
        $s = new Source();
        $this->assertFalse($s->equals(null));
    }

    /**
     * Test non-equal IDs 
     */
    public function testEqualIDs() {
        $s = new Source();
        $s->setID(4);
        $s->setText("Blah");
        $t = new Source();
        $this->assertFalse($s->equals($t));
        $this->assertFalse($t->equals($s));

        $t->setID(5);
        $this->assertFalse($s->equals($t));
        $this->assertFalse($t->equals($s));

        $t->setID(4);
        $this->assertTrue($s->equals($t));
        $this->assertTrue($t->equals($s));
    }
}

/**
 * Database Utility Test File
 *
 *
 * License:
 *
 * @author Tom Laudeman
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

/**
 * Database Utils test suite
 * 
 * @author Tom Laudeman
 *
 */
class WorkflowTest extends PHPUnit_Framework_TestCase 
{
    
    public function testConstructor()
    {
        $wfObj = new snac\server\workflow\Workflow('server');
        // Did we get a non-null object?
        $this->assertNotNull($wfObj);
        // Does the sanity check return true?
        $this->assertTrue($wfObj->sanityCheckStates();

    }

}
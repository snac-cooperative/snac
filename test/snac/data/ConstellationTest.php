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
namespace test\snac\data;

/**
 * Constellation Test Suite
 *
 * @author Robbie Hott
 *
 */
class ConstellationTest extends \PHPUnit\Framework\TestCase {

    /**
     * Test that trying to read garbage instead of JSON results in not importing any data
     */
    public function testJSONGarbage() {
        $identity = new \snac\data\Constellation();
        $jsonOrig = $identity->toJSON();


        $identity->fromJSON("Garbage, not JSON");

        $this->assertEquals($jsonOrig, $identity->toJSON());
    }

    /**
     * Test that trying to read empty JSON instead of Constellation JSON results in not importing any data
     */
    public function testEmptyJSON() {
        $identity = new \snac\data\Constellation();
        $jsonOrig = $identity->toJSON();


        $identity->fromJSON("{}");

        $this->assertEquals($jsonOrig, $identity->toJSON());
    }

    /**
     * Test that reading a JSON object, then serializing back to JSON gives the same result
     * Changing any constellation objects is likely to require a change here.
     */
    public function testJSONJSON() {
        $identity = new \snac\data\Constellation();
        $jsonIn = file_get_contents("test/snac/data/json/constellation_test.json");
        /*
         * rtrim() the input since some people have their editor defaulting to adding a newline at the end of
         * every file. We have to manually edit constellation_test.json. Don't let trailing whitespace at the
         * end of the file interfere with the match below.
         */
        $jsonIn = rtrim($jsonIn);

        $identity->fromJSON($jsonIn);

        /*
         * Run only this test if you uncomment the section below to update the constellation test file.
         * phpunit --filter 'testJSONJSON' ./test/snac/data/ConstellationTest.php  > test.log 2>&1 &
         * diff -y new_constellation_test.json test/snac/data/json/constellation_test.json | less
         * cp test/snac/data/json/constellation_test.json ~/constellation_test-`date +"%F-%H%M%S"`.json
         * mv new_constellation_test.json test/snac/data/json/constellation_test.json
         */

         //$cfile = fopen('new_constellation_test.json', 'w');
         //fwrite($cfile, $identity->toJSON(false));
         //fclose($cfile);

        $this->assertEquals($jsonIn, $identity->toJSON(false));
    }

    /**
     * Test Empty Check
     *
     * Tests whether the isEmpty function works on both an empty and non-empty Constellation
     */
    public function testConstellationEmpty() {
        $identity = new \snac\data\Constellation();
        $this->assertTrue($identity->isEmpty(), "Empty Constellation was not determined to be empty.");

        $jsonIn = file_get_contents("test/snac/data/json/constellation_test2.json");
        $arrayIn = json_decode($jsonIn, true);
        $identity->fromJSON($jsonIn);

        $this->assertFalse($identity->isEmpty(), "Non-empty Constellation was determined to be empty.");
    }


    /**
     * Test Diff
     *
     * Tests whether the diff function works
     */
    public function testConstellationDiff() {
        $id1 = new \snac\data\Constellation();
        $id2 = new \snac\data\Constellation();
        $test = $id1->diff($id2);
        $this->assertNull($test["intersection"], "Intersection of empty constellations was not null");
        $this->assertNull($test["this"], "This of diff of empty constellations was not null");
        $this->assertNull($test["other"], "Other of diff of empty constellations was not null");

        $jsonIn = file_get_contents("test/snac/data/json/constellation_simple.json");
        $arrayIn = json_decode($jsonIn, true);
        $id2->fromJSON($jsonIn);

        $test = $id1->diff($id2);
        $this->assertNull($test["intersection"], "Intersection of empty and full constellations was not null");
        $this->assertNull($test["this"], "This of diff of empty constellation with full one was not null");
        $this->assertNotNull($test["other"], "Other of diff of empty constellation with full one was null");
        $this->assertTrue($id2->equals($test["other"]), "The other of diff with empty should be equal to original full constellation");

        $test = $id2->diff($id1);
        $this->assertNull($test["intersection"], "Intersection of full and empty constellations was not null");
        $this->assertNull($test["other"], "Other of diff of full with empty constellation was not null");
        $this->assertNotNull($test["this"], "This of diff of full with empty constellation was not null");
        $this->assertTrue($id2->equals($test["this"]), "The this of diff with empty should be equal to original full constellation");

        $test = $id2->diff($id2);
        $this->assertNotNull($test["intersection"], "Diff with itself should have full intersection");
        $this->assertNull($test["other"], "self-diff produced non-empty other");
        $this->assertNull($test["this"], "self-diff produced non-empty this");
        $this->assertTrue($id2->equals($test["intersection"]), "self-diff intersection should be equal to original full constellation");


        $id1->fromJSON($jsonIn);
        $test = $id2->diff($id1);
        $this->assertNotNull($test["intersection"], "Diff with identical copy should have full intersection");
        $this->assertNull($test["other"], "diff of copy produced non-empty other");
        $this->assertNull($test["this"], "diff of copy produced non-empty this");
        $this->assertTrue($id1->equals($test["intersection"]), "diff of copy intersection should be equal to original full constellation 1");
        $this->assertTrue($id2->equals($test["intersection"]), "diff of copy intersection should be equal to original full constellation 2");

    }

    /**
     * Test that reading a larger JSON object, then serializing back to JSON gives the same result
     *
     * Changing any constellation objects is likely to require a change here.
     */
    public function testJSONJSON2() {
        $identity = new \snac\data\Constellation();
        $jsonIn = file_get_contents("test/snac/data/json/constellation_test2.json");
        $arrayIn = json_decode($jsonIn, true);
        $identity->fromJSON($jsonIn);


        /*
         * Uncomment the lines below to write out a new copy of constellation_test2.json which you need to
         * verify differs only by whatever you added to the constellation.
         *
         * Run only this test if you uncomment the section below to update the constellation test file.
         * phpunit --filter 'testJSONJSON2' ./test/snac/data/ConstellationTest.php  > test.log 2>&1 &
         * diff -y new_constellation_test2.json test/snac/data/json/constellation_test2.json | less
         * diff -y --suppress-common-lines new_constellation_test2.json test/snac/data/json/constellation_test2.json | sort -u | uniq
         * cp test/snac/data/json/constellation_test2.json ~/constellation_test2-`date +"%F-%H%M%S"`.json
         * mv new_constellation_test2.json test/snac/data/json/constellation_test2.json
         */

        /*
         * $cfile = fopen('new_constellation_test2.json', 'w');
         * fwrite($cfile, $identity->toJSON(false));
         * fclose($cfile);
         */

        unset($jsonIn);
        $arrayOut = json_decode($identity->toJSON(false), true);

        $this->assertEquals($arrayIn, $arrayOut);
    }

    /**
     * Test that reading a JSON object over another object will replace that object
     */
    public function testJSONOverwrite() {
        $identity = new \snac\data\Constellation();
        $identity2 = new \snac\data\Constellation();
        $jsonIn1 = file_get_contents("test/snac/data/json/constellation_test.json");
        $jsonIn2 = file_get_contents("test/snac/data/json/constellation_test2.json");

        $identity->fromJSON($jsonIn1);
        $identity2->fromJSON($jsonIn2);
        $identity2->fromJSON($jsonIn1);

        $this->assertEquals($identity->toJSON(), $identity2->toJSON());
    }

    /**
     * Test that reading a larger JSON object multiple times does not result in memory error
     */
    public function testJSONExtreme() {

        for ($i = 0; $i < 10; $i++) {
            $identity = new \snac\data\Constellation();
            $jsonIn = file_get_contents("test/snac/data/json/constellation_test2.json");
            $identity->fromJSON($jsonIn);
            $arrayIn = json_decode($jsonIn, true);
            unset($jsonIn);
            $this->assertEquals($arrayIn, $identity->toArray(false));
            unset($identity);
            unset($arrayIn);
        }
    }


    /**
     * Test that reading a partial JSON Object works
     */
    public function testPartialJSON() {
        $identity = new \snac\data\Constellation();
        $jsonIn = file_get_contents("test/snac/data/json/constellation_simple.json");

        $identity->fromJSON($jsonIn);

        $arrayIn = json_decode($jsonIn, true);
        $idArray = $identity->toArray(false);

        /*
         * Uncomment the lines below to write out a new copy of constellation_simple.json which you need to
         * verify differs only by whatever you added to the constellation.
         *
         * Run only this test if you uncomment the section below to update the constellation test file.
         * phpunit --filter 'testPartialJSON' ./test/snac/data/ConstellationTest.php  > test.log 2>&1 &
         * cp test/snac/data/json/constellation_simple.json ~/constellation_simple-`date +"%F-%H%M%S"`.json
         * mv new_constellation_simple.json test/snac/data/json/constellation_simple.json
         */

        /*
         * $cfile = fopen('new_constellation_simple.json', 'w');
         * fwrite($cfile, $identity->toJSON(false));
         * fclose($cfile);
         */

        $this->assertEquals($arrayIn["nameEntries"], $idArray["nameEntries"]);
    }

    /**
     * Test empty equals
     */
    public function testEqualsEmpty() {
        $c1 = new \snac\data\Constellation();
        $c2 = new \snac\data\Constellation();

        // equals another empty
        $this->assertTrue($c1->equals($c2));
        $this->assertTrue($c1->equals($c2, false));

        // equals itself
        $this->assertTrue($c1->equals($c1));
        $this->assertTrue($c1->equals($c1, false));
    }

    /**
     * Test empty equals
     */
    public function testEqualsNull() {
        $c1 = new \snac\data\Constellation();

        // equals another empty
        $this->assertFalse($c1->equals(null));
        $this->assertFalse($c1->equals(null, false));

    }

    /**
     * Test that non-empty constellations are equal
     */
    public function testEqualsNonEmpty() {
        $c1 = new \snac\data\Constellation();
        $c2 = new \snac\data\Constellation();
        $jsonIn = file_get_contents("test/snac/data/json/constellation_test.json");

        $c1->fromJSON($jsonIn);
        $c2->fromJSON($jsonIn);

        // equals another
        $this->assertTrue($c1->equals($c2));
        $this->assertTrue($c1->equals($c2, false));

        // equals itself
        $this->assertTrue($c1->equals($c1));
        $this->assertTrue($c1->equals($c1, false));

        $c1->setID(1);
        $this->assertFalse($c1->equals($c2));
        $this->assertTrue($c1->equals($c2, false));
        $this->assertFalse($c2->equals($c1));
        $this->assertTrue($c2->equals($c1, false));
        $c1->setVersion(234);
        $this->assertFalse($c1->equals($c2));
        $this->assertTrue($c1->equals($c2, false));
        $this->assertFalse($c2->equals($c1));
        $this->assertTrue($c2->equals($c1, false));
        $c1->setOperation(\snac\data\Constellation::$OPERATION_INSERT);
        $this->assertFalse($c1->equals($c2));
        $this->assertTrue($c1->equals($c2, false));
        $this->assertFalse($c2->equals($c1));
        $this->assertTrue($c2->equals($c1, false));


    }

    /**
     * Test that non-empty constellations are not equal
     */
    public function testNotEqualsNonEmpty() {
        $c1 = new \snac\data\Constellation();
        $c2 = new \snac\data\Constellation();
        $jsonIn = file_get_contents("test/snac/data/json/constellation_test.json");

        $c1->fromJSON($jsonIn);
        $c2->fromJSON($jsonIn);

        $date = new \snac\data\SNACDate();
        $c2->addDate($date);


        $this->assertFalse($c1->equals($c2));
        $this->assertFalse($c1->equals($c2, false));

        $this->assertFalse($c2->equals($c1));
        $this->assertFalse($c2->equals($c1, false));

    }

    /**
     * Test that equals() can ignore metadata
     */
     public function testEqualsCanIgnoreMetadata() {
         $id1 = new \snac\data\Constellation();
         $id2 = new \snac\data\Constellation();
         $jsonIn = file_get_contents("test/snac/data/json/constellation_test.json");
         $id1->fromJSON($jsonIn);
         $id2->fromJSON($jsonIn);

         $data = array("dataType"   => "SNACControlMetadata",
                       "sourceData" => "University of Virginia",
                       "language"   => "Spanish",
                       "note"       => "This is a mocked metadata");

         $metadata = new \snac\data\SNACControlMetadata($data);

         $id1->addSNACControlMetadata($metadata);
         $this->assertFalse($id1->equals($id2), "Equals failed to compare SNACControlMetadata by default");
         $this->assertTrue($id1->equals($id2, false, false), "Equals failed to ignore SNACControlMetadata");
     }

     /**
      * Test that equals() can ignore NameEntry contributors
      */
     public function testEqualsCanIgnoreContributors() {
         $id1 = new \snac\data\Constellation();
         $id2 = new \snac\data\Constellation();
         $jsonIn = file_get_contents("test/snac/data/json/constellation_test.json");
         $id1->fromJSON($jsonIn);
         $id2->fromJSON($jsonIn);
         $name1 = new \snac\data\NameEntry();
         $name2 = new \snac\data\NameEntry();

         $nameData1 = array("dataType" => "NameEntry",
                            "contributors" => [[ "dataType" => "Contributor", "name" => "Original"]]);

         $nameData2 = array("dataType" => "NameEntry",
                            "contributors" => [[ "dataType" => "Contributor", "name" => "Different"]]);

        $name1->fromArray($nameData1);
        $name2->fromArray($nameData2);
        $id1->addNameEntry($name1);
        $id2->addNameEntry($name2);

        //should be inequal with $strict and $checkSubcomponents
        $this->assertFalse($id1->equals($id2, true, true));
        //should be inequal with $checkSubcomponents
        $this->assertFalse($id1->equals($id2, false, true));

        $this->assertTrue($id1->equals($id2, true, false));

        //should be equal without $strict and $checkSubcomponents
        $this->assertTrue($id1->equals($id2, false, false), "Equals failed to ignore contributors");
     }

     /**
      * Test that equals() can ignore NameEntry components
      */
     public function testEqualsCanIgnoreComponents() {
         $id1 = new \snac\data\Constellation();
         $id2 = new \snac\data\Constellation();
         $jsonIn = file_get_contents("test/snac/data/json/constellation_test.json");
         $id1->fromJSON($jsonIn);
         $id2->fromJSON($jsonIn);
         $name1 = new \snac\data\NameEntry();
         $name2 = new \snac\data\NameEntry();

         $nameData1 = array("dataType" => "NameEntry",
                            "components" => [["dataType" => "NameComponent", "text" => "Original"]]);

         $nameData2 = array("dataType" => "NameEntry",
                            "components" => [["dataType" => "NameComponent", "text" => "Different"]]);

        $name1->fromArray($nameData1);
        $name2->fromArray($nameData2);
        $id1->addNameEntry($name1);
        $id2->addNameEntry($name2);

        //should be inequal with $strict and $checkSubcomponents
        $this->assertFalse($id1->equals($id2, true, true));
        //should be inequal with $checkSubcomponents
        $this->assertFalse($id1->equals($id2, false, true));

        $this->assertTrue($id1->equals($id2, true, false));


        //should be equal without $strict and $checkSubcomponents
        $this->assertTrue($id1->equals($id2, false, false), "Equals failed to ignore components");
     }

    /**
     * Test that combining constellations removes all ids
     */
    public function testCombine() {
        $c1 = new \snac\data\Constellation();
        $c2 = new \snac\data\Constellation();
        $c3 = new \snac\data\Constellation();
        $jsonIn = file_get_contents("test/snac/data/json/constellation_test_wIDs.json");

        $c2->fromJSON($jsonIn);
        $c3->fromJSON($jsonIn);
        $this->assertTrue($c2->equals($c3));

        $c1->combine($c2);
        // Combining has the side effect of wiping out $c2.
        $diff = $c2->diff($c1, true);
        $this->assertTrue($diff["this"] == null && $diff["other"] == null);
        $this->assertFalse($diff["intersection"] == null);

        $c2->setArkID(null);
        $c2->setID(null);
        $c2->setVersion(null);
        $c2->setEntityType(null);

        $this->assertTrue($c1->equals($c2));

    }

}

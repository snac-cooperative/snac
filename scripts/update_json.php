#!/usr/bin/env php
<?php
/**
 * Create up to date versions of two files critical to testing. You must manually overwrite the old file with
 * the new after running this script.
 * 
 * @author Tom Laudeman
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

// Include the global autoloader generated by composer
include "../vendor/autoload.php";

$identity = new \snac\data\Constellation();
$jsonIn = file_get_contents("test/snac/data/json/constellation_test.json");
$identity->fromJSON($jsonIn);
file_put_contents("test/snac/data/json/constellation_test_v2.json", $identity->toJSON(false));
printf("Wrote: test/snac/data/json/constellation_test_v2.json\n");

$identity = new \snac\data\Constellation();
$jsonIn = file_get_contents("test/snac/data/json/constellation_test2.json");
$identity->fromJSON($jsonIn);
file_put_contents("test/snac/data/json/constellation_test2_v2.json", $identity->toJSON(false));
printf("Wrote: test/snac/data/json/constellation_test2_v2.json\n");
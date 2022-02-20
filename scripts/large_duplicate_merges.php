#!/usr/bin/env php
<?php
/**
 * Batch merge duplicate CPFs with exact name matches. Script takes a csv with one or two columns of name strings
 * and merges all CPFs with those exact names together.
 *
 *
 * @author Joseph Glass
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2021 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

// Include the global autoloader generated by composer
include "../vendor/autoload.php";

use \snac\server\Server as Server;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;


set_time_limit(0);

// Set up the global log stream
$log = new StreamHandler(\snac\Config::$LOG_DIR . \snac\Config::$SERVER_LOGFILE, Logger::WARNING);

$db = new \snac\server\database\DatabaseConnector();

$apikey = '[API_KEY_HERE]';

$handle = fopen("large_duplicate_merges.csv", "r");


$mergelist = [];
$failed = [];

// single column version
$db->prepare("find_merge_ids", "SELECT ic_id FROM name_index WHERE name_entry_lower = $1");
while (($data = fgetcsv($handle, 1000, ',')) !== false) {
    $icids = [];
    $lowername = strtolower($data[0]);
    $results = $db->execute("find_merge_ids", array($lowername));
    while ($row = $db->fetchrow($results)) {
        $icids[] = $row["ic_id"];
    }

    if (count($icids) >= 2) {
        $mergelist[$lowername] = $icids;
    } else {
        echo $lowername;
    }
}

// two column version
// $db->prepare("find_merge_ids", "SELECT ic_id FROM name_index WHERE name_entry IN ($1, $2)");
// while (($data = fgetcsv($handle, 1000, ',')) !== false) {
//     $icids = [];
//     $results = $db->execute("find_merge_ids", array($data[0], $data[1]));
//     while ($row = $db->fetchrow($results)) {
//         $icids[] = $row["ic_id"];
//     }

//     if (count($icids) >= 2) {
//         $mergelist[$data[0]] = $icids;
//     } else {
//         echo "No duplicates found for: " . $data[0] . "\n";
//     }
// }


// $mergelist = ["Abbotsford Club (Edinburgh, Scotland)" => [ "341146", "3600936", "7314040", ...]];
foreach ($mergelist as $name => $duplicateIcids) {
    $input =    ["command" => "constellation_auto_merge", "constellationids" => $duplicateIcids, "apikey" => $apikey];
    print_r($input);

    echo "Beginning Merge: " . $name;

    try {
        $server = new Server($input);
        $server->run();

        $response = $server->getResponse();

        // Just print result: success/failure.
        echo substr($response, 0, 27);
    } catch (\Throwable $th) {
        $failed[$name] = $duplicateIcids;
        echo $th;
    }

    // Skip json_decode for speed
    // $response = json_decode($response, true);
}

print_r($failed);

// php large_duplicate_merges.php 2>&1 | tee large_duplicate_merges.log
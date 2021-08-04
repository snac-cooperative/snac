#!/usr/bin/env php
<?php
/**
 * Bulk ingest of files given on standard input
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
// Include the global autoloader generated by composer
include "../vendor/autoload.php";

use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

// Set up the global log stream
$log = new StreamHandler(\snac\Config::$LOG_DIR . \snac\Config::$SERVER_LOGFILE, Logger::DEBUG);

// Did we parse a file?
$parsedFile = false;

// SNAC Postgres DB Handler
$dbu = new snac\server\database\DBUtil();

// SNAC Postgres User Handler
$dbuser = new \snac\server\database\DBUser();
$tempUser = new \snac\data\User();
$tempUser->setUserName("system@localhost");
$user = $dbuser->readUser($tempUser);
$user->generateTemporarySession();

// ElasticSearch Handler
$eSearch = null;
if (\snac\Config::$USE_ELASTIC_SEARCH) {
    $eSearch = Elasticsearch\ClientBuilder::create()
        ->setHosts([\snac\Config::$ELASTIC_SEARCH_URI])
        ->setRetries(0)
        ->build();
}


if (is_dir($argv[1])) {
    printf("Opening dir: $argv[1]\n");
    $dh = opendir($argv[1]);
    printf("Done.\n");

    $cache = fopen('parsed_list.csv', 'w');

    // Create new parser
    $e = new \snac\util\EACCPFParser();
    $e->setConstellationOperation("insert");

    while (($short_file = readdir($dh))) {

        if ($short_file == '.' or $short_file == '..') {
            continue;
        }

        // Create a full path file name
        $filename = $argv[1]."/$short_file";

        $parsedFile = true;

        // Print out a message stating that this file is being parsed
        echo "Parsing: $filename\n";

        $constellation = $e->parseFile($filename);

        // Make sure it isn't already in the database
        $check = $dbu->readPublishedConstellationByARK($constellation->getArk(), true);

        $written = null;
        if ($check !== false) {
            $written = $dbu->readConstellation($check->getID());
        } else {
            // Write the constellation to the DB
            $written = $dbu->writeConstellation($user, $constellation, "bulk ingest of merged", 'ingest cpf');
            echo "    Written: " . $written->getID() . "\n";
        }

        // Update it to be published
        $dbu->writeConstellationStatus($user, $written->getID(), "published");

        // index ES
        indexESearch($written);

        fputcsv($cache, array($written->getArk(), $written->getID()));

        // try to help memory by freeing up the constellation
        unset($written);
    }

    echo "\nCompleted input of records\n\n";
}

fclose($cache);

echo "\nFixing up Relations:\n";
// Go back and fix the constellation relations
$handle = null;
if (($handle = fopen("parsed_list.csv", "r")) === FALSE) {
    die ("Cannot read temp file");
}
while (($seenEntry = fgetcsv($handle, 1000, ",")) !== FALSE) {
    $ark = $seenEntry[0];
    $id = $seenEntry[1];
    echo "Editing: $ark ($id)\n    Relations: ";
    $constellation = $dbu->readConstellation($id);
    // For each relation, try to do a lookup
    foreach ($constellation->getRelations() as &$rel) {
        if ($other = $dbu->readPublishedConstellationByARK($rel->getTargetArkID(), true) !== false) {
            $rel->setTargetConstellation($other->getID());
            $rel->setTargetEntityType($other->getEntityType());
            $rel->setOperation(\snac\data\AbstractData::$OPERATION_UPDATE);
            echo ".";
        }
    }

    // Update the constellation in the database
    try {
        // Write the constellation to the DB
        $written = $dbu->writeConstellation($user, $constellation, "updated Constellation Relations", 'locked editing');
        $dbu->writeConstellationStatus($user, $written->getID(), "published");
        indexESearch($written);
        echo "\n    Published\n";
        unset($written);
    } catch (\snac\exceptions\SNACValidationException $e) {
        echo "      no changes to write\n";
    } catch (\Exception $e) {
        echo "      silently ignoring error...\n";
    }
    unset($constellation);
}
fclose($cache);

// If no file was parsed, then print the output that something went wrong
if ($parsedFile == false) {
    echo "No files in directory\n\n"
        . "Reads files from the snac merged cpf directory (1st argument),\n"
        . "then parses the files into Identity Constellations and adds them\n"
        . "to the database using standard DBUtil calls (as if it were the server).\n"
        . "Sample usage: ./ingest_all.php /path/to/directory\n\n";
}

function indexESearch($written) {
    global $eSearch;
    if ($eSearch != null) {
        $params = [
                'index' => \snac\Config::$ELASTIC_SEARCH_BASE_INDEX,
                'id' => $written->getID(),
                'body' => [
                        'nameEntry' => $written->getPreferredNameEntry()->getOriginal(),
                        'arkID' => $written->getArk(),
                        'id' => $written->getID(),
                        'timestamp' => date("c")
                ]
        ];

        $eSearch->index($params);
    }
}

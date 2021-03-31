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

$limit = 100;

$realdir = $argv[1];
$sampledir = $argv[1];
if (isset($argv[2]))
    $sampledir = $argv[2];

echo "Time: " . date("Y-m-d H:i:s") . "\n";
if (is_dir($realdir) && is_dir($sampledir)) {
    printf("Opening dir: $sampledir\n");
    $dh = opendir($sampledir);
    printf("Done.\n");
    $xx = 0;

    // Create new parser
    $e = new \snac\util\EACCPFParser();
    $e->setConstellationOperation("insert");
    printf("Done creating new parser.\n");

    while (($short_file = readdir($dh)) && $xx++ <= $limit + 1) {

        if ($short_file == '.' or $short_file == '..') {
            continue;
        }

        // Create a full path file name
        $filename = $sampledir."/$short_file";

        $parsedFile = true;

        // Print out a message stating that this file is being parsed
        echo "Parsing: $filename\n";

        $constellation = $e->parseFile($filename);

        $rels = count($constellation->getRelations()) + count($constellation->getResourceRelations());

        //list($junk, $parts) = explode("ark:/", $constellation->getArk());
        //$ark = "http://socialarchive.iath.virginia.edu/" . "ark:/" . $parts;

        //$rels = trim(shell_exec("curl -s $ark  | grep \"badge pull-right\" | sed 's/^.*\">//' | sed 's/<.*//' | awk '{s+=$1}END{print s}'"));

        if ($rels < 350) {
            // Write the constellations to the DB
            $written = $dbu->writeConstellation($user, $constellation, "bulk ingest of merged", 'ingest cpf');

            // Update them to be published
            $dbu->writeConstellationStatus($user, $written->getID(), "published");

            indexESearch($written);

            // If this is published, then it should point to itself in the lookup table.
            $selfDirect = array($written);
            $dbu->updateConstellationLookup($written, $selfDirect);
        }
    }
    echo "Time: " . date("Y-m-d H:i:s") . "\n";

    // Washington
    echo "Parsing: George Washington : ";
    $constellation = $e->parseFile($realdir."/99166-w6028ps4.xml");
    $written = $dbu->writeConstellation($user, $constellation, "bulk ingest of merged", 'ingest cpf');
    // Update them to be published
    $dbu->writeConstellationStatus($user, $written->getID(), "published");
    echo $written->getID()."\n";
    indexESearch($written);
    // If this is published, then it should point to itself in the lookup table.
    $selfDirect = array($written);
    $dbu->updateConstellationLookup($written, $selfDirect);
    echo "Time: " . date("Y-m-d H:i:s") . "\n";

    // Jefferson
    echo "Parsing: Thomas Jefferson : ";
    $constellation = $e->parseFile($realdir."/99166-w6w9576g.xml");
    $written = $dbu->writeConstellation($user, $constellation, "bulk ingest of merged", 'ingest cpf');
    // Update them to be published
    echo $written->getID()."\n";
    $dbu->writeConstellationStatus($user, $written->getID(), "published");
    indexESearch($written);
    // If this is published, then it should point to itself in the lookup table.
    $selfDirect = array($written);
    $dbu->updateConstellationLookup($written, $selfDirect);
    echo "Time: " . date("Y-m-d H:i:s") . "\n";

    // Oppenheimer
    echo "Parsing: Robert Oppenheimer\n";
    $constellation = $e->parseFile($realdir."/99166-w6v1266v.xml");
    $written = $dbu->writeConstellation($user, $constellation, "bulk ingest of merged", 'ingest cpf');
    // Update them to be published
    $dbu->writeConstellationStatus($user, $written->getID(), "published");
    indexESearch($written);
    // If this is published, then it should point to itself in the lookup table.
    $selfDirect = array($written);
    $dbu->updateConstellationLookup($written, $selfDirect);
    echo "Time: " . date("Y-m-d H:i:s") . "\n";

    // Joseph Henry (large record)
    echo "Parsing: Joseph Henry\n";
    $constellation = $e->parseFile($realdir."/99166-w6st7qq0.xml");
    $written = $dbu->writeConstellation($user, $constellation, "bulk ingest of merged", 'ingest cpf');
    // Update them to be published
    $dbu->writeConstellationStatus($user, $written->getID(), "published");
    indexESearch($written);
    // If this is published, then it should point to itself in the lookup table.
    $selfDirect = array($written);
    $dbu->updateConstellationLookup($written, $selfDirect);
    echo "Time: " . date("Y-m-d H:i:s") . "\n";

    //Now, write samples to edit
    echo "Parsing: Sparse other sample files .";
    $constellation = $e->parseFile($realdir."/99166-w6988j92.xml");
    $written = $dbu->writeConstellation($user, $constellation, "bulk ingest of merged", 'ingest cpf');
    $dbu->writeConstellationStatus($user, $written->getID(), "published");
    indexESearch($written);
    // If this is published, then it should point to itself in the lookup table.
    $selfDirect = array($written);
    $dbu->updateConstellationLookup($written, $selfDirect);
    echo ".";
    $constellation = $e->parseFile($realdir."/99166-w69b3nm4.xml");
    $written = $dbu->writeConstellation($user, $constellation, "bulk ingest of merged", 'ingest cpf');
    $dbu->writeConstellationStatus($user, $written->getID(), "published");
    indexESearch($written);
    // If this is published, then it should point to itself in the lookup table.
    $selfDirect = array($written);
    $dbu->updateConstellationLookup($written, $selfDirect);
    echo ".";
    $constellation = $e->parseFile($realdir."/99166-w6ck24z2.xml");
    $written = $dbu->writeConstellation($user, $constellation, "bulk ingest of merged", 'ingest cpf');
    $dbu->writeConstellationStatus($user, $written->getID(), "published");
    indexESearch($written);
    // If this is published, then it should point to itself in the lookup table.
    $selfDirect = array($written);
    $dbu->updateConstellationLookup($written, $selfDirect);
    echo ".";
    $constellation = $e->parseFile($realdir."/99166-w61z46b8.xml");
    $written = $dbu->writeConstellation($user, $constellation, "bulk ingest of merged", 'ingest cpf');
    $dbu->writeConstellationStatus($user, $written->getID(), "published");
    indexESearch($written);
    // If this is published, then it should point to itself in the lookup table.
    $selfDirect = array($written);
    $dbu->updateConstellationLookup($written, $selfDirect);
    echo ".\n";
    $constellation = $e->parseFile($realdir."/99166-w66182x0.xml");
    $written = $dbu->writeConstellation($user, $constellation, "bulk ingest of merged", 'ingest cpf');
    $dbu->writeConstellationStatus($user, $written->getID(), "published");
    indexESearch($written);
    // If this is published, then it should point to itself in the lookup table.
    $selfDirect = array($written);
    $dbu->updateConstellationLookup($written, $selfDirect);

    echo "Parsing: SNAC Sample test file (from db test)\n";
    $constellation = $e->parseFile("../test/snac/server/database/test_record.xml");
    $written = $dbu->writeConstellation($user, $constellation, "bulk ingest of merged", 'ingest cpf');
    $dbu->writeConstellationStatus($user, $written->getID(), "published");
    indexESearch($written);
    // If this is published, then it should point to itself in the lookup table.
    $selfDirect = array($written);
    $dbu->updateConstellationLookup($written, $selfDirect);
    $dbu->writeConstellationStatus($user, $written->getID(), "locked editing");

    echo "Parsing: Parsons, Edward Alexander, 1878-1962 (needed for cpfRelation sameAs test_record.xml)\n";
    $constellation = $e->parseFile($realdir . '/99166-w6qc06d0.xml');
    $written = $dbu->writeConstellation($user, $constellation, "bulk ingest of merged", 'ingest cpf');
    $dbu->writeConstellationStatus($user, $written->getID(), "published");
    indexESearch($written);
    // If this is published, then it should point to itself in the lookup table.
    $selfDirect = array($written);
    $dbu->updateConstellationLookup($written, $selfDirect);
    $dbu->writeConstellationStatus($user, $written->getID(), "locked editing");
    echo ".\n";

    echo "\nCompleted input of sample data.\n\n";

}
echo "Time: " . date("Y-m-d H:i:s") . "\n";

// If no file was parsed, then print the output that something went wrong
if ($parsedFile == false) {
    echo "No files in directory\n\n"
        . "Reads files from the snac merged cpf directory (1st argument),\n"
        . "then parses the files into Identity Constellations and adds them\n"
        . "to the database using standard DBUtil calls (as if it were the server).\n"
        . "Sample usage: ./ingest_sample.php /path/to/merge/directory /path/to/sample/directory\n\n";
}

/**
 * @param \snac\data\Constellation $written
 */
function indexESearch($written) {
    global $eSearch;
    if ($eSearch != null) {
        $params = [
                'index' => \snac\Config::$ELASTIC_SEARCH_BASE_INDEX,
                // 'type' => \snac\Config::$ELASTIC_SEARCH_BASE_TYPE,
                'id' => $written->getID(),
                'body' => [
                        'nameEntry' => $written->getPreferredNameEntry()->getOriginal(),
                        'entityType' => $written->getEntityType()->getID(),
                        'arkID' => $written->getArk(),
                        'id' => $written->getID(),
                        'degree' => count($written->getRelations()),
                        'timestamp' => date("c")
                ]
        ];

        $eSearch->index($params);
    }
}

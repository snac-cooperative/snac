#!/usr/bin/env php
<?php
/**
 * Refresh the Elatic Search Indices
 *
 * This script is more "dirty" to be efficient in rebuilding the Elastic Search index.  It queries
 * the postgres database directly to get required information to build the elastic search indices.
 *
 * It fills two indices by default: the base search index for UI interaction and the all names index
 * for identity reconciliation.
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

// SNAC Postgres DB Connector
$db = new \snac\server\database\DatabaseConnector();

$primaryCount = 0;
$secondaryCount = 0;
$primaryStart = false;
$secondaryStart = false;
$primaryBody = array();
$secondaryBody = array();

$rels = array();

$lookup = [
    "document_type" => [],
    "document_role" => []
];

$connector = null;

if (\snac\Config::$USE_NEO4J) {
    $connector = \GraphAware\Neo4j\Client\ClientBuilder::create()
        ->addConnection('bolt', \snac\Config::$NEO4J_BOLT_URI)
        ->build();
}

if ($connector == null)
    die("Could not connect to Neo4J");

echo "Querying the resources from the database.\n";

$allNames = $db->query("select b.id, cl.current_ic_id as repo_ic_id
                        from resource_cache b, constellation_lookup cl,
                        (select distinct id, max(version) as version from resource_cache group by id) a
                        where b.id = a.id and b.version = a.version and not b.is_deleted and b.repo_ic_id is not null and b.repo_ic_id = cl.ic_id", array());

$nodes = array();
while($name = $db->fetchrow($allNames))
{
        $nodes[$name["id"]] = [
            "id" => $name["id"],
            "repo" => $name["repo_ic_id"]
        ];
}

echo "Updating the Neo4J Graph. This may take a while...\n";

$stack = $connector->stack();
$i = 0;
foreach ($nodes as $node) {
    if ($node['repo'] == null) {
        print_r($node);
        continue;
    }
    $stack->push("MATCH (a:Identity {id: {id1} }),(b:Resource {id: {id2} })
        CREATE (b)-[r:HIRELATION]->(a);",
        [
            'id1' => $node["repo"],
            'id2' => $node["id"]
        ]);
        if ($i++ > 1000) {
            try {
                $txn = $connector->transaction();
                $result = $txn->runStack($stack);
                if ($result == null)
                    die ("An error occurred1");
                $txn->commit();
                $stack = $connector->stack();
                echo "Committed $i edges\n";
                $i = 0;
            } catch (\Exception $e) {
                die($e);
            }
    }
}
$txn = $connector->transaction();
$txn->runStack($stack);
$txn->commit();

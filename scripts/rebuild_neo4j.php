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
    "relation_type" => []
];

$connector = null;

if (\snac\Config::$USE_NEO4J) {
    $connector = \GraphAware\Neo4j\Client\ClientBuilder::create()
        ->addConnection('bolt', \snac\Config::$NEO4J_BOLT_URI)
        ->build();
}

if ($connector == null)
    die("Could not connect to Neo4J");

echo "Querying the vocabulary from the database.\n";

$result = $db->query("select * from vocabulary where type = 'relation_type';", array());
while($row = $db->fetchrow($result))
{
    $lookup["relation_type"][$row["id"]] = [
        "uri" => $row["uri"],
        "value" => $row["value"]
    ];
}


echo "Querying the relations from the database.\n";

$allRel = $db->query("select r.id, r.version, r.ic_id, cl.current_ic_id as related_id, cl.current_ark_id as related_ark, r.arcrole from
                related_identity r, constellation_lookup cl,
                (select distinct id, max(version) as version from related_identity group by id) a
                where a.id = r.id and a.version = r.version and not r.is_deleted and cl.ic_id = r.related_id", array());

echo "Finished base query, now reading through results.\n";

$rcount = 0;
while($row = $db->fetchrow($allRel))
{
    if (!isset($rels[$row["ic_id"]]))
        $rels[$row["ic_id"]] = array();

    $rels[$row["ic_id"]][$row["id"]] = [
        "id" => $row["id"],
        "version" => $row["version"],
        "source" => $row["ic_id"],
        "target" => $row["related_id"],
        "target_ark" => $row["related_ark"],
        "arcrole" => isset($lookup["relation_type"][$row["arcrole"]]) ? $lookup["relation_type"][$row["arcrole"]]["value"] : null
    ];
    $rcount++;
    if ($rcount % 1000 == 0)
        echo "   Read 1000 lines from the table into memory ($rcount)\n";
}

/*
echo "Querying the resource relation degrees from the database.\n";

$allRelCount = $db->query("select a.ic_id, count(*) as degree from
            (select r.id, r.ic_id from
                related_resource r,
                (select distinct id, max(version) as version from related_resource group by id) a
                where a.id = r.id and a.version = r.version and not r.is_deleted) a
                group by ic_id", array());
while($c = $db->fetchrow($allRelCount))
{
    $counts[$c["ic_id"]]["resources"] = $c["degree"];
}
 */



echo "Querying the names from the database.\n";

$allNames = $db->query("select one.ic_id, one.version, one.ark_id, two.id as name_id, two.original, two.preference_score, one.entity_type from
    (select
        aa.is_deleted,aa.id,aa.version, aa.ic_id, aa.original, aa.preference_score
    from
        name as aa,
        (select name.id,max(name.version) as version from name
            left join (select v.id as ic_id, v.version, nrd.ark_id
                    from version_history v
                    left join (select bb.id, max(bb.version) as version from
                    (select id, version from version_history where status in ('published', 'deleted', 'tombstone')) bb
                    group by id order by id asc) mv
                    on v.id = mv.id and v.version = mv.version
                    left join nrd on v.id = nrd.ic_id
                    where
                    v.status = 'published'
                    order by v.id asc, v.version desc) vh
                on name.version<=vh.version and
                name.ic_id=vh.ic_id
            group by name.id) as bb
    where
        aa.id = bb.id and
        not aa.is_deleted and
        aa.version = bb.version
    order by ic_id asc, preference_score desc, id asc) two,
    (select v.id as ic_id, v.version, n.ark_id, etv.value as entity_type
    from
        version_history v,
        (select bb.id, max(bb.version) as version from
            (select id, version from version_history where status in ('published', 'deleted', 'tombstone')) bb
            group by id order by id asc) mv,
        vocabulary etv,
        nrd n
    where
        v.id = mv.id and
        v.version = mv.version and
        v.status = 'published' and
        v.id = n.ic_id and
        n.ark_id is not null and
        n.entity_type = etv.id) one
where
    two.ic_id = one.ic_id
order by
    one.ic_id asc, two.preference_score desc, two.id asc;", array());

$previousICID = -1;

$nodes = array();
while($name = $db->fetchrow($allNames))
{
    // The data is ordered by ic_id and then preference score.  We will currently say the preferred name
    // is the one with the highest preference score for each ic_id.  So, if we haven't ever seen this ic_id
    // before, this is the preferred name entry for this ic.

    if ($previousICID != $name["ic_id"]) {
        $nodes[$name["ic_id"]] = [
            "id" => $name["ic_id"],
            "ark" => $name["ark_id"],
            "version" => $name["version"],
            "entity_type" => $name["entity_type"],
            "name" => $name["original"],
	    'name_lower' => strtolower($name["original"])
        ];
    }
    $previousICID = $name["ic_id"];
}

echo "Updating the Neo4J Graph. This may take a while...\n";

$stack = $connector->stack();
$i = 0;
foreach ($nodes as $node) {
    $stack->push('CREATE (n:Identity) SET n += {infos}',
        [
            'infos' => $node
        ]);
    if ($i++ > 10000) {
        $txn = $connector->transaction();
        $txn->runStack($stack);
        $txn->commit();
        $stack = $connector->stack();
        echo "Committed $i nodes\n";
        $i = 0;
    }

}
$txn = $connector->transaction();
$txn->runStack($stack);
$txn->commit();

$connector->run('CREATE CONSTRAINT ON (i:Identity) ASSERT i.id IS UNIQUE');
$connector->run('CREATE INDEX ON :Identity(name_lower)');


$stack = $connector->stack();
$i = 0;
foreach ($rels as $noderel) {
    foreach ($noderel as $edge) {
        $stack->push("MATCH (a:Identity {id: {id1} }),(b:Identity {id: {id2} })
            CREATE (a)-[r:ICRELATION {infos}]->(b)",
            [
                'id1' => $edge["source"],
                'id2' => $edge["target"],
                'infos' => [
                    "id" => $edge["id"],
                    "version" => $edge["version"],
                    "arcrole" => $edge["arcrole"]
                ]
            ]);
        if ($i++ > 1000) {
            $txn = $connector->transaction();
            $txn->runStack($stack);
            $txn->commit();
            $stack = $connector->stack();
            echo "Committed $i edges\n";
            $i = 0;
        }
    }
}
$txn = $connector->transaction();
$txn->runStack($stack);
$txn->commit();

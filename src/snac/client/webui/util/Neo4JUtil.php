<?php
/**
 * Neo4J Utility Class File
 *
 * Contains the Neo4J connection and query information
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

/**
 *
 * See https://github.com/graphaware/neo4j-php-client/blob/master/README.md for full Neo4J implementation
 *
 * Quick Examples:
 *
 * Search:
 *   $query = "MATCH (n:Person)-[:FOLLOWS]->(friend) RETURN n.name, collect(friend) as friends";
 *   $result = $client->run($query);
 *
 *   foreach ($result->getRecords() as $record) {
 *     echo sprintf('Person name is : %s and has %d number of friends', $record->value('name'), count($record->value('friends'));
 *   }
 *
 * Using stacks (multiple statements):
 *   $stack = $client->stack();
 *   $stack->push('CREATE (n:Person {uuid: {uuidvar} })', ['uuidvar' => '123-fff']);
 *   $stack->push('MATCH (n:Person {uuid: {uuid1} }), (n2:Person {uuid: {uuid2} }) MERGE (n)-[:FOLLOWS]->(n2)', ['uuid1' => '123-fff', 'uuid2' => '456-ddd']);
 *   $results = $client->runStack($stack);
 */

namespace snac\client\webui\util;

use \snac\Config as Config;
use \snac\exceptions\SNACDatabaseException;

/**
 * Neo4J Utility Class
 *
 * This class provides the Neo4J methods to query and update the Neo4J graph database.
 *
 * @author Robbie Hott
 *
 */
class Neo4JUtil {

    /**
     * @var \GraphAware\Neo4j\Client\ClientInterface The Neo4J Client interface connector
     */
    private $connector = null;

    /**
     * @var \Monolog\Logger $logger the logger for this class
     */
    private $logger = null;

    /**
     * Default Constructor
     *
     * Constructor for the elastic search utility.  It connects to a logger and to elastic search.
     */
    public function __construct() {
        global $log;

        // create a log channel
        $this->logger = new \Monolog\Logger('Neo4JUtil');
        $this->logger->pushHandler($log);

        if (\snac\Config::$USE_NEO4J) {
            $this->connector = \Laudis\Neo4j\ClientBuilder::create()
                ->withDriver('bolt', \snac\Config::$NEO4J_BOLT_URI)
                ->build();
        }
        $this->logger->debug("Created neo4j client");
    }

    /**
     * Shorten String Helper
     *
     * Given a string, if the string is longer than 60 characters, it
     * will reduce the string to 60 characters with a trailing "..."
     *
     * @param string $string The string to shorten
     * @return string The shortened string
     */
    public function shortenString($string)
    {
        if (strlen($string) > 60)
        {
            $string = mb_substr($string, 0, 57) . "...";
        }
        return $string;
    }

    /**
     * Get Alchemy Data
     *
     * Queries Neo4J for the data needed to build the connection graph (Alchemy is the
     * name of the original Javascript graphing utility used, hence the name).  Given
     * an IC ID, it gets the related identities from Neo4J and their associated graph.
     *
     * The degree determines the number of "degrees of separation" from the queried
     * Constellation and delta determines the "promenance" of the identities (nodes)
     * returned.  A higher delta limits to only those identities that are well connected
     * to resources.
     *
     * @param int $icid The Identity Constellation ID to search
     * @param int $degree optional The number of degrees of separation to query
     * @param int $delta optional Limiting factor
     * @return string[][] A multi-dimensional array of nodes and edges constituting the graph
     */
    public function getAlchemyData($icid, $degree = 2, $delta = 10) {
        if ($degree > 4) { $degree = 4; }
        $minimum_records = array();
        for ($i = 1; $i <= $degree; $i++) { $minimum_records[$i] = 0; }
        $delta_limit = $delta * 0.1;

        try {
            for ($i = 1; $i <= $degree; $i++) {
              $query = "MATCH (n:Identity {id:\"" . $icid . "\"})";
              for ($j = 1; $j <= $i; $j++) {
                $query .= "-[ir$j:ICRELATION]->(i$j:Identity {entity_type:\"person\"})";
              }
              $query .= " WITH DISTINCT count{(i$i)-[:RRELATION]-()} as acnt, count{(i$i)-[:RRELATION]-()} as mcnt";
              $query .= " RETURN avg(acnt) as avg, max(mcnt) as max";
              $result = $this->connector->run($query);
              $record = $result->first();
              $avg = $record->get('avg');
              $max = $record->get('max');
              $min = round($avg * $delta_limit);
              if ($min > $max) { $min = $max; }
              $minimum_records[$i] = $min;
            }

            if ($degree == 1) { $query = "MATCH p=((n:Identity {id:\"" . $icid . "\"})-[ir1:ICRELATION]->(i1:Identity {entity_type:\"person\"})) WHERE (count{(i1)-[:RRELATION]-()} >= " . $minimum_records[1] . ") RETURN relationships(p) AS the_rels, nodes(p) AS the_nods"; }
            if ($degree == 2) { $query = "MATCH p=((n:Identity {id:\"" . $icid . "\"})-[ir1:ICRELATION]->(i1:Identity {entity_type:\"person\"})-[ir2:ICRELATION]->(i2:Identity {entity_type:\"person\"})) WHERE (count{(i1)-[:RRELATION]-()} >= " . $minimum_records[1] . ") AND (count{(i2)-[:RRELATION]-()} >= " . $minimum_records[2] . ") RETURN relationships(p) AS the_rels, nodes(p) AS the_nods"; }
            if ($degree == 3) { $query = "MATCH p=((n:Identity {id:\"" . $icid . "\"})-[ir1:ICRELATION]->(i1:Identity {entity_type:\"person\"})-[ir2:ICRELATION]->(i2:Identity {entity_type:\"person\"})-[ir3:ICRELATION]->(i3:Identity {entity_type:\"person\"})) WHERE (count{(i1)-[:RRELATION]-()} >= " . $minimum_records[1] . ") AND (count{(i2)-[:RRELATION]-()} >= " . $minimum_records[2] . ") AND (count{(i3)-[:RRELATION]-()} >= " . $minimum_records[3] . ") RETURN relationships(p) AS the_rels, nodes(p) AS the_nods"; }
            if ($degree == 4) { $query = "MATCH p=((n:Identity {id:\"" . $icid . "\"})-[ir1:ICRELATION]->(i1:Identity {entity_type:\"person\"})-[ir2:ICRELATION]->(i2:Identity {entity_type:\"person\"})-[ir3:ICRELATION]->(i3:Identity {entity_type:\"person\"})-[ir4:ICRELATION]->(i4:Identity {entity_type:\"person\"})) WHERE (count{(i1)-[:RRELATION]-()} >= " . $minimum_records[1] . ") AND (count{(i2)-[:RRELATION]-()} >= " . $minimum_records[2] . ") AND (count{(i3)-[:RRELATION]-()} >= " . $minimum_records[3] . ") AND (count{(i4)-[:RRELATION]-()} >= " . $minimum_records[4] . ") RETURN relationships(p) AS the_rels, nodes(p) AS the_nods"; }

            $result = $this->connector->run($query);

            $some_edges = array();
            foreach ($result as $record) {
                foreach ($record->get('the_rels') as $rel) {
                    $some_edges[] = "\n{ \"source\": " . $rel->getStartNodeId() . ", \"target\": " . $rel->getEndNodeId() . " }" ;
                }
            }
            $unique_edges = array_unique($some_edges);
            $some_nodes = array();

            foreach ($result as $record) {
                $node_degree = 0;
                foreach ($record->get('the_nods') as $nod) {
                    $dbid = $nod->getId();
                    $caption = $dbid;
                    if ($nod->__isset('name')) { $caption = $this->shortenString($nod->__get('name')); }
                    $root = "";
                    if ($dbid == $icid) { $root = ", \"root\": true"; }
                    $some_nodes[] = "\n{ \"id\": " . $nod->getId() . ", \"dbid\": " . $dbid . ", \"caption\": \"" . addcslashes($caption, '"') . "\", \"dgr\": \"x" . $node_degree++ . "\"" . $root . " }" ;
                }
            }

            $semi_unique_nodes = array_unique($some_nodes);

            sort($semi_unique_nodes);
        
            $unique_nodes = array();
            $max = sizeof($semi_unique_nodes);
            $last_node_substr = "X";
            for ($i = 0; $i < $max; $i++) {
                $this_node_substr = substr($semi_unique_nodes[$i], 0, 36);
                if ($this_node_substr != $last_node_substr) {
                    $unique_nodes[] = $semi_unique_nodes[$i];
                }
                $last_node_substr = $this_node_substr;
            }
        
            $json = "{\n\"nodes\": [" . implode(",", $unique_nodes) . "],\n\"edges\": [" . implode(",", $unique_edges) . "]\n}";
        
            if ($max < 1) {
                $query_for_root = "MATCH (n:Identity {id:\"" . $icid . "\"}) RETURN n.name AS root_name";
                $result_for_root = $this->connector->run($query_for_root);
                $the_root = $result_for_root->firstRecord();
                $root_name = $icid;
                if ($the_root->hasValue('root_name')) { $root_name = $this->shortenString($the_root->value('root_name')); }
                $json = "{\n\"nodes\": [{ \"id\": 1, \"dbid\": " . $icid . ", \"caption\": \"" . addcslashes($root_name, '"') . "\", \"dgr\": \"x0\", \"root\": true }],\n\"edges\": [ ]\n}";
            }
        
            return json_decode($json, true);
        } catch (\Throwable $e) {
            $this->logger->debug("Neo4j error occurred: " . $e->getMessage() );
            return false;
        }
    }

}

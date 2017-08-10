<?php
/**
 * Neo4J Utility Class File
 *
 * Contains the Neo4J connection and query information
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
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
            $this->connector = \GraphAware\Neo4j\Client\ClientBuilder::create()
                ->addConnection('bolt', \snac\Config::$NEO4J_BOLT_URI)
                ->build();
        }
        $this->logger->addDebug("Created neo4j client");
    }
	
	public function shorten_string($string)
	{
    	if (strlen($string) > 60)
		{
        	$string = mb_substr($string, 0, 57) . "...";
        }
		return $string;
    }
	
	public function getAlchemyData($icid, $degree = 2, $delta = 10) {
	
		if ($degree == 1) { $query_for_limits = "MATCH (n:Identity {id:\"" . $icid . "\"})-[ir1:ICRELATION]->(i1:Identity {entity_type:\"person\"}) RETURN avg(DISTINCT size((i1)-[:RRELATION]-())) AS avg_1, max(size((i1)-[:RRELATION]-())) AS max_1"; }		
		if ($degree == 2) { $query_for_limits = "MATCH (n:Identity {id:\"" . $icid . "\"})-[ir1:ICRELATION]->(i1:Identity {entity_type:\"person\"})-[ir2:ICRELATION]->(i2:Identity {entity_type:\"person\"}) RETURN avg(DISTINCT size((i1)-[:RRELATION]-())) AS avg_1, avg(DISTINCT size((i2)-[:RRELATION]-())) AS avg_2, max(size((i1)-[:RRELATION]-())) AS max_1, max(size((i2)-[:RRELATION]-())) AS max_2"; }
		if ($degree == 3) { $query_for_limits = "MATCH (n:Identity {id:\"" . $icid . "\"})-[ir1:ICRELATION]->(i1:Identity {entity_type:\"person\"})-[ir2:ICRELATION]->(i2:Identity {entity_type:\"person\"})-[ir3:ICRELATION]->(i3:Identity {entity_type:\"person\"}) RETURN avg(DISTINCT size((i1)-[:RRELATION]-())) AS avg_1, avg(DISTINCT size((i2)-[:RRELATION]-())) AS avg_2, avg(DISTINCT size((i3)-[:RRELATION]-())) AS avg_3, max(size((i1)-[:RRELATION]-())) AS max_1, max(size((i2)-[:RRELATION]-())) AS max_2, max(size((i3)-[:RRELATION]-())) AS max_3"; }
		if ($degree == 4) { $query_for_limits = "MATCH (n:Identity {id:\"" . $icid . "\"})-[ir1:ICRELATION]->(i1:Identity {entity_type:\"person\"})-[ir2:ICRELATION]->(i2:Identity {entity_type:\"person\"})-[ir3:ICRELATION]->(i3:Identity {entity_type:\"person\"})-[ir4:ICRELATION]->(i4:Identity {entity_type:\"person\"}) RETURN avg(DISTINCT size((i1)-[:RRELATION]-())) AS avg_1, avg(DISTINCT size((i2)-[:RRELATION]-())) AS avg_2, avg(DISTINCT size((i3)-[:RRELATION]-())) AS avg_3, avg(DISTINCT size((i4)-[:RRELATION]-())) AS avg_4, max(size((i1)-[:RRELATION]-())) AS max_1, max(size((i2)-[:RRELATION]-())) AS max_2, max(size((i3)-[:RRELATION]-())) AS max_3, max(size((i4)-[:RRELATION]-())) AS max_4"; }
		
		$result_for_limits = $this->connector->run($query_for_limits);
		
		$limits = $result_for_limits->firstRecord();
		
		$delta_limit = $delta * 0.1;
		
		$minimum_records_1 = round($limits->value('avg_1') * $delta_limit);
		if ($minimum_records_1 > $limits->value('max_1')) { $minimum_records_1 = $limits->value('max_1'); }
		if ($minimum_records_1 < 1) { $minimum_records_1 = 0; }
		
		if ($degree > 1)
		{
			$minimum_records_2 = round($limits->value('avg_2') * $delta_limit);
			if ($minimum_records_2 > $limits->value('max_2')) { $minimum_records_2 = $limits->value('max_2'); }
			if ($minimum_records_2 < 1) { $minimum_records_2 = 0; }
		}
		if ($degree > 2)
		{
			$minimum_records_3 = round($limits->value('avg_3') * $delta_limit);
			if ($minimum_records_3 > $limits->value('max_3')) { $minimum_records_3 = $limits->value('max_3'); }
			if ($minimum_records_3 < 1) { $minimum_records_3 = 0; }
		}
		if ($degree > 3)
		{
			$minimum_records_4 = round($limits->value('avg_4') * $delta_limit);
			if ($minimum_records_4 > $limits->value('max_4')) { $minimum_records_4 = $limits->value('max_4'); }
			if ($minimum_records_4 < 1) { $minimum_records_4 = 0; }
		}
		
		if ($degree == 1) { $query = "MATCH p=((n:Identity {id:\"" . $icid . "\"})-[ir1:ICRELATION]->(i1:Identity {entity_type:\"person\"})) WHERE (size((i1)-[:RRELATION]-()) >= " . $minimum_records_1 . ") RETURN relationships(p) AS the_rels, nodes(p) AS the_nods"; }
		if ($degree == 2) { $query = "MATCH p=((n:Identity {id:\"" . $icid . "\"})-[ir1:ICRELATION]->(i1:Identity {entity_type:\"person\"})-[ir2:ICRELATION]->(i2:Identity {entity_type:\"person\"})) WHERE (size((i1)-[:RRELATION]-()) >= " . $minimum_records_1 . ") AND (size((i2)-[:RRELATION]-()) >= " . $minimum_records_2 . ") RETURN relationships(p) AS the_rels, nodes(p) AS the_nods"; }
		if ($degree == 3) { $query = "MATCH p=((n:Identity {id:\"" . $icid . "\"})-[ir1:ICRELATION]->(i1:Identity {entity_type:\"person\"})-[ir2:ICRELATION]->(i2:Identity {entity_type:\"person\"})-[ir3:ICRELATION]->(i3:Identity {entity_type:\"person\"})) WHERE (size((i1)-[:RRELATION]-()) >= " . $minimum_records_1 . ") AND (size((i2)-[:RRELATION]-()) >= " . $minimum_records_2 . ") AND (size((i3)-[:RRELATION]-()) >= " . $minimum_records_3 . ") RETURN relationships(p) AS the_rels, nodes(p) AS the_nods"; }
		if ($degree == 4) { $query = "MATCH p=((n:Identity {id:\"" . $icid . "\"})-[ir1:ICRELATION]->(i1:Identity {entity_type:\"person\"})-[ir2:ICRELATION]->(i2:Identity {entity_type:\"person\"})-[ir3:ICRELATION]->(i3:Identity {entity_type:\"person\"})-[ir4:ICRELATION]->(i4:Identity {entity_type:\"person\"})) WHERE (size((i1)-[:RRELATION]-()) >= " . $minimum_records_1 . ") AND (size((i2)-[:RRELATION]-()) >= " . $minimum_records_2 . ") AND (size((i3)-[:RRELATION]-()) >= " . $minimum_records_3 . ") AND (size((i4)-[:RRELATION]-()) >= " . $minimum_records_4 . ") RETURN relationships(p) AS the_rels, nodes(p) AS the_nods"; }
				
		$result = $this->connector->run($query);
	
		$some_edges = array();
		foreach ($result->records() as $record) {
		foreach ($record->get('the_rels') as $rel)
		{
			$some_edges[] = "\n{ \"source\": " . $rel->startNodeIdentity() . ", \"target\": " . $rel->endNodeIdentity() . " }" ;
		}
		}
		
		$unique_edges = array_unique($some_edges);
		
		$some_nodes = array();
		foreach ($result->records() as $record) {
		$node_degree = 0;
		foreach ($record->get('the_nods') as $nod)
		{
			$dbid = $nod->value('id');
			$caption = $dbid;
			if ($nod->hasValue('name')) { $caption = $this->shorten_string($nod->value('name')); }
			//$node_type = $nod->labels()[0];
			$root = "";
			if ($dbid == $icid) { $root = ", \"root\": true"; }
			
			$some_nodes[] = "\n{ \"id\": " . $nod->identity() . ", \"dbid\": " . $dbid . ", \"caption\": \"" . addcslashes($caption, '"') . "\", \"dgr\": \"x" . $node_degree++ . "\"" . $root . " }" ;
		}
		}
		
		$semi_unique_nodes = array_unique($some_nodes);
		
		sort($semi_unique_nodes);
		
		$unique_nodes = array();
		$max = sizeof($semi_unique_nodes);
		$last_node_substr = "X";
		for($i = 0; $i < $max; $i++)
		{
			$this_node_substr = substr($semi_unique_nodes[$i], 0, 36);
			if($this_node_substr != $last_node_substr)
			{
				$unique_nodes[] = $semi_unique_nodes[$i];
			}
			$last_node_substr = $this_node_substr;
		}
		
		$json = "{\n\"nodes\": [" . implode(",", $unique_nodes) . "],\n\"edges\": [" . implode(",", $unique_edges) . "]\n}";
		
		if($max < 1)
		{
			$query_for_root = "MATCH (n:Identity {id:\"" . $icid . "\"}) RETURN n.name AS root_name";
			$result_for_root = $this->connector->run($query_for_root);
			$the_root = $result_for_root->firstRecord();
			$root_name = $icid;
			if ($the_root->hasValue('root_name')) { $root_name = $this->shorten_string($the_root->value('root_name')); }
			$json = "{\n\"nodes\": [{ \"id\": 1, \"dbid\": " . $icid . ", \"caption\": \"" . addcslashes($root_name, '"') . "\", \"dgr\": \"x0\", \"root\": true }],\n\"edges\": [ ]\n}";
		}
		
		return $json;
    }

}

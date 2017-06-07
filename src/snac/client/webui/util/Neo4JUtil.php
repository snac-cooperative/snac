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
    	if (strlen($string) > 54)
		{
        	$string = substr($string, 0, 50) . "...";
        }
		return $string;
    }

    public function performQuery($param) {
	
		$result = "Boo!";
	
		return $result;
	}
	
	public function getAlchemyData($param) {
	
		$identity_constellationid = $param; //"60840396"; //"60840396"; "3068217"; "31994730"; "37485845" (Henry, Joseph)
		
		$query_for_limits = "MATCH (n:Identity {id:\"" . $identity_constellationid . "\"})-[ir1:ICRELATION]->(i1:Identity {entity_type:\"person\"})-[ir2:ICRELATION]->(i2:Identity {entity_type:\"person\"}) RETURN round(avg(DISTINCT size((i1)-[:RRELATION]-()))) AS limit_1, round(avg(DISTINCT size((i2)-[:RRELATION]-()))) AS limit_2";
		
		$result_for_limits = $this->connector->run($query_for_limits);
		$limits = $result_for_limits->firstRecord();
		$minimum_records_1 = $limits->value('limit_1');
		$minimum_records_2 = $limits->value('limit_2');
		
		$query = "MATCH p=((n:Identity {id:\"" . $identity_constellationid . "\"})-[ir1:ICRELATION]->(i1:Identity {entity_type:\"person\"})-[ir2:ICRELATION]->(i2:Identity {entity_type:\"person\"})) WHERE (size((i1)-[:RRELATION]-()) > " . $minimum_records_1 . ") AND (size((i2)-[:RRELATION]-()) > " . $minimum_records_2 . ") AND n <> i1 AND n <> i2 AND i1 <> i2 RETURN relationships(p) AS the_rels, nodes(p) AS the_nods";
		
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
			if ($dbid == $identity_constellationid) { $root = ", \"root\": true"; }
			
			$some_nodes[] = "\n{ \"id\": " . $nod->identity() . ", \"dbid\": " . $dbid . ", \"caption\": \"" . addslashes($caption) . "\", \"dgr\": \"x" . $node_degree++ . "\"" . $root . " }" ;
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
		
		return $json;
    }

}

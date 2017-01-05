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

namespace snac\server\neo4j;

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

    /**
     * Write or Update Constellation Node (and related data)
     *
     * Writes the data from the given constellation to the neo4J database.  If the node and relations already exist, they
     * are updated. If not, they are inserted.
     *
     * @param \snac\data\Constellation $constellation The constellation object to insert/update in Neo4J
     */
    public function writeConstellation(&$constellation) {

        if ($this->connector != null) {
            /** Write at least 
                    'nameEntry' => $constellation->getPreferredNameEntry()->getOriginal(),
                    'entityType' => $constellation->getEntityType()->getTerm(),
                    'arkID' => $constellation->getArk(),
                    'id' => (int) $constellation->getID(),
                    'degree' => (int) count($constellation->getRelations()), // In Neo4J already by nature of graph database
                    'resources' => (int) count($constellation->getResourceRelations()) // In Neo4J already if we include resources
                    **/
            
            /** May want to include the other name entries as part of the node **/             
            $this->logger->addDebug("Updated neo4j with constellation data");
        }
    }

    /**
     * Delete Constellation Node (and related data)
     *
     * Deletes the constellation data found in the given constellation from neo4j.
     *
     * @param \snac\data\Constellation $constellation The constellation object to delete from Neo4J
     */
    public function deleteConstellation(&$constellation) {

        if ($this->connector != null) {
            $this->logger->addDebug("Updated neo4j to remove constellation");
        }

    }

    /**
     * Write or Update Resource Node 
     *     
     * Writes the given resource to a resource node in Neo4J.  If it already exists, the node will be
     * updated. If not, it is inserted.
     *
     * @param \snac\data\Resource $resource The resource object to insert/update in Neo4J
     */
    public function writeResource(&$resource) {

        if ($this->connector != null) {
            
            /** Want to write at least:
                    'id' => (int) $resource->getID(),
                    'title' => $resource->getTitle(),
                    'url' => $resource->getLink(),
                    'abstract' => $resource->getAbstract(),
                    'type' => $resource->getDocumentType()->getTerm(),
                    'type_id' => (int) $resource->getDocumentType()->getID(),
                    'timestamp' => date('c')
                    **/
            
            $this->logger->addDebug("Updated resource in neo4j");
        }
    }

    /**
     * Delete Resource Node
     *
     * Deletes the given resource from Neo4J.
     *
     * @param \snac\data\Resource $resource The resource object to delete from Neo4J
     */
    public function deleteResource(&$resource) {

        if ($this->connector != null) {
            $this->logger->addDebug("Updated neo4j to remove resource");
        }

    }
}

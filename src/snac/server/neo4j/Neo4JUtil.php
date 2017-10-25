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
    public function updateIdentityIndex(&$constellation) {

        if ($this->connector != null) {
            
            // STEP 1: Update or insert this identity as a node:
            $this->logger->addDebug("Updating/Inserting Node into Neo4J database"); 
            $result = $this->connector->run("MATCH (a:Identity {id: {icid} }) SET a.name = {name}, a.version = {version}, a.ark = {ark},
                a.entity_type = {entityType} return a;", 
                [
                    'icid' => $constellation->getID(),
                    'version' => $constellation->getVersion(),
                    'name' => $constellation->getPreferredNameEntry()->getOriginal(),
                    'ark' => $constellation->getArk(),
                    'entityType' => $constellation->getEntityType()->getTerm()
                ]
            );

            // Check to see if anything was added
            $records = $result->getRecords(); 
            if (empty($records)) {
                // Must create this record instead
                $result = $this->connector->run("CREATE (n:Identity) SET n += {infos};", 
                    [
                        "infos" => [
                            'id' => $constellation->getID(),
                            'version' => $constellation->getVersion(),
                            'name' => $constellation->getPreferredNameEntry()->getOriginal(),
                            'ark' => $constellation->getArk(),
                            'entity_type' => $constellation->getEntityType()->getTerm()
                        ]
                    ]
                );
            }

            // ************************************
            // STEP 2: Check all the constellation relations. Update, insert, or delete as appropriate
            $this->logger->addDebug("Reading relationships from Neo4J"); 
            
            $result = $this->connector->run("MATCH p=(a:Identity {id: {icid} })-[r:ICRELATION]->(b:Identity) return p;", 
                [
                    'icid' => $constellation->getID()
                ]
            );

            // List out relations 
            $rels = array();
            foreach ($result->getRecords() as $record) {
                $path = $record->pathValue("p");
                array_push($rels, [
                    "arcrole" => $path->relationships()[0]->value('arcrole'),
                        "target" => $path->end()->value("id"),
                    "operation" => "delete"
                    ]
                );
            }

            $this->logger->addDebug("Reconciling Relationships to Current IC"); 
            $relsToDelete = array();
            $relsToModify = array();
            foreach($constellation->getRelations() as $relation) {
                $add = true;
                foreach ($rels as &$rel) {
                    if ($relation->getTargetConstellation() == $rel["target"]) {
                        // if it's been found, then don't add it to the index
                        $add = false;
                        if ($relation->getType() && $relation->getType()->getTerm() != $rel["arcrole"]) {
                            $rel["arcrole"] = $relation->getType() ? $relation->getType()->getTerm() : "";
                            $rel["operation"] = "update";
                        } else {
                            $rel["operation"] = null;
                        }
                        break;
                    }
                }
                if ($add) 
                    array_push($rels, [
                        "target" => $relation->getTargetConstellation(),
                        "arcrole" => $relation->getType() ? $relation->getType()->getTerm() : "",
                        "operation" => "insert"
                    ]);
            }
            $this->logger->addDebug("List of related identity paths", $rels); 

            // Make the relationship changes
            foreach ($rels as $rel) {
                switch($rel["operation"]) {
                    case "insert":
                        $result = $this->connector->run("MATCH (a:Identity {id: {id1} }),(b:Identity {id: {id2} })
                                                            CREATE (a)-[r:ICRELATION {infos}]->(b)", 
                        [
                            'id1' => $constellation->getID(),
                            'id2' => $rel["target"],
                            'infos' => [
                                "arcrole" => $rel["arcrole"]
                            ]
                        ]);
                        break;
                    case "delete":
                        $result = $this->connector->run("match p=(n1:Identity {id:{id1}})-[r:ICRELATION {arcrole:{arcrole}}]->(n2:Identity {id:{id2}}) 
                                                          delete r;", 
                        [
                            'id1' => $constellation->getID(),
                            'id2' => $rel["target"],
                            "arcrole" => $rel["arcrole"]
                        ]);
                        break;
                    case "update":
                        $result = $this->connector->run("match p=(n1:Identity {id:{id1}})-[r:ICRELATION]->(n2:Identity {id:{id2}}) 
                                                          set r.arcrole = {arcrole} return p;", 
                        [
                            'id1' => $constellation->getID(),
                            'id2' => $rel["target"],
                            "arcrole" => $rel["arcrole"]
                        ]);
                        break;
                }                
            }
            
            // ************************************
            // STEP 3: Check all the resource relations. Update, insert, or delete as appropriate
            $this->logger->addDebug("Reading resource relationships from Neo4J"); 
            
            $result = $this->connector->run("MATCH p=(a:Identity {id: {icid} })-[r:RRELATION]->(b:Resource) return p;", 
                [
                    'icid' => $constellation->getID()
                ]
            );

            // List out relations 
            $rels = array();
            foreach ($result->getRecords() as $record) {
                $path = $record->pathValue("p");
                array_push($rels, [
                    "target" => $path->end()->value("id"),
                    "role" => $path->relationships()[0]->value('role'),
                    "id" => $path->relationships()[0]->value('id'),
                    "version" => $path->relationships()[0]->value('version'),
                    "operation" => "delete"
                    ]
                );
            }

            $this->logger->addDebug("Reconciling Resource Relationships to Current IC"); 
            $relsToDelete = array();
            $relsToModify = array();
            foreach($constellation->getResourceRelations() as $relation) {
                $add = true;
                foreach ($rels as &$rel) {
                    if ($relation->getResource()->getID() == $rel["target"]) {
                        // if it's been found, then don't add it to the index
                        $add = false;
                        if ($relation->getVersion() != $rel["version"]) {
                            $rel["role"] = $relation->getRole() ? $relation->getRole()->getTerm() : "";
                            $rel["version"] = $relation->getVersion();
                            $rel["operation"] = "update";
                        } else {
                            $rel["operation"] = null;
                        }
                        break;
                    }
                }
                if ($add) 
                    array_push($rels, [
                        "target" => $relation->getResource()->getID(),
                        "role" => $relation->getRole() ? $relation->getRole()->getTerm() : "",
                        "id" => $relation->getID(),
                        "version" => $relation->getVersion(),
                        "operation" => "insert"
                    ]);
            }
            $this->logger->addDebug("List of related resource paths", $rels); 
            
            // Make the relationship changes
            foreach ($rels as $rel) {
                switch($rel["operation"]) {
                    case "insert":
                        $result = $this->connector->run("MATCH (a:Identity {id: {id1} }),(b:Resource {id: {id2} })
                                                            CREATE (a)-[r:RRELATION {infos}]->(b)", 
                        [
                            'id1' => $constellation->getID(),
                            'id2' => $rel["target"],
                            'infos' => [
                                "role" => $rel["role"],
                                "id" => $rel["id"],
                                "version" => $rel["version"]
                            ]
                        ]);
                        break;
                    case "delete":
                        $result = $this->connector->run("match p=(n1:Identity {id:{id1}})-[r:RRELATION {id:{rid}}]->(n2:Resource {id:{id2}}) 
                                                          delete r;", 
                        [
                            'id1' => $constellation->getID(),
                            'id2' => $rel["target"],
                            "rid" => $rel["id"]
                        ]);
                        break;
                    case "update":
                        $result = $this->connector->run("match p=(n1:Identity {id:{id1}})-[r:RRELATION]->(n2:Resource {id:{id2}}) 
                                                          set r.role = {role}, r.id = {rid}, r.version = {rversion} return p;", 
                        [
                            'id1' => $constellation->getID(),
                            'id2' => $rel["target"],
                            "role" => $rel["role"],
                            "rid" => $rel["id"],
                            "rversion" => $rel["version"]
                        ]);
                        break;
                }                
            }
            


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
     * List in-edges for constellation
     *
     * Lists the Constellation-Constellation relationships that point into the given constellation
     *
     * @param  \snac\data\Constellation $constellation Constellation to search
     * @return string[]                 The list of results
     */
    public function listConstellationInEdges(&$constellation) {
        $results = array();

        return $results;
    }

    /**
     * List out-edges for constellation
     *
     * Lists the Constellation-Constellation relationships that point out of the given constellation
     *
     * @param  \snac\data\Constellation $constellation Constellation to search
     * @return string[]                 The list of results
     */
    public function listConstellationOutEdges(&$constellation) {
        $results = array();

        return $results;
    }

    /**
     * Write or Update Resource Node
     *
     * Writes the given resource to a resource node in Neo4J.  If it already exists, the node will be
     * updated. If not, it is inserted.
     *
     * @param \snac\data\Resource $resource The resource object to insert/update in Neo4J
     */
    public function updateResourceIndex(&$resource) {
        if ($this->connector != null) {
            
            // STEP 1: Update or insert this identity as a node:
            $this->logger->addDebug("Updating/Inserting Node into Neo4J database"); 
            $result = $this->connector->run("MATCH (a:Resource {id: {id} }) SET a.title = {title}, a.version = {version}, a.href = {href}
                return a;", 
                [
                    'id' => $resource->getID(),
                    'version' => $resource->getVersion(),
                    'title' => $resource->getTitle(),
                    'href' => $resource->getLink()
                ]
            );

            // Check to see if anything was added
            $records = $result->getRecords(); 
            if (empty($records)) {
                // Must create this record instead
                $result = $this->connector->run("CREATE (n:Resource) SET n += {infos};", 
                    [
                        "infos" => [
                            'id' => $resource->getID(),
                            'version' => $resource->getVersion(),
                            'title' => $resource->getTitle(),
                            'href' => $resource->getLink()
                        ]
                    ]
                );
            }
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

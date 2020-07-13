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
            $result = $this->connector->run("MATCH (a:Identity {id: {icid} }) SET a.name = {name}, a.name_lower = {name_lower},  a.version = {version}, a.ark = {ark},
                a.entity_type = {entityType} return a;",
                [
                    'icid' => $constellation->getID(),
                    'version' => $constellation->getVersion(),
                    'name' => $constellation->getPreferredNameEntry()->getOriginal(),
                    'name_lower' => strtolower($constellation->getPreferredNameEntry()->getOriginal()),
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
                            'name_lower' => strtolower($constellation->getPreferredNameEntry()->getOriginal()),
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
            $icRels = array();
            foreach ($result->getRecords() as $record) {
                $path = $record->pathValue("p");
                array_push($icRels, [
                    "arcrole" => $path->relationships()[0]->hasValue('arcrole') ? $path->relationships()[0]->value('arcrole') : null,
                    "id" => $path->relationships()[0]->hasValue('id') ? $path->relationships()[0]->value('id') : null,
                    "version" => $path->relationships()[0]->hasValue('version') ? $path->relationships()[0]->value('version') : null,
                    "target" => $path->end()->value("id"),
                    "operation" => "delete"
                    ]
                );
            }

            $this->logger->addDebug("Reconciling Relationships to Current IC");
            $icRelsToDelete = array();
            $icRelsToModify = array();
            foreach($constellation->getRelations() as $relation) {
                $add = true;
                foreach ($icRels as &$icRel) {
                    if ($relation->getTargetConstellation() == $icRel["target"]) {
                        // if it's been found, then don't add it to the index
                        $add = false;
                        if ($relation->getType() && $relation->getVersion() != $icRel["version"]) {
                            $icRel["arcrole"] = $relation->getType() ? $relation->getType()->getTerm() : "";
                            $icRel["id"] = $relation->getID();
                            $icRel["version"] = $relation->getVersion();
                            $icRel["operation"] = "update";
                        } else {
                            $icRel["operation"] = null;
                        }
                        break;
                    }
                }
                // Be correct with pass by reference foreach loops
                unset($icRel);

                if ($add)
                    array_push($icRels, [
                        "target" => $relation->getTargetConstellation(),
                        "id" => $relation->getID(),
                        "version" => $relation->getVersion(),
                        "arcrole" => $relation->getType() ? $relation->getType()->getTerm() : "",
                        "operation" => "insert"
                    ]);
            }
            $this->logger->addDebug("List of related identity paths", $icRels);

            // Make the relationship changes
            foreach ($icRels as $rel) {
                switch($rel["operation"]) {
                    case "insert":
                        $result = $this->connector->run("MATCH (a:Identity {id: {id1} }),(b:Identity {id: {id2} })
                                                            CREATE (a)-[r:ICRELATION {infos}]->(b);",
                        [
                            'id1' => $constellation->getID(),
                            'id2' => $rel["target"],
                            'infos' => [
                                "id" => $rel["id"],
                                "version" => $rel["version"],
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
                            "id" => $rel["id"],
                            "version" => $rel["version"],
                            "arcrole" => $rel["arcrole"]
                        ]);
                        break;
                    case "update":
                        $result = $this->connector->run("match p=(n1:Identity {id:{id1}})-[r:ICRELATION]->(n2:Identity {id:{id2}})
                            set r.arcrole = {arcrole}, r.id = {id}, r.version = {version} return p;",
                        [
                            'id1' => $constellation->getID(),
                            'id2' => $rel["target"],
                            "id" => $rel["id"],
                            "version" => $rel["version"],
                            "arcrole" => $rel["arcrole"]
                        ]);
                        break;
                }
            }

            // ************************************
            // STEP 3: Check all the resource relations. Update, insert, or delete as appropriate
            $this->logger->addDebug("Reading resource relationships from Neo4J");
            $rRels = array();
            try {
                $result = $this->connector->run("MATCH p=(a:Identity {id: {icid} })-[r:RRELATION]->(b:Resource) return p;",
                    [
                        'icid' => $constellation->getID()
                    ]
                );

                // List out relations
                foreach ($result->getRecords() as $record) {
                    $path = $record->pathValue("p");
                    array_push($rRels, [
                        "target" => $path->end()->value("id"),
                        "role" => $path->relationships()[0]->hasValue('role') ? $path->relationships()[0]->value('role') : null,
                        "id" => $path->relationships()[0]->hasValue('id') ? $path->relationships()[0]->value('id') : null,
                        "version" => $path->relationships()[0]->hasValue('version') ? $path->relationships()[0]->value('version') : null,
                        "operation" => "delete"
                        ]
                    );
                }
            } catch (\Exception $e) {
                $this->logger->addError("Neo4J threw an exception: ".$e->getMessage(), $e->getTrace());
                throw $e;
            }
            $this->logger->addDebug("Reconciling Resource Relationships to Current IC");
            $rRelsToDelete = array();
            $rRelsToModify = array();
            foreach($constellation->getResourceRelations() as $relation) {
                $add = true;
                foreach ($rRels as &$rRel) {
                    if ($relation->getResource()->getID() == $rRel["target"]) {
                        // if it's been found, then don't add it to the index
                        $add = false;
                        if ($relation->getVersion() != $rRel["version"]) {
                            $rRel["role"] = $relation->getRole() ? $relation->getRole()->getTerm() : "";
                            $rRel["version"] = $relation->getVersion();
                            $rRel["operation"] = "update";
                        } else {
                            $rRel["operation"] = null;
                        }
                        break;
                    }
                }
                // Be correct with pass by reference foreach loops
                unset($rRel);

                if ($add)
                    array_push($rRels, [
                        "target" => $relation->getResource()->getID(),
                        "role" => $relation->getRole() ? $relation->getRole()->getTerm() : "",
                        "id" => $relation->getID(),
                        "version" => $relation->getVersion(),
                        "operation" => "insert"
                    ]);
            }
            $this->logger->addDebug("List of related resource paths", $rRels);

            // Make the relationship changes
            foreach ($rRels as $rel) {
                switch($rel["operation"]) {
                    case "insert":
                        $result = $this->connector->run("MATCH (a:Identity {id: {id1} }),(b:Resource {id: {id2} })
                                                            CREATE (a)-[r:RRELATION {infos}]->(b);",
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
            $this->logger->addDebug("Deleting Identity Node from Neo4J database");
            $result = $this->connector->run("MATCH (a:Identity {id: {icid}}) detach delete a;",
                [
                    'icid' => $constellation->getID()
                ]
            );
            $this->logger->addDebug("Updated neo4j to remove constellation");
        }

    }

    /**
     * Redirect/Delete Constellation Node (and related data)
     *
     * Deletes the constellation data found in the given constellation from neo4j while redirecting some of the
     * edges to the given target.
     *
     * @param \snac\data\Constellation $from The constellation object to delete/redirect from Neo4J
     * @param \snac\data\Constellation $to The constellation object that is the target of any redirects
     * @return boolean True if successful, false otherwise
     */
    public function redirectConstellation(&$from, &$to) {
        if ($from == null || $from->getID() == null || $to == null || $to->getID() == null) {
            return false;
        }

        if ($this->connector != null) {
            // Find all in-relations to the from constellation
            $result = $this->connector->run("MATCH p=()-[]->(b:Identity {id: {icid}}) return p;",
                [
                    'icid' => "{$from->getID()}"
                ]
            );

            foreach ($result->getRecords() as $record) {
                $path = $record->pathValue("p");

                // Source of relation
                $startID = $path->start()->value("id");
                $startLabels = $path->start()->labels();
                $startType = $startLabels[0] ?? null;

                if ($startType == null) {
                    throw new \snac\exceptions\SNACDatabaseException("Neo4J Node did not have a type");
                }

                if (count($path->relationships()) > 1) {
                    $this->logger->addWarning("Redirected a Constellation, {$from->getID()}, which had two in-relations from the same source.");
                }
                // Relationship id/version
                foreach ($path->relationships() as $relation) {
                    // Need to know Relation type (ICRELATION, RRELATION, HIRELATION)
                    $type = $relation->type();

                    $data = [];

                    // Resource Relations have id/version
                    if ($relation->hasValue('id'))
                        $data["id"] = $relation->value('id');
                    if ($relation->hasValue('version'))
                        $data["version"] = $relation->value('version');

                    // Constellation Relations have arcrole
                    if ($relation->hasValue('arcrole'))
                        $data["arcrole"] = $relation->value('arcrole');

                    // Add the relation to the other Constellation if the ids are different
                    if ($startID != $to->getID()) {
                        // Note: matches the two nodes, and if a relation already exists of this type (ICRELATION,
                        //       RRELATION, HIRELATION) then it will just update that relation and overwrite any
                        //       values in Neo4J.  If the relation doesn't exist, it will instead create the
                        //       relation with the information.
                        $result = $this->connector->run("MATCH (a:$startType {id: {id1} }),(b:Identity {id: {id2} })
                                                            MERGE (a)-[r:$type]->(b) SET r += {infos}",
                        [
                            'id1' => $startID,
                            'id2' => "{$to->getID()}", // need a string for neo4j
                            'infos' => $data
                        ]);
                    }
                }

            }

            $this->deleteConstellation($from);

            return true;
        }

        return false;

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
        $this->logger->addDebug("Reading relationships from Neo4J");

        $result = $this->connector->run("MATCH p=(a:Identity)-[r:ICRELATION]->(b:Identity {id: {icid}}) return p;",
            [
                'icid' => $constellation->getID()
            ]
        );

        // List out relations
        $rels = array();
        foreach ($result->getRecords() as $record) {
            $path = $record->pathValue("p");

            $target = new \snac\data\Constellation();
            $target->setID($path->start()->value("id"));
            $target->setArkID($path->start()->value("ark"));
            $target->setVersion($path->start()->value("version"));

            $targetName = new \snac\data\NameEntry();
            $targetName->setOriginal($path->start()->value("name"));

            $target->addNameEntry($targetName);

            $relation = new \snac\data\ConstellationRelation();
            if ($path->relationships()[0]->hasValue('id'))
                $relation->setID($path->relationships()[0]->value('id'));
            if ($path->relationships()[0]->hasValue('version'))
                $relation->setVersion($path->relationships()[0]->value('version'));
            $type = new \snac\data\Term();
            $type->setTerm($path->relationships()[0]->value('arcrole'));
            $relation->setType($type);

            array_push($rels, [
                "constellation" => $target,
                "relation" => $relation
            ]);
        }

        // Sort the in edges by preferred name
        usort($rels,
                function ($a, $b) {
                    return $a['constellation']->getPreferredNameEntry()->getOriginal() <=> $b['constellation']->getPreferredNameEntry()->getOriginal();
                });

        return $rels;
    }

    /**
     * Search Holding Institutions
     *
     * Searches for a holding institution
     *
     * @param  string $name Beginning of constellation preferred name entry to search
     * @param  integer $count optional Number of results to return
     * @return string[]  Returns list of id-name pairs.
     */

    public function searchHoldingInstitutions($name, $count=0) {
        $realCount = \snac\Config::$SQL_LIMIT;
        if ($count > 0)
            $realCount = $count;

        $result = $this->connector->run("MATCH p=(:Resource)-[r:HIRELATION]->(a:Identity) where a.name_lower STARTS WITH {name} return DISTINCT a ORDER BY a.name limit $realCount;",
            [
                'name' => strtolower($name)
            ]
        );

        // List out relations
        $matches = array();
        foreach ($result->getRecords() as $record) {
            array_push($matches, [
                "id" => $record->get("a")->value("id"),
                "term" => $record->get("a")->value("name")
            ]);
        }

        return $matches;
    }

    /**
     * Check Holding Institution Status
     *
     * Checks whether the given constellation is a holding institution and sets a constellation flag
     *
     * @param  \snac\data\Constellation $constellation Constellation to search
     * @return boolean  Returns true if it's a holding repository, false otherwise
     */
    public function checkHoldingInstitutionStatus(&$constellation) {
        $result = $this->connector->run("RETURN EXISTS((:Resource)-[:HIRELATION]-(:Identity {id: {icid}}));",
            [
                'icid' => $constellation->getID()
            ]
        );

        $isHoldingInstitution = $result->firstRecord()->values()[0];

        if ($isHoldingInstitution === true) {
                $constellation->setFlag("holdingRepository");
                return true;
        }
        return false;
    }

    /**
     * Get Holding Institution Statistics
     *
     * Returns the statistics of the given Holding Institution.  It currently returns
     * the number of resources connected to the holding repository as well as the number
     * of constellations connected to those resources.
     *
     * @param  \snac\data\Constellation $constellation Constellation to search
     * @return string[] An associative array of statistical data
     */
    public function getHoldingInstitutionStats(&$constellation) {
        $return = [];
        $result = $this->connector->run("MATCH p=()-[r:HIRELATION]->(a:Identity {id: {icid}}) return count(r) as count;",
            [
                'icid' => $constellation->getID()
            ]
        );
        if (count($result->getRecords()) == 1) {
            if ($result->firstRecord()->get('count') > 0) {
                $return['instRes'] = $result->firstRecord()->get('count');;
            }
        }
        $result = $this->connector->run("MATCH (r:Resource) return count(r) as count;",
            [
            ]
        );
        if (count($result->getRecords()) == 1) {
            if ($result->firstRecord()->get('count') > 0) {
                $return['allRes'] = $result->firstRecord()->get('count');;
            }
        }

        $result = $this->connector->run("MATCH p=(c:Identity)-->(:Resource)-[r:HIRELATION]->(a:Identity {id: {icid}}) return count(distinct(c)) as count;",
            [
                'icid' => $constellation->getID()
            ]
        );
        if (count($result->getRecords()) == 1) {
            if ($result->firstRecord()->get('count') > 0) {
                $return['instCons'] = $result->firstRecord()->get('count');;
            }
        }
        $result = $this->connector->run("MATCH (r:Identity) return count(r) as count;",
            [
            ]
        );
        if (count($result->getRecords()) == 1) {
            if ($result->firstRecord()->get('count') > 0) {
                $return['allCons'] = $result->firstRecord()->get('count');;
            }
        }

        return $return;

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
        $result = $this->connector->run("MATCH p=(a:Identity {id: {icid}})-[r:ICRELATION]->(b:Identity) return p;",
            [
                'icid' => $constellation->getID()
            ]
        );

        // List out relations
        $rels = array();
        foreach ($result->getRecords() as $record) {
            $path = $record->pathValue("p");

            $target = new \snac\data\Constellation();
            $target->setID($path->end()->value("id"));
            $target->setArkID($path->end()->value("ark"));
            $target->setVersion($path->end()->value("version"));

            $targetName = new \snac\data\NameEntry();
            $targetName->setOriginal($path->end()->value("name"));

            $target->addNameEntry($targetName);

            $relation = new \snac\data\ConstellationRelation();
            if ($path->relationships()[0]->hasValue('id'))
                $relation->setID($path->relationships()[0]->value('id'));
            if ($path->relationships()[0]->hasValue('version'))
                $relation->setVersion($path->relationships()[0]->value('version'));
            $type = new \snac\data\Term();
            $type->setTerm($path->relationships()[0]->value('arcrole'));
            $relation->setType($type);

            array_push($rels, [
                "constellation" => $target,
                "relation" => $relation
            ]);
        }

        // Sort the in edges by preferred name
        usort($rels,
                function ($a, $b) {
                    return $a['constellation']->getPreferredNameEntry()->getOriginal() <=> $b['constellation']->getPreferredNameEntry()->getOriginal();
                });

        return $rels;
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

            // STEP 1: Update or insert this resource as a node:
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

            // STEP 2: Update or insert the resource's link to holding repository
            $result = $this->connector->run("MATCH (a:Resource {id: {id} })-[r:HIRELATION]->()
                return r;",
                [
                    'id' => $resource->getID(),
                ]
            );
            $records = $result->getRecords();
            if (!empty($records)) {
                // delete the one there so that we can add the correct one (just in case)
                $result = $this->connector->run("MATCH (a:Resource {id: {id}})-[r:HIRELATION]->() delete r;",
                    [
                        'id' => $resource->getID()
                    ]
                );

            }

            // If resource has a repository, then add a link
            if ($resource->getRepository() != null && $resource->getRepository()->getID() != null) {
                $this->connector->run("MATCH (a:Identity {id: {id1} }) MATCH (b:Resource {id: {id2} }) CREATE (b)-[r:HIRELATION]->(a);",
                    [
                        'id1' => (string) $resource->getRepository()->getID(),   
                        'id2' => $resource->getID()
                    ]);
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
            $this->logger->addDebug("Deleting Resource Node from Neo4J database");
            $result = $this->connector->run("MATCH (a:Resource {id: {id}}) detach delete a;",
                [
                    'id' => $resource->getID()
                ]
            );
            $this->logger->addDebug("Updated neo4j to remove resource");
        }

    }


    /**
     * Get Resource Relationships
     *
     * Given a resource id, returns an array of constellation ids related to that resource.
     *
     * @param $resource_id The id of the resource
     * @return string[]    An array of related constellation ids
     */
    public function getResourcesRelatedConstellationIDs($resourceID) {
        if ($this->connector != null) {
            // Returning a single array of ids using collect()
            $result = $this->connector->run("MATCH (r:Resource {id: '{$resourceID}' })-[:RRELATION]-(i:Identity) return collect(i.id) as ids ");
            $relatedConstellationIDs = $result->getRecord()->get("ids");
            return $relatedConstellationIDs;
        }
    }

    /**
     * Get Holdings
     *
     * Given a constellation id, returns an array of resources held by that holding repository.
     *
     * @param $icid The constellation id of the holding reposit
     * @return array $holdings  An array of resources' with id, title, href and count of resource relationships.
     */
    public function getHoldings($icid) {
        if ($this->connector != null) {
            $result = $this->connector->run("MATCH (:Identity {id: '{$icid}'})<-[:HIRELATION]-(r:Resource)
                return r.id as id, r.title as title, r.href as href, size((r)<-[:RRELATION]-(:Identity)) as relation_count
                order by r.title");
            $holdings = [];

            foreach ($result->getRecords() as $record) {
                $id = $record->get('id');
                $title = $record->get('title');
                $href = $record->get('href');
                $relation_count = $record->get('relation_count');
                $holdings[] = ["id" => $id, "title" => $title, "href" => $href, "relation_count" => $relation_count];
            }
        return $holdings;

        }
    }

    /**
     * Count Holdings
     *
     * Given a constellation id, returns count of its holdings.
     *
     * @param $icid The constellation id of the holding reposity
     * @return int $count Count of resources
     */
    public function countHoldings($icid) {
        $result = $this->connector->run("MATCH (c:Identity {id: '{$icid}'}) RETURN size((c)<-[:HIRELATION]-(:Resource)) as count");
        $count = $result->getRecord()->get("count");
        return $count;
    }


    /**
     * Get Relations
     *
     * Given a constellation id, returns an array of its linked constellations.
     *
     * @param $icid The constellation id
     * @return string[] $constellations' id, entity_type, and name
     */
    public function getICRelations($icid) {
        $result = $this->connector->run("MATCH (:Identity {id: '{$icid}'})-[:ICRELATION]-(i:Identity) return i.id, i.entity_type, i.name;");
        $relations = [];
        foreach ($result->getRecords() as $record) {
            $relations[] = [ "id" => $record->get("i.id"),
                             "entity_type" => $record->get("i.entity_type"),
                             "name" => $record->get("i.name")
                           ];
        }
        return $relations;
    }

    /**
     * Get Resources
     *
     * Given a constellation id, returns all its linked resources.
     *
     * @param $icid The constellation id
     * @return string[] Resources
     */
    public function getResources($icid) {
        $result = $this->connector->run("MATCH (:Identity {id: '{$icid}'})-[:RRELATION]->(r:Resource)
            return r.id as id, r.title as title, r.href as href order by r.title");
        $resources = [];

        foreach ($result->getRecords() as $record) {
            $id = $record->get('id');
            $title = $record->get('title');
            $href = $record->get('href');
            $resources[] = ["id" => $id, "title" => $title, "href" => $href];
        }
        return $resources;
    }


    /**
     * Merge Resource
     *
     * Removes all RRELATIONS from a victim and copies them to the target resource node
     *
     * @param \snac\data\Resource $resource The victim object to be discarded
     * @param \snac\data\Resource $target The target resource object
     * @return true
     */
    public function mergeResource($victim, $target) {
            // find all related Identities on the target resource
            $result = $this->connector->run("MATCH (victim:Resource {id: {victimResourceID}})<-[rel1:RRELATION]-(victims_ic:Identity)
                                             MATCH (target:Resource {id: {targetResourceID}})
                                             MERGE (target)<-[rel2:RRELATION]-(victims_ic)
                                             SET rel2 = rel1
                                             DETACH DELETE (victim);",
                [
                    "victimResourceID" => "{$victim->getID()}",
                    "targetResourceID" => "{$target->getID()}"
                ]
            );

            return true;
    }

    /**
    * Get Shared Resources
    *
    * Given a constellation id, returns all its linked resources.
    *
    * @param $icid The constellation id
    * @return string[] Resources
    */
    public function getSharedResources($icid1, $icid2) {
        $result = $this->connector->run("MATCH (i1:Identity {id: {icid1}})-[rr1:RRELATION]->(r:Resource)<-[rr2:RRELATION]-(i2:Identity {id: {icid2}})
            USING INDEX i1:Identity(id) USING INDEX i2:Identity(id)
            return r.id as id, r.title as title, r.href as href, rr1.role as arcrole_1, rr2.role as arcrole_2 order by r.title",
            [
                "icid1" => "{$icid1}",
                "icid2" => "{$icid2}"
            ]
        );
        $resources = [];

        foreach ($result->getRecords() as $record) {
            $id = $record->get("id");
            $title = $record->get("title");
            $href = $record->get("href");
            $arcrole_1 = $record->get("arcrole_1");
            $arcrole_2 = $record->get("arcrole_2");
            $resources[] = [
                             "id" => $id,
                             "title" => $title,
                             "href" => $href,
                             "arcrole_1" => $arcrole_1,
                             "arcrole_2" => $arcrole_2
                            ];
        }
        return $resources;
    }

}

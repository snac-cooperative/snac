<?php
/**
 * Elastic Search Utility Class File
 *
 * Contains the Elastic Search connection and query information
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

namespace snac\server\elastic;

use \snac\Config as Config;
use \snac\exceptions\SNACDatabaseException;

/**
 * Elastic Search Utility Class
 *
 * This class provides the Elastic Search methods to query and update the ES indices.
 *
 * @author Robbie Hott
 *
 */
class ElasticSearchUtil {

    /**
     * @var \Elasticsearch\Client Elastic Search client connected to SNAC ES instance
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
        $this->logger = new \Monolog\Logger('ElasticSearchUtil');
        $this->logger->pushHandler($log);

        if (\snac\Config::$USE_ELASTIC_SEARCH) {
            $this->connector = \Elasticsearch\ClientBuilder::create()
            ->setHosts([\snac\Config::$ELASTIC_SEARCH_URI])
            ->setRetries(0)
            ->build();
        }
        $this->logger->addDebug("Created elastic search client");
    }

    /**
     * Write or Update Name Indices
     *
     * Writes the names from the given constellation to the name indices in Elastic Search.  If they already exist in ES, they
     * are updated. If not, they are inserted.
     *
     * @param \snac\data\Constellation $constellation The constellation object to insert/update in Elastic Search
     */
    public function writeToNameIndices(&$constellation) {

        if ($this->connector != null) {

            // Check wikipedia for an image to cache
            $wiki = \snac\server\util\WikipediaUtil();
            list($hasImage, $imgURL, $imgCaption) = $wiki->getWikiImage($constellation->getArk());

            $params = [
                'index' => \snac\Config::$ELASTIC_SEARCH_BASE_INDEX,
                'type' => \snac\Config::$ELASTIC_SEARCH_BASE_TYPE,
                'id' => $constellation->getID(),
                'body' => [
                    'nameEntry' => $constellation->getPreferredNameEntry()->getOriginal(),
                    'entityType' => $constellation->getEntityType()->getTerm(),
                    'arkID' => $constellation->getArk(),
                    'id' => (int) $constellation->getID(),
                    'degree' => (int) count($constellation->getRelations()),
                    'resources' => (int) count($constellation->getResourceRelations()),
                    'hasImage' => $hasImage,
                    'imageURL' => $imgURL,
                    'imageCaption' => $imgCaption,
                    'timestamp' => date('c')
                ]
            ];

            $this->connector->index($params);
            foreach ($constellation->getNameEntries() as $entry) {
                $params = [
                    'index' => \snac\Config::$ELASTIC_SEARCH_BASE_INDEX,
                    'type' => \snac\Config::$ELASTIC_SEARCH_ALL_TYPE,
                    'id' => $entry->getID(),
                    'body' => [
                        'nameEntry' => $entry->getOriginal(),
                        'entityType' => $constellation->getEntityType()->getTerm(),
                        'arkID' => $constellation->getArk(),
                        'id' => (int) $constellation->getID(),
                        'name_id' => (int) $entry->getID(),
                        'degree' => (int) count($constellation->getRelations()),
                        'resources' => (int) count($constellation->getResourceRelations()),
                        'timestamp' => date("c")
                    ]
                ];
                $this->connector->index($params);
            }
            $this->logger->addDebug("Updated elastic search with new constellation name entries");
        }
    }

    /**
     * Delete Names from Name Indices
     *
     * Deletes the names found in the given constellation from the Elastic Search name indices.
     *
     * @param \snac\data\Constellation $constellation The constellation object to delete from Elastic Search
     */
    public function deleteFromNameIndices(&$constellation) {

        if ($this->connector != null) {
            $params = [
                    'index' => \snac\Config::$ELASTIC_SEARCH_BASE_INDEX,
                    'type' => \snac\Config::$ELASTIC_SEARCH_BASE_TYPE,
                    'id' => $constellation->getID()
            ];

            $this->connector->delete($params);
            foreach ($constellation->getNameEntries() as $entry) {
                $params = [
                    'index' => \snac\Config::$ELASTIC_SEARCH_BASE_INDEX,
                    'type' => \snac\Config::$ELASTIC_SEARCH_ALL_TYPE,
                    'id' => $entry->getID()
                ];
                $this->connector->delete($params);
            }
            $this->logger->addDebug("Updated elastic search to remove constellation");
        }

    }

    /**
     * List Recently Updated
     *
     * List the recently updated entries from the given Elastic Search index and type.
     *
     * @param string $index The elastic search index
     * @param string $type The elastic search index type
     * @return string[] List of recently updated records in the elastic search index
     */
    public function listRecentlyUpdated($index, $type) {
        $params = [
            'index' => $index,
            'type' => $type,
            'body' => [
                'sort' => [
                    'timestamp' => [
                        "order" => "desc"
                    ]
                ]
            ]
        ];
        $this->logger->addDebug("Defined parameters for search", $params);
        $results = $this->connector->search($params);
        $this->logger->addDebug("Completed Elastic Search", $results);

        return $results["hits"]["hits"];
    }



    /**
     * List Random with Image
     *
     * List random entries from the given Elastic Search index and type that have images
     *
     * @param string $index The elastic search index
     * @param string $type The elastic search index type
     * @param boolean $withImage optional Whether or not to require images
     * @return string[] List of recently updated records in the elastic search index
     */
    public function listRandomConstellations($index, $type, $withImage=true) {
        $imagePart = '"match": {"hasImage": true}';
        if ($withImage === false)
            $imagePart = '"match_all" : {}';

        $json = '{"query": {
                    "function_score" : {
                        "query" : { '.$imagePart.' },
                        "random_score" : {}
                    }
                },
                "size" : 30
            }';

        $params = [
            'index' => $index,
            'type' => $type,
            'body' => $json

        ];
        
        $this->logger->addDebug("Defined parameters for search", $params);
        $results = $this->connector->search($params);
        $this->logger->addDebug("Completed Elastic Search", $results);

        return $results["hits"]["hits"];
    }

    /**
     * Search SNAC Main Index
     *
     * Searches the main names index for the query.  Allows for pagination by the start and count parameters.
     *
     * @param string $query The search query
     * @param integer $start optional The result index to start from (default 0)
     * @param integer $count optional The number of results to return from the start (default 10)
     * @return string[] Results from Elastic Search: total, results list, pagination (num pages), page (current page)
     */
    public function searchMainIndex($query, $start=0, $count=10) {
        $this->logger->addDebug("Searching for a Constellation");

        if (\snac\Config::$USE_ELASTIC_SEARCH) {

            $params = [
                'index' => \snac\Config::$ELASTIC_SEARCH_BASE_INDEX,
                'type' => \snac\Config::$ELASTIC_SEARCH_BASE_TYPE,
                'body' => [
                    /* This query uses a keyword search
                       'query' => [
                        'query_string' => [
                            'fields' => [
                                "nameEntry"
                            ],
                            'query' => '*' . $input["term"] . '*'
                        ]
                    ],
                    'from' => $start,
                    'size' => $count*/

                    /* This query uses a full-phrase matching search */
                    'query' => [
                        'match_phrase_prefix' => [
                            'nameEntry' => [
                                'query' => $query,
                                'slop' => 20
                            ]
                        ]
                    ],
                    'from' => $start,
                    'size' => $count
                ]
            ];
            $this->logger->addDebug("Defined parameters for search", $params);

            $results = $this->connector->search($params);

            $this->logger->addDebug("Completed Elastic Search", $results);

            $return = array ();
            foreach ($results["hits"]["hits"] as $i => $val) {
                array_push($return, $val["_source"]);
            }

            $response = array();
            $response["total"] = $results["hits"]["total"];
            $response["results"] = $return;

            if ($response["total"] == 0 || $count == 0) {
                $response["pagination"] = 0;
                $response["page"] = 0;
            } else {
                $response["pagination"] = ceil($response["total"] / $count);
                $response["page"] = floor($start / $count);
            }
            $this->logger->addDebug("Created search response to the user", $response);

            return $response;
        }

        return array (
                    "notice" => "Not Using ElasticSearch"
        );
    }

    /**
     * Search SNAC Main Index with Resource Degree
     *
     * Searches the main names index for the query using number of related resources as a factor.  Allows for pagination by the start and count parameters.
     *
     * @param string $query The search query
     * @param integer $start optional The result index to start from (default 0)
     * @param integer $count optional The number of results to return from the start (default 10)
     * @return string[] Results from Elastic Search: total, results list, pagination (num pages), page (current page)
     */
    public function searchMainIndexWithDegree($query, $start=0, $count=10) {
        $this->logger->addDebug("Searching for a Constellation");

        if (\snac\Config::$USE_ELASTIC_SEARCH) {

            $params = [
                'index' => \snac\Config::$ELASTIC_SEARCH_BASE_INDEX,
                'type' => \snac\Config::$ELASTIC_SEARCH_BASE_TYPE,
                'body' => [

                    /* This query uses a full-word matching search */
                    'query' => [
                        'function_score' => [
                            'query' => [
                                'match' => [
                                    'nameEntry' => [
                                        'query' => $query,
                                        'operator' => 'and'
                                    ]
                                ]
                            ],
                            'field_value_factor' => [
                                'field' => 'resources',
                                'modifier' => 'log1p',
                                'factor' => 1.5
                            ],
                            'boost_mode' => "multiply",
                            'max_boost' => 3
                        ]
                    ],
                    'from' => $start,
                    'size' => $count
                ]
            ];
            $this->logger->addDebug("Defined parameters for search", $params);

            $results = $this->connector->search($params);

            $this->logger->addDebug("Completed Elastic Search", $results);

            $return = array ();
            foreach ($results["hits"]["hits"] as $i => $val) {
                array_push($return, $val["_source"]);
            }

            $response = array();
            $response["total"] = $results["hits"]["total"];
            $response["results"] = $return;

            if ($response["total"] == 0 || $count == 0) {
                $response["pagination"] = 0;
                $response["page"] = 0;
            } else {
                $response["pagination"] = ceil($response["total"] / $count);
                $response["page"] = floor($start / $count);
            }
            $this->logger->addDebug("Created search response to the user", $response);

            return $response;
        }

        return array (
                    "notice" => "Not Using ElasticSearch"
        );
    }

    /**
     * Write or Update Resource Indices
     *
     * Writes the given resource to the resource indices in Elastic Search.  If they already exist in ES, they
     * are updated. If not, they are inserted.
     *
     * @param \snac\data\Resource $resource The resource object to insert/update in Elastic Search
     */
    public function writeToResourceIndices(&$resource) {

        if ($this->connector != null) {
            $params = [
                'index' => \snac\Config::$ELASTIC_SEARCH_RESOURCE_INDEX,
                'type' => \snac\Config::$ELASTIC_SEARCH_RESOURCE_TYPE,
                'id' => $resource->getID(),
                'body' => [
                    'id' => (int) $resource->getID(),
                    'title' => $resource->getTitle(),
                    'url' => $resource->getLink(),
                    'abstract' => $resource->getAbstract(),
                    'type' => $resource->getDocumentType()->getTerm(),
                    'type_id' => (int) $resource->getDocumentType()->getID(),
                    'timestamp' => date('c')
                ]
            ];

            $this->connector->index($params);
            $this->logger->addDebug("Updated elastic search with new resource");
        }
    }

    /**
     * Delete Resource from Resource Indices
     *
     * Deletes the given resource from the Elastic Search resource indices.
     *
     * @param \snac\data\Resource $resource The resource object to delete from Elastic Search
     */
    public function deleteFromResourceIndices(&$resource) {

        if ($this->connector != null) {
            $params = [
                    'index' => \snac\Config::$ELASTIC_SEARCH_RESOURCE_INDEX,
                    'type' => \snac\Config::$ELASTIC_SEARCH_RESOURCE_TYPE,
                    'id' => $resource->getID()
            ];

            $this->logger->addDebug("Updated elastic search to remove resource");
        }

    }

    /**
     * Search SNAC Resources Index
     *
     * Searches the resources index for the query.  Allows for pagination by the start and count parameters.
     *
     * @param string $query The search query
     * @param integer $start optional The result index to start from (default 0)
     * @param integer $count optional The number of results to return from the start (default 10)
     * @return string[] Results from Elastic Search: total, results list, pagination (num pages), page (current page)
     */
    public function searchResourceIndex($query, $start=0, $count=10) {
        $this->logger->addDebug("Searching for a Resource");

        if (\snac\Config::$USE_ELASTIC_SEARCH) {

            $params = [
                'index' => \snac\Config::$ELASTIC_SEARCH_RESOURCE_INDEX,
                'type' => \snac\Config::$ELASTIC_SEARCH_RESOURCE_TYPE,
                'body' => [
                    /* This query uses a keyword search
                       'query' => [
                        'query_string' => [
                            'fields' => [
                                "title",
                                "url"
                            ],
                            'query' => '*' . $query . '*'
                        ]
                    ],
                    'from' => $start,
                    'size' => $count*/

                    /* This query uses a full-phrase matching search */
                    'query' => [
                        'match_phrase' => [
                            '_all' => [
                                'query' => $query,
                                'slop' => 20
                            ]                        ]
                    ],
                    'from' => $start,
                    'size' => $count
                    /* This query uses a full-phrase matching search
                    'query' => [
                        'match_phrase_prefix' => [
                            'nameEntry' => [
                                'query' => $query,
                                'slop' => 20
                            ]
                        ]
                    ],
                    'from' => $start,
                    'size' => $count*/
                ]
            ];
            $this->logger->addDebug("Defined parameters for search", $params);

            $results = $this->connector->search($params);

            $this->logger->addDebug("Completed Elastic Search", $results);

            $return = array ();
            foreach ($results["hits"]["hits"] as $i => $val) {
                array_push($return, $val["_source"]);
            }

            $response = array();
            $response["total"] = $results["hits"]["total"];
            $response["results"] = $return;

            if ($response["total"] == 0 || $count == 0) {
                $response["pagination"] = 0;
                $response["page"] = 0;
            } else {
                $response["pagination"] = ceil($response["total"] / $count);
                $response["page"] = floor($start / $count);
            }
            $this->logger->addDebug("Created resource search response to the user", $response);

            return $response;
        }

        return array (
                    "notice" => "Not Using ElasticSearch"
        );
    }

}

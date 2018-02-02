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
            $wiki = new \snac\server\util\WikipediaUtil();
            list($hasImage, $imgURL, $imgMeta) = $wiki->getWikiImage($constellation->getArk());


            $subjects = [];
            foreach ($constellation->getSubjects() as $subject) {
                array_push($subjects, $subject->getTerm()->getTerm());
            }
            
            $occupations = [];
            foreach ($constellation->getOccupations() as $occupation) {
                array_push($occupations, $occupation->getTerm()->getTerm());
            }

            $functions = [];
            foreach ($constellation->getFunctions() as $function) {
                array_push($functions, $function->getTerm()->getTerm());
            }

            $biogHists = [];
            foreach ($constellation->getBiogHistList() as $biogHist) {
                array_push($biogHists, $biogHist->getText());
            }



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
                    'subject' => $subjects,
                    'occupation' => $occupations,
                    'function' => $functions,
                    'biogHist' => $biogHists,
                    'hasImage' => $hasImage,
                    'imageURL' => $imgURL,
                    'imageMeta' => $imgMeta,
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
            try {
                $this->connector->delete($params);
            } catch (\Exception $e) {
                $this->logger->addWarning("ConstellationID not found when deleting from elastic search index: ". $e->getMessage(), $e->getTrace());
            }
            foreach ($constellation->getNameEntries() as $entry) {
                $params = [
                    'index' => \snac\Config::$ELASTIC_SEARCH_BASE_INDEX,
                    'type' => \snac\Config::$ELASTIC_SEARCH_ALL_TYPE,
                    'id' => $entry->getID()
                ];
                try {
                    $this->connector->delete($params);
                } catch (\Exception $e) {
                    $this->logger->addWarning("ConstellationID not found when deleting from elastic search index: ". $e->getMessage(), $e->getTrace());
                }
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

                    /* This query uses a full-phrase matching search 
                    'query' => [
                        'match_phrase_prefix' => [
                            'nameEntry' => [
                                'query' => $query,
                                'slop' => 20
                            ]
                        ]
                    ],*/
                    'query' => [
                        'filtered' => [
                            'query' => [
                                'match_phrase_prefix' => [
                                    'nameEntry' => [
                                        'query' => $query,
                                        'slop' => 20
                                    ]
                                ]
                            ],
                            'filter' => [
                                'bool' => [
                                    'must' => [
                                        'term' => [
                                            'subject' => 'women sculptors'
                                        ]
                                    ]
                                ]
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
     * Autocomplete Search SNAC Main Index
     *
     * Searches the main names index for the query using number of related resources as a factor, while also using the
     * Elastic Search simple_query_string query that allows for wildcard, missing, and edit distance queries.  This provides
     * an autocomplete-like response by appending the wildcard "*" to the end of the search string.
     *
     * Allows for pagination by the start and count parameters.
     *
     * @param string $query The search query
     * @param string $entityType optional The entity type to search for, or null
     * @param integer $start optional The result index to start from (default 0)
     * @param integer $count optional The number of results to return from the start (default 10)
     * @return string[] Results from Elastic Search: total, results list, pagination (num pages), page (current page)
     */
    public function searchMainIndexAutocomplete($query, $entityType=null, $start=0, $count=10) {

        $searchBody = [
            /* This query uses a full-word matching search */
            'query' => [
                'function_score' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                               'simple_query_string' => [
                                   'fields' => ['nameEntry'],
                                   'query' => $query .'*',
                                   'default_operator' => 'and'
                               ]
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
            ]
        ];
        if ($entityType !== null) {
            $searchBody["query"]["function_score"]["query"]["bool"]["filter"] = [
                    "term" => [
                        'entityType' => strtolower($entityType) // strange elastic search behavior
                    ]
            ];
        }



        return $this->elasticSearchQuery($searchBody, $start, $count);

    }

    /**
     * Advanced Search SNAC Main Index
     *
     * Searches the main names index for the query using number of related resources as a factor, while also using the
     * Elastic Search simple_query_string query that allows for wildcard, missing, and edit distance queries.
     * Allows for pagination by the start and count parameters.
     *
     * @param string $query The search query
     * @param string $entityType optional The entity type to search for, or null
     * @param integer $start optional The result index to start from (default 0)
     * @param integer $count optional The number of results to return from the start (default 10)
     * @param string[][] $parameters optional The list of facets and other parameters to use when performing this search
     * @param boolean $fullSearch optional Whether to search the full text (i.e. biogHist plus names). 
     * @return string[] Results from Elastic Search: total, results list, pagination (num pages), page (current page)
     */
    public function searchMainIndexAdvanced($query, $entityType=null, $start=0, $count=10, $parameters=null, $fullSearch=false) {

        $searchBody = [
            /* This query uses a full-word matching search */
            'query' => [
                'function_score' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                               'simple_query_string' => [
                                   'fields' => ['nameEntry'],
                                   'query' => $query,
                                   'default_operator' => 'and'
                               ]
                           ], 
                           "filter" => [
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
            ]
        ];
        if ($fullSearch !== false) {
            unset($searchBody["query"]["function_score"]["query"]["bool"]["must"]);
            $searchBody["query"]["function_score"]["query"]["bool"]["should"] = [
                [
                    'simple_query_string' => [
                        'fields' => ['nameEntry', 'biogHist'],
                        'query' => $query,
                        'default_operator' => 'and'
                    ]
                ]
            ];
            $searchBody["query"]["function_score"]["query"]["bool"]["minimum_should_match"] = 1;
        }
        if ($entityType !== null) {
            array_push($searchBody["query"]["function_score"]["query"]["bool"]["filter"], [
                'term' => [
                    'entityType' => strtolower($entityType) // strange elastic search behavior
                ]
            ]);
        }
        
        if ($parameters !== null && is_array($parameters) && !empty($parameters)) {
            // Allow an empty query that will just return whatever (as long as there are parameters
            if ($query == "" ) {
                if (isset($searchBody["query"]["function_score"]["query"]["bool"]["must"]))
                    unset($searchBody["query"]["function_score"]["query"]["bool"]["must"]);
                if (isset($searchBody["query"]["function_score"]["query"]["bool"]["should"]))
                    unset($searchBody["query"]["function_score"]["query"]["bool"]["should"]);
                if (isset($searchBody["query"]["function_score"]["query"]["bool"]["minimum_should_match"]))
                    unset($searchBody["query"]["function_score"]["query"]["bool"]["minimum_should_match"]);
                $searchBody["sort"] = [
                    [ "nameEntry.untokenized" => "asc" ],
                    "_score"
                ];
            }
            foreach ($parameters as $type=>$values) {
                foreach ($values as $value) {
                    array_push($searchBody["query"]["function_score"]["query"]["bool"]["filter"], [
                        'match' => [
                            $type.".untokenized" => [
                                'query' => $value 
                            ]
                        ]
                    ]);
                }
            }
        }



        return $this->elasticSearchQuery($searchBody, $start, $count);
    }

    /**
     * Search SNAC Main Index with Resource Degree
     *
     * Searches the main names index for the query using number of related resources as a factor.  Allows for pagination by the start and count parameters.
     *
     * @param string $query The search query
     * @param string $entityType optional The entity type to search for, or null
     * @param integer $start optional The result index to start from (default 0)
     * @param integer $count optional The number of results to return from the start (default 10)
     * @param string[][] $parameters optional The list of facets and other parameters to use when performing this search
     * @param boolean $fullSearch optional Whether to search the full text (i.e. biogHist plus names). 
     * @return string[] Results from Elastic Search: total, results list, pagination (num pages), page (current page)
     */
    public function searchMainIndexWithDegree($query, $entityType=null, $start=0, $count=10, $parameters=null, $fullSearch=false) {

        $searchBody = [
            /* This query uses a full-word matching search */
            'query' => [
                'function_score' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                'match' => [
                                    'nameEntry' => [
                                        'query' => $query,
                                        'operator' => 'and'
                                    ]
                                ]
                            ],
                            'filter' => [
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
            ]
        ];


        if ($fullSearch !== false) {
            unset($searchBody["query"]["function_score"]["query"]["bool"]["must"]);
            $searchBody["query"]["function_score"]["query"]["bool"]["should"] = [
                [
                    'match' => [
                        'nameEntry' => [
                            'query' => $query,
                            'operator' => 'and'
                        ]
                    ]
                ],
                [
                    'match' => [
                        'biogHist' => [
                            'query' => $query,
                            'operator' => 'and'
                        ]
                    ]
                ]
            ];
            $searchBody["query"]["function_score"]["query"]["bool"]["minimum_should_match"] = 1;
        }


        if ($entityType !== null) {
            array_push($searchBody["query"]["function_score"]["query"]["bool"]["filter"], [
                'term' => [
                    'entityType' => strtolower($entityType) // strange elastic search behavior
                ]
            ]);
        }
        if ($parameters !== null && is_array($parameters) && !empty($parameters)) {
            // Allow an empty query that will just return whatever (as long as there are parameters
            if ($query == "" ) {
                if (isset($searchBody["query"]["function_score"]["query"]["bool"]["must"]))
                    unset($searchBody["query"]["function_score"]["query"]["bool"]["must"]);
                if (isset($searchBody["query"]["function_score"]["query"]["bool"]["should"]))
                    unset($searchBody["query"]["function_score"]["query"]["bool"]["should"]);
                if (isset($searchBody["query"]["function_score"]["query"]["bool"]["minimum_should_match"]))
                    unset($searchBody["query"]["function_score"]["query"]["bool"]["minimum_should_match"]);
                $searchBody["sort"] = [
                    [ "nameEntry.untokenized" => "asc" ],
                    "_score"
                ];
            }

            // Filter on the given parameters
            foreach ($parameters as $type=>$values) {
                foreach ($values as $value) {
                    array_push($searchBody["query"]["function_score"]["query"]["bool"]["filter"], [
                        'match' => [
                            $type.".untokenized" => [
                                'query' => $value 
                            ]
                        ]
                    ]);
                }
            }
        }

        return $this->elasticSearchQuery($searchBody, $start, $count);

    }

    /**
     * Search Elastic Search with Query
     *
     * Searches the main names index for the given query body.  This is a helper function to condense the codebase.
     *
     * @param string[] $searchBody Associative array of the Elastic Search query body
     * @param integer $start optional The result index to start from (default 0)
     * @param integer $count optional The number of results to return from the start (default 10)
     * @return string[] Results from Elastic Search: total, results list, pagination (num pages), page (current page)
     */
    private function elasticSearchQuery($searchBody, $start=0, $count=10) {
        $this->logger->addDebug("Searching for a Constellation");

        if (\snac\Config::$USE_ELASTIC_SEARCH) {

            $body = $searchBody;
            $body["from"] = $start;
            $body["size"] = $count;

            $aggs = [
                    "subject"=> [
                        "terms"=> [
                            "field" => "subject.untokenized",
                            "size" => 10
                        ]
                    ],
                    "occupation"=> [
                        "terms"=> [
                            "field" => "occupation.untokenized",
                            "size" => 10
                        ]
                    ],
                    "function"=> [
                        "terms"=> [
                            "field" => "function.untokenized",
                            "size" => 10
                        ]
                    ]
                ];
            $body["aggregations"] = $aggs;

            $params = [
                'index' => \snac\Config::$ELASTIC_SEARCH_BASE_INDEX,
                'type' => \snac\Config::$ELASTIC_SEARCH_BASE_TYPE,
                'body' => $body,
                'from' => $start,
                'size' => $count
            ];

            $this->logger->addDebug("Defined parameters for search", $params);

            $results = $this->connector->search($params);

            $this->logger->addDebug("Completed Elastic Search", $results);

            $return = array ();
            foreach ($results["hits"]["hits"] as $i => $val) {
                array_push($return, $val["_source"]);
            }

            $aggregations = array();
            if (isset($results["aggregations"])) {
                foreach($results["aggregations"] as $agg => $vals) {
                    if (!isset($aggregations[$agg]))
                        $aggregations[$agg] = array();

                    if (isset($vals["buckets"])) {
                        foreach ($vals["buckets"] as $term) {
                            array_push($aggregations[$agg], [
                                "term" => $term["key"],
                                "count" => $term["doc_count"]
                            ]);
                        }
                    }
                }
            }

            $response = array();
            $response["total"] = $results["hits"]["total"];
            $response["results"] = $return;
            $response["aggregations"] = $aggregations;

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
     * @param array $filters optional Array of term => value pairs to filter by (default null)
     * @return string[] Results from Elastic Search: total, results list, pagination (num pages), page (current page)
     */
    public function searchResourceIndex($query, $start=0, $count=10, $filters=null) {
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
            
            if (isset($filters)){
                // build an ES filter and append to $params
                // TODO: put in filters as an array of terms instead of adding to one term
                $queryFilter =  ['bool' => ['filter' => ['term' => []]]];  // any diff tween 'must' and 'filter'?
                foreach ($filters as $field => $value) {
                    $queryFilter['bool']['filter']['term'][$field] = $value;
                }
                $params['body']['filter'] = $queryFilter;
            }

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

<?php

/**
 * OpenRefine Constellation Mapper File
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2021 the Rector and Visitors of the University of Virginia
 */
namespace snac\client\openrefine;

use \snac\client\util\ServerConnect as ServerConnect;

/**
 * OpenRefine Constellation Mapper
 *
 * Maps OpenRefine query data into Constellations to be used when querying
 * the SNAC server for the reconciliation process.
 *
 * @author Robbie Hott
 */
class ORConstellationMapper {

    /**
     * @var \Monolog\Logger $logger the logger for this server
     */
    private $logger;

    /**
     * @var array $properties A set of properties that are provided for
     * additional reconciliation data.  They are stored in Open Refine ready
     * format to include the name, description, and id.
     */
    private $properties = [
        [
            "name" => "entityType",
            "description" => "Entity Type",
            "id" => "entityType"
        ],
        [
            "name" => "sameAs",
            "description" => "Alternative ID for the entity",
            "id" => "sameAs"
        ]
    ];

    /**
     * @var array $vocabCache Cache of vocabulary terms searched
     * during this session
     */
    private $vocabCache = [];

    /**
     * Constructor
     */
    public function __construct() {
        global $log;

        // create a log channel
        $this->logger = new \Monolog\Logger('ORConstellationMapper');
        $this->logger->pushHandler($log);
        
        
        $this->connect = new ServerConnect();
    }

    /**
     * Get List of Properties
     *
     * Returns the list of properties allowed by our OpenRefine endpoint.
     * 
     * @return array Properties in OpenRefine format
     */
    public function getProperties() {
        return $this->properties;
    }

    /**
     * Filter Properties by Prefix
     *
     * Performs a case-insensitive linear search of the properties. This is
     * used when the OpenRefine suggest API is called to find a property. It
     * matches on a prefix (starts-with search)
     *
     * @param string $prefix The search query
     * @return array An array of property objects in OR format
     */
    public function filterPropertiesPrefix($prefix) {
        $return = [];
        foreach ($this->properties as $p) {
            if (stripos($p["name"], $prefix) !== false && stripos($p["name"], $prefix) == 0)
                array_push($return, $p);
        }
        return $return;
    }

    /**
     * Map OR query data to Constellation
     *
     * Maps OpenRefine query data, based on the properties allowed,
     * into a Constellation object.
     *
     * The OpenRefine suggest API provides a list of properties that
     * the user can connect to the column to be reconciled.  The list we
     * allowed is provided in this class.  When the reconcile query happens,
     * each OR row that has data for that property is provided in the
     * properties array.  The array contains objects with:
     * - pid - The property ID as we define in this class
     * - v - The value from OpenRefine data.
     *
     * @param array $query The OpenRefine query with data to be mapped
     * @return \snac\data\Constellation A Constellation object with that data
     */
    public function mapConstellation($query) {
        $testC = new \snac\data\Constellation();
        $testN = new \snac\data\NameEntry();
        $testN->setOriginal($query["query"]);
        $testC->addNameEntry($testN);

        if (isset($query["properties"])) {
            foreach ($query["properties"] as $p) {
                if ($p["pid"] == "sameAs") {
                    $sameas = new \snac\data\SameAs();
                    $sameas->setURI($p["v"]);
                    $sameasType = $this->vocabStringLookup("record_type", "sameAs");
                    $sameas->setType($sameasType);
                    $testC->addOtherRecordID($sameas);
                } else if ($p["pid"] == "entityType") {
                    $entityType = $this->vocabStringLookup("entity_type", $p["v"]);
                    $testC->setEntityType($entityType);
                }             
            }
        }

        return $testC;
    }

    /**
     * Look up Vocabulary Term
     *
     * Looks up the vocabulary term by the string value and the type
     *
     * @param string $type The type of the term
     * @param string $term The term value to find
     * @return \snac\data\Term The first result when searching for this term
     */  
    public function vocabStringLookup($type, $term) {
        if (isset($this->vocabCache[$type."|".$term]))
            return $this->vocabCache[$type."|".$term];
        
        $ask = [
            "command" => "vocabulary",
            "query_string" => $term,
            "type" => $type
        ];
        $response = $this->connect->query($ask);

        if (isset($response["results"]) && isset($response["results"][0])) {
            $returnTerm = new \snac\data\Term($response["results"][0]);
            $this->vocabCache[$type."|".$term] = $returnTerm;
            return $returnTerm;
        }

        return null;
    }

}

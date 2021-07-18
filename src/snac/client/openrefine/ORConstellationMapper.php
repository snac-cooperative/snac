<?php

/**
 * OpenRefine Constellation Mapper File
 *
 * Contains 
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2021 the Rector and Visitors of the University of Virginia
 */
namespace snac\client\openrefine;

use \snac\client\util\ServerConnect as ServerConnect;

/**
 *
 * @author Robbie Hott
 */
class ORConstellationMapper {

    /**
     * @var \Monolog\Logger $logger the logger for this server
     */
    private $logger;

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

    public function getProperties() {
        return $this->properties;
    }

    public function filterPropertiesPrefix($prefix) {
        $return = [];
        foreach ($this->properties as $p) {
            if (stripos($p["name"], $prefix) !== false && stripos($p["name"], $prefix) == 0)
                array_push($return, $p);
        }
        return $return;
    }

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

    public function vocabStringLookup($type, $term) {
        $ask = [
            "command" => "vocabulary",
            "query_string" => $term,
            "type" => $type
        ];
        $response = $this->connect->query($ask);

        if (isset($this->vocabCache[$type."|".$term]))
            return $this->vocabCache[$type."|".$term];

        if (isset($response["results"]) && isset($response["results"][0])) {
            $returnTerm = new \snac\data\Term($response["results"][0]);
            $this->vocabCache[$type."|".$term] = $returnTerm;
            return $returnTerm;
        }

        return null;
    }

}

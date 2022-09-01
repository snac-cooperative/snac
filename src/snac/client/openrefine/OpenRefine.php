<?php

/**
 * OpenRefine Client File
 *
 * Contains the main OpenRefine class that responds to OpenRefine requests
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2017 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\client\openrefine;

use \snac\client\util\ServerConnect as ServerConnect;
use \snac\client\util\LaravelUtil as LaravelUtil;

/**
 * OpenRefine Class
 *
 * The OpenRefine client for SNAC.  This client accepts OpenRefine reconciliation requests and
 * calls the Server (using the ServerAPI) to get the appropriate information to return
 * to the OpenRefine clients.
 *
 * @author Robbie Hott
 */
class OpenRefine {

    /**
     * Input parameters from the querier
     *
     * @var array Associative array of the input query
     */
    private $input = null;

    private $json = null;

    private $mapper = null;

    /**
     * Response text
     *
     * @var string response
     */
    private $response = "";

    /**
     * @var \Monolog\Logger $logger the logger for this server
     */
    private $logger;

    /**
     * Constructor
     *
     * Requires the input to the server as an associative array
     *
     * @param array $input Input to the server
     */
    public function __construct($input, $json) {
        global $log;

        $this->input = $input;
        $this->json = $json;

        $this->mapper = new ORConstellationMapper();

        // create a log channel
        $this->logger = new \Monolog\Logger('OpenRefine');
        $this->logger->pushHandler($log);
    }

    /**
     * Run Method
     *
     * Starts the server
     */
    public function run() {
        $this->logger->addDebug("Handling query", $this->input);
        $this->logger->addDebug("Handling query JSON", array($this->json));

        $this->connect = new ServerConnect();
        $this->lvUtil = new LaravelUtil();

        // suggest/property
        $command = $this->input["command"] ?? null;
        if (isset($command)
            && ($command == "suggest")
            && (isset($this->input["subcommand"]))
            && ($this->input["subcommand"] == "property") ) {

            $result = $this->suggestProperty($this->input["prefix"]);

            $this->response = json_encode($result, JSON_PRETTY_PRINT);
            if (isset($this->input["callback"]))
                $this->response = $this->input["callback"] . "(" . $this->response . ");";
            return $this->response;
        }

        $this->logger->addDebug("Made it past the initial section", []);

        // Decide what to do based on the OpenRefine parameters:
        //  - query = only one search being done at this point
        //  - queries = multiple searches in an array being requested
        //  - else give information about the endpoint
        if (isset($this->input["queries"]["q0"]["type"]) && $this->input["queries"]["q0"]["type"] != "CPF") {
            $this->logger->addDebug("Reconciling Concept", []);
            // Concept reconciliation
            $results = [];
            foreach ($this->input["queries"] as $qid => $query) {
                $reconciled = $this->reconcileConceptTerm($query['query'], $query['type']);
                foreach ($reconciled as $i => $result) {
                    $result["id"] = strval($result["id"]);
                    $result["type"] = [$result["type"]];
                    $results[$qid]["result"] = [$result];
                }
            }
            $this->logger->addDebug("Concept reconciliation results", $results);
            // Set the response appropriately for OpenRefine
            $this->response = json_encode($results, JSON_PRETTY_PRINT);
        } elseif (isset($this->input["query"])) {
            // TODO: Determine if input["query"] path is used
            // CPF Reconciliation
            $query = $this->input["query"];
            $max = $query["limit"] ?? 10;

            // Read the query as a name entry in a new constellation
            $testC = $this->mapper->mapConstellation($query);

            // Ask the server to reconcile the constellation
            $ask = [
                "command" => "reconcile",
                "constellation" => $testC->toArray()
            ];
            $this->logger->addDebug("Reconciling.", array($ask));
            $response = $this->connect->query($ask);
            $this->logger->addDebug("Reconciling.", array($response));

            // Convert the reconciliation results into OpenRefine results
            if (isset($response["reconciliation"])) {
                $results = array();
                foreach ($response["reconciliation"] as $i => $result) {

                    if ($i > $max) break;

                    // build the CSV line to print
                    $output = array(
                        "name" => $result["identity"]["nameEntries"][0]["original"],
                        "id" => (string) $result["identity"]["id"],
                        "type" => ["CPF"],
                        "score" => round($result["strength"], 2),
                        "match" => ($result["strength"] > 11 ? true : false)
                    );
                    array_push($results, $output);
                }
            }

            // Set the response appropriately for OpenRefine
            $this->response = json_encode(["result" => $results], JSON_PRETTY_PRINT);
        } elseif (isset($this->input["queries"])
                  && isset($this->input["queries"]["q0"]["type"])
                  && $this->input["queries"]["q0"]["type"] == "CPF")
        {
            // CPF Reconciliation
            $this->logger->addDebug("CPF Reconciliation");
            $queries = $this->input["queries"];
            $results = array();

            // We basically repeat the individual query logic above for each individual inner-query
            foreach ($queries as $qid => $query) {
                $max = $query["limit"] ?? 5;
                $testC = $this->mapper->mapConstellation($query);

                $ask = [
                    "command" => "reconcile",
                    "constellation" => $testC->toArray()
                ];

                $response = $this->connect->query($ask);

                if (isset($response["reconciliation"])) {
                    $results[$qid] = [
                        "result" => []
                    ];
                    foreach ($response["reconciliation"] as $i => $result) {

                        if ($i > $max) break;

                        // build the results line
                        $output = array(
                            "name" => $result["identity"]["nameEntries"][0]["original"],
                            "id" => (string) $result["identity"]["id"],
                            "type" => ["CPF"],
                            "score" => round($result["strength"], 2),
                            "match" => ($result["strength"] > 11 ? true : false)
                        );
                        array_push($results[$qid]["result"], $output);
                    }
                }
            }

            // Set the response appropriately for OpenRefine
            $this->logger->addDebug("Reconciliation results: ", $results);
            $this->response = json_encode($results, JSON_PRETTY_PRINT);
        } else {
            // Default response: give information about this OpenRefine endpoint
            $response = [
                "defaultTypes" => [
                    [
                        "id" => "CPF",
                        "name" => "Identity Constellation"
                    ],
                    [
                        "id" => "subject",
                        "name" => "Subject Concept Term"
                    ],
                    [
                        "id" => "occupation",
                        "name" => "Occupation Concept Term"
                    ],
                    [
                        "id" => "activity",
                        "name" => "Activity Concept Term"
                    ]
                ],
                "view" => [
                    "url" => \snac\Config::$WEBUI_URL . "/view/{{id}}"
                ],
                "identifierSpace" => \snac\Config::$WEBUI_URL,
                "name" => \snac\Config::$OPENREFINE_ENDPOINT_NAME,
                "schemaSpace" => \snac\Config::$OPENREFINE_URL,
                "preview" => [
                    "width" => 400,
                    "height" => 500,
                    "url" => \snac\Config::$WEBUI_URL . "/snippet/{{id}}"
                ],
                "suggest" => [
                    "property" => [
                        "service_url" => \snac\Config::$OPENREFINE_URL,
                        "service_path" => "/suggest/property"
                    ]
                ]
            ];

            $this->response = json_encode($response, JSON_PRETTY_PRINT);
        }

        if (isset($this->input["callback"]))
            $this->response = $this->input["callback"] . "(" . $this->response . ");";
        return $this->response;
    }

    /**
     * Suggest Property
     *
     * Suggest OpenRefine properties
     *
     * @param $subcommand
     * @param $prefix
     * @return array $result Result array with name, description, and id of property
     */
    public function suggestProperty($prefix = null){
        $error = [
            "status" => "error",
            "message" => "invalid query",
            "details" => "prefix"
        ];
        $result = [];

        if (!isset($prefix))
        return $error;

        $result["result"] = $this->mapper->filterPropertiesPrefix($this->input["prefix"]);
        return $result;
    }

    /**
     * Get Response Headers
     *
     * Returns the headers for the server's return value. This will likely
     * usually be the JSON content header.
     *
     * @return array headers for output
     */
    public function getResponseHeaders() {
        return array(
            "Content-Type: application/json",
            "Access-Control-Allow-Origin: *",
            "Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS",
            "Access-Control-Allow-Headers: Origin, Accept, Content-Type, X-Requested-With, X-CSRF-Token"
        );
    }

    /**
     * Get Return Statement
     *
     * This should compile the Server's response statement. Currently, it returns a
     * JSON-encoded string or other content value that can be echoed to generate the
     * appropriate response. This should usually be JSON, but may be the contents of a file
     * to be downloaded by the user, so we leave it flexible rather than returning an
     * associative array.
     *
     * @return string server response appropriately encoded
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * Reconcile Concept Term
     *
     * Sends a GET request to SNAC-Laravel to reconcile Concept Term
     * (e.g. Subject, Activity, Occupation)
     *
     * @param string $term
     * @param string $category
     * @return array $response
     */
    public function reconcileConceptTerm($term, $category)
    {
        $query = ["term" => $term, "category" => $category];

        return  $this->lvUtil->getLaravel("/api/concepts/reconcile", $query);
    }

}

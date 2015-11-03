<?php
/**
 * Web Frontend
 *
 * This script will read from the $_GET variable for URL arguments.
 * It requires a "q" argument with the name to parse and match. It will print out
 * as JSON the results from the reconciliation engine.
 */
error_reporting(E_ALL);

// Dependencies
require 'vendor/autoload.php';
function my_autoload ($pClassName) {
    include(__DIR__ . "/" . str_replace("\\", "/", $pClassName) . ".php");
}
spl_autoload_register("my_autoload");

// USE Statements
use \reconciliation_engine\identity\identity as identity;


header("Content-Type: application/json");
if (isset($_GET['q'])) {

    $engine = new \reconciliation_engine\reconciliation_engine();

    // Add stages to run
    $engine->add_stage("elastic_original");
    $engine->add_stage("elastic_name");
    $engine->add_stage("elastic_seventyfive");
    $engine->add_stage("original_length");
    $engine->add_stage("multi_stage", "elastic_name", "original_length_difference");
    $engine->add_stage("multi_stage", "elastic_name", "publicity");

    // Create the new identity to search
    $identity = new identity($_GET['q']);
    $identity->parse_original();

    // Run the reconciliation engine against this identity
    $engine->reconcile($identity);

    $output = array();
    $output["search_identity"] = $identity;
    $output["results"] = $engine->get_results();

    $toprint =  json_encode($output, JSON_PRETTY_PRINT);

    if ($toprint !== false)
        echo $toprint;
    else
        echo "{ 'error' => 'could not parse object to json, " . json_last_error_msg() ."' }";
}

?>

<?php
/**
 * Web Frontend
 *
 * This script will read from the $_GET variable for URL arguments.
 * It requires a "q" argument with the name to parse and match. It will print out
 * as JSON the results from the reconciliation engine.
 */

// Dependencies
include ("../../../vendor/autoload.php");

header("Content-Type: application/json");
if (isset($_GET['q'])) {

    $engine = new \snac\server\identityReconciliation\ReconciliationEngine();

    // Add stages to run
    $engine->addStage("ElasticOriginalNameEntry");
    $engine->addStage("ElasticNameOnly");
    $engine->addStage("ElasticSeventyFive");
    $engine->addStage("OriginalLength");
    $engine->addStage("MultiStage", "ElasticNameOnly", "OriginalLengthDifference");
    $engine->addStage("MultiStage", "ElasticNameOnly", "SNACDegree");

    // Create the new identity to search
    $identity = new \snac\data\Constellation();
    $name = new \snac\data\NameEntry();
    $name->setOriginal($_GET['q']);
    $identity->addNameEntry($name);

    // Run the reconciliation engine against this identity
    $engine->reconcile($identity);

    $output = array();
    $output["search_identity"] = $identity->toArray(true);
    $results = array();
    foreach ($engine->getResults() as $k => $v) {
        $results[$k] = $v->toArray();
    }
    $output["results"] = $results;

    $toprint =  json_encode($output, JSON_PRETTY_PRINT);

    if ($toprint !== false)
        echo $toprint;
    else
        echo "{ 'error' => 'could not parse object to json, " . json_last_error_msg() ."' }";
}

?>

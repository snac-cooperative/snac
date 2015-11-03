<?php
/**
 * Main PHP file that loads and runs the reconciliation engine
 */

// Dependencies
require 'vendor/autoload.php';
require 'reconciliation_engine.php';

$engine = new reconciliation_engine\reconciliation_engine();
// Add stages to run
$engine->add_stage("elastic_original");
$engine->add_stage("elastic_name");
$engine->add_stage("elastic_seventyfive");
$engine->add_stage("original_length");
$engine->add_stage("multi_stage", "elastic_name", "original_length_difference");
    $engine->add_stage("multi_stage", "elastic_name", "publicity");

// Create the new identity to search
$identity = new identity("");
$identity->original_string = "George Washington University";
//$identity->original_string = "George Washington 1732";
//$identity->name_only = "George Washington";

// Run the reconciliation engine against this identity
$engine->reconcile($identity);

// Print the results
print_r($engine->get_results());


echo "Done";

?>
    

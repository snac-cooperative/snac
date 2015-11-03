<?php
/**
 * Idenitty Reconciliation Engine  File
 *
 * Contains the main identity reconciliation engine code 
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server\identityReconciliation;

/**
 * Name Reconciliation Engine (Main Class)
 *
 * This class provides the meat of the reconciliation engine. To run the
 * reconciliation engine, create an instance of this class and call the
 * reconcile method.
 *
 * @author Robbie Hott
 */
class ReconciliationEngine {

    /**
     * @var array Raw test results directly from the tests.  This is going to be per
     * test, then per ID.  Later, they will be parsed into a per ID, per test.
     */
    private $raw_results;

    /**
     * @var array Array of tests to perform on the string.  These will have a listing in
     * the battery of tests.  A user may chose a list of tests, a preset list,
     * or write their own. 
     */ 
    private $tests;

    /**
     * @var array Full test results per id
     */
    private $results;

    /**
     * @var weights\helpers\weight Instance of the weighting class to produce the final value of a
     * weighted result vector.
     */
    private $weight;

    /**
     * @var number Number of results to return
     */
    private $num_results = 25;

    /**
     * Constructor
     */
    public function __construct() {
        $this->raw_results = array();
        $this->tests = array();
        $this->results = array();
        $this->weight = new weights\static_weight();
        return;
    }

    /**
     * Destructor
     */
    public function __destruct() {
        return;
    }

    /**
     * Add stage
     *
     * Adds a stage to the list of stages to run
     *
     * @param string $stage name of the stage to include
     */
    public function add_stage($stage) {
        // Load the class as a reflection
        $class = new \ReflectionClass("snac\server\identityReconciliation\stages\\".$stage);
        
        if (func_num_args() < 2) {
            // If only one argument, then create with no params
            array_push($this->tests, $class->newInstance());
        } else {
            // If more than one argument, the rest are parameters to the constructor
            $args = func_get_args();

            // Remove the class name off the list
            array_shift($args);

            // Instantiate and add the class with the args
            array_push($this->tests, $class->newInstanceArgs($args));
        }
            
    }

    /**
     * Main reconciliation function
     *
     * This function does the reconciliation and returns the top identity from
     * the engine.  Other top identities and their corresponding score vectors
     * may be obtained by other functions within this class.  
     * @param identity $identity The identity to be searched. This identity 
     * must be in the proper form 
     * @return identity The top identity by the reconciliation
     * engine
     */
    public function reconcile($identity) {
	unset($this->raw_results);
	unset($this->results);
        $this->raw_results = array();
        $this->results = array();
        $this->weight = new weights\static_weight();
        // Run the tests and collect the results
        foreach ($this->tests as $test) {
            $this->raw_results[$test->get_name()] = $test->run($identity, null);
        }

        // Fix up the results by organizing them by name, then by test
        $this->collate_results();

        // Generate all the scores
        $this->generate_scores();

        // Sort by score
        $this->sort_results();

        // Return the top result from the list
        return $this->top_result();
    }

    /**
     * Get the top result
     *
     * Returns the top result from the result set
     *
     * @return identity The top identity by the reconciliation engine
     */
    public function top_result() {
        if (count($this->results) > 0)
            return $this->results[0]["identity"];
        else
            return null;
    }

    /**
     * Get the top result vector
     *
     * Returns the vector of result values for the top result
     *
     * @return array The result vector for the top result
     */
    public function top_vector() {
        if (count($this->results) > 0) 
            return $this->results[0]["vector"];
        else
            return null;
    }

    /**
     * Get the top result value
     *
     * Returns the numeric value for the top result
     *
     * @return float The numerical value for the top result
     */
    public function top_value() {
        if ($this->top_vector() != null) 
            return $this->results[0]["score"];
        else
            return 0;
    }

    /**
     * Generate Scores
     *
     * Generates all the scores for each vector in the results
     */
    public function generate_scores() {
        foreach ($this->results as $i => $res) {
            $this->results[$i]["score"] = $this->weight->compute($res["vector"]);
        }
    }

    /**
     * Collate Results
     *
     * This function takes the raw output of the reconciliation engine and
     * reformats it back to results that can be easily parsed by id.
     * Specifically, it takes the results of the per-test values and returns
     * them per-id.
     */
    public function collate_results() {
        $tmp = array();
        $all = array();
        foreach ($this->raw_results as $test => $res_list) {
            foreach ($res_list as $res) {
                $k = null;
                
                if ($res["id"] == null) {
                    // If the identity is null, this should apply to all results
                    $all[$test] = $res["strength"];
                } else {
                    // Get Unique ID for this identity
                    $k = $res["id"]->unique_id();
                    // Create entry in the array if it doesn't exist
                    if (!array_key_exists($k, $tmp)) {
                        $tmp[$k] = array("identity"=>$res["id"],
                                         "score" => 0,
                                         "vector" => array());
                    }
                    // Store the strength value in the vector
                    $tmp[$k]["vector"][$test] = $res["strength"];
                }
            }
        }
        // Add any global results to every id's vector
        foreach ($tmp as $k => $v) {
            foreach ($all as $test => $result)
                $tmp[$k]["vector"][$test] = $result;
        }

        // Push the results on the result array
        foreach ($tmp as $res) 
            array_push($this->results, $res);
    }

    /**
     * Sort results
     *
     * Sorts the results by score, highest to lowest
     */
    private function sort_results() {
        usort($this->results, array("reconciliation_engine\\reconciliation_engine", "results_rsort"));
    }

    /**
     * Get all results
     *
     * @return array The full array of results
     */
    public function get_results() {
        return array_splice($this->results,0,$this->num_results);
    }
    
    /**
     * Reverse sort of results
     */
    public static function results_rsort($a, $b) {
         if ($a["score"] == $b["score"])
             return 0;
         return ($a["score"] < $b["score"]) ? 1 : -1;
     }

}



?>

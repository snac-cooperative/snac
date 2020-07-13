<?php
/**
 * Identity Reconciliation Engine  File
 *
 * Contains the main identity reconciliation engine code
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server\identityReconciliation;

/**
 * Identity Reconciliation Engine (Main Class)
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
    private $rawResults;

    /**
     * @var array Array of tests to perform on the string.  These will have a listing in
     * the battery of tests.  A user may chose a list of tests, a preset list,
     * or write their own.
     */
    private $tests;

    /**
     * @var stages\helpers\Stage[] Array of post-processing stages to apply to results after
     * the initial set of stages have been run.  This would ideally add additional "filtering"
     * scores onto the original collated result set.
     */
    private $postProcessingTests;

    /**
     * @var \snac\data\ReconciliationResult[] Full test results per id
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
    private $numResults = 25;

    /**
     * Constructor
     */
    public function __construct() {
        $this->raw_results = array();
        $this->tests = array();
        $this->results = array();
        $this->postProcessingTests = array();
        $this->weight = new weights\StaticWeight();
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
    public function addStage($stage) {
        // Load the class as a reflection
        $class = new \ReflectionClass("\\snac\\server\\identityReconciliation\\stages\\".$stage);

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
     * Add post-processing stage
     *
     * Adds a stage to the list of stages to run
     *
     * @param string $stage name of the stage to include
     */
    public function addPostProcessingStage($stage) {
        // Load the class as a reflection
        $class = new \ReflectionClass("\\snac\\server\\identityReconciliation\\stages\\".$stage);

        if (func_num_args() < 2) {
            // If only one argument, then create with no params
            array_push($this->postProcessingTests, $class->newInstance());
        } else {
            // If more than one argument, the rest are parameters to the constructor
            $args = func_get_args();

            // Remove the class name off the list
            array_shift($args);

            // Instantiate and add the class with the args
            array_push($this->postProcessingTests, $class->newInstanceArgs($args));
        }

    }

    /**
     * Main reconciliation function
     *
     * This function does the reconciliation and returns the top identity from
     * the engine.  Other top identities and their corresponding score vectors
     * may be obtained by other functions within this class.
     * @param \snac\data\Constellation $identity The constellation to be searched. This identity
     * must be in the proper form
     * @return identity The top identity by the reconciliation
     * engine
     */
    public function reconcile($identity) {
        unset($this->rawResults);
        unset($this->results);
        $this->rawResults = array();
        $this->results = array();
        $this->weight = new weights\StaticWeight();
        // Run the tests and collect the results
        foreach ($this->tests as $test) {
            $this->rawResults[$test->getName()] = $test->run($identity, null);
        }

        // Fix up the results by organizing them by name, then by test
        $this->collateResults();

        // Run post-processing tests over all the testing results that apply additional filter scores
        unset($this->rawResults);
        $this->rawResults = array();
        foreach ($this->postProcessingTests as $test) {
            $this->rawResults[$test->getName()] = $test->run($identity, $this->results);
        }

        // Re-collate the post-processing tests inso the main result list
        $this->collateResults();

        // Generate all the scores
        $this->generateScores();

        // Sort by score
        $this->sortResults();

        // Return the top result from the list
        return $this->topResult();
    }

    /**
     * Get the top result
     *
     * Returns the top result from the result set
     *
     * @return identity The top identity by the reconciliation engine
     */
    public function topResult() {
        if (count($this->results) > 0)
            return $this->results[0]->getIdentity();
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
    public function topVector() {
        if (count($this->results) > 0)
            return $this->results[0]->getVector();
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
    public function topValue() {
        if ($this->topVector() != null)
            return $this->results[0]->getStrength();
        else
            return 0;
    }

    /**
     * Generate Scores
     *
     * Generates all the scores for each vector in the results
     */
    public function generateScores() {
        foreach ($this->results as $i => $res) {
            $this->results[$i]->setStrength($this->weight->compute($res->getVector()));
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
    public function collateResults() {
        $all = array();
        foreach ($this->rawResults as $test => $resList) {
            foreach ($resList as $res) {
                $k = null;

                if ($res->getIdentity() == null) {
                    // If the identity is null, this should apply to all results
                    $all[$test] = $res->getStrength();
                } else {
                    // Get Unique ID for this identity
                    $k = $res->getIdentity()->getArkID();
                    // Create entry in the array if it doesn't exist
                    if (!array_key_exists($k, $this->results)) {
                        $this->results[$k] = new \snac\data\ReconciliationResult();
                        $this->results[$k]->setIdentity($res->getIdentity());
                    }
                    // Store the strength value in the vector
                    $this->results[$k]->setScore($test, $res->getStrength());
                    $this->results[$k]->setMultipleProperties($res->getAllProperties());
                }
            }
        }
        // Add any global results to every id's vector
        foreach ($this->results as &$v) {
            foreach ($all as $test => $result)
                $v->setScore($test, $result);
        }
        // Be correct with foreach pass by reference
        unset($v);
    }

    /**
     * Sort results
     *
     * Sorts the results by score, highest to lowest
     */
    private function sortResults() {
        usort($this->results, array("\\snac\\server\\identityReconciliation\\ReconciliationEngine", "resultsRsort"));
    }

    /**
     * Get all results
     *
     * @return \snac\data\ReconciliationResult[] The full array of results
     */
    public function getResults() {
        return array_splice($this->results,0,$this->numResults);
    }

    /**
     * Reverse sort of results
     *
     * This method returns the sort result of the two parameters.  It can be used
     * in usort to sort a list of results in descending order based on strength of
     * result (score).
     *
     * @param \snac\data\ReconciliationResult $a The left-hand operation in a comparison
     * @param \snac\data\ReconciliationResult $b The right-hand operation in a comparison
     * @return int Zero if equal, negative if a's score is greater than b, otherwise positive
     */
    public static function resultsRsort($a, $b) {
         if ($a->getStrength() == $b->getStrength())
             return 0;
         return ($a->getStrength() < $b->getStrength()) ? 1 : -1;
     }

}

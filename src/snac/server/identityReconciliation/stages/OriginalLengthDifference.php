<?php

/**
 * Original Length Difference Stage Class File
 *
 * IR Stage file
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server\identityReconciliation\stages;

/**
 * Original length difference class
 *
 * This stage computes the difference in string length of the original string
 * and each of the identites passed in as the list.  The score for each
 * element in the list is the negative natural log of the difference.  So,
 * all results are either negative or 0.  Larger differences result in values
 * that are more negative.
 *
 * @author Robbie Hott
 */

class OriginalLengthDifference implements helpers\Stage {

    /**
     * Get Name
     *
     * Gets the name of the stage and returns it.  This must return a string.
     *
     * @return string Name of the stage.
     */
    public function getName() {
        return "OriginalLengthDifference";
    }


    /**
     * Run function
     *
     * Calculates the difference in string length between the search identity's original string and the original string of each identites in the list passed.  To calculate the score, we use the following algorithm:
     *
     * `result = -1 * ln( abs( len(search) - len(other) ) )`
     *
     * @param \snac\data\Constellation $search The constellation to be evaluated.
     * @param \snac\data\ReconciliationResult[] $list A list of results to evaluate against.  This
     * may be null.
     * @return array An array of results.  On error, it must at least
     * return an empty array. It may not return null.
     *
     */
    public function run($search, $list) {
        // Error case, list is null
        if ($list == null)
            return array();

        $results = array();

        foreach ($list as $res) {
            // Compute the strength value
            $diff = strlen($search->getPreferredNameEntry()->getOriginal()) - strlen($res->getIdentity()->getPreferredNameEntry()->getOriginal());
            $diff = abs($diff);
            $resultDiff = 0;
            if ($diff > 0)
                $resultDiff = -4 * log($diff);

            $result = new \snac\data\ReconciliationResult();
            $result->setIdentity($res->getIdentity());
            $result->setStrength($resultDiff);
            // Save the result
            array_push($results, $result);
        }

        return $results;
    }

}

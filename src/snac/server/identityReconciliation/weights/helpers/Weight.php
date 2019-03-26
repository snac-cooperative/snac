<?php

/**
 * Weight Interface File
 *
 * Interface file for all weights
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server\identityReconciliation\weights\helpers;

/**
 * Weight Interface
 *
 * This interface defines the weighting mechanism for computing one weight
 * number from a vector of test results.  It provides one method, compute,
 * which takes the vector of test results and produces one final number.  This
 * is arbitrarily defined, and the user may define any compute function they
 * wish.  For ease of use, the vector will be an associative array, with the
 * name of the stages as keys.
 *
 * @author Robbie Hott
 */
interface Weight {

    /**
     * Function that computes the strength/score of a vector by providing
     * weights and calculating the total value from the provided vector of test
     * scores.
     *
     * @param array $vector Associative array of scores, with test names as keys.
     * @return float Computed weight/score of the vector.
     */
    public function compute($vector);

}

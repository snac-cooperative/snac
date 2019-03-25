<?php

/**
 * Static Weight Class File
 *
 * Weight calculation that just sums
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server\identityReconciliation\weights;

/**
 * Static Weight Class
 *
 * This is a simple weight calculation, which just sums up all entries in the
 * vector.  It is provided as an example weighting mechanism.
 *
 * @author Robbie Hott
 */
class StaticWeight implements helpers\Weight {

    /**
     * Implements the compute method in the weight interface.
     *
     * @param array $vector Array of weights
     * @return float Computed weight of the vector. In this case, the sum of entries.
     */
    public function compute($vector) {
        $sum = 0;
        foreach ($vector as $entry)
            $sum = $sum + $entry;
        return $sum;
    }
}

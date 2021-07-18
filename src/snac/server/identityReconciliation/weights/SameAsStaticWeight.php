<?php

/**
 * Static Weight with SameAs Class File
 *
 * Weight calculation that just sums, unless SameAs stage matched
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
 * Static Weight with SameAs Class
 *
 * This is a simple weight calculation, which just sums up all entries in the
 * vector, unless SameAs class has matched, which is then scaled to 100.
 *
 * @author Robbie Hott
 */
class SameAsStaticWeight implements helpers\Weight {

    /**
     * Implements the compute method in the weight interface.
     *
     * @param array $vector Array of weights
     * @return float Computed weight of the vector. In this case, the sum of entries.
     */
    public function compute($vector) {
        // Return a full 100 score if this matched on sameas
        foreach ($vector as $k => $v) {
            if ($k == "SameAs" && $v == 100)
                return 100;
        }

        $sum = 0;
        foreach ($vector as $entry)
            $sum = $sum + $entry;
        return $sum;
    }
}


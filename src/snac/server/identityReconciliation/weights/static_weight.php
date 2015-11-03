<?php
namespace snac\server\identityReconciliation\weights;

/**
 * Static Weight Class
 *
 * This is a simple weight calculation, which just sums up all entries in the
 * vector.  It is provided as an example weighting mechanism.
 * 
 * @author Robbie Hott
 */
class static_weight implements helpers\weight {

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

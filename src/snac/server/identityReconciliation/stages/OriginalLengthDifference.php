<?php
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
     * @param \identity $search The identity to be evaluated.
     * @param \identity[] $list A list of identities to evaluate against.  This
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

        foreach ($list as $id) {
            // Compute the strength value
            $diff = strlen($search->original_string) - strlen($id->original_string);
            $diff = abs($diff);
            $result = 0;
            if ($diff > 0)
                $result = -4 * log($diff);

            // Save the result
            array_push($results, array("id"=>$id, "strength"=>$result));
        }

        return $results;
    }

}


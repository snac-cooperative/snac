<?php
namespace snac\server\identityReconciliation\stages;

/**
 * Degree within SNAC stage
 *
 * This stage incorporates the SNACDegree of the identity into the results value
 *
 * @author Robbie Hott
 */

class SNACDegree implements helpers\Stage {

    /**
     * Get Name
     *
     * Gets the name of the stage and returns it.  This must return a string.
     *
     * @return string Name of the stage.
     */
    public function getName() {
        return "SNACDegree";
    }


    /**
     * Run function
     *
     * Calculates the natural log of the original_string and returns it as a
     * global modifier to all results.
     *
     * @param \identity $search The identity to be evaluated.
     * @param \identity[] $list A list of identities to evaluate against.  This
     * may be null.  
     * @return array 
     *      
     */
    public function run($search, $list) {

        $results = array();

        foreach ($list as $id) {
            $result = 0;
            if ($id->snacDegree != null && $id->snacDegree > 0)
                $result = 5 * log($id->snacDegree);
            if (is_nan($result) || is_infinite($result))
                $result = 0;

            array_push($results, array("id"=>$id, "strength"=>$result));
        }

        return ($results);

    }

}

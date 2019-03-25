<?php

/**
 * Degree Stage Class File
 *
 * IR Stage class file
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
     * @param \snac\data\Constellation $search The constellation to be evaluated.
     * @param \snac\data\ReconciliationResult[] $list A list of resultss to evaluate against.  This
     * may be null.
     * @return array
     *
     */
    public function run($search, $list) {

        $results = array();

        foreach ($list as $res) {
            $id = $res->getIdentity();
            $result = 0;
            $degree = $res->getProperty("degree");
            $resultDeg = 0;
            if ($degree != null && $degree > 0)
                $resultDeg = 5 * log($degree);
            if (is_nan($resultDeg) || is_infinite($resultDeg))
                $resultDeg = 0;

            $result = new \snac\data\ReconciliationResult();
            $result->setIdentity($id);
            $result->setStrength($resultDeg);
            array_push($results,$result);
        }

        return ($results);

    }

}

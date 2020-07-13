<?php

/**
 * Original Length Stage Class File
 *
 * IR Stage Class file
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
 * Original length class
 *
 * This stage computes the string length of the original search string and
 * returns a strength of it.  Basically, it returns the natural log of the
 * length.  The longer the string, the larger the number.
 *
 * @author Robbie Hott
 */

class OriginalLength implements helpers\Stage {

    /**
     * Get Name
     *
     * Gets the name of the stage and returns it.  This must return a string.
     *
     * @return string Name of the stage.
     */
    public function getName() {
        return "OriginalLength";
    }


    /**
     * Run function
     *
     * Calculates the natural log of the original_string and returns it as a
     * global modifier to all results.
     *
     * @param \snac\data\Constellation $search The constellation to be evaluated.
     * @param \snac\data\Constellation[] $list A list of constellations to evaluate against.  This
     * may be null.
     * @return array An array of one element that has the log(length), ie
     * `array("id":null, "strength":float)`. On error, it must at least
     * return an empty array. It may not return null.
     *
     */
    public function run($search, $list) {
        $string = $search->getPreferredNameEntry()->getOriginal();
        $result = new \snac\data\ReconciliationResult();
        $result->setStrength( log(strlen($string)));

        return array($result);
    }

}

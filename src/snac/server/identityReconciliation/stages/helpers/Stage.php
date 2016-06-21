<?php

/**
 * Stage Interface File
 *
 * Interface file for all stages
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server\identityReconciliation\stages\helpers;


/**
 * Stage Interface
 *
 * Defines the interface for each of the stages.  Each stage will be called
 * using the public function below and the result is expected as a numerical
 * value.  This allows the plug-and-play engine to correctly quantify what
 * happened.
 *
 * @author Robbie Hott
 */

interface Stage {

    /**
     * Get Name
     *
     * Gets the name of the stage and returns it.  This must return a string.
     *
     * @return string Name of the stage.
     */
    public function getName();

    /**
     * Run function
     *
     * This function is called by the reconciliation engine to execute this
     * stage of reconciliation.  It will pass the function the name string to
     * be considered.  The function will then perform its work on the string
     * and return a numeric value based on the strength of the string and the
     * computed function.
     *
     * If the id in any of the id,strength pairs in the return is null, then
     * that strength will be applied to all returned matches.  This allows
     * for a stage that impacts all match quality, such as a search string
     * strength calculation.
     *
     * @param \snac\data\Constellation $search The identity to be evaluated.
     * @param \snac\data\ReconciliationResult[] $list A list of results to evaluate against.  This
     * may be null.  
     * @return array An array of matches and strength pairs, ie an array of
     * `array("id":identity, "strength":float)`. On error, it must at least
     * return an empty array. It may not return null.
     *
     */
    public function run($search, $list); 

}

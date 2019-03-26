<?php

/**
 * EntityType Filter Stage Class File
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
 * EntityType Filter Stage
 *
 * This stage compares the searched entityType against a result list.  It will
 * discount any results that are not the same entityType as the requested search term.
 * So, all results are either -50 (no match) or 0 (match).  If the query identity does
 * not have an entity type, this stage will return 0 for all results.
 *
 * @author Robbie Hott
 */

class EntityTypeFilter implements helpers\Stage {

    /**
     * Constant amount to discount the score by on a non-matching entity type
     *
     * @var integer
     */
    private $discount = -50;

    /**
     * Get Name
     *
     * Gets the name of the stage and returns it.  This must return a string.
     *
     * @return string Name of the stage.
     */
    public function getName() {
        return "EntityTypeFilter";
    }


    /**
     * Run function
     *
     * Compares the entity type term strings (i.e. "person").  If they match, a score of 0 is assigned.  If not,
     * then the score is discounted by a constant.
     *
     * @param \snac\data\Constellation $search The constellation to be evaluated.
     * @param \snac\data\Constellation[] $list A list of constellation to evaluate against.  This
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
            $strength = 0;

            if ($search->getEntityType() !== null && $search->getEntityType()->getTerm() !== null) {
                if ($search->getEntityType()->getTerm() != $res->getIdentity()->getEntityType()->getTerm()) {
                    $strength = $this->discount;
                }
            }

            $result = new \snac\data\ReconciliationResult();
            $result->setIdentity($res->getIdentity());
            $result->setStrength($strength);
            // Save the result
            array_push($results, $result);
        }

        return $results;
    }

}

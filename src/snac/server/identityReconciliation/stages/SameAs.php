<?php

/**
 * SameAs Stage Class File
 *
 * IR Stage abstract class file for DBUtil Stages 
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
 * SameAs Stage
 *
 * Finds matches based on sameas links
 *
 * @author Robbie Hott
 */
class SameAs implements helpers\Stage {

    /**
     * @var string Name of the stage
     */
    protected $name = "SameAs";

    /**
     * Get Name
     *
     * Gets the name of the stage and returns it.  This must return a string.
     *
     * @return string Name of the stage.
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Run function
     *
     * Finds a list of Constellations that have the same SameAs links.  If any are found,
     * they are given a score of 100 (perfect match). 
     *
     * @param \snac\data\Constellation $search The identity to be evaluated.
     * @param \snac\data\Constellation[] $list A list of identities to evaluate against.  This
     * may be null.
     * @return array An array of matches and strengths,
     * `{"id":identity, "strength":float}`.
     *
     */
    public function run($search, $list=null) {
        $cStore = new \snac\server\database\DBUtil();

        $constellations = [];

        if (!empty($search->getOtherRecordIDs())) {
            foreach ($search->getOtherRecordIDs() as $other) {
                if ($other->getURI() != null) {
                    $otherID = $other->getURI();
                    $icids = $cStore->getCurrentIDsForOtherID($otherID);
                    foreach ($icids as $icid)
                    array_push($constellations, $cStore->readPublishedConstellationByID($icid, 
                       \snac\server\database\DBUtil::$READ_ALL_BUT_RELATIONS_AND_META ));
                }
            }

        }

        
        // Return the results
        $results = array();
        foreach($constellations as $c) {
            $result = new \snac\data\ReconciliationResult();
            $result->setIdentity($c);
            $result->setStrength(100);
            array_push($results, $result);
        }
        return $results;

    }
}

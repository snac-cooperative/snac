<?php

/**
 * Elastic Search Complete Stage Class File
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2021 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server\identityReconciliation\stages;

/**
 * Elastic Search Complete Stage
 *
 * This stage queries elastic search for the primary name entry and any other
 * information provided in the search Constellation and hten returns
 * the list of identities that are the best matches for that Constellation.
 *
 * @author Robbie Hott
 */
class ElasticComplete implements helpers\Stage {

    /**
     * @var string Name
     */
    protected $name = "ElasticComplete";

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
     * Performs the body of the stage
     *
     * @param \snac\data\Constellation $search The identity to be evaluated.
     * @param \snac\data\Constellation[] $list A list of identities to evaluate against.  This
     * may be null.
     * @return array An array of matches and strengths,
     * `{"id":identity, "strength":float}`.
     *
     */
    public function run($search, $list) {
        $elastic = new \snac\server\elastic\ElasticSearchUtil();

        $query = $search->getPreferredNameOnly()->getOriginal();
        $params = []; // array with ES filters (subject, occupation, activity) 
        $entityType = null;

        // Include entity type if it exists
        if ($search->getEntityType() != null)
            $entityType = $search->getEntityType()->getTerm();

        // Run the query
        $queryResponse = $elastic->searchMainIndexWithDegree($query, $entityType, 0, 10, $params, false);

        // Return the results
        $results = array();
        foreach($queryResponse["results"] as $hit) {
            $id = new \snac\data\Constellation();
            $name = new \snac\data\NameEntry();
            $name->setOriginal($hit["nameEntry"]);
            $id->addNameEntry($name);
            $id->setArkID($hit["arkID"]);
            $id->setID($hit["id"]);

            $entityType = new \snac\data\Term();
            $entityType->setTerm($hit["entityType"]);
            $id->setEntityType($entityType);

            $result = new \snac\data\ReconciliationResult();
            $result->setIdentity($id);
            $result->setStrength($hit["_score"]);
            $result->setProperty("degree", $hit["degree"]);
            array_push($results, $result);
        }
        return $results;
    }
}

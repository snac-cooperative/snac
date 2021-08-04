<?php

/**
 * Elastic Abstract Stage Class File
 *
 * IR Stage abstract class file for Elastic Search stages
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server\identityReconciliation\stages\helpers;

/**
 * Elastic Search Abstract Stage
 *
 * This stage provides functionality to query elastic search, allowing for
 * quicker writing of other elastic search based stages.
 *
 * @author Robbie Hott
 */
abstract class Elastic implements Stage {

    /**
     * @var string Name of the stage
     */
    protected $name = "";

    /**
     * @var string Elastic Search result field to search
     */
    protected $field = "nameEntry";

    /**
     * @var string Operator to use in the search
     */
    protected $operator = null;

    /**
     * @var int Number of results to return
     */
    protected $numResults = 2500;

    /**
     * @var string Minimum match required (percentage)
     */
    protected $minMatch = null;

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
        // Get the search string based on the sub-class' method
        $search_string = $this->getSearchString($search);
        $field = $this->field;

        // Create elastic search client
        try {
            $client = \Elasticsearch\ClientBuilder::create()
            ->setHosts([\snac\Config::$ELASTIC_SEARCH_URI])
            ->setRetries(0)
            ->build();
        } catch (Error $e) {
            die("Could not instantiate Elastic Search");
        }

        $searchParams = array();
        $searchParams['index'] = \snac\Config::$ELASTIC_SEARCH_BASE_INDEX;
        $searchParams['body']['query']['match'][$this->field]["query"] = $search_string;
        if ($this->minMatch != null)
            $searchParams['body']['query']['match'][$this->field]['minimum_should_match'] = $this->minMatch;
        if ($this->operator != null)
            $searchParams['body']['query']['match'][$this->field]['operator'] = $this->operator;
        if ($this->numResults != null)
            $searchParams['body']['size'] = $this->numResults;

        // Run the query
        $queryResponse = $client->search($searchParams);

        // Return the results
        $results = array();
        foreach($queryResponse["hits"]["hits"] as $hit) {
            $id = new \snac\data\Constellation();
            $name = new \snac\data\NameEntry();
            $name->setOriginal($hit["_source"]["nameEntry"]);
            $id->addNameEntry($name);
            $id->setArkID($hit["_source"]["arkID"]);
            $id->setID($hit["_source"]["id"]);

            $entityType = new \snac\data\Term();
            $entityType->setTerm($hit["_source"]["entityType"]);
            $id->setEntityType($entityType);

            $result = new \snac\data\ReconciliationResult();
            $result->setIdentity($id);
            $result->setStrength($hit["_score"]);
            $result->setProperty("degree", $hit["_source"]["degree"]);
            array_push($results, $result);
        }
        return $results;

    }

    /**
     * Create search string
     *
     * Determines what part of the $search identity should be used as the search string
     *
     * @param identity $search The identity to parse
     * @return string The search string for elastic search
     */
    protected abstract function getSearchString($search);
}

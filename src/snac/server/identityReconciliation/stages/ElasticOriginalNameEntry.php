<?php
namespace snac\server\identityReconciliation\stages;

/**
 * Elastic Search (Name) Stage
 *
 * This stage queries elastic search for the entire original name and returns
 * the list of identities that are the best matches for that string.
 *
 * @author Robbie Hott
 */
class ElasticOriginalNameEntry extends helpers\elastic {

    /**
     * @var string Name
     */
    protected $name = "ElasticOriginalNameEntry";

    /**
     * Choose what parts to search
     *
     * @param \identity $search The identity to parse
     * @return string The search string;
     */
    protected function get_search_string($search) {
        return $search->original_string;
    }
}

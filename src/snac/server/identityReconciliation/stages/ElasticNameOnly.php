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
class ElasticNameOnly extends helpers\Elastic {

    /**
     * @var string Name
     */
    protected $name = "ElasticNameOnly";

    /**
     * @var string Operator to use
     */
    protected $operator = "AND";


    /**
     * Choose what parts to search
     *
     * @param \identity $search The identity to parse
     * @return string The search string;
     */
    protected function getSearchString($search) {
        return $search->name_only;
    }

}

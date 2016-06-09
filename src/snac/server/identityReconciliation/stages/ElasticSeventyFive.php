<?php
namespace snac\server\identityReconciliation\stages;

/**
 * Elastic Search (Name) Stage
 *
 * This stage queries elastic search for the entire original name and returns
 * the list of identities that are the best matches for that string. 75% of the
 * original string must be matched
 *
 * @author Robbie Hott
 */
class ElasticSeventyFive extends helpers\Elastic {

    /**
     * @var string Name
     */
    protected $name = "ElasticSeventyFive";

    /**
     * @var string Must match threshold
     */
    protected $minMatch = "75%";

    /**
     * Choose what parts to search
     *
     * @param \snac\data\Constellation $search The constellation to parse
     * @return string The search string;
     */
    protected function getSearchString($search) {
        return $search->getPreferredNameEntry()->getOriginal();
    }
}

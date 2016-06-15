<?php

/**
 * Elastic Seventy Five Stage Class File
 *
 * Elastic Search IR Stage File
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
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

<?php

/**
 * Elastic Original Name Entry Stage Class File
 *
 * Elastic Search IR Stage file
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
 * the list of identities that are the best matches for that string.
 *
 * @author Robbie Hott
 */
class ElasticOriginalNameEntry extends helpers\Elastic {

    /**
     * @var string Name
     */
    protected $name = "ElasticOriginalNameEntry";

    /**
     * Choose what parts to search
     *
     * @param \snac\data\Constellation $search The identity to parse
     * @return string The search string;
     */
    protected function getSearchString($search) {
        return $search->getPreferredNameEntry()->getOriginal();
    }
}

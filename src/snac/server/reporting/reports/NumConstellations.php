<?php

/**
 * Number of Constellations Report Class File
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server\reporting\reports;

/**
 * Numberof Constellations Report
 *
 * This report computes the number of constellations.
 *
 * @author Robbie Hott
 */
class NumConstellations extends helpers\Report {

    /**
     * The name of this report
     * @var string The name of this report
     */
    protected $name = "Number of Identity Constellations";

    /**
     * @var string The description of this report
     */
    protected $description = "The number of Identity Constellations in SNAC.";

    /**
     * @var string The type of the report data (series, text, numeric)
     */
    protected $type = "numerical";

    /**
     * Run report
     *
     * @param  \snac\server\database\DatabaseConnector $psql Postgres Connector
     * @return string[]       Report results
     */
    public function compute($psql) {
        $sql = "select count(*)
                from version_history as aa,
                    (select max(version) as version, id from version_history
                    where status in ('published', 'tombstone', 'deleted', 'embargoed')
                    group by id) as cc
                where
                    aa.id=cc.id and
                    aa.version=cc.version and
                    aa.status = 'published';";

        $result = $psql->query($sql, array());
        $rawResults = $psql->fetchAll($result);

        $results = array();

        if (count($rawResults) > 0) {
            $results["value"] = $rawResults[0]["count"];
        }
        return $results;

    }

}

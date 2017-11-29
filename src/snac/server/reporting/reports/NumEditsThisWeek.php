<?php

/**
 * Number of Edits This Week Report Class File
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server\reporting\reports;

/**
 * Number of Edits This Week Report
 *
 * This report computes the number of edits in the last 7 days.
 *
 * @author Robbie Hott
 */
class NumEditsThisWeek extends helpers\Report {

    /**
     * The name of this report
     * @var string The name of this report
     */
    protected $name = "Number of Edits This Week";

    /**
     * @var string The description of this report
     */
    protected $description = "The number of Identity Constellations edited this week in SNAC.";

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
                from version_history aa
                where              
                    aa.timestamp > NOW() - INTERVAL '7 days';";

        $result = $psql->query($sql, array());
        $rawResults = $psql->fetchAll($result);

        $results = array();

        if (count($rawResults) > 0) {
            $results["value"] = $rawResults[0]["count"];
        }
        return $results;

    }

}



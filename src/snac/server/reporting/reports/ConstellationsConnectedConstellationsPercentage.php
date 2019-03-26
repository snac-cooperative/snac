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
 * Number of Constellations by Type Report
 *
 * This report computes the number of constellations of each type.
 *
 * @author Robbie Hott
 */
class ConstellationsConnectedConstellationsPercentage extends helpers\Report {

    /**
     * The name of this report
     * @var string The name of this report
     */
    protected $name = "Constellations Connected To Constellations Percentages";

    /**
     * @var string The description of this report
     */
    protected $description = "What percentage of our constellations are connected to only a few constellations.";

    /**
     * @var string The type of the report data (series, text, numeric)
     */
    protected $type = "percentages";

    /**
     * Run report
     *
     * @param  \snac\server\database\DatabaseConnector $psql Postgres Connector
     * @return string[]       Report results
     */
    public function compute($psql) {
        $sql = "select count(*) from name_index;";

        $result = $psql->query($sql, array());
        $rawResults = $psql->fetchAll($result);

        $total = $rawResults[0]["count"];
        
        $sql = "select degree, count(*) from name_index where degree < 4 group by degree order by degree asc;";

        $result = $psql->query($sql, array());
        $rawResults = $psql->fetchAll($result);

        $results = array();
        $sum = 0;
        foreach ($rawResults as $result) {
            $results[$result["degree"] . " Constellations"] = ($result["count"] / (float) $total) * 100;
            $sum += $result["count"];
        }
        $results["All Others"] = ( ($total - $sum) / (float) $total) * 100;

        return $results;
    }

}


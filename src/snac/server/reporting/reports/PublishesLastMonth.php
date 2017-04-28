<?php

/**
 * Publishes in the Last Month Report Class File
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server\reporting\reports;

/**
 * Publishes in the Last Month Report
 *
 * This report computes the number of publishes in the last month by day.
 *
 * @author Robbie Hott
 */
class PublishesLastMonth extends helpers\Report {

    /**
     * The name of this report
     * @var string The name of this report
     */
    protected $name = "Publishes Last Month";

    /**
     * @var string The description of this report
     */
    protected $description = "The number of publish, deleted, tombstoned, or embargoed events per day for the last month.";

    /**
     * @var string The type of the report data (series, text, numeric)
     */
    protected $type = "series";

    /**
     * Run report
     *
     * @param  \snac\server\database\DatabaseConnector $psql Postgres Connector
     * @return string[]       Report results
     */
    public function compute($psql) {
        $sql = "select count(*), date_trunc('day', timestamp) as date
                    from version_history
                    where
                        status in ('published', 'tombstoned', 'deleted', 'embargoed') and
                        timestamp > NOW() - INTERVAL '31 days'
                    group by date
                    order by date asc;";

        $result = $psql->query($sql, array());
        $rawResults = $psql->fetchAll($result);

        $results = array();
        for ($i = 30; $i >= 0; $i--) {
            $date = strtotime("-$i day");
            $count = 0;
            foreach ($rawResults as $row) {
                if ($row["date"] == date("Y-m-d 00:00:00", $date)) {
                    $count = $row["count"];
                }
            }
            $results[date("Y-m-d", $date)] = (int) $count;
        }


        return $results;

    }

}

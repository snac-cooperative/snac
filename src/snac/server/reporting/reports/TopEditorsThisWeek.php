<?php

/**
 * Top Editing Users Report Class File
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server\reporting\reports;

/**
 * Top Editing Users Over the Last Week
 *
 * This report computes the top editing users over the last week
 *
 * @author Robbie Hott
 */
class TopEditorsThisWeek extends helpers\Report {

    /**
     * The name of this report
     * @var string The name of this report
     */
    protected $name = "Top Editors This Week";

    /**
     * @var string The description of this report
     */
    protected $description = "The top editors over the last 7 days.";

    /**
     * @var string The type of the report data (series, text, numeric)
     */
    protected $type = "list";

    /**
     * Run report
     *
     * @param  \snac\server\database\DatabaseConnector $psql Postgres Connector
     * @return string[]       Report results
     */
    public function compute($psql) {
        $sql = "select a.fullname, b.user_id, b.count from
                    (select count(*), user_id
                        from version_history
                        where
                            status in ('needs review', 'published', 'tombstone', 'deleted', 'embargoed') and
                            timestamp > NOW() - INTERVAL '7 days' and
                            user_id > 100
                        group by user_id) b,
                    appuser a
                where b.user_id = a.id
                order by b.count desc
                limit 5;";

        $result = $psql->query($sql, array());
        $rawResults = $psql->fetchAll($result);

        $results = array();
        if ($rawResults) {
            foreach ($rawResults as $result) {
                $results[$result["fullname"]] = $result["count"];
            }
        }
        return $results;

    }

}

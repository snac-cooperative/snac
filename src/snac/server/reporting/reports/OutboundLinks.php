<?php

/**
 * Outbound Links Report
 *
 * @author Joseph Glass
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2019 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server\reporting\reports;

/**
 * Outbound Links Report
 *
 * This report provides the traffic of tracked links clicked in the past month
 *
 * @author Joseph Glass
 */
class OutboundLinks extends helpers\Report {

    /**
     * The name of this report
     * @var string The name of this report
     */
    protected $name = "Outbound Links in SNAC";

    /**
     * @var string The description of this report
     */
    protected $description = "Traffic information on outbound links to Holding Institutions.";

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
         $sql = "SELECT count(*), date_trunc('day', timestamp) AS date
                    FROM outbound_link
                    WHERE timestamp > TIMESTAMP 'yesterday' - INTERVAL '31 days'
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

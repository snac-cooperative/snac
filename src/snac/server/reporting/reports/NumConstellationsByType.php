<?php

/**
 * Number of Constellations Report Class File
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
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
class NumConstellationsByType extends helpers\Report {

    /**
     * The name of this report
     * @var string The name of this report
     */
    protected $name = "Number of Identity Constellations by Type";

    /**
     * @var string The description of this report
     */
    protected $description = "The number of Identity Constellations of each entity type.";

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
        $sql = "select v.value, count(*)
                from version_history as aa,
                    (select n.ic_id, n.entity_type, b.version from nrd n, 
                        (select max(version) as version, ic_id from nrd
                        group by ic_id) as b where n.ic_id = b.ic_id and
                        not n.is_deleted) as bb, 
                    (select max(version) as version, id from version_history
                    where status in ('published', 'tombstoned', 'deleted', 'embargoed')
                    group by id) as cc,
                    vocabulary v
                where              
                    aa.id=cc.id and
                    aa.version=cc.version and
                    bb.ic_id=cc.id and
                    bb.version<=cc.version and
                    v.id = bb.entity_type and
                    aa.status = 'published' group by v.value order by v.value desc;";

        $result = $psql->query($sql, array());
        $rawResults = $psql->fetchAll($result);

        $results = array();

        foreach ($rawResults as $result) {
            $results[$result["value"]] = $result["count"];
        }
        return $results;

    }

}

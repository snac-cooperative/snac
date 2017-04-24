<?php

/**
 * Number of Constellations Pointing to Holdings Report Class File
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server\reporting\reports;

/**
 * Number of Constellations pointing to Top holding institutions Report
 *
 * This report computes the number of constellations pointed to by the top holding institutions.
 *
 * @author Robbie Hott
 */
class TopHoldingInstitutions extends helpers\Report {

    protected $name = "Top Holding Institutions";

    protected $description = "Holding institutions that have repositories pointing to the most Identity Constellations in SNAC.";

    protected $type = "list";

    /**
     * Run report
     *
     * @param  \snac\server\database\DatabaseConnector $psql Postgres Connector
     * @return string[]       Report results
     */
    public function compute($psql) {
        $sql = "select rc.repo_ic_id, count(*)
                    from
                        (select distinct rr.ic_id, rc.repo_ic_id
                            from (select ic_id, resource_id from related_resource) rr,
                                resource_cache rc
                            where rc.id = rr.resource_id) rc
                    where rc.repo_ic_id is not null
                    group by rc.repo_ic_id
                    order by count desc limit 20;";

        $result = $psql->query($sql, array());
        $repoCounts = $psql->fetchAll($result);


        $paramNums = array();
        $values = array();
        $i = 1;
        foreach ($repoCounts as $repo) {
            array_push($paramNums, "$".$i++);
            $values[$repo["repo_ic_id"]] = $repo["count"];
        }
        $paramlist = implode(",", $paramNums);

        $sql = "select distinct on (n.ic_id) n.ic_id, n.id, n.original, n.preference_score from name n,
                    (select n.id, n.ic_id, max(n.version) as version from name n
                    left join (select id, max(version) as version from version_history where (status = 'published') group by id) vh
                    on n.ic_id = vh.id and n.version <= vh.version
                    group by n.id, n.ic_id) nh
                    where n.id = nh.id and n.version = nh.version and n.ic_id in ($paramlist)
                    order by n.ic_id asc, n.preference_score desc;";

        $result = $psql->query($sql, array_keys($values));
        $repoNames = $psql->fetchAll($result);


        $results = array();

        foreach ($repoCounts as $counter) {
            foreach ($repoNames as $name) {
                if ($name["ic_id"] == $counter["repo_ic_id"])
                    $results[$name["original"]] = $counter["count"];
            }
        }
        return $results;

    }

}

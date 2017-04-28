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
 * Number of Constellations pointing to all holding institutions Report
 *
 * This report computes the number of constellations pointed to by all holding institutions.
 *
 * @author Robbie Hott
 */
class AllHoldingInstitutions extends helpers\Report {

    /**
     * The name of this report
     * @var string The name of this report
     */
    protected $name = "Holding Institutions";

    /**
     * @var string The description of this report
     */
    protected $description = "Number of Identity Constellations connected with each Holding Institution's repository";

    /**
     * @var string The type of the report data (series, text, numeric)
     */
    protected $type = "list";

    /**
     * @var string[] The headings used in the report, if necessary
     */
    protected $headings = ["key" => "Holding Repository", "value" => "Identity Constellations"];

    /**
     * Run report
     *
     * @param  \snac\server\database\DatabaseConnector $psql Postgres Connector
     * @return string[]       Report results
     */
    public function compute($psql) {
        $sql = "select rc.repo_ic_id, cast(coalesce(count(*),0) as integer) as count
                    from
                        (select distinct rr.ic_id, rc.repo_ic_id
                            from (select ic_id, resource_id from related_resource) rr,
                                resource_cache rc
                            where rc.id = rr.resource_id) rc
                    where rc.repo_ic_id is not null
                    group by rc.repo_ic_id
                    order by count desc;";

        $result = $psql->query($sql, array());
        $repoCounts = $psql->fetchAll($result);

        $sql = "
                select n.id, n.original, n.preference_score from name n,
                (select id, version from version_history where status = 'published' and id = $1 order by version desc limit 1) vh
                where vh.id = n.ic_id and n.version <= vh.version
                order by n.preference_score desc
                limit 1;";
        $psql->prepare("getName", $sql);

        $results = array();
        foreach ($repoCounts as $repo) {
            $result = $psql->execute("getName", array($repo["repo_ic_id"]));
            $name = $psql->fetchRow($result);
            $results[$name["original"] . " (".$repo["repo_ic_id"].")"] = $repo["count"];
        }
        return $results;
    }
}


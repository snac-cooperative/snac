<?php

/**
 * Number of Holdings at each Lat/Lon
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server\reporting\reports;

/**
 * Number of Resources in the cache at each location
 *
 * This report computes the number of resources (holdings) in the entirety of snac
 * at each lat/lon.
 *
 * @author Robbie Hott
 */
class AllHoldingLocations extends helpers\Report {

    /**
     * The name of this report
     * @var string The name of this report
     */
    protected $name = "Holding Locations";

    /**
     * @var string The description of this report
     */
    protected $description = "Number of Resources in the cache for each latitude and longitude";

    /**
     * @var string The type of the report data (series, text, numeric)
     */
    protected $type = "list";

    /**
     * @var string[] The headings used in the report, if necessary
     */
    protected $headings = ["latitude" => "Latitude", 
        "longitude" => "Longitude",
        "count" => "Count of Resources"];

    /**
     * Run report
     *
     * @param  \snac\server\database\DatabaseConnector $psql Postgres Connector
     * @return string[]       Report results
     */
    public function compute($psql) {
        $sql = "select p.latitude, p.longitude, cast(coalesce(count(*), 0) as integer) as count 
            from resource_cache r, place_link pl, geo_place p 
            where r.repo_ic_id = pl.ic_id 
                and pl.geo_place_id = p.id 
            group by p.latitude, p.longitude;";

        $result = $psql->query($sql, array());
        $list = $psql->fetchAll($result);
        return $list;
    }
}



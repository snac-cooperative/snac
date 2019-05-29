<?php
/**
 * Report Interface File
 *
 * Interface file for all reports
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2017 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server\reporting\reports\helpers;

/**
 * Report Abstract Class
 *
 * This interface defines the report class structure to run over the snac
 * database.  The reporting engine will instantiate and call the reporting
 * methods of the report classes, then aggregate their results to the caller.
 *
 * @author Robbie Hott
 */
abstract class Report {

    /**
     * The name of this report
     * @var string The name of this report
     */
    protected $name;

    /**
     * @var string The description of this report
     */
    protected $description;

    /**
     * @var string The type of the report data (series, text, numeric)
     */
    protected $type;

    /**
     * @var string[] The headings used in the report, if necessary
     */
    protected $headings;

    /**
     * Performs the report, using the given connection object to interact with
     * the database(s) and returns the results of the report.  The results are
     * always returned as an associative array.
     *
     * @param \snac\server\database\DatabaseConnector $psql Handle to the SNAC postgres connector
     * @return string[] Associative array of reporting data
     */
    public abstract function compute($psql);

    /**
     * Returns the name of this report
     *
     * @return string   The name of this report
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns the type of this report
     *
     * @return string The type of this report
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Returns the description of this report
     *
     * @return string The description of this report
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Returns the headings of this report
     *
     * @return string[] The headings of this report
     */
    public function getHeadings() {
        return $this->headings;
    }


}

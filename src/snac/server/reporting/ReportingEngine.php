<?php
/**
 * Reporting Engine Class File
 *
 * Contains the reporting engine class
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2017 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server\reporting;

/**
 * Reporting Engine
 *
 * This class serves as the heart of the reporting engine. It provides methods for running reports
 * and getting their data.
 *
 * @author Robbie Hott
 *
 */
class ReportingEngine {

    /**
     * @var \Monolog\Logger $logger the logger for this server
     */
    private $logger;

    /**
     * Reports to run
     * @var \snac\server\reporting\reports\helpers\Report[] Reports to run
     */
    private $reports;

    /**
     * Connector to the PostgresDB
     * @var \snac\server\database\DatabaseConnector The connector to the postgres database
     */
    private $postgres;

    /**
     * Constructor
     */
    public function __construct() {
        global $log;
        // create a log channel
        $this->logger = new \Monolog\Logger('ReportingEngine');
        $this->logger->pushHandler($log);

        $this->reports = array();
        return;
    }

    /**
     * Destructor
     */
    public function __destruct() {
        return;
    }

    /**
     * Set Postgres Connector
     *
     * A method to pass a postgres connector to the reporting engine for use in
     * analyzing the data.
     *
     * @param \snac\server\database\DatabaseConnector The connector to the postgres database
     */
    public function setPostgresConnector($connector) {
        $this->postgres = $connector;
    }

    /**
     * Add report
     *
     * Adds a report to the list of stages to run
     *
     * @param string $report name of the report to include
     */
    public function addReport($report) {
        // Load the class as a reflection
        $class = new \ReflectionClass("\\snac\\server\\reporting\\reports\\".$report);

        // If only one argument, then create with no params
        array_push($this->reports, $class->newInstance());

    }

    /**
     * Run the reports
     *
     * Run the reports and return the results of all that succeeded in
     * running as an associatve array by report name.
     *
     * @return string[] Associative array of report results for all reports run
     */
    public function runReports() {
        $results = array();

        foreach ($this->reports as $report) {
            $result = null;
            try {
                $this->logger->addDebug("Running report: ", array($report->getName()));
                $result = $report->compute($this->postgres);
                $this->logger->addDebug("Finished report: ", array($report->getName()));
            } catch (\Exception $e) {
                // Right now, ignore any errors
            }
            if ($result !== null) {
                $results[$report->getName()] = array(
                    "title" => $report->getName(),
                    "type" => $report->getType(),
                    "description" => $report->getDescription(),
                    "result" => $result);
                if ($report->getHeadings() !== null)
                    $results[$report->getName()]["headings"] = $report->getHeadings();
            }
        }

        return $results;
    }



}

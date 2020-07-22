<?php

/**
 * Base Exception Class
 *
 * The master exception. All SNAC Exceptions should extend this class
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\exceptions;

/**
 * SNACException Class
 *
 * Base class for exceptions thrown by SNAC. All exceptions should extend this class,
 * overwriting the $type variable to give more information about the type of exception
 * that is being thrown.
 *
 * @author Robbie Hott
 *
 */
class SNACException extends \Exception {

    /**
     * Type of the exception being thrown
     *
     * @var string
     */
    protected $type = "Unknown";

    /**
     * Returns this exception as a JSON-encoded error message for printing by the server.
     *
     * {@inheritDoc}
     *
     * @see Exception::__toString()
     */
    public function __toString() {

        $json = [
            "error" => [
                "type" => $this->type,
                "message" => $this->message
            ], "timing" => round((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) * 1000, 2)
        ];

        return json_encode($json, JSON_PRETTY_PRINT);
    }
}

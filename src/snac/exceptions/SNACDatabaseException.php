<?php

/**
 * Database Exception Class
 *
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
 * SNACDatabaseException Class
 *
 * Exception for handling errors with SNAC back-end database connection.
 *
 * @author Robbie Hott
 *
 */
class SNACDatabaseException extends SNACException {

    /**
     * Type of the exception being thrown
     *
     * @var string
     */
    protected $type = "Data Storage Error";
}

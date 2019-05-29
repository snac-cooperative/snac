<?php

/**
 * Validation Exception Class
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
 * SNACValidationException Class
 *
 * Exception for handling errors with validating Identity Constellations
 *
 * @author Robbie Hott
 *
 */
class SNACValidationException extends SNACException {

    /**
     * Type of the exception being thrown
     *
     * @var string
     */
    protected $type = "Constellation Validation Error";
}

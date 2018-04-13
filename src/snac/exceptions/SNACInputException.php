<?php

/**
 * Input Exception Class
 *
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\exceptions;

/**
 * SNACInputException Class
 *
 * Exception for handling errors with user input to SNAC.
 *
 * @author Robbie Hott
 *
 */
class SNACInputException extends SNACException {

    /**
     * Type of the exception being thrown
     *
     * @var string
     */
    protected $type = "Input Error";
}

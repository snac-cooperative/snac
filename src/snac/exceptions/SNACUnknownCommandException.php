<?php

/**
 * Unknown Command Exception Class
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
 * SNACUnknownCommandException Class
 *
 * Exception for handling errors with SNAC Server's unknown commands.
 *
 * @author Robbie Hott
 *
 */
class SNACUnknownCommandException extends SNACException {

    /**
     * Type of the exception being thrown
     *
     * @var string
     */
    protected $type = "Unknown Command Error";
}

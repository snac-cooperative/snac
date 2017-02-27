<?php

/**
 * Concurrent Edit Exception Class
*
* @author Robbie Hott
* @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
* @copyright 2015 the Rector and Visitors of the University of Virginia, and
*            the Regents of the University of California
*/
namespace snac\exceptions;

/**
 * SNACConcurrentEditException Class
 *
 * Exception for handling concurrent edit errors with SNAC users.
 *
 * @author Robbie Hott
 *
 */
class SNACConcurrentEditException extends SNACException {

    /**
     * Type of the exception being thrown
     *
     * @var string
     */
    protected $type = "Concurrent Edit Error";
}

<?php

/**
 * User Exception Class
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
 * SNACUserException Class
 *
 * Exception for handling errors with SNAC Server's users.
 *
 * @author Robbie Hott
 *
 */
class SNACUserException extends SNACException {

    /**
     * Type of the exception being thrown
     *
     * @var string
     */
    protected $type = "User Error";
}

<?php

/**
 * Permission Exception Class
*
* @author Robbie Hott
* @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
* @copyright 2015 the Rector and Visitors of the University of Virginia, and
*            the Regents of the University of California
*/
namespace snac\exceptions;

/**
 * SNACPermissionException Class
 *
 * Exception for handling permission errors with SNAC users.
 *
 * @author Robbie Hott
 *
 */
class SNACPermissionException extends SNACException {

    /**
     * Type of the exception being thrown
     *
     * @var string
     */
    protected $type = "Permission Error";
}

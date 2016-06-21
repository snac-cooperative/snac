<?php

/**
 * Groups for users
 *
 * Groups exist for the workflow to be able to send work or notifications to a group, with the assumption that
 * one member of that group will take the work.
 *
 * License:
 *
 * @author Tom Laudeman
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2016 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\data;

/**
 * Group class
 *
 * Users are in groups. This is a group object. Properties and methods are identical to class Privilege.
 *
 * @author Tom Laudeman
 *        
 */
class Group extends Privilege {

    /**
     * Constructor
     *
     * @param string[][] $data The data for this object in an associative array
     */ 
    public function __construct($data=null)
    {
        parent::__construct($data);
        $this->dataType = 'group';
    }


}
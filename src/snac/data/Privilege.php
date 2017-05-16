<?php

/**
 * Privileges for authorization
 *
 * User's are authorized for system functions based on having privileges. Privileges are grouped inside Roles.
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
 * Privilege class
 *
 * These are the fundamental Privileges. A list of these is in class Role.
 *
 * @author Tom Laudeman
 *        
 */
class Privilege extends AbstractGrouping {

    /**
     * Constructor
     *
     * @param string[] $data optional An associative array representation of this object to create
     */ 
    public function __construct($data=null)
    {
        parent::__construct($data);
        $this->dataType = 'privilege';
    }

}

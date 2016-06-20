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
 * The same fields as Role, except that $privilegeList is ignored. 
 * 
 *
 * @author Tom Laudeman
 *        
 */
class Privilege extends Role {
    /*
     * Nothing here. Just like Role, but used for Privileges.
     *
     * Being a Privilege, it doesn't use the $privilegeList.
     */ 
}
<?php

/**
 * Role for user authorization
 *
 * User's are authorized for system functions based on being members of some role.
 *
 * License:
 *
 *
 * @author Tom Laudeman
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\data;

/**
 * Role class
 *
 * Storage class user role. Each role has. A user has a list of roles which define what functions
 * the user is authorized to perform
 *
 * @author Tom Laudeman
 *        
 */
class Role {

    /**
     * Role id
     *
     * @var integer Role id from sql role.id record id
     */
    private $id;

    /**
     * Role label
     *
     * Short label that identifies this role
     *
     * @var string Role label 
     */
    private $label;

    /**
     * Role description
     *
     * Description of what this role authorizes, and the purpose. One sentence or phrase.
     *
     * @var string Description of this role.
     */
    private $description;

    /**
     * List of all privileges in this role
     *
     * @var \snac\data\Role[] List of Role objects which have mostly the same fields as Privileges would have.
     *
     */
    private $privilegeList = null;

    /**
     * Constructor
     *
     * @param string $label optional Label string
     *
     * @param string $description optional Description string
     */ 
    public function __construct($label=null, $description=null)
    {
        if ($label)
        {
            $this->setLabel($label);
        }
        if ($description)
        {
            $this->setDescription($description);
        }
        $this->privilegeList = array();
    }

    /**
     * Add a new privilege
     *
     * @param \snac\data\Role A privilege which uses the same class as Role.
     */
    public function addPrivilege($privilege)
    {
        array_push($this->privilegeList, $privilege);
    }

    /**
     * Remove a privilege
     *
     * Might be able to use a list slice or something, but this simple algo gets the job done.
     * 
     * @param \snac\data\Privilege $privilege Remove this privilege. 
     */ 
    public function removePrivilege($privilege)
    {
        $removeID = $privilege->getID();
        $oldList = $this->privilegeList;
        $this->privilegeList = array();
        foreach($oldList as $priv)
        {
            if ($priv->getID() != $removeID)
            {
                $this->addPrivilege($priv);
            }
        }
    }

    /**
     * Return the privilege list
     * 
     * @return \snac\data\Role[] The list of privilege objects, which are Role objects. We could expand
     * AbstractTermData to include Role and Privilege.
     */
    public function getPrivilegeList()
    {
        return $this->privilegeList;
    }

    /**
     * Get role id
     * @return integer Role id
     */ 
    public function getID()
    {
        return $this->id;
    }

    /**
     * Set role id
     * @param integer $id
     */ 
    public function setID($id)
    {
        $this->id = $id;
    }

    /**
     * Get role label
     * @return string Role label
     */ 
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set role label
     * @param string $label
     */ 
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * Get role description
     * @return string Role description
     */ 
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set role description
     * @param string $description
     */ 
    public function setDescription($description)
    {
        $this->description = $description;
    }


}
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
 * Storage class user role. In addition to label and description, each role has a list of privileges. A user
 * has a list of roles which in turn define what functions the user is authorized to perform
 *
 * @author Tom Laudeman
 *        
 */
class Role extends Privilege {

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
     * @param string[][] $data The data for this object in an associative array
     */ 
    public function __construct($data=null)
    {
        parent::__construct($data);
        if ($data != null && is_array($data))
            $this->fromArray($data);
        $this->dataType = 'role';
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
     * Required method to convert this term structure to an array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This object as an associative array
     */
    public function toArray($shorten = true) {

        $return['privilegeList'] = array();
        if (isset($this->privilegeList) && $this->privilegeList != null) {
            foreach ($this->privilegeList as $i => $v)
            {
                $return["privilegeList"][$i] = $v->toArray($shorten);
            }
        }

        $return = array_merge($return, parent::toArray($shorten));
        
        // Shorten if necessary
        if ($shorten) {
            $return2 = array();
            foreach ($return as $i => $v)
                if ($v != null && !empty($v))
                    $return2[$i] = $v;
            unset($return);
            $return = $return2;
        }

        return $return; 
    }

    /**
     * Required method to import an array into this data object
     *
     * @param string[][] $data The data for this object in an associative array
     */
    public function fromArray($data) {
            
        if (!isset($data["dataType"]) || $data["dataType"] != $this->dataType)
            return false;

            
        parent::fromArray($data);

        unset($this->privilegeList);
        $this->privilegeList = array();
        if (isset($data["privilegeList"])) {
            foreach ($data["privilegeList"] as $i => $entry)
                if ($entry != null)
                    $this->privilegeList[$i] = new Privilege($entry);


        }
        // Note: inheriting classes should set the maxDateCount appropriately
        // based on the definition of that class.
    }

}
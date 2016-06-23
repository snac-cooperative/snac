<?php

/**
 * Role, Privilege, Group
 *
 * User's are authorized for system functions based on having privileges. Privileges are grouped inside Roles.
 *
 * Groups are simply groups of users to simplify workflow.
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
 * Abstract class for various grouping of user meta data
 *
 * Fundamental class for privilege, group, role. Role has a list of privileges.
 *
 * @author Tom Laudeman
 *        
 */
class AbstractGrouping implements \Serializable {

    /**
     * @var string $dataType The data type of this object.
     * 
     * This should be overwritten by any inheriting/child class
     */
    protected $dataType;

    /**
     * Object id
     *
     * @var integer Object id from sql object.id record id
     */
    private $id;

    /**
     * Object label
     *
     * Short label that identifies this object
     *
     * @var string Object label 
     */
    private $label;

    /**
     * Object description
     *
     * Description of what this object authorizes, and the purpose. One sentence or phrase.
     *
     * @var string Description of this object.
     */
    private $description;
    
    /**
     * Constructor
     *
     * @param string $label optional Label string
     *
     * @param string $description optional Description string
     */ 
    public function __construct($data=null)
    {
        if ($data != null && is_array($data))
            $this->fromArray($data);
    }

    /**
     * Get id
     * @return integer id
     */ 
    public function getID()
    {
        return $this->id;
    }

    /**
     * Set id
     * @param integer $id
     */ 
    public function setID($id)
    {
        $this->id = $id;
    }

    /**
     * Get label
     * @return string label
     */ 
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set label
     * @param string $label
     */ 
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * Get description
     * @return string description
     */ 
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set description
     * @param string $description
     */ 
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Required method to convert this term structure to an array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This object as an associative array
     */
    public function toArray($shorten = true) {
        $return = array(
            'dataType' => $this->dataType,
            'id' => $this->getID(),
            'label' => $this->getLabel(),
            'description' => $this->getDescription()
        );
        
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
        
        unset($this->id);
        if (isset($data["id"]))
            $this->id = $data["id"];
        else
            $this->id = null;
        
        unset($this->label);
        if (isset($data["label"]))
            $this->label = $data["label"];
        else
            $this->label = null;
        
        unset($this->description);
        if (isset($data["description"]))
            $this->description = $data["description"];
        else
            $this->description = null;
        
        // Note: inheriting classes should set the maxDateCount appropriately
        // based on the definition of that class.
    }

    /**
     * Convert this object to JSON
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string JSON encoding of this object
     */
    public function toJSON($shorten = true) {
        return json_encode($this->toArray($shorten), JSON_PRETTY_PRINT);
    } 

    /**
     * Prepopulate this object from the given JSON
     *
     * @param string $json JSON encoding of this object
     * @return boolean true on success, false on failure
     */
    public function fromJSON($json) {
        $data = json_decode($json, true);
        $return = $this->fromArray($data);
        unset($data);
        return $return;
    } 

    /**
     * Serialization Method
     *
     * Allows PHP's serialize() method to correctly serialize the object.
     *
     * {@inheritDoc}
     * 
     * @return string Serialized form of this object
     */ 
    public function serialize() {
        return $this->toJSON();
    }

    /**
     * Un-Serialization Method
     *
     * Allows PHP's unserialize() method to correctly unserialize the object.
     *
     * {@inheritDoc}
     * 
     * @param string $data Serialized version of this object
     */ 
    public function unserialize($data) {
        $this->fromJSON($data);
    }    

}
<?php
/**
 * Resource Relation Origination Name File
 *
 * Contains the data class for the originators (creators) of resource relations
 * 
 * License:
 *
 *
 * @author Tom Laudeman
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\data;

/**
 * Resource Relation Origination Name 
 *
 * Stores the name (string) and eventually the ic_id of the creator
 * 
 * @author Tom Laudeman
 * @author Robbie Hott
 *
 */
class RROriginationName extends AbstractData {

    /**
     * @var string Name of the originator (creator).
     * 
     * A simple string.
     */
    private $name = null;
    
    /**
     * Constructor
     * 
     * @param string[] $data optional An array of data to pre-fill this object
     */
    public function __construct($data = null) {
        $this->setMaxDateCount(0);
        parent::__construct($data);
    }


    /**
     * Get the name
     *
     * @return string Name of the originator (creator)
     *
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name
     *
     * @param string $name Name of the originator (creator)
     *
     */
    public function setName($name)
    {
        $this->name = $name;
    }


    /**
     * Returns this object's data as an associative array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = true) {
        $return = array(
            "dataType" => "RROriginationName",
            "name" => $this->name
        );
            
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
     * Replaces this object's data with the given associative array
     *
     * @param string[][] $data This objects data in array form
     * @return boolean true on success, false on failure
     */
    public function fromArray($data) {
        if (!isset($data["dataType"]) || $data["dataType"] != "RROriginationName")
            return false;

        parent::fromArray($data);

        if (isset($data["name"]))
            $this->name = $data["name"];
        else
            $this->name = null;

        return true;
    }

    /**
     *
     * {@inheritDoc}
     *
     * @param \snac\data\RROriginationName $other Other object
     * @param boolean $strict optional Whether or not to check id, version, and operation
     * @return boolean true on equality, false otherwise
     *       
     * @see \snac\data\AbstractData::equals()
     */
    public function equals($other, $strict = true) {

        if ($other == null || !($other instanceof \snac\data\RROriginationName))
            return false;
        
        if (!parent::equals($other, $strict))
            return false;
        
        if ($this->getName() != $other->getName())
            return false;
        
        return true;
    }
}

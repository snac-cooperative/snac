<?php

/**
 * NameComponent Class file
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\data;

/**
 * Name Component Class
 *
 * This class contains the components of a NameEntry.
 *
 * @author Robbie Hott
 */
abstract class NameComponent extends AbstractData {

    /**
     * string $text The text of the name component
     */
    private $text = null;

    /**
     * \snac\data\Term $type The name part associated with this name component
     */
    private $type = null;

    /**
     * int $order The ordering of this component in the name entry
     */
    private $order = 0;


    /**
     * Constructor.
     *
     * @param string[] $data A list of data suitable for fromArray(). This exists for use by internal code to
     * send objects around the system, not for generally creating a new object.
     * 
     */
    public function __construct($data = null) {
        $this->setMaxDateCount(0);
        parent::__construct($data);
    }


    /**
     * Get Component Text
     *
     * Get the text of this name component.
     *
     * @return string The text of the name component
     */
    public function getText() {
        return $this->text;
    }

    /**
     * Get Component Type
     *
     * Get what part of the name Entry this component describes.
     *
     * @return \snac\data\Term They part this component describes
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Get Component Ordering
     *
     * Get the order within the name Entry that this component was entered.
     *
     * @return int ordering
     */
    public function getOrder() {
        return $this->order;
    }

    /**
     * Set Component Text
     *
     * Set the text of this component.
     *
     * @param string $text Text to use for this component.
     */
    public function setText($text) {
        $this->text = $text;
    }

    /**
     * Set Component Type
     *
     * Set the part of the name entry that this component describes
     *
     * @param \snac\data\Term $type The part of the name entry
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * Set Component Order
     *
     * Set the order of this component within the name entry.  This is an integer index.
     *
     * @param int $i The index of the order
     */
    public function setOrder($i) {
        $this->order = $i;
    }

    /**
     * Returns this object's data as an associative array. 
     * 
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = true) {
        $return = array(
            "dataType" => "NameComponent",
            "text" => $this->text,
            "order" => $this->order,
            "type" => $this->type == null ? null : $this->type->toArray($shorten),
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
        if (!isset($data["dataType"]) || $data["dataType"] != "NameComponent")
            return false;
       
        parent::fromArray($data);
            
        if (isset($data["text"]))
            $this->text = $data["text"];
        else
            $this->text = null;

        if (isset($data["order"]))
            $this->order = $data["order"];
        else
            $this->order = null;
        
                
        if (isset($data["type"]) && $data["type"] != null) 
            $this->type = new Term($data["type"]);
        else
            $this->type = null;

        return true;
    }


    /**
     *
     * {@inheritDoc}
     *
     * @param \snac\data\NameEntry $other Other object
     * @param boolean $strict optional Whether or not to check id, version, and operation
     * @return boolean true on equality, false otherwise
     *       
     * @see \snac\data\AbstractData::equals()
     */
    public function equals($other, $strict = true) {

        if ($other == null || ! ($other instanceof \snac\data\NameComponent))
            return false;
        
        if (! parent::equals($other, $strict))
            return false;
        
        if ($this->getText() != $other->getText())
            return false;
        if ($this->getOrder() != $other->getOrder())
            return false;
        
        if (($this->getType() != null && ! $this->getType()->equals($other->getType(), $strict)) ||
                 ($this->getType() == null && $other->getType() != null))
            return false;
        return true;
    }
}

<?php

/**
 * AbstractOrderedComponent Class file
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
 * Abstract Ordered Component Class
 *
 * This class contains an abstract implementation of an ordered (term, text) component
 *
 * @author Robbie Hott
 */
abstract class AbstractOrderedComponent extends AbstractData {

    /**
     * @var string $dataType The data type of this object.
     * 
     * This should be overwritten by any inheriting/child class
     */
    protected $dataType;

    /**
     * string $text The text of the component
     */
    private $text = null;

    /**
     * \snac\data\Term $type The term associated with this component
     */
    private $type = null;

    /**
     * int $order The ordering of this component in the list
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
        parent::__construct($data);
    }


    /**
     * Get the type of this object
     *
     * @return string The type of this object
     *
     */
    public function getDataType()
    {
        return $this->dataType;
    }
    
    /**
     * Set the data type for this object
     *
     * @param string $dataType the data type for this object
     */
    protected function setDataType($dataType) {
        $this->dataType = $dataType;
    }

    /**
     * Get Component Text
     *
     * Get the text of this component.
     *
     * @return string The text of the component
     */
    public function getText() {
        return $this->text;
    }

    /**
     * Get Component Type
     *
     * Get type of this component, i.e. what it part it is 
     *
     * @return \snac\data\Term The part this component describes
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Get Component Ordering
     *
     * Get the order within the list that this component was entered.
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
     * Set the type of component 
     *
     * @param \snac\data\Term $type The type of component 
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * Set Component Order
     *
     * Set the order of this component within the list.  This is an integer index.
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
            "dataType" => $this->dataType,
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
        if (!isset($data["dataType"]) || $data["dataType"] != $this->dataType)
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
     * @param \snac\data\AbstractOrderedComponent $other Other object
     * @param boolean $strict optional Whether or not to check id, version, and operation
     * @return boolean true on equality, false otherwise
     *       
     * @see \snac\data\AbstractData::equals()
     */
    public function equals($other, $strict = true) {

        if ($other == null || !($other instanceof \snac\data\AbstractOrderedComponent))
            return false;
        
        if ($other->getDataType() != $this->getDataType())
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


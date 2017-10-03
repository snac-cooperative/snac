<?php

/**
 * Abstract Class that contains methods to handle controlled vocab-containing
 * objects.  For example, Gender, OtherRecordIDs, etc, which must be versioned
 * but also contain links to controlled vocabularies.
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
 * Term-holding abstract class
 *
 * Abstract class that extends AbstractData and also hold a Term object.  Extend this to use it.
 *
 * @author Robbie Hott
 */
abstract class AbstractTermData extends AbstractData {

    /**
     * @var string $dataType The data type of this object.
     * 
     * This should be overwritten by any inheriting/child class
     */
    protected $dataType;

    /**
     * @var \snac\data\Term $term The term for this object 
     */
    protected $term;

    
    /**
     * Constructor
     * 
     * @param string[] $data optional Array with data to build this object
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
     * Get the term of this object
     *
     *  @return \snac\data\Term term of this object
     */
    public function getTerm() {
        return $this->term;
    }

    /**
     * Set the term of this object
     *
     * @param \snac\data\Term $term Term for this object
     */
    public function setTerm($term) {
        $this->term = $term;
    }

    public function toString() {
        return $this->dataType . ": " . $this->getTerm()->getTerm();
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
            'term' => $this->getTerm() == null ? null : $this->getTerm()->toArray($shorten)
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
     * Required method to import an array into this term structure.
     *
     * @param string[][] $data The data for this object in an associative array
     */
    public function fromArray($data) {

        if (!isset($data["dataType"]) || $data["dataType"] != $this->dataType)
            return false;

            
        parent::fromArray($data);
        
        unset($this->term);
        if (isset($data["term"]))
            $this->term = new \snac\data\Term($data["term"]);
        else
            $this->term = null;
    }
    
    /**
     * {@inheritDoc}
     * 
     * @param \snac\data\AbstractTermData $other Other object
     * @param boolean $strict optional Whether or not to check id, version, and operation
     * @return boolean true on equality, false otherwise
     * @see \snac\data\AbstractData::equals()
     */
    public function equals($other, $strict = true) {


        if ($other == null || !($other instanceof \snac\data\AbstractTermData))
            return false;

        
        if ($other->getDataType() != $this->getDataType())
            return false;
        
        if (!parent::equals($other, $strict))
            return false;
        
        if (($this->getTerm() != null && !$this->getTerm()->equals($other->getTerm())) ||
                ($this->getTerm() == null && $other->getTerm() != null))
            return false;
        
        return true;
    }

}

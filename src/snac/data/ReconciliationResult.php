<?php

/**
 * Reconciliation Result class.
 *
 * Contains the data class for the reconciliation results.
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
 * Reconciliation Result class
 *
 * This class holds the data associated with a reconcilation result.  That includes
 * the score for that result, the identity associated with that result, and any
 * additional information related to the process of obtaining the results.
 *
 * @author Robbie Hott
 */
class ReconciliationResult extends AbstractData {


    private $strength = 0;
    private $identity;
    private $properties;
    private $vector;



    /**
     * Constructor
     *
     * @param string[][] $data optional Associative array of data to fill this
     *                                  object with.
     */
    public function __construct($data = null) {
        $this->properties = array();
        $this->vector = array();
        if ($data != null)
            parent::__construct($data);
    }

    public function setStrength($strength) {
        $this->strength = $strength;
    }

    public function getStrength() {
        return $this->strength;
    }

    public function setIdentity($id) {
        $this->identity = $id;
    }

    public function getIdentity() {
        return $this->identity;
    }

    public function setProperty($name, $value) {
        $this->properties[$name] = $value;
    }

    public function getProperty($name) {
        if (isset($this->properties[$name]))
            return $this->properties[$name];
        return null;
    }

    public function setScore($test, $score) {
        $this->vector[$test] = $score;
    }

    public function getScore($test) {
        if (isset($this->vector[$test]))
            return $this->vector[$test];
        return 0;
    }

    public function getVector() {
        return $this->vector;
    }

    public function setMultipleProperties($properties) {
        $this->properties = array_merge($this->properties, $properties);
    }

    public function getAllProperties($properties) {
        return $this->properties;
    }
    
    /**
     * Required method to convert this data object to an array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This object as an associative array
     */
    public function toArray($shorten = true) {
        $array = array();
        $array["strength"] = $this->strength;
        if ($this->identity != null)
            $array["identity"] = $this->identity->toArray();
        else if (!$shorten) 
            $array["identity"] = null;
        if ($this->vector != null || !$shorten)
            $array["vector"] = $this->vector;
        if ($this->properties != null || !$shorten)
        $array["properties"] = $this->properties;
        return $array;
    }

    /**
     * Required method to import an array into this data object
     *
     * @param string[][] $data The data for this object in an associative array
     */
    public function fromArray($data) {
        $this->strength = $data["strength"];
        $this->identity = new \snac\data\Constellation($data["identity"]);
        $this->vector = $data["vector"];
        $this->properties = $data["properties"];
    }
    

}

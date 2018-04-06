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


    /**
     * @var float strength of this result
     */
    private $strength = 0;
    /**
     * @var \snac\data\Constellation Identity for this result
     */
    private $identity;
    /**
     * @var string[] List of properties for this result
     */
    private $properties;
    /**
     * @var float[] Score vector for this result
     */
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

    /**
     * Set overall strength for this result
     * @param float $strength strength
     */
    public function setStrength($strength) {
        $this->strength = $strength;
    }

    /**
     * Get overall strength for this result
     * @return float strength
     */
    public function getStrength() {
        return $this->strength;
    }

    /**
     * Set the identity constellation for this result
     * @param \snac\data\Constellation $id Identity constellation
     */
    public function setIdentity($id) {
        $this->identity = $id;
    }

    /**
     * Get the constellation associated with this result
     * @return \snac\data\Constellation Identity constellation
     */
    public function getIdentity() {
        return $this->identity;
    }

    /**
     * Set a property value for this result
     * @param string $name name of the property to set
     * @param mixed $value value of the property
     */
    public function setProperty($name, $value) {
        $this->properties[$name] = $value;
    }

    /**
     * Get a property for this result
     * @param string $name Property name
     * @return mixed|NULL Value of the property or null if not found
     */
    public function getProperty($name) {
        if (isset($this->properties[$name]))
            return $this->properties[$name];
        return null;
    }

    /**
     * Set the score for one test
     * @param string $test test name
     * @param float $score score
     */
    public function setScore($test, $score) {
        $this->vector[$test] = $score;
    }

    /**
     * Get the score for one test
     *
     * @param string $test Test to check
     * @return float The score, or 0 if not run
     */
    public function getScore($test) {
        if (isset($this->vector[$test]))
            return $this->vector[$test];
        return 0;
    }

    /**
     * Get the score vector for this result
     * @return string[] score vector
     */
    public function getVector() {
        return $this->vector;
    }

    /**
     * Set multiple properties for this result
     * @param string[][] $properties List of properties
     *
     */
    public function setMultipleProperties($properties) {
        $this->properties = array_merge($this->properties, $properties);
    }

    /**
     * Get all properties for this result
     *
     * @return string[][] List of properties
     */
    public function getAllProperties() {
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

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
abstract class ReconciliationResult extends AbstractData {


    private float $strength = 0;
    private \snac\data\Constellation $identity;
    private $properties;
    private $vector;



    /**
     * Constructor
     *
     * @param string[][] $data optional Associative array of data to fill this
     *                                  object with.
     */
    public function __construct($data = null) {
        $this->properties = new array();
        $this->vector = new array();
        if ($data != null)
            parent::__construct($data);
    }

    public function setStrength(float $strength) {
        $this->strength = $strength;
    }

    public function getStrength() {
        return $this->strength;
    }

    public function setIdentity(\snac\data\Constellation $id) {
        $this->identity = $id;
    }

    public function getIdentity() {
        return $this->identity;
    }

    public function setProperty(string $name, $value) {
        $this->properties[$name] = $value;
    }

    public function getProperty(string $name) {
        if (isset($this->properties[$name]))
            return $this->properties[$name];
        return null;
    }

    public function addScore(string $test, float $score) {
        $this->vector[$test] = $score;
    }

    public function getScore(string $test) {
        if (isset($this->vector[$test]))
            return $this->vector[$test];
        return 0;
    }

    public function getVector() {
        return $this->vector;
    }

    
    /**
     * Required method to convert this data object to an array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This object as an associative array
     */
    public abstract function toArray($shorten = true) {
        ;
    }

    /**
     * Required method to import an array into this data object
     *
     * @param string[][] $data The data for this object in an associative array
     */
    public abstract function fromArray($data) {
        ;
    }
    

}

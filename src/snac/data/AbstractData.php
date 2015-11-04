<?php

/**
 * Abstract data object class.
 *
 * Contains the data class for the resource relations.
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
 * Abstract data class
 *
 * This class provides methods to read, construct, and export to JSON.  It also
 * requires inheriting classes to include toArray and fromArray functions that
 * will convert the data object to and from associative arrays.  It provides a
 * default constructor that may take an array as a parameter to fill the object.
 *
 * @author Robbie Hott
 */
abstract class AbstractData {

    /**
     * Constructor
     *
     * @param string[][] $data optional Associative array of data to fill this
     *                                  object with.
     */
    public function __construct($data = null) {
        if ($data != null && is_array($data))
            $this->fromArray($data);
    }

    
    /**
     * Required method to convert this data object to an array
     *
     * @return string[][] This object as an associative array
     */
    public abstract function toArray();

    /**
     * Required method to import an array into this data object
     *
     * @param string[][] $data The data for this object in an associative array
     */
    public abstract function fromArray($data);

    /**
     * Convert this object to JSON
     *
     * @return string JSON encoding of this object
     */
    public function toJSON() {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
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
    

}

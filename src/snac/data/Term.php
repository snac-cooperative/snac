<?php

/**
 * Term Class that holds simple database terms.
 *
 * Holds the information for an individual term in a controlled vocabulary. 
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @author Tom Laudeman
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\data;

/**
 * Vocabulary term data class
 *
 * This class contains the data associated with one controlled vocabulary term's data.  At first, this
 * includes database IDs and vocabulary terms.
 *
 * @author Robbie Hott
 * @author Tom Laudeman
 */
abstract class Term {


    /**
     * var int $id The canonical vocabulary ID for this term
     */
    protected $id;

    /**
     * var int $term The term (in any language) for this particular vocabulary term
     */
    protected $term;
    
    /**
     * Constructor
     *
     * The associative array $data varies depending on the object being created, but is always consistent
     * between toArray() and fromArray() for each object. By and large, outside an object, nothing cares about
     * the internal structure of the $data array. The standard way to create one of these objects is to
     * instantiate with no $data, and then use the getters to set the object's properties.
     *
     * @param string[][] $data optional Associative array of data to fill this
     *                                  object with.
     */
    public function __construct($data = null) {
        if ($data != null && is_array($data))
            $this->fromArray($data);
    }

    /**
     * Get the ID of this vocab term
     *
     *  @return int ID of this vocab term
     */
    public function getID() {
        return $this->id;
    }

    /**
     * Set the ID of this vocab term
     *
     * @param int $id ID to assign this vocab term
     */
    public function setID($id) {
        $this->id = $id;
    }
    
    /**
     * Get the term of this vocab term
     *
     *  @return string term of this vocab term
     */
    public function getTerm() {
        return $this->term;
    }

    /**
     * Set the term of this vocab term
     *
     * @param string $term Term for this vocab term
     */
    public function setVersion($version) {
        $this->version = $version;
    }
    
    /**
     * Required method to convert this term structure to an array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This object as an associative array
     */
    public function toArray($shorten = true) {
        $return = array(
            'id' => $this->getID(),
            'term' => $this->getTerm()
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
     * Required method to import an array into this term structure
     *
     * @param string[][] $data The data for this object in an associative array
     */
    public function fromArray($data) {
            
        unset($this->id);
        if (isset($data["id"]))
            $this->id = $data["id"];
        else
            $this->id = null;

        unset($this->term);
        if (isset($data["term"]))
            $this->term = $data["term"];
        else
            $this->term = null;
    }

    /**
     * Convert term structure to JSON
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string JSON encoding of this object
     */
    public function toJSON($shorten = true) {
        return json_encode($this->toArray($shorten), JSON_PRETTY_PRINT);
    } 

    /**
     * Prepopulate term structure from the given JSON
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

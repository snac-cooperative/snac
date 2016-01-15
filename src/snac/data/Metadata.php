<?php

/**
 * SNAC Control Metadata Object class.
 *
 * Contains the snac control metadata class.
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
 * SNAC Control Metadata class
 *
 * This class contains the snac control metadata block associated with any other piece of
 * data.  It is included in the AbstractData class and is therefore allowed on
 * any piece of data that inherits from the abstract class.
 *
 * @author Robbie Hott
 * @author Tom Laudeman
 */
class SNACControlMetadata extends AbstractData {

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
        
        // Metadata should never have metadata
        unset ($this->snacControlMetadata);
    }

    
    /**
     * Required method to convert this data object to an array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This object as an associative array
     */
    public function toArray($shorten = true) {
        $return = array(
            "dataType" => "SNACControlMetadata"
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
     * Required method to import an array into this data object
     *
     * @param string[][] $data The data for this object in an associative array
     */
    public function fromArray($data) {
        
    }

}

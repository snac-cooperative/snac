<?php

/**
 * Abstract concept data object class.
 *
 * Contains the abstract class for the basis for concept data classes.
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2025 the Rector and Visitors of the University of Virginia, and
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
abstract class AbstractConceptData implements \Serializable {

    /**
     *
     * The record id for this class. This has two different meanings, depending on the
     * class. For Constellation.php this is the ic_id of the constellation aka version_history.ic_id. For
     * all other classes this is table.id, which is the record id, not the constellation id.
     *
     * @var int $id
     */
    protected $id = null;

    /**
     * @var \Monolog\Logger $logger Logger for this class
     */
    protected $logger = null;

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
        global $log;

        // create a log channel
        $this->logger = new \Monolog\Logger(get_class($this));
        $this->logger->pushHandler($log);
    }

    /**
     * Is Equal
     *
     * This function tests whether the current object is equal to the parameter.  They
     * must match exactly.  It allows for a parameter to enable skipping of the ID/version/operation
     * matching.
     *
     * @param \snac\data\AbstractConceptData $other The other object to compare
     * @param boolean $strict optional Whether to disable strict checking (skip id)
     * @param boolean $checkSubcomponents optional Whether or not to check SNACControlMetadata, nameEntries contributors & components
     * @return boolean true if equal, false if not
     */
    public function equals($other, $strict = true, $checkSubcomponents = true) {

        if ($other == null || !($other instanceOf \snac\data\AbstractConceptData))
            return false;

        if ($strict) {
            if ($this->getID() != $other->getID())
                return false;
        }

        // If all the tests pass, they are equal
        return true;
    }

    /**
     * Array Equality
     *
     * Checks that two arrays are equal.  Specifically, tests that the second array has all
     * the same objects as the first.
     *
     * @param \snac\data\AbstractData[] $first first array
     * @param \snac\data\AbstractData[] $second second array
     * @param boolean $strict optional whether or not to check ID/Version/Operation
     * @param boolean $checkSubcomponents optional Whether or not to check SNACControlMetadata, nameEntries contributors & components
     * @return boolean true if equal, false otherwise
     */
    protected function checkArrayEqual($first, $second, $strict = true, $checkSubcomponents = true) {
        if ($first == null && $second == null)
            return true;
        if ($first == null || $second == null)
            return false;
        if (count($first) != count($second))
            return false;

        $tmp = array();

        foreach ($first as $data) {
            foreach ($second as $k => $odata) {
                if ((($data == null && $odata == null) || ($data != null && $data->equals($odata, $strict, $checkSubcomponents)))
                        && !isset($tmp[$k])) {
                    $tmp[$k] = true;
                }
            }
        }

        $count = count($tmp);
        unset($tmp);

        if ($count != count($second))
            return false;

        return true;

    }

    /**
     * diff Array
     *
     * Goes through arrays of AbstractData and performs a diff.  It returns an array of
     * three different arrays: intersection (the shared components), first (the items
     * of the first not appearing in the second), and second (the items of the
     * second not appearing in the first).
     *
     * @param \snac\data\AbstractData[] $first first array
     * @param \snac\data\AbstractData[] $second second array
     * @param boolean $strict optional whether or not to check ID/Version/Operation
     * @return mixed[] An associative array of AbstractData[] with "intersection," "first," and "second" keys
     */
    protected function diffArray($first, $second, $strict = true) {
        $return = array(
                "intersection" => array(),
                "first" => array(),
                "second" => array()
                );

        if ($first == null && $second == null)
            return $return;
        if ($first == null) {
            $return["second"] = $second;
            return $return;
        }
        if ($second == null) {
            $return["first"] = $first;
            return $return;
        }

        $tmp = array();
        foreach ($first as $data) {
            $seen = false;
            foreach ($second as $k => $odata) {
                if ($data != null && $data->equals($odata, $strict)
                        && !isset($tmp[$k])) {
                    // in case there are duplicates in first
                    $tmp[$k] = true;
                    $seen = true;
                    array_push($return["intersection"], $data);
                }
            }
            if (!$seen) {
                array_push($return["first"], $data);
            }
        }

        foreach ($second as $k => $odata) {
            // make use of our key-bitmap to not have an inner loop
            if (!isset($tmp[$k])) {
                array_push($return["second"], $odata);
            }
        }

        return $return;

    }

    /**
     * Get the ID of this data structure. See comments for getDBInfo(). Class constellation this is ic_id. All
     * other classes this is table.id.
     *
     *  @return int ID of this structure
     */
    public function getID() {
        return $this->id;
    }


    /**
     * Set the id of this object. See comments for setDBInfo(). Class constellation this is ic_id. All
     * other classes this is table.id.
     *
     * @param int $id Set the constellation ic_id, or Object record id aka table.id for all other objects.
     */
    public function setID($id) {
        $this->id = $id;
    }

    /**
     * To String
     *
     * Converts this object to a human-readable summary string.  This is enough to identify
     * the object on sight, but not enough to discern programmatically.
     *
     * @return string A human-readable summary string of this object
     */
    public function toString() {
        return get_class($this);
    }

    /**
     * Required method to convert this data object to an array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This object as an associative array
     */
    public function toArray($shorten = true) {
        $return = array(
                'id' => $this->getID()
                );

        return $return;
    }

    /**
     * Required method to import an array into this data object
     *
     * @param string[][] $data The data for this object in an associative array
     */
    public function fromArray($data) {

        unset($this->id);
        if (isset($data["id"]))
            $this->id = $data["id"];
        else
            $this->id = null;

    }

    /**
     * Convert this object to JSON
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string JSON encoding of this object
     */
    public function toJSON($shorten = true) {
        return json_encode($this->toArray($shorten), JSON_PRETTY_PRINT);
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

    /**
     * Serialization Method
     *
     * Allows PHP's serialize() method to correctly serialize the object.
     *
     * {@inheritDoc}
     *
     * @return string Serialized form of this object
     */
    public function serialize() {
        return $this->toJSON();
    }

    /**
     * Un-Serialization Method
     *
     * Allows PHP's unserialize() method to correctly unserialize the object.
     *
     * {@inheritDoc}
     *
     * @param string $data Serialized version of this object
     */
    public function unserialize($data) {
        $this->fromJSON($data);
    }

}


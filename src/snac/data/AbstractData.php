<?php

/**
 * Abstract data object class.
 *
 * Contains the abstract class for the basis for any other data classes.
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
 * Abstract data class
 *
 * This class provides methods to read, construct, and export to JSON.  It also
 * requires inheriting classes to include toArray and fromArray functions that
 * will convert the data object to and from associative arrays.  It provides a
 * default constructor that may take an array as a parameter to fill the object.
 *
 * @author Robbie Hott
 * @author Tom Laudeman
 */
abstract class AbstractData implements \Serializable {

    /**
     * Constants associated with all data
     * @var string $OPERATION_INSERT the insert operation
     */
    public static $OPERATION_INSERT = "insert";
    /**
     * Constants associated with all data
     * @var string $OPERATION_UPDATE the update operation
     */
    public static $OPERATION_UPDATE = "update";
    /**
     * Constants associated with all data
     * @var string $OPERATION_DELETE the delete operation
     */
    public static $OPERATION_DELETE = "delete";

    /**
     *
     * The record id, or constellation id for this class. This has two different meanings, depending on the
     * class. For Constellation.php this is the ic_id of the constellation aka version_history.ic_id. For
     * all other classes this is table.id, which is the record id, not the constellation id.
     *
     * @var int $id
     */
    protected $id = null;


    /**
     *
     * The record version number, or constellation version (max) for this class. For Constellation.php this is
     * the "constellation version number" aka max(version) aka max(version_history.id). For all other classes,
     * this is the table.version which is a per-record version number, <= the constellation version number.
     *
     * @var int $version
     */
    protected $version = null;


    /**
     * @var \snac\data\SNACDate[] $dateList Universal date object list.
     *
     *
     */
    protected $dateList;

    /**
     * How many dates might be in the $dateList. Objects with no dates set this to some number greater than zero.
     *
     * @var int $maxDateCount maximum number of dates allowed in this object
     */
    protected $maxDateCount = 0;


    /**
     * var \snac\data\SNACControlMetadata[] $snacControlMetadata The snac control metadata entries for this piece of data.
     */
    protected $snacControlMetadata;

    /**
     * @var string Operation for this object.  Must be set to one of the constant values or null.
     */
    protected $operation;

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

        $this->snacControlMetadata = array();
        $this->dateList = array();
        if ($data != null && is_array($data))
            $this->fromArray($data);

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
     * @param \snac\data\AbstractData $other The other object to compare
     * @param boolean $strict optional Whether to disable strict checking (skip id)
     *
     * @return boolean true if equal, false if not
     */
    public function equals($other, $strict = true) {

        if ($other == null || !($other instanceOf \snac\data\AbstractData))
            return false;

        if ($strict) {
            if ($this->getID() != $other->getID())
                return false;
            if ($this->getVersion() != $other->getVersion())
                return false;
            if ($this->getOperation() != $other->getOperation())
                return false;
        }


        if ($this->getMaxDateCount() != $other->getMaxDateCount())
            return false;

        if ($this->getMaxDateCount() > 0) {
            $tmp = array();

            if (!$this->checkArrayEqual($this->getDateList(), $other->getDateList(), $strict))
                return false;
        }

        if (!$this->checkArrayEqual($this->getSNACControlMetadata(), $other->getSNACControlMetadata(), $strict))
            return false;

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
     * @return boolean true if equal, false otherwise
     */
    protected function checkArrayEqual($first, $second, $strict = true) {
        if ($first == null && $second == null)
            return true;
        if ($first == null || $second == null)
            return false;
        if (count($first) != count($second))
            return false;

        $tmp = array();

        foreach ($first as $data) {
            foreach ($second as $k => $odata) {
                if ((($data == null && $odata == null) || ($data != null && $data->equals($odata, $strict)))
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
     * Set the number of date objects we can have in the list of dates.
     *
     * @param integer $count The number of dates supported.
     *
     */
    protected function setMaxDateCount($count)
    {
        $this->maxDateCount = $count;
    }

    /**
     * Get the number of date objects we can have in the list of dates.
     *
     * @return integer $count The number of dates supported.
     *
     */
    public function getMaxDateCount()
    {
        return $this->maxDateCount;
    }

    /**
     * Get the list of dates.
     *
     * @return \snac\data\SNACDate[] Returns a list of SNACDate objects, or an empty list if there are no
     * dates. If someone has called unsetDateList() then it won't be a list and the calling code is expecting
     * a list, always, even if empty.
     *
     */
    public function getDateList()
    {
        return $this->dateList;
    }

    /**
     * Add a date object to our list of dates. Succeeds only if the object allows dates, or has room based on
     * maxDateCount
     *
     * @param \snac\data\SNACDate A single SNACDate that will be added to our list of dates.
     * @return boolean true on success, false on failure
     *
     */
    public function addDate($dateObj)
    {
        if ($this->maxDateCount != 0 &&
                (count($this->dateList) < $this->maxDateCount)) {
            array_push($this->dateList, $dateObj);
            return true;
        }
        return false;
    }

    /**
     * Set the Date List
     *
     * Sets the date list to be the given list of SNACDate objects, if this object can legally
     * hold that many dates.
     *
     * @param \snac\data\SNACDate[] A list of date objects
     * @return boolean true on success, false on failure
     *
     */
    public function setDateList($dateList)
    {
        if ($this->maxDateCount > 0 && count($dateList) < $this->maxDateCount) {
            $this->dateList = $dateList;
            return true;
        }
        return false;
    }


    /**
     * Set this object's database info in a single setter call, equivalent to setVersion($version); setID($id);
     *
     * Either or both keys may be empty, so there is no obvious sanity check. When a new Constellation object
     * is created by parsing a CPF file, both keys will be empty.
     *
     * @param int $version A version number. If $version is true for any meaning of true, then assign it to our private variable.
     *
     * @param int $id An id number. For constellation objects this is version_history.ic_id. For all other
     * objects this is table.id. If $id is true for any meaning of true, then assign it to our private
     * variable.
     *
     */
    public function setDBInfo($version, $id)
    {
        if ($version)
        {
            $this->version = $version;
        }
        if ($id)
        {
            $this->id = $id;
        }
    }


    /**
     * Get the dbInfo, returning a list with version and id. Do not return a list with keys unless you have a good reason. The
     * variable $this->id is *not* compatible with $vhInfo in DBUtils, except for class Constellation. All
     * other objects are not $vhInfo compatible, so it is best that we do not return a $vhInfo associative
     * list.
     *
     * @return string[] An array of version and id. For class Constellation, version is the max(version) aka
     * *the* version of the constellation aka max(version_history.id). For all other classes, version is the
     * version of each object (SQL record), and id is the table.id, not the constellation id.
     *
     */
    public function getDBInfo()
    {
        return array($this->version, $this->id);
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
     * Get the version number of this. For constellation this is *the* constellation version
     * aka max(version) aka max(version_history.id). For all other objects this is table.version for each
     * record (object).
     *
     *  @return int The version of this object.
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * Set the version number of this object. For constellation this is *the* constellation version aka
     * max(version) aka max(version_history.id). For all other objects this is table.version for each record
     * (object).
     *
     * @param int $version The version of this object.
     */
    public function setVersion($version) {
        $this->version = $version;
    }

    /**
     * Add a piece of snac control metadata to this structure
     *
     * @param \snac\data\SNACControlMetadata $metadata snac control metadata to add to this structure
     */
    public function addSNACControlMetadata($metadata) {
        array_push($this->snacControlMetadata, $metadata);
    }

    /**
     * Set all the snac control metadata for this structure
     *
     * @param \snac\data\SNACControlMetadata[] $metadata Array of snac control metadata to add to this structure
     */
    public function setAllSNACControlMetadata($metadata) {
        unset($this->snacControlMetadata);
        $this->snacControlMetadata = $metadata;
    }

    /**
     * Get all snac control metadata for this structure
     *
     * @return \snac\data\SNACControlMetadata[] Array of snac control metadata about this data
     */
    public function getSNACControlMetadata() {
        if (isset($this->snacControlMetadata))
            return $this->snacControlMetadata;
        return null;
    }

    /**
     * Update SCM Citations
     *
     * Goes through the SCMs attached to this data object and updates any with citation
     * oldSource to use citation newSource instead.
     *
     * @param  \snac\data\Source $oldSource Source to replace
     * @param  \snac\data\Source $newSource Source to replace with
     */
    public function updateSCMCitation($oldSource, $newSource=null) {
        if ($oldSource === null) {
            return;
        }

        if (isset($this->snacControlMetadata) && $this->snacControlMetadata !== null) {
            foreach ($this->snacControlMetadata as &$scm) {
                if ($scm->getCitation() !== null && $scm->getCitation()->getID() == $oldSource->getID()) {
                    $scm->setCitation($newSource);
                }
            }
        }
    }

    /**
     * Set the operation for this data
     *
     * @param string $operation The constant for the operation
     * @return boolean true on success, false on failure
     */
    public function setOperation($operation) {
        if ($operation == AbstractData::$OPERATION_UPDATE ||
            $operation == AbstractData::$OPERATION_DELETE ||
            $operation == AbstractData::$OPERATION_INSERT) {
                $this->operation = $operation;
                return true;
        }
        return false;
    }

    /**
     * Get the operation for this data object
     *
     * @return string the operation, or null if no operation
     */
    public function getOperation() {
        return $this->operation;
    }

    /**
     * Required method to convert this data object to an array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This object as an associative array
     */
    public function toArray($shorten = true) {
        $return = array(
            'id' => $this->getID(),
            'version' => $this->getVersion(),
            'operation' => $this->getOperation()
            );

        $return['dates'] = array();
        if (isset($this->dateList) && $this->dateList != null) {
            foreach ($this->dateList as $i => $v)
            {
                $return["dates"][$i] = $v->toArray($shorten);
            }
        }

        if (isset($this->snacControlMetadata) && !empty($this->snacControlMetadata)) {
            $return['snacControlMetadata'] = array();
            foreach ($this->snacControlMetadata as $i => $v)
                $return["snacControlMetadata"][$i] = $v->toArray($shorten);
        }

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

        unset($this->id);
        if (isset($data["id"]))
            $this->id = $data["id"];
        else
            $this->id = null;

        unset($this->version);
        if (isset($data["version"]))
            $this->version = $data["version"];
        else
            $this->version = null;

        unset($this->operation);
        if (isset($data["operation"]))
            $this->operation = $data["operation"];
        else
            $this->operation = null;

        unset($this->snacControlMetadata);
        $this->snacControlMetadata = array();
        if (isset($data["snacControlMetadata"])) {
            foreach ($data["snacControlMetadata"] as $i => $entry)
                if ($entry != null)
                    $this->snacControlMetadata[$i] = new SNACControlMetadata($entry);
        }

        unset($this->dateList);
        $this->dateList = array();
        if (isset($data["dates"])) {
            foreach ($data["dates"] as $i => $entry)
                if ($entry != null)
                    $this->dateList[$i] = new SNACDate($entry);
        }
        // Note: inheriting classes should set the maxDateCount appropriately
        // based on the definition of that class.
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

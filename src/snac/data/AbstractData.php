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
abstract class AbstractData {


    /**
     *
     * The record id, or constellation id for this class. This has two different meanings, depending on the
     * class. For Constellation.php this is the main_id of the constellation aka version_history.main_id. For
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
     * Set this object's database info in a single setter call, equivalent to setVersion($version); setID($id);
     *
     * Either or both keys may be empty, so there is no obvious sanity check. When a new Constellation object
     * is created by parsing a CPF file, both keys will be empty.
     *
     * @param int $version A version number. If $version is true for any meaning of true, then assign it to our private variable.
     *
     * @param int $id An id number. For constellation objects this is version_history.main_id. For all other
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
     * Get the ID of this data structure. See comments for getDBInfo(). Class constellation this is main_id. All
     * other classes this is table.id.
     *
     *  @return int ID of this structure
     */
    public function getID() {
        return $this->id;
    }


    /**
     * Set the id of this object. See comments for setDBInfo(). Class constellation this is main_id. All
     * other classes this is table.id.
     *
     * @param int $id Set the constellation main_id, or Object record id aka table.id for all other objects.
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
     * Required method to convert this data object to an array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This object as an associative array
     */
    public abstract function toArray($shorten = true);

    /**
     * Required method to import an array into this data object
     *
     * @param string[][] $data The data for this object in an associative array
     */
    public abstract function fromArray($data);

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
    

}

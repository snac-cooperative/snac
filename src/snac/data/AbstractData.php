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
     * var int $id The record id for this data structure. This is not the constellation id.
     */
    protected $id = null;

    /**
     * var int $version The version number for this data structure
     */
    protected $version = null;

    /**
     * var int $nrdVersion The version number for this data structure. Table nrd.version, not max(version_history.id)
     */
    protected $nrdVersion = null;

    /**
     * var int $mainID The constellation id for this data structure
     */
    protected $mainID = null;

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
     * Set this object's dbInfo. Take a list with keys 'version' and 'main_id' in any order. We use this list
     * because it is compatible with $vhInfo used in the DBUtil and SQL classes. Most code will pass $vhInfo
     * and $dbiList without knowing what is inside the list, and since most code doesn't know about the inner
     * workings, we use an associative list. Note that via setDBInfo() we are compatible the $vhInfo convention,
     * but hide our private internal workings.
     *
     * Either or both keys may be empty, so there is no obvious sanity check. When a new Constellation object
     * is created by parsing a CPF file, both $dbiList keys will be empty.
     *
     * @param int $version A version number. If $version is true for any meaning of true, then assign it to our private variable.
     * If $version not true (for any php meaning of 'not true'), then this function will do nothing.
     *
     * @param int $mainID A main id number. If $mainID is true for any meaning of true, then assign it to our
     * private variable.  If $mainID not true (for any php meaning of 'not true'), then this function will do
     * nothing.
     * 
     */
    public function setDBInfo($version, $mainID, $id, $nrdVersion)
    {
        // printf("sdi-one: $version $mainID $id\n");
        if ($version)
        {
            $this->version = $version;
        }
        if ($mainID)
        {
            $this->mainID = $mainID;
        }
        if ($id)
        {
            $this->id = $id;
        }
        if ($nrdVersion) // Table nrd.version, not max(version_history.id)
        {
            $this->nrdVersion = $nrdVersion;
        }
        // printf("sdi-two: $this->version $this->mainID $this->id\n");
    }


    /**
     * Get the dbInfo, returning a list with keys 'version', 'main_id', and 'id' in any order. This is compatible
     * with $vhInfo used extensively in DBUtil and SQL. Key 'id' was added Jan 2016.
     *
     * @return string[] An array with keys 'version', 'main_id', 'id' in any order.
     *
     */
    public function getDBInfo()
    {
        return array('version' => $this->version,
                     'main_id' => $this->mainID,
                     'id' => $this->id);
    }

    /**
     * Get constellation id aka main_id aka mainID aka version_history.main_id.
     * This is *the* Constellation main_id derived from version_history.main_id.
     *
     *  @return int Constellation ID of this structure.
     */
    public function getMainID() {
        return $this->mainID;
    }

    /**
     * Get the ID of this data structure. See comments from setID(). In keeping with SQL conventions, the id
     * is a per-record numeric key. This is *not* the Constellation main_id derived from version_history.main_id.
     *
     *  @return int ID of this structure
     */
    public function getID() {
        return $this->id;
    }

    /**
     * Set the constellation ID of this object. This is table.main_id, derived from version_history.main_id.
     *
     * @param int $mainID constellation id of this object
     */
    public function setMainID($mainID) {
        $this->mainID = $mainID;
    }

    /**
     * Set the record ID of this data structure. This is sql table.id for all tables. Note that to find the
     * correct table.id, you need version_history.id (as version) and version_history.main_id.
     *
     * (An old comment suggested that for table nrd, there might be confusion about nrd.id and nrd.main_id. As
     * far as I know, there is no confusion.)
     *
     * @param int $id Record ID to assign this structure
     */
    public function setID($id) {
        $this->id = $id;
    }
    
    /**
     * Get the version number of this data structure
     *
     *  @return int version of this structure
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * Set the version number of this data structure
     *
     * @param int $id version to assign this structure
     */
    public function setVersion($version) {
        $this->version = $version;
    }

        /**
     * Get the version number of this data structure. Table nrd.version, not max(version_history.id)
     *
     *  @return int version of this structure
     */
    public function getNrdVersion() {
        return $this->nrdVersion;
    }

    /**
     * Set the version number of this data structure. Table nrd.version, not max(version_history.id)
     *
     * @param int $id version to assign this structure
     */
    public function setNrdVersion($version) {
        $this->version = $nrdVersion;
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

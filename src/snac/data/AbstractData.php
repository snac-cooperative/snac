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
     * Database info that the constellation objects carry around. Keys are not order dependent. Keys have been
     * chosen for compatibility with $vhInfo in DBUtil and SQL.
     *
     * @var $dbInfo An array with keys 'version' and 'main_id' in any order.
     */
    private $dbInfo = array('version' => null,
                            'main_id' => null);

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
     * @param string[] $dbiList A list with keys 'version' and 'main_id' in any order. If $dbiList is true for
     * any meaning of true, then we check for each key. If $dbiList not true (for any php meaning of 'not
     * true'), then this function will do nothing.
     *
     */
    public function xsetDBInfo($dbiList)
    {
        if ($dbiList)
        {
            if (isset($dbiList['version']))
            {
                $this->dbInfo['version'] = $dbiList['version'];
            }
            if (isset($dbiList['main_id']))
            {
                $this->dbInfo['main_id'] = $dbiList['main_id'];
            }
        }
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
    public function setDBInfo($version, $mainID)
    {
        if ($version)
        {
            $this->dbInfo['version'] = $version;
        }
        if ($mainID)
        {
            $this->dbInfo['main_id'] = $mainID;
        }
    }


    /**
     * Get the dbInfo, returning a list with keys 'version' and 'main_id' in any order. This is compatible
     * with $vhInfo used extensively in DBUtil and SQL.
     *
     * @return string[] An array with keys 'version' and 'main_id' in any order.
     *
     */
    public function getDBInfo()
    {
        return array('version' => $this->dbInfo['version'],
                     'main_id' => $this->dbInfo['main_id']);
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

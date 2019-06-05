<?php

/**
 * API Key for user
 *
 * API key information 
 *
 * @author Robbie Hott 
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2019 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\data;

/**
 * API Key class
 *
 */
class APIKey extends AbstractData {

    /**
     * string $expires The expiration time of this key
     */
    private $expires = null;

    /**
     * string $label The clear-text label for this key
     */
    private $label = null;

    /**
     * string $generated The generation time of this key
     */
    private $generated = null;

    /**
     * string $key The Key string.  It should NEVER be set except when generating the key
     * for the first time.  Else, it should be null!
     *
     * WARNING: This should NEVER be logged on production!
     */
    private $keyString = null;


    /**
     * Constructor
     *
     * @param string[][] $data The data for this object in an associative array
     */
    public function __construct($data=null) {
        // Calling parent's constructor doesn't affect the execution
        //parent::__construct($data);
        $this->dataType = 'APIKey';
        if ($data != null && is_array($data))
            $this->fromArray($data);
    }

    /**
     * Set Label
     *
     * @param string $lbl the new label value
     */ 
    public function setLabel($lbl) {
        $this->label = $lbl; 
    }

    /**
     * Set Generated
     * 
     * @param string $gen The new generated time for this key
     */
    public function setGenerated($gen) {
        $this->generated = $gen; 
    }

    /**
     * Set Expires
     *
     * @param string $exp The new expiration time for this key
     */
    public function setExpires($exp) {
        $this->expires = $exp; 
    }

    /**
     * Set Key
     *
     * WARNING: This function should NEVER be used except when generating a
     * key for the first time.  And then, the value of the key field should NEVER be logged.
     *
     * @param string $key The clear-text key
     */
    public function setKey($key) {
        $this->keyString = $key;
    }

    /**
     * Get Label
     *
     * @return string The label of this key
     */
    public function getLabel() {
        return $this->label;
    }

    /**
     * Get Generated Time
     *
     * @return string The generation time of this key
     */
    public function getGenerated() {
        return $this->generated;
    }

    /**
     * Get Expiration Time
     *
     * @return string The expiration time of this key
     */
    public function getExpires() {
        return $this->expires;
    }

    /**
     * Get Key
     *
     * This method returns the clear text value of the key.  It should rarely be set.
     *
     * WARNING: This method should NEVER be used except on key generation.  In all other
     * circumstances, the key field MUST be null.  The return value of this method
     * must NEVER be logged on production.
     *
     * @return string The API key in clear-text
     */
    public function getKey() {
        return $this->keyString;
    }


    /**
     * Required method to convert this term structure to an array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This object as an associative array
     */
    public function toArray($shorten = true) {

        $return = [
            "dataType" => $this->dataType,
            "key" => $this->keyString,
            "label" => $this->label,
            "generated" => $this->generated,
            "expires" => $this->expires
        ];
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

        if (!isset($data["dataType"]) || $data["dataType"] != $this->dataType)
            return false;

        parent::fromArray($data);

        if (isset($data["expires"]))
            $this->expires = $data["expires"];

        if (isset($data["generated"]))
            $this->generated = $data["generated"];

        if (isset($data["label"]))
            $this->label = $data["label"];
        
        if (isset($data["key"]))
            $this->keyString = $data["key"];
    }

}


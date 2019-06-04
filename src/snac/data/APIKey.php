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

    private $expires = null;
    private $label = null;
    private $generated = null;

    /**
     * The Key string.  It should NEVER be set except when generating the key
     * for the first time.  Else, it should be null!
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

    
    public function setLabel($lbl) {
        $this->label = $lbl; 
    }

    public function setGenerated($gen) {
        $this->generated = $gen; 
    }

    public function setExpires($exp) {
        $this->expires = $exp; 
    }

    public function setKey($key) {
        $this->keyString = $key;
    }

    public function getLabel() {
        return $this->label;
    }

    public function getGenerated() {
        return $this->generated;
    }

    public function getExpires() {
        return $this->expires;
    }

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


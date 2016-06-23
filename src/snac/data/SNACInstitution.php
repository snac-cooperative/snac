<?php

/**
 * SNAC Institution Class
 *
 *
 * License:
 *
 *
 * @author Tom Laudeman
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2016 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\data;

/**
 * SNAC Institution Class
 *
 * Class that holds SNAC Institution information
 *
 * @author Tom Laudeman
 */
class SNACInstitution extends AbstractData {

    /**
     * Constellation ID
     * @var integer $ic_id Constellation ID of this institution
     */
    private $ic_id = null;

    /**
     * Get Constellation ID
     * @return integer Constellation ID
     */
    public function getConstellationID()
    {
        return $this->ic_id;
    }

    /**
     * Set Constellation ID
     * @param integer $ic_id Constellation ID
     */
    public function setConstellationID($ic_id)
    {
        $this->ic_id = $ic_id;
    }

    /**
     * Constructor
     * 
     * @param string[] $data optional Array of data to pre-fill this object
     */
    public function __construct($data = null) {
        $this->setMaxDateCount(0);
        parent::__construct($data);
    }

        /**
     * Required method to convert this term structure to an array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This object as an associative array
     */
    public function toArray($shorten = true) {

        $return = array(
            'dataType' => 'SNACInstitution',
            'ic_id' => $this->getConstellationID()
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
            
        if (!isset($data["dataType"]) || $data["dataType"] != $this->dataType)
            return false;
        
        unset($this->ic_id);
        if (isset($data["ic_id"]))
            $this->ic_id = $data["ic_id"];
        else
            $this->ic_id = null;
        
        parent::fromArray($data);
        
        // Note: inheriting classes should set the maxDateCount appropriately
        // based on the definition of that class.
    }


}

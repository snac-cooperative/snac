<?php

/**
 * Place File
 *
 * Contains the data class for the places
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
 * Place data structure class
 *
 * @author Robbie Hott
 *        
 */
class Place extends AbstractData {

    /**
     *
     * @var \snac\data\SNACDate Date range the place is valid
     */
    private $dates;

    /**
     *
     * @var string Descriptive note
     */
    private $note;

    /**
     *
     * @var string Type of the place
     */
    private $type;

    /**
     *
     * @var string Place role
     */
    private $role;

    /**
     *
     * @var \snac\data\PlaceEntry[] Place entries contained in this place
     */
    private $entries;

    /**
     * Constructor
     */
    public function __construct($data = null) {

        $this->entries = array ();
        parent::__construct($data);
    }
    
    /**
     * Returns this object's data as an associative array
     *
     * @return string[][] This objects data in array form
     */
    public function toArray() {
        $return = array(
            "dataType" => "Place",
            "dates" => $this->dates == null ? null : $this->dates->toArray(),
            "type" => $this->type,
            "role" => $this->role,
            "entries" => array(),
            "note" => $this->note
        );

        foreach ($this->entries as $i => $entry) 
            $return["entries"][$i] = $entry->toArray();
        
        return $return;
    }

    /**
     * Replaces this object's data with the given associative array
     *
     * @param string[][] $data This objects data in array form
     * @return boolean true on success, false on failure
     */
    public function fromArray($data) {
        if (!isset($data["dataType"]) || $data["dataType"] != "Place")
            return false;

        if (isset($data["dates"]))
            $this->dates = new SNACDate($data["dates"]);
        else
            $this->dates = null;

        if (isset($data["type"]))
            $this->type = $data["type"];
        else
            $this->type = null;

        if (isset($data["role"]))
            $this->role = $data["role"];
        else
            $this->role = null;

        $this->entries = array();
        if (isset($data["entries"])) {
            foreach ($data["entries"] as $i => $entry)
                $this->entries[$i] = new PlaceEntry($entry);
        }

        if (isset($data["note"]))
            $this->note = $data["note"];
        else
            $this->note = null;

        return true;
    }

    /**
     * Set the date range
     *
     * @param \snac\data\SNACDate $date Date range
     */
    public function setDateRange($date) {

        $this->dates = $date;
    }

    /**
     * Set the descriptive note
     * 
     * @param string $note descriptive note
     */
    public function setNote($note) {

        $this->note = $note;
    }

    /**
     * Set the place type
     * 
     * @param string $type Place type
     */
    public function setType($type) {

        $this->type = $type;
    }

    /**
     * Set the place role
     * 
     * @param string $role place role
     */
    public function setRole($role) {

        $this->role = $role;
    }

    /**
     * Add a place entry to the place
     * 
     * @param \snac\data\PlaceEntry $entry Place entry to add
     */
    public function addPlaceEntry($entry) {

        array_push($this->entries, $entry);
    }
}

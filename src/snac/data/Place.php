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
 * See the abstract parent class for common methods setDBInfo() and getDBInfo().
 *
 * @author Robbie Hott
 *        
 */
class Place extends AbstractData {

    /**
     * From EAC-CPF tag(s):
     * 
     * * place/date/*
     * * place/dateRange/*
     * 
     * @var \snac\data\SNACDate Date range the place is valid
     */
    private $dates;

    /**
     * From EAC-CPF tag(s):
     * 
     * * place/descriptiveNote
     * 
     * @var string Descriptive note
     */
    private $note;

    /**
     * From EAC-CPF tag(s):
     * 
     * * place/@localType
     * 
     * @var \snac\data\Term Type of the place
     */
    private $type;

    /**
     * From EAC-CPF tag(s):
     * 
     * * place/placeRole
     * 
     * @var \snac\data\Term Place role
     */
    private $role;

    /**
     * From EAC-CPF tag(s):
     * 
     * * place/placeEntry/*
     * 
     * @var \snac\data\PlaceEntry[] Place entries contained in this place
     */
    private $entries;

    /**
     * Constructor
     *
     * @param string[] $data A list of data suitable for fromArray(). This exists for use by internal code to
     * send objects around the system, not for generally creating a new object.
     * 
     */
    public function __construct($data = null) {

        $this->entries = array ();
        parent::__construct($data);
    }
    
    /**
     * Returns this object's data as an associative array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = true) {
        $return = array(
            "dataType" => "Place",
            "dates" => $this->dates == null ? null : $this->dates->toArray($shorten),
            "type" => $this->type == null ? null : $this->type->toArray($shorten),
            "role" => $this->role == null ? null : $this->role->toArray($shorten),
            "entries" => array(),
            "note" => $this->note
        );

        foreach ($this->entries as $i => $entry) 
            $return["entries"][$i] = $entry->toArray($shorten);
            
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
     * Replaces this object's data with the given associative array
     *
     * @param string[][] $data This objects data in array form
     * @return boolean true on success, false on failure
     */
    public function fromArray($data) {
        if (!isset($data["dataType"]) || $data["dataType"] != "Place")
            return false;

        parent::fromArray($data);

        if (isset($data["dates"]))
            $this->dates = new SNACDate($data["dates"]);
        else
            $this->dates = null;

        if (isset($data["type"]))
            $this->type = new \snac\data\Term($data["type"]);
        else
            $this->type = null;

        if (isset($data["role"]))
            $this->role = new \snac\data\Term($data["role"]);
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
     * @param \snac\data\Term $type Place type
     */
    public function setType($type) {

        $this->type = $type;
    }

    /**
     * Set the place role
     * 
     * @param \snac\data\Term $role place role
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

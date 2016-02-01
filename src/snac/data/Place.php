<?php

/**
 * Place File
 *
 * Contains the data class for the Constellation place. See PlaceEntry for the place controlled vocabulary
 * class.
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
 * Place data structure class for place in Constellation. Constellation may contain Place, which may contain a
 * list of PlaceEntry objects. The list of PlaceEntry is denormalized form of the database place_entry and
 * geo_place tables.
 *
 * Constellation may have zero Place objects when there are no associated places. (I mention this because
 * while fairly obvious, the implementation of place is also fairly confusing).
 *
 * Likewise, Place may have zero PlaceEntry objects where there is no known related geo names place.
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
     * The list of PlaceEntry objects. There may be zero of these if the Place is not related to a known place
     * in the geo names controlled vocabulary. There may be multiple PlaceEntry objects, each with the data
     * from SQL place_link and geo_place. All of the data from those two tables is necessary to build the UI
     * for normal constellation editing.
     *
     * Constellation has a list of zero or more Place objects. Each Place object as a list of zero or more
     * PlaceEntry objects.
     *
     * From EAC-CPF tag(s):
     * 
     * * place/placeEntry/*
     * 
     * @var \snac\data\PlaceEntry[] Place entries contained in this place
     */
    private $entries;

        /**
     * From EAC-CPF tag(s):
     * 
     * * placeEntry/
     * * snac:placeEntry/placeEntry
     * 
     * @var string original text within this entry
     */
    private $original;

    /**
     * Constructor
     *
     * A setMaxDateCount(1) means a single date object.
     *
     * @param string[] $data A list of data suitable for fromArray(). This exists for use by internal code to
     * send objects around the system, not for generally creating a new object.
     * 
     */
    public function __construct($data = null) {
        $this->setMaxDateCount(1);
        $this->entries = array ();
        parent::__construct($data);
    }
    
    /**
     * Set the original place name
     * 
     * @param string $original original place name
     */
    public function setOriginal($original) {

        $this->original = $original;
    }

    /**
     * Get the original place name
     * 
     * @return string $original original place name
     */
    public function getOriginal() {

        $this->original = $original;
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
            "type" => $this->type == null ? null : $this->type->toArray($shorten),
            "role" => $this->role == null ? null : $this->role->toArray($shorten),
            "original" => $this->original,
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

        if (isset($data["original"]))
            $this->original = $data["original"];
        else
            $this->original = null;

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
    public function setType(\snac\data\Term $type) {

        $this->type = $type;
    }

    /**
     * Set the place role
     * 
     * @param \snac\data\Term $role place role
     */
    public function setRole(\snac\data\Term $role) {

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

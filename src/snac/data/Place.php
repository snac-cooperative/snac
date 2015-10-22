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
class Place {

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
    public function __construct() {

        $this->entries = array ();
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
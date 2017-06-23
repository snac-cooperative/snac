<?php

/**
 * Place class file
 *
 * Contains the place data class.
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
 * Place 
 * 
 * The place data class contains information about places throughout the Constellation structure.  In EAC-CPF terms, these
 * objects are used anywhere a PlaceEntry may be used.  Therefore, they contain the bits of information from the CPF
 * description's Place tags (which may contain repeatable placeEntry tags) and information from placeEntry tags themselves.
 * If there is a known controlled vocabulary (canonical) geographical place associated with this Place, that term should be
 * pulled using a GeoTerm and stored appropriately.  (In SNAC EAC-CPF, this would be a <snac:placeEntry> contained inside a 
 * placeEntry tag that is a GeoNames match).
 * 
 * When generating EAC-CPF from the Constellation structure, any Places in the top-level Constellation must be created as
 * <place> wrapped <placeEntry> tags.  Everywhere else, these Places must be generated as <placeEntry> tags only.
 *
 *
 * @author Robbie Hott
 * @author Tom Laudeman
 *        
 */
class Place extends AbstractData {

    /**
     * Original place string from CPF
     *
     * This is the original string from the CPF <placeEntry> tag. Once a place is linked to a controlled vocab,
     * this string becomes unnecessary, or is filled with the name from the GeoTerm associated with this object.
     * 
     * @var string $original
     */
    private $original;

    /**
     * Descriptive Note
     * 
     * From EAC-CPF tag(s):
     * 
     * * place/descriptiveNote
     * 
     * @var string $note Descriptive note
     */
    private $note;

    /**
     * Type of the place
     * 
     * This is a controlled-vocabulary place type term.
     * 
     * From EAC-CPF tag(s):
     * 
     * * place/@localType
     * 
     * @var \snac\data\Term $type Type of the place
     */
    private $type;

    /**
     * Role of the place
     * 
     * This is a controlled-vocabulary place role term.
     * From EAC-CPF tag(s):
     * 
     * * place/placeRole
     * 
     * @var \snac\data\Term $role Place role
     */
    private $role;
    
    /**
     * Match confidence score
     * 
     * This is a machine-generated confidence score that the GeoTerm linked to this
     * place is actually the place referred to by the "original" string.
     * 
     * @var float $score machine-generated match confidence score
     */
    private $score;
    
    /**
     * Confirmed Geographical Place
     * 
     * This boolean states whether the linked GeoTerm was human-vetted as a match.  If a human made
     * the match, this is true, else it is false.
     * 
     * @var boolean $confirmed
     */
    private $confirmed;
    
    /**
     * Geographical Place Term
     * 
     * @var \snac\data\GeoTerm $geoTerm The controlled vocabulary term object for the geographical place
     */ 
    private $geoTerm;

    /**
     * Address Lines 
     * 
     * From EAC-CPF tag(s):
     * 
     * * place/address/addressLine
     * 
     * @var \snac\data\AddressLine[] $address Address of this place
     */
    private $address;

    /**
     * Constructor
     *
     * Set up the Place object.  Read an associative array as a parameter.  This object may only have one
     * date, so we use the parent's method to limit dates to 1.
     *
     * @param string[] $data A list of data suitable for fromArray(). This exists for use by internal code to
     * send objects around the system, not for generally creating a new object.
     * 
     */
    public function __construct($data = null) {
        $this->setMaxDateCount(1);
        
        // Set some default values
        $this->confirmed = false;
        $this->score = 0;

        $this->address = array();

        if ($data != null)
            $this->fromArray($data);
        
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
     * Set the Match Score
     * 
     * Set the machine-generated match confidence score between the GeoTerm
     * and this place.
     * 
     * @param float $score Match score
     */
    public function setScore($score) {
        $this->score = $score;
    }
    
    /**
     * Set Confirmed
     * 
     * Set the confirmation status of this place by parameter.  This confirmation status
     * is true if a human has confirmed that the GeoTerm is actually this Place.
     * 
     * This method will return false if trying to confirm a place that has no GeoTerm.
     * 
     * @param boolean $confirmed true if human confirmed, false otherwise
     * @return boolean True if the confirmed flag was set, false otherwise
     */
    public function setConfirmed($confirmed) {
        if ($this->geoTerm == null)
            return false;
        $this->confirmed = $confirmed;
        return true;
    }
    
    /**
     * Confirm this place
     * 
     * Confirm that this place is actually the GeoTerm listed, by human inspection.
     * 
     * This method will return false if trying to confirm a place that has no GeoTerm.
     * 
     * @return boolean True if confirmed flag was set, false otherwise
     */
    public function confirm() {
        if ($this->getGeoTerm() == null)
            return false;
        $this->confirmed = true;
        return true;
    }
    
    /**
     * Deconfirm this place
     * 
     * Remove confirmation that this place is actually the GeoTerm listed.
     */
    public function deconfirm() {
        $this->confirmed = false;
    }
    
    /**
     * Set the Geographical Term
     * 
     * Set the canonical geographical term associated with this place
     * 
     * @param \snac\data\GeoTerm $geoTerm The canonical geographical term
     */
    public function setGeoTerm($geoTerm) {
        $this->geoTerm = $geoTerm;
    }

    /**
     * Add an address line
     *
     * Add an address line to this place
     *
     * @param \snac\data\AddressLine $addressLine The address line to add
     */
    public function addAddressLine($addressLine) {
        array_push($this->address, $addressLine);
    }

    /**
     * Set address
     *
     * Sets the address this set
     *
     * @param \snac\data\AddressLine[] $address The address to use
     */
    public function setAddress($address) {
        $this->address = $address;
    }

    /**
     * Get Address
     *
     * Gets the address associated with this place
     *
     * @return \snac\data\AddressLine[] The address as a set of lines
     */
    public function getAddress() {
        return $this->address;
    }

    /**
     * Get the original place name
     *
     * @return string $original original place name
     */
    public function getOriginal() {
    
        return $this->original;
    }
    
    /**
     * Get descriptive note
     * 
     * Get the descriptive note
     * 
     * @return string descriptive note
     */
    public function getNote() {
        return $this->note;
    }
    
    /**
     * Get Type
     * 
     * Get the type of this place
     * 
     * @return \snac\data\Term Type term for this place
     */
    public function getType() {
        return $this->type;
    }
    
    /**
     * Get Role
     * 
     * Get the role associated with this place
     * 
     * @return \snac\data\Term Role term for this place
     */
    public function getRole() {
        return $this->role;
    }
    
    /**
     * Get Confirmation Status
     * 
     * Get the confirmation status for this place.  This returns true if the GeoTerm has been
     * human-vetted to match this place, false otherwise.
     * 
     * @return boolean true if human-matched GeoTerm, false otherwise
     */
    public function getConfirmed() {
        return $this->confirmed;
    }
    
    /**
     * Get Match Confidence Score
     * 
     * Get the machine-generated match score between the associated GeoTerm and this place.
     * 
     * @return float match score, or 0 if no GeoTerm present
     */
    public function getScore() {
        if ($this->geoTerm == null)
            return 0;
        return $this->score;
    }
    
    /**
     * Get the Geographical Term
     * 
     * Get the canonical geographic term for this place
     * 
     * @return \snac\data\GeoTerm The geographic term, or null if none exists
     */
    public function getGeoTerm() {
        return $this->geoTerm;
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
            "original" => $this->original,
            "type" => $this->type == null ? null : $this->type->toArray($shorten),
            "role" => $this->role == null ? null : $this->role->toArray($shorten),
            "address" => array(),
            "geoplace" => $this->geoTerm == null ? null : $this->geoTerm->toArray($shorten),
            "score" => $this->score,
            "confirmed" => $this->confirmed,
            "note" => $this->note
        );
        
        foreach ($this->address as $i => $v)
            $return["address"][$i] = $v->toArray($shorten);

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

        if (isset($data["score"]))
            $this->score = $data["score"];
        else
            $this->score = 0;

        if (isset($data["confirmed"]))
            $this->confirmed = $data["confirmed"];
        else
            $this->confirmed = false;

        if (isset($data["type"]) && $data["type"] != null)
            $this->type = new \snac\data\Term($data["type"]);
        else
            $this->type = null;

        if (isset($data["role"]) && $data["role"] != null)
            $this->role = new \snac\data\Term($data["role"]);
        else
            $this->role = null;

        if (isset($data["geoplace"]) && $data["geoplace"] != null)
            $this->geoTerm = new \snac\data\GeoTerm($data["geoplace"]);
        else
            $this->geoTerm = null;

        if (isset($data["note"]))
            $this->note = $data["note"];
        else
            $this->note = null;

        unset($this->address);
        $this->address = array();
        if (isset($data["address"]))
            foreach ($data["address"] as $i => $entry)
                if ($entry != null)
                    $this->address[$i] = new \snac\data\AddressLine($entry);

        return true;
    }

    /**
     *
     * {@inheritDoc}
     *
     * @param \snac\data\Place $other Other object
     * @param boolean $strict optional Whether or not to check id, version, and operation
     * @return boolean true on equality, false otherwise
     *       
     * @see \snac\data\AbstractData::equals()
     */
    public function equals($other, $strict = true) {

        if ($other == null || ! ($other instanceof \snac\data\Place))
            return false;
        
        if (! parent::equals($other, $strict))
            return false;
        
        if ($this->getOriginal() != $other->getOriginal())
            return false;
        if ($this->getScore() != $other->getScore())
            return false;
        if ($this->getConfirmed() != $other->getConfirmed())
            return false;
        if ($this->getNote() != $other->getNote())
            return false;
        
        if (($this->getType() != null && ! $this->getType()->equals($other->getType())) ||
                 ($this->getType() == null && $other->getType() != null))
            return false;
        if (($this->getRole() != null && ! $this->getRole()->equals($other->getRole())) ||
                 ($this->getRole() == null && $other->getRole() != null))
            return false;
        if (($this->getGeoTerm() != null && ! $this->getGeoTerm()->equals($other->getGeoTerm())) ||
                 ($this->getGeoTerm() == null && $other->getGeoTerm() != null))
            return false;
        
        if (!$this->checkArrayEqual($this->getAddress(), $other->getAddress(), $strict))
            return false;
        
        return true;
    }

    /**
     * Cleanse all sub-elements
     *
     * Removes the ID and Version from sub-elements and updates the operation to be
     * INSERT.  If the operation is specified by the parameter, this method
     * will use that operation instead of INSERT.
     *
     * @param string $operation optional The operation to use (default is INSERT)
     */ 
    public function cleanseSubElements($operation=null) {
        $newOperation = \snac\data\AbstractData::$OPERATION_INSERT;
        if ($operation !== null) {
            $newOperation = $operation;
        }

        parent::cleanseSubElements($newOperation);

        foreach ($this->address as &$addressLine) {
            $addressLine->setID(null);
            $addressLine->setVersion(null);
            $addressLine->setOperation($newOperation);
            $addressLine->cleanseSubElements($newOperation);
        }
    }
}

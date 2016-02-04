<?php

/**
 * Place class
 *
 * Contains the place data for the objects such as Constellation.
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
 * Place data class
 *
 * Place in Constellations and other objects. Constellation may contain Place. Place
 * data is denormalized form of the database place_link, (related snac meta data) and geo_place tables.
 *
 * Constellation may have zero Place objects when there are no associated places. (I mention this because
 * while fairly obvious, the implementation of place is also fairly confusing).
 *
 * Likewise, Place may have many empty properties where there is no known related geo_place (aka geonames) record.
 *
 * See the abstract parent class for common methods setDBInfo() and getDBInfo().
 *
 * @author Robbie Hott
 *        
 */
class Place extends AbstractData {

    /**
     * Original place from CPF
     *
     * This is the original data as seen in the parsed CPF. Once a place is linked to a geo name by a human,
     * what do we do with this string? If we don't change to the empty string, it may confuse people, athough
     * once $confirmed is true, the UI could stop showing the original.
     *
     */
    private $original;

    /**
     * The original, source place data from CPF.
     *
     * From EAC-CPF tag(s):
     * 
     * place/descriptiveNote,  place/@localType, place/placeRole, placeEntry/,
     * snac:placeEntry/placeEntry
     *
     * Note that CPF <placeEntry> appear to be broader hierarchial regions for the actual geographic
     * location. The nature of XML necessiates a certain amount of copying of data from authorities for the
     * sake of convience and legibility. We don't need to copy broader geographical entities, but we do
     * denormalize enough to build the user interface. 
     * 
     * @var \snac\data\SNACControlMetadata A SCM object with all the human readable XML for CPF place and placeEntry.
     */
    private $source;

    // \snac\data\GeoTerm
    private $geoTerm;

    // move to GeoTerm.
    private $latitude;
    private $longitude;
    private $adminCode;
    // Why a code and not a id from our vocabulary?
    private $countryCode;
    private $name;
    private $geoNameId;

    /**
     * Set $source
     *
     * @var \snac\data\SNACControlMetadata $source
     */
    public function setSource(\snac\data\SNACControlMetadata $source)
    {
        $this->source = $source;
    }

    /**
     * Get $source
     *
     * @return \snac\data\SNACControlMetadata source
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set $latitude
     *
     * @var string $latitude
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    }

    /**
     * Set $longitude
     *
     * @var string $longitude
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
    }
    
    /**
     * Set $adminCode
     *
     * @var string $adminCode
     */
    public function setAdmincode($adminCode)
    {
        $this->adminCode = $adminCode;
    }

    /**
     * Set $countryCode
     *
     * @var string $countryCode
     */
    public function setCountrycode($countryCode)
    {
        $this->countryCode = $countryCode;
    }

    /**
     * Set $name
     *
     * @var string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Set $geoNameId
     *
     * @var string $geoNameId
     */
    public function setGeonameid($geoNameId)
    {
        $this->geoNameId = $geoNameId;
    }

    /**
     * Get latitude
     *
     * @return string latitude
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Get longitude
     *
     * @return string longitude
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Get adminCode
     *
     * @return string adminCode
     */
    public function getAdmincode()
    {
        return $this->adminCode;
    }

    /**
     * Get countryCode
     *
     * @return string countryCode
     */
    public function getCountrycode()
    {
        return $this->countryCode;
    }

    /**
     * Get name
     *
     * @return string name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get geoNameId
     *
     * @return string geoNameId
     */
    public function getGeonameid()
    {
        return $this->geoNameId;
    }

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
            "note" => $this->note
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

}

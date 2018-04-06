<?php

/**
 * Geographical Term authority class file
 *
 * Contains the data class for the place entries the geo names controlled vocabulary. These objects are
 * contained in Place which is contained in Constellation.
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
 * Geographical Term
 *
 * Geographical authority term data storage class. This corresponds to table geo_place.
 *
 * This is somewhat akin to class Term. It does NOT extend class AbstractData.
 *
 * @author Robbie Hott
 * @author Tom Laudeman
 *
 */
class GeoTerm {

    /**
     * Name
     *
     * The display name of this geographical place
     *
     * @var string $name
     */
    private $name;

    /**
     * Id (database record id)
     *
     * The ID of this geographical term in SNAC's database
     *
     * @var integer $id
     */
    private $id;

    /**
     * Latitute
     *
     * The latitude of this geographical place
     *
     * @var float Latitude
     */
    private $latitude;

    /**
     * Longitude
     *
     * The longitude of this geographical place
     *
     * @var float Longitude
     */
    private $longitude;

    /**
     * Administration Code
     *
     * The administration code of this geographical place.  This is usually a string denoting
     * the state-level code of the place.
     *
     * @var string administration code
     */
    private $administrationCode;

    /**
     * Country Code
     *
     * The country code for this geographical place.  This is usually the 2-digit country code.
     *
     * @var string country code
     */
    private $countryCode;

    /**
     * Persistent Identifier from the External Vocabulary
     *
     * This is the persistent identifier, a URI, to the external controlled vocabulary source
     * for this geographical place.  For example, in GeoNames, this would be the full URI including the
     * GeoName's ID for the place that will resolve to the GeoNames page for this place.
     *
     * From EAC-CPF tag(s):
     *
     * * placeEntry/@vocabularySource
     *
     * @var string vocabulary source uri
     */
    private $uri;

    /**
     * Constructor
     *
     * Sets up the object if one is passed in as a parameter to the constuctor as an associative array.
     *
     * @param string[] $data A list of data suitable for fromArray(). This exists for use by internal code to
     * send objects around the system, not for generally creating a new object.
     *
     *
     */
    public function __construct($data = null) {
        if ($data != null)
            $this->fromArray($data);
    }


    /**
     * Returns this object's data as an associative array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = true) {
        $return = array(
            "id" => $this->id,
            "uri" => $this->uri,
            "name" => $this->name,
            "latitude" => $this->latitude,
            "longitude" => $this->longitude,
            "administrationCode" => $this->administrationCode,
            "countryCode" => $this->countryCode
        );

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

        if (isset($data["latitude"]))
            $this->latitude = $data["latitude"];
        else
            $this->latitude = null;

        if (isset($data["longitude"]))
            $this->longitude = $data["longitude"];
        else
            $this->longitude = null;

        if (isset($data["administrationCode"]))
            $this->administrationCode = $data["administrationCode"];
        else
            $this->administrationCode = null;

        if (isset($data["countryCode"]))
            $this->countryCode = $data["countryCode"];
        else
            $this->countryCode = null;

        if (isset($data["name"]))
            $this->name = $data["name"];
        else
            $this->name = null;

        if (isset($data["id"]))
            $this->id = $data["id"];
        else
            $this->id = null;

        if (isset($data["uri"]))
            $this->uri = $data["uri"];
        else
            $this->uri = null;

        return true;
    }

    /**
     * Set the latitude
     *
     * @param float $lat latitude
     */
    public function setLatitude($lat) {

        $this->latitude = $lat;
    }

    /**
     * Set the longitude
     *
     * @param float $lon longitude
     */
    public function setLongitude($lon) {

        $this->longitude = $lon;
    }

    /**
     * Set the administration code
     *
     * @param string $code administration code
     */
    public function setAdministrationCode($code) {

        $this->administrationCode = $code;
    }

    /**
     * Set the country code
     *
     * @param string $code country code
     */
    public function setCountryCode($code) {

        $this->countryCode = $code;
    }

    /**
     * Set vocabularySource (alias of setURI)
     *
     * @param string $source vocabulary source
     */
    public function setVocabularySource($source) {

        $this->setURI($source);
    }


    /**
     * Set URI
     *
     * Set the canonical URI for this geographical place from its controlled vocabulary
     *
     * @param string $uri canonical URI
     */
    public function setURI($uri) {

        $this->uri = $uri;
    }

    /**
     * Set name
     *
     * @param string $name Name of this place
     */
    public function setName($name) {

        $this->name = $name;
    }

    /**
     * Set id
     *
     * @param string $id Id of this place
     */
    public function setID($id) {

        $this->id = $id;
    }

    /**
     * Get the Longitude
     *
     * @return float longitude
     */
    public function getLongitude() {
        return $this->longitude;
    }

    /**
     * Get the Latitude
     *
     * @return float latitude
     */
    public function getLatitude() {
        return $this->latitude;
    }

    /**
     * Get the Administration Code
     *
     * @return string administration code
     */
    public function getAdministrationCode() {
        return $this->administrationCode;
    }

    /**
     * Get the Country Code
     *
     * Get the 2-character country code
     *
     * @return string country code
     */
    public function getCountryCode() {
        return $this->countryCode;
    }

    /**
     * Get the Vocabulary Source
     *
     * This is an alias for getURI
     *
     * @return string URI for the vocabulary source
     */
    public function getVocabularySource() {
        return $this->getURI();
    }

    /**
     * Get the URI
     *
     * Get the canonical URI for this geographical term
     *
     * @return string URI
     */
    public function getURI() {
        return $this->uri;
    }

    /**
     * Get name
     *
     * @return string $name  Name of this place
     */
    public function getName() {

        return $this->name;
    }

    /**
     * Get id
     *
     * @return string $id Id of this place
     */
    public function getID() {

        return $this->id;
    }


    /**
     * is Equal
     *
     * Checks whether the given parameter is the same as this object. If
     * the IDs match, then that is taken as priority above any other data.  Else,
     * everything must match.
     *
     * @param \snac\data\GeoTerm $other the Other Term object
     * @return boolean true if equal, false otherwise
     */
    public function equals($other) {
        // Don't consider it if it's not a GeoTerm object
        if ($other != null && $other instanceOf \snac\data\GeoTerm) {
            // Check IDs first
            if ($other->getID() != null && $this->getID() != null) {
                if ($other->getID() == $this->getID())
                    return true;
                    else
                        // If they both have IDs, but they are different, no match
                        return false;
            }

            if ($this->getName() == $other->getName() &&
                    $this->getURI() == $other->getURI() &&
                    $this->getVocabularySource() == $other->getVocabularySource() &&
                    $this->getLatitude() == $other->getLatitude() &&
                    $this->getLongitude() == $other->getLongitude() &&
                    $this->getAdministrationCode() == $other->getAdministrationCode() &&
                    $this->getCountryCode() == $other->getCountryCode()) {
                        return true;
                    }
        }
        return false;
    }

}

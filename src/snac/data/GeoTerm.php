<?php

/**
 * Geo Term authority class
 *
 * Contains the data class for the place entries the geo names controlled vocabulary. These objects are
 * contained in Place which is contained in Constellation.
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
 * Geo Term authority aka geonames
 *
 * Geo authority data storage class. This corresponds to table geo_place. At least initially all the data
 * comes from geonames.
 *
 * This is somewhat akin to class Term. It does not extend class AbstractData.
 *
 * @author Robbie Hott
 * @author Tom Laudeman
 *        
 */
class GeoTerm {

    /* 
     * <geonameid>6252001</geonameid>
     * <name>United States</name>
     * <asciiname>United States</asciiname>
     * <alternatenames>
     * <alt>ABD</alt>
     * <coords><latitude>39.76</latitude>
     * <longitude>-98.5</longitude></coords>
     * <feature_class>A</feature_class>
     * <feature_code>PCLI</feature_code>
     * <country_code>US</country_code>
     * <cc2></cc2>
     * <admin1>00</admin1>
     * <admin2></admin2>
     * <admin3></admin3>
     * <admin4></admin4>
     * <population>310232863</population>
     * <elevation></elevation>
     * <gtopo30>543</gtopo30>
     * <timezone></timezone>
     * <modification>2012-01-30</modification>
     */

    /**
     * Name
     *
     * Is this the <place> or <placeEntry> value?
     *
     * @var string $name
     */ 
    private $name;

    /**
     * Id (database record id)
     * @var integer $id
      */ 
    private $name;

    /**
     * From EAC-CPF tag(s):
     * 
     * * placeEntry/@latitude
     * 
     * @var float Latitude
     */
    private $latitude;

    /**
     * From EAC-CPF tag(s):
     * 
     * * placeEntry/@longitude
     * 
     * @var float Longitude
     */
    private $longitude;

    /**
     * From EAC-CPF tag(s):
     * 
     * * placeEntry/@administrationCode
     * 
     * @var string administration code
     */
    private $administrationCode;

    /**
     * From EAC-CPF tag(s):
     * 
     * * placeEntry/@countryCode
     * 
     * @var string country code
     */
    private $countryCode;

    /**
     *
     * Vocabulary source aka geonameid
     *
     * The persistent id. This is geonameid for geoname, or vocabularySource for AnF geo auth records.
     *
     * Since this is 1:1 to each place controlled vocabulary, it seems to belong in table geo_place, although
     *
     * Seems to be place/@vocabularySource. If I read this right in EACCPFParser.php, get the attributes, call
     * setVocabularySource() with the attribute "vocabularySource".
     * 
     * $plAtts = $this->getAttributes($placeTag);
     * $placeEntry->setVocabularySource($plAtts["vocabularySource"]);
     *
     * This appears to be an AnF only feature:
     *
     * <placeEntry localType="voie" vocabularySource="d3nzbt224g-1wpyx0m9bwiae">louis-le-grand (rue)</placeEntry>
     *
     * And also it is apparently the Geonames URL from match attempts:
     *
     * <snac:placeEntryLikelySame administrationCode="00" certaintyScore="1.0" countryCode="DE"
     * latitude="51.5" longitude="1 0.5" vocabularySource="http://www.geonames.org/2921044">Federal Republic
     * of Germany</snac:placeEntryLikelySame>
     * 
     * Unclear if this belongs here. This is apparently a persistent identifier for a geo authority id, but
     * not necessarily geonames. That's fine because the GeoAuth class is ostensibly a generalized authority, and not
     * specifically geonames.
     *
     * From EAC-CPF tag(s):
     * 
     * * placeEntry/@vocabularySource
     * 
     * @var string vocabulary source (href)
     */
    private $vocabularySource;

    /**
     * Constructor.  See the abstract parent class for common methods setDBInfo() and getDBInfo().
     *
     * @param string[] $data A list of data suitable for fromArray(). This exists for use by internal code to
     * send objects around the system, not for generally creating a new object.
     *
     *
     * @return snac\data\PlaceEntry object
     * 
     */
    public function __construct($data = null) {
        $this->maybeSame = array ();
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
            "dataType" => "PlaceEntry",
            "latitude" => $this->latitude,
            "longitude" => $this->longitude,
            "administrationCode" => $this->administrationCode,
            "countryCode" => $this->countryCode,
            "name" => $this->name,
            "id" => $this->id,
            "vocabularySource" => $this->vocabularySource
        );
        foreach ($this->maybeSame as $i => $placeEntry)
            $return["maybeSame"][$i] = $placeEntry->toArray($shorten);
            
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
        if (!isset($data["dataType"]) || $data["dataType"] != "PlaceEntry")
            return false;

        parent::fromArray($data);

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

        if (isset($data["vocabularySource"]))
            $this->vocabularySource = $data["vocabularySource"];
        else
            $this->vocabularySource = null;

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
     * Set vocabularySource
     * 
     * @param string $source vocabulary source
     */
    public function setVocabularySource($source) {

        $this->vocabularySource = $source;
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
     * Get name
     * 
     * @return string $name  Name of this place
     */
    public function getName() {

        return $this->name;
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
     * Get id
     * 
     * @return string $id Id of this place
     */
    public function getID() {

        return $this->id;
    }

    

}

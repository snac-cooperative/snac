<?php

/**
 * PlaceEntry File
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
 * Place Entry data storage class. This is a denormalized form of table place_link and table geo_place. If
 * there are 3 place_link records there will be 3 of these PlaceEntry objects. To save the UI from having to
 * go back to the database, we denomormalize by adding geo_place fields to each PlaceEntry object as well.
 *
 * These Place Entry objects are inside Place objects which are inside Constellation objects.
 *
 * Feb 1 2016 Move $original and associated setter to Place. Place and original are 1:1. Place and PlaceEntry
 * are 1:many.
 *
 * @author Robbie Hott
 *        
 */
class PlaceEntry extends AbstractData {

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
     * From EAC-CPF tag(s):
     * 
     * * placeEntry/@vocabularySource
     * 
     * @var string vocabulary source (href)
     */
    private $vocabularySource;

    /**
     * From EAC-CPF tag(s):
     * 
     * * placeEntry/@certaintyScore
     * 
     * @var float certainty score of this entry
     */
    private $certaintyScore;

    /**
     * From EAC-CPF tag(s):
     * 
     * * snac:placeEntry/placeEntryBestMaybeSame
     * * snac:placeEntry/placeEntryLikelySame
     * 
     * @var \snac\data\PlaceEntry Best match for this place entry (BestMaybeSame or LikelySame)
     */
    private $bestMatch;

    /**
     * From EAC-CPF tag(s):
     * 
     * * snac:placeEntry/placeEntryMaybeSame
     * 
     * @var \snac\data\PlaceEntry[] Alternate matches for this place entry
     */
    private $maybeSame;

    /**
     * From EAC-CPF tag(s):
     * 
     * * placeEntry/@localType
     * 
     * @var string type of the place entry
     */
    private $type;

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
            "vocabularySource" => $this->vocabularySource,
            "certaintyScore" => $this->certaintyScore,
            "bestMatch" => $this->bestMatch == null ? null : $this->bestMatch->toArray($shorten),
            "maybeSame" => array(),
            "type" => $this->type
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

        if (isset($data["vocabularySource"]))
            $this->vocabularySource = $data["vocabularySource"];
        else
            $this->vocabularySource = null;

        if (isset($data["certaintyScore"]))
            $this->certaintyScore = $data["certaintyScore"];
        else
            $this->certaintyScore = null;

        if (isset($data["bestMatch"]))
            $this->bestMatch = new PlaceEntry($data["bestMatch"]);
        else
            $this->bestMatch = null;

        if (isset($data["type"]))
            $this->type = $data["type"];
        else
            $this->type = null;

        $this->maybeSame = array();
        if (isset($data["maybeSame"])) {
            foreach ($data["maybeSame"] as $i => $entry)
                $this->maybeSame[$i] = new PlaceEntry($entry);
        }

        if (isset($data["note"]))
            $this->note = $data["note"];
        else
            $this->note = null;

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
     * Set the vocabulary source, a string. Since this is 1:1 to each place controlled vocabulary, perhaps
     * this should be in table geo_place, although it would still be here in the php because we denormalize
     * place_link and geo_place to create PlaceEntry objects.
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
     * @param string $source vocabulary source
     */
    public function setVocabularySource($source) {

        $this->vocabularySource = $source;
    }

    /**
     * Set the certainty score
     * 
     * @param float $score certainty score
     */
    public function setCertaintyScore($score) {

        $this->certaintyScore = $score;
    }

    /**
     * Set the best-matching place entry
     *
     * Feb 1 2016 Is this the single best match? If so, in an implementation of flat (non-nested lists of)
     * PlaceEntry it seems like this should be in class Place. Perhaps it is best to simply go with the
     * convention that the PlaceEntry list is always sorted descending by certainty score. The single best can
     * also have a isBest property with getter and setter. The getBest would be in class Place. There would be
     * no setter since "set" is done by convention of adding PlaceEntries in descending score order.
     * 
     * @param \snac\data\PlaceEntry $match best matching place entry
     */
    public function setBestMatch($match) {

        $this->bestMatch = $match;
    }

    /**
     * Add an alternate place entry that might be the same as this one.
     *
     * Feb 1 2016 It seems to make more sense to create a new PlaceEntry for every possible alternate. As
     * opposed to nested lists of PlaceEntry objects.
     * 
     * @param \snac\data\PlaceEntry $match alternate matching place entry
     */
    public function addMaybeSame($match) {

        array_push($this->maybeSame, $match);
    }

    /**
     * Set the local type of the place entry. Where does this come from?
     * 
     * @param string $type type
     */
    public function setType($type) {

        $this->type = $type;
    }
}

<?php

/**
 * PlaceEntry File
 *
 * Contains the data class for the place entries
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
 * Place Entry data storage class
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
     * * placeEntry/
     * * snac:placeEntry/placeEntry
     * 
     * @var string original text within this entry
     */
    private $original;

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
            "original" => $this->original,
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

        if (isset($data["original"]))
            $this->original = $data["original"];
        else
            $this->original = null;

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
     * Set the vocabulary source
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
     * Set the original place name
     * 
     * @param string $original original place name
     */
    public function setOriginal($original) {

        $this->original = $original;
    }

    /**
     * Set the best-matching place entry
     * 
     * @param \snac\data\PlaceEntry $match best matching place entry
     */
    public function setBestMatch($match) {

        $this->bestMatch = $match;
    }

    /**
     * Add an alternate place entry that might be the same as this one
     * 
     * @param \snac\data\PlaceEntry $match alternate matching place entry
     */
    public function addMaybeSame($match) {

        array_push($this->maybeSame, $match);
    }

    /**
     * Get the best match place entry
     *
     * @return \snac\data\PlaceEntry The best match place entry
     */
    public function getBestMatch() {
        return $this->bestMatch;
    }

    /**
     * Get the list of possible matches
     *
     * @return \snac\data\PlaceEntry[] The list of possible matches for this placeEntry
     */
    public function getMaybeSames() {
        return $this->maybeSame;
    }

    /**
     * Set the local type of the place entry
     * 
     * @param string $type type
     */
    public function setType($type) {

        $this->type = $type;
    }
}

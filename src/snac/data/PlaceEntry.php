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
class PlaceEntry {

    /**
     *
     * @var float Latitude
     */
    private $latitude;

    /**
     *
     * @var float Longitude
     */
    private $longitude;

    /**
     *
     * @var string administration code
     */
    private $administrationCode;

    /**
     *
     * @var string country code
     */
    private $countryCode;

    /**
     *
     * @var string vocabulary source (href)
     */
    private $vocabularySource;

    /**
     *
     * @var float certainty score of this entry
     */
    private $certaintyScore;

    /**
     *
     * @var string original text within this entry
     */
    private $original;

    /**
     *
     * @var \snac\data\PlaceEntry Best match for this place entry (BestMaybeSame or LikelySame)
     */
    private $bestMatch;

    /**
     *
     * @var \snac\data\PlaceEntry Alternate matches for this place entry
     */
    private $maybeSame;

    /**
     *
     * @var string type of the place entry
     */
    private $type;

    /**
     * Constructor
     */
    public function __construct() {

        $this->maybeSame = array ();
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
     * Set the local type of the place entry
     * 
     * @param string $type type
     */
    public function setType($type) {

        $this->type = $type;
    }
}
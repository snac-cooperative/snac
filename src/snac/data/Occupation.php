<?php
/**
 * Occupation File
 *
 * Contains the data class for the occupations
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
 * Occupation Class
 * 
 * Stores the data related to an individual Constellation's occupation.
 * 
 * @author Robbie Hott
 *
 */
class Occupation extends AbstractData {
    
    /**
     * @var string Occupation controlled vocabulary term
     */
    private $term = null;
    /**
     * @var string Vocabulary source for the occupation
     */
    private $vocabularySource = null;
    /**
     * @var \snac\data\SNACDate Date range for the occupation
     */
    private $dates = null;
    /**
     * @var string Note attached to occupation
     */
    private $note = null;
    
    /**
     * Returns this object's data as an associative array
     *
     * @return string[][] This objects data in array form
     */
    public function toArray() {
        $return = array(
            "dataType" => "Occupation",
            "term" => $this->term,
            "vocabularySource" => $this->vocabularySource,
            "dates" => $this->dates == null ? null : $this->dates->toArray(),
            "note" => $this->note
        );
        return $return;
    }

    /**
     * Replaces this object's data with the given associative array
     *
     * @param string[][] $data This objects data in array form
     * @return boolean true on success, false on failure
     */
    public function fromArray($data) {
        if (!isset($data["dataType"]) || $data["dataType"] != "Occupation")
            return false;

        if (isset($data["term"]))
            $this->term = $data["term"];
        else
            $this->term = null;

        if (isset($data["vocabularySource"]))
            $this->vocabularySource = $data["vocabularySource"];
        else
            $this->vocabularySource = null;

        if (isset($data["dates"]))
            $this->dates = new SNACDate($data["dates"]);
        else
            $this->dates = null;

        if (isset($data["note"]))
            $this->note = $data["note"];
        else
            $this->note = null;

        return true;
    }
    
    /**
     * Set the occupation controlled vocabulary name
     * 
     * @param string $term The occupation term
     */
    public function setTerm($term) {
        $this->term = $term;
    }
    
    /**
     * Set the date range
     * @param \snac\data\SNACDate $date Date object for the range
     */
    public function setDateRange($date) {
        $this->dates = $date;
        
    }
    
    /**
     * Set the vocabulary source
     * @param string $vocab Vocabulary source string
     */
    public function setVocabularySource($vocab) {
        $this->vocabularySource = $vocab;
    }
    
    /**
     * Set the descriptive note for this occupation
     * @param string $note Descriptive note string
     */
    public function setNote($note) {
        $this->note = $note;
    }
    
    
}

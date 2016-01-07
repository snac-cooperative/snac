<?php

/**
 * Snac Function File
 *
 * Contains the data class for functions
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
 * Function data storage class
 *
 *  See the abstract parent class for common methods setDBInfo() and getDBInfo().
 * 
 * @author Robbie Hott
 *        
 */
class SNACFunction extends AbstractData {

    /**
     * From EAC-CPF tag(s):
     * 
     * * function/term
     * 
     * @var string Function controlled vocabulary term
     */
    private $term;

    /**
     * From EAC-CPF tag(s):
     * 
     * * function/@localType
     * 
     * @var string Type of the function
     */
    private $type;

    /**
     * From EAC-CPF tag(s):
     * 
     * * function/dateRange
     * 
     * @var \snac\data\SNACDate Date range of the function. As far as I can tell, this is a single date, so
     * ignore the pluralization.
     */
    private $dates;

    /**
     * From EAC-CPF tag(s):
     * 
     * * function/descriptiveNote
     * 
     * @var string Descriptive note for the function
     */
    private $note;

    /**
     * From EAC-CPF tag(s):
     * 
     * * function/term/@vocabularySource
     * 
     * @var string Vocabulary source for the function
     */
    private $vocabularySource = '';

    /**
     * getter for $this->term
     *
     * * function/term
     * 
     * @return string Function controlled vocabulary term
     *
     */
    function getTerm()
    {
        return $this->term;
    }
    
    /**
     * getter for $this->type
     *
     * * function/@localType
     * 
     * @return string Type of the function
     *
     */
    function getType()
    {
        return $this->type;
    }

    /**
     * getter for $this->dates. This is only one date, not an array.
     *
     * * function/dateRange
     * 
     * @return \snac\data\SNACDate Date range of the function. One date, not a list of dates.
     *
     */
    function getDates()
    {
        return $this->dates;
    }

    /**
     * getter for $this->note
     *
     * * function/descriptiveNote
     * 
     * @return string Descriptive note for the function
     *
     */
    function getNote()
    {
        return $this->note;
    }

    /**
     * getter for $this->vocabularySource
     *
     * * function/term/@vocabularySource
     * 
     * @return string Vocabulary source for the function
     *
     */
    function getVocabularySource()
    {
        return $this->vocabularySource;
    }

    /**
     * Returns this object's data as an associative array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = true) {
        $return = array(
            "dataType" => "SNACFunction",
            "term" => $this->term,
            "type" => $this->type,
            "dates" => $this->dates == null ? null : $this->dates->toArray($shorten),
            "vocabularySource" => $this->vocabularySource,
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
        if (!isset($data["dataType"]) || $data["dataType"] != "SNACFunction")
            return false;

        parent::fromArray($data);

        if (isset($data["term"]))
            $this->term = $data["term"];
        else
            $this->term = null;

        if (isset($data["type"]))
            $this->type = $data["type"];
        else
            $this->type = null;

        if (isset($data["dates"]))
            $this->dates = new SNACDate($data["dates"]);
        else
            $this->dates = null;

        if (isset($data["vocabularySource"]))
            $this->vocabularySource = $data["vocabularySource"];
        else
            $this->vocabularySource = null;

        if (isset($data["note"]))
            $this->note = $data["note"];
        else
            $this->note = null;

        return true;

    }

    /**
     * Set the term of this function (controlled vocabulary)
     * 
     * @param string $term term
     */
    public function setTerm($term) {

        $this->term = $term;
    }

    /**
     * Set the type of this function
     * 
     * @param string $type type
     */
    public function setType($type) {

        $this->type = $type;
    }

    /**
     * Set the date range
     *
     * @param \snac\data\SNACDate $date Date object for the range
     */
    public function setDateRange($date) {

        $this->dates = $date;
    }

    /**
     * Set the vocabulary source
     *
     * @param string $vocab Vocabulary source string
     */
    public function setVocabularySource($vocab) {

        $this->vocabularySource = $vocab;
    }

    /**
     * Set the descriptive note for this function
     *
     * @param string $note Descriptive note string
     */
    public function setNote($note) {

        $this->note = $note;
    }
}

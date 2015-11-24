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
     * From EAC-CPF tag(s):
     * 
     * * occupation/term
     * 
     * @var string Occupation controlled vocabulary term
     */
    private $term = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * occupation/term/@vocabularySource
     *
     * This example for <function> is similar to <occupation>
     * 
     * <function>
     *    <term vocabularySource="d3nyui3o8w--11y7jgy8q3wnt">notaire à paris</term>
     *    <dateRange>
     *        <fromDate standardDate="1578-01-01">1er janvier 1578</fromDate>
     *        <toDate standardDate="1613-10-22">22 octobre 1613</toDate>
     *    </dateRange>
     * </function>
     * 
     *
     * The vocabulary source. These values come from a controlled vocabulary, but so far, they are not
     * well defined. For example: d699msirr1g-3naumnfaswc
     *
     * 
     * @var string Vocabulary source for the occupation
     */
    private $vocabularySource = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * occupation/dateRange
     * 
     * @var \snac\data\SNACDate Date range for the occupation
     */
    private $dates = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * occupation/descriptiveNote
     * 
     * @var string Note attached to occupation
     */
    private $note = null;

    public function getTerm()
    {
        return $this->term;
    }

    public function getVocabularySource()
    {
        return $this->vocabularySource;
    }

    public function getDates()
    {
        if ($this->dates)
        {
            return $this->dates;
        }
        else
        {
            return array();
        }
    }

    public function getNote()
    {
        return $this->note;
    }


    
    /**
     * Returns this object's data as an associative array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = true) {
        $return = array(
            "dataType" => "Occupation",
            "term" => $this->term,
            "vocabularySource" => $this->vocabularySource,
            "dates" => $this->dates == null ? null : $this->dates->toArray($shorten),
            "note" => $this->note
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
     * Set the date range. If the supplied arg is false for any reason (and any definition of 'false'), then
     * set the private var to an empty SNACDate. Try checking get_class().
     *
     * 
     * @param \snac\data\SNACDate $date Date object for the range
     */
    public function setDateRange($date) {
        if (! $date or get_class($date) != 'SNACDate')
        {
            $this->dates = new \snac\data\SNACDate();
        }
        else
        {
            $this->dates = $date;
        }
    }
    
    /**
     * Set the vocabulary source. These values come from a controlled vocabulary, but so far, they are not
     * well defined. For example: d699msirr1g-3naumnfaswc
     * 
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

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
 *  See the abstract parent class for common methods setDBInfo() and getDBInfo().
 * 
 * Stores the data related to an individual Constellation's occupation.
 * 
 * @author Robbie Hott
 *
 */
class Occupation extends AbstractData {
    
    /**
     * Occupation Term
     * 
     * From EAC-CPF tag(s):
     * 
     * * occupation/term
     * 
     * @var \snac\data\Term Occupation controlled vocabulary term
     */
    private $term = null;

    /**
     * Vocabulary Source
     * 
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
     * Descriptive Note
     * 
     * From EAC-CPF tag(s):
     * 
     * * occupation/descriptiveNote
     * 
     * @var string Note attached to occupation
     */
    private $note = null;

    /**
     * Constructor for the class. See the abstract parent class for common methods setDBInfo() and getDBInfo().
     *
     * A setMaxDateCount(1) means a single date object.
     *
     * @param string[] $data A list of data suitable for fromArray(). This exists for use by internal code to
     * send objects around the system, not for generally creating a new object. Normal use is to call the
     * constructor without an argument, get an empty class and use the setters to fill in the properties.
     *
     * @return snac\data\Occupation And occuption object
     * 
     */
    public function __construct($data = null) {
        $this->setMaxDateCount(1);
        parent::__construct($data);
    }

    /**
     * Get the Occupation Term for this occupation 
     *
     * @return \snac\data\Term Occupation controlled vocabulary term
     * 
     */ 
    public function getTerm()
    {
        return $this->term;
    }

    /**
     * Get the vocabulary source for this occupation
     *
     * @return string Vocabulary source for the occupation
     */ 
    public function getVocabularySource()
    {
        return $this->vocabularySource;
    }

    /**
     * Get the human readable descriptive note attached to this occupation 
     *
     * @return string Note attached to occupation
     *
     */
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
            "term" => $this->term == null ? null : $this->term->toArray($shorten),
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
        if (!isset($data["dataType"]) || $data["dataType"] != "Occupation")
            return false;

        parent::fromArray($data);

        if (isset($data["term"]) && $data["term"] != null)
            $this->term = new Term($data["term"]);
        else
            $this->term = null;

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
     * Set the occupation controlled vocabulary term
     * 
     * @param \snac\data\Term $term The occupation term
     */
    public function setTerm($term) {
        $this->term = $term;
    }
    
    
    /**
     * Set the vocabulary source
     * 
     * These values come from a controlled vocabulary, but so far, they are not
     * well defined. For example: d699msirr1g-3naumnfaswc
     * 
     * @param string $vocab Vocabulary source string
     */
    public function setVocabularySource($vocab) {
        $this->vocabularySource = $vocab;
    }
    
    /**
     * Set the descriptive note for this occupation
     * 
     * @param string $note Descriptive note string
     */
    public function setNote($note) {
        $this->note = $note;
    }

    /**
     *
     * {@inheritDoc}
     *
     * @param \snac\data\Occupation $other Other object
     * @param boolean $strict optional Whether or not to check id, version, and operation
     * @return boolean true on equality, false otherwise
     *       
     * @see \snac\data\AbstractData::equals()
     */
    public function equals($other, $strict = true) {

        if ($other == null || ! ($other instanceof \snac\data\Occupation))
            return false;
        
        if (! parent::equals($other, $strict))
            return false;
        
        if ($this->getVocabularySource() != $other->getVocabularySource())
            return false;
        if ($this->getNote() != $other->getNote())
            return false;
        
        if (($this->getTerm() != null && !$this->getTerm()->equals($other->getTerm())) ||
                ($this->getTerm() == null && $other->getTerm() != null))
            return false;
        
        return true;
    }
    
    
}

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
     * Vocabulary Term
     * 
     * From EAC-CPF tag(s):
     * 
     * * function/term
     * 
     * @var \snac\data\Term Function controlled vocabulary term
     */
    private $term;

    /**
     * Type of Function
     * 
     * From EAC-CPF tag(s):
     * 
     * * function/@localType
     * 
     * @var \snac\data\Term Type of the function
     */
    private $type;


    /**
     * Descriptive Note
     * 
     * From EAC-CPF tag(s):
     * 
     * * function/descriptiveNote
     * 
     * @var string Descriptive note for the function
     */
    private $note;

    /**
     * Vocabulary Source
     * 
     * From EAC-CPF tag(s):
     * 
     * * function/term/@vocabularySource
     * 
     * @var string Vocabulary source for the function
     */
    private $vocabularySource = '';

    /**
     * Constructor
     *
     * Functions may only have one date object.
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
     * Get the Term for this function 
     *
     * * function/term
     * 
     * @return \snac\data\Term Function controlled vocabulary term
     *
     */
    public function getTerm()
    {
        return $this->term;
    }
    
    /**
     * Get the type of this function 
     *
     * * function/@localType
     * 
     * @return \snac\data\Term Type of the function
     *
     */
    public function getType()
    {
        return $this->type;
    }


    /**
     * Get Descriptive Note
     * 
     * Get the human-readable descriptive note for this function 
     *
     * * function/descriptiveNote
     * 
     * @return string Descriptive note for the function
     *
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Get the vocabulary source
     *
     * * function/term/@vocabularySource
     * 
     * @return string Vocabulary source for the function
     *
     */
    public function getVocabularySource()
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
            "term" => $this->term == null ? null : $this->term->toArray($shorten),
            "type" => $this->type == null ? null : $this->type->toArray($shorten),
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

        if (isset($data["term"]) && $data["term"] != null)
            $this->term = new Term($data["term"]);
        else
            $this->term = null;

        if (isset($data["type"]) && $data["type"] != null)
            $this->type = new Term($data["type"]);
        else
            $this->type = null;

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
     * Set the term of this function
     * 
     * This comes from the controlled vocabulary
     * 
     * @param \snac\data\Term $term term
     */
    public function setTerm($term) {

        $this->term = $term;
    }

    /**
     * Set the type of this function
     * 
     * @param \snac\data\Term $type type
     */
    public function setType($type) {

        $this->type = $type;
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

    /**
     *
     * {@inheritDoc}
     *
     * @param \snac\data\SNACFunction $other Other object
     *       
     * @see \snac\data\AbstractData::equals()
     */
    public function equals($other, $strict = true) {

        if ($other == null || ! ($other instanceof \snac\data\SNACFunction))
            return false;
        
        if (! parent::equals($other, $strict))
            return false;
        
        if ($this->getVocabularySource() != $other->getVocabularySource())
            return false;
        if ($this->getNote() != $other->getNote())
            return false;
        
        if (($this->getTerm() != null && ! $this->getTerm()->equals($other->getTerm())) ||
                 ($this->getTerm() == null && $other->getTerm() != null))
            return false;
        if (($this->getType() != null && ! $this->getType()->equals($other->getType())) ||
                 ($this->getType() == null && $other->getType() != null))
            return false;
        
        return true;
    }
}

<?php
/**
 * Language File
 *
 * Contains the data class for the languages
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
 * Language Class
 *
 *  See the abstract parent class for common methods setDBInfo() and getDBInfo().
 * 
 * Stores the data related to an individual Constellation's language and script.
 * 
 * @author Robbie Hott
 *
 */
class Language extends AbstractData {
    
    /**
     * @var \snac\data\Term Language, a controlled vocabulary term object
     */
    private $language = null;

    /**
     * @var \snac\data\Term Script, a controlled vocabulary term object.
     */
    private $script = null;

    /**
     * @var string Vocabulary source for the language. A simple string.
     */
    private $vocabularySource = null;

    /**
     * @var string Note attached to language. A simple string.
     */
    private $note = null;
    
    /**
     * Constructor
     */
    public function __construct($data = null) {
        $this->setMaxDateCount(0);
        parent::__construct($data);
    }

    /**
     * Get the language controlled vocab term 
     *
     * @return \snac\data\Term Language controlled vocabulary term
     * 
     */ 
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Get the script controlled vocab term
     *
     * @return \snac\data\Term Script controlled vocabulary term
     * 
     */ 
    public function getScript()
    {
        return $this->script;
    }

    /**
     * Get the vocabulary source for this language
     *
     * @return string Vocabulary source for the language
     */ 
    public function getVocabularySource()
    {
        return $this->vocabularySource;
    }

    /**
     * Get the descriptive note
     *
     * @return string Note attached to occupation
     *
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Check to see if this language is empty
     *
     * @return boolean true if the language has nothing set, false otherwise
     */
    public function isEmpty() {
        if ($this->language == null && $this->script == null && $this->vocabularySource == null
            && $this->note == null)
            return true;
        else
            return false;
    }

    /**
     * Returns this object's data as an associative array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = true) {
        $return = array(
            "dataType" => "Language",
            "language" => $this->language == null ? null : $this->language->toArray($shorten),
            "script" => $this->script == null ? null : $this->script->toArray($shorten),
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
        if (!isset($data["dataType"]) || $data["dataType"] != "Language")
            return false;

        parent::fromArray($data);

        if (isset($data["language"]))
            $this->language = new Term($data["language"]);
        else
            $this->language = null;

        if (isset($data["script"]))
            $this->script = new Term($data["script"]);
        else
            $this->script = null;

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
     * Set the language controlled vocabulary name
     * 
     * @param \snac\data\Term $language The language term
     */
    public function setLanguage(\snac\data\Term $language) {
        $this->language = $language;
    }
    
    /**
     * Set the script controlled vocabulary name
     * 
     * @param \snac\data\Term $script The script term
     */
    public function setScript(\snac\data\Term $script) {
        $this->script = $script;
    }
    
    /**
     * Set the vocabulary source. 
     *
     * @param string $vocab Vocabulary source string
     */
    public function setVocabularySource($vocab) {
        $this->vocabularySource = $vocab;
    }
    
    /**
     * Set the descriptive note for this language
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
     * @param \snac\data\Language $other Other object
     *       
     * @see \snac\data\AbstractData::equals()
     */
    public function equals($other, $strict = true) {

        if ($other == null || ! ($other instanceof \snac\data\Language))
            return false;
        
        if (! parent::equals($other, $strict))
            return false;
        
        if ($this->getVocabularySource() != $other->getVocabularySource())
            return false;
        if ($this->getNote() != $other->getNote())
            return false;
                
        if ( ($this->getScript() != null && !$this->getScript()->equals($other->getScript())) ||
                ($this->getScript() == null && $other->getScript() != null))
            return false;
        if ( ($this->getLanguage() != null && !$this->getLanguage()->equals($other->getLanguage())) ||
                ($this->getLanguage() == null && $other->getLanguage() != null))
            return false;

        
        return true;
    }
    
}

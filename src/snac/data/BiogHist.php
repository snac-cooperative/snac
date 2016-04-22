<?php

/**
 * Snac BiogHist File
 *
 * Contains the data class for biographical histories
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
 * BiogHist data storage class
 *
 * @author Robbie Hott
 *        
 */
class BiogHist extends AbstractData {

    /**
     * 
     * @var \snac\data\Language The language this biogHist was written in
     */
    private $language;

    /**
     * @var string Text/XML contents of this biogHist. 
     */
    private $text;

    /**
     * Constructor. 
     *
     * Mostly this exists to call setMaxDateCount() to a reasonable number for this class.
     *
     * @param string[] $data optional Array with data to populate this object
     */ 
    public function __construct($data = null) {
        $this->setMaxDateCount(0);
        parent::__construct($data);
    }


    /**
     * Get the language this biogHist was written in
     * 
     * @return \snac\data\Language Language of this BiogHist
     *
     */
    public function getLanguage()
    {
        return $this->language;
    }
    
    /**
     * Get the text/xml of this biogHist
     *
     * @return string The full biogHist 
     *
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Returns this object's data as an associative array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = true) {
        $return = array(
            "dataType" => "BiogHist",
            "language" => $this->language == null ? null : $this->language->toArray($shorten),
            "text" => $this->text
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
        if (!isset($data["dataType"]) || $data["dataType"] != "BiogHist")
            return false;

        parent::fromArray($data);

        if (isset($data["language"]) && $data["language"] != null)
            $this->language = new Language($data["language"]);
        else
            $this->language = null;

        if (isset($data["text"]))
            $this->text = $data["text"];
        else
            $this->text = null;

        return true;
    }

    /**
     * Set the language of this BiogHist.
     * 
     * @param \snac\data\Language $language the language of this BiogHist
     */
    public function setLanguage($language) {

        $this->language = $language;
    }

    /**
     * Set the text/xml of this BiogHist
     * 
     * @param string $text The full text/xml of this biogHist
     */
    public function setText($text) {

        $this->text = $text;
    }

    /**
     *
     * {@inheritDoc}
     *
     * @param \snac\data\BiogHist $other Other object
     * @param boolean $strict optional Whether or not to check id, version, and operation
     * @return boolean true on equality, false otherwise
     *       
     * @see \snac\data\AbstractData::equals()
     */
    public function equals($other, $strict = true) {

        if ($other == null || !($other instanceof \snac\data\BiogHist))
            return false;
        
        if (! parent::equals($other, $strict))
            return false;
        
        if ($this->getText() != $other->getText())
            return false;
        
        if (($this->getLanguage() != null && !$this->getLanguage()->equals($other->getLanguage(), $strict)) ||
                ($this->getLanguage() == null && $other->getLanguage() != null))
            return false;
        
        return true;
    }
}

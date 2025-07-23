<?php

/**
 * Concept Term Class 
 *
 * Contains the term of a Concept. 
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2025 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\data;

/**
 * ConceptTerm
 *
 * Stores terms for Concepts. 
 *
 * @author Robbie Hott
 */
class ConceptTerm extends AbstractConceptData {

    private $text = null;
    
    private $language = null;

    public function __construct($data = null) {
        // always call the parent constructor
        parent::__construct($data);
    }

    public function getText() {
        return $this->text;
    }

    public function setText($text) {
        $this->text = $text;
    }

    public function getLanguage() {
        return $this->language;
    }

    public function setLanguage($term) {
        $this->language = $term;
    }

    /**
     * Returns this object's data as an associative array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = true) {
        $return = array(
                "dataType" => "ConceptTerm",
                "text" => $this->text,
                "language" => $this->language ? $this->language->toArray($shorten) : []
                );

        $return = array_merge($return, parent::toArray($shorten));

        return $return;
    }

    /**
     * Replaces this object's data with the given associative array
     *
     * @param string[][] $data This objects data in array form
     * @return boolean true on success, false on failure
     */
    public function fromArray($data) {
        if (!isset($data["dataType"]) || $data["dataType"] != "ConceptTerm")
            return false;

        parent::fromArray($data);

        unset($this->text);
        if (isset($data["text"]))
            $this->text = $data["text"];
        else
            $this->text = false;

        unset($this->language);
        if (isset($data["language"]))
            $this->language = new Term($data["language"]);
        else
            $this->language = null;

        return true;
    }
    

}


<?php

/**
 * Concept Relation Class 
 *
 * Contains the relationship of a Concept to another Concept. 
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2025 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\data;

/**
 * ConceptRelationship
 *
 * Stores a relation for Concepts. 
 *
 * @author Robbie Hott
 */
class ConceptRelationship extends AbstractConceptData {

    private $type = null;
    
    private $relatedConcept = null;

    public function __construct($data = null) {
        // always call the parent constructor
        parent::__construct($data);
    }

    public function getType() {
        return $this->type;
    }

    public function setType($type) {
        $this->type = $type;
    }

    public function getRelatedConcept() {
        return $this->relatedConcept;
    }

    public function setRelatedConcept($term) {
        $this->relatedConcept = $term;
    }

    /**
     * Returns this object's data as an associative array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = true) {
        $return = array(
                "dataType" => "ConceptRelationship",
                "type" => $this->type,
                "related_concept" => $this->relatedConcept ? $this->relatedConcept->toArray($shorten) : []
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
        if (!isset($data["dataType"]) || $data["dataType"] != "ConceptRelationship")
            return false;

        parent::fromArray($data);

        unset($this->type);
        if (isset($data["type"]))
            $this->type = $data["type"];
        else
            $this->type = false;

        unset($this->relatedConcept);
        if (isset($data["related_concept"]))
            $this->relatedConcept = new Concept($data["related_concept"]);
        else
            $this->relatedConcept = null;

        return true;
    }
    

}



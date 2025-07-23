<?php

/**
 * Concept Class 
 *
 * Contains the full concept for a given id.
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2025 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\data;

/**
 * Concept 
 *
 * Stores all the information related to a concept.  This is the root of a concept.
 *
 * @author Robbie Hott
 */
class Concept extends AbstractConceptData {

    private $deprecated = false;

    private $deprecatedTo = null;

    private $sources = [];

    private $relationships = [];

    private $categories = [];

    private $terms = [];

    private $preferredTerm = null;


    public function __construct($data = null) {
        // always call the parent constructor
        parent::__construct($data);
    }

    public function getDeprecated() {
        return $this->deprecated;
    }

    public function setDeprecated($dep) {
        $this->deprecated = $dep;
    }

    public function getDeprecatedTo() {
        return $this->deprecatedTo;
    }

    public function setDeprecatedTo($depTo) {
        $this->deprecatedTo = $depTo;
    }


    public function getSources() {
        return $this->sources;
    }

    public function addSource($source) {
        if ($this->sources == null) $this->sources = [];
        array_push($this->sources, $source);
    }

    public function getRelationships() {
        return $this->relationships;
    }

    public function addRelationship($relationship) {
        if ($this->relationships == null) $this->relationships = [];
        array_push($this->relationships, $relationship);
    }

    public function getCategories() {
        return $this->categories;
    }

    public function addCategory($category) {
        if ($this->categories == null) $this->categories = [];
        array_push($this->categories, $category);
    }

    public function getTerms() {
        return $this->terms;
    }
    
    public function addTerm($term) {
        if ($this->terms == null) $this->terms = [];
        array_push($this->terms, $term);
    }

    public function getPreferredTerm() {
        return $this->preferredTerm;
    }

    public function setPreferredTerm($term) {
        $this->preferredTerm = $term;
    }

    /**
     * Returns this object's data as an associative array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = false) {
        $return = array(
                "dataType" => "Concept",
                "deprecated" => $this->deprecated,
                "deprecated_to" => $this->deprecatedTo,
                "preferred_term" => $this->preferredTerm->toArray($shorten),
                "sources" => [],
                "relationships" => [],
                "categories" => [],
                "terms" => []
                );
        foreach ($this->sources as $i => $v)
            $return["sources"][$i] = $v->toArray($shorten);
        foreach ($this->relationships as $i => $v)
            $return["relationships"][$i] = $v->toArray($shorten);
        foreach ($this->categories as $i => $v)
            $return["categories"][$i] = $v->toArray($shorten);
        foreach ($this->terms as $i => $v)
            $return["terms"][$i] = $v->toArray($shorten);

        $return = array_merge($return, parent::toArray($shorten));
        if ($shorten) {
            unset($return["terms"]);
            unset($return["relationships"]);
            unset($return["sources"]);
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
        if (!isset($data["dataType"]) || $data["dataType"] != "Concept")
            return false;

        parent::fromArray($data);

        unset($this->deprecated);
        if (isset($data["deprecated"]))
            $this->deprecated = $data["deprecated"];
        else
            $this->deprecated = false;

        unset($this->deprecatedTo);
        if (isset($data["deprecatedTo"]))
            $this->deprecatedTo = $data["deprecated_to"];
        else
            $this->deprecatedTo = null;

        unset($this->sources);
        $this->sources = array();
        if (isset($data["sources"]))
            foreach ($data["sources"] as $i => $entry)
                if ($entry != null)
                    $this->sources[$i] = new ConceptSource($entry);

        unset($this->relationships);
        $this->relationships = array();
        if (isset($data["relationships"]))
            foreach ($data["relationships"] as $i => $entry)
                if ($entry != null)
                    $this->relationships[$i] = new ConceptRelationship($entry);

        unset($this->categories);
        $this->categories = array();
        if (isset($data["categories"]))
            foreach ($data["categories"] as $i => $entry)
                if ($entry != null)
                    $this->categories[$i] = new Term($entry);

        unset($this->terms);
        $this->terms = array();
        if (isset($data["terms"]))
            foreach ($data["terms"] as $i => $entry)
                if ($entry != null)
                    $this->terms[$i] = new ConceptTerm($entry);

        unset($this->preferredTerm);
        if (isset($data["preferred_term"]))
            $this->preferredTerm = new ConceptTerm($data["preferred_term"]);
        else
            $this->preferredTerm = null;

        return true;
    }


}



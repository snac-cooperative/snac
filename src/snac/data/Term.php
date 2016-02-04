<?php

/**
 * Term Class that holds simple database terms.
 *
 * Holds the information for an individual term in a controlled vocabulary. 
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @author Tom Laudeman
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\data;

/**
 * Vocabulary term data class
 *
 * This class contains the data associated with one controlled vocabulary term's data.  At first, this
 * includes database IDs and vocabulary terms.
 *
 * @author Robbie Hott
 * @author Tom Laudeman
 */
class Term {


    /**
     * @var string $type Vocabulary type of this term. Really more of a group or category.
     *
     * As of Jan 29 2016 the list includes: record_type, script_code, entity_type, event_type, name_type,
     * occupation, language_code, gender, nationality, maintenance_status, agent_type, document_role,
     * document_type, function_type, function, subject, date_type, relation_type, place_match, source_type
     * 
     */
    protected $type;

    /**
     * @var int $id The postgres vocabulary ID for this term
     */
    protected $id;

    /**
     * @var int $term The term (in any language) for this particular vocabulary term
     */
    protected $term;
    
    /**
     * @var string $uri The full URI for this controlled vocabulary term
     */
    protected $uri;
    
    /**
     * @var string $description The description (in any language) for this particular vocabulary term
     */
    protected $description;
    
    /**
     * Constructor
     *
     * The associative array $data varies depending on the object being created, but is always consistent
     * between toArray() and fromArray() for each object. By and large, outside an object, nothing cares about
     * the internal structure of the $data array. The standard way to create one of these objects is to
     * instantiate with no $data, and then use the getters to set the object's properties.
     *
     * @param string[][] $data optional Associative array of data to fill this
     *                                  object with.
     */
    public function __construct($data = null) {
        if ($data != null && is_array($data))
            $this->fromArray($data);
    }

    /**
     * Set the type for this vocabulary term. Objects using this term will match their type against this. User
     * interface will use this constrain vocabulary term selection only to appropriate values.
     * 
     * @param string $type Set the vocabulary type of this Term.
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get the type for this vocabulary term. Objects using this term will match their type against this. User
     * interface will use this constrain vocabulary term selection only to appropriate values.
     * 
     * @return string The vocabulary type of this Term.
     */
    public function getType($type)
    {
        return $this->type;
    }

    /**
     * Get the ID of this vocab term
     *
     *  @return int ID of this vocab term
     */
    public function getID() {
        return $this->id;
    }

    /**
     * Set the ID of this vocab term
     *
     * @param int $id ID to assign this vocab term
     */
    public function setID($id) {
        $this->id = $id;
    }
    
    /**
     * Get the term of this vocab term
     *
     *  @return string term of this vocab term
     */
    public function getTerm() {
        return $this->term;
    }

    /**
     * Set the term of this vocab term
     *
     * @param string $term Term for this vocab term
     */
    public function setTerm($term) {
        $this->term = $term;
    }

    /**
     * Get the URI of this vocab term
     *
     *  @return string URI of this vocab term
     */
    public function getURI() {
        return $this->uri;
    }

    /**
     * Set the URI of this vocab term
     *
     * @param string $term URI for this vocab term
     */
    public function setURI($uri) {
        $this->uri = $uri;
    }
    
    /**
     * Get the description of this vocab term
     *
     *  @return string description of this vocab term
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Set the description of this vocab term
     *
     * @param string $term Description for this vocab term
     */
    public function setDescription($description) {
        $this->description = $description;
    }

    /**
     * Check whether or not this term object is empty (all null values).
     *
     * @return boolean True if the term is empty, false otherwise.
     */
    public function isEmpty() {
        if ($this->uri == null && $this->description == null &&
            $this->id == null && $this->term == null)
            return true;
        return false;
    }

    /**
     * Required method to convert this term structure to an array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This object as an associative array
     */
    public function toArray($shorten = true) {
        $return = array(
            'id' => $this->getID(),
            'term' => $this->getTerm(),
            'uri' => $this->getURI(),
            'description' => $this->getDescription()
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
     * Required method to import an array into this term structure
     *
     * @param string[][] $data The data for this object in an associative array
     */
    public function fromArray($data) {
            
        unset($this->id);
        if (isset($data["id"]))
            $this->id = $data["id"];
        else
            $this->id = null;

        unset($this->term);
        if (isset($data["term"]))
            $this->term = $data["term"];
        else
            $this->term = null;

        unset($this->uri);
        if (isset($data["uri"]))
            $this->uri = $data["uri"];
        else
            $this->uri = null;

        unset($this->description);
        if (isset($data["description"]))
            $this->description = $data["description"];
        else
            $this->description = null;
    }

    /**
     * Convert term structure to JSON
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string JSON encoding of this object
     */
    public function toJSON($shorten = true) {
        return json_encode($this->toArray($shorten), JSON_PRETTY_PRINT);
    } 

    /**
     * Prepopulate term structure from the given JSON
     *
     * @param string $json JSON encoding of this object
     * @return boolean true on success, false on failure
     */
    public function fromJSON($json) {
        $data = json_decode($json, true);
        $return = $this->fromArray($data);
        unset($data);
        return $return;
    } 
    

}

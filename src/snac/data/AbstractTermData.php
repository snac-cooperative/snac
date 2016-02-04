<?php

/**
 * Abstract Class that contains methods to handle controlled vocab-containing
 * objects.  For example, Gender, OtherRecordIDs, etc, which must be versioned
 * but also contain links to controlled vocabularies.
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
 * Absract data class to hold terms. Term objects do not extend this class. A Term object is a
 *
 * Abstract class that extends AbstractData and also hold terms
 *
 * @author Robbie Hott
 */
abstract class AbstractTermData extends AbstractData{

    /**
     * This matches up to Term->getType() and field vocabulary.type which is the type valid for an
     * instance. Set this in the constructor of any extending classes. There should be no setter because this
     * read only for data objects.
     *
     * There was a protected $dataType here, but $dataType is in the Term object and we do not want two
     * copies. There was a setter (below, commented out) but no getter which suggests something.
     *
     * If you need the data type, get it from the Term object $foo->getTerm()->getDataType();
     *
     * Data type should only ever be used in special circumstances: building the initial vocabulary, building the
     * UI for vocab term selection. I'm pretty sure that any other use is misguided, but if you find a valid use,
     * please update this comment and explain.
     *
     * As of Jan 29 2016 the list includes: record_type, script_code, entity_type, event_type, name_type,
     * occupation, language_code, gender, nationality, maintenance_status, agent_type, document_role,
     * document_type, function_type, function, subject, date_type, relation_type, place_match, source_type
     *
     * This is not the same as the json key 'dataType'.
     */
    protected $validType;

    /**
     * @var \snac\data\Term $term The term for this object 
     */
    protected $term;

    /**
     * Return the valid type string for this object. Use it to constrain vocabulary selects
     *
     * @return string $validType The valid vocabulary type.
     *
     */
    public function getValidType()
    {
        return $this->validType;
    }

    /**
     * Redundant, see comment for $dataType above.
     *
     * Set the data type for this object. For some reason, this was proctected. This is the vocabulary type,
     * and high level code that is creating Term objects needs to set this.
     *
     * This is not the json $data['dataType'].
     *
     * @param string $dataType The vocabulary data type for this object
     */
    /* 
     * public function setDataType($dataType) {
     *     $this->dataType = $dataType;
     * }
     */

    /**
     * Get the term of this object
     *
     *  @return \snac\data\Term term of this object
     */
    public function getTerm() {
        return $this->term;
    }

    /**
     * Set the term of this object
     *
     * @param \snac\data\Term $term Term for this object
     */
    public function setTerm($term) {
        $this->term = $term;
    }

    /**
     * Required method to convert this term structure to an array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This object as an associative array
     */
    public function toArray($shorten = true) {
        $return = array(
            'dataType' => 'term',
            'term' => $this->getTerm() == null ? null : $this->getTerm()->toArray($shorten)
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
     * Required method to import an array into this term structure.
     *
     * @param string[][] $data The data for this object in an associative array
     */
    public function fromArray($data) {

        if (!isset($data["dataType"]) || $data["dataType"] != 'term')
            return false;

            
        parent::fromArray($data);
        
        unset($this->term);
        if (isset($data["term"]))
            $this->term = new \snac\data\Term($data["term"]);
        else
            $this->term = null;
    }

}

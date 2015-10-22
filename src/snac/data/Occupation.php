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
class Occupation {
    
    /**
     * @var string Occupation controlled vocabulary term
     */
    private $term = null;
    /**
     * @var string Vocabulary source for the occupation
     */
    private $vocabularySource = null;
    /**
     * @var \snac\data\SNACDate Date range for the occupation
     */
    private $dates = null;
    /**
     * @var string Note attached to occupation
     */
    private $note = null;
    
    /**
     * Set the occupation controlled vocabulary name
     * 
     * @param string $term The occupation term
     */
    public function setTerm($term) {
        $this->term = $term;
    }
    
    /**
     * Set the date range
     * @param \snac\data\SNACDate $date Date object for the range
     */
    public function setDateRange($date) {
        $this->dates = $date;
        
    }
    
    /**
     * Set the vocabulary source
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
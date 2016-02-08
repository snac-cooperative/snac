<?php
/**
 * Local Vocabulary Class File
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\util;

/**
 * Local Vocabulary Class
 *
 * This class provides access to the vocabulary table stored in postgres
 * as a list of id, value, uri, and description pairs.  It allows the
 * seaching for a Term object from a string of the term or ID.
 *
 * @author Robbie Hott
 *        
 */
class LocalVocabulary implements \snac\util\Vocabulary {

    /**
     * @var string[][] The vocabulary array, preloaded in the constructor
     */
    private $vocab = null;

    /**
     * Constructor
     *
     * Loads all the vocabulary from the database, then organizes it by type
     * to aid in lookups.
     */
    public function __construct() {
        $db = new \snac\server\database\DBUtil();
        $vocab = $db->getAllVocabulary();
        // Fix up the vocabulary into a nested array
        foreach($vocab as $v) {
            if (!isset($this->vocab[$v["type"]]))
                $this->vocab[$v["type"]] = array();
            array_push($this->vocab[$v["type"]], 
                array(
                    "id"=>$v["id"], 
                    "value"=>$v["value"],
                    "uri"=>$v["uri"],
                    "description"=>$v["description"]));
        }
    }


    /**
     * Get a Term by string value/term
     *
     * @param string $type The type of controlled vocab
     * @param string $value The value of the term to search for
     * @return \snac\data\Term The Term object for the value
     */
    public function getTermByValue($value, $type) {
        $term = new \snac\data\Term();
        if (isset($this->vocab[$type]))
            foreach ($this->vocab[$type] as $k => $v) {
                if ($v["value"] == $value) {
                    $term->setTerm($value);   
                    $term->setID($v["id"]);
                    $term->setURI($v["uri"]);
                    $term->setDescription($v["description"]);
                    return $term;
                }
            }
        return $term;
    }

    /**
     * Get a Term by integer id 
     *
     * @param string $type The type of controlled vocab
     * @param string $id The persistent ID (int) for the term
     * @return \snac\data\Term The Term object for the id
     */
    public function getTermByID($id, $type) {
        $term = new \snac\data\Term();
        if (isset($this->vocab[$type]))
            if (isset($this->vocab[$type][$id])) {
                $v = $this->vocab[$type][$id];
                $term->setTerm($v["value"]);   
                $term->setID($v["id"]);
                $term->setURI($v["uri"]);
                $term->setDescription($v["description"]);
                return $term;
            }
        return $term;
    }
}

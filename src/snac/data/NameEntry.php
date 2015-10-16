<?php
/**
 * Name Entry File
 *
 * Contains the information about an individual name entry.
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
 * NameEntry Class
 *
 * Storage class for name entries.
 *
 * @author Robbie Hott
 *        
 */
class NameEntry {

    /**
     *
     * @var string Original name given in this entry
     */
    private $original;

    /**
     *
     * @var float Preference score given to this entry
     */
    private $preferenceScore;

    /**
     *
     * @var string[][] Contributors providing this name entry including their type for this name entry
     */
    private $contributors;

    /**
     * Constructor
     */
    public function __construct() {

        $this->contributors = array ();
    }

    /**
     * Set the original name.
     * 
     * @param string $original Original name
     */
    public function setOriginal($original) {

        $this->original = $original;
    }

    /**
     * Add contributor to the list of contributors.
     * 
     * @param string $type Type associated with this name entry
     * @param string $name Name of the contributor
     */
    public function addContributor($type, $name) {

        array_push($this->contributors, 
                array (
                        "type" => $type,
                        "contributor" => $name
                ));
    }

    /**
     * Set the preference score.
     * 
     * @param float $score Preference score associated with this name entry
     */
    public function setPreferenceScore($score) {

        $this->preferenceScore = $score;
    }
}
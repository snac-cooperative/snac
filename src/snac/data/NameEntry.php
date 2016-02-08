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
class NameEntry extends AbstractData {

    /**
     * Original string for the name
     * 
     * From EAC-CPF tag(s):
     * 
     * * nameEntry/part
     *
     * @var string Original name given in this entry
     */
    private $original;

    /**
     * Preference Score
     * 
     * From EAC-CPF tag(s):
     * 
     * * nameEntry/@preferenceScore
     * 
     * @var float Preference score given to this entry
     */
    private $preferenceScore;

    /**
     * Contributor List
     * 
     * From EAC-CPF tag(s):
     * 
     * 'type' as a string:  
     * * nameEntry/alternativeForm
     * * nameEntry/authorizedForm
     *
     * 'contributor' name value as a string
     *
     * @var \snac\data\Contributor[] List of Contributor
     */
    private $contributors;
    
    /**
     * Language
     * 
     * From EAC-CPF tag(s):
     * 
     * * nameEntry/@lang
     * * nameEntry/@scriptcode
     * 
     * @var \snac\data\Language Language of the entry
     */
    private $language;
    
    /**
     * Constructor.
     *
     * @param string[] $data A list of data suitable for fromArray(). This exists for use by internal code to
     * send objects around the system, not for generally creating a new object.
     *
     * @return NameEntry object
     * 
     */
    public function __construct($data = null) {

        $this->contributors = array ();
        $this->setMaxDateCount(1);
        parent::__construct($data);
    }
    
    /**
     * Get the original 
     * 
     * Get the original (full combined nameString/header) for this name Entry 
     *
     * @return string Original name given in this entry
     *
     *
     */
    public function getOriginal()
    {
        return $this->original;
    }

    /**
     * Get the SNAC preference score
     * 
     *  Get the preference score for display of this name Entry
     *
     * @return float Preference score given to this entry
     *
     *
     */ 
    public function getPreferenceScore()
    {
        return $this->preferenceScore;
    }

    /**
     * Get the list of contributors for this name entry 
     *
     * @return \snac\data\Contributor[] Contributors providing this name entry including their type for this name entry
     *
     */
    public function getContributors()
    {
        return $this->contributors;
    }

    /**
     * Get the language that this name entry is written in (language and script) 
     *
     * @return \snac\data\Language Language of the entry. If you then call the Language object's getLanguage()
     * it returns a Term object. Language getScript() returns a Term object for the script.
     *
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Returns this object's data as an associative array. 
     * 
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = true) {
        $return = array(
            "dataType" => "NameEntry",
            "original" => $this->original,
            "preferenceScore" => $this->preferenceScore,
            "contributors" => array(),
            "language" => $this->language == null ? null : $this->language->toArray($shorten),
        );
        

        foreach ($this->contributors as $i => $v)
            $return["contributors"][$i] = $v->toArray($shorten);

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
        if (!isset($data["dataType"]) || $data["dataType"] != "NameEntry")
            return false;
       
        parent::fromArray($data);
            
        if (isset($data["original"]))
            $this->original = $data["original"];
        else
            $this->original = null;

        if (isset($data["preferenceScore"]))
            $this->preferenceScore = $data["preferenceScore"];
        else
            $this->preferenceScore = null;
        
        unset($this->contributors);
        $this->contributors = array();
        if (isset($data["contributors"]))
            foreach ($data["contributors"] as $i => $entry)
                if ($entry != null)
                    $this->contributors[$i] = new \snac\data\Contributor($entry);

                
        if (isset($data["language"]) && $data["language"] != null) 
            $this->language = new Language($data["language"]);
        else
            $this->language = null;

        return true;
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
     * Set the language
     * 
     * @param \snac\data\Language $lang Language
     */
    public function setLanguage($lang) {
        $this->language = $lang;
    }
    
    /**
     * Add contributor to the list of contributors.
     * 
     * @param \snac\data\Contributor $contributor Contributor object
     */
    public function addContributor($contributor) {

        array_push($this->contributors, $contributor);
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

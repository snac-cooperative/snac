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
 * See the abstract parent class for common methods setDBInfo() and getDBInfo().
 *
 * Storage class for name entries.
 *
 * @author Robbie Hott
 *        
 */
class NameEntry extends AbstractData {

    /**
     * From EAC-CPF tag(s):
     * 
     * * nameEntry/part
     *
     * @var string Original name given in this entry
     */
    private $original;

    /**
     * From EAC-CPF tag(s):
     * 
     * * nameEntry/@preferenceScore
     * 
     * @var float Preference score given to this entry
     */
    private $preferenceScore;

    /**
     * From EAC-CPF tag(s):
     *'type' as a string:  
     * * nameEntry/alternativeForm
     * * nameEntry/authorizedForm
     *
     * 'contributor' name value as a string
     *
     * Stored as:
     * ```
     * [ [ "type"=> "alternativeForm", "contributor"=>val ], ... ] 
     * ```
     * @var string[][] Contributors providing this name entry including their type for this name entry
     */
    private $contributors;
    
    /**
     * From EAC-CPF tag(s):
     * 
     * * nameEntry/@lang
     * 
     * @var string Language of the entry
     */
    private $language;
    
    /**
     * From EAC-CPF tag(s):
     * 
     * * nameEntry/@scriptcode
     * 
     * @var string Script code of the entry
     */
    private $scriptCode;
    
    /**
     * From EAC-CPF tag(s):
     * 
     * * nameEntry/useDates
     * 
     * @var \snac\data\SNACDate Use dates of the name entry
     */
    private $useDates;

    /**
     * Constructor.  See the abstract parent class for common methods setDBInfo() and getDBInfo().
     *
     * @param string[] $data A list of data suitable for fromArray(). This exists for use by internal code to
     * send objects around the system, not for generally creating a new object.
     *
     * @return NameEntry object
     * 
     */
    public function __construct($data = null) {

        $this->contributors = array ();
        parent::__construct($data);
    }
    
    /**
     * Getter for $this->original
     *
     * @return string Original name given in this entry
     *
     *
     */
    function getOriginal()
    {
        return $this->original;
    }

    /**
     * Getter for $this->preferenceScore
     *
     * @return float Preference score given to this entry
     *
     *
     */ 
    function getPreferenceScore()
    {
        return $this->preferenceScore;
    }

    /**
     * getter for $this->contributors
     *
     * @return string[][] Contributors providing this name entry including their type for this name entry
     *
     */
    function getContributors()
    {
        return $this->contributors;
    }

    /**
     * getter for $this->language
     *
     * @return string Language of the entry
     *
     */
    function getLanguage()
    {
        return $this->language;
    }

    /**
     * getter for $this->scriptCode
     *
     * @return string Script code of the entry
     *
     */
    function getScriptCode()
    {
        return $this->scriptCode;
    }

    /**
     * getter for $this->useDates
     *
     *
     * @return \snac\data\SNACDate[] Use dates of the name entry, returned as an array of SNACDate objects.
     *
     */
    function getUseDates()
    {
        return $this->useDates;
    }



    /**
     * Returns this object's data as an associative array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = true) {
        $return = array(
            "dataType" => "NameEntry",
            'id' => $this->getID(),
            'version' => $this->getVersion(),
            "original" => $this->original,
            "preferenceScore" => $this->preferenceScore,
            "contributors" => $this->contributors,      // already an array
            "language" => $this->language,
            "scriptCode" => $this->scriptCode,
            "useDates" => $this->useDates == null ? null : $this->useDates->toArray($shorten)
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
     * Replaces this object's data with the given associative array
     *
     * @param string[][] $data This objects data in array form
     * @return boolean true on success, false on failure
     */
    public function fromArray($data) {
        if (!isset($data["dataType"]) || $data["dataType"] != "NameEntry")
            return false;
        
        unset($this->id);
        if (isset($data["id"]))
            $this->id = $data["id"];
        else
            $this->id = null;

        unset($this->version);
        if (isset($data["version"]))
            $this->version = $data["version"];
        else
            $this->version = null;
        
        if (isset($data["original"]))
            $this->original = $data["original"];
        else
            $this->original = null;

        if (isset($data["preferenceScore"]))
            $this->preferenceScore = $data["preferenceScore"];
        else
            $this->preferenceScore = null;

        if (isset($data["contributors"]))
            $this->contributors = $data["contributors"];
        else
            $this->contributors = null;

        if (isset($data["language"]))
            $this->language = $data["language"];
        else
            $this->language = null;

        if (isset($data["scriptCode"]))
            $this->scriptCode = $data["scriptCode"];
        else
            $this->scriptCode = null;

        if (isset($data["useDates"]))
            $this->useDates = new SNACDate($data["useDates"]);
        else
            $this->useDates = null;


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
     * @param string $lang Language
     */
    public function setLanguage($lang) {
        $this->language = $lang;
    }
    
    /**
     * Set the script code of the name entry
     * 
     * @param string $code Script code
     */
    public function setScriptCode($code) {
        $this->scriptCode = $code;
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
     * Set the use dates of the entry
     * 
     * @param \snac\data\SNACDate $date Dates
     */
    public function setUseDates($date) {
        $this->useDates = $date;
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

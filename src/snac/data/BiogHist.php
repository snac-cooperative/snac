<?php

/**
 * Snac BiogHist File
 *
 * Contains the data class for biographical histories
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
 * BiogHist data storage class
 *
 * @author Robbie Hott
 *        
 */
class BiogHist extends AbstractData {

    /**
     * 
     * @var \snac\data\Language The language this biogHist was written in
     */
    private $language;

    /**
     * @var string Text/XML contents of this biogHist. 
     */
    private $text;

    /**
     * Constructor. 
     *
     * Mostly this exists to call setMaxDateCount() to a reasonable number for this class.
     *
     */ 
    public function __construct($data = null) {
        $this->setMaxDateCount(0);
        parent::__construct($data);
    }


    /**
     * Get the language this biogHist was written in
     * 
     * @return \snac\data\Language Language of this BiogHist
     *
     */
    public function getLanguage()
    {
        return $this->language;
    }
    
    /**
     * Get the text/xml of this biogHist
     *
     * @return string The full biogHist 
     *
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Returns this object's data as an associative array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = true) {

        if (gettype($this->language) == 'array')
        {
            $langJSON = array();
            foreach($this->language as $lang)
            {
                /*
                 * Convert a list of objects into a list of list. The inner list is an assoc array instead of
                 * an object.
                 */ 
                array_push($langJSON, $lang->toArray());
            }
        }
        else
        {
            /*
             * Our practice is for empty data to be null, even lists. Empty lists don't exist in the json.
             */ 
            $langJSON = null;
        }

        $return = array(
            "dataType" => "BiogHist",
            "language" => $langJSON,
            "text" => $this->text
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
     * Replaces this object's data with the given associative array
     *
     * @param string[][] $data This objects data in array form
     * @return boolean true on success, false on failure
     */
    public function fromArray($data) {
        if (!isset($data["dataType"]) || $data["dataType"] != "BiogHist")
            return false;

        parent::fromArray($data);

        if (isset($data["language"]) && gettype($data['language']) == 'array')
        {
            /*
             * Can we use gettype() and skip the isset? Will php balk at missing indexes?
             * 
             * We could call array_push() for $this->language, but it seems better practice to use our own
             * setter.
             */ 
            foreach($data['language'] as $language)
            {
                $this->addLanguage(new \snac\data\Language($language));
            }
        }

        if (isset($data["text"]))
            $this->text = $data["text"];
        else
            $this->text = null;

        return true;
    }

    /**
     * Set the language of this BiogHist.
     * 
     * @param \snac\data\Language $language the language of this BiogHist
     */
    public function setLanguage(\snac\data\Language $language) {

        $this->language = $language;
    }

    /**
     * Set the text/xml of this BiogHist
     * 
     * @param string $text The full text/xml of this biogHist
     */
    public function setText($text) {

        $this->text = $text;
    }
}

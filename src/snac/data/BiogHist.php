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
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
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
     * @param string[] $data optional Array with data to populate this object
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
     * Append to this BiogHist
     *
     * Appends the information in the given biog hist to this one.  The text field is appended
     * to this one, the SCMs are merged, and if there is no language in this biogHist,
     * then the language is copied.  Otherwise, the given biogHist's language will be dropped.
     *
     * @param  \snac\data\BiogHist $biogHist BiogHist to append
     */
    public function append($biogHist) {
        if ($biogHist === null) {
            return;
        }

        // Append the text objects
        $this->text .= $biogHist->text;

        // Combine SCMs
        $this->snacControlMetadata = array_merge($this->snacControlMetadata, $biogHist->snacControlMetadata);

        // If this biogHist's language is null or empty, pull in the given biogHist's language
        if ($this->language === null || $this->language->isEmpty()) {
            $this->language = $biogHist->language;
        }
    }

    /**
     * XML-Format this BiogHist
     *
     * Updates this BiogHist to add XML elements, including the <biogHist> tag and any <p> tags necessary.
     */
    public function formatXML() {
        $orig = $this->text;

        // check for <p> tags, and if none, then add them to each line
        if (strpos($orig, "<p>") === false) {
            $new = "";
            foreach (preg_split("/((\r?\n)|(\r\n?))/", $orig) as $par) {
                if (!empty(trim($par))) {
                    $new .= "<p>".htmlentities(trim($par), ENT_COMPAT|ENT_XML1)."</p>\n";  
                }
            } 
            $orig = $new;
        }

        // check for <biogHist> tag and add if needed
        if (strpos($orig, "<biogHist>") === false) {
            $orig = "<biogHist>\n$orig\n</biogHist>";
        }

        $this->text = $orig;
    }

    /**
     * Returns this object's data as an associative array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = true) {
        $return = array(
            "dataType" => "BiogHist",
            "language" => $this->language == null ? null : $this->language->toArray($shorten),
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

        if (isset($data["language"]) && $data["language"] != null)
            $this->language = new Language($data["language"]);
        else
            $this->language = null;

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
    public function setLanguage($language) {

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

    /**
     * To String
     *
     * Converts this object to a human-readable summary string.  This is enough to identify
     * the object on sight, but not enough to discern programmatically.
     *
     * @return string A human-readable summary string of this object
     */
    public function toString() {
        return "BiogHist";
    }

    /**
     *
     * {@inheritDoc}
     *
     * @param \snac\data\BiogHist $other Other object
     * @param boolean $strict optional Whether or not to check id, version, and operation
     * @param boolean $checkSubcomponents optional Whether or not to check SNACControlMetadata, nameEntries contributors & components
     * @return boolean true on equality, false otherwise
     *
     * @see \snac\data\AbstractData::equals()
     */
    public function equals($other, $strict = true, $checkSubcomponents = true) {

        if ($other == null || !($other instanceof \snac\data\BiogHist))
            return false;

        if (! parent::equals($other, $strict, $checkSubcomponents))
            return false;

        if ($this->getText() != $other->getText())
            return false;

        if (($this->getLanguage() != null && !$this->getLanguage()->equals($other->getLanguage(), $strict, $checkSubcomponents)) ||
                ($this->getLanguage() == null && $other->getLanguage() != null))
            return false;

        return true;
    }

    /**
     * Cleanse all sub-elements
     *
     * Removes the ID and Version from sub-elements and updates the operation to be
     * INSERT.  If the operation is specified by the parameter, this method
     * will use that operation instead of INSERT.
     *
     * @param string $operation optional The operation to use (default is INSERT)
     */
    public function cleanseSubElements($operation=null) {
        $newOperation = \snac\data\AbstractData::$OPERATION_INSERT;
        if ($operation !== null) {
            $newOperation = $operation;
        }

        parent::cleanseSubElements($newOperation);

        if (isset($this->language) && $this->language != null) {
            $this->language->setID(null);
            $this->language->setVersion(null);
            $this->language->setOperation($newOperation);
            $this->language->cleanseSubElements($newOperation);
        }
    }
}

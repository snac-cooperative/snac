<?php
/**
 * Snac Source File
 *
 * Contains the data class for source information
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
 * Source
 *
 * A "source" is a citation source, and has qualities of an authority file although every
 * source is independent, even if it seems to be a duplicate.  This appears to derive from
 * /eac-cpf/control/source in the CPF. Going forward we use it for all sources.  For example,
 * SNACControlMetadata->citation is a Source object. Constellation->sources is a list of sources.
 *
 * @author Robbie Hott
 *        
 */
class Source extends AbstractData {

    /**
     * Language
     * 
     * @var \snac\data\Language The language this source was written in
     */
    private $language;
    
    /**
     * @var string Display name of this source. 
     */
    private $displayName;

    /**
     * @var string Text of this source. 
     */
    private $text;

    /**
     * @var string Note related to this source 
     */
    private $note;

    /**
     * @var string URI of this source 
     */
    private $uri;

    /**
     * @var \snac\data\Term Type of this source
     */
    private $type;
    
    /**
     * Constructor
     * 
     * @param string[] $data optional An array of data to pre-fill this object
     */
    public function __construct($data = null) {
        $this->setMaxDateCount(0);
        parent::__construct($data);
    }

    /**
     * Get Language
     * 
     * Get the language this source was written in
     * 
     * @return \snac\data\Language Language of this source
     *
     */
    public function getLanguage()
    {
        return $this->language;
    }
    
    /**
     * Get the note of this source
     *
     * @return string The note attached to this source
     *
     */
    public function getNote()
    {
        return $this->note;
    }
    
    /**
     * Get the text of this source
     *
     * @return string The description text/xml
     *
     */
    public function getText()
    {
        return $this->text;
    }
    

    /**
     * Get the display name of this source
     *
     * @return string The display name of the source
     *
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * Get the URI of this source
     *
     * @return string The uri of this source
     */
    public function getURI() {
        return $this->uri;
    }

    /**
     * Get the type of this source
     *
     * @return \snac\data\Term The type of this source
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Returns this object's data as an associative array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = true) {
        $return = array(
            "dataType" => "Source",
            "language" => $this->language == null ? null : $this->language->toArray($shorten),
            "type" => $this->type == null ? null : $this->type->toArray($shorten),
            "displayName" => $this->displayName,
            "text" => $this->text,
            "note" => $this->note,
            "uri" => $this->uri
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
        if (!isset($data["dataType"]) || $data["dataType"] != "Source")
            return false;

        parent::fromArray($data);

        if (isset($data["language"]) && $data["language"] != null)
            $this->language = new Language($data["language"]);
        else
            $this->language = null;

        if (isset($data["type"]) && $data["type"] != null)
            $this->type = new Term($data["type"]);
        else
            $this->type = null;

        if (isset($data["uri"]))
            $this->uri = $data["uri"];
        else
            $this->uri = null;

        if (isset($data["displayName"]))
            $this->displayName = $data["displayName"];
        else
            $this->displayName = null;

        if (isset($data["text"]))
            $this->text = $data["text"];
        else
            $this->text = null;

        if (isset($data["note"]))
            $this->note = $data["note"];
        else
            $this->note = null;

        return true;

    }

    /**
     * Set the language of this source 
     * 
     * @param \snac\data\Language $language the language of this source
     */
    public function setLanguage($language) {

        $this->language = $language;
    }

    /**
     * Set the text/xml of this Source
     * 
     * @param string $text The full text/xml of this source
     */
    public function setText($text) {

        $this->text = $text;
    }
    
    /**
     * Set the display name of this Source
     *
     * @param string $displayName The display name of this source
     */
    public function setDisplayName($displayName) {
    
        $this->displayName = $displayName;
    }

    /**
     * Set the note of this Source
     * 
     * @param string $note the note attached to this source
     */
    public function setNote($note) {

        $this->note = $note;
    }

    /**
     * Set the URI of this source
     *
     * @param string $uri The uri
     */
    public function setURI($uri) {
        $this->uri = $uri;
    }

    /**
     * Set the type of this source
     *
     * @param \snac\data\Term $type the type of this source
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     *
     * {@inheritDoc}
     *
     * @param \snac\data\Source $other Other object
     * @param boolean $strict optional Whether or not to check id, version, and operation
     * @return boolean true on equality, false otherwise
     *       
     * @see \snac\data\AbstractData::equals()
     */
    public function equals($other, $strict = true) {

        if ($other == null || ! ($other instanceof \snac\data\Source))
            return false;
        
        if (! parent::equals($other, $strict))
            return false;

        if ($strict && ($this->getDisplayName() != $other->getDisplayName()))
            return false;
        if ($this->getText() != $other->getText())
            return false;
        if ($this->getURI() != $other->getURI())
            return false;
        if ($this->getNote() != $other->getNote())
            return false;
        
        
        if (($this->getType() != null && ! $this->getType()->equals($other->getType())) ||
                 ($this->getType() == null && $other->getType() != null))
            return false;
            
        if (($this->getLanguage() != null && ! $this->getLanguage()->equals($other->getLanguage(), $strict)) ||
                 ($this->getLanguage() == null && $other->getLanguage() != null))
            return false;
        
        return true;
    }

}

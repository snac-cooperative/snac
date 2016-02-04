<?php

/**
 * Snac Source. A "source" is a source in the sense of a citation, and has qualities of an authority file.
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
 * Source data storage class. Snac Source File. A "source" is a citation source, and has qualities of an
 * authority file.  This appears to derive from /eac-cpf/control/source in the CPF. Going forward we use it
 * for all sources which merit any level of authority control. For example, SNACControlMetadata->citation is a
 * Source object. Constellation->sources is a list of sources. 
 *
 * @author Robbie Hott
 *        
 */
class Source extends AbstractData {

    /**
     * 
     * @var \snac\data\Language The language this source was written in
     */
    private $language;

    /**
     * @var string text of this source. 
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

        if (isset($data["language"]))
            $this->language = new Language($data["language"]);
        else
            $this->language = null;

        if (isset($data["type"]))
            $this->type = new Term($data["type"]);
        else
            $this->type = null;

        if (isset($data["uri"]))
            $this->uri = $data["uri"];
        else
            $this->uri = null;

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
}

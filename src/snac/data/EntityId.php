<?php

/**
 * Snac EntityId File
 *
 * Contains the data class for entityId information
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
 * EntityId data storage class
 *
 * @author Robbie Hott
 *
 */
class EntityId extends AbstractData {

    /**
     * @var string text of this entityId
     */
    private $text;

    /**
     * @var string URI of this entityId
     */
    private $uri;

    /**
     * @var \snac\data\Term Type of this entityId
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
     * Get the text of this entityId
     *
     * @return string The description text/xml
     *
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Get the URI of this entityId
     *
     * @return string The uri of this entityId
     */
    public function getURI() {
        return $this->uri;
    }

    /**
     * Get the type of this entityId
     *
     * @return \snac\data\Term The type of this entityId
     */
    public function getType() {
        return $this->type;
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
        return "EntityID: " . $this->text;
    }

    /**
     * Returns this object's data as an associative array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = true) {
        $return = array(
            "dataType" => "EntityId",
            "type" => $this->type == null ? null : $this->type->toArray($shorten),
            "text" => $this->text,
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
        if (!isset($data["dataType"]) || $data["dataType"] != "EntityId")
            return false;

        parent::fromArray($data);

        if (isset($data["type"]) && $data["type"] != null)
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

        return true;

    }

    /**
     * Set the text/xml of this EntityId
     *
     * @param string $text The full text/xml of this entityId
     */
    public function setText($text) {

        $this->text = $text;
    }

    /**
     * Set the URI of this entityId
     *
     * @param string $uri The uri
     */
    public function setURI($uri) {
        $this->uri = $uri;
    }

    /**
     * Set the type of this entityId
     *
     * @param \snac\data\Term $type the type of this entityId
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     *
     * {@inheritDoc}
     *
     * @param \snac\data\EntityId $other Other object
     * @param boolean $strict optional Whether or not to check id, version, and operation
     * @param boolean $checkSubcomponents optional Whether or not to check SNACControlMetadata, nameEntries contributors & components
     * @return boolean true on equality, false otherwise
     *
     * @see \snac\data\AbstractData::equals()
     */
    public function equals($other, $strict = true, $checkSubcomponents = true) {

        if ($other == null || ! ($other instanceof \snac\data\EntityId))
            return false;

        if (! parent::equals($other, $strict, $checkSubcomponents))
            return false;

        if ($this->getText() != $other->getText())
            return false;
        if ($this->getURI() != $other->getURI())
            return false;

        if (($this->getType() != null && ! $this->getType()->equals($other->getType())) ||
                 ($this->getType() == null && $other->getType() != null))
            return false;

        return true;
    }
}

<?php

/**
 * Abstract Class that contains methods to handle text- or xml-only containing
 * objects.  For example, StructureOrGenealogy, Mandate, etc, which must be versioned
 * but also contain text.
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
 * Text-holding Abstract Class
 *
 * Abstract class that extends AbstractData and also holds a text string
 *
 * @author Robbie Hott
 */
abstract class AbstractTextData extends AbstractData{


    /**
     * @var string $dataType The type of this data object.
     *
     * This is the json $data['dataType'].
     */
    protected $dataType;

    /**
     * var string Text of this object
     */
    protected $text;

    /**
     * Set the data type for this object
     *
     * @param string $dataType the data type for this object
     */
    protected function setDataType($dataType) {
        $this->dataType = $dataType;
    }

    /**
     * Get the text of this object
     *
     *  @return string text of this object
     */
    public function getText() {
        return $this->text;
    }

    /**
     * Set the text of this object
     *
     * @param string $text text this object
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
        return $this->dataType . ": " . $this->getText();
    }

    /**
     * Required method to convert this term structure to an array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This object as an associative array
     */
    public function toArray($shorten = true) {
        $return = array(
            'dataType' => $this->dataType,
            'text' => $this->getText()
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
     * Required method to import an array into this term structure
     *
     * @param string[][] $data The data for this object in an associative array
     */
    public function fromArray($data) {

        if (!isset($data["dataType"]) || $data["dataType"] != $this->dataType)
            return false;


        parent::fromArray($data);

        unset($this->text);
        if (isset($data["text"]))
            $this->text = $data["text"];
        else
            $this->text = null;
    }

    /**
     *
     * {@inheritDoc}
     *
     * @param \snac\data\AbstractTextData $other Other object
     * @param boolean $strict optional Whether or not to check id, version, and operation
     * @return boolean true on equality, false otherwise
     *
     * @see \snac\data\AbstractData::equals()
     */
    public function equals($other, $strict = true) {

        if ($other == null || ! ($other instanceof \snac\data\AbstractTextData))
            return false;

        if (!parent::equals($other, $strict))
            return false;

        if ($this->getText() != $other->getText())
            return false;

        return true;
    }

}

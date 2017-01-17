<?php

/**
 * Image Class file
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
 * Image Class
 *
 * Image class that holds a URL and citation information
 *
 * @author Robbie Hott
 */
class Image extends AbstractData {

    /**
     * var string URL
     */
    protected $url;

    /**
     * var string Citation HTML
     */
    protected $citation;

    /**
     * Get the citation of this object
     *
     *  @return string citation of this object
     */
    public function getCitation() {
        return $this->citation;
    }

    /**
     * Set the citation of this object
     *
     * @param string $citation citation this object
     */
    public function setCitation($citation) {
        $this->citation = $citation;
    }

    /**
     * Get the url of this object
     *
     *  @return string url of this object
     */
    public function getURL() {
        return $this->url;
    }

    /**
     * Set the url of this object
     *
     * @param string $url url this object
     */
    public function setURL($url) {
        $this->url = $url;
    }
    /**
     * Required method to convert this term structure to an array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This object as an associative array
     */
    public function toArray($shorten = true) {
        $return = array(
            'dataType' => "Image",
            'url' => $this->getURL(),
            'citation' => $this->getCitation()
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

        if (!isset($data["dataType"]) || $data["dataType"] != "Image")
            return false;


        parent::fromArray($data);

        unset($this->citation);
        if (isset($data["citation"]))
            $this->citation = $data["citation"];
        else
            $this->citation = null;


        unset($this->url);
        if (isset($data["url"]))
            $this->url = $data["url"];
        else
            $this->url = null;
    }

    /**
     *
     * {@inheritDoc}
     *
     * @param \snac\data\Image $other Other object
     * @param boolean $strict optional Whether or not to check id, version, and operation
     * @return boolean true on equality, false otherwise
     *
     * @see \snac\data\AbstractData::equals()
     */
    public function equals($other, $strict = true) {

        if ($other == null || ! ($other instanceof \snac\data\Image))
            return false;

        if (!parent::equals($other, $strict))
            return false;

        if ($this->getURL() != $other->getURL())
            return false;

        if ($this->getCitation() != $other->getCitation())
            return false;

        return true;
    }

}
